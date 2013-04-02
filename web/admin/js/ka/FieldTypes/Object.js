ka.FieldTypes.Object = new Class({
    
    Extends: ka.FieldAbstract,
    options: {
        object: null,
        objects: null,
        withoutObjectWrapper: false,
        combobox: false
    },

    createLayout: function(){

        if (typeOf(this.options.object) == 'string') this.options.objects = [this.options.object];

        if (!this.options.objects || (typeOf(this.options.objects) == 'array' && this.options.objects.length == 0)) {
            //add all objects
            this.options.objects = [];

            Object.each(ka.settings.configs, function(config, key){
                if (config.objects){
                    Object.each(config.objects, function(object, objectKey){
                        this.options.objects.push(key+'\\'+objectKey);
                    }.bind(this));
                }
            }.bind(this));
        };

        var definition = ka.getObjectDefinition(this.options.objects[0]);

        if (!definition){
            this.fieldInstance.fieldPanel.set('text', t('Object not found %s').replace('%s', this.options.objects[0]));
            throw 'Object not found '+this.options.objects[0];
        }

        if (definition.chooserFieldJavascriptClass){

            if (!window[definition.chooserFieldJavascriptClass]){
                throw 'Can no load custom object field class "'+definition.chooserFieldJavascriptClass+'" for object '+this.options.objects[0];
            }

            this.customObj = new window[definition.chooserFieldJavascriptClass](this.field, this.fieldInstance.fieldPanel, this);

            this.customObj.addEvent('change', function () {
                this.fireChange();
            }.bind(this));

            this.setValue = this.customObj.setValue.bind(this.customObj);
            this.getValue = this.customObj.getValue.bind(this.customObj);
            this.isOk = this.customObj.isEmpty.bind(this.customObj);
            this.highlight = this.customObj.highlight.bind(this.customObj);

        } else {

            if (this.options.objectRelation == 'nToM' || this.options.multi == 1){
                this.renderChooserMulti(this.options.objects);
            } else {
                this.renderChooserSingle(this.options.objects);
            }
        }

    },


    renderObjectTableNoItems: function(){

        var tr = new Element('tr').inject(this.chooserTable.tableBody);
        new Element('td', {
            colspan: this.renderChooserColumns.length,
            style: 'text-align: center; color: gray; padding: 5px;',
            text: t('Empty')
        }).inject(tr);
    },

    renderObjectTable: function(){

        this.chooserTable.empty();

        this.objectTableLoaderQueue = {};

        if (!this.objectId || this.objectId.length == 0){
            this.renderObjectTableNoItems();
        } else {
            Array.each(this.objectId, function(id){

                var row = [];

                var placeHolder = new Element('span');
                row.include(placeHolder);

                if (typeOf(id) == 'object')
                    id = ka.getObjectUrlId(this.options.object, id);

                ka.getObjectLabel(ka.getObjectUrl(this.options.object, id), function(label){
                    placeHolder.set('html', label);
                });

                var actionBar = new Element('div');

                var remoteIcon = new Element('a', {
                    'class': 'text-button-icon',
                    href: 'javascript:;',
                    title: t('Remove'),
                    html: '&#xe04a;'
                }).inject(actionBar);

                row.include(actionBar);

                var tr = this.chooserTable.addRow(row);
                remoteIcon.addEvent('click', function(){
                    tr.destroy();
                    this.updateThisValue();
                }.bind(this));

                tr.kaFieldObjectId = id;

            }.bind(this));
        }
    },

    updateThisValue: function(){

        var rows = this.chooserTable.getRows();

        this.objectId = [];
        Array.each(rows, function(row){
            this.objectId.push(row.kaFieldObjectId);
        }.bind(this));

    },

    renderChooserSingle: function(){

        var table = new Element('table', {
            style: 'width: 100%', cellpadding: 0, cellspacing: 0
        }).inject(this.fieldInstance.fieldPanel);

        var tbody = new Element('tbody').inject(table);

        var tr = new Element('tr').inject(tbody);
        var leftTd = new Element('td').inject(tr);
        var rightTd = new Element('td', {width: '50px'}).inject(tr);

        this.field = new ka.Field({
            noWrapper: true
        }, leftTd)

        this.input = this.field.getFieldObject().input;
        this.input.addClass('ka-input-disabled');
        this.input.disabled = true;

        if (this.options.combobox){
            this.input.disabled = false;
            this.input.addEvent('focus', function(){
                this.input.removeClass('ka-input-disabled');
                this._lastValue = this.input.value;

                if (this.objectId){
                    this.lastObjectLabel = this.input.value;
                    this.lastObjectId = this.objectId;
                }
            }.bind(this));

            this.input.addEvent('blur', function(){
                if (this.input.value == this.lastObjectLabel){
                    this.objectId = this.lastObjectId;
                    this.input.addClass('ka-input-disabled');
                    return;
                }

                if (typeOf(this._lastValue) != 'null' && this.input.value != this._lastValue){
                    //changed it, so we delete this.objectValue since its now a custom value
                    delete this.objectId;
                    this.input.removeClass('ka-input-disabled');
                } else if (this.objectId){
                    this.input.addClass('ka-input-disabled');
                }
            }.bind(this));
        }

        if (this.options.inputWidth){
            this.input.setStyle('width', this.options.inputWidth);
        }

        var div = new Element('span').inject(this.fieldInstance.fieldPanel);

        var chooserParams = {
            onSelect: function (pUrl) {
                this.setValue(pUrl, true);
            }.bind(this),
            value: this.objectId,
            cookie: this.options.cookie,
            objects: this.options.objects,
            browserOptions: this.options.browserOptions
        };

        if (this.objectId)
            chooserParams.value = this.objectId;

        if (this.options.cookie)
            chooserParams.cookie = this.options.cookie;

        if (this.options.domain)
            chooserParams.domain = this.options.domain;


        var button = new ka.Button(t('Choose')).addEvent('click', function () {

            if (this.options.designMode) return;
            ka.wm.openWindow('admin/backend/chooser', null, -1, chooserParams);

        }.bind(this))
        .inject(rightTd);

        this.setValue = function (pVal, pIntern) {

            if (typeOf(pVal) == 'null' || pVal === false || pVal === '' || !ka.getCroppedObjectId(pVal)) {
                this.objectId = '';
                this.input.value = '';
                this.input.title = '';
                return;
            }

            pVal = String.from(pVal);

            if ((typeOf(pVal) == 'string' && pVal.substr(0, 'object://'.length) != 'object://')){
                pVal = 'object://'+this.options.objects[0]+'/'+ka.urlEncode(pVal);
            }
            this.objectId = pVal;

            this.showLabel(this.objectId);

            this.input.title = ka.urlDecode(ka.getCroppedObjectId(pVal));

        };

        this.getValue = function(){
            if (!this.objectId) return this.input.value;

            var val = this.objectId;

            if (this.options.withoutObjectWrapper && typeOf(val) == 'string' && val.substr(0, 'object://'.length) == 'object://'){
                return ka.getCroppedObjectId(val);
            }
            return val;
        }

    },

    renderChooserMulti: function(){

        this.renderChooserColumns = [];

        this.objectDefinition = ka.getObjectDefinition(this.options.objects[0]);

        this.renderChooserColumns.include([""]);
        this.renderChooserColumns.include(["", 50]);

        this.chooserTable = new ka.Table(this.renderChooserColumns, {absolute: false, selectable: false});

        this.chooserTable.inject(this.fieldInstance.fieldPanel);
        this.renderObjectTableNoItems();

        //compatibility
        if (this.options.domain){
            if (!this.options.browserOptions) this.options.browserOptions = {};
            if (!this.options.browserOptions.node) this.options.browserOptions.node = {};
            this.options.browserOptions.node.domain = this.options.domain;
        }

        var chooserParams = {
            onSelect: function (pId) {

                if (!this.objectId) this.objectId = [];

                this.objectId.include(ka.getCroppedObjectId(pId));
                this.renderObjectTable();

            }.bind(this),
            value: this.objectId,
            cookie: this.options.cookie,
            objects: this.options.objects,
            browserOptions: this.options.browserOptions
        };

        if (this.objectId)
            chooserParams.value = this.objectId;

        if (this.options.cookie)
            chooserParams.cookie = this.options.cookie;

        if (this.options.domain)
            chooserParams.domain = this.options.domain;

        var button = new ka.Button(t('Add')).addEvent('click', function () {

            if (this.options.designMode) return;
            ka.wm.open('admin/backend/chooser', chooserParams, -1, true);

        }.bind(this));

        button.inject(this.fieldInstance.fieldPanel);

        this.setValue = function(pVal){

            this.objectId = pVal;

            if (!this.objectId) this.objectId = [];

            if (typeOf(this.objectId) != 'array') this.objectId = [this.objectId];

            this.renderObjectTable();

        }.bind(this);

        this.getValue = function(){
            return this.objectId;
        };

    },

    showLabel: function(pObjectUri){

        ka.getObjectLabel(pObjectUri, function(pLabel){
            if (pLabel === false){
                if (!this.options.combobox) {
                    this.input.value = 'not found: '+pObjectUri;
                    this.input.removeClass('ka-input-disabled');
                    delete this.objectId;
                } else {
                    this.input.value = ka.urlDecode(ka.getCroppedObjectId(pObjectUri));
                }
            } else {
                this.input.value = pLabel;
                this.input.addClass('ka-input-disabled');
            }

        }.bind(this));

    }

});