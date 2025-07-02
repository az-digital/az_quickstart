<?php

namespace Drupal\metatag;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Separator logic used elsewhere.
 */
trait MetatagSeparator {

  /**
   * The default separator to use when one is not defined through configuration.
   *
   * @var string
   */
  public static $defaultSeparator = ',';

  /**
   * Returns the multiple value separator for this site.
   *
   * This is the character used to explode multiple values. It defaults to a
   * comma but can be set to any other character or string.
   *
   * @return string
   *   The correct separator.
   */
  public function getSeparator(): string {
    // Load the separator saved in configuration.
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->get('metatag.settings');

    // @todo This extra check shouldn't be needed.
    if ($config) {
      $separator = $config->get('separator');
    }

    // By default the separator setting has a blank value, so use the default
    // value defined above.
    if (is_null($separator) || $separator == '') {
      $separator = $this::$defaultSeparator;
    }

    return $separator;
  }

}
