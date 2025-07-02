/**
 * @file
 * Provides the processing logic for html elements.
 */

(($) => {
  Drupal.FieldGroup = Drupal.FieldGroup || {};
  Drupal.FieldGroup.Effects = Drupal.FieldGroup.Effects || {};

  /**
   * Implements Drupal.FieldGroup.processHook().
   */
  Drupal.FieldGroup.Effects.processHtml_element = {
    execute(context, settings, groupInfo) {
      $(once('fieldgroup-effects', '.field-group-html-element', context)).each(
        (index, element) => {
          const $wrapper = $(element);

          if ($wrapper.hasClass('fieldgroup-collapsible')) {
            Drupal.FieldGroup.Effects.processHtml_element.renderCollapsible(
              $wrapper,
            );
          } else if (
            groupInfo.settings.show_label &&
            element.matches('.required-fields') &&
            ($wrapper.find('[required]').length > 0 ||
              $wrapper.find('.form-required').length > 0)
          ) {
            $wrapper
              .find(`${groupInfo.settings.label_element}:first`)
              .addClass('form-required');
          }
        },
      );
    },
    renderCollapsible($wrapper) {
      // Turn the legend into a clickable link, but retain span.field-group-format-toggler
      // for CSS positioning.
      const $toggler = $('.field-group-toggler:first', $wrapper);
      const $link = $('<a class="field-group-title" href="#"></a>');
      $link.prepend($toggler.contents());

      // Add required field markers if needed.
      if (
        // eslint-disable-next-line jquery/no-is
        $wrapper.is('.required-fields') &&
        ($wrapper.find('[required]').length > 0 ||
          $wrapper.find('.form-required').length > 0)
      ) {
        $link.addClass('form-required');
      }

      $link.appendTo($toggler);

      // .wrapInner() does not retain bound events.
      $link.click(() => {
        const wrapper = $wrapper.get(0);
        // Don't animate multiple times.
        if (!wrapper.animating) {
          wrapper.animating = true;
          const speed = $wrapper.hasClass('speed-fast') ? 300 : 1000;
          if (
            $wrapper.hasClass('effect-none') &&
            $wrapper.hasClass('speed-none')
          ) {
            $('> .field-group-wrapper', wrapper).toggle();
          } else {
            $('> .field-group-wrapper', wrapper).toggle(speed);
          }
          wrapper.animating = false;
        }
        $wrapper.toggleClass('collapsed');
        return false;
      });
    },
  };
})(jQuery);
