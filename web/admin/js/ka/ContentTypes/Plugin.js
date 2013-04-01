ka.ContentTypes || (ka.ContentTypes = {});

ka.ContentTypes.Plugin = new Class({

    Extends: ka.ContentAbstract,
    Binds: ['applyValue', 'openDialog'],

    icon: '&#xe271;',


    options: {

    },

    createLayout: function(){

        this.main = new Element('div', {
            'class': 'ka-normalize ka-content-plugin'
        }).inject(this.contentInstance);

        this.iconDiv = new Element('div', {
            'class': 'ka-content-inner-icon icon-cube-2'
        }).inject(this.main);

        this.inner = new Element('div', {
            'class': 'ka-content-inner'
        }).inject(this.main);

        this.main.addEvent('click', this.openDialog);

    },

    openDialog: function(){
        this.dialog = new ka.Dialog(this.main.getDocument().body, {
            title: t('Edit plugin'),
            minWidth: '80%',
            minHeight: '80%',
            fixed: true,
            absolute: true,
            withButtons: true
        });

        this.dialog.addEvent('apply', function(){
            this.applyValue();
        }.bind(this));

        this.dialogPluginChoser = new ka.Field({
            type: 'plugin'
        }, this.dialog.getContentContainer());

        this.dialogPluginChoser.setValue(this.value);

        this.dialogPluginChoser.addEvent('change', function(){
            this.dialog.center();
        }.bind(this));

        this.dialog.center(true);
    },

    applyValue: function(){

        this.dialog.close();

        this.value = this.dialogPluginChoser.getValue();
        this.value = this.normalizeValue(this.value);

        this.renderValue();

        this.contentInstance.fireChange();

    },

    /**
     * since old kryn version stores the value as string
     * we need to convert it to the new object def.
     * @param {String|Object} pValue
     * @return {Object}
     */
    normalizeValue: function(pValue){

        if (typeOf(pValue) == 'object') return pValue;

        if (typeOf(pValue) == 'string' && JSON.validate(pValue)){
            return JSON.decode(pValue);
        }
        if (typeOf(pValue) != 'string') return {};

        var module  = pValue.substr(0, pValue.indexOf('::'));
        var plugin  = pValue.substr(module.length+2, pValue.substr(module.length+2).indexOf('::'));
        var options = pValue.substr(module.length+plugin.length+4);

        options = JSON.validate(options) ? JSON.decode(options) : {};

        return {
            module: module,
            plugin: plugin,
            options: options
        };
    },

    renderValue: function(){

        this.inner.empty();

        var module  = this.value.module;
        var plugin  = this.value.plugin;
        var options = this.value.options;

        if (ka.settings.configs[module] && ka.settings.configs[module].plugins && ka.settings.configs[module].plugins[plugin]){
            var pluginConfig = ka.settings.configs[module].plugins[plugin];

            new Element('div', {
                'class': 'ka-content-inner-title',
                text: ka.settings.configs[module].title
            }).inject(this.inner);

            new Element('div', {
                'class': 'ka-content-inner-subtitle',
                text: pluginConfig.label
            }).inject(this.inner);

            /*
            var optionsText = [];
            var text = '';
            var value = '';

            Array.each(pluginConfig[1], function(property, key){

                text  = property.label;
                value = options[key];


                optionsText.push()

            });


            new Element('div', {
                'class': 'ka-content-plugin-options',
                text: optionsText.join(', ')
            }).inject(this.inner);
            */

        } else {
            this.inner.set('text', tf('Plugin or extension not found: %s/%s', module, plugin));
        }

    },

    getEditorConfig: function(){

    },

    setValue: function(pValue){
        if (!pValue) {
            this.value = null;
            return;
        }
        this.value = this.normalizeValue(pValue);
        this.renderValue();
    },

    getValue: function(){
        return typeOf(this.value) == 'string' ? this.value : JSON.encode(this.value);
    }

});

