<?php

/*
* This file is part of Kryn.cms.
*
* (c) Kryn.labs, MArc Schmidt <marc@kryn.org>
*
* To get the full copyright and license informations, please view the
* LICENSE file, that was distributed with this source code.
*
*/

/**
 * krynCache
 * A caching class, that provides several ways to cache data
 * with inheritance invalidation mechanism.

 */

class krynCache {

    /**
     * Contains the current type
     * @type string
     */
    public $type;

    /**
     * Contains the current config values
     * @type array
     */
    public $config;


    /**
     * All gets/sets will be cached in this array for faster access
     * during multiple get() calls on the same key
     *
     * @var array
     */
    public $cache = array();

    /**
     * This activates the invalidate() mechanism
     *
     * @type bool
     *
     * If activated, each time get() is called, the function searched
     * for parents based on a exploded string by '_'. If a parent is
     * found is a invalidated cache, the call is ignored and false will be returned.
     * Example: call get('workspace_tables_tableA')
     *          => checks 'workspace_tables' for invalidating (getInvalidate('workspace_tables'))
     *          => if 'workspace_tables' was flagged as invalidate (invalidate('workspace_tables')), return false
     *          => checks 'workspace' for invalidating (getInvalidate('workspace'))
     *          => if 'workspace' was flagged as invalidate (invalidate('workspace')), return false
     * So you can invalidate multiple keys with just one call.

     */
    public $withInvalidationChecks = true;


    /**
     * krynCache class constructor.
     *
     * @param string  $pType                    can be memcached, redis, files, xcache or apc.
     * @param array  $pConfig
     *                                          memcached and redis: array(
     *                                              'servers' => array(
     *                                                  array('ip' => '12.12.12.12', 'port' => 6379
     *                                                  array('ip' => '12.12.12.13', 'port' => 6379
     *                                              )
     *                                            )
     *                                            files: array('files_path' => '<path to store the cached files')
     * @param bool    $pWithInvalidationChecks    activates the invalidating mechanism
     *
     * @access public
     * @throws Exception
     */
    function __construct($pType = 'files', $pConfig = array(), $pWithInvalidationChecks = true) {

        $this->type = $pType;
        $this->config = $pConfig;
        $this->withInvalidationChecks = $pWithInvalidationChecks;

        switch ($this->type) {
            case 'memcached':
                if (!$this->initMemcached()) {
                    throw new Exception('Can not load the memcache(d) class');
                }
                break;
            case 'redis':
                if (!$this->initRedis()) {
                    throw new Exception('Can not load the Redis class');
                }
                break;
            case 'apc':
                if (!function_exists('apc_store')) {
                    throw new Exception('The php apc module is not loaded');
                }
                break;
            case 'xcache':
                if (!function_exists('xcache_get')) {
                    throw new Exception('The php xcache module is not loaded');
                }
                break;
            case 'files':

                if ($this->config['files_path'] == '')
                    $this->config['files_path'] = 'cache/object/';

                if (!is_dir($this->config['files_path'])) {
                    if (!mkdir($this->config['files_path'])) {
                        die('Can not create cache folder: ' . $this->config['files_path']);
                    }
                }
                if (!is_writable($this->config['files_path'])) {
                    die('Cache folder is not writable: ' . $this->config['files_path']);
                }
                break;
        }

    }

    /**
     * Initialize the redis objects
     *
     * @return boolean
     */
    public function initRedis() {

        if (!class_exists('Redis')) return false;

        $this->redis = new Redis;

        foreach ($this->config['servers'] as $server) {
            $this->redis->connect($server['ip'], $server['port'] + 0);
        }

        $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);

