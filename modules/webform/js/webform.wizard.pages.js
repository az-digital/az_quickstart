/**
 * @file
 * JavaScript behaviors for webform wizard pages.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Link the wizard's previous pages.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Links the wizard's previous pages.
   */
  Drupal.behaviors.webformWizardPagesLink = {
    attach: function (context) {
      $(once('webform-wizard-pages-links', '.js-webform-wizard-pages-links', context)).each(function () {
        var $pages = $(this);
        var $form = $pages.closest('form');

        var hasProgressLink = $pages.data('wizard-progress-link');
        var hasPreviewLink = $pages.data('wizard-preview-link');

        $pages.find('.js-webform-wizard-pages-link').each(function () {
          var $button = $(this);
          var title = $button.attr('title');
          var page = $button.data('webform-page');

          // Link progress marker and title.
          if (hasProgressLink) {
            var $progress = $form.find('.webform-progress [data-webform-page="' + page + '"]');
            $progress.find('.progress-marker, .progress-title, .webform-progress-bar__page-title')
              .attr({
                'role': 'link',
                'title': title,
                'aria-label': title,
                'tabindex': '0'
              })
              .on('click', function () {
                $button.trigger('click');
              })
              .on('keydown', function (event) {
                if (event.which === 13) {
                  $button.trigger('click');
                }
              });
            // Only allow the marker to be tabbable.
            $progress.find('.progress-marker, .webform-progress-bar__page-title').attr('tabindex', 0);
          }

          // Move button to preview page div container with [data-webform-page].
          // @see \Drupal\webform\Plugin\WebformElement\WebformWizardPage::formatHtmlItem
          if (hasPreviewLink) {
            $form
              .find('.webform-preview [data-webform-page="' + page + '"]')
              .append($button)
              .show();
          }
        });
      });
    }
  };

})(jQuery, Drupal, once);
