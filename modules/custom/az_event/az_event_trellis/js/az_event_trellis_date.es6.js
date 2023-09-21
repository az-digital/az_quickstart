/**
 * @file
 * Trellis date range picker.
 */

(($, Drupal, once) => {
  Drupal.behaviors.trellisDatePickerr = {
    attach(context) {
      $(once('aztrellisdate', '.az-trellis-daterange', context))
        // eslint-disable-next-line func-names
        .each(function () {
          const begin = this;
          const id = $(this).data('az-trellis-daterange-end');
          const end = $(`#${id}`).get(0);
          // todo figure out how to make CSS declaration unnecessary.
          // eslint-disable-next-line no-unused-vars, no-undef, new-cap
          const picker = new easepick.create({
            element: begin,
            css: [
              'https://cdn.jsdelivr.net/npm/@easepick/bundle@1.2.1/dist/index.css',
            ],
            zIndex: 10,
            RangePlugin: {
              elementEnd: end,
            },
            plugins: ['RangePlugin'],
          });
        });
    },
  };
})(jQuery, Drupal, once);