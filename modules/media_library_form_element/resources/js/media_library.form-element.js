/**
 * @file media_library.form-element.js
 */
(($, Drupal, Sortable) => {
  "use strict";

  /**
   * Allow users to edit media library items inside a modal.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to allow editing of a media library item.
   */
  Drupal.behaviors.MediaLibraryFormElementEditItem = {
    attach: function attach(context) {
      const itemSelector = '.media-library-form-element .js-media-library-item a[href]';
      $(once('media-library-edit', itemSelector, context)).each(function (index) {
        var elementSettings = {
          progress: { type: "throbber" },
          dialogType: "modal",
          dialog: { width: "80%" },
          dialogRenderer: null,
          base: $(this).attr("id"),
          element: this,
          url: $(this).attr("href"),
          event: "click"
        };
        Drupal.ajax(elementSettings);
      });
    }
  };

  /**
   * Disable the open button when the user is not allowed to add more items.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to disable the media library open button.
   */
  Drupal.behaviors.MediaLibraryFormElementDisableButton = {
    attach(context) {
      // When the user returns from the modal to the widget, we want to shift
      // the focus back to the open button. If the user is not allowed to add
      // more items, the button needs to be disabled. Since we can't shift the
      // focus to disabled elements, the focus is set back to the open button
      // via JavaScript by adding the 'data-disabled-focus' attribute.

      const itemSelector = '.js-media-library-open-button[data-disabled-focus="true"]';
      $(once('media-library-disable', itemSelector, context)).each(function (index) {
        $(this).focus();
        // There is a small delay between the focus set by the browser and the
        // focus of screen readers. We need to give screen readers time to
        // shift the focus as well before the button is disabled.
        setTimeout(() => {
          $(this).attr("disabled", "disabled");
        }, 50);
      });
    }
  };

  /**
   * Allows selection order to be set without drag+drop for accessibility.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to toggle the weight field for media items.
   */
  Drupal.behaviors.MediaLibraryFormElementToggleWeight = {
    attach(context) {
      const strings = {
        show: Drupal.t("Show media item weights"),
        hide: Drupal.t("Hide media item weights")
      };

      $(once('media-library-toggle', '.js-media-library-widget-toggle-weight', context)).each(function (index) {
        $(this)
          .on('click', e => {
            e.preventDefault();
            $(e.currentTarget)
              .toggleClass('active')
              .text(
                $(e.currentTarget).hasClass('active')
                  ? strings.hide
                  : strings.show
              )
              .closest('.js-media-library-widget')
              .find('.js-media-library-item-weight')
              .parent()
              .toggle();
          })
          .text(strings.show);
      });

      $(once('media-library-toggle', '.js-media-library-item-weight', context)).each(function (index) {
        $(this).parent().hide();
      });
    }
  };

  /**
   * Allows users to re-order their selection with drag+drop.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to re-order selected media items.
   */
  Drupal.behaviors.MediaLibraryFormElementSortable = {
    attach(context) {
      // Allow media items to be re-sorted with drag+drop in the widget.
      const selection = context.querySelectorAll(".js-media-library-selection");
      selection.forEach(widget => {
        Sortable.create(widget, {
          draggable: ".js-media-library-item",
          handle: ".js-media-library-item-preview",
          onEnd: () => {
            $(widget)
              .children()
              .each((index, child) => {
                $(child)
                  .find(".js-media-library-item-weight")
                  .val(index);
              });
          }
        });
      });
    }
  };

  /**
   * Updates the selected media items field with the provided data.
   *
   * @param {string} data
   *  The data to append to the selection.
   * @param {string} element
   *  The element which contains the media items ids.
   */
  $.fn.setMediaUploadFieldValue = function(data, element) {
    const currentValue = $(element).val();
    $(element).val(`${currentValue}${currentValue === "" ? "" : ","}${data}`);
  };
})(jQuery, Drupal, Sortable);
