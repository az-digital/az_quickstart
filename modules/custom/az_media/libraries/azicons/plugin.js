
CKEDITOR.plugins.add('azicons', {
    requires: 'widget',
    icons: 'azicons',
    init: function(editor) {
        editor.widgets.add('AzIcons', {
            button: 'Insert AZ Icons',
            template: '<span class="" style=""></span>',
            dialog: 'aziconsDialog',
            allowedContent: 'span(!az-icon-*){style}',
            upcast: function(element) {
                return element.name == 'span' && element.toString().includes('az-icon');
            },
            init: function() {
                this.setData('class', this.element.getAttribute('class'));
                this.setData('color', this.element.getStyle('color'));
                this.setData('size', this.element.getStyle('font-size'));
            },
            data: function() {
                var istayl = '';
                this.element.setAttribute('class', this.data.class);
                istayl += this.data.color != '' ? 'color:' + this.data.color + ';' : '';
                istayl += this.data.size != '' ? 'font-size:' + parseInt(this.data.size) + 'px;' : '';
                istayl != '' ? this.element.setAttribute('style', istayl) : '';
                istayl == '' ? this.element.removeAttribute('style') : ''
            }
        });
        CKEDITOR.dialog.add('aziconsDialog', this.path + 'dialogs/azicons.js');
        CKEDITOR.dtd.$removeEmpty['span'] = false;

    }
});