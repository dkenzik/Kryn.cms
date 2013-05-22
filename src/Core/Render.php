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

use Core\Models\Content;
use Core\Render\TypeNotFoundException;
use Propel\Runtime\Map\TableMap;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Html render class
 *
 * @author MArc Schmidt <marc@Kryn.org>
 *
 * @events onRenderSlot
 *
 */

class Render
{
    /**
     * Cache of the current contents stage.
     *
     * @var array
     */
    public static $contents;

    /**
     *
     * Build the HTML for given page. If pPageId is a deposit, it returns with Kryn/blankLayout.tpl as layout, otherwise
     * it returns the layouts with all it contents.
     *
     * @static
     *
     * @param  bool         $pPageId
     * @param  bool         $pSlotId
     * @param  bool         $pProperties
     *
     * @return mixed|string
     */
    public static function renderPage($pPageId = false, $pSlotId = false, $pProperties = false)
    {
        if (self::$contents) {
            $oldContents = self::$contents;
        }

        Kryn::$forceKrynContent = true;
        Kryn::getLogger()->addDebug('renderPage(' . $pPageId . ', ' . $pSlotId . ')');

        if ($pPageId == Kryn::$page->getId()) {
            //endless loop
            return 'You produced a endless loop. Please check your latest changed pages.';
        }

        if (!$pPageId) {

            $pPageId = Kryn::$page->getId();

        } elseif ($pPageId != Kryn::$page->getId()) {

            $oldPage = Kryn::$page;
            Kryn::$page = Kryn::getPage($pPageId, true);

            if (!Kryn::$page) {
                Kryn::$page = $oldPage;

                return 'page_not_found';
            }

            Kryn::$nestedLevels[] = Kryn::$page;
        }

        Kryn::getEventDispatcher()->dispatch('core/render/contents/pre');

        if (file_exists($file = 'css/_pages/' . $pPageId . '.css')) {
            Kryn::getResponse()->addCssFile($file);
        }

        if (file_exists($file = 'js/_pages/' . $pPageId . '.js')) {
            Kryn::getResponse()->addJsFile($file);
        }

        self::$contents[$pSlotId] =& PageController::getSlotContents($pSlotId, $pSlotId);

        if (Kryn::$page->getType() == 3) { //deposit
            Kryn::$page->setLayout('core/blankLayout.tpl');
        }

        $arguments = array($pPageId, $pSlotId, &self::$contents[$pSlotId]);
        Kryn::getEventDispatcher()->dispatch('core/render/contents', new GenericEvent($arguments));

        if ($pSlotId) {
            $html = self::renderContents(self::$contents[$pSlotId], $pProperties);
        } else {
            $html = tFetch(Kryn::$page->getLayout());
        }

        if ($oldContents) {
            self::$contents = $oldContents;
        }
        if ($oldPage) {
            Kryn::$page = $oldPage;
            array_pop(Kryn::$nestedLevels);
        }
        Kryn::$forceKrynContent = false;

        $arguments = array($pPageId, $pSlotId, &$html);
        Kryn::getEventDispatcher()->dispatch('core/render/contents/post', new GenericEvent($arguments));

        return $html;
    }

    /**
     * Build HTML for given contents.
     *
     * @param array $pContents
     * @param array $pSlotProperties
     *
     * @return string
     * @internal
     */
    public static function renderContents(&$pContents, $pSlotProperties)
    {
        $contents = array();

        if (!($pContents instanceof \Traversable)) {
            return;
        }

        foreach ($pContents as $content) {

            $access = true;

            if (
                ($content->getAccessFrom() + 0 > 0 && $content->getAccessFrom() > time()) ||
                ($content->getAccessTo() + 0 > 0 && $content->getAccessTo() < time())
            ) {
                $access = false;
            }

            if ($content->getHide()) {
                $access = false;
            }

            if ($access && $content->getAccessFromGroups()) {

                $access = false;
                $groups = ',' . $content->getAccessFromGroups() . ',';

                $userGroups = Kryn::getClient()->getUser()->getUserGroups();

                foreach ($userGroups as $group) {
                    if (strpos($groups, ',' . $group->getGroupId() . ',') !== false) {
                        $access = true;
                        break;
                    }
                }

                if (!$access) {
                    $adminGroups = Kryn::getClient()->getUser()->getUserGroups();
                    foreach ($adminGroups as $group) {
                        if (strpos($groups, ',' . $group->getGroupId() . ',') !== false) {
                            $access = true;
                            break;
                        }
                    }
                }
            }

            if ($access) {
                $contents[] = $content;
            }
        }

        $count = count($contents);
        /*
         * Compatibility
         */
        $data['layoutContentsMax'] = $count;
        $data['layoutContentsIsFirst'] = true;
        $data['layoutContentsIsLast'] = false;
        $data['layoutContentsId'] = $pSlotProperties['id'];
        $data['layoutContentsName'] = $pSlotProperties['name'];

        $i = 0;

        //$oldContent = $tpl->getTemplateVars('content');
        $argument = array($slot);
        Event::fire('onBeforeRenderSlot', $argument);

        $html = '';

        if ($count > 0) {
            foreach ($contents as &$content) {
                if ($i == $count) {
                    $data['layoutContentsIsLast'] = true;
                }

                if ($i > 0) {
                    $data['layoutContentsIsFirst'] = false;
                }

                $i++;
                $data['layoutContentsIndex'] = $i;

                $html .= self::renderContent($content, $data);

            }
        }

        $argument = array($data, &$html);
        Event::fire('onRenderSlot', $argument);

        if ($pSlotProperties['assign'] != "") {
            Kryn::getInstance()->assign($pSlotProperties['assign'], $html);
            return '';
        }

        return $html;

    }

