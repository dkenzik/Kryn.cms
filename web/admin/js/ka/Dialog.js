ka.Dialog = new Class({

    Binds: ['center', 'close'],
    Implements: [Events, Options],

    options: {
        content: '',
        minWidth: null,
        minHeight: null,
        maxHeight: null,
        maxWidth: null,
        width: null,
        height: null,

        cancelButton: true,
        applyButton: true,
        withButtons: false,
        noBottom: false,

        absolute: false,
        fixed: false,

        autoDisplay: true,

        animatedTransition: Fx.Transitions.Cubic.easeOut,
        animatedTransitionOut: Fx.Transitions.Cubic.easeIn,
        animatedDuration: 200
    },

    canClosed: true,

    initialize: function(pParent, pOptions){
        this.lastFocusedElement = document.activeElement;
        this.container = instanceOf(pParent, ka.Window) ? pParent.toElement() : pParent;

        this.setOptions(pOptions);
        this.renderLayout();

        if (instanceOf(pParent, ka.Window)){
            this.window = pParent;
            this.window.addEvent('resize', this.center);
        } else {
            this.container.getDocument().getWindow().addEvent('resize', this.center);
            if (!this.container.getDocument().hiddenCount) this.container.getDocument().hiddenCount = 0;
            this.container.getDocument().hiddenCount++;
            this.container.getDocument().body.addClass('hide-scrollbar');

            //todo, register `esc` listener for closing.
        }
    },

    renderLayout: function(){
        this.overlay = new Element('div', {
            'class': 'ka-admin ka-dialog-overlay'
        })

        if (this.options.autoDisplay)
            this.overlay.inject(this.container);

        this.overlay.kaDialog = this;

        this.main = new Element('div', {
            'class': 'ka-dialog selectable'
        }).inject(this.overlay);

        this.content = new Element('div', {
            'class': 'ka-dialog-content'
        }).inject(this.main);

        if (typeOf(this.options.content) == 'string'){
            this.content.set('text', this.options.content);
        } else if (typeOf(this.options.content) == 'element'){
            this.options.content.inject(this.content);
        }

        ['minWidth', 'maxWidth', 'minHeight', 'maxHeight', 'height', 'width'].each(function(item){
            if (typeOf(this.options[item]) != 'null')
                this.main.setStyle(item, this.options[item]);
        }.bind(this));

        if (!this.options.noBottom){
            this.bottom = new Element('div', {
                'class': 'ka-dialog-bottom'
            }).inject(this.main);
        }

        if (this.options.fixed){
            this.overlay.addClass('ka-dialog-fixed');
        }

        if (this.options.absolute){
            if (this.bottom) this.bottom.addClass('ka-dialog-bottom-absolute');
            this.content.addClass('ka-dialog-content-absolute');
            if (this.options.noBottom)
                this.content.addClass('ka-dialog-content-no-bottom');
        }

        if (this.options.withButtons && this.bottom){
            if (this.options.cancelButton){
                this.cancelButton = this.addButton(t('Cancel'))
                    .addEvent('click', function(){
                        this.closeAnimated(true);
                    }.bind(this));
            }

            if (this.options.applyButton){
                this.applyButton = this.addButton(t('Apply'))
                    .addEvent('click', function(){
                        this.fireEvent('apply');
                        this.closeAnimated(true);
                    }.bind(this))
                    .setButtonStyle('blue');
                this.applyButton.focus();
            }
        }

        this.center();
    },

    setStyle: function(p1, p2){
        return this.main.setStyle(p1, p2);
    },

    setStyles: function(p1, p2){
        return this.main.setStyles(p1, p2);
    },

    getCancelButton: function(){
        return this.cancelButton;
    },

    getApplyButton: function(){
        return this.applyButton;
    },

    getContentContainer: function(){
        return this.content;
    },


    setContent: function(pHtml){
        this.getContentContainer().set('html', pHtml);
    },

    setText: function(pText){
        this.getContentContainer().set('text', pText);
    },

    addButton: function(pTitle){
        return new ka.Button(pTitle).inject(this.bottom);
    },

    closeAnimated: function(pInternal){
        return this.close(pInternal, true);
    },

    cancelClosing: function(){
        this.cancelNextClosing = true;
    },

    close: function(pInternal, pAnimated){

        if (this.cancelNextClosing){
            delete this.cancelNextClosing;
            return;
        }

        if (pInternal)
            this.main.fireEvent('preClose');

        if (!this.canClosed) return;

        if (pAnimated){
            var dsize = this.main.getSize();

            if (!this.fxOut){
                this.fxOut = new Fx.Morph(this.main, {
                    transition: this.options.animatedTransitionOut,
                    duration: this.options.animatedDuration
                });
            }

            this.fxOut.addEvent('complete', function(){
                this.overlay.destroy();
                if (this.lastFocusedElement) this.lastFocusedElement.focus();
                this.fireEvent('postClose');
            }.bind(this));

            this.fxOut.start({
                top: dsize.y * -1
            });
        } else {
            this.main.dispose();
        }

        if (this.window){
            this.window.removeEvent('resize', this.center);
        } else {
            this.container.getDocument().getWindow().removeEvent('resize', this.center);
        }

        if (pInternal)
            this.fireEvent('close');

        if (!pAnimated){
            this.overlay.destroy();
            if (this.lastFocusedElement) this.lastFocusedElement.focus();
        }

        this.container.getDocument().hiddenCount--;
        if (this.container.getDocument().hiddenCount == 0)
            this.container.getDocument().body.removeClass('hide-scrollbar');
    },

    /**
     * @param {Boolean} pCanClosed
     */
    setCanClosed: function(pCanClosed){
        this.canClosed = pCanClosed;
    },

    getBottomContainer: function(){
        return this.bottom;
    },

    /**
     * Position the dialog to the correct position.
     *
     * @param {Boolean} pAnimated position the dialog out of the viewport and animate it into it.
     */
    center: function(pAnimated){
        if (!this.overlay.getParent()){
            if (this.options.autoDisplay)
                this.overlay.inject(this.container);
        }

        var size = this.container.getSize();
        var dsize = this.main.getSize();

        var left = (size.x.toInt() / 2 - dsize.x.toInt() / 2);
        this.main.setStyle('left', left);

        if (pAnimated){
            this.main.setStyle('top', dsize.y * -1);
            if (!this.fx){
                this.fx = new Fx.Morph(this.main, {
                    transition: this.options.animatedTransition,
                    duration: this.options.animatedDuration
                });
            }
            this.fx.start({
                top: 0
            });
        }
    },

    toElement: function(){
        return this.main;
    }


});