/**
 * @file
 * Provides the processing logic for tabs.
 */

(($) => {
  Drupal.FieldGroup = Drupal.FieldGroup || {};
  Drupal.FieldGroup.Effects = Drupal.FieldGroup.Effects || {};

  /**
   * Implements Drupal.FieldGroup.processHook().
   */
  Drupal.FieldGroup.Effects.processTabs = {
    execute(context, settings, groupInfo) {
      if (groupInfo.context === 'form') {
        // Add required fields mark to any element containing required fields.
        const { direction } = groupInfo.settings;
        $(context)
          .find(`[data-${direction}-tabs-panes]`)
          .each((indexTabs, tabs) => {
            let errorFocussed = false;
            $(once('fieldgroup-effects', $(tabs).find('> details'))).each(
              (index, element) => {
                const $this = $(element);
                if (typeof $this.data(`${direction}Tab`) !== 'undefined') {
                  if (
                    element.matches('.required-fields') &&
                    ($this.find('[required]').length > 0 ||
                      $this.find('.form-required').length > 0)
                  ) {
                    $this
                      .data(`${direction}Tab`)
                      .link.find('strong:first')
                      .addClass('form-required');
                  }

                  if ($('.error', $this).length) {
                    $this
                      .data(`${direction}Tab`)
                      .link.parent()
                      .addClass('error');

                    // Focus the first tab with error.
                    if (!errorFocussed) {
                      Drupal.FieldGroup.setGroupWithFocus($this);
                      $this.data(`${direction}Tab`).focus();
                      errorFocussed = true;
                    }
                  }
                }
              },
            );
          });
      }
    },
  };
})(jQuery);
