<?php

declare(strict_types=1);

namespace Drupal\az_finder\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides helper methods for working with view options in AZ Finder.
 */
final class AZFinderViewOptions {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cacheBackend;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

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
    asort($viewOptions);
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
      // Get the view executable.
      $view_exec = $view->getExecutable();
      $displays = $view->get('display') ?: [];
      foreach ($displays as $display_id => $display) {
        // Initialize the view with the selected display.
        $view_exec->initDisplay();
        $view_exec->setDisplay($display_id);
        // Load the display handler so we have access to the overridden options.
        $display_handler = $view_exec->getDisplay();
        if ($display_handler->isDefaultDisplay()) {
          // Don't display master displays as override options.
          continue;
        }
        $exposed_form_options = $display_handler->getOption('exposed_form') ?? [];
        $filters = $exposed_form_options['options']['bef']['filter'] ?? [];
        foreach ($filters as $filter_id => $filter_settings) {
          if (isset($filter_settings['plugin_id']) && $filter_settings['plugin_id'] === $plugin_id) {
            $options[$view->id() . ':' . $display_id] = $view->label() . ' (' . $displays[$display_id]['display_title'] . ')';
            break;
          }
        }
      }
    }

    return $options;
  }

}