        return true;
    }

    /**
     * Initialize the memcached objects
     *
     * @return boolean
     */
    public function initMemcached() {

        if (class_exists('Memcache')) {
            $this->memcache = new Memcache;
            foreach ($this->config['servers'] as $server) {
                $this->memcache->addServer($server['ip'], $server['port'] + 0);
            }
        } else if (class_exists('Memcached')) {
            $this->memcached = new Memcached;
            foreach ($this->config['servers'] as $server) {
                $this->memcached->addServer($server['ip'], $server['port'] + 0);
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Returns the content of the specified cache-key
     *
     * @param string $pCode
     * @param bool   $pWithoutValidationCheck
     *
     * @return ref to object
     */
    public function &get($pCode, $pWithoutValidationCheck = false) {
        global $kcache;

        if (!$this->cache[$pCode]){
            switch ($this->type) {
                case 'memcached':

                    if ($this->memcache) {
                        $this->cache[$pCode] = $this->memcache->get($pCode);
                    } else if ($this->memcached) {
                        $this->cache[$pCode] = $this->memcached->get($pCode);
                    }
                    break;

                case 'redis':

                    $this->cache[$pCode] = $this->redis->get($pCode);
                    break;

                case 'files':

                    $cacheCode = 'krynPhpCache_' . $pCode;
                    $path = $this->config['files_path'] . urlencode($pCode) . '.php';

                    if (!file_exists(PATH . $path)) return false;
                        include(PATH . $path);

                    if ($kcache[$cacheCode]['type'] == 'object')
                        $kcache[$cacheCode]['value'] = unserialize($kcache[$cacheCode]['value']);

                    $this->cache[$pCode] =& $kcache[$cacheCode];

                    break;

                case 'apc':

                    $this->cache[$pCode] = apc_fetch($pCode);
                    break;

                case 'xcache':

                    $this->cache[$pCode] = xcache_get($pCode);
            }
        }

        if (!$this->cache[$pCode]) {
            return false;
        }

        if ($this->withInvalidationChecks) {


            if ($pWithoutValidationCheck == true) {
                if (!$this->cache[$pCode]['value'] || !$this->cache[$pCode]['time']
                    || $this->cache[$pCode]['timeout'] < time()) {
                    return false;
                }
                return $this->cache[$pCode]['value'];
            }

            //valid cache
            //search if a parent has been flagged as invalid
            if (strpos($pCode, '_') !== false) {

                $parents = explode('_', $pCode);
                $code = '';
                if (is_array($parents)) {
                    foreach ($parents as $parent) {
                        $code .= $parent;
                        $invalidateTime = $this->getInvalidate($code);
                        if ($invalidateTime && $invalidateTime > $this->cache['time']) {
                            return false;
                        }
                        $code .= '_';
                    }
                }
            }
        }

        return $this->cache[$pCode]['value'];

    }

    /**
     * Returns the invalidation time
     *
     * @param string $pCode
     * @return string
     */
    public function getInvalidate($pCode) {
        return $this->get($pCode . '_i', true);
    }


    /**
     * Marks a code as invalidate until $pTime
     *
     * @param string   $pCode
     * @param bool|int $pTime
     */
    public function invalidate($pCode, $pTime = false) {

        $this->set($pCode . '_i', $pTime ? $pTime : time(), time() + (3600 * 24 * 20), false);

    }

    /**
     * Sets a content to the specified cache-key.
     * pTimeout is only in files mode available when activating the $withInvalidationChecks=true
     *
     * @param string   $pCode
     * @param mixed    $pValue
     * @param int|bool $pTimeout In seconds. Default is one hour
     *
     * @return mixed  ref of cache or false if failed
     */
    public function &set($pCode, $pValue, $pTimeout = false) {

        if (!$pCode) return false;

        if (!$pTimeout)
            $pTimeout = time() + 3600;
        else
            $pTimeout += time();

        $pValue = array(
            'timeout' => $pTimeout,
            'time' => time(),
            'value' => $pValue
        );



        $this->cache[$pCode] = $pValue;

        switch ($this->type) {
            case 'memcached':

                if ($this->memcache) {
                    return $this->memcache->set($pCode, $pValue, 0, $pTimeout ? $pTimeout : null);
                } else if ($this->memcached) {
                    return $this->memcached->set($pCode, $pValue, $pTimeout ? $pTimeout : null);
                }

            case 'redis':

                return $this->redis->setex($pCode, $pTimeout, $pValue);

            case 'files':

                if (is_object($pValue['value'])){
                    $pValue['type'] = 'object';
                    $pValue['value'] = serialize($pValue['value']);
                }

                $cacheCode = 'krynPhpCache_' . urlencode($pCode);
                $varname = '$kcache[\'' . $cacheCode . '\'] ';

                $phpCode = "<" . "?php \n$varname = " . var_export($pValue, true) . ";\n ?" . ">";

                if (class_exists('kryn'))
                    kryn::fileWrite($this->config['files_path'] . urlencode($pCode) . '.php', $phpCode);
                else
                    file_put_contents($this->config['files_path'] . urlencode($pCode) . '.php', $phpCode);

                return file_exists(PATH . $this->config['files_path'] . urlencode($pCode) . '.php');

            case 'apc':

                return apc_store($pCode, $pValue, time() - $pTimeout);

            case 'xcache':

                return xcache_set($pCode, $pValue, time() - $pTimeout);
        }

        return $this->cache[$pCode];
    }

    /**
     * Deletes the cache for specified cache-key.
     *
     * @param string $pCode
     * @return bool
     */
    public function delete($pCode) {

        unset($this->cache[$pCode]);

        switch ($this->type) {
            case 'memcached':

                if ($this->memcache) {
                    return $this->memcache->delete($pCode);
                } else if ($this->memcached) {
                    return $this->memcached->delete($pCode);
                }

            case 'redis':

                return $this->redis->delete($pCode);

            case 'files':

                $cacheCode = 'krynPhpCache_' . $pCode;
                if (file_exists(PATH . $this->config['files_path'] . $pCode . '.php'))
                    @unlink(PATH . $this->config['files_path'] . $pCode . '.php');
                break;

            case 'apc':

                return apc_delete($pCode);

            case 'xcache':

                return xcache_unset($pCode);
        }
    }

    /**
     * Deletes the cache for specified cache-key.
     *
     * @param string $pCode
     * @return bool
     */
    public function clear($pCode) {
        return $this->delete($pCode);
    }
}

?>