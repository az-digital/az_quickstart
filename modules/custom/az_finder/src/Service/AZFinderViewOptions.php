<?php

declare(strict_types=1);

namespace Drupal\az_finder\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides helper methods for working with view options in AZ Finder.
 */
class AZFinderViewOptions {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AZFinderViewOptions object.
   */
  public function __construct(CacheBackendInterface $cache_backend, EntityTypeManagerInterface $entity_type_manager) {
    $this->cacheBackend = $cache_backend;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get the view options for a plugin.
   */
  public function getViewOptions(string $plugin_id = 'az_finder_tid_widget', bool $force_refresh = FALSE): array {
    $cache_id = 'az_finder:view_options:' . $plugin_id;
    if (!$force_refresh) {
      $cached_data = $this->cacheBackend->get($cache_id);
      if ($cached_data) {
        return $cached_data->data;
      }
    }

    $viewOptions = $this->getViewsUsingPlugin($plugin_id);
    $this->cacheBackend->set($cache_id, $viewOptions, CacheBackendInterface::CACHE_PERMANENT, ['az_finder:view_options']);
    return $viewOptions;
  }

  /**
   * Get the views using a specific plugin id.
   */
  private function getViewsUsingPlugin(string $plugin_id): array {
    $options = ['' => '- Select -'];
    $views = $this->entityTypeManager->getStorage('view')->loadMultiple();

    foreach ($views as $view) {
      $displays = $view->get('display') ?: [];
      foreach ($displays as $display_id => $display) {
        $exposed_form_options = $display['display_options']['exposed_form']['options'] ?? [];
        $filters = $exposed_form_options['bef']['filter'] ?? [];
        foreach ($filters as $filter_id => $filter_settings) {
          if (isset($filter_settings['plugin_id']) && $filter_settings['plugin_id'] === $plugin_id) {
            $options[$view->id() . ':' . $display_id] = $view->label() . ' (' . $display_id . ')';
            break;
          }
        }
      }
    }

    return $options;
  }

}
