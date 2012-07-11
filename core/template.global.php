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
 * Global template functions
 * @author MArc Schmidt <marc@kryn.org>
 */

/**
 * Defines a value to the specified name in the template engine
 * Accessible in template engine {$<$pName>}
 *
 * @param type $pName
 * @param type $pVal
 */
function tAssign($pName, $pVal) {
    global $tpl;
    tInit();
    $tpl->assign($pName, $pVal);
}

/**
 * Defines a value by reference to the specified name in the template engine
 * Accessible in template engine {$<$pName>}
 *
 * @param type $pName
 * @param type $pVal
 */
function tAssignRef($pName, &$pVal) {
    global $tpl;
    tInit();
    $tpl->assignByRef($pName, $pVal);
}

/**
 * Parse and compiled specified template and return the parsed template.
 * This function also replaces all [[.*]] translation strings through kryn::translate().
 *
 * @param $pPath <module>/<template_file_in_views>
 * @return mixed
 */
function tFetch($pPath) {

    tInit();
    global $tpl;

    $path = tPath($pPath);
    return kryn::translate($tpl->fetch($path));
}

/**
 * Returns the path of given view/template file.
 *
 * @param $pPath
 * @return string
 */
function tPath($pPath){
    $pos = strpos($pPath, '/');
    $file = substr($pPath, $pos+1);
    $module = substr($pPath, 0, $pos);
    return (($module == 'kryn' || $module == 'core')? PATH_CORE : PATH_MODULE . $module . '/') . 'views/' . $file;
}

/**
 * Returns the modification timestamp of given view/template file.
 *
 * @param $pPath
 * @return int
 */
function tModTime($pPath){
    $path = tPath($pPath);
    return file_exists($path) ? filemtime($path): false;
}

/**
 * Initialize the Smarty object to $tpl
 */
function tInit(){
    global $tpl;

    if (!$tpl){

        $tpl = new Smarty();
        $tpl->template_dir = './';
        $tpl->compile_dir = 'cache/smarty_compile/';
    }
}

?>
