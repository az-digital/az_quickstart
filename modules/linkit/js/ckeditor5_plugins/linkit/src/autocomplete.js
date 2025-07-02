const $ = jQuery;


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
  });

  $.each(grouped_items, function (group, items) {
    if (group.length) {
      ul.append('<li class="linkit-result-line--group ui-menu-divider">' + group + '</li>');
    }

    $.each(items, function (index, item) {
      self._renderItemData(ul, item);
    });
  });
}

export default function initializeAutocomplete(element, settings) {
  const { autocompleteUrl, selectHandler, closeHandler, openHandler } = settings;
  const autocomplete = {
    cache: {},
    ajax: {
      dataType: 'json',
      jsonp: false,
    },
  };

  /**
   * JQuery UI autocomplete source callback.
   *
   * @param {object} request
   *   The request object.
   * @param {function} response
   *   The function to call with the response.
   */
  function sourceData(request, response) {
    const { cache } = autocomplete;
    /**
     * Transforms the data object into an array and update autocomplete results.
     *
     * @param {object} data
     *   The data sent back from the server.
     */
    function sourceCallbackHandler(data) {
      cache[term] = data.suggestions;
      response(data.suggestions);
    }

    // Get the desired term and construct the autocomplete URL for it.
    var term = request.term;

    // Check if the term is already cached.
    if (cache.hasOwnProperty(term)) {
      response(cache[term]);
    }
    else {
      $.ajax(autocompleteUrl, {
        success: sourceCallbackHandler,
        data: {q: term},
        ...autocomplete.ajax,
      });
    }
  }

  const options = {
    appendTo: element.closest('.ck-labeled-field-view'),
    source: sourceData,
    select: selectHandler,
    focus: () => false,
    search: () => !options.isComposing,
    close: closeHandler,
    open: openHandler,
    minLength: 1,
    isComposing: false,
  }
  const $auto = $(element).autocomplete(options);

  // Override a few things.
  const instance = $auto.data('ui-autocomplete');
  instance.widget().menu('option', 'items', '> :not(.linkit-result-line--group)');
  instance._renderMenu = renderMenu;
  instance._renderItem = renderItem;


  $auto.autocomplete('widget').addClass('linkit-ui-autocomplete ck-reset_all-excluded');

  $auto.on('click', function () {
    $auto.autocomplete('search', $auto.val());
  });

  $auto.on('compositionstart.autocomplete', function () {
    options.isComposing = true;
  });
  $auto.on('compositionend.autocomplete', function () {
    options.isComposing = false;
  });

  return $auto;
}
