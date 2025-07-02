/**
 * @file
 * Provides admin utilities.
 */

(function ($, _d, Drupal, _doc) {

  'use strict';

  var DESC = 'description';
  var ID_FORM = 'b-form';
  var ID_TOOLTIP = 'b-' + DESC;
  var C_CHECKBOX = 'form-checkbox';
  var C_VANILLA_ON = 'form--vanilla-on';
  var C_VANILLA_OFF = 'form--vanilla-off';
  var S_TOOLTIP = '.' + DESC + ', .form-item__' + DESC;
  var S_CHECKBOX = '.' + C_CHECKBOX;
  var S_FORM = '.form--blazy';
  var S_FORM_ITEM = '.form-item';
  var S_EXPANDABLE = '.js-expandable';
  var C_HINT = 'b-hint';
  var S_HINT = '.' + C_HINT;
  var C_IS_FOCUSED = 'is-focused';
  var C_IS_HOVERED = 'is-hovered';
  var C_IS_SELECTED = 'is-selected';
  var ADDCLASS = 'addClass';
  var REMOVECLASS = 'removeClass';
  var P_CHECKED = 'checked';
  var E_CHANGE = 'change';
  var E_CLICK = 'click';

  /**
   * Blazy admin utility functions.
   *
   * @param {HTMLElement} form
   *   The Blazy form wrapper HTML element.
   */
  function blazyForm(form) {
    var t = $(form);

    function cleanSwitch(el) {
      el.removeClass(function (index, css) {
        return (css.match(/(^|\s)form--media-switch-\S+/g) || []).join(' ');
      });
    }

    $('.details-legend-prefix', t).removeClass('element-invisible');

    t[$(S_CHECKBOX + '--vanilla', t).prop(P_CHECKED) ? ADDCLASS : REMOVECLASS](C_VANILLA_ON);

    t.on(E_CLICK, S_CHECKBOX, function () {
      var $input = $(this);
      var checked = $input.prop(P_CHECKED);

      $input[checked ? ADDCLASS : REMOVECLASS]('on');

      if ($input.hasClass(C_CHECKBOX + '--vanilla')) {
        t[checked ? ADDCLASS : REMOVECLASS](C_VANILLA_ON);
        t[checked ? REMOVECLASS : ADDCLASS](C_VANILLA_OFF);

        if (checked) {
          cleanSwitch(t);
          $('select[name$="[media_switch]"]', t).val('');
        }
      }
    });

    $('select[name$="[style]"]', t).off(E_CHANGE).on(E_CHANGE, function () {
      var $select = $(this);
      var value = $select.val();

      t.removeClass(function (index, css) {
        return (css.match(/(^|\s)form--style-\S+/g) || []).join(' ');
      });

      if (value === '') {
        t.addClass('form--style-off form--style-is-grid');
      }
      else {
        t.addClass('form--style-on form--style-' + value);
        if (['column', 'grid', 'flex', 'nativegrid'].includes(value)) {
          t.addClass('form--style-is-grid');
        }
      }
    }).change();

    $('input[name$="[grid]"]', t).off(E_CHANGE).on(E_CHANGE, function () {
      var $select = $(this);
      var value = $select.val();

      t[value === '' ? REMOVECLASS : ADDCLASS]('form--grid-on');
    }).change();

    t.on(E_CLICK, 'input[name$="[override]"]', function () {
      var $input = $(this);
      var checked = $input.prop(P_CHECKED);

      t[checked ? ADDCLASS : REMOVECLASS]('form--override-on');
    });

    $('select[name$="[responsive_image_style]"]', t).off(E_CHANGE).on(E_CHANGE, function () {
      var $select = $(this);
      t[$select.val() === '' ? REMOVECLASS : ADDCLASS]('form--responsive-image-on');
    }).change();

    $('select[name$="[media_switch]"]', t).off(E_CHANGE).on(E_CHANGE, function () {
      var $select = $(this);
      var value = $select.val();
      var nobox;

      cleanSwitch(t);

      t[value === '' ? REMOVECLASS : ADDCLASS]('form--media-switch-on');
      t[value === '' ? REMOVECLASS : ADDCLASS]('form--media-switch-' + value);

      nobox = ['', 'content', 'link', 'media', 'rendered'].includes(value);
      t[nobox ? REMOVECLASS : ADDCLASS]('form--media-switch-lightbox');
    }).change();

    t.on('mouseenter touchstart', S_HINT, function () {
      $(this).closest(S_FORM_ITEM).addClass(C_IS_HOVERED);
    });

    t.on('mouseleave touchend', S_HINT, function () {
      $(this).closest(S_FORM_ITEM).removeClass(C_IS_HOVERED);
    });

    t.on(E_CLICK, S_HINT, function () {
      $('.form-item.' + C_IS_SELECTED, t).removeClass(C_IS_SELECTED);
      $(this).parent().toggleClass(C_IS_SELECTED);
    });

    t.on(E_CLICK, '.description, .form-item__description', function () {
      $(this).closest('.' + C_IS_SELECTED).removeClass(C_IS_SELECTED);
    });

    t.off('focus').on('focus', S_EXPANDABLE, function () {
      $(this).parent().addClass(C_IS_FOCUSED);
    });

    t.off('blur').on('blur', S_EXPANDABLE, function () {
      $(this).parent().removeClass(C_IS_FOCUSED);
    });
  }

  /**
   * Blazy admin tooltip function.
   *
   * @param {HTMLElement} elm
   *   The Blazy form item description HTML element.
   */
  function blazyTooltip(elm) {
    var $tip = $(elm);

    // Claro removed description for BEM form-item__description.
    if (!$tip.hasClass(DESC)) {
      $tip.addClass(DESC);
    }

    if (!$tip.siblings(S_HINT).length) {
      $tip.closest(S_FORM_ITEM).append('<span class="' + C_HINT + '">?</span>');
    }
  }

  /**
   * Attaches Blazy form behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyAdmin = {
    attach: function (context) {
      _d.once(blazyTooltip, ID_TOOLTIP, S_TOOLTIP, context);
      _d.once(blazyForm, ID_FORM, S_FORM, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        _d.once.removeSafely(ID_TOOLTIP, S_TOOLTIP, context);
        _d.once.removeSafely(ID_FORM, S_FORM, context);
      }
    }
  };

})(jQuery, dBlazy, Drupal, this.document);
