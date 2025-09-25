<?php

declare(strict_types=1);

namespace Drupal\az_finder\Service;

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
      '#template' => '<svg title="{{ title }}" xmlns="http://www.w3.org/2000/svg" width="{{ size }}" height="{{ size }}" viewBox="0 -960 960 960"><path fill="{{ fill_color }}" d="{{ icon_path }}"/></svg>',
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
        // Stat Minus 1: https://fonts.google.com/icons?icon.style=Rounded&icon.set=Material+Symbols&icon.size=24&icon.color=%23e3e3e3&selected=Material+Symbols+Rounded:stat_minus_1:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=stat
        '0' => 'M480-362q-8 0-15-2.5t-13-8.5L268-557q-11-11-11.5-27.5T268-613q11-11 28-11t28 11l156 155 156-155q11-11 27.5-11.5T692-613q11 11 11 28t-11 28L508-373q-6 6-13 8.5t-15 2.5Z',
        // Add: https://fonts.google.com/icons?icon.style=Rounded&icon.set=Material+Symbols&icon.size=24&icon.color=%23e3e3e3&selected=Material+Symbols+Rounded:add:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=add
        '1' => 'M440-440H240q-17 0-28.5-11.5T200-480q0-17 11.5-28.5T240-520h200v-200q0-17 11.5-28.5T480-760q17 0 28.5 11.5T520-720v200h200q17 0 28.5 11.5T760-480q0 17-11.5 28.5T720-440H520v200q0 17-11.5 28.5T480-200q-17 0-28.5-11.5T440-240v-200Z',
      ],
      'collapse' => [
        // Stat 1: https://fonts.google.com/icons?icon.style=Rounded&icon.set=Material+Symbols&icon.size=24&icon.color=%23e3e3e3&selected=Material+Symbols+Rounded:stat_1:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=stat
        '0' => 'M480-528 324-373q-11 11-27.5 11.5T268-373q-11-11-11-28t11-28l184-184q6-6 13-8.5t15-2.5q8 0 15 2.5t13 8.5l184 184q11 11 11.5 27.5T692-373q-11 11-28 11t-28-11L480-528Z',
        // Remove: https://fonts.google.com/icons?icon.style=Rounded&icon.set=Material+Symbols&icon.size=24&icon.color=%23e3e3e3&selected=Material+Symbols+Rounded:remove:FILL@0;wght@400;GRAD@0;opsz@24&icon.query=remove
        '1' => 'M240-440q-17 0-28.5-11.5T200-480q0-17 11.5-28.5T240-520h480q17 0 28.5 11.5T760-480q0 17-11.5 28.5T720-440H240Z',
      ],
    ];

    return $icon_paths[$action][$depth] ?? NULL;
  }

}
