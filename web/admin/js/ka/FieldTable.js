ka.FieldTable = new Class({

    Implements: [Options, Events],

    Binds: ['fireChange'],

    options: {
        addLabel: t('Add'),
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

        noActAsTableField: false, //Remove the field 'Acts as a table item'
        asFrameworkFieldDefinition: false, //means for usage in ka.FieldForm (and therefore in adminWindowEdit/Add), delete some relation stuff
        arrayKey: false //allows key like foo[bar], foo[barsen], foo[bar][sen]
    },

    container: false,

    initialize: function(pContainer, pWin, pOptions){

        this.setOptions(pOptions);

        this.container = pContainer;
        this.win = pWin;

        this._createLayout();

    },

    _createLayout: function(){

        this.main = new Element('div').inject(this.container);

        this.header = new Element('table', {
            width: '100%',
            'class': 'ka-Table-head'
        }).inject(this.main);

        this.headerTr = new Element('tr').inject(this.header);
        new Element('th', {text: 'Key'}).inject(this.headerTr);
        if (this.options.asFrameworkColumn || this.options.withWidth){
            this.widthTd = new Element('th', {width: 80, text: 'Width'}).inject(this.headerTr);
        }
        new Element('th', {width: 150, text: 'Type'}).inject(this.headerTr);
        new Element('th', {width: 150, text: 'Properties'}).inject(this.headerTr);
        new Element('th', {width:  80, text: 'Actions'}).inject(this.headerTr);

        this.table = new Element('table', {
            width: '100%',
            'class': 'ka-Table-body'
        }).inject(this.main);

        new ka.Button([this.options.addLabel, '#icon-plus-5'])
            .addEvent('click', function(){
                this.add(null, null, this.itemContainer);
            }.bind(this))
            .inject(this.main);

    },

    toElement: function(){
        return this.main;
    },


    fireChange: function(){
        this.fireEvent('change');
    },

    getValue: function(){

        var result = {};

        this.table.getChildren('tr').each(function(item){

            var fieldProperty = item.retrieve('ka.FieldProperty');
            var value = fieldProperty.getValue();

            result[value.key] = value.definition;

        }.bind(this));

        return result;
    },

    setValue: function(pValue){

        if (typeOf(pValue) == 'object'){
            Object.each(pValue, function(property,key){
                this.add(key, property);
            }.bind(this));

        }
    },

    add: function(pKey, pDefinition){

        var fieldProperty = new ka.FieldProperty(pKey, pDefinition, this.table, this.options, this.win);
        fieldProperty.addEvent('change', this.fireChange);

        this.fireEvent('add', fieldProperty);
        return fieldProperty;

    }

});