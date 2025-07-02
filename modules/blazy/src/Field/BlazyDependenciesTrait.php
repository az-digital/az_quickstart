<?php

namespace Drupal\blazy\Field;

use Drupal\blazy\BlazyDefault;

/**
 * A Trait common for file, image or media to handle dependencies.
 */
trait BlazyDependenciesTrait {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $style_ids = [];
    foreach (BlazyDefault::imageStyles() as $key) {
      if (!empty($this->getSetting($key . '_style'))) {
        $style_ids[] = $this->getSetting($key . '_style');
      }
    }

    if ($style_ids) {
      foreach ($style_ids as $style_id) {
        /** @var \Drupal\image\ImageStyleInterface $style */
        if ($style = $this->formatter->load($style_id, 'image_style')) {
          // If this formatter uses a valid image style to display the image,
          // add the image style configuration entity as dependency of this
          // formatter.
          $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
        }
      }
    }

    if ($this->formatter->moduleExists('responsive_image')) {
      foreach (['box', 'responsive_image'] as $key) {
        $style_id = $this->getSetting($key . '_style');

        /** @var \Drupal\responsive_image\ResponsiveImageStyleInterface $style */
        if ($style_id && $style = $this->formatter->load($style_id, 'responsive_image_style')) {
          // Add the responsive image style as dependency.
          $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
        }
      }
    }

    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    $style_ids = [];
    foreach (BlazyDefault::imageStyles() as $key) {
      $name = $key . '_style';
      if (!empty($this->getSetting($name))) {
        $style_ids[$name] = $this->getSetting($name);
      }
    }

    if ($style_ids) {
      foreach ($style_ids as $name => $style_id) {
        /** @var \Drupal\image\ImageStyleInterface $style */
        if ($style = $this->formatter->load($style_id, 'image_style')) {
          if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
            $replacement_id = $this->formatter->getStorage('image_style')->getReplacementId($style_id);
            // If a valid replacement has been provided in the storage, replace
            // the image style with the replacement and signal that the
            // formatter plugin settings were updated.
            if ($replacement_id && $this->formatter->load($replacement_id, 'image_style')) {
              $this->setSetting($name, $replacement_id);
              $changed = TRUE;
            }
          }
        }
      }
    }

    return $changed;
  }

}
