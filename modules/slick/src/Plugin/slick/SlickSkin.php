<?php

namespace Drupal\slick\Plugin\slick;

use Drupal\slick\SlickSkinPluginBase;

/**
 * Provides slick skins.
 *
 * @SlickSkin(
 *   id = "slick_skin",
 *   label = @Translation("Slick skin")
 * )
 */
class SlickSkin extends SlickSkinPluginBase {

  /**
   * Sets the slick skins.
   *
   * @inheritdoc
   */
  protected function setSkins() {
    // If you copy this file, be sure to add base_path() before any asset path
    // (css or js) as otherwise failing to load the assets. Your module can
    // register paths pointing to a theme. Check out slick.api.php for details.
    $skins = [
      'default' => [
        'name' => 'Default',
        'css' => [
          'theme' => [
            'css/theme/slick.theme--default.css' => [],
          ],
        ],
      ],
      'asnavfor' => [
        'name' => 'Thumbnail: asNavFor',
        'css' => [
          'theme' => [
            'css/theme/slick.theme--asnavfor.css' => [],
          ],
        ],
        'description' => $this->t('Affected thumbnail navigation only.'),
      ],
      'classic' => [
        'name' => 'Classic',
        'description' => $this->t('Adds dark background color over white caption, only good for slider (single slide visible), not carousel (multiple slides visible), where small captions are placed over images.'),
        'css' => [
          'theme' => [
            'css/theme/slick.theme--classic.css' => [],
          ],
        ],
      ],
      'fullscreen' => [
        'name' => 'Full screen',
        'description' => $this->t('Adds full screen display, works best with 1 slidesToShow.'),
        'css' => [
          'theme' => [
            'css/theme/slick.theme--full.css' => [],
            'css/theme/slick.theme--fullscreen.css' => [],
          ],
        ],
      ],
      'fullwidth' => [
        'name' => 'Full width',
        'description' => $this->t('Adds .slide__constrained wrapper to hold caption overlay within the max-container.'),
        'css' => [
          'theme' => [
            'css/theme/slick.theme--full.css' => [],
            'css/theme/slick.theme--fullwidth.css' => [],
          ],
        ],
      ],
      'grid' => [
        'name' => 'Grid Foundation',
        'description' => $this->t('Use slidesToShow > 1 to have more grid combination, only if you have considerable amount of grids, otherwise 1.'),
        'css' => [
          'theme' => [
            'css/theme/slick.theme--grid.css' => [],
          ],
        ],
      ],
      'split' => [
        'name' => 'Split',
        'description' => $this->t('Puts image and caption side by side, requires any split layout option.'),
        'css' => [
          'theme' => [
            'css/theme/slick.theme--split.css' => [],
          ],
        ],
      ],
    ];

    foreach ($skins as $key => $skin) {
      $skins[$key]['group'] = $key == 'asnavfor' ? 'thumbnail' : 'main';
      $skins[$key]['provider'] = 'slick';
    }

    return $skins;
  }

}