    /**
     * Build HTML for given content.
     *
     * @param Content $content
     * @param array   $parameters
     *
     * @return string
     * @throws Render\TypeNotFoundException
     */
    public static function renderContent(Content $content, $parameters = array())
    {
        $type = $content->getType();
        $class = 'Core\\Render\\Type' . ucfirst($type);

        if (class_exists($class)) {
            /** @var \Core\Render\TypeInterface $typeRenderer */
            $typeRenderer = new $class($content, $parameters);
            $html = $typeRenderer->render();
        } else {
            throw new TypeNotFoundException(sprintf(
                'Type renderer for `%s` not found. [%s]',
                $content->getType(),
                json_encode($content)
            ));
        }

        $data['content'] = $content->toArray(TableMap::TYPE_STUDLYPHPNAME);
        $data['parameter'] = $parameters;
        $data['html'] = $html;

        Kryn::getEventDispatcher()->dispatch('core/render/content/pre', new GenericEvent($data));

        $unsearchable = false;
        if ((!is_array($content->getAccessFromGroups()) && $content->getAccessFromGroups() != '') ||
            (is_array($content->getAccessFromGroups()) && count($content->getAccessFromGroups()) > 0) ||
            ($content->getAccessFrom() > 0 && $content->getAccessFrom() > time()) ||
            ($content->getAccessTo() > 0 && $content->getAccessTo() < time()) ||
            $content->getUnsearchable()
        ) {
            $unsearchable = true;
        }

        Event::fire('onRenderContent', $argument);

        if ($content->getTemplate() == '' || $content->getTemplate() == '-') {
            if ($unsearchable) {
                $result = '<!--unsearchable-begin-->' . $data['html'] . '<!--unsearchable-end-->';
            }
        } else {
            $result = Kryn::getInstance()->renderView($content->getTemplate(), $data);

            if ($unsearchable) {
                $result = '<!--unsearchable-begin-->' . $result . '<!--unsearchable-end-->';
            }
        }

        $argument = array(&$result, $data);
        Kryn::getEventDispatcher()->dispatch('core/render/content', new GenericEvent($argument));

        return $result;
    }

    public static function updateDomainCache()
    {
        $res = dbQuery('SELECT * FROM ' . pfx . 'system_domain');
        $domains = array();

        while ($domain = dbFetch($res, 1)) {

            $code = $domain['domain'];
            $lang = "";
            if ($domain['master'] != 1) {
                $lang = '_' . $domain['lang'];
                $code .= $lang;
            }

            $domains[$code] = $domain['id'];

            $alias = explode(",", $domain['alias']);
            if (count($alias) > 0) {
                foreach ($alias as $ad) {
                    $domainName = str_replace(' ', '', $ad);
                    if ($domainName != '') {
                        $domains[$domainName . $lang] = $domain['id'];
                    }
                }
            }

            $redirects = explode(",", $domain['redirect']);
            if (count($redirects) > 0) {
                foreach ($redirects as $redirect) {
                    $domainName = str_replace(' ', '', $redirect);
                    if ($domainName != '') {
                        $domains['_redirects'][$domainName] = $domain['id'];
                    }
                }
            }

            Kryn::deleteCache('systemDomain-' . $domain['id']);
        }
        Kryn::setCache('systemDomains', $domains);
        dbFree($res);

        return $domains;
    }

