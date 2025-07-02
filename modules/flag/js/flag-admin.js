(function ($, Drupal) {
  const _this = this;

  Drupal.behaviors.flagsSummary = {
    attach: function attach(context) {
      const $context = $(context);
      $context
        .find('details[data-drupal-selector="edit-flag"]')
        .drupalSetSummary(function (context) {
          const checkedBoxes = $(context).find('input:checkbox:checked');
          if (checkedBoxes.length === 0) {
            return Drupal.t('No flags');
          }
          const getTitle = function getTitle() {
            return _this.title;
          };
          return checkedBoxes.map(getTitle).toArray().join(', ');
        });
    },
  };
})(jQuery, Drupal);
