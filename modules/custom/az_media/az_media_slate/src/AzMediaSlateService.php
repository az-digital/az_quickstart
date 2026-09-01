<?php

declare(strict_types=1);

namespace Drupal\az_media_slate;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Helper functions for the az_media_slate module.
 *
 * Tells the formatter whether the page being built is somewhere an editor is
 * working, so it can render a placeholder instead of a live form.
 */
class AzMediaSlateService {

  /**
   * Routes where a live Slate form must not render.
   *
   * A real form inside an edit form fights the editor: its required fields can
   * block saving, and its scripts run inside CKEditor's preview.
   */
  private const EDITING_ROUTES = [
    // Node add, edit, and preview.
    'entity.node.add_form',
    'entity.node.edit_form',
    'entity.node.preview',
    // Media add and edit, including the media the form itself lives on.
    'entity.media.add_form',
    'entity.media.edit_form',
    // Media Library, and CKEditor's inline preview of an embedded media.
    'media_library.ui',
    'media.filter.preview',
    // Block content editing.
    'block_content.add_form',
    'entity.block_content.canonical',
  ];

  /**
   * Route name prefixes where a live Slate form must not render.
   *
   * Layout Builder has many routes and adds more between releases, so match on
   * the prefix rather than trying to keep a list of them current.
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
   * Whether the current request is an editing context.
   *
   * Anything branching on this needs the route.name cache context, or a
   * placeholder rendered for an editor can be served from cache to a visitor.
   *
   * @return bool
   *   TRUE when an editor is working on this page.
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
