/* ka window.manager */
ka.wm = {

    windows: {},

    /* depend: [was => mitWem] */
    depend: {},
    lastWindow: 0,
    events: {},
    zIndex: 1000,

    activeWindowInformation: [],
    tempItems: {},

    openWindow: function (pEntryPoint, pLink, pParentWindowId, pParams, pInline) {
        var win;
        if (win = this.checkOpen(pEntryPoint) && !pInline) {
            return win.toFront();
        }

        return ka.wm.loadWindow(pEntryPoint, pLink, pParentWindowId, pParams, pInline);
    },

    addEvent: function (pEv, pFunc) {
        if (!ka.wm.events[pEv]) {
            ka.wm.events[pEv] = [];
        }

        ka.wm.events[pEv].include(pFunc);
    },

    fireEvent: function (pEv) {
        if (ka.wm.events[pEv]) {
            Object.each(ka.wm.events[pEv], function (func) {
                $try(func);
            });
        }
    },

    /**
     * Open a new entry point. (e.g. Window)
     *
     * @param {String}  pEntryPoint
     * @param {Object}  pParams
     * @param {Integer} pParentWindowId
     * @param {Boolean} pInline True to open the target in a dialog container (ka.Dialog).
     * @returns {*}
     */
    open: function (pEntryPoint, pParams, pParentWindowId, pInline) {
        return ka.wm.openWindow(pEntryPoint, null, pParentWindowId, pParams, pInline);
    },

    getWindow: function (pId) {
        if (pId == -1){
            pId == ka.wm.lastWindow;
        }
        return ka.wm.windows[ pId ];
    },

    sendSoftReload: function (pEntryPoint) {
        ka.wm.softReloadWindows(pEntryPoint);
    },

    softReloadWindows: function (pEntryPoint) {
        Object.each(ka.wm.windows, function (win) {
            if (win && win.getEntryPoint() == pEntryPoint) {
                win.softReload();
            }
        });
    },

    resizeAll: function () {
        ka.settings['user']['windows'] = {};
        Object.each(ka.wm.windows, function (win) {
            win.loadDimensions();
        });
    },

    setFrontWindow: function (pWindow) {
        Object.each(ka.wm.windows, function (win, winId) {
            if (win && pWindow.id != winId){
                win.toBack();
            }
        });
        ka.wm.lastWindow = pWindow;
    },

    loadWindow: function (pEntryPoint, pLink, pParentWindowId, pParams, pInline) {
        var instance = Object.getLength(ka.wm.windows) + 1;

        if (pParentWindowId == -1){
            pParentWindowId = ka.wm.lastWindow ? ka.wm.lastWindow.id : false;
        }

        if (pParentWindowId && !ka.wm.getWindow(pParentWindowId)) throw 'Parent window not found.';

        ka.wm.windows[instance] = new ka.Window(pEntryPoint, pLink, instance, pParams, pInline, pParentWindowId);
        ka.wm.windows[instance].toFront();
        ka.wm.updateWindowBar();
        ka.wm.reloadHashtag();
    },

    close: function (pWindow) {
        var parent = pWindow.getParentId();
        if (parent){
            parent = ka.wm.getWindow(parent);
            parent.removeChildren();
        }

        if (ka.wm.tempItems[pWindow.getId()]) {
            ka.wm.tempItems[pWindow.getId()].destroy();
            delete ka.wm.tempItems[pWindow.getId()];
        }

        delete ka.wm.windows[pWindow.id];

        if (parent){
            parent.toFront();
        } else {
            ka.wm.bringLastWindow2Front();
        }

        ka.wm.updateWindowBar();
        ka.wm.reloadHashtag();
    },

    bringLastWindow2Front: function(){

        var lastWindow;

        Object.each(ka.wm.windows, function (win) {
            if (!win) return;
            if (!lastWindow || win.border.getStyle('z-index') > lastWindow.border.getStyle('z-index')){
                lastWindow = win;
            }
        });

        if (lastWindow){
            lastWindow.toFront();
        }
    },

    getWindowsCount: function () {
        var count = 0;
        Object.each(ka.wm.windows, function (win, winId) {
            if (!win) return;
            if (win.inline) return;
            count++;
        });
        return count;
    },

    updateWindowBar: function () {

        ka.wm.removeActiveWindowInformation();

        var atLeastOneActive = false;

        if (ka.adminInterface.frontendLink) {
            ka.adminInterface.frontendLink.removeClass('ka-main-menu-item-active');
        }
        document.body.removeClass('hide-scrollbar');

        Object.each(ka.wm.windows, function (win) {

            if (win.getParentId()) return;
            var menuItem = ka.adminInterface.getMenuItem(win.getEntryPoint());

            if (menuItem) {
                menuItem.object.addClass('ka-main-menu-item-open');
            }

            if (win.isInFront()){

                if (win.getEntryPoint() == 'admin/nodes/frontend')
                    return ka.adminInterface.frontendLink.addClass('ka-main-menu-item-active');

                atLeastOneActive = true;
                if (menuItem) {
                    menuItem.object.addClass('ka-main-menu-item-active');
                } else if (!ka.wm.tempItems[win.getId()]){
                    var item = ka.adminInterface.addTempLink(win);
                    item.addClass('ka-main-menu-item-active');
                    ka.wm.tempItems[win.getId()] = item;
                } if (menuItem = ka.wm.tempItems[win.getId()]){
                    menuItem.set('text', win.getEntryPointDefinition().title+' » '+win.getTitle());
                    menuItem.addClass('ka-main-menu-item-active');
                }
            }

        });

        if (atLeastOneActive && ka.adminInterface.options.frontPage){
            document.body.addClass('hide-scrollbar');
        }

        if (!atLeastOneActive && ka.adminInterface.options.frontPage) {
            ka.adminInterface.frontendLink.addClass('ka-main-menu-item-active');
        }

    },

    reloadHashtag: function(pForce){
        var hash = '';

        Object.each(ka.wm.windows, function (win) {
            if (win.isInFront() && !win.isInline()){
                hash = win.getEntryPoint()+( win.getParameter() ? '!'+JSON.encode(win.getParameter()) : '' );
            }
        });

        if (hash != window.location.hash){
            window.location.hash = hash;
        }
    },

    handleHashtag: function(pForce){

        if (ka.wm.hashHandled && !pForce) return;

        ka.wm.hashHandled = true;

        if (window.location.hash){

            var first = window.location.hash.indexOf('!');
            var entryPoint = window.location.hash.substr(1);
            var parameters = null;
            if (first !== -1){
                entryPoint = entryPoint.substr(0, first-1);

                parameters = window.location.hash.substr(first+1);
                if (parameters){
                    parameters = JSON.decode(parameters);
                }
            }

            ka.wm.open(entryPoint, parameters);
        }
    },

    removeActiveWindowInformation: function(){
        ka.adminInterface.mainLinks.getElements('a').removeClass('ka-main-menu-item-active');
        ka.adminInterface.mainLinks.getElements('a').removeClass('ka-main-menu-item-open');
    },

    checkOpen: function (pEntryPoint, pInstanceId, pParams) {
        opened = false;
        Object.each(ka.wm.windows, function (win) {
            if (win && win.getEntryPoint() == pEntryPoint) {
                if (pInstanceId > 0 && pInstanceId == win.id) {
                    return;
                }
                opened = win;
            }
        });
        return opened;
    },

    closeAll: function () {
        Object.each(ka.wm.windows, function (win) {
            win.close();
        });
    },

    hideContents: function () {
        Object.each(ka.wm.windows, function (win, winId) {
            win.content.setStyle('display', 'none');
        });
    },

    showContents: function () {
        Object.each(ka.wm.windows, function (win, winId) {
            win.content.setStyle('display', 'block');
        });
    }

};
