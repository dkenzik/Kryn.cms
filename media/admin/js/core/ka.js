if (typeof ka == 'undefined') window.ka = {};

ka.clipboard = {};
ka.settings = {};

ka.performance = false;
ka.streamParams = {};

ka.uploads = {};
ka._links = {};

window.PATH_MODULE = 'module/';
window.PATH_MEDIA = 'media/';

/*
 * Build the administration interface after login
 */
ka.init = function () {

    //ka.buildClipboardMenu();
    ka.buildUploadMenu();

    if (!document.body.hasClass('ka-no-desktop')){
        if (!ka.desktop)
            ka.desktop = new ka.Desktop(document.id('desktop'));

        ka.desktop.load();
    }

    if (!ka.helpsystem)
        ka.helpsystem = new ka.Helpsystem(document.id('desktop'));

    if (ka._iconSessionCounterDiv) {
        ka._iconSessionCounterDiv.destroy();
    }
    ka._iconSessionCounterDiv = new Element('div', {
        'class': 'iconbar-item icon-users',
        title: t('Visitors')
    }).inject(document.id('iconbar'));

    ka._iconSessionCounter = new Element('span', {text: 0}).inject(ka._iconSessionCounterDiv);


    window.addEvent('resize', ka.checkMainBarWidth);
    ka.buildUserMenu();

    window.fireEvent('init');

    if (ka._crawler) {
        ka._crawler.stop();
        delete ka._crawler;
        ka._crawler = new ka.Crawler();
    } else {
        ka._crawler = new ka.Crawler();
    }

    //ka.loadStream();

    window.onbeforeunload = function (evt) {

        if (ka.wm.getWindowsCount() > 0) {
            var message = _('There are open windows. Are you sure you want to leaving the administration?');
            if (typeof evt == 'undefined') {
                evt = window.event;
            }
            if (evt) {
                evt.returnValue = message;
            }
            return message;
        }
    };

    $(document.body).addEvent('contextmenu', function (e) {
        e = e || window.event;
        e.cancelBubble = true;
        e.returnValue = false;
        if (e.stopPropagation) e.stopPropagation();
        if (e.preventDefault) e.preventDefault();
        if (e.target) {
            $(e.target).fireEvent('mousedown', e);
        }
        return false;
    });

    window.addEvent('mouseup', function () {
        ka.destroyLinkContext();
    });
};


window.addEvent('stream', function (res) {
    $('serverTime').set('html', res.time);
    ka._iconSessionCounter.set('text', res.sessions_count);
});

window.addEvent('stream', function (res) {
    if (res.corruptJson) {
        Array.each(res.corruptJson, function (item) {
            ka.helpsystem.newBubble(_('Extension config Syntax Error'), _('There is an error in your inc/module/%s/config.json').replace('%s', item), 4000);
        });
    }
});

ka.tryLock = function (pWin, pKey, pForce) {
    if (!pForce) {

        new Request.JSON({url: _path + 'admin/backend/tryLock', noCache: 1, onComplete: function (res) {

            if (!res.locked) {
                ka.lockNotPossible(pWin, res);
            }

        }}).get({key: pKey, force: pForce ? 1 : 0});

    } else {
        ka.lockContent(pKey);
    }
}

ka.alreadyLocked = function (pWin, pResult) {

    pWin._alert(_('Currently, a other user has this content open.'));

}

