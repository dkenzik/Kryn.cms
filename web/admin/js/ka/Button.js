ka.Button = new Class({

    $eventsBackuper: null,

    /**
     * @constructor
     * @param {String|Array} pTitle A string or a array. With the array you can define a icon: ['title', '#icon-add']
     * @param {String}       pOnClick
     * @param {String}       pTooltip
     */
    initialize: function (pTitle, pOnClick, pTooltip) {
        this.main = new Element('a', {
            'class': 'ka-Button',
            href: 'javascript:;',
            title: (pTooltip) ? pTooltip : null
        });

        this.setText(pTitle);

        if (pOnClick) {
            this.main.addEvent('click', pOnClick);
        }
    },

    setText: function (pText) {

        if (typeOf(pText) == 'element' && pText.inject) {
            pText.inject(this.main);

        } else if (typeOf(pText) == 'array'){
            this.main.set('text', pText[0]);

            if (typeOf(pText[1]) == 'string'){

                if (pText[1].substr(0,1) == '#'){
                    this.main.addClass(pText[1].substr(1));
                } else {
                    new Element('img', {
                        src: ka.mediaPath(pText[1])
                    }).inject(this.main, 'top');
                }
            }

        } else {
            this.main.set('text', pText);
        }
    },

    setButtonStyle: function(pStyle){
        if (this.oldButtonStyle)
            this.main.removeClass('ka-Button-'+oldButtonStyle);

        this.main.addClass('ka-Button-'+pStyle);
        this.oldButtonStyle = pStyle;
        return this;
    },

    setEnabled: function(pEnabled){

        if (this.enabled === pEnabled) return;

        this.enabled = pEnabled;

        if (this.enabled){
            
            //add back all events
            if (this.$eventsBackuper)
                this.main.cloneEvents(this.$eventsBackuper);
            
            this.main.removeClass('ka-Button-deactivate');
            delete this.$eventsBackuper;

        } else {

            this.$eventsBackuper = new Element('span');
            //backup all events and remove'm.
            this.$eventsBackuper.cloneEvents(this.main);
            this.main.removeEvents();

            this.main.addClass('ka-Button-deactivate');
        }

    },

    toElement: function () {
        return this.main;
    },

    inject: function(pTarget, pWhere){
        this.main.inject(pTarget, pWhere);
        return this;
    },

    addEvent: function(pType, pFn){
        (this.$eventsBackuper || this.main).addEvent(pType, pFn);
        return this;
    },

    fireEvent: function(pType, pParams){
        (this.$eventsBackuper || this.main).fireEvent(pType, pParams);
    },

    focus: function () {
        this.main.focus();
    },

    startTip: function (pText) {
        if (!this.toolTip) {
            this.toolTip = new ka.Tooltip(this.main, pText);
        }

        this.toolTip.setText(pText);
        this.toolTip.show();
    },

    /**
     *
     * @param {String} pText
     * @param {Integer} pDelay Default is 300
     */
    startLaggedTip: function(pText, pDelay){

        if (!this.toolTip)
            this.toolTip = new ka.Tooltip(this.main, pText);

        this.toolTip.setText(pText);
        this.laggedTip = (function(){

            this.toolTip.show();

        }).delay(pDelay ? pDelay : 300, this);
    },

    stopTip: function (pText) {
        if (this.laggedTip) clearTimeout(this.laggedTip);

        if (this.toolTip)
            this.toolTip.stop(pText);
    },

    show: function () {
        this.main.setStyle('display', 'inline-block');
    },

    hide: function () {
        this.main.setStyle('display', 'none');
    },

    isHidden: function(){
        return this.main.getStyle('display') == 'none';
    },

    activate: function () {
        this.setEnabled(true);
    },

    deactivate: function () {
        this.setEnabled(false);
    }
});
