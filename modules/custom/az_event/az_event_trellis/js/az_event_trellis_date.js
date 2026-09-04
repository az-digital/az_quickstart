/**
 * @file
 * Trellis date range picker.
 */

((Drupal, drupalSettings, once) => {
  Drupal.behaviors.trellisDatePicker = {
    attach(context) {
      const elements = once('aztrellisdate', '.az-trellis-daterange', context);
      elements.forEach((element) => {
        const begin = element;
        const id = element.dataset.azTrellisDaterangeEnd;
        const end = document.getElementById(id);
        // eslint-disable-next-line no-unused-vars, no-undef, new-cap
        const picker = new easepick.create({
          element: begin,
          css: drupalSettings.trellisDatePicker.css,
          zIndex: 10,
          RangePlugin: {
            elementEnd: end,
          },
          plugins: ['RangePlugin'],
        });
      });
    },
  };
})(Drupal, drupalSettings, once);
