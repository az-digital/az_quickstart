/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/
(function ($, Drupal, once) {
  Drupal.behaviors.datetimeTweaksDefaultDate = {
    attach: function attach(context) {
      $(once('azpublicationdate', '.az-publication-date-picker input', context)).each(function () {
        var dateFormat = $(this).data('drupal-date-format').replace('Y', 'yyyy').replace('m', 'mm').replace('d', 'dd');
        var viewmode = $(this).data('az-publication-date-mode');
        var components = dateFormat.split('-');
        var value = $(this).val();
        if (value) {
          value = value.split('-');
          while (value.length < components.length) {
            value.push('01');
          }
          value = value.slice(0, components.length);
          value = value.join('-');
          $(this).val(value);
        }
        $(this).datepicker({
          clear: true,
          autoclose: true,
          format: dateFormat,
          viewMode: viewmode,
          minViewMode: viewmode
        });
      });
    }
  };
})(jQuery, Drupal, once);