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


class adminPages {

    public static function init() {

        switch (getArgv(3)) {
            case 'domain':
                return self::domain();
            case 'save':
                return self::save();
            case 'getLayout':
                return adminLayout::get(getArgv('name'), getArgv('plain'));
            case 'move':
                return self::move();
            case 'add':
                return self::add();
//            return self::save( true );
            case 'getPage':
                return self::getPage(getArgv('rsn') + 0, true);
            case 'getPageInfo':
                return self::getPageInfo(getArgv('rsn') + 0, true);
            case 'deletePage':
                return self::deletePage(getArgv('rsn') + 0);
            case 'getNotices':
                return self::getNotices(getArgv('rsn') + 0);
            case 'addNotice':
                return self::addNotice(getArgv('rsn') + 0);
            case 'getIcons':
                return json(self::getIcons(getArgv('rsn')));
            case 'getDomains':
                return self::getDomains(getArgv('language'));
            case 'getTree':
                return self::getTree(getArgv('page_rsn') + 0);
            case 'getTreeDomain':
                return self::getTreeDomain(getArgv('domain_rsn') + 0);
            case 'getTemplate':
                return self::getTemplate(getArgv('template'));
            case 'getVersions':
                return self::getVersions();
            case 'getUrl':
                return self::getUrl(getArgv('rsn'));
            case 'getPageVersions':
                json(self::getPageVersion(getArgv('rsn')));
            case 'getVersion':
                $rsn = getArgv('rsn') + 0;
                $version = getArgv('version') + 0;
                return json(self::getVersion($rsn, $version));
            /*case 'addVersion':
        return self::addVersion( getArgv('rsn')+0, getArgv('name',true) );*/
            case 'setLive':
                return json(self::setLive(getArgv('version')));

            case 'paste':
                return json(self::paste());

            case 'setHide':
                return json(self::setHide(getArgv('rsn'), getArgv('visible')));

            case 'deleteAlias':
                return self::deleteAlias(getArgv('rsn') + 0);
            case 'getAliases':
                return self::getAliases(getArgv('page_rsn') + 0);

            default:
                return self::itemList();
        }
    }

    public static function setHide($pRsn, $pVisible) {
        $pRsn += 0;
        $pVisible += 0;

        if (kryn::checkPageAcl($pRsn, 'visible'))
            dbUpdate('system_pages', 'rsn = ' . $pRsn, array('visible' => $pVisible));
    }

    public static function getPageInfo($pRsn) {

        $pRsn += 0;
        $page = dbTableFetch('system_pages', "rsn = $pRsn", 1);
        $page['_parents'] = kryn::getPageParents($pRsn);

        if (!$page['_parents'])
            $page['_parents'] = array();

        return $page;

    }

    public static function getAliases($pRsn) {
        $pRsn = $pRsn + 0;

        $items = dbTableFetch('system_urlalias', 'to_page_rsn = ' . $pRsn, -1);
        json($items);
    }

    public static function deleteAlias($pRsn) {
        $pRsn = $pRsn + 0;

        dbDelete('system_urlalias', 'rsn = ' . $pRsn);
    }

    public static function setLive($pVersion) {

        $pVersion = $pVersion + 0;
        $version = dbTableFetch('system_pagesversions', 1, 'rsn = ' . $pVersion);

        if ($version['rsn'] > 0) {
            $newstVersion = dbTableFetch('system_pagesversions', 1,
                'page_rsn = ' . $version['page_rsn'] . ' ORDER BY created DESC');

            if ($newstVersion['rsn'] == $pVersion)
                dbUpdate('system_pages', array('rsn' => $version['page_rsn']), array('draft_exist' => 0));
            else
                dbUpdate('system_pages', array('rsn' => $version['page_rsn']), array('draft_exist' => 1));

            dbUpdate('system_pagesversions', array('page_rsn' => $version['page_rsn']), array('active' => 0));
            dbUpdate('system_pagesversions', array('rsn' => $version['rsn']), array('active' => 1));
            return 1;
        }
        return 0;

    }

    public static function paste() {

        $domain = getArgv('to_domain') == 1 ? true : false;
        if (getArgv('type') == 'pageCopy') {
            self::copyPage(getArgv('page'), getArgv('to'), $domain, getArgv('pos'));
        }
        if (getArgv('type') == 'pageCopyWithSubpages') {
            self::copyPage(getArgv('page'), getArgv('to'), $domain, getArgv('pos'), true);
        }

        $pageTo = dbTableFetch('system_pages', 1, 'rsn = ' . (getArgv('to') + 0));
        self::cleanSort($pageTo['domain_rsn'], $pageTo['prsn']);
        self::updateUrlCache($pageTo['domain_rsn']);
        self::updateMenuCache($pageTo['domain_rsn']);

        $page = dbTableFetch('system_pages', 1, 'rsn = ' . (getArgv('page') + 0));
        self::cleanSort($page['domain_rsn'], $page['prsn']);
        if ($page['domain_rsn'] != $pageTo['domain_rsn']) {
            self::updateUrlCache($page['domain_rsn']);
            self::updateMenuCache($page['domain_rsn']);
        }

        self::updatePage2DomainCache();
        kryn::deleteCache('kryn_pluginrelations');

        return true;

    }

