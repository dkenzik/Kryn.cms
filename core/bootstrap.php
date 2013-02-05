<?php

$_time = time();
$_start = microtime(true);

error_reporting(E_CORE_ERROR|E_COMPILE_ERROR|E_RECOVERABLE_ERROR|E_ERROR|E_CORE_ERROR|E_USER_ERROR|E_PARSE);


if (!defined('PATH')){
    define('PATH', realpath(dirname(__FILE__).'/../') . '/');
    define('PATH_CORE', 'core/');
    define('PATH_MODULE', 'module/');
    define('PATH_MEDIA', 'media/');
    define('PATH_MEDIA_CACHE', 'media/cache/');
}

/**
 * Check and loading config.php or redirect to install.php
 */
if (!isset($cfg)){
    $cfg = array();
    if (!file_exists(PATH.'config.php') || !is_array($cfg = include(PATH.'config.php'))){
        header("Location: install.php");
        exit;
    }
}


if (!function_exists('mb_internal_encoding'))
    die('FATAL ERROR: PHP module mbstring is not loaded. Aborted. Run the installer again.');

mb_internal_encoding("UTF-8");


if (substr($_SERVER['PATH_INFO'], 0, 1) == '/')
    $_SERVER['PATH_INFO'] = substr($_SERVER['PATH_INFO'], 1);

/**
 * Define global functions.
 */
include_once(PATH_CORE.'global/misc.global.php');
include_once(PATH_CORE.'global/database.global.php');
include_once(PATH_CORE.'global/template.global.php');
include_once(PATH_CORE.'global/internal.global.php');
include_once(PATH_CORE.'global/framework.global.php');
include_once(PATH_CORE.'global/exceptions.global.php');

include_once(PATH.'core/bootstrap.autoloading.php');

Core\Kryn::$config = $cfg;

$http = 'http://';
if ($_SERVER['HTTPS'] == '1' || strtolower($_SERVER['HTTPS']) == 'on')
    $http = 'https://';

$port = '';
if (($_SERVER['SERVER_PORT'] != 80 && $http == 'http://') ||
    ($_SERVER['SERVER_PORT'] != 443 && $http == 'https://')
) {
    $port = ':' . $_SERVER['SERVER_PORT'];
}

Core\Kryn::setBaseUrl($http.$_SERVER['SERVER_NAME'].$port.str_replace('index.php', '', $_SERVER['SCRIPT_NAME']));


/**
 * Load active modules into Kryn::$extensions.
 */
Core\Kryn::loadActiveModules();


if ($cfg['timezone'])
    date_default_timezone_set($cfg['timezone']);

if ($cfg['locale'])
    setlocale(LC_ALL, $cfg['locale']);

define('pfx', $cfg['database']['prefix']);

?>