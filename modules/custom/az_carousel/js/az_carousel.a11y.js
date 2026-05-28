(function azCarouselA11yScript(Drupal, once, $) {
  function normalizeCarouselAria(carousel) {
    carousel
      .querySelectorAll('.slick-track[role="listbox"], .slick-slide[role="option"]')
      .forEach((element) => {
        element.removeAttribute('role');
        element.removeAttribute('aria-describedby');
      });

    carousel.querySelectorAll('.slick-dots[role="tablist"]').forEach((dots) => {
      dots.removeAttribute('role');
      dots.removeAttribute('aria-label');
      dots.removeAttribute('aria-labelledby');
      dots.removeAttribute('aria-orientation');
    });

    carousel.querySelectorAll('.slick-dots li').forEach((dotItem) => {
      dotItem.removeAttribute('role');
      dotItem.removeAttribute('aria-selected');
      dotItem.removeAttribute('aria-controls');
    });

    carousel
      .querySelectorAll(
        '[aria-hidden="true"] a, [aria-hidden="true"] button, [aria-hidden="true"] input, [aria-hidden="true"] select, [aria-hidden="true"] textarea, [aria-hidden="true"] [tabindex]',
      )
      .forEach((focusable) => {
        focusable.setAttribute('tabindex', '-1');
      });

    carousel
      .querySelectorAll(
        '[aria-hidden="false"] a[tabindex="-1"], [aria-hidden="false"] button[tabindex="-1"], [aria-hidden="false"] input[tabindex="-1"], [aria-hidden="false"] select[tabindex="-1"], [aria-hidden="false"] textarea[tabindex="-1"], [aria-hidden="false"] [tabindex="-1"]',
      )
      .forEach((focusable) => {
        focusable.removeAttribute('tabindex');
      });
  }

  Drupal.behaviors.azCarouselA11y = {
    attach(context) {
      once('az-carousel-a11y', '.az-carousel', context).forEach((carousel) => {
        const $carousel = $(carousel);
        const runNormalization = () => normalizeCarouselAria(carousel);

        runNormalization();
        $carousel.on('init reInit afterChange setPosition breakpoint', () => {
          runNormalization();
        });
      });
    },
  };
})(Drupal, once, jQuery);
