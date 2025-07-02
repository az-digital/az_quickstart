<?php

namespace Drupal\blazy_layout\Plugin\Layout;

/**
 * Provides a BlazyLayout class for Layout plugins.
 */
class BlazyLayout extends BlazyLayoutForm {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions): array {
    $this->init();

    $build    = parent::build($regions);
    $settings = $this->settings();

    $build['#settings'] = $settings;
    $build['#count']    = static::$count;

    // Modifies output.
    $output = $this->interpolate($settings, $build);

    // Modifies attachments.
    $this->attachments($output, $settings);

    // Modifies attributes.
    $this->attributes($output, $settings);

    // Modifies regions.
    $this->regions($output, $settings);

    // Provides inline style.
    $this->styles($output, $settings);

    // Updates settings and layout.
    $output['#settings'] = $settings;
    $this->setConfiguration($settings);
    $output['#layout'] = $this->pluginDefinition;

    ksort($output);
    return $output;
  }

  /**
   * Interpolate data from Layout Builder to extract grid attributes.
   */
  private function interpolate(array &$settings, array $build): array {
    $sets = $settings;
    $sets['is_form'] = FALSE;
    $data = $this->manager->initGrid($sets);

    $settings = $this->manager->merge($data['settings'], $settings);
    $build['#attributes'] = $this->manager->merge(
      $data['attributes'],
      $build,
      '#attributes'
    );

    return $build;
  }

}
