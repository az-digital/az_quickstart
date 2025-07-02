<?php

namespace Drupal\views_bootstrap;

use Drupal\Component\Utility\Html;
use Drupal\views\ViewExecutable;

/**
 * The primary class for the Views Bootstrap module.
 *
 * Provides many helper methods.
 *
 * @ingroup utility
 */
class ViewsBootstrap {

  /**
   * Returns the theme hook definition information.
   */
  public static function getThemeHooks() {
    $hooks['views_bootstrap_accordion'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_accordion',
        'template_preprocess_views_view_accordion',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];
    $hooks['views_bootstrap_carousel'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_carousel',
        'template_preprocess_views_view_carousel',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];
    $hooks['views_bootstrap_cards'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_cards',
        'template_preprocess_views_view_cards',
      ],
    ];
    $hooks['views_bootstrap_grid'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_grid',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];
    $hooks['views_bootstrap_list_group'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_list_group',
        'template_preprocess_views_view_list_group',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];
    $hooks['views_bootstrap_media_object'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_media_object',
        'template_preprocess_views_view_media_object',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];
    $hooks['views_bootstrap_tab'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_tab',
        'template_preprocess_views_view_tab',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];
    $hooks['views_bootstrap_table'] = [
      'preprocess functions' => [
        'template_preprocess_views_bootstrap_table',
        'template_preprocess_views_view_table',
      ],
      'file' => 'views_bootstrap.theme.inc',
    ];

    return $hooks;
  }

  /**
   * Return an array of breakpoint names.
   */
  public static function getBreakpoints() {
    return ['xs', 'sm', 'md', 'lg', 'xl'];
  }

  /**
   * Get column class prefix for the breakpoint.
   */
  public static function getColumnPrefix($breakpoint) {
    return 'col' . ($breakpoint != 'xs' ? '-' . $breakpoint : '');
  }

  /**
   * Get unique element id.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   A ViewExecutable object.
   *
   * @return string
   *   A unique id for an HTML element.
   */
  public static function getUniqueId(ViewExecutable $view) {
    $id = $view->storage->id() . '-' . $view->current_display;
    return Html::getUniqueId('views-bootstrap-' . $id);
  }

  /**
   * Get the number of items from the column class string.
   *
   * @param string $size
   *   Bootstrap grid size xs|sm|md|lg.
   *
   * @return int|false
   *   Number of columns in a 12 column grid or false.
   */
  public static function getColSize($size) {
    if (preg_match('~col-(?:[a-z]{2}-)?([0-9]+)~', $size, $matches)) {
      return 12 / $matches[1];
    }

    return FALSE;
  }

}
