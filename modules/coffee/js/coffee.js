/**
 * @file
 * JavaScript file for the Coffee module.
 */

(function ($, Drupal, drupalSettings, DrupalCoffee) {

  'use strict';

  // Remap the filter functions for autocomplete to recognise the
  // extra value "command".
  var proto = $.ui.autocomplete.prototype;
  var initSource = proto._initSource;

  function filter(array, term) {
    var matcher = new RegExp($.ui.autocomplete.escapeRegex(term), 'i');
    return $.grep(array, function (value) {
      return matcher.test(value.command) || matcher.test(value.label) || matcher.test(value.value);
    });
  }

  $.extend(proto, {
    _initSource: function () {
      if (Array.isArray(this.options.source)) {
        this.source = function (request, response) {
          response(filter(this.options.source, request.term));
        };
      }
      else {
        initSource.call(this);
      }
    }
  });

  /**
   * Coffee module namespace.
   *
   * @namespace
   *
   * @todo put this in Drupal.coffee to expose it.
   */
  DrupalCoffee = DrupalCoffee || {};

  /**
   * Attaches coffee module behaviors.
   *
   * Initializes DOM elements coffee module needs to display the search.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attach coffee functionality to the page.
   *
   * @todo get most of it out of the behavior in dedicated functions.
   */
  Drupal.behaviors.coffee = {
    attach: function (context) {
      const body = once('coffee', 'body', context);
      body.forEach((body) => {
        var $body = $(body);
        DrupalCoffee.bg.appendTo($body).hide();
        DrupalCoffee.wrapper.appendTo('body').addClass('hide-form');
        DrupalCoffee.form
          .append(DrupalCoffee.label)
          .append(DrupalCoffee.field)
          .append(DrupalCoffee.results)
          .wrapInner('<div id="coffee-form-inner" />')
          .appendTo(DrupalCoffee.wrapper);

        DrupalCoffee.dataset = [];
        DrupalCoffee.isItemSelected = false;

        $('.toolbar-icon-coffee').click(function (event) {
          event.preventDefault();
          DrupalCoffee.coffee_show();
        });
        // Key events.
        $(document).keydown(function (event) {

          // Show the form with alt + D. Use 2 keycodes as 'D' can be uppercase or lowercase.
          if (DrupalCoffee.wrapper.hasClass('hide-form') &&
            event.altKey === true &&
              // 68/206 = d/D, 75 = k.
            (event.keyCode === 68 || event.keyCode === 206 || event.keyCode === 75)) {
            DrupalCoffee.coffee_show();
            event.preventDefault();
          }
          // Close the form with esc or alt + D.
          else {
            if (!DrupalCoffee.wrapper.hasClass('hide-form') && (event.keyCode === 27 || (event.altKey === true && (event.keyCode === 68 || event.keyCode === 206)))) {
              DrupalCoffee.coffee_close();
              event.preventDefault();
            }
          }
        });
      });
    }
  };

  /**
   * Initializes the autocomplete widget with data.
   */
  DrupalCoffee.coffee_initialize_search_box = function () {
    // Only do this once per page request to allow for opening and closing the
    // dialog multiple times.
    if (DrupalCoffee.dataset.length !== 0) {
      return;
    }
    var autocomplete_data_element = 'ui-autocomplete';

    var url;
    if (drupalSettings.coffee.dataPath) {
      url = drupalSettings.coffee.dataPath;
    }
    else {
      url = Drupal.url('admin/coffee/get-data');
    }
    $.ajax({
      url: url,
      dataType: 'json',
      success: function (data) {
        DrupalCoffee.dataset = data;

        // Apply autocomplete plugin on show.
        var $autocomplete = $(DrupalCoffee.field).autocomplete({
          source: DrupalCoffee.dataset,
          focus: function (event, ui) {
            // Prevents replacing the value of the input field.
            DrupalCoffee.isItemSelected = true;
            event.preventDefault();
          },
          change: function (event, ui) {
            DrupalCoffee.isItemSelected = false;
          },
          select: function (event, ui) {
            DrupalCoffee.redirect(ui.item.value, event.metaKey || event.ctrlKey);
            event.preventDefault();
            return false;
          },
          delay: 0,
          appendTo: DrupalCoffee.results
        });

        $autocomplete.data(autocomplete_data_element)._renderItem = function (ul, item) {
          // Strip the basePath when displaying the link description.
          var description = item.value;
          if (item.value.indexOf(drupalSettings.path.basePath) === 0) {
            description = item.value.substring(drupalSettings.path.basePath.length);
          }
          return $('<li></li>')
            .data('item.autocomplete', item)
            .append('<a>' + item.label + '<small class="description">' + description + '</small></a>')
            .appendTo(ul);
        };

        // We want to limit the number of results.
        $(DrupalCoffee.field).data(autocomplete_data_element)._renderMenu = function (ul, items) {
          var self = this;
          items = items.slice(0, drupalSettings.coffee.maxResults);
          $.each(items, function (index, item) {
            self._renderItemData(ul, item);
          });
        };

        DrupalCoffee.form.keydown(function (event) {
          if (event.keyCode === 13) {
            var openInNewWindow = false;

            if (event.metaKey || event.ctrlKey) {
              openInNewWindow = true;
            }

            if (!DrupalCoffee.isItemSelected) {
              var $firstItem = $(DrupalCoffee.results).find('li:first').data('item.autocomplete');
              if (typeof $firstItem === 'object') {
                DrupalCoffee.redirect($firstItem.value, openInNewWindow);
                event.preventDefault();
              }
            }
          }
        });
      },
      error: function () {
        DrupalCoffee.field.val('Could not load data, please refresh the page');
      }
    });
  }

  // Prefix the open and close functions to avoid
  // conflicts with autocomplete plugin.
  /**
   * Open the form and focus on the search field.
   */
  DrupalCoffee.coffee_show = function () {
    DrupalCoffee.coffee_initialize_search_box();
    DrupalCoffee.wrapper.removeClass('hide-form');
    DrupalCoffee.bg.show();
    DrupalCoffee.field.focus();
    $(DrupalCoffee.field).autocomplete({enable: true});
  };

  /**
   * Close the form and destroy all data.
   */
  DrupalCoffee.coffee_close = function () {
    DrupalCoffee.field.val('');
    DrupalCoffee.wrapper.addClass('hide-form');
    DrupalCoffee.bg.hide();
    $(DrupalCoffee.field).autocomplete({enable: false});
  };

  /**
   * Close the Coffee form and redirect.
   *
   * @param {string} path
   *   URL to redirect to.
   * @param {bool} openInNewWindow
   *   Indicates if the URL should be open in a new window.
   */
  DrupalCoffee.redirect = function (path, openInNewWindow) {
    DrupalCoffee.coffee_close();

    if (openInNewWindow) {
      window.open(path);
    }
    else {
      document.location = path;
    }
  };

  /**
   * The HTML elements.
   *
   * @todo use Drupal.theme.
   */
  DrupalCoffee.label = $('<label for="coffee-q" class="visually-hidden" />').text(Drupal.t('Query', '', ''));
  DrupalCoffee.results = $('<div id="coffee-results" />');
  DrupalCoffee.wrapper = $('<div class="coffee-form-wrapper" />');
  DrupalCoffee.form = $('<form id="coffee-form" action="#" />');
  DrupalCoffee.bg = $('<div id="coffee-bg" />').click(function () {
    DrupalCoffee.coffee_close();
  });

  DrupalCoffee.field = $('<input id="coffee-q" type="text" autocomplete="off" />');

})(jQuery, Drupal, drupalSettings);
