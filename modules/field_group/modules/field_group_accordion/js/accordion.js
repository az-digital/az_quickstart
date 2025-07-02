/**
 * @file
 * Provides the processing logic for accordion.
 */

(($) => {
  Drupal.FieldGroup = Drupal.FieldGroup || {};
  Drupal.FieldGroup.Effects = Drupal.FieldGroup.Effects || {};

  /**
   * Implements Drupal.FieldGroup.processHook().
   */
  Drupal.FieldGroup.Effects.processAccordion = {
    execute(context, settings, groupInfo) {
      $(
        once(
          'fieldgroup-effects',
          'div.field-group-accordion-wrapper',
          context,
        ),
      ).each((index, elementWrapper) => {
        const wrapper = $(elementWrapper);

        // Get the index to set active.
        let activeIndex = false;
        wrapper.find('.accordion-item').each((i) => {
          if ($(this).hasClass('field-group-accordion-active')) {
            activeIndex = i;
          }
        });

        wrapper.accordion({
          heightStyle: 'content',
          active: activeIndex,
          collapsible: true,
          // cspell:ignore changestart
          changestart(event, ui) {
            if ($(this).hasClass('effect-none')) {
              ui.options.animated = false;
            } else {
              ui.options.animated = 'slide';
            }
          },
        });

        if (groupInfo.context === 'form') {
          let $firstErrorItem = false;

          // Add required fields mark to any element containing required fields.
          wrapper.find('div.field-group-accordion-item').each((i, element) => {
            const $this = $(element);

            if (
              element.matches('.required-fields') &&
              ($this.find('[required]').length > 0 ||
                $this.find('.form-required').length > 0)
            ) {
              $('h3.ui-accordion-header a').eq(i).addClass('form-required');
            }
            if ($('.error', $this).length) {
              // Save first error item, for focussing it.
              if (!$firstErrorItem) {
                $firstErrorItem = $this
                  .parent()
                  .accordion('option', 'active', i);
              }
              $('h3.ui-accordion-header').eq(i).addClass('error');
            }
          });

          // Save first error item, for focussing it.
          if (!$firstErrorItem) {
            // eslint-disable-next-line jquery/no-css
            $('.ui-accordion-content-active', $firstErrorItem).css({
              height: 'auto',
              width: 'auto',
              display: 'block',
            });
          }
        }
      });
    },
  };
})(jQuery);
