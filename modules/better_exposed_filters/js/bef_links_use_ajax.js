/**
 * @file
 * bef_links_use_ajax.js
 *
 * Allows to use ajax with Bef links.
 */

(function ($, once) {

  // This is only needed to provide ajax functionality
  Drupal.behaviors.better_exposed_filters_select_as_links = {
    attach: function (context) {
      $(once('bef-links-use-ajax', '.bef-links.bef-links-use-ajax', context)).each(function () {
        let $links = $(this);
        let links_name = $(this).attr('name');
        let links_multiple = $(this).attr('multiple');
        let $form = $(this).closest('form');
        let $filters = $form.find('input[name^="' + links_name + '"]');

        $(this).find('a').click(function (event) {
          // Prevent following the link URL.
          event.preventDefault();

          let link_name = links_multiple ? $(this).attr('name') : links_name;
          let link_value = $(this).attr('name').substring(links_name.length).replace(/^\[|\]$/g, '');
          let $filter = $form.find('input[name="' + link_name + '"]');

          if ($(this).hasClass('bef-link--selected')) {
            // The previously selected link is selected again. Deselect it.
            $(this).removeClass('bef-link--selected');
            if (!links_multiple || link_value === 'All') {
              $filters.remove();
            }
            else {
              $filter.remove();
            }
          }
          else {
            if (!links_multiple || link_value === 'All') {
              $links.find('.bef-link--selected').removeClass('bef-link--selected');
            }
            $(this).addClass('bef-link--selected');

            if (!$filter.length) {
              $filter = $('<input type="hidden" name="' + link_name + '" />')
                .prependTo($links);
            }
            $filter.val(link_value);
          }

          // Submit the form.
          $form.find('.form-submit').not('[data-drupal-selector*=edit-reset]').click();
        });
      });
    }
  };
})(jQuery, once);
