ka.FieldTypes.Textarea = new Class({
    
    Extends: ka.FieldTypes.Input,

    options: {
        inputWidth: '100%',
        inputHeight: '50px'
    },

    createLayout: function(){


        this.wrapper = new Element('div', {
            style: this.options.style,
            styles: {
                'width': this.options.inputWidth=='100%'?null:this.options.inputWidth,
                'margin': '2px'
            }
        }).inject(this.fieldInstance.fieldPanel);

        this.innerWrapper = new Element('div', {
            'class': 'ka-Input-inner-wrapper'
        }).inject(this.wrapper);

        this.input = new Element('textarea', {
            'class': 'ka-Input',
            styles: {
                'width': '100%',
                'height': this.options.inputHeight
            }
        }).inject(this.innerWrapper);

        this.input.addEvent('change', this.checkChange);
        this.input.addEvent('keyup', this.checkChange);
    }
});