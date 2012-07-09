ka.ObjectTree = new Class({

    Implements: [Options, Events],
    ready: false,

    options: {
        openFirstLevel: false,
        rootObject: false,
        scopeId: false,
        selectObject: false,
        withContext: true,
        iconMap: false,
        withObjectAdd: false, //displays a plus icon and fires 'objectAdd' event on click with the objectId and objectKey as param
        iconAdd: 'admin/images/icons/add.png',

        move: true, //can we move the objects around
        icon: 'admin/images/icons/folder.png' //If iconMap is not defined, we use this
    },

    items: {},
    loadChildrenRequests: {},

    loadingDone: false,
    firstLoadDone: false,

    load_object_children: false,
    need2SelectAObject: false,
    domainA: false,
    inItemsGeneration: false,

    rootObject: {}, //copy of current root object
    rootLoaded: false,

    //contains the open state of the objects
    opens: {},

    objectKey: '',
    objectDefinition: {},

    initialize: function (pContainer, pObjectKey, pOptions, pRefs) {

        this.objectKey = pObjectKey;
        this.container = pContainer;
        this.objectDefinition = ka.getObjectDefinition(this.objectKey);
        if (!this.objectDefinition){
            throw 'Object not found: '+pObjectKey;
            return;
        }

        this.primaryKeys = ka.getPrimariesForObject(pObjectKey);
        Object.each(this.primaryKeys, function(def, id){
            if (!this.primaryKey) this.primaryKey = id;
        }.bind(this));

        if (!this.primaryKey){
            throw 'Object has no primary key: '+pObjectKey;
        }

        this.setOptions(pOptions);


        if (this.objectDefinition.nestedRootAsObject){
            if (!this.options.rootObject)
                this.options.rootObject = this.objectDefinition.nestedRootObject;
        }

        if (Cookie.read('krynObjectTree_' + pObjectKey)) {
            var opens = Cookie.read('krynObjectTree_' + pObjectKey);
            opens = opens.split('.');
            Array.each(opens, function (open) {
                this.opens[ open ] = true;
            }.bind(this));
        }

        if (pRefs) {
            this.options.objectObj = pRefs.objectObj;
            this.options.win = pRefs.win;
        }

        this.main = new Element('div', {
            'class': 'ka-objectTree'
        }).inject(this.container);

        this.topDummy = new Element('div', {
            'class': 'ka-objectTree-top-dummy'
        }).inject(this.main);

        this.paneObjectsTable = new Element('table', {
            style: 'width: 100%',
            cellpadding: 0,
            cellspacing: 0
        }).inject(this.main);

        this.container.addEvent('scroll', this.setRootPosition.bind(this));

        if (this.options.win)
            this.options.win.addEvent('resize', this.setRootPosition.bind(this));

        this.paneObjectsTBody = new Element('tbody').inject(this.paneObjectsTable);
        this.paneObjectsTr = new Element('tr').inject(this.paneObjectsTBody);
        this.paneObjectsTd = new Element('td').inject(this.paneObjectsTr);

        this.paneObjects = new Element('div', {
            'class': 'ka-objectTree-objects'
        }).inject(this.paneObjectsTd);

        this.paneObjects.setStyle('display', 'block');

        this.paneRoot = new Element('div', {
            'class': 'ka-objectTree-root'
        }).inject(this.main);

        this.paneRoot.set('morph', {duration: 200});

        if (this.options.selectObject) {
            this.startupWithObjectInfo(this.options.selectObject);
        } else {
            this.loadFirstLevel();
        }

        if (pContainer && pContainer.getParent('.kwindow-border')) {
            pContainer.getParent('.kwindow-border').retrieve('win').addEvent('close', this.clean.bind(this));
        }

        window.addEvent('mouseup', this.destroyContext.bind(this));

        this.main.addEvent('mouseup', this.onClick.bind(this));
        this.main.addEvent('mousedown', this.onMousedown.bind(this));
    },

    startupWithObjectInfo: function (pId, pCallback) {

        new Request.JSON({url: _path + 'admin/backend/objectParents', noCache: 1, onComplete: function (parents) {

            this.load_object_children = [];
            Object.each(parents, function (item, id) {
                this.load_object_children.include(id);
            }.bind(this));

            if (pCallback) {
                pCallback(parents);
            } else {
                this.loadFirstLevel();
            }

        }.bind(this)}).get({object: this.objectKey+'/'+pId});

    },

    clean: function () {

        this.destroyContext();

    },

    setRootPosition: function () {

        if (!this.options.rootObject) return;

        var nLeft = this.container.scrollLeft;
        var nTop = 0;

        var panePos = this.paneObjectsTable.getPosition(this.container).y;
        if (panePos - 20 < 0) {
            nTop = (panePos - 20) * -1;
            var maxTop = this.paneObjects.getSize().y - 20;
            if (nTop > maxTop) nTop = maxTop;
        }

        this.paneRoot.morph({
            //'width': nWidth,
            'left': nLeft,
            'top': nTop
        });

    },

    loadFirstLevel: function (pRootId) {

        if (this.lastFirstLevelRq) {
            this.lastFirstLevelRq.cancel();
        }

        if (this.options.rootObject && !this.rootLoaded){
            this.loadRoot();
            return;
        } else {

            this.rootA = new Element('a');
            this.rootA.childContainer = this.paneObjects;

        }
        var objectUrl = this.objectKey;

        if (this.options.rootObject)
            objectUrl += '?'+Object.toQueryString({scopeId: this.options.scopeId});

        this.lastFirstLevelRq = new Request.JSON({url: _path + 'admin/backend/objectTree', noCache: 1, onComplete: this.renderFirstLevel.bind(this)}).get({
            object: objectUrl
        });

    },

    loadRoot: function(){

        if (this.lastFirstLevelRq) {
            this.lastFirstLevelRq.cancel();
        }

        if (typeOf(this.options.scopeId) == 'null' || this.options.scopeId === false){
            throw t('Missing option scopeId in ka.ObjectTree. Unable to load root ob the object:'+' '+this.objectKey);
        }

        this.rootLoaded = false;

        this.lastFirstLevelRq = new Request.JSON({url: _path + 'admin/backend/objectTreeRoot', noCache: 1, onComplete: this.renderRoot.bind(this)}).get({
            scopeId: this.options.scopeId,
            object: this.objectKey
        });

    },

    renderRoot: function(pRes){

        var rootDefinition = ka.getObjectDefinition(this.objectDefinition.nestedRootObject);
        var primaryKeys = ka.getPrimaryListForObject(this.objectDefinition.nestedRootObject);

        this.rootObject = pRes;
        var id = pRes[primaryKeys[0]];
        var label = pRes[this.objectDefinition.nestedRootObjectLabel];

        if (this.paneRoot)
            this.paneRoot.empty();

        var a = new Element('div', {
            'class': 'ka-objectTree-item',
            title: 'ID=' + id
        });

        a.id = id;
        a.objectKey = this.objectDefinition.nestedRootObject;
        a.label = label;

        if (id == this.options.selectObject && this.options.noActive != true){
            a.addClass('ka-objectTree-item-selected');
        }

        a.inject(this.paneRoot);

        a.objectTreeObj = this;

        a.span = new Element('span', {
            'class': 'ka-objectTree-item-title',
            text: label
        }).inject(a);

        this.items[ this.objectDefinition.nestedRootObject+'_'+id ] = a;

        a.store('item', pRes);

        a.childrenLoaded = true;

        this.rootA = a;
        a.childContainer = this.paneObjects;

        var icon = this.objectDefinition.chooserBrowserTreeRootObjectIconPath;

        if (!this.objectDefinition.chooserBrowserTreeRootObjectFixedIcon){
            //todo
            icon = this.options.iconMap[pRes[this.objectDefinition.chooserBrowserTreeRootObjectIcon]];
        }

        if (icon){
            a.masks = new Element('span', {
                'class': 'ka-objectTree-item-masks'
            }).inject(a, 'top');

            new Element('img', {
                'class': 'ka-objectTree-item-type',
                src: _path + PATH_MEDIA + icon
            }).inject(a.masks);
        }

        a.toggler = new Element('img', {
            'class': 'ka-objectTree-item-toggler',
            title: _('Open/Close sub-items'),
            src: _path + PATH_MEDIA + '/admin/images/icons/tree_minus.png'
        }).inject(a, 'top');

        a.toggler.addEvent('click', function (e) {
            e.stopPropagation();
            window.fireEvent('click');
            this.toggleChildren(a);
        }.bind(this));



        this.rootLoaded = true;


        this.loadFirstLevel();

        if (this.options.openFirstLevel){
            this.openChildren(this.rootA);
        }

    },

    renderFirstLevel: function (pItems) {

        this.loadingDone = false;

        if (!pItems && this.lastRootItems) {
            pItems = this.lastRootItems;
        }

        this.lastRootItems = pItems;

        this.paneObjects.empty();

        this.addRootItems(pItems, this.paneObjects);

        if (this.options.withObjectAdd) {

            if (ka.checkDomainAccess(this.rootA.id, 'addObjects')) {

                new Element('img', {
                    src: _path + PATH_MEDIA+this.options.iconAdd,
                    title: t('Add object'),
                    'class': 'ka-objectTree-add'
                }).addEvent('click', function (e) {
                    this.fireEvent('objectAdd', this.rootA.id, this.rootA.objectKey);
                }.bind(this)).inject(this.rootA);

            }

        }

    },

    onMousedown: function (e) {


        if (this.options.move && e.target){

            var el = e.target;

            if (!el.hasClass('ka-objectTree-item'))
                el = el.getParent('.ka-objectTree-item');

            if (el){
                this.activePress = true;
                (function(){
                    if (this.activePress)
                        this.createDrag(el, e);
                }).delay(200, this);

            }
        }

        e.preventDefault();
    },

    onClick: function (e) {

        if (this.inDragMode) return;
        this.activePress = false;

        var target = e.target;
        if (!target) return;
        var a = null;

        if (target.hasClass('ka-objectTree-item-toggler')) return;

        if (target.hasClass('ka-objectTree-item')) {
            a = target;
        }

        if (!a && target.getParent('.ka-objectTree-item')) {
            a = target.getParent('.ka-objectTree-item');
        }

        if (!a) return;

        var item = a.retrieve('item');

        if (e.rightClick) {
            this.openContext(e, a, item);
            return;
        }

        if (item.domain) {

            if (this.options.no_domain_select != true) {


                this.deselect();
                if (this.options.noActive != true) {
                    a.addClass('ka-objectTree-item-selected');
                }

                this.fireEvent('selection', [item, a])
                this.fireEvent('domainClick', [item, a])

                this.lastSelectedItem = a;
                this.lastSelectedObject = item;
            }

        } else {

            this.deselect();

            if (this.options.noActive != true) {
                a.addClass('ka-objectTree-item-selected');
            }

            this.fireEvent('selection', [item, a])
            this.fireEvent('click', [item, a]);

            this.lastSelectedItem = a;
            this.lastSelectedObject = item;
        }

    },

    reloadParentOfActive: function () {

        if (!this.lastSelectedItem) return;

        if (this.lastSelectedObject.domain || this.lastSelectedObject.prsn == 0) {
            this.reload();
            return;
        }

        var parent = this.lastSelectedItem.getParent().getPrevious();
        if (parent && parent.hasClass('ka-objectTree-item')) {
            this.lastScrollPos = this.container.getScroll();
            this.loadChildren(parent);
        }
    },

    addRootItems: function(pItems, pContainer){

        Array.each(pItems, function(item){

            this.addItem(item, this.rootA);

        }.bind(this));

    },

    addItem: function (pItem, pParent) {


        var id = pItem[this.primaryKey];
        var label = pItem[this.objectDefinition.nestedLabel];

        var a = new Element('div', {
            'class': 'ka-objectTree-item',
            title: 'ID=' + id
        });

        a.id = id;
        a.parent = pParent;
        a.objectKey = this.objectKey;
        a.label = label;

        var container = pParent;
        if (pParent.childContainer) {
            container = pParent.childContainer;
            a.parent = pParent;
        }

        if (id == this.options.selectObject && this.options.noActive != true){
            a.addClass('ka-objectTree-item-selected');
        }

        a.inject(container);

        a.objectTreeObj = this;

        a.span = new Element('span', {
            'class': 'ka-objectTree-item-title',
            text: label
        }).inject(a);

        this.items[ id ] = a;

        a.store('item', pItem);


        if (a.parent) {
            var paddingLeft = 15;
            if (a.parent.getStyle('padding-left').toInt())
                paddingLeft += a.parent.getStyle('padding-left').toInt();

            a.setStyle('padding-left', paddingLeft);
        }

        this.addItemIcon(pItem, a);


        /*
         if (this.lastSelectedObject && (
         (this.lastSelectedObject.domain && pItem.domain && this.lastSelectedObject.rsn == pItem.rsn) || (!pItem.domain && !this.lastSelectedObject.domain && this.lastSelectedObject.rsn == pItem.rsn)
         )) {

         if (this.options.noActive != true) {
         a.addClass('ka-objectTree-item-selected');
         }

         this.lastSelectedItem = a;
         this.lastSelectedObject = pItem;
         }*/

        /* masks */

        /* toggler */
        a.toggler = new Element('img', {
            'class': 'ka-objectTree-item-toggler',
            title: _('Open/Close subitems'),
            src: _path + PATH_MEDIA + '/admin/images/icons/tree_plus.png'
        }).inject(a, 'top');

        if (pItem._children_count == 0) {
            a.toggler.setStyle('visibility', 'hidden');
        } else {
            a.toggler.addEvent('click', function (e) {
                e.stopPropagation();
                window.fireEvent('click');
                this.toggleChildren(a);
            }.bind(this));
        }

        a.childContainer = new Element('div', {
            'class': 'ka-objectTree-item-children'
        }).inject(container);

        a.childrenLoaded = (pItem._children) ? true : false;

        var openId = id;

        if ((!this.firstLoadDone || this.need2SelectAObject)) {
            if ((this.options.selectDomain && pItem.domain ) || (this.options.selectObject && !pItem.domain && pItem.rsn == this.options.selectObject)) {
                if (this.options.noActive != true) {
                    a.addClass('ka-objectTree-item-selected');
                }
                this.lastSelectedItem = a;
                this.lastSelectedObject = pItem;
                this.need2SelectAObject = false;
            }
        }

        if (this.opens[openId]) {
            this.openChildren(a);
        }

        if (/*(!this.firstLoadDone || this.need2SelectAObject) && */this.load_object_children !== false) {

            if (this.load_object_children.contains(id)) {
                this.openChildren(a);
            }
        }
        /*else if ((!this.firstLoadDone || this.need2SelectAObject) && this.options.openFirstLevel && !this.opens[openId]) {
            this.openChildren(a);
        }*/

        if (pItem._children) {
            var canChangeItemsGeneration = this.inItemsGeneration == true ? false : true;

            if (canChangeItemsGeneration) {
                this.inItemsGeneration = true;
            }

            Array.each(pItem._children, function (item) {
                this.addItem(item, a);
            }.bind(this));

            if (canChangeItemsGeneration) {
                this.inItemsGeneration = false;
            }
        }

        this.checkDoneState();

        return a;
    },

    addItemIcon: function(pItem, pA){

        var icon = this.options.icon;

        if (this.options.iconMap && this.objectDefinition.nestedIcon)
            icon = this.options.iconMap[pItem[this.objectDefinition.nestedIcon]];

        if (!icon) return;

        pA.masks = new Element('span', {
            'class': 'ka-objectTree-item-masks'
        }).inject(pA, 'top');

        new Element('img', {
            'class': 'ka-objectTree-item-type',
            src: _path + PATH_MEDIA + icon
        }).inject(pA.masks);


        /**
         *  Extract to pagesTree
         *
         */
        if ((pItem.type == 0 || pItem.type == 1) && pItem.visible == 0) {
            new Element('img', {
                src: _path + PATH_MEDIA + '/admin/images/icons/pageMasks/invisible.png'
            }).inject(pA.masks);
        }

        if (pItem.type == 1) {
            new Element('img', {
                src: _path + PATH_MEDIA + '/admin/images/icons/pageMasks/link.png'
            }).inject(pA.masks);
        }

        if ((pItem.type == 0 || pItem.type == 3) && pItem.draft_exist == 1) {
            new Element('img', {
                src: _path + PATH_MEDIA + '/admin/images/icons/pageMasks/draft_exist.png'
            }).inject(pA.masks);
        }

        if (pItem.access_denied == 1) {
            new Element('img', {
                src: _path + PATH_MEDIA + '/admin/images/icons/pageMasks/access_denied.png'
            }).inject(pA.masks);
        }

        if (pItem.type == 0 && pItem.access_from_groups != "" && typeOf(pItem.access_from_groups) == 'string') {
            new Element('img', {
                src: _path + PATH_MEDIA + '/admin/images/icons/pageMasks/access_group_limited.png'
            }).inject(pA.masks);
        }

    },

    checkDoneState: function () {

        var loadingDone = true;
        if (this.inItemsGeneration == false) {
            Object.each(this.loadChildrenRequests, function (request) {
                if (request == true) {
                    loadingDone = false;
                }
            }.bind(this));
        } else {
            loadingDone = false;
        }

        if (loadingDone == true) {

            this.loadChildrenRequests = {};
            if (this.firstLoadDone == false) {
                this.firstLoadDone = true;

                this.fireEvent('ready');
            }

            if (this.lastScrollPos) {
                this.container.scrollTo(this.lastScrollPos.x, this.lastScrollPos.y);
            }
            this.setRootPosition();
        }

        this.loadingDone = loadingDone;

    },

    saveOpens: function () {

        var opens = '';
        Object.each(this.opens, function (bool, key) {
            if (bool == true) {
                opens += key + '.';
            }
        });
        Cookie.write('krynObjectTree_' + this.objectKey, opens);

    },

    toggleChildren: function (pA) {

        if (pA.childContainer.getStyle('display') != 'block') {
            this.openChildren(pA);
        } else {
            this.closeChildren(pA);
        }
    },

    closeChildren: function (pA) {
        var item = pA.retrieve('item');

        pA.childContainer.setStyle('display', '');
        pA.toggler.set('src', _path + PATH_MEDIA + '/admin/images/icons/tree_plus.png');
        this.opens[ pA.id ] = false;
        this.setRootPosition();

        this.saveOpens();
    },

    openChildren: function (pA) {

        if (!pA.toggler) return;

        pA.toggler.set('src', _path + PATH_MEDIA + '/admin/images/icons/tree_minus.png');
        if (pA.childrenLoaded == true) {
            pA.childContainer.setStyle('display', 'block');
            this.opens[ pA.id ] = true;
            this.saveOpens();
        } else {
            this.loadChildren(pA, true);
        }
        this.setRootPosition();

    },

    reloadChildren: function (pA) {

        if (this.rootA == pA){
            this.loadFirstLevel();
        } else {
            this.loadChildren(pA, false);
        }
    },

    removeChildren: function(pA){

        if (!pA.childContainer) return;

        pA.childContainer.getElements('ka-objectTree-item').each(function(a){
            delete this.items[a.id];
        }.bind(this));


        pA.childContainer.empty();

    },

    loadChildren: function (pA, pAndOpen) {

        var item = pA.retrieve('item');

        var loader = new Element('img', {
            src: _path + PATH_MEDIA + '/admin/images/loading.gif'
        }).inject(pA.span);

        this.loadChildrenRequests[ pA.id ] = true;
        new Request.JSON({url: _path + 'admin/backend/objectTree', noCache: 1, onComplete: function (pItem) {

            this.removeChildren(pA);

            loader.destroy();

            if (pAndOpen) {
                pA.toggler.set('src', _path + PATH_MEDIA + '/admin/images/icons/tree_minus.png');
                pA.childContainer.setStyle('display', 'block');
                this.opens[ pA.id ] = true;
                this.saveOpens();
            }

            pA.childrenLoaded = true;

            if (pItem._children_count == 0) {
                pA.toggler.setStyle('visibility', 'hidden');
                return;
            }

            this.inItemsGeneration = true;
            Array.each(pItem._children, function (childitem) {
                this.addItem(childitem, pA);
            }.bind(this));
            this.inItemsGeneration = false;

            this.loadChildrenRequests[ pA.id ] = false;
            this.checkDoneState();

            this.fireEvent('childrenLoaded', [item, pA]);
            this.setRootPosition();

        }.bind(this)}).get({ object: this.objectKey+'/'+pA.id });

    },

    deselect: function () {

        this.container.getElements('.ka-objectTree-item-selected').removeClass('ka-objectTree-item-selected');

        this.lastSelectedItem = false;
        this.lastSelectedObject = false;
    },

    createDrag: function (pA, pEvent) {

        this.currentObjectToDrag = pA;

        var canMoveObject = true;
        var object = pA.retrieve('item');
        /*
        if (object.domain) {
            if (!ka.checkObjectAccess(object.rsn, 'moveObjects', 'd')) {
                canMoveObject = false;
            }
        } else {
            if (!ka.checkObjectAccess(object.rsn, 'moveObjects')) {
                canMoveObject = false;
            }
        }*/

        var kwin = pA.getParent('.kwindow-border');

        if (this.lastClone) {
            this.lastClone.destroy();
        }

        this.lastClone = new Element('div', {
            'class': 'ka-objectTree-drag-box'
        }).inject(kwin);

        new Element('span', {
            text: pA.get('text')
        }).inject(this.lastClone);

        if (pA.masks)
            pA.masks.clone().inject(this.lastClone, 'top');

        var drag = this.lastClone.makeDraggable({
            snap: 0,
            onDrag: function (pDrag, pEvent) {
                if (!pEvent.target) return;
                var element = pEvent.target;

                if (!element.hasClass('ka-objectTree-item')) {
                    element = element.getParent('.ka-objectTree-item');
                }

                if (element) {

                    var pos = pEvent.target.getPosition(document.body);
                    var size = pEvent.target.getSize();
                    var mrposy = pEvent.client.y - pos.y;

                    if (mrposy < size.y / 3) {
                        this.createDropElement(element, 'before');
                    } else if (mrposy > ((size.y / 3) * 2)) {
                        this.createDropElement(element, 'after');
                    } else {
                        //middle
                        this.createDropElement(element, 'inside');
                    }

                }
            }.bind(this),
            onDrop: this.cancelDragNDrop.bind(this),
            onCancel: function () {
                this.cancelDragNDrop(true);
            }.bind(this)
        });

        this.inDragMode = true;
        this.inDragModeA = pA;

        var pos = kwin.getPosition(document.body);

        this.lastClone.setStyles({
            'left': pEvent.client.x + 5 - pos.x,
            'top': pEvent.client.y + 5 - pos.y
        });

        document.addEvent('mouseup', this.cancelDragNDrop.bind(this, true));

        drag.start(pEvent);
    },

    createDropElement: function (pTarget, pPos) {

        if (this.loadChildrenDelay) clearTimeout(this.loadChildrenDelay);

        if (this.dropElement) {
            this.dropElement.destroy();
            delete this.dropElement;
        }

        if (this.currentObjectToDrag == pTarget) return;

        this.dragNDropElement = pTarget;
        this.dragNDropPos = pPos;

        if (this.dropLastItem) {
            this.dropLastItem.removeClass('ka-objectTree-item-dragOver');
            this.dropLastItem.setStyle('padding-bottom', 1);
            this.dropLastItem.setStyle('padding-top', 1);
        }

        var item = pTarget.retrieve('item');


        pTarget.setStyle('padding-bottom', 1);
        pTarget.setStyle('padding-top', 1);

        if (pTarget.objectKey == this.objectKey) {
            if (pPos == 'after' || pPos == 'before') {
                this.dropElement = new Element('div', {
                    'class': 'ka-objectTree-dropElement',
                    styles: {
                        'margin-left': pTarget.getStyle('padding-left').toInt() + 16
                    }
                });
            } else {
                if (this.lastDropElement == pTarget) {
                    return;
                }
            }
        }


        var canMoveInto = true;
        /*
        if (item.domain) {
            if (!ka.checkObjectAccess(item.rsn, 'addObjects', 'd')) {
                canMoveInto = false;
            }
        } else {
            if (!ka.checkObjectAccess(item.rsn, 'addObjects')) {
                canMoveInto = false;
            }
        }*/

        var canMoveAround = true;
        if (pTarget.parent) {
            var parentObject = pTarget.parent.retrieve('item');
            /*
            if (parentObject.domain) {
                if (!ka.checkObjectAccess(parentObject.rsn, 'addObjects', 'd')) {
                    canMoveAround = false;
                }
            } else {
                if (!ka.checkObjectAccess(parentObject.rsn, 'addObjects')) {
                    canMoveAround = false;
                }
            }*/
        }

        if (pTarget.objectKey == this.objectKey && pPos == 'after') {
            if (canMoveAround) {
                this.dropElement.inject(pTarget.getNext(), 'after');
                pTarget.setStyle('padding-bottom', 0);
            }

        } else if (pTarget.objectKey == this.objectKey && pPos == 'before') {
            if (canMoveAround) {
                this.dropElement.inject(pTarget, 'before');
                pTarget.setStyle('padding-top', 0);
            }

        } else if (pPos == 'inside') {
            if (canMoveInto) {
                pTarget.addClass('ka-objectTree-item-dragOver');
            }
            this.loadChildrenDelay = function () {
                this.openChildren(pTarget);
            }.delay(1000, this);
        }


        this.dropLastItem = pTarget;
    },

    cancelDragNDrop: function (pWithoutMoving) {

        if (this.lastClone) {
            this.lastClone.destroy();
            delete this.lastClone;
        }
        if (this.dropElement) {
            this.dropElement.destroy();
            delete this.dropElement;
        }
        if (this.dropLastItem) {
            this.dropLastItem.removeClass('ka-objectTree-item-dragOver');
            this.dropLastItem.setStyle('padding-bottom', 1);
            this.dropLastItem.setStyle('padding-top', 1);
            delete this.dropLastItem;
        }
        this.inDragMode = false;
        delete this.inDragModeA;


        if (pWithoutMoving != true) {

            var pos = {
                'before': 'over',
                'after': 'below',
                'inside': 'into'
            };

            var target = this.dragNDropElement;
            var source = this.currentObjectToDrag;

            var code = pos[this.dragNDropPos];
            var targetId = target.objectKey+'/'+target.id;
            var sourceId = source.objectKey+'/'+source.id;

            if (this.rootA == this.dragNDropElement){
                code = 'into';
            }

            this.moveObject(sourceId, targetId, code);
        }
        document.removeEvent('mouseup', this.cancelDragNDrop.bind(this));
    },


    reloadParent: function (pA) {
        if (pA.parent) {
            pA.objectTreeObj.reloadChildren(pA.parent);
        } else {
            pA.objectTreeObj.reload();
        }
    },

    moveObject: function (pSourceId, pTargetId, pCode, pToDomain) {
        var _this = this;
        var req = {
            source: pSourceId,
            target: pTargetId,
            mode: pCode
        };

        new Request.JSON({url: _path + 'admin/backend/moveObject', onComplete: function (res) {

            //target item this.dragNDropElement

            if (this.dragNDropElement.parent) {
                this.dragNDropElement.objectTreeObj.reloadChildren(this.dragNDropElement.parent);
            } else {
                this.dragNDropElement.objectTreeObj.reload();
            }

            //origin item this.currentObjectToDrag
            if (this.currentObjectToDrag.parent && (!this.dragNDropElement.parent || this.dragNDropElement.parent != this.currentObjectToDrag.parent)) {
                this.currentObjectToDrag.objectTreeObj.reloadChildren(this.currentObjectToDrag.parent);
            } else if (!this.dragNDropElement.parent || this.dragNDropElement.objectTreeObj != this.currentObjectToDrag.objectTreeObj) {
                this.currentObjectToDrag.objectTreeObj.reload();
            }

            ka.loadSettings(['r2d']);

        }.bind(this)}).get(req);
    },

    reload: function () {
        this.lastScrollPos = this.container.getScroll();
        this.loadFirstLevel();
    },


    isReady: function () {
        return this.firstLoadDone;
    },

    hasChildren: function (pObject) {
        if (this._objectsParent.get(pObject.rsn)) {
            return true;
        }
        return false;
    },

    getSelected: function () {
        var selected = this.container.getElement('.ka-objectTree-item-selected');
        return selected?selected.retrieve('item'):false;
    },

    getItem: function(pId){
        return this.items[ pId ]?this.items[ pId ]:false;
    },

    select: function (pId) {

        this.deselect();

        if (this.items[ pId ]) {
            //has been loaded already
            this.items[ pId ].addClass('ka-objectTree-item-selected');

            this.lastSelectedItem = this.items[ pId ];
            this.lastSelectedObject = this.items[ pId ].retrieve('item');

            //open parents too
            var parent = this.items[ pId ];
            while(true){
                if (parent.parent){
                    parent = parent.parent;
                    this.openChildren(parent);
                } else {
                    break;
                }
            }

            return;
        }

        //this.need2SelectAObject = true;

        this.startupWithObjectInfo(pId, function (parents) {

            this.options.selectObject = pId;

            Array.each(this.load_object_children, function (id) {
                if (this.items[id]) {
                    this.openChildren(this.items[id]);
                }
            }.bind(this));

        }.bind(this));

    },

    destroyContext: function () {
        if (this.oldContext) {
            this.lastContextA.removeClass('ka-objectTree-item-hover');
            this.oldContext.destroy();
            delete this.oldContext;
        }
    },

    openContext: function (pEvent, pA, pObject) {

        if (this.options.withContext != true) return;

        if (!pEvent.rightClick) return;

        window.fireEvent('mouseup');
        pEvent.stopPropagation();

        pA.addClass('ka-objectTree-item-hover');
        this.lastContextA = pA;

        this.oldContext = new Element('div', {
            'class': 'ka-objectTree-context'
        }).inject(document.body);


        this.createContextItems(pA);
        this.doContextPosition(pEvent);
    },

    createContextItems: function(pA){

        var pObject = pA.retrieve('item');

        var objectCopy = {
            objectKey: this.objectKey,
            object: pObject
        };

        new Element('a', {
            html: t('Copy')
        }).addEvent('click', function () {
            ka.setClipboard(t('Object %s copied').replace('%s', this.objectDefinition.label), 'objectCopy', objectCopy);
        }.bind(this)).inject(this.oldContext);

        new Element('a', {
            html: t('Copy with sub-elements')
        }).addEvent('click', function () {
            ka.setClipboard(t('Object %s with sub elements copied').replace('%s', this.objectDefinition.label),
                'objectCopyWithSubElements', objectCopy);
        }.bind(this)).inject(this.oldContext);

        new Element('a', {
            'class': 'delimiter'
        }).inject(this.oldContext);

        new Element('a', {
            html: t('Delete')
        }).addEvent('click', function () {

        }.bind(this)).inject(this.oldContext);

        var clipboard = ka.getClipboard();
        if (!(clipboard.type == 'objectCopyWithSubpages' || clipboard.type == 'objectCopy')) {
            return;
        }

        var canPasteInto = true;
        var canPasteAround = true;

        /* todo

         if (pPage.domain) {
             if (!ka.checkPageAccess(pPage.rsn, 'addPages', 'd')) {
                canPasteInto = false;
             }
         } else {
             if (!ka.checkPageAccess(pPage.rsn, 'addPages')) {
                canPasteInto = false;
             }
         }

         if (pA.parent) {
            var parentPage = pA.parent.retrieve('item');
            if (parentPage.domain) {
                if (!ka.checkPageAccess(parentPage.rsn, 'addPages', 'd')) {
                    canPasteAround = false;
                }
            } else {
                if (!ka.checkPageAccess(parentPage.rsn, 'addPages')) {
                    canPasteAround = false;
                }
            }
        }*/


        if (canPasteAround || canPasteInto) {

            new Element('a', {
                'class': 'noaction',
                html: _('Paste')
            }).inject(this.oldContext);


            if (canPasteAround && !pPage.domain) {
                new Element('a', {
                    'class': 'indented',
                    html: _('Before')
                }).addEvent('click', function () {
                    this.paste('up', pPage);
                }.bind(this)).inject(this.oldContext);
            }

            if (canPasteInto) {
                new Element('a', {
                    'class': 'indented',
                    html: _('Into')
                }).addEvent('click', function () {
                    this.paste('into', pPage);
                }.bind(this)).inject(this.oldContext);
            }
            if (canPasteAround && !pPage.domain) {
                new Element('a', {
                    'class': 'indented',
                    html: _('After')
                }).addEvent('click', function () {
                    this.paste('down', pPage);
                }.bind(this)).inject(this.oldContext);
            }
        }

    },

    doContextPosition: function(pEvent){

        var wsize = window.getSize();
        var csize = this.oldContext.getSize();


        var left = pEvent.page.x - (this.container.getPosition(document.body).x);
        var mtop = pEvent.page.y - (this.container.getPosition(document.body).y);

        var left = pEvent.page.x;
        var mtop = pEvent.page.y;
        if (mtop < 0) {
            mtop = 1;
        }

        this.oldContext.setStyles({
            left: left,
            'top': mtop
        });

        if (mtop + csize.y > wsize.y) {
            mtop = mtop - csize.y;
            this.oldContext.setStyle('top', mtop + 1);
        }
    }

});
