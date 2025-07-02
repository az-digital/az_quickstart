/**
 * @file
 * JavaScript behaviors for admin pages.
 */

// eslint-disable-next-line func-names
(function ($, Drupal) {
  Drupal.behaviors.auto_entitylabel_admin = {
    attach(context) {
      let option = $(
        'input[name=node_type_page_status]:checked',
        '#edit-node-type-page-status',
        context,
      ).attr('value');

      this.checkPatternLabel(option);

      $('#edit-node-type-page-status input', context).on('change', () => {
        option = $(
          'input[name=node_type_page_status]:checked',
          '#edit-node-type-page-status',
          context,
        ).attr('value');
        this.checkPatternLabel(option);
      });
    },

    /**
     * Set or unset disabled, read-only attrs on pattern label based on option.
     *
     * @param {string} option
     *   The controlling option value. If option === '0', then the element with
     *   class .pattern-label is given the disabled and readonly attributes;
     *   otherwise, the disabled and readonly attributes are removed.
     */
    checkPatternLabel(option) {
      const patternLabel = $('.pattern-label');
      if (option === '0') {
        patternLabel.attr('disabled', 'disabled');
        patternLabel.attr('readonly', 'readonly');
      } else {
        patternLabel.removeAttr('disabled');
        patternLabel.removeAttr('readonly');
      }
    },
  };
})(jQuery, Drupal);
