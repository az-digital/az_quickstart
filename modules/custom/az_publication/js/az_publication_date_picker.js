/**
 * @file
 * Default date values.
 */

(($, Drupal, once) => {
  Drupal.behaviors.datetimeTweaksDefaultDate = {
    attach(context) {
      $(once('azpublicationdate', '.az-publication-date-picker input', context))
        // eslint-disable-next-line func-names
        .each(function () {
          // Fetch field date settings.
          const dateFormat = $(this)
            .data('drupal-date-format')
            .replace('Y', 'yyyy')
            .replace('m', 'mm')
            .replace('d', 'dd');
          // Get datepicker settings.
          const viewmode = $(this).data('az-publication-date-mode');
          const components = dateFormat.split('-');
          // Modify form value to correct number of components.
          let value = $(this).val();
          if (value) {
            value = value.split('-');
            while (value.length < components.length) {
              value.push('01');
            }
            value = value.slice(0, components.length);
            value = value.join('-');
            $(this).val(value);
          }
          // Initialize datepicker.
          $(this).datepicker({
            clear: true,
            autoclose: true,
            format: dateFormat,
            viewMode: viewmode,
            minViewMode: viewmode,
          });
        });
    },
  };
})(jQuery, Drupal, once);
