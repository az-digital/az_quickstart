/**
 * @file
 * Linkit Autocomplete based on jQuery UI.
 */

(function ($, Drupal, once) {

  'use strict';

  var autocomplete;

  /**
   * JQuery UI autocomplete source callback.
   *
   * @param {object} request
   *   The request object.
   * @param {function} response
   *   The function to call with the response.
   */
  function sourceData(request, response) {
    var elementId = this.element.attr('id');

    if (!(elementId in autocomplete.cache)) {
      autocomplete.cache[elementId] = {};
    }

    /**
     * Transforms the data object into an array and update autocomplete results.
     *
     * @param {object} data
     *   The data sent back from the server.
     */
    function sourceCallbackHandler(data) {
      autocomplete.cache[elementId][term] = data.suggestions;
      response(data.suggestions);
    }

    // Get the desired term and construct the autocomplete URL for it.
    var term = request.term;

    // Check if the term is already cached.
    if (autocomplete.cache[elementId].hasOwnProperty(term)) {
      response(autocomplete.cache[elementId][term]);
    }
    else {
      var options = $.extend({
        success: sourceCallbackHandler,
        data: {q: term}
      }, autocomplete.ajax);
      $.ajax(this.element.attr('data-autocomplete-path'), options);
    }
  }

  /**
   * Handles an autocomplete select event.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   * @param {object} ui
   *   The jQuery UI settings object.
   *
   * @return {boolean}
   *   False to prevent further handlers.
   */
  function selectHandler(event, ui) {
    var linkSelector = event.target.getAttribute('data-drupal-selector');
    var $context = $(event.target).closest('form,fieldset,tr');

    // Set hidden inputs for "href_dirty_check" and the "options" field.
    setMetadata(ui.item, $context);

    if (ui.item.label) {
      // Automatically set the link title.
      var $linkTitle = $('*[data-linkit-widget-title-autofill-enabled]', $context);
      if ($linkTitle.length > 0) {
        var titleSelector = $linkTitle.attr('data-drupal-selector');
        if (titleSelector === undefined || linkSelector === undefined) {
          return false;
        }
        if (titleSelector.replace('-title', '') !== linkSelector.replace('-uri', '')) {
          return false;
        }
        if (!$linkTitle.val() || $linkTitle.hasClass('link-widget-title--auto')) {
          // Set value to the label.
          $linkTitle.val($('<span>').html(ui.item.label).text());
          // Flag title as being automatically set.
          $linkTitle.addClass('link-widget-title--auto');
        }
      }
    }

    event.target.value = ui.item.path;

    return false;
  }

  /**
   * Sets hidden inputs for "href_dirty_check" and the "options" field.
   *
   * @param {object} metadata
   *   Values for path and other metadata.
   * @param {jQuery} $context
   *   The element search context.
   */
  function setMetadata(metadata, $context) {
    const { path, entity_type_id, entity_uuid, substitution_id } = metadata;

    if (!path) {
      throw 'Missing path param. ' + JSON.stringify(metadata);
    }

    $('input[name="href_dirty_check"]', $context).val(path);

    if (entity_type_id || entity_uuid || substitution_id) {
      if (!entity_type_id || !entity_uuid || !substitution_id) {
        throw 'Invalid parameter combination; must have all or none of: entity_type_id, entity_uuid, substiution_id. '
          + JSON.stringify(metadata);
      }
    }
    $getAttributesInput('href', $context).val(path);
    $getAttributesInput('data-entity-type', $context).val(entity_type_id);
    $getAttributesInput('data-entity-uuid', $context).val(entity_uuid);
    $getAttributesInput('data-entity-substitution', $context).val(substitution_id);
  }

  /**
   * Helper function for getting one of the "attributes" input elements.
   *
   * @param {string} name
   *   The name of the input within the attributes group.
   * @param {jQuery} $context
   *   The element search context.
   *
   * @returns {jQuery}
   *   The selected element.
   */
  function $getAttributesInput(name, $context) {
    return $(`input[name="attributes[${name}]"], input[name$="[attributes][${name}]"]`, $context);
  }

  /**
   * Override jQuery UI _renderItem function to output HTML by default.
   *
   * @param {object} ul
   *   The <ul> element that the newly created <li> element must be appended to.
   * @param {object} item
   *  The list item to append.
   *
   * @return {object}
   *   jQuery collection of the ul element.
   */
  function renderItem(ul, item) {
    var $line = $('<li>').addClass('linkit-result-line');
    var $wrapper = $('<div>').addClass('linkit-result-line-wrapper');
    $wrapper.addClass(item.status);
    $wrapper.append($('<span>').html(item.label).addClass('linkit-result-line--title'));

    if (item.hasOwnProperty('description')) {
      $wrapper.append($('<span>').html(item.description).addClass('linkit-result-line--description'));
    }
    return $line.append($wrapper).appendTo(ul);
  }

  /**
   * Override jQuery UI _renderMenu function to handle groups.
   *
   * @param {object} ul
   *   An empty <ul> element to use as the widget's menu.
   * @param {array} items
   *   An Array of items that match the user typed term.
   */
  function renderMenu(ul, items) {
    var self = this.element.autocomplete('instance');

    var grouped_items = {};
    items.forEach(function (item) {
      const group = item.hasOwnProperty('group') ? item.group : '';
      if (!grouped_items.hasOwnProperty(group)) {
        grouped_items[group] = [];
      }
      grouped_items[group].push(item);
    })

    $.each(grouped_items, function (group, items) {
      if (group.length) {
        ul.append('<li class="linkit-result-line--group ui-menu-divider">' + group + '</li>');
      }

      $.each(items, function (index, item) {
        if (typeof self._renderItemData === "function") {
          self._renderItemData(ul, item);
        }
      });
    });
  }

  function focusHandler() {
    return false;
  }

  function searchHandler(event) {
    var options = autocomplete.options;

    return !options.isComposing;
  }

  /**
   * Attaches the autocomplete behavior to all required fields.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the autocomplete behaviors.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the autocomplete behaviors.
   */
  Drupal.behaviors.linkit_autocomplete = {
    attach: function (context) {
      // Act on textfields with the "form-linkit-autocomplete" class.
      var $autocomplete = $(once('linkit-autocomplete', 'input.form-linkit-autocomplete', context));
      if ($autocomplete.length) {
        $.widget('ui.autocomplete', $.ui.autocomplete, {
          _create: function () {
            this._super();
            this.widget().menu('option', 'items', '> :not(.linkit-result-line--group)');
          },
          _renderMenu: autocomplete.options.renderMenu,
          _renderItem: autocomplete.options.renderItem
        });

        // Process each item.
        $autocomplete.each(function () {
          var $uri = $(this);

          // In case the user makes an edit and does not click on the
          // autocomplete dropdown (so selectHandler() does not run), add a
          // listener to update the hidden form inputs.
          $uri.focusout(event => {
            const $context = $(event.target).closest('form,fieldset,tr');
            let $href = $getAttributesInput('href', $context),
                 href = new URL($href.val(), document.baseURI),
                  uri = new URL($uri.val(), document.baseURI);
            // If any of the these properties differ between the two URLs, the
            // hidden inputs storing options field data will be cleared.
            // Essentially, we leave out any of the props that contain URL
            // fragment (#) or query string (?). These include hash, href,
            // search, and others.
            const URLpropsToCheck = [
              'auth',
              'host',
              'hostname',
              'pathname',
              'protocol',
              'slashes',
              'port',
            ];
            // If the manually-entered path (uri text input) differs from the
            // "href" hidden input, recalculate all the hidden inputs.
            URLpropsToCheck.some(prop => {
              if (href[prop] !== uri[prop]) {
                setMetadata({ path: $uri.val() }, $context);
                return true;
              }
            });
            // Make sure the "href" metadata hidden input is always up to date,
            // e.g., in case a fragment or query string is added to the uri.
            $href.val($uri.val());
          });

          // Use jQuery UI Autocomplete on the textfield.
          $uri.autocomplete(autocomplete.options);
          $uri.autocomplete('widget').addClass('linkit-ui-autocomplete');

          $uri.click(function () {
            $uri.autocomplete('search', $uri.val());
          });

          $uri.on('compositionstart.autocomplete', function () {
            autocomplete.options.isComposing = true;
          });
          $uri.on('compositionend.autocomplete', function () {
            autocomplete.options.isComposing = false;
          });

          $uri.closest('.form-item').siblings('.form-type-textfield').find('.linkit-widget-title')
            .each(function() {
              // Set automatic title flag if title is the same as uri text.
              var $title  = $(this);
              var uriValue = $uri.val();
              if (uriValue && uriValue === $title.val()) {
                $title.addClass('link-widget-title--auto');
              }
            })
            .change(function () {
              // Remove automatic title flag.
              $(this).removeClass('link-widget-title--auto');
            });
        });
      }
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        once.remove('linkit-autocomplete', 'input.form-linkit-autocomplete', context)
          .forEach((autocomplete) => $(autocomplete).autocomplete('destroy'));
      }
    }
  };

  /**
   * Autocomplete object implementation.
   */
  autocomplete = {
    cache: {},
    options: {
      source: sourceData,
      focus: focusHandler,
      search: searchHandler,
      select: selectHandler,
      renderItem: renderItem,
      renderMenu: renderMenu,
      minLength: 1,
      isComposing: false
    },
    ajax: {
      dataType: 'json'
    }
  };

})(jQuery, Drupal, once);
