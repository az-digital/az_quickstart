<?php

declare(strict_types=1);

namespace Drupal\az_media_slate;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Decides whether an editor is working on the current page.
 *
 * AzMediaRemoteSlateFormatter asks this before it renders. On an editing page,
 * such as a node edit form or the Media Library, it shows a grey placeholder
 * with the media's name instead of the live form.
 */
class AzMediaSlateService {

  /**
   * Routes where a live Slate form must not render.
   *
   * Rationale: a live form would load Slate's scripts and a working form into
   * the page the editor is working on, including inside CKEditor's preview.
   */
  private const EDITING_ROUTES = [
    // Node add, edit, and preview.
    'entity.node.add_form',
    'entity.node.edit_form',
    'entity.node.preview',
    // Media add and edit.
    'entity.media.add_form',
    'entity.media.edit_form',
    // The Media Library, and CKEditor's preview of media embedded in text.
    'media_library.ui',
    'media.filter.preview',
    // Adding and editing a custom block. A custom block's canonical route is
    // its edit form.
    'block_content.add_form',
    'entity.block_content.canonical',
  ];

  /**
   * Route name prefixes where a live Slate form must not render.
   *
   * Layout Builder has many routes, such as layout_builder.choose_block, so
   * match the prefix instead of listing each one.
   */
  private const EDITING_ROUTE_PREFIXES = [
    'layout_builder.',
  ];

  /**
   * The current route match.
   */
  private RouteMatchInterface $routeMatch;

  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Checks whether the current page is one where an editor is working.
   *
   * Careful: anything that branches on this needs the route.name cache
   * context. Without it, Drupal can cache the placeholder built for an editor
   * and serve that copy to a visitor, with no error. A cache context tells
   * Drupal to keep a separate cached copy per value, here one per route.
   *
   * @return bool
   *   TRUE when the current route is an editing route.
   */
  public function isEditingContext(): bool {
    $route_name = $this->routeMatch->getRouteName();
    if ($route_name === NULL) {
      return FALSE;
    }
    if (in_array($route_name, self::EDITING_ROUTES, TRUE)) {
      return TRUE;
    }
    foreach (self::EDITING_ROUTE_PREFIXES as $prefix) {
      if (str_starts_with($route_name, $prefix)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
