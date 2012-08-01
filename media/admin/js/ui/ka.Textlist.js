ka.Textlist = new Class({
    Implements: [Events, Options],

    arrow: PATH_MEDIA+'admin/images/icons/tree_minus.png',

    opened: false,
    value: null,

    items: {},
    a: {},
    enabled: true,

    chosen: [],

    options: {

        items: false, //array or object
        store: false, //string
        customValue: false //boolean

    },

    initialize: function (pContainer, pOptions) {

        this.setOptions(pOptions);

        this.box = new Element('div', {
            'class': 'ka-normalize ka-Textlist-box'
        }).addEvent('click', this.toggle.bind(this));

        this.title = new Element('div', {
            'class': 'ka-Input ka-Textlist-box-title'
        }).addEvent('mousedown', function (e) {
            e.preventDefault();
        }).inject(this.box);

        this.input = new Element('input', {
            autocomplete: false,
            tabindex: 0,
            style: 'width: 7px;'
        }).inject(this.title);

        this.arrowBox = new Element('div', {
            'class': 'ka-Textlist-arrow'
        }).inject(this.box);

        this.arrow = new Element('img', {
            src: _path + this.arrow
        }).inject(this.arrowBox);

        this.chooser = new Element('div', {
            'class': 'ka-Textlist-chooser ka-normalize'
        });

        this.chooser.addEvent('click', function (e) {
            e.stop();
        });

        if (pContainer)
            this.box.inject(pContainer)

        if (this.options.items){
            if (typeOf(this.options.items) == 'object'){
                Object.each(this.options.items, function(label, key){
                    this.add(key, label);
                }.bind(this))
            }

            if (typeOf(this.options.items) == 'array'){
                Array.each(this.options.items, function(label){
                    this.add(label, label);
                }.bind(this))
            }
        }

    },

    setEnabled: function(pEnabled){

        this.enabled = pEnabled;

        this.arrowBox.setStyle('opacity', pEnabled?1:0.4);

    },

    inject: function (p, p2) {
        this.box.inject(p, p2);

        return this;
    },

    destroy: function () {
        this.chooser.destroy();
        this.box.destroy();
        this.chooser = null;
        this.box = null;
    },

    remove: function(pId){
        if (typeOf(this.items[ pId ]) == 'null') return;

        this.hideOption(pId);
        delete this.items[pId];
        delete this.a[pId];

    },

    hideOption: function(pId){

        if (typeOf(this.items[ pId ]) == 'null') return;

        this.a[pId].setStyle('display', 'none');

        if (this.value == pId){

            var found = false, before, first;
            Object.each(this.items,function(label, id){
                if (found) return;
                if (!first) first = id;
                if (before && id == pId){
                    found = true;
                    return;
                }

                before = id;
            }.bind(this));

            if (found){
                this.setValue(before);
            } else {
                this.setValue(first);
            }
        }

    },

    showOption: function(pId){

        if (typeOf(this.items[ pId ]) == 'null') return;

        this.a[pId].setStyle('display');

    },

    addSplit: function (pLabel) {
        new Element('div', {
            html: pLabel,
            'class': 'group'
        }).inject(this.chooser);
    },

    setText: function(pId, pLabel){

        if (typeOf(this.items[ pId ]) == 'null') return;

        this.items[ pId ] = pLabel;

        var img;
        if (this.a[pId].getElement('img')){
            img = this.a[pId].getElement('img');
            img.dispose();
        }

        this.a[pId].set('text', pLabel);

        if (img){
            img.inject(this.a[pId], 'top');
        }

        if (this.value == pId){
            this.title.set('text', this.items[ pId ]);
            this.box.set('title', (this.items[ pId ] + "").stripTags());

            if (this.a[pId].getElement('img')){
                this.a[pId].getElement('img').clone().inject(this.title, 'top');
            }
        }
    },


    addImage: function (pId, pLabel, pImagePath, pPos) {

        return this.add(pId, pLabel, pPos, pImagePath);
    },

    add: function (pId, pLabel, pPos, pImagePath) {

        if (typeOf(pLabel) == 'array'){
            pImagePath = pLabel[1];
            pLabel = pLabel[0];
        }

        if (typeOf(pLabel) != 'string')
            pLabel = pId;

        this.items[ pId ] = pLabel;


        this.a[pId] = new Element('a', {
            text: pLabel,
            href: 'javascript:;'
        }).addEvent('click', function () {

            this.setValue(pId, true);
            this.close();

        }.bind(this));

        if (pImagePath){

            new Element('img', {
                src: ka.mediaPath(pImagePath)
            }).inject(this.a[pId], 'top');

        }

        if (!pPos) {
            this.a[pId].inject(this.chooser);
        } else if (pPos == 'top') {
            this.a[pId].inject(this.chooser, 'top');
        } else if (this.a[pPos]) {
            this.a[pId].inject(this.a[pPos], 'after');
        }

        if (this.value == null) {
            this.setValue(pId);
        }

    },

    setStyle: function (p, p2) {
        this.box.setStyle(p, p2);
        return this;
    },

    empty: function () {

        this.items = {};
        this.value = null;
        this.title.set('html', '');
        this.chooser.empty();

    },

    setValue: function (pValue, pEvent) {

        //if (!this.items[ pValue ]) return false;

        this.value = pValue;
        this.title.set('text', this.items[ pValue ]);
        this.box.set('title', (this.items[ pValue ] + "").stripTags());

        if (this.a[pValue].getElement('img')){
            this.a[pValue].getElement('img').clone().inject(this.title, 'top');
        }

        Object.each(this.a, function (item, id) {
            item.removeClass('active');
            if (id == pValue) {
                item.addClass('active');
            }
        });

        //chrome rendering bug
        this.arrowBox.setStyle('right', 3);
        (function () {
            this.arrowBox.setStyle('right', 2);
        }.bind(this)).delay(10);

        if (pEvent) {
            this.fireEvent('change', pValue);
        }

        return true;
    },

    getValue: function () {
        return this.value;
    },

    toggle: function (e) {

        if (this.chooser.getParent()) {
            this.close();
        } else {
            this.open();
        }
    },

    close: function(){
        this.chooser.dispose();
        this.box.removeClass('ka-Textlist-box-open');
    },

    open: function () {

        if (!this.enabled) return;

        if (this.box.getParent('.kwindow-win-titleGroups'))
            this.chooser.addClass('ka-Textlist-darker');
        else
            this.chooser.removeClass('ka-Textlist-darker');

        this.box.addClass('ka-Textlist-box-open');

        ka.openDialog({
            element: this.chooser,
            target: this.box,
            onClose: this.close.bind(this),
            offset: {y: -1}
        });

        this.checkChooserSize();

        return;

    },

    checkChooserSize: function(){

        if (this.borderLine)
            this.borderLine.destroy();

        this.box.removeClass('ka-Textlist-withBorderLine');

        var csize = this.chooser.getSize();
        var bsize = this.box.getSize();

        if (bsize.x < csize.x){

            var diff = csize.x-bsize.x;

            this.borderLine = new Element('div', {
                'class': 'ka-Textlist-borderline',
                styles: {
                    width: diff
                }
            }).inject(this.chooser);

            this.box.addClass('ka-Textlist-withBorderLine');
        } else if (bsize.x - csize.x < 4 && bsize.x - csize.x >= 0){
            this.box.addClass('ka-Textlist-withBorderLine');
        }

    },

    toElement: function () {
        return this.box;
    }

});