    public static function copyPage($pFrom, $pTo, $pToDomain, $pPos, $pWithSubpages = false, $pWithoutThisPage = false) {
        global $user;

        $pFrom += 0;
        $pTo += 0;
        $pWithoutThisPage += 0;

        $fromPage = dbTableFetch('system_pages', 1, 'rsn = ' . $pFrom);
        $newPage = $fromPage;

        if (!$pToDomain) {
            $toPage = dbTableFetch('system_pages', 1, 'rsn = ' . $pTo);
            $siblingWhere = "prsn = " . $toPage['prsn'];
            $newPage['domain_rsn'] = $toPage['domain_rsn'];
        }

        if ($pPos == 'down' || $pPos == 'up') {
            $newPage['sort'] = $toPage['sort'];
            $newPage['prsn'] = $toPage['prsn'];
            $newPage['sort_mode'] = $pPos;
            if ($pToDomain) {
                return false;
            }
        } else {
            $newPage['sort'] = 1;
            $newPage['sort_mode'] = 'up';
            if (!$pToDomain) {
                $siblingWhere = "prsn = " . $toPage['rsn'];
                $newPage['prsn'] = $toPage['rsn'];
            } else {
                $newPage['prsn'] = 0;
                $newPage['domain_rsn'] = $pTo;
                $siblingWhere = "prsn = 0 AND domain_rsn = " . $pTo;
            }
        }
        $newPage['draft_exist'] = 1;
        unset($newPage['rsn']);
        $newPage['visible'] = 0;

        if ($pWithSubpages) {
            $withoutPage = '';
            if ($pWithoutThisPage) {
                $withoutPage = ' AND rsn != ' . $pWithoutThisPage;
            }

            $childs = dbTableFetch('system_pages', -1, 'prsn = ' . $pFrom . $withoutPage . ' ORDER BY sort ');
        }

        //ceck url & titles
        $siblings = dbTableFetch('system_pages', -1, $siblingWhere);

        if (count($siblings) > 0) {

            $newCount = 0;
            $t = $newPage['title'];
            $needlePos = strpos($t, ' #') + 2;
            $needleLast = substr($t, $needlePos);

            foreach ($siblings as &$sibling) {

                //check title
                if (
                    $needleLast + 0 == 0 && $newPage['title'] == substr($sibling['title'], 0, strlen($newPage['title']))
                ) {
                    //same start, if last now a number ?
                    $end = substr($sibling['title'], strlen($newPage['title']) + 2);
                    if ($end + 0 > 0) {
                        if ($newCount < $end + 1)
                            $newCount = $end + 1; //$newPage['title'] .= ' #'.($end+1);
                    } else if ($end == '') { //equal title
                        if ($newCount == 0)
                            $newCount = 1; //$newPage['title'] .= ' #1';
                    }
                } else {

                    $ts = $sibling['title'];
                    $needleSPos = strpos($ts, ' #') + 2;
                    $needleSLast = substr($ts, $needleSPos);

                    if ($needleLast + 0 > 0 && $needleSLast + 0 > 0) {
                        //both seems to be increased
                        if ($newCount < $needleSLast + 1)
                            $newCount = $needleSLast + 1;
                    }

                }

                if ($newPage['url'] == substr($sibling['url'], 0, strlen($newPage['url']))) {
                    //same start, if last now a number ?
                    $end = substr($sibling['url'], strlen($newPage['url']));
                    if ($end + 0 > 0) {
                        $newPage['url'] .= '_' . ($end + 1);
                    } else if ($end == '') { //equal title
                        $newPage['url'] .= '_1';
                    }
                }
            }

            if ($newCount > 0) {
                if ($needlePos > 2)
                    $newPage['title'] = substr($t, 0, $needlePos - 2) . ' #' . $newCount;
                else
                    $newPage['title'] .= ' #' . $newCount;

            }
        }

        if ($newPage['prsn'] == 0) {
            if (!kryn::checkPageAcl($newPage['domain_rsn'], 'addPages', 'd'))
                json(array('error' => 'access_denied'));
            ;
        } else {
            if (!kryn::checkPageAcl($newPage['prsn'], 'addPages'))
                json(array('error' => 'access_denied'));
            ;
        }

        unset($newPage['rsn']);
        $lastId = dbInsert('system_pages', $newPage);

        if (!$pWithoutThisPage)
            $pWithoutThisPage = $lastId;

        if ($newPage['prsn'] == 0) {
            if (!kryn::checkPageAcl($newPage['domain_rsn'], 'canPublish', 'd'))
                json(array('error' => 'access_denied'));
            ;
        } else {
            if (!kryn::checkPageAcl($newPage['prsn'], 'canPublish'))
                json(array('error' => 'access_denied'));
            ;
        }

        //copy contents
        $curVersion = dbTableFetch('system_pagesversions', 1, 'active = 1 AND page_rsn = ' . $pFrom);
        $contents = dbTableFetch('system_contents', -1, 'version_rsn = ' . $curVersion['rsn']);

        if (count($contents) > 0) {
            $newVersion = dbInsert('system_pagesversions', array(
                'page_rsn' => $lastId,
                'owner_rsn' => $user->user_rsn,
                'created' => time(),
                'modified' => time(),
                'active' => 0
            ));

            foreach ($contents as &$content) {
                $content['page_rsn'] = $lastId;
                unset($content['rsn']);
                $content['mdate'] = time();
                $content['cdate'] = time();
                $content['version_rsn'] = $newVersion;
                dbInsert('system_contents', $content);
            }
        }


        //copy subpages
        if ($pWithSubpages) {
            if (count($childs) > 0) {
                foreach ($childs as &$child) {
                    self::copyPage($child['rsn'], $lastId, 'into', true, $pWithoutThisPage);
                }
            }
        }

        return $lastId;
    }

    public static function domain() {
        switch (getArgv(4)) {
            case 'add':
                return self::addDomain();
            case 'delete':
                return self::delDomain();
            case 'getMaster':
                return self::getDomainMaster();
            case 'get':
                return self::getDomain();
            case 'save':
                return self::saveDomain();
        }
    }

    public static function getDomainMaster() {
        $rsn = getArgv('rsn') + 0;
        if (!kryn::checkPageAcl($rsn, 'domainLanguageMaster', 'd')) {
            json(array('error' => 'access_denied'));
            ;
        }
        $cur = dbTableFetch('system_domains', 1, "rsn = $rsn");
        $res = dbTableFetch('system_domains', 1, "domain = '" . $cur['domain'] . "' AND master = 1");
        json($res);
    }

    public static function saveDomain() {
        $rsn = getArgv('rsn') + 0;

        $dbUpdate = array();
        $canChangeMaster = false;


        if (kryn::checkPageAcl($rsn, 'domainName', 'd')) {
            $dbUpdate[] = 'domain';
        }

        if (kryn::checkPageAcl($rsn, 'domainTitle', 'd')) {
            $dbUpdate[] = 'title_format';
        }

        if (kryn::checkPageAcl($rsn, 'domainStartpage', 'd')) {
            $dbUpdate[] = 'startpage_rsn';
        }

        if (kryn::checkPageAcl($rsn, 'domainPath', 'd')) {
            $dbUpdate[] = 'path';
        }
        if (kryn::checkPageAcl($rsn, 'domainFavicon', 'd')) {
            $dbUpdate[] = 'favicon';
        }
        if (kryn::checkPageAcl($rsn, 'domainLanguage', 'd')) {
            $dbUpdate[] = 'lang';
        }
        if (kryn::checkPageAcl($rsn, 'domainLanguageMaster', 'd')) {
            $canChangeMaster = true;
            $dbUpdate[] = 'master';
        }
        if (kryn::checkPageAcl($rsn, 'domainEmail', 'd')) {
            $dbUpdate[] = 'email';
        }


        if (kryn::checkPageAcl($rsn, 'themeProperties', 'd')) {
            $dbUpdate[] = 'themeproperties';
        }
        if (kryn::checkPageAcl($rsn, 'limitLayouts', 'd')) {
            $dbUpdate[] = 'layouts';
        }
        if (kryn::checkPageAcl($rsn, 'domainProperties', 'd')) {
            $dbUpdate[] = 'extproperties';
        }
        if (kryn::checkPageAcl($rsn, 'aliasRedirect', 'd')) {
            $dbUpdate[] = 'alias';
            $dbUpdate[] = 'redirect';
        }


        if (kryn::checkPageAcl($rsn, 'phpLocale', 'd')) {
            $dbUpdate[] = 'phplocale';
        }
        if (kryn::checkPageAcl($rsn, 'robotRules', 'd')) {
            $dbUpdate[] = 'robots';
        }
        if (kryn::checkPageAcl($rsn, '404', 'd')) {
            $dbUpdate[] = 'page404interface';
            $dbUpdate[] = 'page404_rsn';
        }

        if (kryn::checkPageAcl($rsn, 'domainOther', 'd')) {
            $dbUpdate[] = 'resourcecompression';
        }

        //todo need a acl for that
        $dbUpdate['session'] = json_encode(getArgv('session'));

        $domain = getArgv('domain', 1);
        if ($canChangeMaster) {
            if (getArgv('master') == 1) {
                dbUpdate('system_domains', "domain = '$domain'", array('master' => 0));
            }
        }

        kryn::deleteCache('systemDomains-'.$rsn);
        dbUpdate('system_domains', array('rsn' => $rsn), $dbUpdate);
        self::updateDomainCache();

        json($domain);
    }

