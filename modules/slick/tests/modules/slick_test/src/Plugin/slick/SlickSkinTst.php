<?php

namespace Drupal\slick_test\Plugin\slick;

use Drupal\slick\SlickSkinPluginBase;

/**
 * Provides slick skin tests.
 *
 * @SlickSkin(
 *   id = "slick_skin_test",
 *   label = @Translation("Slick skin test")
 * )
 */
class SlickSkinTst extends SlickSkinPluginBase {

  /**
   * Sets the slick skins.
   *
   * @inheritdoc
   */
  protected function setSkins() {
    // If you copy this file, be sure to add base_path() before any asset path
    // (css or js) as otherwise failing to load the assets. Your module can
    // register paths pointing to a theme. Check out slick.api.php for details.
    $path = $this->getPath('module', 'slick_test');
    $skins = [
      'test' => [
        'name' => 'Test',
        'description' => $this->t('Test slick skins.'),
        'group' => 'main',
        'provider' => 'slick_test',
        'css' => [
          'theme' => [
            $path . '/css/slick.theme--test.css' => [],
          ],
        ],
        'options' => [
          'zoom' => TRUE,
        ],
      ],
    ];

    return $skins;
  }

  /**
   * Sets the slick arrow skins.
   *
   * @inheritdoc
   */
  protected function setArrows() {
    $path = $this->getPath('module', 'slick_test');
    $skins = [
      'arrows' => [
        'name' => 'Arrows',
        'description' => $this->t('Test slick arrows.'),
        'provider' => 'slick_test',
        'group' => 'arrows',
        'css' => [
          'theme' => [
            $path . '/css/slick.theme--arrows.css' => [],
          ],
        ],
      ],
    ];

    return $skins;
  }

  /**
   * Sets the slick dots skins.
   *
   * @inheritdoc
   */
  protected function setDots() {
    $path = $this->getPath('module', 'slick_test');
    $skins = [
      'dots' => [
        'name' => 'Dots',
        'description' => $this->t('Test slick dots.'),
        'provider' => 'slick_test',
        'group' => 'dots',
        'css' => [
          'theme' => [
            $path . '/css/slick.theme--dots.css' => [],
          ],
        ],
      ],
    ];

    return $skins;
  }

}
