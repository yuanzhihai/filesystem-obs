<?php

namespace yzh52521\Flysystem\Obs;

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToWriteFile;
use Obs\ObsClient;
use Obs\ObsException;

/**
 * Class ObsAdapter
 * @package Obs
 */
class ObsAdapter implements FilesystemAdapter
{

    /**
     * @var ObsClient
     */
    protected $client;

    /**
     * @var
     */
    protected $bucket;

    /**
     * @var
     */
    protected $endpoint;

    /**
     * @var
     */
    protected $cdnDomain;

    /**
     * @var
     */
    protected $ssl;

    /**
     * ObsAdapter constructor.
     * @param array $option
     */
    public function __construct(array $option = [])
    {
        try {
            $this->bucket    = $option['bucket'];
            $this->endpoint  = $option['endpoint'];
            $this->cdnDomain = $option['cdnDomain'];
            $this->ssl       = $option['ssl'];
            $this->client    = new ObsClient($option);
        } catch (ObsException $e) {
            throw $e;
        }
    }


    /**
     * @return string
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }

    public function fileExists(string $path): bool
    {
        return $this->getMetadata($path);
    }

    /**
     * @param string $path
     * @param string $contents
     * @param Config $config
     * @return array|bool|false
     */
    public function write(string $path, string $contents, Config $config): void
    {
        try {
            $this->client->putObject(
                [
                    'Bucket'     => $this->getBucket(),
                    'Key'        => $path,
                    'SourceFile' => $contents
                ]
            );
        } catch (ObsException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage());
        }
    }

    /**
     * @param string $path
     * @param resource $contents
     * @param Config $config
     * @return array|bool|false
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $this->client->putObject(
                [
                    'Bucket' => $this->getBucket(),
                    'Key'    => $path,
                    'Body'   => $contents
                ]
            );
        } catch (ObsException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage());
        }
    }

    /**
     * @param string $path
     * @param string $contents
     * @param Config $config
     * @return array|bool|false
     */
    public function update(string $path, string $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * @param string $path
     * @param resource $resource
     * @param Config $config
     * @return array|bool|false
     */
    public function updateStream(string $path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * @param string $path
     * @param string $destination
     * @param Config $config
     */
    public function rename(string $path, string $destination, Config $config): void
    {
        $this->copy($path, $destination, $config) && $this->delete($path);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->copy($source, $destination, $config) && $this->delete($source);
    }


    public function visibility(string $path): FileAttributes
    {
        $response = $this->client->getObjectAcl($this->bucket);
        return new FileAttributes($path, null, $response);
    }


    /**
     * @param string $path
     * @param string $destination
     * @param Config $config
     */
    public function copy(string $path, string $destination, Config $config): void
    {
        try {
            $this->client->deleteObject(
                [
                    'Bucket'     => $this->getBucket(),
                    'Key'        => $destination,
                    'CopySource' => $this->getBucket() . '/' . $path
                ]
            );
        } catch (ObsException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage());
        }
    }

    /**
     * @param string $path
     */
    public function delete(string $path): void
    {
        try {
            $this->client->deleteObject(
                [
                    'Bucket' => $this->getBucket(),
                    'Key'    => $path
                ]
            );
        } catch (ObsException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage());
        }
    }

    /**
     * @param string $path
     */
    public function deleteDirectory(string $path): void
    {
        $this->delete($path);
    }

    /**
     * @param string $path
     * @param Config $config
     * @return array|bool|false
     */
    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->client->putObject(
                [
                    'Bucket' => $this->getBucket(),
                    'Key'    => $path
                ]
            );
        } catch (ObsException $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage());
        }
    }

    /**
     * @param string $path
     * @return array|bool|false|null
     */
    public function has(string $path)
    {
        return $this->getMetadata($path);
    }

    public function setVisibility(string $path, string $visibility): void
    {

    }

    /**
     * @param string $path
     * @return array|bool|false
     */
    public function read(string $path): string
    {
        try {
            $object = $this->client->getObject(
                [
                    'Bucket' => $this->getBucket(),
                    'Key'    => $path
                ]
            );
        } catch (ObsException $e) {
            return false;
        }

        $object['contents'] = (string)$object['Body'];
        unset($object['Body']);

        return $this->normalizeResponse($object);
    }

    /**
     * @param string $path
     * @return array|bool|false
     */
    public function readStream(string $path)
    {
        try {
            $object = $this->client->getObject(
                [
                    'Bucket'       => $this->getBucket(),
                    'Key'          => $path,
                    'SaveAsStream' => true
                ]
            );
        } catch (ObsException $e) {
            return false;
        }

        $object['stream'] = $object['Body'];
        unset($object['Body']);

        return $this->normalizeResponse($object);
    }

    /**
     * @param string $path
     * @param bool $deep
     * @return array|bool
     */
    public function listContents(string $path, bool $deep): iterable
    {
        try {
            $object = $this->client->listObjects(
                [
                    'Bucket'  => $this->getBucket(),
                    'MaxKeys' => 1000,
                    'Prefix'  => $path,
                    'Marker'  => null
                ]
            );
        } catch (ObsException $e) {
            return false;
        }

        $contents = $object["Contents"];

        if (!count($contents)) {
            return [];
        }

        return array_map(
            function ($entry) {
                return $this->normalizeResponse($entry);
            },
            $contents
        );
    }

    /**
     * @param string $path
     * @return array|bool|false
     */
    public function getMetadata(string $path)
    {
        try {
            $object = $this->client->getObjectMetadata(
                [
                    'Bucket' => $this->getBucket(),
                    'Key'    => $path
                ]
            );
        } catch (ObsException $e) {
            return false;
        }

        return $this->normalizeResponse($object);
    }

    /**
     * @param string $path
     * @return array|bool|false
     */
    public function getSize(string $path)
    {
        $object         = $this->getMetadata($path);
        $object['size'] = $object['ContentLength'];
        return $object;
    }

    public function fileSize(string $path): FileAttributes
    {
        $object = $this->getSize($path);
        return new FileAttributes($path, $object['size']);
    }

    /**
     * @param string $path
     * @return array|bool|false
     */
    public function getMimetype(string $path)
    {
        $object             = $this->getMetadata($path);
        $object['mimetype'] = $object['ContentType'];

        return $object;
    }

    public function mimeType(string $path): FileAttributes
    {
        $object = $this->getMimetype($path);
        return new FileAttributes($path, $object['mimetype']);
    }

    /**
     * @param string $path
     * @return array|bool|false
     */
    public function getTimestamp(string $path)
    {
        return $this->getMetadata($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        $response = $this->getMetadata($path);
        return new FileAttributes($path, null, null, $response['last-modified']);
    }

    /**
     * @param $path
     * @return string
     */
    public function getUrl($path): string
    {
        return ($this->ssl ? 'https://' : 'http://')
            . ($this->cdnDomain === '' ? $this->getBucket() . '.' . $this->endpoint : $this->cdnDomain)
            . '/' . ltrim($path, '/');
    }

    /**
     * @param $object
     * @return array
     */
    public function normalizeResponse($object): array
    {
        $result = ['path' => $object];

        if (isset($object['LastModified'])) {
            $result['timestamp'] = strtotime($object['LastModified']);
        }

        if (isset($object['Size'])) {
            $result['size']  = $object['Size'];
            $result['bytes'] = $object['Size'];
        }

        $type = (substr($result['path'], -1) === '/' ? 'dir' : 'file');

        $result['type'] = $type;

        return $result;
    }

}