    public static function updateMenuCache($pDomainRsn)
    {
        $resu = dbQuery(
            "SELECT id, title, url, pid FROM " . pfx . "system_node WHERE
                         domain_id = $pDomainRsn AND (type = 0 OR type = 1 OR type = 4)"
        );
        $res = array();
        while ($page = dbFetch($resu, 1)) {

            if ($page['type'] == 0) {
                $res[$page['id']] = self::getParentMenus($page);
            } else {
                $res[$page['id']] = self::getParentMenus($page, true);
            }

        }

        Kryn::setCache("menus-$pDomainRsn", $res);
        Kryn::invalidateCache('navigation_' . $pDomainRsn);

        dbFree($resu);

        return $res;
    }

    public static function getParentMenus($pPage, $pAllParents = false)
    {
        $pid = $pPage['parent_id'];
        $res = array();
        while ($pid != 0) {
            $parent_page =
                dbExfetch("SELECT id, title, url, pid, type FROM " . pfx . "system_node WHERE id = " . $pid, 1);
            if ($parent_page['type'] == 0 || $parent_page['type'] == 1 || $parent_page['type'] == 4) {
                //page or link or page-mount
                array_unshift($res, $parent_page);
            } elseif ($pAllParents) {
                array_unshift($res, $parent_page);
            }
            $pid = $parent_page['parent_id'];
        }

        return $res;
    }

    public static function updateUrlCache($pDomainRsn)
    {
        $pDomainRsn = $pDomainRsn + 0;

        $resu = dbQuery(
            "SELECT id, title, url, type, link FROM " . pfx . "system_node WHERE domain_id = $pDomainRsn AND parent_id IS NULL"
        );
        $res = array('url' => array(), 'id' => array());

        $domain = Kryn::getDomain($pDomainRsn);

        while ($page = dbFetch($resu)) {

            $page = self::__pageModify($page, array('realurl' => ''));
            $newRes = self::updateUrlCacheChildren($page, $domain);
            $res['url'] = array_merge($res['url'], $newRes['url']);
            $res['id'] = array_merge($res['id'], $newRes['id']);
        }

        $aliasRes = dbQuery('SELECT node_id, url FROM ' . pfx . 'system_node_alias WHERE domain_id = ' . $pDomainRsn);
        while ($row = dbFetch($aliasRes)) {
            $res['alias'][$row['url']] = $row['node_id'];
        }

        self::updatePage2DomainCache();
        Kryn::setCache("core/node-ids-to-url-$pDomainRsn", $res);
        dbFree($aliasRes);
        dbFree($resu);

        return $res;
    }

    public static function updatePage2DomainCache()
    {
        $r2d = array();
        $res = dbQuery('SELECT id, domain_id FROM ' . pfx . 'system_node ');

        while ($row = dbFetch($res)) {
            $r2d[$row['domain_id']] .= $row['id'] . ',';
        }
        dbFree(res);

        Kryn::setDistributedCache('core/node/toDomains', $r2d);

        return $r2d;
    }

    public static function updateUrlCacheChildren($pPage, $pDomain = false)
    {
        $res = array('url' => array(), 'id' => array(), 'r2d' => array());

        if ($pPage['type'] < 2) { //page or link or folder
            if ($pPage['realurl'] != '') {
                $res['url']['url=' . $pPage['realurl']] = $pPage['id'];
                $res['id'] = array('id=' . $pPage['id'] => $pPage['realurl']);
            } else {
                $res['id'] = array('id=' . $pPage['id'] => $pPage['url']);
            }
        }

        $pages = dbExfetchAll(
            "SELECT id, title, url, type, link
                                         FROM " . pfx . "system_node
                             WHERE parent_id = " . $pPage['id']
        );

        if (is_array($pages)) {
            foreach ($pages as $page) {

                Kryn::deleteCache('page_' . $page['id']);

                $page = self::__pageModify($page, $pPage);
                $newRes = self::updateUrlCacheChildren($page);

                $res['url'] = array_merge($res['url'], $newRes['url']);
                $res['id'] = array_merge($res['id'], $newRes['id']);
                $res['r2d'] = array_merge($res['r2d'], $newRes['r2d']);

            }
        }

        return $res;
    }

    public static function __pageModify($page, $pPage)
    {
        if ($page['type'] == 0) {
            $del = '';
            if ($pPage['realurl'] != '') {
                $del = $pPage['realurl'] . '/';
            }
            $page['realurl'] = $del . $page['url'];

        } elseif ($page['type'] == 1) { //link
            if ($page['url'] == '') { //if empty, use parent-url else use url-hiarchy
                $page['realurl'] = $pPage['realurl'];
            } else {
                $del = '';
                if ($pPage['realurl'] != '') {
                    $del = $pPage['realurl'] . '/';
                }
                $page['realurl'] = $del . $page['url'];
            }

            $page['prealurl'] = $page['link'];
        } elseif ($page['type'] != 3) { //no deposit
            //ignore the hiarchie-item
            $page['realurl'] = $pPage['realurl'];
        }

        return $page;
    }

}