    public static function getDomain() {


        $rsn = getArgv('rsn') + 0;

        if (!kryn::checkPageAcl($rsn, 'showDomain', 'd')) {
            json(array('error' => 'access_denied'));
        }

        $res['domain'] = dbExfetch("SELECT * FROM %pfx%system_domains WHERE rsn = $rsn");
        json($res);
    }

    public static function delDomain() {
        $domain = getArgv('rsn') + 0;


        if (!kryn::checkPageAcl($domain, 'deleteDomain', 'd')) {
            json(array('error' => 'access_denied'));
            ;
        }

        dbDelete('system_pages', "domain_rsn = $domain");
        dbDelete('system_domains', "rsn = $domain");
        json(true);
    }

    public static function updateDomainCache() {
        $res = dbExec('SELECT * FROM %pfx%system_domains');
        $domains = array();

        while ($domain = dbFetch($res, 1)) {

            $code = $domain['domain'];
            $lang = "";
            if ($domain['master'] != 1) {
                $lang = '_' . $domain['lang'];
                $code .= $lang;
            }

            $domains[$code] = $domain['rsn'];

            $alias = explode(",", $domain['alias']);
            if (count($alias) > 0) {
                foreach ($alias as $ad) {
                    $domainName = str_replace(' ', '', $ad);
                    if ($domainName != '') {
                        $domains[$domainName . $lang] = $domain['rsn'];
                    }
                }
            }

            $redirects = explode(",", $domain['redirect']);
            if (count($redirects) > 0) {
                foreach ($redirects as $redirect) {
                    $domainName = str_replace(' ', '', $redirect);
                    if ($domainName != '')
                        $domains['_redirects'][$domainName] = $domain['rsn'];
                }
            }

            kryn::deleteCache('systemDomain-' . $domain['rsn']);
        }
        kryn::setCache('systemDomains', $domains);
        return $domains;
    }

    public static function addDomain() {

        if (!kryn::checkUrlAccess('admin/pages/addDomains'))
            json(array('error' => 'access_denied'));
        ;

        dbInsert('system_domains', array('domain', 'lang', 'master' => 0,
            'search_index_key' => md5(getArgv('domain') . '-' . mktime() . '-' . rand())));
        json(true);
    }


    /*
     *
     *  Pages
     */

