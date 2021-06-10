/*
	Author	: Michael Janea (www.michaeljanea.com)
	Version	: 1.2
*/

var azicons = '';
var icons = Array();
icons[0] = Array('az-icon-arizona', 'Arizona');
icons[1] = Array('az-icon-award', 'award');
icons[2] = Array('az-icon-cost', 'cost');
icons[3] = Array('az-icon-facebook', 'facebook');
icons[4] = Array('az-icon-financial-aid', 'financial-aid');
icons[5] = Array('az-icon-grad-cap', 'grad-cap');
icons[6] = Array('az-icon-instagram', 'instagram');
icons[7] = Array('az-icon-linkedin', 'linkedin');
icons[8] = Array('az-icon-majors-and-degrees', 'majors-and-degrees');
icons[9] = Array('az-icon-map-marker', 'map-marker');
icons[10] = Array('az-icon-pinterest', 'pinterest');
icons[11] = Array('az-icon-scholarship', 'scholarship');
icons[12] = Array('az-icon-sign-post', 'sign-post');
icons[13] = Array('az-icon-spotify', 'spotify');
icons[14] = Array('az-icon-spring-fling', 'spring-fling');
icons[14] = Array('az-icon-tiktok', 'tiktok');
icons[14] = Array('az-icon-twitter', 'twitter');
icons[14] = Array('az-icon-wildcat', 'wildcat');
icons[14] = Array('az-icon-youtube', 'youtube');



icons.sort();
var azIcons = '';
for (var i = 0; i < icons.length; i++) {
    var newTitle = '';
    var ctr = 0;
    var title = icons[i][1];
    title = title.split(' ');
    for (var x = 0; x < title.length; x++) {
        ctr++;
        newTitle += ctr == 3 ? '<br />' : '';
        newTitle += title[x] + ' ';
        ctr = ctr == 3 ? 0 : ctr;
    }
    azIcons += '<a href="#" onclick="klik(this);return false;" title="' + icons[i][0] + '"><span class="' + icons[i][0] + '">&nbsp;</span><div>' + newTitle + '</div></a>';
};

function klik(el) {
    document.getElementsByClassName('aziconsClass')[0].getElementsByTagName('input')[0].value = el.getAttribute('title');
    a = document.getElementById('azicons');
    a = a.getElementsByTagName('a');
    for (i = 0; i < a.length; i++) {
        a[i].className = '';
    }
    el.className += 'active';
};

function searchIcon(val) {
    var aydi = document.getElementById('azicons');
    var klases = aydi.getElementsByTagName('a');
    for (var i = 0, len = klases.length, klas, klasNeym; i < len; i++) {
        klas = klases[i];
        klasNeym = klas.getAttribute('title');
        if (klasNeym && klasNeym.indexOf(val) >= 0) {
            klas.style.display = 'block';
        } else {
            klas.style.display = 'none';
        }
    }
};

function setSpanColor(color) {
    el = document.getElementById('azicons');
    el = el.getElementsByTagName('span');
    for (i = 0; i < el.length; i++) {
        el[i].setAttribute('style', 'color:' + color)
    }
};

function setCheckboxes() {
    klases = '';
    klases += document.getElementsByClassName('spinning')[0].getElementsByTagName('input')[0].checked ? ' fa-spin' : klases;
    klases += document.getElementsByClassName('fixedWidth')[0].getElementsByTagName('input')[0].checked ? ' fa-fw' : klases;
    klases += document.getElementsByClassName('bordered')[0].getElementsByTagName('input')[0].checked ? ' fa-border' : klases;
    klases += ' ' + document.getElementsByClassName('flippedRotation')[0].getElementsByTagName('select')[0].value;
    el = document.getElementById('azicons');
    el = el.getElementsByTagName('span');
    for (i = 0; i < el.length; i++) {
        el[i].className = el[i].parentNode.getAttribute('title') + klases;
    }
};

function in_array(needle, haystack) {
    for (var i in haystack) {
        if (haystack[i] == needle) return true;
    }
    return false;
};
CKEDITOR.dialog.add('aziconsDialog', function(editor) {
    return {
        title: 'Insert Az Icon',
        minWidth: 600,
        minHeight: 400,
        resizable: false,
        contents: [{
            id: 'insertAzicons',
            label: 'insertAzicons',
            elements: [{
                type: 'hbox',
                widths: ['50%', '50%'],
                children: [{
                    type: 'hbox',
                    widths: ['75%', '25%'],
                    children: [{
                        type: 'text',
                        id: 'colorChooserID',
                        className: 'colorChooser',
                        label: 'Color',
                        onKeyUp: function(e) {
                            setSpanColor(e.sender.$.value);
                        },
                        setup: function(widget) {
                            color = widget.data.color != '' ? widget.data.color : '#000000';
                            this.setValue(color);
                            setSpanColor(color);
                        },
                        commit: function(widget) {
                            widget.setData('color', this.getValue());
                        }
                    }
                    ]
                }, {
                    type: 'text',
                    id: 'size',
                    className: 'size',
                    label: 'Size',
                    setup: function(widget) {
                        this.setValue(widget.data.size);
                    },
                    commit: function(widget) {
                        widget.setData('size', this.getValue());
                    }
                }]
            }, {
                type: 'text',
                id: 'aziconsSearch',
                className: 'aziconsSearch cke_dialog_ui_input_text',
                label: 'Search',
                onKeyUp: function(e) {
                    searchIcon(e.sender.$.value);
                }
            }, {
                type: 'text',
                id: 'aziconsClass',
                className: 'aziconsClass',
                style: 'display:none',
                setup: function(widget) {
                    var klases = '';
                    if (widget.data.class != '') {
                        klases = widget.data.class;
                        klases = klases.split(' ');
                        klases = klases.join(' ');
                    }
                    this.setValue(klases);
                },
                commit: function(widget) {
                    var klases = '';
                    widget.setData('class', this.getValue() + klases);
                }
            }, {
                type: 'html',
                html: '<div id="azicons">' + azIcons + '</div>'
            }]
        }],
        onOk: function() {
            glyphs = document.getElementById('azicons');
            glyphs = glyphs.getElementsByTagName('a');
            for (i = 0; i < glyphs.length; i++) {
                glyphs[i].firstChild.className = glyphs[i].getAttribute('title');
                glyphs[i].className = '';
                glyphs[i].style.display = '';
                glyphs[i].getElementsByTagName('span')[0].style.color = '';
            }
        }
    }
});