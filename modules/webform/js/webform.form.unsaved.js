/**
 * @file
 * JavaScript behaviors for unsaved webforms.
 */

(function ($, Drupal, once) {

  'use strict';

  var unsaved = false;

  /**
   * Unsaved changes.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for unsaved changes.
   */
  Drupal.behaviors.webformUnsaved = {
    clear: function () {
      // Allow Ajax refresh/redirect to clear unsaved flag.
      // @see Drupal.AjaxCommands.prototype.webformRefresh
      unsaved = false;
    },
    get: function () {
      // Get the current unsaved flag state.
      return unsaved;
    },
    set: function (value) {
      // Set the current unsaved flag state.
      unsaved = value;
    },
    attach: function (context) {
      // Look for the 'data-webform-unsaved' attribute which indicates that
      // a multi-step webform has unsaved data.
      // @see \Drupal\webform\WebformSubmissionForm::buildForm
      if ($(once('data-webform-unsaved', '.js-webform-unsaved[data-webform-unsaved]')).length) {
        unsaved = true;
      }
      else {
        $(once('webform-unsaved', $('.js-webform-unsaved :input:not(:button, :submit, :reset, [type="hidden"])'))).on('change keypress', function (event, param1) {
          // Ignore events triggered when #states API is changed,
          // which passes 'webform.states' as param1.
          // @see webform.states.js ::triggerEventHandlers().
          if (param1 !== 'webform.states') {
            unsaved = true;
          }
        });
      }

      $(once('webform-unsaved', $('.js-webform-unsaved button, .js-webform-unsaved input[type="submit"]', context))).not('[data-webform-unsaved-ignore]')
        .on('click', function (event) {
          // For reset button we must confirm unsaved changes before the
          // before unload event handler.
          if ($(this).hasClass('webform-button--reset') && unsaved) {
            if (!window.confirm(Drupal.t('Changes you made may not be saved.') + '\n\n' + Drupal.t('Press OK to leave this page or Cancel to stay.'))) {
              return false;
            }
          }

          unsaved = false;
        });

      // Add submit handler to form.beforeSend.
      // Update Drupal.Ajax.prototype.beforeSend only once.
      if (typeof Drupal.Ajax !== 'undefined' && typeof Drupal.Ajax.prototype.beforeSubmitWebformUnsavedOriginal === 'undefined') {
        Drupal.Ajax.prototype.beforeSubmitWebformUnsavedOriginal = Drupal.Ajax.prototype.beforeSubmit;
        Drupal.Ajax.prototype.beforeSubmit = function (form_values, element_settings, options) {
          unsaved = false;
          return this.beforeSubmitWebformUnsavedOriginal.apply(this, arguments);
        };
      }

      // Track all CKEditor change events.
      // @see https://ckeditor.com/old/forums/Support/CKEditor-jQuery-change-event
      if (window.CKEDITOR && !CKEDITOR.webformUnsaved) {
        CKEDITOR.webformUnsaved = true;
        CKEDITOR.on('instanceCreated', function (event) {
          event.editor.on('change', function (evt) {
            unsaved = true;
          });
        });
      }
    }
  };

  $(window).on('beforeunload', function () {
    if (unsaved) {
      return true;
    }
  });

  /**
   * An experimental shim to partially emulate onBeforeUnload on iOS.
   * Part of https://github.com/codedance/jquery.AreYouSure/
   *
   * Copyright (c) 2012-2014, Chris Dance and PaperCut Software http://www.papercut.com/
   * Dual licensed under the MIT or GPL Version 2 licenses.
   * http://jquery.org/license
   *
   * Author:  chris.dance@papercut.com
   * Date:    19th May 2014
   */
  $(function () {
    // @see https://stackoverflow.com/questions/58019463/how-to-detect-device-name-in-safari-on-ios-13-while-it-doesnt-show-the-correct
    var isIOSorOpera = navigator.userAgent.toLowerCase().match(/iphone|ipad|ipod|opera/)
      || navigator.platform.toLowerCase().match(/iphone|ipad|ipod/)
      || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    if (!isIOSorOpera) {
      return;
    }

    $('a:not(.use-ajax)').bind('click', function (evt) {
      var a = $(evt.target).closest('a');
      var href = a.attr('href');
      if (typeof href !== 'undefined' && !(href.match(/^#/) || href.trim() === '')) {
        if ($(window).triggerHandler('beforeunload')) {
          if (!window.confirm(Drupal.t('Changes you made may not be saved.') + '\n\n' + Drupal.t('Press OK to leave this page or Cancel to stay.'))) {
            return false;
          }
        }
        var target = a.attr('target');
        if (target) {
          window.open(href, target);
        }
        else {
          window.location.href = href;
        }
        return false;
      }
    });
  });

})(jQuery, Drupal, once);
