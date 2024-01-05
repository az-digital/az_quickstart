/**
 * @file
 * az_exposed_filters_links_use_ajax.js
 *
 * Allows to use ajax with AZ Exposed Filters links.
 */

(function ($, once) {

  // This is only needed to provide ajax functionality
  Drupal.behaviors.az_exposed_filters_select_as_links = {
    attach: function (context, settings) {
      $(once('az-exposed-filters-links-use-ajax', '.az-exposed-filters-links.az-exposed-filters-links-use-ajax', context)).each(function () {
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

          if ($(this).hasClass('az-exposed-filters-link--selected')) {
            // The previously selected link is selected again. Deselect it.
            $(this).removeClass('az-exposed-filters-link--selected');
            let all = $links.find('a[name="' + links_name + '[All]"]').addClass('az-exposed-filters-link--selected');
            if (!links_multiple || link_value == 'All') {
              $filters.remove();
            }
            else {
              $filter.remove();
            }
          }
          else {
            if (!links_multiple || link_value == 'All') {
              $links.find('.az-exposed-filters-link--selected').removeClass('az-exposed-filters-link--selected');
            }
            $(this).addClass('az-exposed-filters-link--selected');

            if (!$filter.length) {
              $filter = $('<input type="hidden" name="' + link_name + '" />')
                .prependTo($links);
            }
            $filter.val(link_value);
          }

          // Submit the form.
          $form.find('.form-submit').click();
        });
      });
    }
  };
})(jQuery, once);
