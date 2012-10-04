ka.ObjectTable = new Class({

    Implements: [Options, Events],

    container: false,
    objectKey: '',
    win: false,

    options: {
        multi: false,
        itemsPerPage: 20
    },


    currentPage: 1,

    initialize: function(pContainer, pChooserBrowserOptions, pWindowInstance, pObjectKey){

        this.container = pContainer;

        this.subcontainer = new Element('div', {
            'class': 'ka-autoChooser-subContainer'
        }).inject(this.container);

        this.subcontainer.set('tween', {
            transition: Fx.Transitions.Quad.easeOut,
            duration: 300
        });

        this.objectKey = pObjectKey;
        this.setOptions(pChooserBrowserOptions);
        this.win = pWindowInstance;

        this._createLayout();

    },

    _createLayout: function(){

        this.subcontainer.empty();

        var columns = [];

        var objectDefinition = ka.getObjectDefinition(this.objectKey);

        Object.each(objectDefinition.chooserBrowserAutoColumns, function(column, key){
            columns.include([column.label?column.label:key, column.width?column.width:null]);
        });

        this.table = new ka.Table(columns, {
            selectable: true,
            multi: this.options.multi
        });

        this.table.body.setStyle('padding-bottom', 24);

        this.table.addEvent('select', function(){
            this.fireEvent('select');
        }.bind(this));

        document.id(this.table).inject(this.subcontainer);

        this.subcontainer.setStyle('overflow', 'hidden');

        this.absBar = new Element('div', {
            'class': 'ka-autoChooser-absBar'
        }).inject(this.subcontainer);

        this.pagination = new Element('div', {
            'class': 'ka-autoChooser-pagination-container gradient'
        }).inject(this.absBar);

        this.searchBtn = new Element('div', {
            'class': 'ka-autoChooser-searchBtn gradient',
            text: t('Search')
        })
        .addEvent('click', this.toggleSearch.bind(this))
        .inject(this.absBar);

        this.searchPane = new Element('div', {
            'class': 'ka-autoChooser-searchPane'
        });

        this.absBar.setStyle('bottom', -25);

        this.imgToLeft = new Element('img', {
            src: _path+ PATH_MEDIA + '/admin/images/icons/control_back.png'
        })
        .addEvent('click', this.pageToLeft.bind(this))
        .inject(this.pagination);

        this.iCurrentPage = new Element('input', {
            value: '-',
            maxlength: 5
        })
        .addEvent('keydown', function(e){

            if (e.control == false && e.meta == false && e.key.length == 1 && !e.key.test(/[0-9]/))
                e.stop();

            if (e.key == 'enter')
                this.loadPage(this.iCurrentPage.value);

        }.bind(this))
        .addEvent('keyup', function(e){
            this.value = this.value.replace(/[^0-9]+/, '');
        })
        .addEvent('blur', function(e){
            if (this.value == ''){
                this.value = 1;
                this.loadPage(this.iCurrentPage.value);
            }
        })
        .inject(this.pagination);

        new Element('span', {
            text: '/'
        }).inject(this.pagination);

        this.sMaxPages = new Element('span', {
            text: ''
        }).inject(this.pagination);

        this.imgToRight = new Element('img', {
            src: _path+ PATH_MEDIA + '/admin/images/icons/control_play.png'
        })
        .addEvent('click', this.pageToRight.bind(this))
        .inject(this.pagination);

        this.loadPage(1);

        this.absBar.tween('bottom', 0);
    },

    toggleSearch: function(){

        if(this.searchBtn.hasClass('ka-autoChooser-searchBtn-expanded')){
            this.subcontainer.tween('bottom', 0);
            this.searchPane.dispose()
            this.searchBtn.removeClass('ka-autoChooser-searchBtn-expanded');
        } else {
            this.subcontainer.tween('bottom', 150);
            this.searchPane.inject(this.container, 'top');
            this.searchBtn.addClass('ka-autoChooser-searchBtn-expanded');
        }

    },

    pageToLeft: function(){

        if (this.currentPage<=1) return false;
        this.loadPage(--this.currentPage);

    },

    pageToRight: function(){

        if (this.currentPage>=this.maxPages) return false;
        this.loadPage(++this.currentPage);

    },

    deselect: function(){
        this.table.deselect();
    },

    getValue: function(){

        var tr = this.table.selected();
        if (!tr) return;

        var item = tr.retrieve('item');

        return item;

    },

    loadPage: function(pPage){

        //first get count, fire there then real _loadPage() which loads the items then
        this.getCount(pPage);
    },

    getCount: function(pPage){

        this.lr = new Request.JSON({url: _path+'admin/backend/object-count/'+this.objectKey, noCache: 1, onComplete: function(pRes){

            this.itemsCount = pRes.data;
            this._loadPage(pPage);

        }.bind(this)}).get();

    },

    _loadPage: function(pPage){

        if (this.lr) this.lr.cancel();

        var offset = 0;
        if (pPage){
            offset = (pPage * this.options.itemsPerPage) - this.options.itemsPerPage;
        }

        var fields = [];

        var objectDefinition = ka.getObjectDefinition(this.objectKey);
        Object.each(objectDefinition.chooserBrowserAutoColumns, function(column, key){
            fields.push(key);
        });

        var req = {
            limit: this.options.itemsPerPage,
            offset: offset,
            fields: fields.join(',')
        }

        this.lr = new Request.JSON({url: _path+'admin/backend/object/'+this.objectKey, noCache: 1, onComplete: function(pRes){

            this.renderResult(pRes.data);
            this.renderActions(pPage, Math.ceil(this.itemsCount/this.options.itemsPerPage), this.itemsCount);

        }.bind(this)}).get(req);

    },

    renderActions: function(pPage, pMaxPages, pMaxItems){

        this.currentPage = pPage;
        this.maxPages = pMaxPages;
        this.sMaxPages.set('text', pMaxPages+' ('+pMaxItems+')');
        this.iCurrentPage.value = pPage;

        this.imgToLeft.setStyle('opacity', (pPage == 1)?0.5:1);
        this.imgToRight.setStyle('opacity', (pPage == pMaxPages)?0.5:1);


    },

    renderResult: function(pItems){

        this.table.empty();
        var objectDefinition = ka.getObjectDefinition(this.objectKey);

        var value;
        Array.each(pItems, function(item){

            var row  = [];

            Object.each(objectDefinition.chooserBrowserAutoColumns, function(column, key){

                value = ka.getObjectLabel(item, column, key, this.objectKey);
                row.include(value);
                
            });

            var tr = this.table.addRow(row);
            tr.store('item', item);

        }.bind(this));

    }

});