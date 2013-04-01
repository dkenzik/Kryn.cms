ka.FieldProperty = new Class({

    Implements: [Events, Options],

    Binds: ['fireChange', 'openProperties', 'applyFieldProperties'],

    kaFields: {
        key: {

            label: t('Key'),
            desc: t('Surround the value with __ and __ to let it only act as UI.'),
            modifier: 'trim',
            required: true

        },

        label: {
            label: t('Label'),
            desc: t('Surround the value with [[ and ]] to make it multilingual.'),
            type: 'text',
            required: true
        },
        'type': {
            label: t('Type'),
            type: 'select',
            items: {
                text: t('Text'),
                password: t('Password'),
                number: t('Number'),

                checkbox: t('Checkbox'),
                imageGroup: t('Imagegroup'),
                checkboxgroup: t('Checkboxgroup'),

                page: t('Page'),
                file: t('File'),
                folder: t('Folder'),
                object: t('Object'),

                select: t('Select'),
                textlist: t('Textlist'),
                lang: t('Language select'),

                predefined: t('Predefined'),

                array: t('Ka.Field Array'),
                properties: t('Properties (multi array class)'),
                fieldtable: t('Ka.Field table'),
                
                fieldCondition: t('Field Condition'),
                objectCondition: t('Object Condition'),

                textarea: t('Textarea'),
                wysiwyg: t('Wysiwyg'),
                layoutElement: t('Layout element'),
                codemirror: t('CodeMirror (sourcecode editor)'),

                date: t('Date'),
                datetime: t('Datetime'),

                files: t('File select from folder'),
                //filelist: t('File list (Attachments)'),

                tab: t('Tab'),
                headline: t('Headline'),
                info: t('Info'),
                label: t('Label'),
                html: t('Html'),
                childrenSwitcher: t('Children switcher'),

                custom: t('Custom')
                //,
                //windowlist: t('Framework windowList')
            },
            children: {

                //datetime, date
                format: {
                    type: 'text',
                    label: t('Date format'),
                    help: 'admin/field-date-format',
                    needValue: ['date', 'datetime'],
                    againstField: 'type',
                    inputWidth: 150
                },

                //array
                withOrder: {
                    type: 'checkbox',
                    label: t('With order possibility'),
                    needValue: 'array',
                    againstField: 'type'
                },

                columns: {
                    label: t('Columns'),
                    needValue: 'array',
                    againstField: 'type',

                    type: 'fieldTable',
                    options: {
                        asFrameworkColumn: true,
                        withoutChildren: true,
                        tableItemLabelWidth: 200,
                        addLabel: t('Add column')
                    }
                },

                //select
                '__info__': {
                    needValue: 'select',
                    type: 'label',
                    label: t('Use a store, a table, SQL or static items.')
                },
                'table': {
                    needValue: 'select',
                    label: t('Table name'),
                    desc: t('Start with / to use a table which is not defined in kryn or is in a different database.'),
                    type: 'text'
                },
                __or__: {
                    needValue: 'select',
                    type: 'label',
                    label: t('- or -')
                },
                'sql': {
                    needValue: 'select',
                    label: t('SQL'),
                    desc: t('Please only select in your SQL the table_key and table_label from below.'),
                    type: 'text'
                },
                table_key: {
                    needValue: 'select',
                    label: t('Table primary column')
                },
                table_label: {
                    needValue: 'select',
                    label: t('Table label column')
                },
                items: {
                    needValue: ['select', 'checkboxGroup', 'imageGroup'],
                    label: t('static items'),
                    desc: t('Use JSON notation. Array(key==label) or Object(key => label). Example: {"item1": "[[Item 1]]"} or ["Foo", "Bar", "Three"].')
                },

                //select,textlist
                'store': {
                    needValue: ['select', 'textlist'],
                    label: t('Store path'),
                    desc: t('&lt;extKey&gt;/&lt;EntryPath&gt;, Example: publication/stores/news.')
                },

                //select, file and folder
                'multi': {
                    needValue: ['select', 'file', 'folder'],
                    label: t('Multiple selection'),
                    desc: t('This field returns then an array.'),
                    'default': 'false',
                    type: 'checkbox'
                },

                //select, file and folder
                'combobox': {
                    needValue: ['select', 'file', 'folder', 'object'],
                    label: t('Combobox'),
                    'default': 'false',
                    desc: t('if you want to allow the user to enter a own value.'),
                    type: 'checkbox'
                },

                //object
                'object': {
                    needValue: ['object', 'predefined', 'fieldCondition', 'objectCondition'],
                    label: t('Objecy key'),
                    required: true,
                    combobox: true,
                    desc: t('The key of the object')
                },
                'field': {
                    needValue: ['predefined', 'fieldCondition'],
                    label: t('Field key'),
                    desc: t('The key of the field')
                },
                'objectLabel': {
                    needValue: 'object',
                    label: t('Object label field (Optional)'),
                    desc: t('The key of the field which should be used as label.')
                },

                'objectRelation': {
                    label: t('Relation'),
                    needValue: 'object',
                    type: 'select',
                    required: true,
                    items: {
                        'nTo1': 'Many to One',
                        'nToM': 'Many to Many'
                    }
                },

                'objectRelationTable': {
                    needValue: 'nToM',
                    againstField: 'objectRelation',
                    label :t('Relation table name (Optional)'),
                    desc: t('The columns of this table are based on the primary keys of left and right table. Propel ORM generates a new model based on this value. Default value is &lt;moduleKey&gt;_&lt;currentObjectKey&gt;_&lt;fieldKey&gt;')
                },
                
                'objectRelationName': {
                    againstField: 'objectRelation',
                    label: t('Relation name (Optional)'),
                    desc: t('Default is the camelCased field name.')
                },

                //tab
                tabFullPage: {
                    label: t('Full page'),
                    type: 'checkbox',
                    needValue: 'tab'
                },

                //textlist
                'doubles': {
                    needValue: 'textlist',
                    label: t('Allow double entries'),
                    type: 'checkbox'
                },

                //files
                'withoutExtension': {
                    needValue: 'files',
                    type: 'checkbox',
                    label: t('File names without extensions'),
                    'default': 1
                },
                
                directory: {
                    needValue: 'files',
                    label: t('List files from this folder'),
                    desc: t('Relative from Kryn.cms installation folder.')
                }

            }
        },
        width: {
            label: t('Width'),
            desc: t('Use a px value or a % value. Example: 25%, 50, 35px')
        },
        primaryKey: {
            needValue: ['text', 'password', 'number', 'checkbox', 'select', 'date', 'object', 'datetime', 'file', 'folder', 'page'],
            againstField: 'type',
            label: t('Primary key'),
            'default': false,
            type: 'checkbox'
        },
        autoIncrement: {
            label: 'Auto increment?',
            desc: t('If no value is assigned the value will be increased by each insertion.'),
            type: 'checkbox',
            'default': false,
            needValue: 'number',
            againstField: 'type'
        },
        __optional__: {
            label: t('Optional'),
            cookieStorage: 'ka.FieldProperty.__optional__',
            type: 'childrenSwitcher',
            children: {
                desc: {
                    label: t('Description'),
                    type: 'text'
                },
                required: {
                    label: t('Required field?'),
                    type: 'checkbox',
                    'default': false
                },
                inputWidth: {
                    label: t('Input element width'),
                    needValue: ['text', 'number', 'password', 'object', 'file', 'folder', 'page', 'domain', 'datetime', 'date'],
                    againstField: 'type',
                    type: 'text'
                },
                inputHeight: {
                    label: t('Input element height'),
                    needValue: ['textarea', 'codemirror'],
                    againstField: 'type',
                    type: 'text'
                },
                maxlength: {
                    label: t('Max length'),
                    needValue: ['text', 'number', 'password'],
                    againstField: 'type',
                    type: 'text'
                },
                target: {
                    label: t('Inject to target'),
                    desc: t('If your tab has a own layout.'),
                    type: 'text'
                },
                'needValue': {
                    label: tc('kaFieldTable', 'Visibility condition'),
                    desc: t("Shows this field only, if the field defined below or the parent field has the defined value. String, JSON notation for arrays and objects, /regex/ or 'javascript:(value=='foo'||value.substr(0,4)=='lala')'")
                },
                againstField: {
                    label: tc('kaFieldTable', 'Visibility condition target field'),
                    desc: t("Define the key of another field if the condition should not against the parent. Use JSON notation for arrays and objects. String or Array")
                },
                'default': {
                    againstField: 'type',
                    type: 'text',
                    label: t('Default value. Use JSON notation for arrays and objects.')
                },
                'requiredRegex': {
                    needValue: ['text','password', 'number', 'checkbox', 'select', 'date', 'datetime', 'file', 'folder'],
                    againstField: 'type',
                    type: 'text',
                    label: t('Required value as regular expression.'),
                    desc: t('Example of an email-check: /^[^@]+@[^@]+/')
                },
                tableItem: {
                    label: t('Acts as a table item'),
                    desc: t('Injects instead of a DIV a TR element.'),
                    type: 'checkbox',
                    'default': false
                },
                noWrapper: {
                    label: t('No wrapper. Removes all around the field itself.'),
                    desc: t('Injects only the pure UI of the defined type.'),
                    type: 'checkbox',
                    'default': false
                }
            }
        }
    },

    options: {
        addLabel: t('Add property'),
        withTableDefinition: false, //shows the 'Is primary key?' and 'Auto increment' fields
        asFrameworkColumn: false, //for column definition, with width field. without the optional stuff and limited range of types
        asFrameworkSearch: false, //Remove some option fields, like 'visibility condition', 'can be empty', etc
        withoutChildren: false, //deactivate children?
        tableItemLabelWidth: 330,
        allTableItems: true,
        withActions: true,

        withWidth: false, //is enabled if asFrameworkColumn is active. otherwise you can enable it here manually.

        fieldTypes: false, //if as array defined, we only have types which are in this list
        fieldTypesBlacklist: false, //if as array defined, we only have types which are not in this list

        keyModifier: '',

        asTableItem: true,

        noActAsTableField: false, //Remove the field 'Acts as a table item'
        asFrameworkFieldDefinition: false, //means for usage in ka.FieldForm (and therefore in adminWindowEdit/Add), delete some relation stuff
        arrayKey: false //allows key like foo[bar], foo[barsen], foo[bar][sen]
    },

    childDiv: false,
    main: false,

    children: [], //instances of ka.FieldProperty

    initialize: function(pKey, pDefinition, pContainer, pOptions, pWin){

        this.setOptions(pOptions);
        this.win = pWin;
        this.key = pKey;
        this.container = pContainer;
        this.definition = pDefinition || {};

        this.prepareFields();

        this._createLayout();
    },

    prepareFields: function(){

        this.kaFields = Object.clone(this.kaFields);

        if (!this.options.withTableDefinition){
            delete this.kaFields.primaryKey;
            delete this.kaFields.autoIncrement;
        } else {
            delete this.kaFields.type.items.label;
            delete this.kaFields.type.items.html;
            delete this.kaFields.type.items.info;
            delete this.kaFields.type.items.headline;
            delete this.kaFields.type.items.tab;
            delete this.kaFields.type.items.predefined;
        }

        if (this.options.noActAsTableField){
            delete this.kaFields.__optional__.children.tableitem;
        }

        if (this.options.asFrameworkFieldDefinition){

            delete this.kaFields.type.children.object_label;
            delete this.kaFields.type.children.object_label_map;
            delete this.kaFields.type.children.object_relation;
            delete this.kaFields.type.children.object_relation_table;
            delete this.kaFields.type.children.object_relation_table_left;
            delete this.kaFields.type.children.object_relation_table_right;

        } else {
            //if not frameworkField
            delete this.kaFields.__optional__.children.target;
            if (this.kaFields.__optional__.children.tableitem)
                delete this.kaFields.__optional__.children.tableitem;

        }

        if (this.options.asFrameworkSearch){
            delete this.kaFields.__optional__.children.empty;
            delete this.kaFields.__optional__.children.target;
            delete this.kaFields.__optional__.children.needValue;
            delete this.kaFields.__optional__.children.againstField;
            delete this.kaFields.__optional__.children.required_regexp;

            if(this.kaFields.__optional__.children.tableitem)
                delete this.kaFields.__optional__.children.tableitem;

            delete this.kaFields.type.items.window_list;
            delete this.kaFields.type.items.childrenSwitcher;
            delete this.kaFields.type.items.layoutelement;
            delete this.kaFields.type.items.wysiwyg;
            delete this.kaFields.type.items.array;
            delete this.kaFields.type.items.tab;
        }

        if (this.options.asFrameworkColumn){
            delete this.kaFields.__optional__;
            this.kaFields.type.label = t('Display type');
            this.kaFields.type.items = {
                text: t('Text'),
                number: t('Number'),
                bool: t('Boolean'),
                lang: t('Language select'),
                datetime: t('Datetime'),
                imagemap: t('Imagemap'),
                predefined: t('Predefined')
            };

            this.kaFields.type.children.imageMap = {
                label: t('Map'),
                desc: t('To use Regex surround the value with /.'),
                type: 'array',
                needValue: 'imagemap',
                columns: [
                    {label: t('Value'), width: '50%'},
                    {label: t('Image path')}
                ],
                fields: {
                    value: {
                        type: 'text'
                    },
                    imagePath: {
                        type: 'file'
                    }
                }

            };
        } else if (!this.options.withWidth){
            delete this.kaFields.width;
        }



        if (typeOf(this.options.fieldTypes) == 'array'){
            Object.each(this.kaFields.type.items, function(def, key){
                if (!this.options.fieldTypes.contains(key))
                    delete this.kaFields.type.items[key];
            }.bind(this));
        }

        if (typeOf(this.options.fieldTypesBlacklist) == 'array'){
            Array.each(this.options.fieldTypesBlacklist, function(key){
                delete this.kaFields.type.items[key];
            }.bind(this));
        }

        if (this.kaFields.type.items.object){
            this.kaFields.type.children.object.type = 'select';
            this.kaFields.type.children.object.items = {};

            Object.each(ka.settings.configs, function(config, extensionKey){
                if (config.objects){
                    extensionKey = extensionKey.charAt(0).toUpperCase()+extensionKey.substr(1);
                    Object.each(config.objects, function(object,object_key){
                        object_key = object_key.charAt(0).toUpperCase()+object_key.substr(1);
                        if ((this.options.asFrameworkFieldDefinition && object.selectable) || !this.options.asFrameworkFieldDefinition)
                            this.kaFields.type.children.object.items[extensionKey+'\\'+object_key] = object.label+" ("+extensionKey+'\\'+object_key+")";
                    }.bind(this));
                }
            }.bind(this));
        }
    },

    _createLayout: function(){

        var count = this.container.getElements('.ka-fieldProperty-item').length+1;

        if (this.options.asTableItem){

            this.main = new Element('tr', {
                'class': 'ka-fieldProperty-item'
            }).inject(this.container);

            this.main.store('ka.FieldProperty', this);

            this.tdLabel = new Element('td').inject(this.main);

            this.iKey = new ka.Field({
                type: 'text',
                modifier: this.options.keyModifier,
                noWrapper: true
            }, this.tdLabel);

            delete this.kaFields.key;

            this.iKey.setValue(this.key?this.key:'property_'+count);

            if (this.options.asFrameworkColumn || this.options.withWidth){
                this.tdWidth = new Element('td', {width: 80}).inject(this.main);

                var width = Object.clone(this.kaFields.width);
                width.noWrapper = true;
                this.widthField = new ka.Field(width, this.tdWidth);

                this.widthField.setValue(this.definition && this.definition.width?this.definition.width:'');

            }

            this.tdType = new Element('td', {width: 150}).inject(this.main);

            var field = Object.clone(this.kaFields.type);
            delete field.children;

            field.noWrapper = true;
            this.typeField = new ka.Field(field, this.tdType);

            this.typeField.setValue(this.definition && this.definition.type?this.definition.type:'text');

            this.tdProperties = new Element('td', {width: 150}).inject(this.main);

            this.propertiesButton = new ka.Button(t('Properties'))
            .addEvent('click', this.openProperties)
            .inject(this.tdProperties);

            this.actionContainer = new Element('td', {
                width: 80
            }).inject(this.main);

        } else {
            //non tr/td

            this.main = new Element('div', {
                'class': 'ka-fieldProperty-item'
            }).inject(this.container);

            this.main.store('ka.FieldProperty', this);

            this.fieldObject = new ka.FieldForm(this.main, this.kaFields, {
                allTableItems: this.options.allTableItems,
                tableItemLabelWidth: this.options.tableItemLabelWidth,
                withEmptyFields: false
            }, {win:this.win});

            this.fieldObject.setValue(this.definition);

            this.fieldObject.addEvent('change', this.fireChange);


        }

        if (!this.options.withoutChildren){

            new Element('a', {
                style: "cursor: pointer; font-family: 'icomoon'; padding: 0px 5px;",
                title: _('Add children'),
                html: '&#xe109;'
            })
            .addEvent('click', this.addChild.bind(this, '', {}))
            .inject(this.actionContainer);
        }

        if (this.options.withActions){

            new Element('a', {
                style: "cursor: pointer; font-family: 'icomoon'; padding: 0px 5px;",
                title: _('Remove'),
                html: '&#xe26b;'
            })
            .addEvent('click', function(){
                this.win._confirm(t('Really delete?'), function(ok){
                    if(ok){
                        this.fireEvent('delete');
                        this.removeEvents('change');
                        this.main.destroy();
                        if (this.childContainer) this.childContainer.destroy();
                    }
                }.bind(this));
            }.bind(this))
            .inject(this.actionContainer);

            new Element('a', {
                style: "cursor: pointer; font-family: 'icomoon'; padding: 0px 2px;",
                title: t('Move up'),
                html: '&#xe2ca;'
            })
            .addEvent('click', function(){

                var previous = this.main.getPrevious('.ka-fieldProperty-item');
                if (!previous) return;
                this.main.inject(previous, 'before');

                if (this.childContainer) this.childContainer.inject(this.main, 'after');

            }.bind(this))
            .inject(this.actionContainer);


            new Element('a', {
                style: "cursor: pointer; font-family: 'icomoon'; padding: 0px 2px;",
                title: t('Move down'),
                html: '&#xe2cc;'
            })
            .addEvent('click', function(){

                var next = this.main.getNext('.ka-fieldProperty-item');
                if (!next) return;
                this.main.inject(next.childContainer || next, 'after');

                if (this.childContainer) this.childContainer.inject(this.main, 'after');

            }.bind(this))
            .inject(this.actionContainer);

        }

    },

    openProperties: function(){

        this.dialog = new ka.Dialog(this.win, {
            absolute: true,
            minWidth: '90%',
            minHeight: '90%',
        });

        /*if (!this.options.withTableDefinition) {
            var headerInfo = new Element('div', {
                text: t('Surround the key above with __ and __ (double underscore) to define a field which acts only as a user interface item and does not appear in the result.'),
                style: 'color: gray',
                'class': 'ka-fieldTable-key-info'
            }).inject(this.header);
        }*/

        var main = new Element('div', {'class': 'ka-fieldTable-definition'}).inject(this.dialog.content);

        var fieldContainer;

        if (this.options.allTableItems){
            var table = new Element('table', {
                width: '100%'
            }).inject(main);

           fieldContainer = new Element('tbody').inject(table);
        } else {
            fieldContainer = main;
        }

        this.saveBtn = new ka.Button(t('Apply'));

        this.fieldObject = new ka.FieldForm(fieldContainer, this.kaFields, {
            allTableItems: this.options.allTableItems,
            tableItemLabelWidth: this.options.tableItemLabelWidth,
            saveButton: this.saveBtn,
            withEmptyFields: false
        }, {win:this.win});

        this.fieldObject.setValue(this.definition);

        this.fieldObject.getField('type').setValue(this.typeField.getValue(), true);

        if (this.options.asFrameworkColumn || this.options.withWidth){
            this.fieldObject.getField('width').setValue(this.widthField.getValue(), true);
        }

        new ka.Button(t('Cancel'))
        .addEvent('click', function(){ this.dialog.closeAnimated(); }.bind(this))
        .inject(this.dialog.bottom);

        this.saveBtn.addEvent('click', function(){
            if (!this.fieldObject.checkValid()){
                return;
            }

            this.definition = this.fieldObject.getValue();
            this.typeField.setValue(this.definition.type);
            if (this.options.asFrameworkColumn || this.options.withWidth)
                this.widthField.setValue(this.definition.width);

            this.dialog.close();

        }.bind(this))
        .setButtonStyle('blue')
        .inject(this.dialog.bottom);

        this.dialog.center(true);
        return;
    },

    fireChange: function(){
        this.fireEvent('change');
    },

    addChild: function(pKey, pDefinition){

        if (!this.childContainer){

            this.childContainer = new Element('tr').inject(this.main, 'after');
            this.main.childContainer = this.childContainer;

            this.childTd = new Element('td', {
                colspan: this.main.getChildren().length
            }).inject(this.childContainer);

            this.childDiv = new Element('div', {
                style: 'margin-left: 25px'
            }).inject(this.childTd);

            this.childContainer = new Element('table', {
                width: '100%'
            }).inject(this.childDiv);

        }

        new ka.FieldProperty(pKey, pDefinition, this.childContainer, this.options, this.win);

    },

    getValue: function(){

        var key;

        if (this.options.asTableItem){
            key = this.iKey.getValue();
            var type = this.typeField.getValue();

            if (!key) return;

            this.definition.type = type;
        } else {
            this.definition = this.fieldObject.getValue();
            key = this.definition.key;
        }

        var property = this.definition;

        Object.each(property, function(pval, pkey){

            if(typeOf(pval) != 'string') return;

            var newItem = false;

            try {

                //check if json array
                if (pval.substr(0,1) == '[' && pval.substr(pval.length-1) == ']'&&
                    pval.substr(0,2) != '[[' && pval.substr(pval.length-2) != ']]')
                    newItem = JSON.decode(pval);

                //check if json object
                if (pval.substr(0,1) == '{' && pval.substr(pval.length-1,1) == '}')
                    newItem = JSON.decode(pval);

            } catch(e){}

            if (newItem)
                property[pkey] = newItem;

        }.bind(this));

        if (!this.options.withoutChildren && this.childContainer){
            property.children = {};

            this.childContainer.getChildren('tr').each(function(child){
                var fieldProperty = child.retrieve('ka.FieldProperty');
                var value = fieldProperty.getValue();
                property.children[value.key] = value.definition;
            });

            if (Object.getLength(property.children) === 0)
                delete property.children;
        }

        return {
            key: key,
            definition: property
        };
    },


    setValue: function(pKey, pDefinition){

        if(pDefinition.type == 'select' && pDefinition.tableItems){
            if (typeOf(pDefinition.tableItems) == 'object')
                pDefinition.items = Object.clone(pDefinition.tableItems);

            if (typeOf(pDefinition.tableItems) == 'array')
                pDefinition.items = Array.clone(pDefinition.tableItems);

            delete pDefinition.tableItems;
        }

        if (typeOf(pDefinition.items) == 'array'){
            var first = pDefinition.items[0];
            if (typeOf(first) == 'object'){
                var newItems = {};
                Array.each(pDefinition.items, function(item){
                    newItems[ item[pDefinition.table_key] ] = item[pDefinition.table_label];
                });
                pDefinition.items = newItems;
            }
        }

        if (this.options.asTableItem){
            this.iKey.setValue(pKey);
            this.typeField.setValue(pDefinition.type);
            this.definition = pDefinition;

            if (this.options.asFrameworkColumn || this.options.withWidth)
                this.widthField.setValue(pDefinition.width);

        } else {
            this.fieldObject.setValue(pDefinition);
        }

        delete this.children;

        this.children = [];
        if (this.childDiv)
            this.childDiv.empty();

        if (!this.options.withoutChildren){
            if (pDefinition.children){
                Object.each(pDefinition.children, function(definition, key){

                    this.addChild(key, definition);

                }.bind(this));
            }
        }

        this.fireEvent('set');
    }

});