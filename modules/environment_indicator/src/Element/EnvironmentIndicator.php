<?php

namespace Drupal\environment_indicator\Element;

use Drupal\Core\Render\Element\Container;

/**
 * Provides a render element to add the environment indicator to the page.
 *
 * Usage example:
 * @code
 * $page_top['indicator'] = [
 *   '#type' => 'environment_indicator',
 *   '#title' => t('Acceptance'),
 *   '#description' => t('Sprint 9'),
 *   '#attributes' => ['class' => 'custom-class'],
 *   '#fg_color' => '#123412',
 *   '#bg_color' => 'FEDCBA9',
 * ];
 * @endcode
 *
 * @RenderElement("environment_indicator")
 */
class EnvironmentIndicator extends Container {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#theme_wrappers'][] = 'environment_indicator';
    return $info;
  }

}
