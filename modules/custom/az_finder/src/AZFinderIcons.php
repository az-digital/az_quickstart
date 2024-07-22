<?php

declare(strict_types=1);

namespace Drupal\az_finder;

/**
 * Provides SVG icons for the Quickstart Finder module.
 */
final class AZFinderIcons {

  /**
   * Cache for SVG icon render arrays.
   *
   * @var array
   */
  protected $svgIconCache = [];

  /**
   * Generates SVG icons.
   *
   * @return array
   *   The SVG render arrays for the icons.
   */
  public function generateSvgIcons(): array {
    if (!empty($this->svgIconCache)) {
      return $this->svgIconCache;
    }
    $svg_icons = [];
    $levels = [0, 1];
    $actions = ['expand', 'collapse'];
    foreach ($levels as $level) {
      foreach ($actions as $action) {
        $icon_render_array = $this->generateSvgRenderArray($level, $action);
        $svg_icons["level_{$level}_{$action}"] = $icon_render_array;
      }
    }

    return $svg_icons;
  }

  /**
   * Generates a render array for an SVG icon based on depth and action.
   *
   * @param int $depth
   *   The depth of the option, affecting icon size and path.
   * @param string $action
   *   The action ('expand' or 'collapse') determining the icon.
   *
   * @return array
   *   A cached render array for the SVG icon.
   */
  protected function generateSvgRenderArray($depth, $action): array {
    $cacheKey = "level_{$depth}_{$action}";
    if (!isset($this->svgIconCache[$cacheKey])) {
      // Generate the icon and cache it.
      $this->svgIconCache[$cacheKey] = $this->createSvgIconRenderArray($depth, $action);
    }

    return $this->svgIconCache[$cacheKey];
  }

  /**
   * Creates a render array for an SVG icon based on depth and action.
   *
   * @param int $depth
   *   The depth of the option, affecting icon size and path.
   * @param string $action
   *   The action ('expand' or 'collapse') determining the icon.
   *
   * @return array
   *   A render array for the SVG icon.
   */
  protected function createSvgIconRenderArray($depth, $action) {
    $size = $depth === 0 ? '24' : '16';
    // Default color.
    $fillColor = '#1e5288';
    // Default title.
    if ($action === 'expand') {
      $title = 'Expand this section';
    }
    else {
      $title = 'Collapse this section';
    }
    $iconPath = $this->getIconPath($depth, $action);
    $attributes = [
      'fill_color' => $fillColor,
      'size' => $size,
      'title' => $title,
      'icon_path' => $iconPath,
    ];
    foreach ($attributes as &$attribute) {
      $attribute = htmlspecialchars($attribute, ENT_QUOTES, 'UTF-8');
    }
    $svg_render_template = [
      '#type' => 'inline_template',
      '#template' => '<svg title="{{ title }}" xmlns="http://www.w3.org/2000/svg" width="{{ size }}" height="{{ size }}" viewBox="0 0 24 24"><path fill="{{ fill_color }}" d="{{ icon_path }}"/></svg>',
      '#context' => $attributes,
    ];

    return $svg_render_template;
  }

  /**
   * Determines the SVG path for the icon based on depth and action.
   *
   * @param int $depth
   *   Depth of the item, affecting the icon shape.
   * @param string $action
   *   Action type ('expand' or 'collapse').
   *
   * @return string|null
   *   SVG path for the specified icon, or NULL if not found.
   */
  protected function getIconPath($depth, $action): ?string {
    $icon_paths = [
      'expand' => [
        '0' => 'M16.59 8.59 12 13.17 7.41 8.59 6 10l6 6 6-6-1.41-1.41z',
        '1' => 'M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z',
      ],
      'collapse' => [
        '0' => 'm12 8-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14l-6-6z',
        '1' => 'M19 13H5v-2h14v2z',
      ],
    ];

    return $icon_paths[$action][$depth] ?? NULL;
  }

}
