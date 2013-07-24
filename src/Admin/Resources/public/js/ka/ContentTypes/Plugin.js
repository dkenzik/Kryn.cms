ka.ContentTypes = ka.ContentTypes || {};

ka.ContentTypes.Plugin = new Class({

    Extends: ka.ContentAbstract,
    Binds: ['applyValue', 'openDialog'],

    icon: 'icon-cube-2',
    label: 'Plugin',

    options: {

    },

    createLayout: function () {
        this.main = new Element('div', {
            'class': 'ka-normalize ka-content-plugin'
        }).inject(this.contentInstance);

        this.iconDiv = new Element('div', {
            'class': 'ka-content-inner-icon icon-cube-2'
        }).inject(this.main);

        this.inner = new Element('div', {
            'class': 'ka-content-inner ka-normalize'
        }).inject(this.main);

        //this.main.addEvent('click', this.openDialog);
    },

    openDialog: function () {
        this.dialog = new ka.Dialog(ka.wm.lastWindow || this.main.getDocument().body, {
            title: t('Edit plugin'),
            minWidth: '80%',
            minHeight: '80%',
            fixed: !ka.wm.lastWindow,
            absolute: true,
            withButtons: true
        });

        this.dialog.addEvent('apply', function () {
            this.applyValue();
        }.bind(this));

        this.dialogPluginChoser = new ka.Field({
            type: 'plugin'
        }, this.dialog.getContentContainer());

        this.dialogPluginChoser.setValue(this.value);

        this.dialogPluginChoser.addEvent('change', function () {
            this.dialog.center();
        }.bind(this));

        this.dialog.center(true);
    },

    applyValue: function () {
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
    normalizeValue: function (pValue) {
        if (typeOf(pValue) == 'object') {
            var bundle = pValue.bundle || pValue.module || '';

            bundle = bundle.toLowerCase();
            if ('bundle' === bundle.substr(-6)) {
                bundle = bundle.substr(0, bundle.length - 6);
            }

            pValue.bundle = bundle;
            return pValue;
        }

        if (typeOf(pValue) == 'string' && JSON.validate(pValue)) {
            return this.normalizeValue(JSON.decode(pValue));
        }
        if (typeOf(pValue) != 'string') {
            return {};
        }

        var bundle = pValue.substr(0, pValue.indexOf('::'));
        var plugin = pValue.substr(bundle.length + 2, pValue.substr(bundle.length + 2).indexOf('::'));
        var options = pValue.substr(bundle.length + plugin.length + 4);

        options = JSON.validate(options) ? JSON.decode(options) : {};

        return this.normalizeValue({
            bundle: bundle,
            plugin: plugin,
            options: options
        });
    },

    renderValue: function () {
        this.inner.empty();

        var bundle = this.value.bundle;
        var plugin = this.value.plugin;
        var options = this.value.options;

        if (ka.settings.configs[bundle] && ka.settings.configs[bundle].plugins &&
            ka.settings.configs[bundle].plugins[plugin]) {
            var pluginConfig = ka.settings.configs[bundle].plugins[plugin];

            new Element('div', {
                'class': 'ka-content-inner-title',
                text: ka.settings.configs[bundle].label || ka.settings.configs[bundle].name
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
            if (!ka.settings.configs[bundle]) {
                this.inner.set('text', tf('Bundle `%s` not found', bundle));
            } else if (!ka.settings.configs[bundle].plugins || ka.settings.configs[bundle].plugins[plugin]) {
                this.inner.set('text', tf('Plugin `%s` in bundle `%s` not found', plugin, bundle));
            }
        }

    },

    getEditorConfig: function () {

    },

    /**
     * adds/loads all additional fields to the inspector.
     */
    loadInspector: function(container) {
        console.log('loadInspector', this.value);

        var toolbarContainer = new Element('div', {
            'class': 'ka-content-plugin-toolbarContainer'
        }).inject(container);

        this.pluginChoser = new ka.Field({
            type: 'plugin',
            noWrapper: true
        }, toolbarContainer);

        this.pluginChoser.setValue(this.value);

        this.pluginChoser.addEvent('change', function () {
            this.value = this.pluginChoser.getValue();
            this.value = this.normalizeValue(this.value);

            this.renderValue();

            this.contentInstance.fireChange();
        }.bind(this));
    },

    saveInspector: function(value) {
        value.content = this.getValue();
    },

    setValue: function (pValue) {
        if (!pValue) {
            this.value = null;
            return;
        }
        this.value = this.normalizeValue(pValue);
        console.log('plugin setValue', this.value);
        this.renderValue();
    },

    getValue: function () {
        return typeOf(this.value) == 'string' ? this.value : JSON.encode(this.value);
    }

});
