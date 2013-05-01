ka.FieldTypes.View = new Class({
    
    Extends: ka.FieldTypes.Select,

    options: {

        inputWidth: '100%',
        module: '',
        directory: ''

    },

    module: '',
    path: '',

    initialize: function(pFieldInstance, pOptions){

        pOptions.object = 'Core\\View';

        if (pOptions.directory == '')
            throw 'Option `directory` is empty in ka.Field `view`.';

        if (pOptions.directory.substr(0,1) == '/')
            pOptions.directory = pOptions.directory.substr(1);

        if (pOptions.directory.substr(pOptions.directory.length-1, 1) != '/')
            pOptions.directory += '/';

        var sIdx    = pOptions.directory.indexOf('/');
        this.module = pOptions.directory.substr(0, sIdx);
        this.path   = pOptions.directory.substr(sIdx+1);

        pOptions.objectBranch = pOptions.directory ? pOptions.directory : true;
        this.parent(pFieldInstance, pOptions);
    },

    getValue: function(){
        var value = this.parent();
        value = value.path;
        return value.substr((this.module+'/'+this.path).length);
    },

    setValue: function(pValue){
        if (typeOf(pValue) == 'string'){
            pValue = (this.module+'/'+this.path)+pValue;
        }
        this.parent({path: pValue});
    }
});