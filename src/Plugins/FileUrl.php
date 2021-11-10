<?php

namespace yzh52521\Flysystem\Obs\Plugins;

use League\Flysystem\Plugin\AbstractPlugin;

class FileUrl extends AbstractPlugin
{
    /**
     * get file url.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'getUrl';
    }

    /**
     * handle.
     *
     * @param $path
     *
     * @return mixed
     */
    public function handle($path)
    {
        return $this->filesystem->getAdapter()->getUrl($path);
    }
}
