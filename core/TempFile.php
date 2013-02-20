<?php

/*
 * This file is part of Kryn.cms.
 *
 * (c) Kryn.labs, MArc Schmidt <marc@Kryn.org>
 *
 * To get the full copyright and license information, please view the
 * LICENSE file, that was distributed with this source code.
 *
 */

namespace Core;

/**
 * SystemFile
 *
 * Class to proxy the file functions to the local file layer on temp folder.
 * Use this class, if you want to modify files of the webserver's temp folder.
 *
 * Does not support external mount points.
 *
 */
class TempFile extends MediaFile
{
    public static $fsObjects = array();


    //not permission check, since file object is only for media/ folder, so for File:: class.
    public static $checkObject = false;


    //we do not support mounts outside of media/.
    public static $checkMounts = false;

    /**
     *
     * Returns the instance of the local file layer.
     *
     * @static
     * @param  string $pPath
     * @return object
     */
    public static function getLayer($pPath = null)
    {
        $class = '\Core\FAL\Local';
        $params['root'] = Kryn::getTempFolder();

        if (static::$fsObjects[$class]) return static::$fsObjects[$class];

        static::$fsObjects[$class] = new $class('', $params);

        return static::$fsObjects[$class];
    }

    public static function getPath($pPath)
    {
        throw new \Exception(t('getPath on TempFile is not possible. Use Core\File::getPath'));
    }

    public static function getUrl($pPath)
    {
        throw new \Exception(t('getUrl on TempFile is not possible. Use Core\File::getUrl'));
    }

    public static function getTrashFiles()
    {
        throw new \Exception(t('getTrashFiles on TempFile is not possible. Use Core\File::getTrashFiles'));
    }

}
