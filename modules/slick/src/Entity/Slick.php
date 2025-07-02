<?php

namespace Drupal\slick\Entity;

/**
 * Defines the Slick configuration entity.
 *
 * @ConfigEntityType(
 *   id = "slick",
 *   label = @Translation("Slick optionset"),
 *   list_path = "admin/config/media/slick",
 *   config_prefix = "optionset",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "weight",
 *     "label",
 *     "group",
 *     "skin",
 *     "breakpoints",
 *     "optimized",
 *     "options",
 *   }
 * )
 */
class Slick extends SlickBase implements SlickInterface {

  /**
   * The optionset group for easy selections.
   *
   * @var string
   */
  protected $group = '';

  /**
   * The skin name for the optionset.
   *
   * @var string
   */
  protected $skin = '';

  /**
   * The number of breakpoints for the optionset.
   *
   * @var int
   */
  protected $breakpoints = 0;

  /**
   * The flag indicating to optimize the stored options by removing defaults.
   *
   * @var bool
   */
  protected $optimized = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getBreakpoints(): int {
    return $this->breakpoints ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getSkin(): string {
    return $this->skin ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup(): string {
    return $this->group ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function optimized(): bool {
    return $this->optimized ?? FALSE;
  }

  /**
   * Defines the dependent options.
   *
   * @return array
   *   The dependent options.
   */
  public static function getDependentOptions(): array {
    $down_arrow = ['downArrowTarget', 'downArrowOffset'];
    return [
      'arrows'     => ['arrowsPlacement', 'prevArrow', 'nextArrow', 'downArrow'] + $down_arrow,
      'downArrow'  => $down_arrow,
      'autoplay'   => [
        'pauseOnHover',
        'pauseOnDotsHover',
        'pauseOnFocus',
        'autoplaySpeed',
        'useAutoplayToggleButton',
        'pauseIcon',
        'playIcon',
      ],
      'centerMode' => ['centerPadding'],
      'dots'       => ['dotsClass', 'appendDots'],
      'swipe'      => ['swipeToSlide'],
      'useCSS'     => ['cssEase', 'cssEaseBezier', 'cssEaseOverride'],
      'vertical'   => ['verticalSwiping'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getResponsiveOptions(): array {
    $options = [];
    if (empty($this->breakpoints)) {
      return $options;
    }

    if (isset($this->options['responsives']['responsive'])) {
      $responsives = $this->options['responsives'];
      if ($responsives['responsive']) {
        foreach ($responsives['responsive'] as $delta => $responsive) {
          if (empty($responsives['responsive'][$delta]['breakpoint'])) {
            unset($responsives['responsive'][$delta]);
          }
          if (isset($responsives['responsive'][$delta])) {
            $options[$delta] = $responsive;
          }
        }
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function setResponsiveSettings($values, $delta = 0, $key = 'settings'): self {
    $this->options['responsives']['responsive'][$delta][$key] = $values;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeWastedDependentOptions(array &$js): void {
    foreach (self::getDependentOptions() as $key => $option) {
      if (isset($js[$key]) && empty($js[$key])) {
        foreach ($option as $dependent) {
          unset($js[$dependent]);
        }
      }
    }

    if (!empty($js['useCSS']) && !empty($js['cssEaseBezier'])) {
      $js['cssEase'] = $js['cssEaseBezier'];
    }
    unset($js['cssEaseOverride'], $js['cssEaseBezier']);
  }

  /**
   * {@inheritdoc}
   */
  public function toJson(array $js): array {
    $config   = [];
    $defaults = self::defaultSettings();

    // Remove wasted dependent options if disabled, empty or not.
    if (!$this->optimized) {
      $this->removeWastedDependentOptions($js);
    }

    $config = array_diff_assoc($js, $defaults);

    // Remove empty lazyLoad, or left to default ondemand, to avoid JS error.
    if (empty($config['lazyLoad'])) {
      unset($config['lazyLoad']);
    }

    // Do not pass arrows HTML to JSON object as some are enforced.
    $excludes = [
      'downArrow',
      'downArrowTarget',
      'downArrowOffset',
      'prevArrow',
      'nextArrow',
    ];
    foreach ($excludes as $key) {
      unset($config[$key]);
    }

    // Clean up responsive options if similar to defaults.
    if ($responsives = $this->getResponsiveOptions()) {
      $cleaned = [];
      foreach ($responsives as $key => $responsive) {
        $cleaned[$key]['breakpoint'] = $responsives[$key]['breakpoint'];

        // Destroy responsive slick if so configured.
        if (!empty($responsives[$key]['unslick'])) {
          $cleaned[$key]['settings'] = 'unslick';
          unset($responsives[$key]['unslick']);
        }
        else {
          // Remove wasted dependent options if disabled, empty or not.
          if (!$this->optimized) {
            $this->removeWastedDependentOptions($responsives[$key]['settings']);
          }
          $cleaned[$key]['settings'] = array_diff_assoc($responsives[$key]['settings'], $defaults);
        }
      }
      $config['responsive'] = $cleaned;
    }
    return $config;
  }

  /**
   * Strip out options containing default values so to have real clean JSON.
   *
   * @return array
   *   The cleaned out settings.
   *
   * @todo reprecated in 2.10, and is removed from 3.x. Use self::toJson()
   *   instead.
   */
  public function removeDefaultValues(array $js): array {
    return $this->toJson($js);
  }

  /**
   * Deprecated in blazy:8.x-2.17.
   *
   * Since blazy:2.17, sliders lazyloads are deprecated to avoid complication.
   *
   * @deprecated in slick:8.x-2.10 and is removed from slick:3.0.0. Use
   * none instead.
   * @see https://www.drupal.org/node/3239708
   */
  public function whichLazy(array &$settings): void {
    // Do nothing.
  }

}
