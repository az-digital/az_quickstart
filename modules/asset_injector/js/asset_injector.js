/**
 * Conditional plugins summary helpers.
 */
(function ($, window, Drupal) {
  Drupal.behaviors.assetInjectorSettingsSummary = {
    attach() {
      if (typeof $.fn.drupalSetSummary === 'undefined') {
        return;
      }

      function selectSummary(context) {
        const $select = $(context).find('select');
        return $select.find(`option[value='${$select[0].value}']`).html();
      }

      function checkboxesSummary(context) {
        const checkedValues = [];
        const $checkboxes = $(context).find(
          'input[type="checkbox"]:checked + label',
        );
        const il = $checkboxes.length;
        for (let i = 0; i < il; i++) {
          checkedValues.push($($checkboxes[i]).html());
        }
        if (!checkedValues.length) {
          checkedValues.push(Drupal.t('Not restricted'));
        }
        return checkedValues.join(', ');
      }

      $(
        '[data-drupal-selector="edit-conditions-node-type"], [data-drupal-selector="edit-conditions-language"], [data-drupal-selector="edit-conditions-user-role"]',
      ).drupalSetSummary(checkboxesSummary);
      $(
        '[data-drupal-selector="edit-conditions-current-theme"]',
      ).drupalSetSummary(selectSummary);

      $('[data-drupal-selector="edit-conditions-and-or"]').drupalSetSummary(
        function (context) {
          const requireAll = $(context).find('input[type="checkbox"]:checked ');

          if (requireAll.length) {
            return Drupal.t('Require ALL conditions');
          }
          return Drupal.t('Require any condition');
        },
      );

      $(
        '[data-drupal-selector="edit-conditions-request-path"]',
      ).drupalSetSummary(function (context) {
        const $pages = $(context).find(
          'textarea[name="conditions[request_path][pages]"]',
        );

        if (!$pages[0].value) {
          return Drupal.t('Not restricted');
        }

        return Drupal.t('Restricted to certain pages');
      });
    },
  };
})(jQuery, window, Drupal);