ka.bytesToSize = function (bytes) {
    var sizes = ['Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
    if (!bytes) return '0 Bytes';
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    if (i == 0) {
        return (bytes / Math.pow(1024, i)) + ' ' + sizes[i];
    }
    return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
};


ka.toggleMainbar = function () {
    if ($('border').getStyle('top').toInt() != 0) {
        $('border').tween('top', 0);
        $('arrowUp').setStyle('background-color', 'transparent');
        $('arrowUp').morph({
            'top': 0,
            left: 0
        });
    } else {
        $('border').tween('top', -76);
        $('arrowUp').setStyle('background-color', '#399BC7');
        $('arrowUp').morph({
            'top': 61,
            left: 32
        });
    }
}


ka.buildUserMenu = function(){
    if (ka.userMenu) ka.userMenu.destroy();

    ka.userMenu = new Element('div', {
        'class': 'bar-dock-menu-style',
        style: 'right: 36px;'
    }).inject(document.id('border'))

    new Element('a', {
        text: t('Edit me')
    })
    .addEvent('click', function(){
        ka.wm.open('users/users/editMe', {values: {rsn: window._user_rsn}});
    })
    .inject(ka.userMenu);

    new Element('a', {
        text: t('Logout')
    })
    .addEvent('click', function(){
        ka.ai.logout();
    })
    .inject(ka.userMenu);

    var xoffset = ka.userMenu.getSize().x;
    xoffset -= document.id('user-username').getSize().x;
    xoffset *= -1;

    ka.makeMenu(document.id('user-username'), ka.userMenu, true, {y: 46, x: xoffset+2});
}

ka.clearCache = function () {

    if (!ka.cacheToolTip) {
        ka.cacheToolTip = new ka.Tooltip($('ka-btn-clear-cache'), _('Clearing cache ...'), 'top');
    }
    ka.cacheToolTip.show();

    new Request.JSON({url: _path + 'admin/backend/clearCache', noCache: 1, onComplete: function (res) {
        ka.cacheToolTip.stop(_('Cache cleared'));
    }}).get();


}

ka.getDomain = function (pRsn) {
    var result = [];
    ka.settings.domains.each(function (domain) {
        if (domain.rsn == pRsn) {
            result = domain;
        }
    })
    return result;
}

ka.loadSettings = function (pOnlyThisKeys) {
    if (!ka.settings) ka.settings = {};

    new Request.JSON({url: _path + 'admin/backend/getSettings', noCache: 1, async: false, onComplete: function (res) {
        if (res.error == 'access_denied') return;

        Object.each(res, function(val,key){
            ka.settings[key] = val;
        })

        ka.settings['images'] = ['jpg', 'jpeg', 'bmp', 'png', 'gif', 'psd'];

        if (ka.settings.user && ka.settings.user.userBg) {
            document.id(document.body).setStyle('background-image', 'url(' + _path + PATH_MEDIA + ka.settings.user.userBg + ')');
        }

        if (ka.settings.system && ka.settings.system.systemtitle) {
            document.title = ka.settings.system.systemtitle + t(' | Kryn.cms Administration');
        }

    }.bind(this)}).get({lang: window._session.lang, keys: pOnlyThisKeys});
}

ka.loadLanguage = function (pLang) {
    if (!pLang) pLang = 'en';
    window._session.lang = pLang;

    Cookie.write('kryn_language', pLang);

    Asset.javascript(_path + 'admin/getLanguagePluralForm:' + pLang);

    new Request.JSON({url: _path + 'admin/getLanguage:' + pLang, async: false, noCache: 1, onComplete: function (res) {
        ka.lang = res;
        Locale.define('en-US', 'Date', res.mootools);
    }}).get();

}


ka.saveUserSettings = function () {
    if (ka.lastSaveUserSettings) {
        ka.lastSaveUserSettings.cancel();
    }

    ka.settings.user = new Hash(ka.settings.user);

    ka.lastSaveUserSettings = new Request.JSON({url: _path + 'admin/backend/saveUserSettings', noCache: 1, onComplete: function (res) {
    }}).post({ settings: JSON.encode(ka.settings.user) });
}

ka.resetWindows = function () {
    ka.settings.user['windows'] = new Hash();
    ka.saveUserSettings();
    ka.wm.resizeAll();
}

ka.check4Updates = function () {
    if (window._session.user_rsn == 0) return;
    new Request.JSON({url: _path + 'admin/system/module/check4updates', noCache: 1, onComplete: function (res) {
        if (res && res.found) {
            ka.displayNewUpdates(res.modules);
        }
        ka.check4Updates.delay(10 * (60 * 1000));
    }}).post();
}

ka.checkPageAccessHasCode = function (pCodes, pAction) {
    var access = (pCodes.indexOf(pAction) != -1 );

    return access;

    /*
     Why did we do that?
     if( !access ){

     var acl_all = false;
     var acl_tab = false;

     $H(ka.settings.pageAcls).each(function(subAll,keyAll){

     $H(subAll).each(function(tabSubs, tabKey){
     if( tabSubs.indexOf(pAction) != -1 && tabKey != 'tree' ){
     acl_tab = tabKey;
     }
     })

     });

     if( acl_tab ){
     access = (pCodes.indexOf( acl_tab ) != -1 );
     }

     }
     return access;
     */
}


ka.checkDomainAccess = function (pRsn, pAction) {
    return ka.checkPageAccess(pRsn, pAction, 'd');
}

ka.checkPageAccess = function (pRsn, pAction, pType) {
    if (!pType) pType = 'p'; //p=page, d=domain

    if (!pRsn) return false;

    if (ka.settings.acl_pages.length == 0) return true;

    var access = false;

    var current_rsn = pRsn;
    var current_type = pType;
    var not_found = true;
    var parent_acl = false;

    var codes = [];

    while (not_found) {

        var acl = ka._getPageAcl(current_rsn, current_type);
        if (acl && acl.code) {
            codes = acl.code.split('[')[1].replace(']', '').split(',');
            if (ka.checkPageAccessHasCode(codes, pAction)) {
                if ((parent_acl == false) || (parent_acl == true && acl.code.indexOf('%') != -1)) {
                    access = (acl.access == 1) ? true : false;
                    not_found = false;
                }
            }
        }

        if (not_found == true && current_type == 'd') {
            //no parent acl on domain-level
            not_found = false;
        }

        if (not_found == true && current_type == 'p') {
            //search and set parent
            var parent = ka.getPageParent(current_rsn);
            if (parent.domain) {
                //parent is domain
                current_rsn = parent.domain_rsn;
                current_type = 'd';
            } else {
                current_rsn = parent.rsn;
            }
            parent_acl = true;
        }
    }


    return access;
}

ka.getPageParent = function (pRsn) {

    var domain_rsn = ka.getDomainOfPage(pRsn);

    var page_tree = $H(ka.settings['menus_' + domain_rsn]).get(pRsn);
    var result = {prsn: 0, domain_rsn: domain_rsn, domain: true};

    if (!page_tree) return result;

    var page = page_tree[ page_tree.length - 1 ];

    if (page_tree.length >= 1 && page) {
        result = page;
        result.domain_rsn = domain_rsn;
    }

    return result;
}

ka.getDomainOfPage = function (pRsn) {
    var rsn = false;

    pRsn = ',' + pRsn + ',';
    Object.each(ka.settings.r2d, function (pages, domain_rsn) {
        var pages = ',' + pages + ',';
        if (pages.indexOf(pRsn) != -1) {
            rsn = domain_rsn;
        }
    });
    return rsn;
}

ka._getPageAcl = function (pRsn, pType) {
    var acl = false;
    ka.settings.acl_pages.each(function (item) {

        var type = item.code.substr(0, 1);
        if (pType != type) return;
        var rsn = item.code.substr(1, item.code.length).split('[')[0].replace('%', '');
        if (rsn == pRsn) {
            acl = item;
        }
    })
    return acl;
}


ka.checkAccess = function (pType, pCode, pAction, pRootHasAccess) {

    //self::normalizeCode( $pCode );

    if (!ka.settings.acls[pType] || ka.settings.acls[pType].length == 0) return true;

    var access = false;

    var current_code = $pCode;

    var not_found = true;
    var parent_acl = false;

    var codes = [];


    while (not_found) {

        var acl = ka.getAcl(pType, current_code);

        if (acl && acl['code']) {

            var code = acl.code.replace(']', ''); //str_replace(']', '', $acl['code']);
            var t = code.split('['); //explode('[', $code);
            var codes = false;
            if (t[1]) {
                codes = t[1].split(',');
            }

            if (codes == false || codes.contains(pAction)) {
                if ((parent_acl == false) || //i'am not a parent
                    (parent_acl == true && acl.code.indexOf('%') > 0)) {
                    access = (acl['access'] == 1) ? true : false;
                    not_found = false; //done
                }
            }
        }

        if (current_code == '/') {
            //we are at the top. no parents left
            if (pRootHasAccess == true) {
                access = true;
            }
            not_found = false; //go out
        }

        //go to parent
        if (not_found == true && current_code != '/') {
            //search and set parent
            pos = current_code.indexOf('/'); //strpos($current_code, '/');
            current_code = current_code.substr(0, pos); //substr( $current_code, 0, $pos );

            parent_acl = true;
        }
    }

    return access;
}

ka.getAcl = function (pType, current_code) {
    var acls = ka.settings.acls[pType];

    var acl = false;

    acls.each(function (item, key) {

        var code = item.code.replace('%', '');
        var t = code.split('[');
        code = t[0];
        if (current_code == code) {
            acl = item;
        }

    });
    return acl;
}


ka.addStreamParam = function (pKey, pVal) {
    ka.streamParams[pKey] = pVal;
}

ka.removeStreamParam = function (pKey) {
    delete ka.streamParams[pKey];
}


ka.loadStream = function () {
    if (ka._lastStreamid) {
        clearTimeout(ka._lastStreamid);
    }

    if (ka._lastStreamCounter) {
        clearTimeout(ka._lastStreamCounter);
    }

    _lastStreamCounter = (function () {
        if (window._session.user_rsn > 0) {
            new Request.JSON({url: _path + 'admin/backend/stream', noCache: 1, onComplete: function (res) {
                if (res) {
                    if (res.error == 'access_denied') {
                        ka.ai.logout(true);
                    } else {
                        ka.streamParams.last = res.last;
                        window.fireEvent('stream', res);
                        $('serverTime').set('html', res.time);
                    }
                }
                ka._lastStreamid = ka.loadStream.delay(5 * 1000);
            }}).post(ka.streamParams);
        }
    }).delay(50);
}

ka.startSearchCrawlerInfo = function (pHtml) {
    ka.stopSearchCrawlerInfo();

    this.startSearchCrawlerInfoMenu = new Element('div', {
        'class': 'ka-updates-menu',
        style: 'left: 170px; width: 177px;'
    }).inject($('border'));

    this.startSearchCrawlerInfoMenuHtml = new Element('div', {
        html: pHtml
    }).inject(this.startSearchCrawlerInfoMenu);

    this.startSearchCrawlerProgressLine = new Element('div', {
        style: 'position: absolute; bottom: 1px; left: 4px; width: 0px; height: 1px; background-color: #444;'
    }).inject(this.startSearchCrawlerInfoMenu);

    this.startSearchCrawlerInfoMenu.tween('top', 48);
}

ka.setSearchCrawlerInfo = function (pHtml) {
    this.startSearchCrawlerInfoMenuHtml.set('html', pHtml);
}

ka.stopSearchCrawlerInfo = function (pOutroText) {
    if (!this.startSearchCrawlerInfoMenu) return;

    var doOut = function () {
        this.startSearchCrawlerInfoMenu.tween('top', 17);
    }.bind(this);

    if (pOutroText) {
        this.startSearchCrawlerInfoMenuHtml.set('html', pOutroText);
        doOut.delay(2000);
    } else {
        doOut.call();
    }

}

ka.setSearchCrawlerProgress = function (pPos) {
    var maxLength = 177 - 8;
    var pos = maxLength * pPos / 100;
    this.startSearchCrawlerProgressLine.set('tween', {duration: 100});
    this.startSearchCrawlerProgressLine.tween('width', pos);
}

ka.stopSearchCrawlerProgress = function () {
    this.startSearchCrawlerProgressLine.set('tween', {duration: 10});
    this.startSearchCrawlerProgressLine.tween('width', 0);
}

ka.openSearchContextClose = function () {
    if (ka.openSearchContextLast) {
        ka.openSearchContextLast.destroy();
    }

}

ka.openSearchContext = function () {

    var button = $('ka-btn-create-search-index');

    ka.openSearchContextClose();

    this.openSearchContextLast = new Element('div', {
        'class': 'ka-searchcontext'
    }).inject($('border'));

    var pos = button.getPosition($('border'));
    var size = $('border').getSize();
    var right = size.x - pos.x;

    this.openSearchContextLast.setStyle('right', right - 30);

    new Element('img', {
        'class': 'ka-searchcontext-arrow',
        src: _path + PATH_MEDIA + '/admin/images/ka-tooltip-corner-top.png'
    }).inject(this.openSearchContextLast);

    this.openSearchContextContent = new Element('div', {
        'class': 'ka-searchcontext-content'
    }).inject(this.openSearchContextLast);

    this.openSearchContextBottom = new Element('div', {
        'class': 'ka-searchcontext-bottom'
    }).inject(this.openSearchContextLast);

    new ka.Button(_('Indexed pages')).addEvent('click',
        function () {
            ka.wm.open('admin/system/searchIndexerList');
        }).inject(this.openSearchContextBottom);


    ka.openSearchContextClearIndex = new ka.Button(_('Clear index')).addEvent('click',
        function () {
            ka.openSearchContextClearIndex.startTip(_('Clearing index ...'));

            new Request.JSON({url: _path + 'admin/backend/searchIndexer/clearIndex', noCache: 1, onComplete: function (pRes) {
                ka.openSearchContextClearIndex.stopTip(_('Done'));
            }.bind(this)}).post();
        }).inject(this.openSearchContextBottom);

    new Element('a', {
        style: 'position: absolute; right: 5px; top: 3px; text-decoration: none; font-size: 13px;',
        text: 'x',
        title: _('Close'),
        href: 'javascript: ;'
    }).addEvent('click', ka.openSearchContextClose).inject(this.openSearchContextLast);

    ka.openSearchContextLoad();

}


ka.openSearchContextLoad = function () {

    ka.openSearchContextContent.set('html', '<br /><br /><div style="text-align: center; color: gray;">' + _('Loading ...') + '</div>');


    ka.openSearchContextTable = new ka.Table([
        [_('Domain'), 190],
        [_('Indexed pages')]
    ]);

    new Request.JSON({url: _path + 'admin/backend/searchIndexer/getIndexedPages4AllDomains',
        noCache: 1,
        onComplete: function (pRes) {

            ka.openSearchContextContent.empty();

            ka.openSearchContextTable.inject(ka.openSearchContextContent);

            if (pRes) {
                pRes.each(function (domain) {
                    ka.openSearchContextTable.addRow([domain.domain + '<span style="color:gray"> (' + domain.lang + ')</span>', domain.indexedcount]);
                });
            }

        }
    }).post();


}


ka.displayNewUpdates = function (pModules) {
    if (this.newUpdatesMenu) {
        this.newUpdatesMenu.destroy();
    }

    var html = _('New updates !');
    /*
     pModules.each(function(item){
     html += item.name+' ('+item.newVersion+')<br />';
     });
     */
    this.newUpdatesMenu = new Element('div', {
        'class': 'ka-updates-menu',
        html: html
    })/*
     .addEvent('mouseover', function(){
     this.tween('height', this.scrollHeight );
     })
     .addEvent('mouseout', function(){
     this.tween('height', 24 );
     })
     */.addEvent('click',
        function () {
            ka.wm.open('admin/system/module', {updates: 1});
        }).inject($('border'));
    this.newUpdatesMenu.tween('top', 48);
}

ka.buildClipboardMenu = function () {
    ka.clipboardMenu = new Element('div', {
        'class': 'ka-clipboard-menu'
    }).inject($('header'), 'before');
}

ka.buildUploadMenu = function () {
    ka.uploadMenu = new Element('div', {
        'class': 'ka-upload-menu',
        styles: {
            height: 22
        }
    }).addEvent('mouseover',
        function () {
            this.tween('height', this.scrollHeight);
        }).addEvent('mouseout',
        function () {
            this.tween('height', 22);
        }).inject($('header'), 'before');

    ka.uploadMenuInfo = new Element('div', {
        'class': 'ka-upload-menu-info'
    }).inject(ka.uploadMenu);
}

ka.getClipboard = function () {
    return ka.clipboard;
}

ka.setClipboard = function (pTitle, pType, pValue) {
    ka.clipboard = { type: pType, value: pValue };
    //ka.clipboardMenu.set('html', pTitle);
    //ka.clipboardMenu.tween('top', 50);
}

ka.clearClipboard = function () {
    ka.clipboard = {};
    //ka.clipboardMenu.tween('top', 20);
}

ka.createModuleMenu = function () {
    if (ka._moduleMenu) {
        ka._moduleMenu.destroy();
    }

    ka._moduleMenu = new Element('div', {
        'class': 'ka-module-menu',
        style: 'left: -250px;'
    }).addEvent('mouseover', ka.toggleModuleMenuIn.bind(this, true)).addEvent('mouseout', ka.toggleModuleMenuOut).inject(document.body);
    ka._moduleMenu.set('tween', {transition: Fx.Transitions.Quart.easeOut});

    ka.moduleToggler = new Element('div', {
        'class': 'ka-module-toggler'
    }).addEvent('click',
        function () {
            ka.toggleModuleMenuIn();
        }).inject(ka._moduleMenu);

    new Element('img', {
        src: _path + PATH_MEDIA + _('admin/images/extensions-text.png')
    }).addEvent('click', ka.toggleModuleMenuIn).inject(ka.moduleToggler);

    new Element('div', {
        html: _('Extensions'),
        style: 'padding-left: 15px; color: white; font-weight: bold; padding-top: 4px;'
    }).inject(ka._moduleMenu);

    ka.moduleItems = new Element('div', {
        'class': 'ka-module-items'
    }).inject(ka._moduleMenu);


    ka.moduleItemsScrollerContainer = new Element('div', {
        'class': 'ka-module-items-scroller-container'
    }).inject(ka._moduleMenu);

    ka.moduleItemsScroller = new Element('div', {
        'class': 'ka-module-items-scroller'
    }).inject(ka.moduleItemsScrollerContainer);
    //}).inject( ka._moduleMenu );

    //window.addEvent('resize', ka.updateModuleItemsScrollerSize);


    ka.moduleItemsScroller.addEvent('mousedown', function () {
        ka.moduleItemsScrollerDown = true;
    });

    ka.moduleItems.addEvent('mousewheel', function (e) {

        var newPos = ka.moduleItemsScrollSlider.step;

        if (e.wheel > 0) {
            //up
            newPos--;
        } else if (e.wheel < 0) {
            //down
            newPos++;
        }
        if (newPos > ka.moduleItemsScrollSlider.max) {
            newPos = ka.moduleItemsScrollSlider.max;
        }

        if (newPos < ka.moduleItemsScrollSlider.min) {
            newPos = ka.moduleItemsScrollSlider.min;
        }

        ka.moduleItemsScrollSlider.set(newPos);

    });
    ka.toggleModuleMenuOut(true);
}

ka.updateModuleItemsScrollerSize = function () {

    var completeSize = ka.moduleItems.getScrollSize();
    var size = ka.moduleItems.getSize();

    var diffHeight = completeSize.y - size.y;

    if (diffHeight > 12) {
        ka.moduleItemsScroller.setStyle('display', 'block');

        var proz = Math.ceil(diffHeight / (completeSize.y / 100));

        var newDiffHeight = (proz / 100) * size.y;

        var scrollBarHeight = size.y - newDiffHeight;

        ka.moduleItemsScroller.setStyle('height', scrollBarHeight);

        //if( ka.moduleItemsScrollSlider )
        //	ka.moduleItemsScrollSlider.deattach();

        ka.moduleItemsScrollSlider = new Slider(ka.moduleItemsScrollerContainer, ka.moduleItemsScroller, {
            wheel: true,
            mode: 'vertical',
            steps: 25,
            onChange: function (pPos) {
                var scrollTop = ((pPos * 4) / 100) * diffHeight;
                ka.moduleItems.scrollTo(0, scrollTop);
            },
            onComplete: function () {
                ka.moduleItemsScrollerDown = false;
            }
        });
        ka.moduleItemsScrollSlider.set(0);
    } else {
        ka.moduleItemsScroller.setStyle('display', 'none');
        ka.moduleItems.scrollTo(0, 0);
    }

}

ka.toggleModuleMenuIn = function (pOnlyStay) {


    if (ka.lastModuleMenuOutTimer) {
        clearTimeout(ka.lastModuleMenuOutTimer);
    }

    if (ka.ModuleMenuOutOpen == true) {
        return;
    }

    if (pOnlyStay == true) {
        return;
    }

    ka.ModuleMenuOutOpen = false;
    ka._moduleMenu.set('tween', {transition: Fx.Transitions.Quart.easeOut, onComplete: function () {
        ka.ModuleMenuOutOpen = true;
    }});
    ka._moduleMenu.tween('left', 0);
    ka.moduleToggler.store('active', true);
    ka.moduleItems.setStyle('right', 0);
    //ka.moduleItemsScroller.setStyle('left', 188);
    //ka.moduleItemsScrollerContainer.setStyle('right', 0);
}

ka.toggleModuleMenuOut = function (pForce) {

    //if( !ka.ModuleMenuOutOpen && pForce != true )
    //	return;

    if (ka.lastModuleMenuOutTimer) {
        clearTimeout(ka.lastModuleMenuOutTimer);
    }

    ka.ModuleMenuOutOpen = false;

    ka.lastModuleMenuOutTimer = (function () {
        ka._moduleMenu.set('tween', {transition: Fx.Transitions.Quart.easeOut, onComplete: function () {
            ka.ModuleMenuOutOpen = false;
        }});
        ka._moduleMenu.tween('left', (ka._moduleMenu.getSize().x - 33) * -1);
        ka.moduleToggler.store('active', false);
        ka.moduleItems.setStyle('right', 40);
        //ka.moduleItemsScrollerContainer.setStyle('right', 50);
        ka.destroyLinkContext();
    }).delay(300);

}

ka.toggleModuleMenu = function () {
    if (ka.moduleToggler.retrieve('active') != true) {
        ka.toggleModuleMenuIn();
    } else {
        ka.toggleModuleMenuOut();
    }
}

ka.loadMenu = function () {

    if (ka.lastLoadMenuReq) ka.lastLoadMenuReq.cancel();

    ka.lastLoadMenuReq = new Request.JSON({url: _path + 'admin/backend/getMenus/', noCache: true, onComplete: function (res) {

        //ka.createModuleMenu();
        //ka.moduleItems.empty();
        $('mainLinks').empty();
        if (ka.additionalMainMenu) {
            ka.additionalMainMenu.destroy();
            ka.additionalMainMenuContainer.destroy();
            delete ka.additionalMainMenu;
        }

        ka.removedMainMenuItems = [];
        delete ka.mainMenuItems;

        var mlinks = res;

        Object.each(mlinks['admin'], function (item, pCode) {
            ka.addAdminLink(item, pCode, 'admin');
        });

        delete mlinks['admin'];

        if (mlinks['users']) {
            Object.each(mlinks['users'], function (item, pCode) {
                ka.addAdminLink(item, pCode, 'users');
            });
        }
        delete mlinks['users'];

        Object.each(ka.settings.configs, function (config, extKey) {
            if (!mlinks[extKey]) return;

            Object.each(mlinks[extKey], function (item, pCode) {
                ka.addAdminLink(item, pCode, extKey);
            });

        });

        ka.needMainMenuWidth = false;

        //ka.updateModuleItemsScrollerSize();
        ka.checkMainBarWidth();


    }}).get();
};


ka.removedMainMenuItems = [];

ka.checkMainBarWidth = function () {

    var windowSize = window.getSize().x;
    if (windowSize < 500) {
        if (!ka.toSmallWindowBlocker) {
            ka.toSmallWindowBlocker = new Element('div', {
                'style': 'position: absolute; left: 0px; right: 0px; top: 0px; bottom: 0px; z-index: 600000000; background-color: white;'
            }).inject(document.body);
            var t = new Element('table', {style: 'width: 100%; height: 100%'}).inject(ka.toSmallWindowBlocker);
            var tr = new Element('tr').inject(t);
            var td = new Element('td', {
                align: 'center', valign: 'center',
                text: _('Your browser window is too small.')
            }).inject(tr);
        }
    } else if (ka.toSmallWindowBlocker) {
        ka.toSmallWindowBlocker.destroy();
        ka.toSmallWindowBlocker = null;
    }

    var iconbar = $('iconbar');
    var menubar = $('mainLinks');
    var header = $('header');

    var menubarSize = menubar.getSize();
    var iconbarSize = iconbar.getSize();
    var headerSize = header.getSize();
    //var searchBoxWidth = 263;
    var searchBoxWidth = 221;


    if (ka.additionalMainMenu) {
        searchBoxWidth += ka.additionalMainMenu.getSize().x;
    }

    var curWidth = menubarSize.x + iconbarSize.x + searchBoxWidth;

    if (!ka.needMainMenuWidth) {
        //first run, read all children widths

        if (!ka.mainMenuItems) {
            ka.mainMenuItems = menubar.getChildren('a');
        }

        ka.mainMenuItems.each(function (menuitem, index) {
            if (index == 0) return;
            menuitem.store('width', menuitem.getSize().x);
        });
    }


    //if( curWidth > headerSize.x ){

    var childrens = menubar.getChildren('a');

    var fullsize = 0;
    var addMenuWidth = 50;

    //diff is the free space we have to display menuitems
    var diff = ((menubarSize.x + iconbarSize.x + searchBoxWidth) - headerSize.x);

    //availWidth is now the availWidth we have for the menuitems
    //var availWidth = menubarSize.x - diff - addMenuWidth;
    var availWidth = window.getSize().x - (document.id('iconbar').getSize().x + 60);

    if (!ka.needMainMenuWidth) {
        ka.needMainMenuWidth = availWidth;
    }

    ka.removedMainMenuItems = [];

    ka.mainMenuItems.each(function (menuitem, index) {
        if (index == 0) return;

        var width = menuitem.retrieve('width');
        fullsize += width;

        if (fullsize < availWidth) {
            //we have place for this item
            //check if this menuitem is in the additional menu bar or in origin
            if (menuitem.retrieve('inAdditionalMenuBar') == true) {
                menuitem.inject(menubar);
                menuitem.store('inAdditionalMenuBar', false);
            }
        } else {
            //we have no place for this menuitem
            ka.removedMainMenuItems.include(menuitem);
        }
    });


    if (ka.removedMainMenuItems.length > 0) {

        if (!ka.additionalMainMenu) {
            ka.additionalMainMenu = new Element('a', {
                'class': 'ka-mainlink-additionalmenubar',
                style: 'width: 17px; cursor: default;'
            }).inject(menubar);

            new Element('img', {
                src: _path + PATH_MEDIA + '/admin/images/ka.mainmenu-additional.png',
                style: 'width: 11px; height: 12px; left: 6px; top: 8px;'
            }).inject(ka.additionalMainMenu);

            ka.additionalMainMenuContainer = new Element('div', {
                'class': 'ka-mainlink-additionalmenubar-container bar-dock-menu-style',
                style: 'display: none'
            }).inject($('border'));

            ka.makeMenu(ka.additionalMainMenu, ka.additionalMainMenuContainer, true, {y: 40, x: -1});
        }

        ka.removedMainMenuItems.each(function (menuitem) {
            menuitem.inject(ka.additionalMainMenuContainer);
            menuitem.store('inAdditionalMenuBar', true);
        });
        ka.additionalMainMenu.inject(menubar);
    } else {

        if (ka.additionalMainMenu) {
            ka.additionalMainMenu.destroy();
            ka.additionalMainMenuContainer.destroy();
            ka.additionalMainMenu = null;
        }

    }
};

ka.makeMenu = function (pToggler, pMenu, pCalPosition, pOffset) {


    pMenu.setStyle('display', 'none');

    var showMenu = function () {
        pMenu.setStyle('display', 'block');
        pMenu.store('ka.makeMenu.canHide', false);

        if (pCalPosition) {
            var pos = pToggler.getPosition($('border'));
            if (pOffset) {
                if (pOffset.x) {
                    pos.x += pOffset.x;
                }
                if (pOffset.y) {
                    pos.y += pOffset.y;
                }
            }
            pMenu.setStyles({
                'left': pos.x,
                'top': pos.y
            });
        }
    };

    var _hideMenu = function () {
        if (pMenu.retrieve('ka.makeMenu.canHide') != true) return;
        pMenu.setStyle('display', 'none');
    };

    var hideMenu = function () {
        pMenu.store('ka.makeMenu.canHide', true);
        _hideMenu.delay(250);
    };

    pToggler.addEvent('mouseover', showMenu);
    pToggler.addEvent('mouseout', hideMenu);
    pMenu.addEvent('mouseover', showMenu);
    pMenu.addEvent('mouseout', hideMenu);

    //ka.additionalMainMenu, ka.additionalMainMenuContainer, true, {y: 80});
}

ka.addAdminLink = function (pLink, pCode, pExtCode) {

    var mlink = false;

    if (!pLink.isLink) return;

    if (pCode == 'system') {

        mlink = new Element('a', {
            title: pLink.title,
            text: ' ',
            'class': 'bar-dock-logo first gradient'
        });

        new Element('img', {
            src: _path + PATH_MEDIA + '/admin/images/dock-logo-icon.png'
        }).inject(mlink);

    } else {

        mlink = new Element('a', {
            text: pLink.title
        });

        if (pLink.icon) {
            if (pLink.icon.substr(0,1) == '#'){
                mlink.addClass(pLink.icon.substr(1));
            } else {
                mlink.addClass('ka-mainmenubar-item-hasIcon');
                new Element('img', {
                    src: _path + PATH_MEDIA + pLink.icon
                }).inject(mlink);
            }
        }
    }

    var menu = new Element('div', {
        'class': 'bar-dock-menu bar-dock-menu-style',
        styles: {
            display: 'none'
        }
    });

    var hasActiveChilds = false;

    Object.each(pLink.childs, function (item, code) {

        if (!item.isLink) return;

        hasActiveChilds = true;
        var sublink = new Element('a', {
            html: item.title,
            'class': 'ka-module-items-deactivated'
        }).inject(menu);

        if (item.type) {
            sublink.removeClass('ka-module-items-deactivated');
            sublink.addEvent('click', function () {
                ka.wm.openWindow(pExtCode, pCode + '/' + code, pLink);
            })
        }

        Object.each(item.childs, function (subitem, subcode) {

            if (!subitem.isLink) return;

            var subsublink = new Element('a', {
                html: subitem.title,
                'class': 'ka-module-item-sub'
            }).inject(menu);

            if (subitem.type) {
                subsublink.addClass('ka-module-items-activated');
                subsublink.addEvent('click', function () {
                    ka.wm.openWindow(pExtCode, pCode + '/' + code + '/' + subcode, pLink);
                })
            }
        });

    });

    if (!hasActiveChilds){
        menu.destroy();
    } else {
        var childOpener;

        if (pCode != 'system') {
            mlink.addClass('ka-menu-item-hasChilds');

            childOpener = new Element('a', {
                'class': 'ka-menu-item-childopener'
            }).inject(mlink);

            new Element('img', {
                src: _path+ PATH_MEDIA + '/admin/images/ka-mainmenu-item-tree_minus.png'
            })
            .addEvent('mouseover',  function () {
                childOpener.fireEvent('mouseover');
            })
            .addEvent('mousemove',  function () {
                childOpener.fireEvent('mouseover');
            })
            .inject(childOpener);


            childOpener.addEvent('click',function (e) {
                e.stopPropagation();
            });
        } else {
            childOpener = mlink;
        }

        childOpener.addEvent('mousemove', function (e) {
            childOpener.fireEvent('mouseover');
        });

        childOpener.addEvent('mouseover', function (e) {

            if (e)
                e.stopPropagation();

            if (ka.lastVisibleDockMenu && ka.lastVisibleDockMenu != menu)
                ka.lastVisibleDockMenu.setStyle('display', 'none');

            childOpener.store('allowToDisappear', false);

            //find position
            var position = mlink.getPosition(document.id('border'));
            var size = mlink.getSize();
            menu.setStyle('left', position.x-1);
            menu.setStyle('top', 48);
            menu.removeClass('ka-menu-withRightTopBorderRadius');

            var addMenuBar = mlink.getParent('.ka-mainlink-additionalmenubar-container');

            menu.removeEvents('mouseover');
            menu.removeEvents('mouseout');

            if (addMenuBar){
                addMenuBar.removeClass('ka-mainlink-additionalmenubar-container-withoutBottomRightBorderRadius')

                menu.setStyle('left', position.x+size.x);
                menu.setStyle('top', position.y);
                menu.addClass('ka-menu-withRightTopBorderRadius');
                menu.setStyle('display', 'block');

                if (menu.getPosition().y+menu.getSize().y > addMenuBar.getPosition().y+addMenuBar.getSize().y)
                    addMenuBar.addClass('ka-mainlink-additionalmenubar-container-withoutBottomRightBorderRadius');

                menu.addEvent('mouseover', function(){
                    addMenuBar.store('ka.makeMenu.canHide', false);
                });

                menu.addEvent('mouseout', function(){
                    addMenuBar.fireEvent('mouseout');
                });

                childOpener.addEvent('mouseover', function(){
                    addMenuBar.store('ka.makeMenu.canHide', false);
                });
            }

            menu.addEvent('mouseover', function () {
                childOpener.store('allowToDisappear', false);
            }).addEvent('mouseout', function () {
                childOpener.fireEvent('mouseout');
            }).inject($('header'), 'before');

            menu.setStyle('display', 'block');
            ka.lastVisibleDockMenu = menu;

        }).addEvent('mouseout', function () {

            childOpener.store('allowToDisappear', true);
            (function () {
                if (childOpener.retrieve('allowToDisappear') == true) {
                    menu.setStyle('display', 'none');
                }
            }).delay(250);
        });
    }

    ka._links[ pExtCode + '/' + pCode ] = {
        level: 'main',
        object: mlink,
        link: pLink,
        module: pExtCode,
        code: pCode,
        path: pExtCode + '/' + pCode,
        title: pLink.title
    };

    mlink.inject($('mainLinks'));
    pLink.module = pExtCode;
    pLink.code = pCode;
    mlink.store('link', pLink);
    ka.linkClick(mlink);


}

/*
 * @deprecated
 * @param pLinks
 * @param pModule
 */
ka.addModuleLink = function (pLinks, pModule) {
    var moduleContainer = new Element('div', {
        'class': 'ka-module-container'
    }).inject(ka.moduleItems);

    $H(pLinks).each(function (pLink, code) {
        if (pLink == null) return;

        var mlink = new Element('a', {
            html: pLink.title,
            'class': 'ka-subnavi-main',
            styles: {
                'background-image': (pLink.icon) ? 'url(' + _path + PATH_MEDIA + pLink.icon + ')' : 'none;'
            }
        }).inject(moduleContainer);
        pLink.module = pModule;
        pLink.code = code;
        mlink.store('link', pLink);

        ka._links[ pModule + '/' + code ] = {
            level: 'sub',
            object: mlink,
            link: pLink,
            module: pModule,
            code: code,
            path: pModule + '/' + code,
            title: pLink.title
        };

        ka.linkClick(mlink);


        var childs = $H(pLink.childs);
        if (childs.getLength() > 0) {
            var subnavi = new Element('div', {
                'class': 'ka-subnavi-npa'
                /*'class': 'ka-subnavi',
                 styles: {
                 opacity: 0,
                 display: 'block'
                 }*/
            }).inject(ka.moduleItems);

            //ecken
            new Element('img', {
                'class': 'ka-subnavi-top-left',
                'src': _path + PATH_MEDIA + '/admin/images/ka-submenu-top-left.png'
            }).inject(subnavi);
            new Element('img', {
                'class': 'ka-subnavi-bottom-left',
                'src': _path + PATH_MEDIA + '/admin/images/ka-submenu-bottom-left.png'
            }).inject(subnavi);

            var linkCount = 0;
            childs.each(function (item, key) {

                if (item.isLink === false) return;
                linkCount++;

                var title = (!item.titleNavi ) ? item.title : item.titleNavi;


                var smlink = new Element('a', {
                    'class': 'ka-subnavi',
                    html: title
                }).addEvent('mouseover',
                    function () {
                        mlink.store('allowToDisappear', false);
                    }).addEvent('mouseout',
                    function () {
                        //pLink.store( 'allowToDisappear', false );
                        mlink.fireEvent('mouseout');
                    }).inject(subnavi);


                item.module = pModule;
                item.code = code + '/' + key;
                smlink.store('link', item);

                ka._links[ pModule + '/' + code + '/' + key ] = {
                    level: 'sub',
                    object: smlink,
                    link: item,
                    module: pModule,
                    code: code + '/' + key,
                    path: pModule + '/' + code + '/' + key,
                    title: title
                };

                ka.linkClick(smlink);
            });

            if (linkCount == 0) {
                return;
            }

            /*var pos = mlink.getPosition(ka.moduleItems);
             var size = subnavi.getSize();

             subnavi.setStyles({
             top: pos.y-5,
             right: (size.x)*-1
             });
             //subnavi.setStyle('display', 'none');

             mlink.addEvent('mouseover', function(){
             this.store( 'allowToDisappear', false );
             //subnavi.setStyle('display', 'block');
             subnavi.setStyle('opacity', 1);
             });
             mlink.addEvent('mouseout', function(){
             mlink.store( 'allowToDisappear', true );
             (function(){
             if( mlink.retrieve('allowToDisappear') ){
             //subnavi.setStyle('display', 'none');
             subnavi.setStyle('opacity', 0);
             }
             }).delay(100);
             });*/
        }
    });
}

ka.destroyLinkContext = function () {

    if (ka._lastLinkContextDiv) {
        ka._lastLinkContextDiv.destroy();
        ka._lastLinkContextDiv = null;
    }

}

ka.linkClick = function (pLink) {
    var mlink = pLink.retrieve('link');

    if (['iframe', 'list', 'combine', 'custom', 'add', 'edit'].indexOf(mlink.type) != -1) {

        var link = ka._links[mlink.module + '/' + mlink.code];

        pLink.getParent().addClass('ka-module-items-activated');

        pLink.addEvent('click', function (e) {
            ka.destroyLinkContext();

            if (e.rightClick) return;
            e.stopPropagation();
            e.stop();

            var windows = [];
            Object.each(ka.wm.windows, function (pwindow) {
                if (!pwindow) return;
                if (pwindow.code == mlink.code && pwindow.module == mlink.module) {
                    windows.include(pwindow);
                }
            }.bind(this));


            if (windows.length == 0) {
                //none exists, just open
                ka.wm.open(mlink.module+'/'+mlink.code);
            } else if (windows.length == 1) {
                //only one is open, bring it to front
                windows[0].toFront();
            } else if (windows.length > 1) {
                //open contextmenu
                e.stopPropagation();
                e.stop();
                ka._openLinkContext(link);
            }

            delete windows;
        });

        pLink.addEvent('mouseup', function (e) {

            if (e.rightClick) {
                e.stopPropagation();
                ka._openLinkContext(link);
            }
        });
    }
}

ka._openLinkContext = function (pLink) {

    if (ka._lastLinkContextDiv) {
        ka._lastLinkContextDiv.destroy();
        ka._lastLinkContextDiv = null;
    }

    var pos = {x: 0, y: 0};
    var corner = false;

    /*if( pLink.level == 'main' ){
     var div = new Element('div', {
     'class': 'ka-linkcontext-main'
     }).inject( document.body );

     pos = pLink.object.getPosition();
     var size = pLink.object.getSize();

     div.setStyle('left', pos.x);
     //div.setStyle('width', size.x);
     }
     if( pLink.level == 'sub' ){
     */

    var parent = pLink.object.getParent('.ka-module-menu');
    if (!parent) {
        parent = document.body;
    }
    var div = new Element('div', {
        'class': 'ka-linkcontext-main ka-linkcontext-sub'
    }).inject(parent);

    corner = new Element('div', {
        'class': 'ka-tooltip-corner-top',
        style: 'height: 15px; width: 30px;'
    }).inject(div);

    pos = pLink.object.getPosition(pLink.object.getParent('.ka-module-menu'));
    var size = pLink.object.getSize();

    div.setStyle('left', pos.x);
    div.setStyle('top', pos.y + size.y);
    if (pLink.level == 'main') {

        corner.setStyle('bottom', 'auto');
        corner.setStyle('top', -8);
    }

    ka._lastLinkContextDiv = div;

    var windows = [];
    Object.each(ka.wm.windows, function (pwindow) {
        if (!pwindow) return;
        if (pwindow.code == pLink.code && pwindow.module == pLink.module) {
            windows.include(pwindow);
        }
    }.bind(this));

    var opener = new Element('a', {
        text: _('Open new %s').replace('%s', "'" + pLink.title + "'"),
        'class': 'ka-linkcontext-opener'
    }).addEvent('click',
        function () {
            ka.wm.openWindow(pLink.module, pLink.code);
            ka._lastLinkContextDiv.destroy();
        }).inject(div);

    if (windows.length == 0) {
        opener.addClass('ka-linkcontext-last');
    }

    var lastItem = false;
    windows.each(function (window) {
        lastItem = new Element('a', {
            text: '#' + window.id + ' ' + window.getTitle()
        }).addEvent('click',
            function () {
                window.toFront();
                ka._lastLinkContextDiv.destroy();
            }).inject(div);
    });

    if (pLink.level == 'sub') {
        var bsize = div.getSize();
        var wsize = window.getSize();
        var mtop = div.getPosition(document.body).y;

        if (mtop + bsize.y > wsize.y) {
            mtop = pos.y - bsize.y;
            div.setStyle('top', mtop);
            corner.set('class', 'ka-tooltip-corner');
            corner.setStyle('bottom', '-15px');
        } else {
            corner.setStyle('top', '-7px');
        }
        if (lastItem) {
            lastItem.addClass('ka-linkcontext-last');
        }
    }

    delete windows;

}

ka.autoPositionLastOverlay = false;
ka.autoPositionLastItem = false;

ka.closeDialog = function () {
    if (ka.autoPositionLastOverlay) {
        ka.autoPositionLastOverlay.destroy();
    }
    delete ka.autoPositionLastOverlay;

    if (ka.autoPositionLastItem) {
        ka.autoPositionLastItem.addEvent('close');
        ka.autoPositionLastItem.dispose();
    }

    delete ka.autoPositionLastItem;
}

ka.openDialog = function (item) {
    if (!item.element || !item.element.getParent) {
        return;
    }
    if (ka.autoPositionLastItem == item.element) {
        return;
    }

    ka.closeDialog();

    var target = document.body;
    if (item.target && item.target.getWindow()) {
        target = item.target.getWindow().document.body;
    }

    ka.autoPositionLastOverlay = new Element('div', {
        style: 'position: absolute; left:0px; top: 0px; right:0px; bottom:0px;background-color: white; z-index: 201000;',
        styles: {
            opacity: 0.001
        }
    }).addEvent('click', function (e) {
        ka.closeDialog();
        e.stop();
        if (item.onClose)
            item.onClose();
    }).inject(target);
    item.element.setStyle('z-index', 201001);

    var size = item.target.getWindow().getScrollSize();

    ka.autoPositionLastOverlay.setStyles({
        width: size.x,
        height: size.y
    });

    ka.autoPositionLastItem = item.element;

    item.element.inject(target);
    item.element.removeEvent('click', ka.closeDialog);
    item.element.addEvent('click', ka.closeDialog);

    if (!item.offset) item.offset = {};

    if (!item.primary) {
        item.primary = {
            'position': 'bottomRight',
            'edge': 'upperRight',
            offset: item.offset
        }
    }
    if (!item.secondary) {
        item.secondary = {
            'position': 'upperRight',
            'edge': 'bottomRight',
            offset: item.offset
        }
    }

    item.primary.relativeTo = item.target;
    item.secondary.relativeTo = item.target;

    item.element.position(item.primary);

    var pos = item.element.getPosition();
    var size = item.element.getSize();

    var bsize = item.element.getParent().getSize();
    var bscroll = item.element.getParent().getScroll();
    var height;

    item.element.setStyle('height', '');

    item.minHeight = item.element.getSize().y;

    if (size.y + pos.y > bsize.y + bscroll.y) {
        height = bsize.y - pos.y - 10;
    }

    if (height) {
        if (item.minHeight && height < item.minHeight) {
            item.element.position(item.secondary);
        } else {
            item.element.setStyle('height', height);
        }
    }
}

ka.getPrimariesForObject = function(pObjectKey){

    var definition = ka.getObjectDefinition(pObjectKey);

    var result = {};

    if (!definition) {
        logger('Can not found object definition for object "'+pObjectKey+'"');
        return;
    }

    Object.each(definition.fields, function(field, fieldKey){

        if (field.primaryKey){
            result[fieldKey] = Object.clone(field);
        }

    });

    return result;
}

ka.getPrimaryListForObject = function(pObjectKey){

    var definition = ka.getObjectDefinition(pObjectKey);

    var result = [];

    if (!definition) {
        logger('Can not found object definition for object "'+pObjectKey+'"');
        return;
    }

    Object.each(definition.fields, function(field, fieldKey){

        if (field.primaryKey){
            result.push(fieldKey);
        }

    });

    return result;
}

ka.getObjectDefinition = function(pObjectKey){

    var definition;

    Object.each(ka.settings.configs, function(config,extensionKey){
        if (config.objects && config.objects[pObjectKey])
            definition = config.objects[pObjectKey];
    });

    return definition;
}


ka.getFieldCaching = function () {

    return {
        'cache_type': {
            label: _('Cache storage'),
            type: 'select',
            items: {
                'memcached': _('Memcached'),
                'redis': _('Redis'),
                'apc': _('APC'),
                'files': _('Files')
            },
            'depends': {
                'cache_params[servers]': {
                    needValue: ['memcached', 'redis'],
                    'label': 'Servers',
                    'type': 'array',
                    startWith: 1,
                    'width': 310,
                    'columns': [
                        {'label': _('IP')},
                        {'label': _('Port'), width: 50}
                    ],
                    'fields': {
                        ip: {
                            type: 'text',
                            width: '95%',
                            empty: false
                        },
                        port: {
                            type: 'number',
                            width: 50,
                            empty: false
                        }
                    }
                },
                'cache_params[files_path]': {
                    needValue: 'files',
                    type: 'text',
                    label: 'Caching directory',
                    'default': 'cache/object/'
                }
            }
        }
    }
}


ka.renderLayoutElements = function (pDom, pClassObj) {

    var layoutBoxes = {};

    pDom.getWindow().$$('.kryn_layout_content, .kryn_layout_slot').each(function (item) {

        var options = {};
        if (item.get('params')) {
            var options = JSON.decode(item.get('params'));
        }

        if (item.hasClass('kryn_layout_slot')) {
            layoutBoxes[ options.id ] = new ka.LayoutBox(item, options, pClassObj);
        } //options.name, this.win, options.css, options['default'], this, options );
        else {
            layoutBoxes[ options.id ] = new ka.ContentBox(item, options, pClassObj);
        }

    });

    return layoutBoxes;
}


initWysiwyg = function (pElement, pOptions) {

    var options = {
        extraClass: 'SilkTheme',
        //flyingToolbar: true,
        dimensions: {
            x: '100%'
        },
        actions: 'bold italic underline strikethrough | formatBlock justifyleft justifycenter justifyright justifyfull | insertunorderedlist insertorderedlist indent outdent | undo redo | tableadd | createlink unlink | image | toggleview'
    };

    if (pOptions) {
        options = Object.append(options, pOptions);
    }

    return new MooEditable(document.id(pElement), options);
}
/*
 initSmallTiny
 initTiny
 initResizeTiny
 initTinyWithoutResize*/
