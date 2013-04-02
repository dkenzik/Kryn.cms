ka.Window = new Class({
    Binds: ['close'],
    Implements: Events,

    id: 0,

    entryPoint: '',
    module: '',

    inline : false,
    link   : {},
    params : {},

    children: null,

    initialize: function (pEntryPoint, pLink, pInstanceId, pParameter, pInline, pParentId) {
        this.params = pParameter;
        this.id = pInstanceId;

        this.entryPoint = pEntryPoint;

        this.inline = pInline;
        this.link = pLink;
        this.parentId = pParentId;
        if (this.parentId){
            ka.wm.getWindow(this.parentId).setChildren(this);
        }

        this.link = pLink || {};

        this.active = true;
        this.isOpen = true;

        this.createWin();

        if (pEntryPoint) {
            this.loadContent();

            this.addHotkey('esc', false, false, function(e){
                (function(){
                    this.close(true);
                }).delay(50, this);
            }.bind(this));
        }
    },

    isInline: function(){
        return this.inline;
    },

    /**
     *
     * @return {*}
     */
    getParameter: function(){
        return this.params;
    },

    /**
     *
     * @param {*} pParameter
     */
    setParameter: function(pParameter){
        this.params = pParameter;
        ka.wm.reloadHashtag();
    },

    getParentId: function(){
        return this.parentId;
    },

    getParent: function(){
        return ka.wm.getWindow(this.parentId);
    },

    isInFront: function(){
        if (!this.children)
            return this.inFront;

        return this.children.isInFront();
    },

    setChildren: function(pWindow){
        this.children = pWindow;
    },

    removeChildren: function(){
        delete this.children;
    },

    getChildren: function(){
        return this.children;
    },

    onResizeComplete: function () {
    },

    softReload: function () {
    },

    iframeOnLoad: function () {

        if (this.inline) {
            var opener = ka.wm.getOpener(this.id);
            //opener.inlineContainer.empty();
            //this.content.inject( opener.inlineContainer );

            this.getContentContainer().setStyles({'top': 5, 'bottom': 5, left: 5, right: 5});
            var borderSize = opener.border.getSize();

            opener.inlineContainer.setStyle('width', 530);

            //            this.iframe.contentWindow.document.body.style.height = '1px';
            //            this.iframe.contentWindow.document.body.style.width = '1px';

            var inlineSize = {x: this.iframe.contentWindow.document.html.scrollWidth + 50,
                y: this.iframe.contentWindow.document.html.scrollHeight + 50};

            //            this.iframe.contentWindow.document.body.style.height = inlineSize.y+'px';
            //            this.iframe.contentWindow.document.body.style.width = inlineSize.x+'px';

            if (inlineSize.x > borderSize.x) {
                opener.border.setStyle('width', inlineSize.x);
            }

            if (inlineSize.y + 35 > borderSize.y) {
                opener.border.setStyle('height', inlineSize.y + 35);
            }

            if (inlineSize.y < 450) {
                inlineSize.y = 450;
            }


            opener.inlineContainer.setStyles({
                height: inlineSize.y - 25,
                width: inlineSize.x
            });

        }
    },

    setLoading: function(pState, pText, pOffset){
        if (pState == true){

            if (!pText){
                pText = t('Loading ...');
            }

            if (this.loadingObj){
                this.loadingObj.destroy();
                delete this.loadingObj;
            }

            if (this.loadingFx)
                delete this.loadingFx;

            this.loadingObj = new ka.Loader(this.border, {
                overlay: true,
                absolute: true
            });

            var div = new Element('div', {
                'class': 'ka-kwindow-loader-content gradient',
                html: "<br/>"+pText
            }).inject(this.loadingObj.td);


            document.id(this.loadingObj).setStyles({'top': 25});
            this.loadingObj.transBg.setStyles({'top': 25});

            document.id(this.loadingObj).setStyles(pOffset);
            this.loadingObj.transBg.setStyles(pOffset);

            this.loadingObj.getLoader().inject(div, 'top');
            this.loadingObj.getLoader().setStyle('line-height', 25);
            
            this.loadingObj.transBg.setStyle('opacity', 0.05);
            div.setStyles({
                'opacity': 0,
                'top': 30
            });

            this.loadingFx = new Fx.Morph(div, {
                duration: 500, transition: Fx.Transitions.Quint.easeOut
            });

            this.loadingObj.show();

            this.loadingFx.start({
                'top': 0,
                opacity: 1
            });

        } else {
            if (this.loadingObj){

                this.loadingFx.cancel();

                this.loadingFx.addEvent('complete', function(){

                    this.loadingObj.destroy();
                    delete this.loadingObj;
                    delete this.loadingFx;

                }.bind(this));

                this.loadingFx.start({
                    'top': -30,
                    opacity: 0
                });

            }
        }


    },

    getOpener: function () {
        return ka.wm.getOpener(this.id);
    },

    toBlockMode: function (pOpts, pCallback) {
        if (!pOpts.id > 0) return;

        this.blockModeOverlay = new Element('div', {
            style: ''
        }).inject(this.blockModeContainer);
    },

    alert: function(pText, pCallback){
        return this._alert(pText, pCallback);
    },

    _alert: function (pText, pCallback) {
        return this._prompt(pText, null, pCallback, {
            'alert': 1
        });
    },

    _confirm: function (pText, pCallback) {
        return this.confirm(pText, pCallback);
    },

    confirm: function (pText, pCallback) {
        return this._prompt(pText, null, pCallback, {
            'confirm': 1
        });
    },

    _passwordPrompt: function (pDesc, pDefaultValue, pCallback, pOpts) {
        if (!pOpts) pOpts = {};
        pOpts.pw = 1;
        return this._prompt(pDesc, pDefaultValue, pCallback, pOpts);
    },

    _prompt: function (pDesc, pDefaultValue, pCallback, pOpts) {

        var res = false;
        if (!pOpts) pOpts = {};
        if (pOpts['confirm'] == 1) {
            res = true;
        }

        var main = this.newDialog(pDesc);

        if (pOpts['alert'] != 1 && pOpts['confirm'] != 1) {
            var input = new Element('input', {
                'class': 'text',
                'type': (pOpts.pw == 1) ? 'password' : 'text',
                value: pDefaultValue
            }).inject(main.content);

            input.focus();
        }

        var ok = false;

        if (pOpts['alert'] != 1) {

            if (pCallback){
                var closeEvent = function(){
                    pCallback(false);
                }
                main.addEvent('close', closeEvent);
            }

            new ka.Button(t('Cancel')).addEvent('click', function(){
                main.close();
            }.bind(this)).inject(main.bottom);

            ok = new ka.Button(t('OK')).addEvent('keyup', function(e){
                e.stopPropagation();
                e.stop();
            }).addEvent('click', function(e){
                if (e) {
                    e.stop();
                }
                if (input && input.value != '') {
                    res = input.value;
                }
                if (pCallback) main.removeEvent('close', closeEvent);
                main.close();
                if (pCallback) pCallback(res);
            }.bind(this)).inject(main.bottom);
        }

        if (pOpts && pOpts['alert'] == 1) {

            if (pCallback)
                main.addEvent('close', pCallback);

            ok = new ka.Button('OK')
            .addEvent('click', function(e){
                if (e) e.stop();
                main.close(true);
            }.bind(this))
            .inject(main.bottom);
        }

        if (pOpts['alert'] != 1 && pOpts['confirm'] != 1) {
            input.addEvent('keyup', function (e) {
                if (e.key == 'enter') {
                    e.stopPropagation();
                    e.stop();
                    if (ok) {
                        ok.fireEvent('click');
                    }
                }
            });
        }

        if (ok && !input) {
            ok.focus();
        }

        main.center();

        return main;
    },


    /**
     * Creates a new dialog over the current window.
     *
     * @param  {mixed} pText A string (non html) or an element, that will be injected in the content area.
     *
     * @param  {Boolean} pAbsoluteContent If we position the content absolute or inline.
     * @return {ka.Dialog}
     */
    newDialog: function (pText, pAbsoluteContent) {
        return new ka.Dialog(this, {
            content: pText,
            absolute: pAbsoluteContent
        });
    },

    parseTitle: function (pHtml) {
        pHtml = pHtml.replace('<img', ' » <img');
        pHtml = pHtml.stripTags();
        if (pHtml.indexOf('»') !== false) {
            pHtml = pHtml.substr(3);
        }
        return pHtml;
    },

    getTitle: function () {
        if (this.titleAdditional) {
            return this.parseTitle(this.titleAdditional.get('html'));
        }
        return '';
    },

    getFullTitle: function () {
        if (this.titlePath) {
            return this.parseTitle(this.titlePath.get('html'));
        }
        return '';
    },

    setTitle: function (pTitle) {
        this.clearTitle();
        this.addTitle(pTitle);
    },

    toBack: function () {
        this.title.removeClass('ka-kwindow-inFront');
        if (!this.isInline() && (!this.children || !this.children.isInline()))
            this.border.setStyle('display', 'none');
        this.inFront = false;
    },

    clearTitle: function () {
        this.titleAdditional.empty();
        ka.wm.updateWindowBar();
    },

    addTitle: function (pText) {
        new Element('img', {
            src: _path + 'admin/images/ka-kwindow-title-path.png'
        }).inject(this.titleAdditional);

        new Element('span', {
            text: pText
        }).inject(this.titleAdditional);

        ka.wm.updateWindowBar();
    },

    toFront: function(pOnlyZIndex) {

        if (this.active) {
            this.title.addClass('ka-kwindow-inFront');
            if (this.border.getStyle('display') != 'block') {
                this.border.setStyles({
                    'display': 'block'
                });
            }

            if (this.getParent()){
                this.getParent().toFront(true);
            }


            if (!this.isInline()){
                ka.wm.zIndex++;
                this.border.setStyle('z-index', ka.wm.zIndex);
            }
            if (pOnlyZIndex) return true;

            if (this.getChildren()){
                this.getChildren().toFront();
                this.getChildren().highlight();
                return false;
            }

            ka.wm.setFrontWindow(this);
            this.isOpen = true;
            this.inFront = true;

            this.border.setStyle('display', 'block');

            this.deleteOverlay();
            ka.wm.updateWindowBar();

            this.fireEvent('toFront');

            return true;
        }
    },

    addHotkey: function (pKey, pControlOrMeta, pAlt, pCallback) {

        if (!this.hotkeyBinds) this.hotkeyBinds = [];

        var bind = function(e){
            if (document.activeElement){
                if (document.activeElement.get('tag') != 'body' && !document.activeElement.hasClass('ka-Button')){
                    return;
                }
            }
            if (this.isInFront() && (!this.inOverlayMode)) {

                if (pControlOrMeta && (!e.control && !e.meta)) return;
                if (pAlt && !e.alt) return;
                if (e.key == pKey) {
                    pCallback(e);
                }
            }
        }.bind(this);

        this.hotkeyBinds.push(bind);

        document.body.addEvent('keydown', bind);

    },

    removeHotkeys: function(){

        Array.each(this.hotkeyBinds, function(bind){
            document.removeEvent('keydown', bind);
        })

    },

    _highlight: function () {
        [this.title, this.bottom].each(function (item) {
            item.set('tween', {duration: 50, onComplete: function () {
                item.tween('opacity', 1);
            }});
            item.tween('opacity', 0.3);
        });
    },

    highlight: function () {

        (function () {
            this._highlight();
        }.bind(this)).delay(1);
        (function () {
            this._highlight()
        }.bind(this)).delay(150);
        (function () {
            this._highlight()
        }.bind(this)).delay(300);
    },

    setBarButton: function (pButton) {
        this.barButton = pButton;
    },

    minimize: function () {

        this.isOpen = false;

        ka.wm.updateWindowBar();

        var cor = this.border.getCoordinates();
        var quad = new Element('div', {
            styles: {
                position: 'absolute',
                left: cor.left,
                top: cor.top,
                width: cor.width,
                height: cor.height,
                border: '3px solid gray'
            }
        }).inject(this.border.getParent());

        quad.set('morph', {duration: 300, transition: Fx.Transitions.Quart.easeOut, onComplete: function () {
            quad.destroy();
        }});

        var cor2 = this.barButton.getCoordinates(this.border.getParent());
        quad.morph({
            width: cor2.width,
            top: cor2.top,
            left: cor2.left,
            height: cor2.height
        });
        this.border.setStyle('display', 'none');
        this.onResizeComplete();
    },

    maximize: function (pDontRenew) {
        if (this.inline || this.isPopup()) return;

        if (this.maximized) {
            //this.borderDragger.attach();

            this.border.setStyles(this.oldDimension);
            this.maximizer.removeClass('icon-shrink-3');
            this.maximizer.addClass('icon-expand-4');
            this.maximized = false;
            this.border.removeClass('kwindow-border-maximized');

            Object.each(this.sizer, function(sizer){
                sizer.setStyle('display', 'block');
            });

            this.bottom.set('class', 'kwindow-win-bottom');
        } else {
            //this.borderDragger.detach();
            this.border.addClass('kwindow-border-maximized');

            this.oldDimension = this.border.getCoordinates(this.border.getParent());
            this.border.setStyles({
                width: '100%',
                height: '100%',
                left: 0,
                top: 0
            });
            this.maximizer.removeClass('icon-expand-4');
            this.maximizer.addClass('icon-shrink-3');
            this.maximized = true;

            Object.each(this.sizer, function(sizer){
                sizer.setStyle('display', 'none');
            });

            this.bottom.set('class', 'kwindow-win-bottom-maximized');
        }

        this.onResizeComplete();
        this.fireEvent('resize');
    },

    close: function (pInternal) {

        //search for dialogs
        if (this.border){
            var dialogs = this.border.getChildren('.ka-kwindow-prompt');
            if (!dialogs || !dialogs.length){
                dialogs = this.border.getChildren('.ka-dialog-overlay');
            }
            if (dialogs.length > 0){

                var lastDialog = dialogs[dialogs.length-1];
                if (lastDialog.kaDialog) lastDialog = lastDialog.kaDialog;

                if (lastDialog.canClosed === false) return;
                lastDialog.closeAnimated(true);

                delete lastDialog;
                return false;
            }
        }

        //search for children windows
        if (this.getChildren()){
            this.getChildren().highlight();
            return false;
        }

        if (pInternal) {
            this.interruptClose = false;
            this.fireEvent('close');
            if (this.interruptClose == true) return;
        }

        if (this.onClose) {
            this.onClose();
        }

        if (this.border) {
            if (this.getEntryPoint() == 'users/users/edit/') {
                ka.loadSettings();
            }
            this.border.getElements('a.kwindow-win-buttonWrapper').each(function (button) {
                if (button.toolTip && button.toolTip.main) {
                    button.toolTip.main.destroy();
                }
            });
        }
        this.inFront = false;

        if (this.dialogContainer){
            this.addEvent('postClose', function(){
                this.destroy();
            }.bind(this));
            this.dialogContainer.closeAnimated();
        } else {
            this.destroy();
        }
        ka.wm.close(this);
    },

    destroy: function(){
        this.removeHotkeys();

        if (window['contentCantLoaded_' + this.customId])
            delete window['contentCantLoaded_' + this.customId];

        if (window['contentLoaded_' + this.customId])
            delete window['contentLoaded_' + this.customId];

        if (this.custom)
            delete this.custom;

        if (this.title){
            this.title.destroy();
            delete this.title;
        }

        if (this.customCssAsset){
            this.customCssAsset.destroy();
            delete this.customCssAsset;
        }

        if (this.customJsAsset){
            this.customJsAsset.destroy();
            delete this.customJsAsset;
        }

        if (this.customJsClassAsset){
            this.customJsClassAsset.destroy();
            delete this.customJsClassAsset;
        }

        this.border.destroy();
    },

    getEntryPoint: function(){
        return this.entryPoint;
    },

    getId: function(){
        return this.id;
    },

    getEntryPointDefinition: function(){
        return this.entryPointDefinition;
    },

    getModule: function(){
        if (!this.module){
            if (this.getEntryPoint().indexOf('/') > 0){
                this.module = this.getEntryPoint().substr(0, this.getEntryPoint().indexOf('/'));
            } else {
                this.module = this.getEntryPoint();
            }
        }
        return this.module;
    },

    isPopup: function(){
        return this.isPopup;
    },

    loadContent: function () {
        if (this.getContentContainer())
            this.getContentContainer().empty();

        this.entryPointDefinition = ka.entrypoint.get(this.getEntryPoint());

        if (!this.entryPointDefinition){
            this.win.alert(tf('Entry point `%s` not found.', this.getEntryPoint()));
            return;
        }

        if (this.entryPointDefinition.multi === false || this.entryPointDefinition.multi === 0) {
            var win = ka.wm.checkOpen(this.getEntryPoint(), this.id);
            if (win) {
                this.close(true);
                if (win.softOpen) win.softOpen(this.params);
                win.toFront();
                return;
            }
        }

        var title = ka.settings.configs[ this.getModule() ]['title'];

        if (title != 'Kryn.cms') {
            new Element('span', {
                text: title
            }).inject(this.titleText, 'before');

            new Element('img', {
                src: _path + 'admin/images/ka-kwindow-title-path.png'
            }).inject(this.titleText, 'before');
        }

        var path = Array.clone(this.entryPointDefinition._path);
        path.pop();
        Array.each(path, function (label) {

            new Element('span', {
                text: t(label)
            }).inject(this.titleText, 'before');

            new Element('img', {
                src: _path + 'admin/images/ka-kwindow-title-path.png'
            }).inject(this.titleText, 'before');


        }.bind(this));

        if (!this.inline && !this.isPopup()) {
            this.createResizer();
        }

        this.titleText.set('text', t(this.entryPointDefinition.title));

        this.content.empty();
        new Element('div', {
            style: 'text-align: center; padding: 15px; color: gray',
            text: t('Loading content ...')
        }).inject(this.content);

        if (this.entryPointDefinition.type == 'iframe') {
            this.content.empty();
            this.iframe = new IFrame('iframe_kwindow_' + this.id, {
                'class': 'kwindow-iframe',
                frameborder: 0
            }).addEvent('load', function () {
                this.iframe.contentWindow.win = this;
                this.iframe.contentWindow.ka = ka;
                this.iframe.contentWindow.wm = ka.wm;
                this.iframe.contentWindow.fireEvent('kload');
            }.bind(this)).inject(this.content);
            this.iframe.set('src', _path + this.entryPointDefinition.src);
        } else if (this.entryPointDefinition.type == 'custom') {
            this.renderCustom();
        } else if (this.entryPointDefinition.type == 'combine') {
            this.renderCombine();
        } else if (this.entryPointDefinition.type == 'list') {
            this.renderList();
        } else if (this.entryPointDefinition.type == 'add') {
            this.renderAdd();
        } else if (this.entryPointDefinition.type == 'edit') {
            this.renderEdit();
        }

        if (this.entryPointDefinition.type != 'combine') {
            if (this.dialogContainer)
                this.dialogContainer.center(true);
        }

        ka.wm.updateWindowBar();

        if (this.entryPointDefinition.noMaximize === true) {
            this.maximizer.destroy();
        }

        if (this.entryPointDefinition.print === true) {
            this.printer = new Element('img', {
                'class': 'kwindow-win-printer',
                src: _path + 'admin/images/icons/printer.png'
            }).inject(this.border);
            this.printer.addEvent('click', this.print.bind(this));
        }

    },

    print: function () {
        var size = this.border.getSize();
        var popup = window.open('', '', 'width=' + size.x + ',height=' + size.y + ',menubar=yes,resizeable=yes,status=yes,toolbar=yes');
        var clone = this.content.clone();
        popup.document.open();
        popup.document.write('<head><title>Drucken</title></head><body></body>');
        clone.inject(popup.document.body);
        popup.document.close();

        Array.each(document.styleSheets, function (s, index) {
            var w = new Element('link', {
                rel: 'stylesheet',
                type: 'text/css',
                href: s.href,
                media: 'screen'
            }).inject(popup.document.body);
        });
        popup.print();
    },

    renderEdit: function () {
        this.edit = new ka.WindowEdit(this);
    },

    renderAdd: function () {
        this.add = new ka.WindowAdd(this);
    },

    renderCombine: function () {
        this.combine = new ka.WindowCombine(this);
    },

    renderList: function () {
        this.list = new ka.WindowList(this, null, this.content);
    },

    renderCustom: function () {
        var id = 'text';

        var code = this.getEntryPoint().substr(this.getModule().length+1);

        var javascript = code.replace(/\//g, '_');

        var noCache = (new Date()).getTime();

        if (this.getModule() == 'admin') {
            this.customCssAsset = new Asset.css(_path + 'admin/css/' + javascript + '.css?noCache=' + noCache);
        } else {
            this.customJsAsset = new Asset.css(_path + this.getModule() + '/admin/css/' + javascript + '.css?noCache=' + noCache);
        }

        this.customId = parseInt(Math.random() * 100) + parseInt(Math.random() * 100);

        window['contentCantLoaded_' + this.customId] = function (pFile) {
            this.content.empty();
            this._alert(t('Custom javascript file not found')+"\n" + pFile, function(){
                this.close(true);
            }.bind(this));
        }.bind(this);

        window['contentLoaded_' + this.customId] = function () {
            this.content.empty();
            this.custom = new window[ this.getEntryPoint().replace(/\//g, '_') ](this);

            if (this.dialogContainer)
                this.dialogContainer.center(true);
        }.bind(this);

        this.customJsClassAsset =
            new Asset.javascript(_path + 'admin/backend/custom-js?module=' + this.getModule() + '&code=' + javascript +
                '&onLoad=' + this.customId);
    },

    toElement: function(){
        return this.border;
    },

    createWin: function () {
        this.border = new Element('div', {
            'class': 'ka-admin kwindow-border'
        });

        this.border.windowInstance = this;

        this.win = this.border;

        this.title = new Element('div', {
            'class': 'kwindow-win-title'
        }).addEvent('dblclick', function () {
            if (this.entryPointDefinition && this.entryPointDefinition.noMaximize !== true) {
                this.maximize();
            }
        }.bind(this)).inject(this.win);

        this.addEvent('resize', function(){
            ka.generateNoise(this.title, 0.1);
        }.bind(this));
        this.fireEvent('resize');

        this.titlePath = new Element('span', {'class': 'ka-kwindow-titlepath'}).inject(this.title);
        this.titleText = new Element('span').inject(this.titlePath);

        this.titleAdditional = new Element('span').inject(this.titlePath);


        this.titleGroups = new Element('div', {
            'class': 'kwindow-win-titleGroups'
        }).inject(this.win);

        this.createTitleBar();

        this.bottom = new Element('div', {
            'class': 'kwindow-win-bottom'
        }).inject(this.win);

        if (this.isInline()) {
            this.title.setStyle('display', 'none');
            this.titleGroups.setStyle('display', 'none');
            this.titleBar.setStyle('display', 'none');
            if (this.linker)
                this.linker.setStyle('display', 'none');
        }

        this.content = new Element('div', {
            'class': 'kwindow-win-content'
        }).inject(this.win);

        this.inFront = true;

        if (this.inline) {
            this.dialogContainer = new ka.Dialog(ka.wm.getWindow(this.parentId), {
                absolute: true,
                noBottom: true,
                width: this.link.width || '75%',
                height: this.link.height || '75%',
                minWidth: this.link.minWidth,
                minHeight: this.link.minHeight
            });

            /*
            this.getOpener().inlineContainer.empty();
            this.border.addClass('kwindow-border-inline');
            this.border.inject(this.getOpener().inlineContainer);
            this.updateInlinePosition();
            this.getOpener().addEvent('resize', this.updateInlinePosition.bind(this));
            */

            this.border.inject(this.dialogContainer.getContentContainer());

        } else {
            this.border.inject(ka.adminInterface.desktopContainer);
        }

    },

    setStatusText: function (pVal) {
        this.bottom.set('html', pVal);
    },

    getTitleContaner: function(){
        return this.title;
    },

    extendHead: function () {
        this.border.addClass('ka-window-extend-head');
        this.getTitleContaner().addClass('kwindow-win-title-extended');
    },

    addTabGroup: function () {
        this.extendHead();
        return new ka.TabGroup(this.getTitleGroupContainer());
    },

    addSmallTabGroup: function () {
        this.extendHead();
        return new ka.SmallTabGroup(this.getTitleGroupContainer());
    },

    getTitleGroupContainer: function(){
        return this.titleGroups;
    },

    getContentContainer: function(){
        return this.content;
    },

    addButtonGroup: function () {
        this.extendHead();
        return new ka.ButtonGroup(this.getTitleGroupContainer());
    },

    addBottomBar: function () {
        this.bottomBar = new Element('div', {
            'class': 'ka-windowEdit-actions',
            style: 'bottom: 0px'
        }).inject(this.content, 'after');

        this.bottomBar.addButton = function (pTitle, pOnClick) {
            var button = new ka.Button(pTitle).inject(this.bottomBar);
            if (pOnClick) button.addEvent('click', pOnClick);
            return button;
        }.bind(this);

        this.content.setStyle('bottom', 31);
        return this.bottomBar;
    },

    createTitleBar: function () {

        this.titleBar = new Element('div', {
            'class': 'kwindow-win-titleBar'
        }).inject(this.win);

        if (!this.isPopup()){
            this.maximizer = new Element('div', {
                'class': 'kwindow-win-titleBarIcon icon-expand-4'
            }).addEvent('click', this.maximize.bind(this)).inject(this.titleBar);
        }

        this.closer = new Element('div', {
            'class': 'kwindow-win-titleBarIcon icon-cancel-4'
        }).addEvent('click', this.close.bind(this)).inject(this.titleBar);

    },

    setBlocked: function(pBlocked){

        if (pBlocked)
            this.blockedOverlay = this.createOverlay();
        else if(this.blockedOverlay){
            this.blockedOverlay.destroy();
            delete this.blockedOverlay;
        }

    },

    createOverlay: function () {

        var overlay = new Element('div', {
            'class': 'ka-kwindow-overlay',
            styles: {
                opacity: 0.5,
                position: 'absolute',
                'background-color': '#666',
                left: 0, right: 0, 'top': 21, bottom: 0
            }
        });

        overlay.inject(this.border);

        return overlay;
    },

    deleteOverlay: function () {

        if (ka.performance) {
            this.content.setStyle('display', 'block');
            this.getTitleGroupContainer().setStyle('display', 'block');
        }

        this.inOverlayMode = false;
    },

    createResizer: function () {

        this.sizer = {};

        ['n', 'ne','e','se', 's', 'sw', 'w','nw'].each(function(item){
            this.sizer[item] = new Element('div', {
                'class': 'ka-kwindow-sizer ka-kwindow-sizer-'+item
            }).inject(this.border);
        }.bind(this));

        this.border.dragX = 0;
        this.border.dragY = 0;

        var minWidth = ( this.entryPointDefinition.minWidth > 0 ) ? this.entryPointDefinition.minWidth : 400;
        var minHeight = ( this.entryPointDefinition.minHeight > 0 ) ? this.entryPointDefinition.minHeight : 300;

        Object.each(this.sizer, function(item, key){
            item.setStyle('opacity', 0.01);

            var height, width, x, y, newHeight, newWidth, newY, newX, max;

            var options = {
                handle: item,
                style: false,
                modifiers: {
                    x: !['s', 'n'].contains(key)?'dragX':null,
                    y: !['e', 'w'].contains(key)?'dragY':null
                },
                snap: 0,
                onBeforeStart: function(pElement){
                    pElement.dragX = 0;
                    pElement.dragY = 0;
                    height = pElement.getStyle('height').toInt();
                    width  = pElement.getStyle('width').toInt();
                    y  = pElement.getStyle('top').toInt();
                    x  = pElement.getStyle('left').toInt();

                    newWidth = newHeight = newY = newX = null;

                    max = ka.adminInterface.desktopContainer.getSize();
                },
                onDrag: function(pElement, pEvent){

                    if (key === 'n' || key == 'ne' || key == 'nw'){
                        newHeight = height-pElement.dragY;
                        newY = y+pElement.dragY;
                    }
                    
                    if (key === 's' || key == 'se' || key == 'sw')
                        newHeight = height+pElement.dragY;

                    if (key === 'e' || key == 'se' || key == 'ne')
                        newWidth = width+pElement.dragX;

                    if (key === 'w' || key == 'sw' || key == 'nw'){
                        newWidth = width-pElement.dragX;
                        newX = x+pElement.dragX;
                    }

                    if (newWidth !== null && (newWidth > max.x || newWidth < minWidth) )
                        newWidth = newX = null;

                    if (newHeight !== null && (newHeight > max.y || newHeight < minHeight))
                        newHeight = newY = null;

                    if (newX !== null && newX > 0)
                        pElement.setStyle('left', newX);

                    if (newY !== null && newY > 0)
                        pElement.setStyle('top', newY);

                    if (newWidth !== null)
                        pElement.setStyle('width', newWidth);

                    if (newHeight !== null)
                        pElement.setStyle('height', newHeight);

                }

            };

            new Drag(this.border, options);
        }.bind(this));

    }

});