    public static function getPageVersion($pRsn) {
        $pRsn = $pRsn + 0;

        $res = array();
        if (!kryn::checkPageAcl($pRsn, 'versions')) {
            json(array('error' => 'access_denied'));
            ;
        }

        //$res['live'] = dbTableFetch( 'system_pages', 1, "rsn = $pRsn" );
        $res['versions'] = dbExFetch("SELECT v.*, u.username FROM %pfx%system_user u, %pfx%system_pagesversions v
            WHERE page_rsn = $pRsn AND u.rsn = v.owner_rsn ORDER BY created DESC", -1);

        return $res;
    }

    public static function getUrl($pRsn) {
        $pRsn = $pRsn + 0;

        json(kryn::getPagePath($pRsn));
    }

    public static function deletePage($pPage, $pNoCacheRefresh = false) {

        $pPage = $pPage + 0;

        if (!kryn::checkPageAcl($pPage, 'deletePages')) {
            json(array('error' => 'access_denied'));
            ;
        }

        kryn::deleteCache('page-' . $pPage);

        $page = dbExfetch("SELECT * FROM %pfx%system_pages WHERE rsn = $pPage", 1);

        $subpages = dbTableFetch('system_pages', 'prsn = ' . $pPage, -1);
        if (count($subpages) > 0) {
            foreach ($subpages as $page) {
                self::deletePage($page['rsn'], true);
                dbExec("DELETE FROM %pfx%system_pages WHERE rsn = $pPage");
            }
        }

        dbExec("DELETE FROM %pfx%system_pages WHERE rsn = $pPage");

        if (!$pNoCacheRefresh) {
            self::cleanSort($page['domain_rsn'], $page['prsn']);
            self::updateUrlCache($page['domain_rsn']);
            self::updateMenuCache($page['domain_rsn']);
        }
    }

    public static function getDomains($pLanguage) {
        $where = " 1=1 ";
        if ($pLanguage != "")
            $where = "lang = '$pLanguage'";

        $res = dbTableFetch('system_domains', DB_FETCH_ALL, "$where ORDER BY domain ASC");
        if (count($res) > 0) {
            foreach ($res as $domain) {

                if (kryn::checkPageAcl($domain['rsn'], 'showDomain', 'd')) {
                    $result[] = $domain;
                }
            }
        }
        json($result);
    }


    public static function getTemplate($pTemplate) {
        global $cfg;

        kryn::resetJs();
        kryn::resetCss();

        $domain = urlencode(getArgv('domain'));

        $domainPath = str_replace('\\', '/', str_replace('\\\\\\\\', '\\', urldecode(getArgv('path'))));
        //        $url = 'http://'.getArgv('domain').str_replace('\\','/',str_replace('\\\\\\\\','\\',urldecode(getArgv('path'))));
        $path = 'http://' . $domain . $domainPath . PATH_MEDIA;

        kryn::addJs($path . 'kryn/mootools-core.js');
        kryn::addJs($path . 'kryn/mootools-more.js');
        kryn::addJs($path . 'admin/js/ka.js');
        kryn::addJs('http://' . $domain . $domainPath . 'krynJavascriptGlobalPath.js');
        kryn::addCss($path . 'admin/css/ka.layoutBox.css');
        kryn::addCss($path . 'admin/css/inpage.css');
        kryn::addCss($path . 'admin/css/ka.Field.css');
        kryn::addCss($path . 'admin/css/ka.Button.css');
        kryn::addCss($path . 'admin/css/ka.Select.css');
        kryn::addCss($path . 'admin/css/ka.pluginChooser.css');
        kryn::addCss($path . 'admin/css/inpage.css');

        kryn::addCss($path . 'admin/css/ka.layoutBox.css');
        kryn::addCss($path . 'admin/css/ka.layoutContent.css');

        //kryn::addHeader( '<script type="text/javascript" src="'.'http://'.getArgv('domain').$domainPath.'inc/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>');

        $js = array(
            'MooEditable.js',
            'MooEditable.UI.MenuList.js',
            'MooEditable.Extras.js',
            'MooEditable.Image.js',
            'MooEditable.Table.js'
        );

        $css = array(
            'MooEditable.css',
            'MooEditable.Extras.css',
            'MooEditable.SilkTheme.css',
            'MooEditable.Image.css',
            'MooEditable.Table.css'
        );

        /*foreach( $js as $t ){
            kryn::addHeader( '<script type="text/javascript" src="'.'http://'.getArgv('domain').$domainPath.
                'inc/lib/mooeditable/Source/MooEditable/'.$t.'"></script>');
        }*/

        foreach ($css as $t) {
            kryn::addHeader(
                '<link rel="stylesheet" type="text/css" href="' . 'http://' . getArgv('domain') . $domainPath .
                'inc/lib/mooeditable/Assets/MooEditable/' . $t . '" />');
        }


        $rsn = getArgv('rsn') + 0;
        $page = dbTableFetch('system_pages', 1, "rsn = $rsn");
        //$domain = dbTableFetch('system_domains', 1, "domain = '".getArgv('domain',1)."'");
        $domain = dbTableFetch('system_domains', 1, "rsn = '" . $page['domain_rsn'] . "'"); //.getArgv('domain',1)."'");

        $domainName = $domain['domain'];

        $http = 'http://';
        if ($_SERVER['HTTPS'] == '1' || strtolower($_SERVER['HTTPS']) == 'on')
            $http = 'https://';

        $port = '';
        if (($_SERVER['SERVER_PORT'] != 80 && $http == 'http://') ||
            ($_SERVER['SERVER_PORT'] != 443 && $http == 'https://')
        ) {
            $port = ':' . $_SERVER['SERVER_PORT'];
        }

        if (getArgv(1) == 'admin') {
            $domain['path'] = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
        }

        if ($domain['path'] != '') {
            tAssign('path', $domain['path']);
            $cfg['path'] = $domain['path'];
            $cfg['templatepath'] = $domain['path'] . PATH_MEDIA;
            tAssign('cfg', $cfg);
            tAssign('_path', $domain['path']);
        }

        kryn::$baseUrl = $http . $domainName . $port . $cfg['path'];
        if ($domain['master'] != 1) {
            kryn::$baseUrl = $http . $domainName . $port . $cfg['path'] . $possibleLanguage . '/';
        }

        kryn::$current_page = $page;
        kryn::$page = $page;

        $page = krynHtml::printPage();
        exit;
    }

    public static function getVersion($pPageRsn, $pVersion) {

        $pPageRsn = $pPageRsn + 0;

        if (!kryn::checkPageAcl($pPageRsn, 'versions')) {
            json(array('error' => 'access_denied'));
            ;
        }
        $conts = array();
        if ($pVersion > 0) {
            $conts = dbTableFetch('system_contents', DB_FETCH_ALL, "page_rsn = $pPageRsn AND version_rsn = $pVersion
            AND (cdate > 0 AND cdate IS NOT NULL)  ORDER BY sort");
        }

        if (count($conts) > 0) {
            foreach ($conts as $cont) {
                $contents[$cont['box_id']][] = $cont;
            }
        }
        return $contents;
    }

    public static function getVersions() {
        $rsn = getArgv('rsn') + 0;


        if (!kryn::checkPageAcl($rsn, 'versions')) {
            json(array('error' => 'access_denied'));
            ;
        }

        $res = dbExfetch("SELECT v.*, u.username FROM %pfx%system_pagesversions v, %pfx%system_user u
            WHERE u.rsn = v.owner_rsn AND page_rsn = $rsn ORDER BY created DESC", -1);
        json($res);
    }

    public static function addNotice($pRsn) {
        global $user;
        dbInsert('system_page_notices', array('page_rsn' => $pRsn, 'user_rsn' => $user->user_rsn, 'content',
            'created' => time()));
        json(true);
    }

    public static function getNotices($pRsn) {
        $res['notices'] = dbExfetch('SELECT n.*, u.username
            FROM %pfx%system_page_notices n, %pfx%system_user u
            WHERE u.rsn = n.user_rsn AND page_rsn = ' . $pRsn . ' ORDER BY rsn', DB_FETCH_ALL);
        $res['count'] = count($res['notices']);
        json($res);
    }

    public static function getTreeDomain($pDomainRsn) {
        $pDomainRsn = $pDomainRsn + 0;

        $viewAllPages = (getArgv('viewAllPages') == 1) ? true : false;
        if ($viewAllPages && !kryn::checkUrlAccess('users/users/acl'))
            $viewAllPages = false;

        if (!$viewAllPages && !kryn::checkPageAcl($pDomainRsn, 'showDomain', 'd')) {
            json(array('error' => 'access_denied'));
        }

        $domain = dbTableFetch('system_domains', 1, "rsn = $pDomainRsn");
        $domain['type'] = -1;

        $childs = dbTableFetch('system_pages', DB_FETCH_ALL, "domain_rsn = $pDomainRsn AND prsn = 0 ORDER BY sort");
        $domain['childs'] = array();

        $cachedUrls =& kryn::getCache('systemUrls-' . $pDomainRsn);

        foreach ($childs as &$page) {
            if ($viewAllPages || kryn::checkPageAcl($page['rsn'], 'showPage') == true) {
                $page['realUrl'] = $cachedUrls['rsn']['rsn=' . $page['rsn']];
                $page['hasChilds'] = kryn::pageHasChilds($page['rsn']);
                $domain['childs'][] = $page;
            }
        }

        json($domain);
    }

    public static function getTree($pPageRsn) {
        $pPageRsn += 0;

        if ($pPageRsn == 0) return array();

        $viewAllPages = (getArgv('viewAllPages') == 1) ? true : false;
        if ($viewAllPages && !kryn::checkUrlAccess('users/users/acl'))
            $viewAllPages = false;

        $page = dbExfetch('SELECT prsn, domain_rsn FROM %pfx%system_pages WHERE rsn = ' . $pPageRsn);

        if (!$viewAllPages && !kryn::checkPageAcl($page['domain_rsn'], 'showDomain', 'd')) {
            json(array('error' => 'access_denied'));
            ;
        }

        if (!$viewAllPages && !kryn::checkPageAcl($page['rsn'], 'showPage')) {
            json(array('error' => 'access_denied'));
            ;
        }

        $items = dbTableFetch('system_pages', DB_FETCH_ALL, "prsn = $pPageRsn ORDER BY sort");

        $cachedUrls =& kryn::getCache('systemUrls-' . $page['domain_rsn']);

        if (count($items) > 0) {
            foreach ($items as &$item) {
                if ($viewAllPages || kryn::checkPageAcl($item['rsn'], 'showPage')) {
                    $item['realUrl'] = $cachedUrls['rsn']['rsn=' . $item['rsn']];
                    $item['hasChilds'] = kryn::pageHasChilds($item['rsn']);
                } else {
                    unset($item);
                }
            }
            return $items;

        } else {
            return array();
        }
    }

    public static function getIcons($pRsn) {
        global $cfg;

        $page = self::getPageByRsn($pRsn);

        if ($page['visible'] == '0' && $page['type'] != '2')
            $pngs[] = 'bullet_white';

        if ($page['access_denied'] == '1')
            $pngs[] = 'bullet_delete';

        if ($page['type'] == '1')
            $pngs[] = 'bullet_go';


        if (count($pngs) > 0)
            foreach ($pngs as $png) {
                $res .= '<img src="' . $cfg['path'] . PATH_MEDIA . '/admin/images/icons/' . $png . '_.png" />';
            }
        return $res;
    }

    public static function move() {
        $whoId = $_REQUEST['rsn'] + 0;
        $targetId = $_REQUEST['torsn'] + 0;
        $mode = getArgv('mode', 1);


        $who = self::getPageByRsn($whoId);
        $target = self::getPageByRsn($targetId);


        //check if $who is parent of $target, then cancel
        $whoIsParent = false;
        $menus =& kryn::getCache('menus-' . $who['domain_rsn']);
        if (is_array($menus[$targetId])) {
            foreach ($menus[$targetId] as $parent) {
                if ($parent['rsn'] == $whoId) {
                    $whoIsParent = true;
                }
            }
        }

        if ($whoIsParent) {
            return false;
        }

        if (getArgv('toDomain') == 1) {
            $target['domain_rsn'] = $targetId;
            $targetId = 0;
            $mode = 'into';
        }


        if ($who['domain_rsn'] != $target['domain_rsn']) {
            $domainChanged = true;
        }

        if (!kryn::checkPageAcl($target['domain_rsn'], 'addPages', 'd')) {
            json(array('error' => 'access_denied'));
            ;
        }

        $oldRealUrl = kryn::pageUrl($whoId, $who['domain_rsn']);

        //handle mode
        switch ($mode) {
            case 'into':
                if ($targetId != 0 && !kryn::checkPageAcl($targetId, 'addPages')) {
                    json(array('error' => 'access_denied'));
                    ;
                }
                dbExec("UPDATE %pfx%system_pages SET prsn = $targetId, domain_rsn = '" . $target['domain_rsn'] .
                       "', sort = 1, sort_mode = 'up' WHERE rsn = $whoId");
                break;

            case 'down':
                if ($target['prsn'] == 0) {
                    if (!kryn::checkPageAcl($target['domain_rsn'], 'addPages', 'd')) {
                        json(array('error' => 'access_denied'));
                        ;
                    }
                } else {
                    if (!kryn::checkPageAcl($target['prsn'], 'addPages')) {
                        json(array('error' => 'access_denied'));
                        ;
                    }
                }

                dbExec("UPDATE %pfx%system_pages SET prsn = " . $target['prsn'] . ", sort = " . $target['sort'] . ",
            sort_mode = 'down', domain_rsn = '" . $target['domain_rsn'] . "'  WHERE rsn = $whoId");
                break;
            case 'up':
                if ($target['prsn'] == 0) {
                    if (!kryn::checkPageAcl($target['domain_rsn'], 'addPages', 'd')) {
                        json(array('error' => 'access_denied'));
                        ;
                    }
                } else {
                    if (!kryn::checkPageAcl($target['prsn'], 'addPages')) {
                        json(array('error' => 'access_denied'));
                        ;
                    }
                }
                dbExec("UPDATE %pfx%system_pages SET prsn = " . $target['prsn'] . ", sort = " . $target['sort'] . ",
            sort_mode = 'up', domain_rsn = '" . $target['domain_rsn'] . "' WHERE rsn = $whoId");
                break;
        }

        if (getArgv('toDomain') || $domainChanged) {
            self::fixPageDomainRsn($whoId, $target['domain_rsn']);
        }

        kryn::deleteCache('page-' . $whoId);
        kryn::deleteCache('page-' . $target);


        $parents = kryn::getPageParents($whoId);
        foreach ($parents as &$parent) {
            kryn::deleteCache('page-' . $parent['rsn']);
        }

        $parents = kryn::getPageParents($target);
        foreach ($parents as &$parent) {
            kryn::deleteCache('page-' . $parent['rsn']);
        }

        self::cleanSort($target['domain_rsn'], 0);
        self::updateUrlCache($target['domain_rsn']);
        self::updateMenuCache($target['domain_rsn']);
        kryn::invalidateCache('navigation-' . $target['domain_rsn']);
        kryn::invalidateCache('systemNavigations-' . $target['domain_rsn']);
        kryn::invalidateCache('systemWholePage-' . $target['domain_rsn']);

        kryn::deleteCache('kryn_pluginrelations');

        if ($target['domain_rsn'] != $who['domain_rsn']) {
            self::cleanSort($who['domain_rsn'], 0);
            self::updateUrlCache($who['domain_rsn']);
            self::updateMenuCache($who['domain_rsn']);
            kryn::invalidateCache('navigation-' . $who['domain_rsn']);
            kryn::invalidateCache('systemNavigations-' . $who['domain_rsn']);
            kryn::invalidateCache('systemWholePage-' . $who['domain_rsn']);
        }

        return true;
    }

    public static function fixPageDomainRsn($pPageRsn, $pDomainRsn) {
        $pPageRsn += 0;

        dbUpdate('system_pages', 'prsn = ' . $pPageRsn, array('domain_rsn' => $pDomainRsn));

        $res = dbExec('SELECT rsn FROM %pfx%system_pages WHERE prsn = ' . $pPageRsn);
        while ($row = dbFetch($res)) {
            self::fixPageDomainRsn($row['rsn'], $pDomainRsn);
        }
    }

    public static function cleanSort($pDomain, $pParent) {
        //$pages = dbExfetch( "SELECT * FROM %pfx%system_pages WHERE domain_rsn = $pDomain AND prsn = $pParent AND sort_mode = '' ORDER BY sort", DB_FETCH_ALL );
        $pages =
            dbExfetch("SELECT * FROM %pfx%system_pages WHERE domain_rsn = $pDomain AND prsn = $pParent ORDER BY sort, sort_mode", DB_FETCH_ALL);
        //$cleanPage = dbExfetch( "SELECT * FROM %pfx%system_pages WHERE domain_rsn = $pDomain AND prsn = $pParent AND sort_mode != ''" );

        $count = count($pages);
        $c = 1;
        $lastPage = false;
        if (count($pages) > 0)
            foreach ($pages as &$page) {

                if ($page['sort_mode'] == 'up') {
                    if ($lastPage) {
                        dbExec("UPDATE %pfx%system_pages SET sort = " . ($c) . " WHERE rsn = " . $lastPage['rsn']);
                        dbExec("UPDATE %pfx%system_pages SET sort = " . ($c - 1) . " WHERE rsn = " . $page['rsn']);
                    } else {
                        dbExec("UPDATE %pfx%system_pages SET sort = " . ($c) . " WHERE rsn = " . $page['rsn']);
                        $c++;
                    }
                } else {
                    dbExec("UPDATE %pfx%system_pages SET sort = " . $c . " WHERE rsn = " . $page['rsn']);
                }
                $c++;

                if ($page['sort_mode'] == 'down') {
                    dbExec("UPDATE %pfx%system_pages SET sort = " . ($c) . " WHERE rsn = " . $page['rsn']);
                    $c++;
                }

                $lastPage = $page;
                self::cleanSort($pDomain, $page['rsn']);
            }

        dbExec("UPDATE %pfx%system_pages SET sort_mode = '' WHERE domain_rsn = $pDomain AND prsn = $pParent");
    }

    public static function getPageByRsn($pRsn) {
        return dbExfetch("SELECT * FROM %pfx%system_pages WHERE rsn = " . ($pRsn + 0));
    }

    public static function add() {

        $found = (getArgv('field_1') != '') ? true : false;
        $c = 1;
        $rsn = getArgv('rsn') + 0;
        $pos = getArgv('pos');
        $type = getArgv('type') + 0;

        $layout = getArgv('layout', 1);
        $visible = getArgv('visible');

        if (!getArgv('parentId'))
            jsonError('no_parent_id');

        if (!getArgv('parentObjectKey'))
            jsonError('no_parent_object_key');

        $targetItem = krynObjects::get(getArgv('parentObjectKey'), getArgv('parentId'));

        if (getArgv('parentObjectKey') == 'node'){
            $domain_rsn = $targetItem['domain_rsn'];
        } else {
            $domain_rsn = $targetItem['rsn'];
        }

        //3print_r($targetItem); exit;

        //if ($rsn > 0)
        //    $page = dbTableFetch('system_pages', 1, "rsn = $rsn");

        //$domain_rsn = ($rsn > 0) ? $page['domain_rsn'] : getArgv('domain_rsn');
        //$prsn = ($rsn > 0) ? $page['prsn'] : 0;

        //todo, check ACL

        /*
        if ($prsn == 0) {
            if (!kryn::checkPageAcl($domain_rsn, 'addPages', 'd')) {
                json(array('error' => 'access_denied'));
                ;
            }
        } else {
            if (!kryn::checkPageAcl($prsn, 'addPages')) {
                json(array('error' => 'access_denied'));
                ;
            }
        }*/

        while ($found) {
            $val = getArgv('field_' . $c);
            if ($val == '') {
                $found = false;
                continue;
            }

            $row = array(
                'title' => $val,
                'access_denied' => 0,
                'cdate' => time(),
                'mdate' => time(),
                'cache' => 0,
                'access_from' => 0,
                'access_to' => 0,
                'url' => kryn::toModRewrite($val),
                'layout' => $layout,
                'visible' => $visible,
                'domain_rsn' => $domain_rsn,
                'type' => $type
            );

            krynObjects::add('node', $row, getArgv('parentId'), $pos, getArgv('parentObjectKey'));

            $c++;
        }

        self::updateUrlCache($domain_rsn);
        self::updateMenuCache($domain_rsn);

        if (getArgv('parentObjectKey') == 'node'){
            kryn::deleteCache('page-' . $rsn);
            $parents = kryn::getPageParents($rsn);
            foreach ($parents as &$parent) {
                kryn::deleteCache('page-' . $parent['rsn']);
            }
        }

        kryn::invalidateCache('navigation-' . $domain_rsn);
        kryn::invalidateCache('systemNavigations-' . $domain_rsn);
        kryn::invalidateCache('systemWholePage-' . $domain_rsn);

        self::updatePage2DomainCache();

        kryn::deleteCache('kryn_pluginrelations');
        json(true);
    }

    public static function save() {
        global $user, $kcache;

        $rsn = getArgv('rsn') + 0;

        kryn::deleteCache('page-' . $rsn);
        kryn::deleteCache('pageContents-' . $rsn);

        $domain_rsn = getArgv('domain_rsn') + 0;

        $aclCanPublish = false;
        $canSaveContents = false;

        if (kryn::checkPageAcl($rsn, 'contents') && kryn::checkPageAcl($rsn, 'canPublish')) {
            $aclCanPublish = true;
        }

        $groups = '';
        if (is_array(getArgv('access_from_groups')))
            $groups = esc(implode(",", getArgv('access_from_groups')));


        $active = 0;
        $publishPage = false;
        if (getArgv('andPublish') == 1 && $aclCanPublish) {
            $publishPage = true;
        }


        $updateArray = array();

        if (kryn::checkPageAcl($rsn, 'general')) {

            if (kryn::checkPageAcl($rsn, 'title'))
                $updateArray[] = 'title';

            if (kryn::checkPageAcl($rsn, 'page_title'))
                $updateArray[] = 'page_title';

            if (kryn::checkPageAcl($rsn, 'type'))
                $updateArray[] = 'type';

            if (kryn::checkPageAcl($rsn, 'url'))
                $updateArray[] = 'url';

            if (kryn::checkPageAcl($rsn, 'meta'))
                $updateArray[] = 'meta';

            $updateArray[] = 'target';
            $updateArray[] = 'link';
        }


        if (kryn::checkPageAcl($rsn, 'access')) {

            if (kryn::checkPageAcl($rsn, 'visible'))
                $updateArray[] = 'visible';

            if (kryn::checkPageAcl($rsn, 'access_denied'))
                $updateArray[] = 'access_denied';

            if (kryn::checkPageAcl($rsn, 'force_https'))
                $updateArray[] = 'force_https';

            if (kryn::checkPageAcl($rsn, 'releaseDates')) {
                $updateArray[] = 'access_from';
                $updateArray[] = 'access_to';
            }

            if (kryn::checkPageAcl($rsn, 'limitation')) {
                $updateArray['access_from_groups'] = $groups;
                $updateArray[] = 'access_need_via';
                $updateArray[] = 'access_nohidenavi';
                $updateArray[] = 'access_redirectto';
            }
        }

        if (kryn::checkPageAcl($rsn, 'contents')) {

            $canSaveContents = true;

            if (kryn::checkPageAcl($rsn, 'canChangeLayout'))
                $updateArray[] = 'layout';

        }

        if (kryn::checkPageAcl($rsn, 'properties')) {
            $updateArray[] = 'properties';
        }

        if (kryn::checkPageAcl($rsn, 'search')) {

            if (kryn::checkPageAcl($rsn, 'exludeSearch')) {
                $updateArray[] = 'unsearchable';

                if (getArgv('unsearchable', 1) + 0 > 0)
                    dbExec(
                        "DELETE FROM %pfx%system_search WHERE page_rsn = '" . $rsn . "' AND domain_rsn=" . $domain_rsn);
            }

            if (kryn::checkPageAcl($rsn, 'searchKeys'))
                $updateArray[] = 'search_words';

        }

        $updateArray['draft_exist'] = ($publishPage) ? 0 : 1;
        $updateArray['mdate'] = time();


        $oldPage = dbTableFetch("system_pages", "rsn = " . ($rsn + 0), 1);

        kryn::invalidateCache('systemWholePage-' . $oldPage['domain_rsn']);
        kryn::invalidateCache('systemNavigations-' . $oldPage['domain_rsn']);
        kryn::invalidateCache('navigation-' . $oldPage['domain_rsn']);

        $kcache['realUrl'] =& kryn::getCache('systemUrls-' . $oldPage['domain_rsn']);
        $oldRealUrl = $kcache['realUrl']['rsn']['rsn=' . $rsn];

        if (in_array('url', $updateArray) && $oldPage['url'] != getArgv('url') && getArgv('newAlias')) {


            if (getArgv('newAliasWithSub')) {
                $oldRealUrl .= '/%';
            }

            $existRow = dbExfetch(
                "SELECT rsn FROM %pfx%system_urlalias WHERE to_page_rsn=" . $page . " AND url = '" . $oldRealUrl .
                "'", 1);

            if ($existRow['rsn'] + 0 == 0)
                dbInsert('system_urlalias', array('domain_rsn' => $oldPage['domain_rsn'], 'url' => $oldRealUrl,
                    'to_page_rsn' => $rsn));

        }

        if ($oldPage['url'] != getArgv('url')) {
            $indexedPages =& kryn::getCache('systemSearchIndexedPages');

            $need = $rsn . '_';
            foreach ($indexedPages as $key => &$index) {
                if (substr($key, 0, strlen($need)) == $need) {
                    unset($indexedPages[$key]);
                }
            }

            dbDelete('system_search', 'page_rsn	= ' . $rsn);
            kryn::invalidateCache('krynSearch_' . $oldPage['rsn']);
        }

        dbUpdate('system_pages', array('rsn' => $rsn), $updateArray);

        //if page marked as unsearchable the delete it from index

        if ($canSaveContents && !(getArgv('dontSaveContents') == 1) && (getArgv('type') == 0 || getArgv('type') == 3)) {
            $contents = json_decode($_POST['contents'], true);

            $active = 0;
            if (getArgv('andPublish') == 1 && $aclCanPublish) {
                $active = 1;
                dbUpdate('system_pagesversions', array('page_rsn' => $rsn), array('active' => 0));
            }

            $time = time();

            $version_rsn = dbInsert('system_pagesversions', array(
                'page_rsn' => $rsn, 'owner_rsn' => $user->user_rsn, 'created' => $time, 'modified' => $time,
                'active' => $active
            ));

            if (count($contents) > 0) {
                foreach ($contents as $boxId => &$box) {

                    $sort = 1;

                    foreach ($box as &$content) {
                        $contentGroups = '';
                        if (is_array($content['access_from_groups']))
                            $contentGroups = esc(implode(",", $content['access_from_groups']));

                        if (kryn::checkPageAcl($rsn, 'content-' . $content['type'])) {

                            $contentRsn = dbInsert('system_contents', array(
                                'page_rsn' => $rsn,
                                'box_id' => $boxId,
                                'title' => $content['title'],
                                'content' => $content['content'],
                                'template' => $content['template'],
                                'type' => $content['type'],
                                'mdate' => $time,
                                'cdate' => $time,
                                'hide' => $content['hide'],
                                'sort' => $sort,
                                'version_rsn' => $version_rsn,
                                'unsearchable' => $content['unsearchable'],
                                'access_from' => $content['access_from'],
                                'access_to' => $content['access_to'],
                                'access_from_groups' => $contentGroups
                            ));

                            $sort++;
                        } else {

                            $oldContent = dbTableFetch('system_contents',
                                'rsn = ' . ($content['rsn'] + 0) . ' AND page_rsn = ' . $rsn . ' AND box_id = ' .
                                $boxId, 1);
                            if ($oldContent['rsn'] + 0 > 0 && $oldContent['type'] == $content['type']) {

                                $oldContent['version_rsn'] = $version_rsn;
                                unset($oldContent['rsn']);
                                dbInsert('system_contents', $oldContent);
                                $sort++;

                            }
                        }
                    }
                }
            }
        }


        if (kryn::checkPageAcl($rsn, 'resources')) {
            if (getArgv('getType') == 0 || getArgv('getType') == 3) { //page or deposit

                if (!file_exists(PATH_MEDIA.'css/_pages/')) {
                    klog('autofix', PATH_MEDIA.'css/_pages/ doesnt exists, create it.');
                    @mkdir(PATH_MEDIA.'css/_pages');
                }

                if (!file_exists(PATH_MEDIA.'js/_pages/')) {
                    klog('autofix', PATH_MEDIA.'js/_pages/ doesnt exists, create it.');
                    @mkdir(PATH_MEDIA.'js/_pages');
                }

                if (kryn::checkPageAcl($rsn, 'css')) {
                    if (getArgv('resourcesCss') != '')
                        kryn::fileWrite(PATH_MEDIA."css/_pages/$rsn.css", getArgv('resourcesCss'));
                    else if (file_exists(PATH_MEDIA."css/_pages/$rsn.css"))
                        @unlink(PATH_MEDIA."css/_pages/$rsn.css");
                }


                if (kryn::checkPageAcl($rsn, 'js')) {
                    if (getArgv('resourcesJs') != '')
                        kryn::fileWrite(PATH_MEDIA."js/_pages/$rsn.js", getArgv('resourcesJs'));
                    else
                        @unlink(PATH_MEDIA."js/_pages/$rsn.js");
                }
            }
        }

        self::updateUrlCache($domain_rsn);
        self::updateMenuCache($domain_rsn);

        kryn::deleteCache('kryn_pluginrelations');

        $res = self::getPage($rsn);
        $res['version_rsn'] = $version_rsn;
        json($res);
    }

    public static function updateMenuCache($pDomainRsn) {
        $resu = dbExec("SELECT rsn, title, url, prsn FROM %pfx%system_pages WHERE
        				 domain_rsn = $pDomainRsn AND (type = 0 OR type = 1 OR type = 4)");
        $res = array();
        while ($page = dbFetch($resu, 1)) {

            if ($page['type'] == 0)
                $res[$page['rsn']] = self::getParentMenus($page);
            else
                $res[$page['rsn']] = self::getParentMenus($page, true);

        }

        kryn::setCache("menus-$pDomainRsn", $res);
        kryn::invalidateCache('navigation_' . $pDomainRsn);

        return $res;
    }

    public static function getParentMenus($pPage, $pAllParents = false) {
        $prsn = $pPage['prsn'];
        $res = array();
        while ($prsn != 0) {
            $parent_page =
                dbExfetch("SELECT rsn, title, url, prsn, type FROM %pfx%system_pages WHERE rsn = " . $prsn, 1);
            if ($parent_page['type'] == 0 || $parent_page['type'] == 1 || $parent_page['type'] == 4) {
                //page or link or page-mount
                array_unshift($res, $parent_page);
            } else if ($pAllParents) {
                array_unshift($res, $parent_page);
            }
            $prsn = $parent_page['prsn'];
        }
        return $res;
    }

    public static function updateUrlCache($pDomainRsn) {

        $pDomainRsn = $pDomainRsn + 0;

        $resu =
            dbExec("SELECT rsn, title, url, type, link FROM %pfx%system_pages WHERE domain_rsn = $pDomainRsn AND prsn = 0");
        $res = array('url' => array(), 'rsn' => array());

        $domain = kryn::getDomain($pDomainRsn);
        while ($page = dbFetch($resu, 1)) {

            kryn::deleteCache('page_' . $page['rsn']);

            $page = self::__pageModify($page, array('realurl' => ''));
            $newRes = self::updateUrlCacheChilds($page, $domain);
            $res['url'] = array_merge($res['url'], $newRes['url']);
            $res['rsn'] = array_merge($res['rsn'], $newRes['rsn']);
            //$res['r2d'] = array_merge( $res['r2d'], $newRes['r2d'] );
        }

        $aliasRes = dbExec('SELECT to_page_rsn, url FROM %pfx%system_urlalias WHERE domain_rsn = ' . $pDomainRsn);
        while ($row = dbFetch($aliasRes)) {
            $res['alias'][$row['url']] = $row['to_page_rsn'];
        }

        self::updatePage2DomainCache();
        kryn::setCache("systemUrls-$pDomainRsn", $res);
        return $res;
    }

    public static function updatePage2DomainCache() {

        $r2d = array();
        $res = dbExec('SELECT rsn, domain_rsn FROM %pfx%system_pages ');

        while ($row = dbFetch($res)) {
            $r2d[$row['domain_rsn']] .= $row['rsn'] . ',';
        }
        kryn::setCache('systemPages2Domain', $r2d);
        return $r2d;
    }

    public static function updateUrlCacheChilds($pPage, $pDomain = false) {
        $res = array('url' => array(), 'rsn' => array(), 'r2d' => array());

        if ($pPage['type'] < 2) { //page or link or folder
            if ($pPage['realurl'] != '') {
                $res['url']['url=' . $pPage['realurl']] = $pPage['rsn'];
                $res['rsn'] = array('rsn=' . $pPage['rsn'] => $pPage['realurl']);
            } else {
                $res['rsn'] = array('rsn=' . $pPage['rsn'] => $pPage['url']);
            }
        }

        $pages = dbExfetchAll("SELECT rsn, title, url, type, link
                             FROM %pfx%system_pages
                             WHERE prsn = ?", array($pPage['rsn']+0));

        if (is_array($pages)) {
            foreach ($pages as $page) {


                kryn::deleteCache('page_' . $page['rsn']);

                $page = self::__pageModify($page, $pPage);
                $newRes = self::updateUrlCacheChilds($page);

                $res['url'] = array_merge($res['url'], $newRes['url']);
                $res['rsn'] = array_merge($res['rsn'], $newRes['rsn']);
                $res['r2d'] = array_merge($res['r2d'], $newRes['r2d']);

            }
        }
        return $res;
    }

    public static function __pageModify($page, $pPage) {
        if ($page['type'] == 0) {
            $del = '';
            if ($pPage['realurl'] != '')
                $del = $pPage['realurl'] . '/';
            $page['realurl'] = $del . $page['url'];

        } elseif ($page['type'] == 1) { //link
            if ($page['url'] == '') { //if empty, use parent-url else use url-hiarchy
                $page['realurl'] = $pPage['realurl'];
            } else {
                $del = '';
                if ($pPage['realurl'] != '')
                    $del = $pPage['realurl'] . '/';
                $page['realurl'] = $del . $page['url'];
            }

            $page['prealurl'] = $page['link'];
        } else if ($page['type'] != 3) { //no deposit
            //ignore the hiarchie-item
            $page['realurl'] = $pPage['realurl'];
        }
        return $page;
    }

    public static function getPage($pRsn, $pLock = false) {

        $pRsn = $pRsn + 0;

        $res = self::getPageByRsn($pRsn);
        //$res['resourcesCss'] = kryn::readFile(PATH_MEDIA."css/_pages/$pRsn.css");
        //$res['resourcesJs'] = kryn::readFile(PATH_MEDIA."js/_pages/$pRsn.js");

        $curVersion = dbTableFetch('system_pagesversions', 1, "page_rsn = $pRsn AND active = 1");
        if (!$curVersion['rsn'] > 0) {
            $curVersion = dbTableFetch('system_pagesversions', 1, "page_rsn = $pRsn ORDER BY rsn DESC");
        }

        $contents = self::getVersion($pRsn, $curVersion['rsn']);
        $res['_activeVersion'] = $curVersion['rsn'];

        $res['alias'] = dbExfetch('SELECT * FROM %pfx%system_urlalias WHERE to_page_rsn=' . $pRsn, -1);

        $domain =
            dbExfetch("SELECT d.rsn FROM %pfx%system_domains d, %pfx%system_pages p WHERE p.domain_rsn = d.rsn AND p.rsn = $pRsn");
        kryn::$domain = $domain;

        $cachedUrls =& kryn::readCache('systemUrls');
        $res['realUrl'] = $cachedUrls['rsn']['rsn=' . $pRsn];
        $res['contents'] = json_encode($contents);

        $res['versions'] =
            dbExfetch("SELECT version_rsn, MAX(mdate) FROM %pfx%system_contents WHERE page_rsn = $pRsn GROUP BY version_rsn", DB_FETCH_ALL);

        json($res);
    }
}

?>
