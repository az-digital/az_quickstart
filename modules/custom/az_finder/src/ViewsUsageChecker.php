<?php

namespace Drupal\az_finder;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service to check views usage of AZFinderTaxonomyIndexTidWidget.
 */
class ViewsUsageChecker {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new ViewsUsageChecker service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Retrieves all views and displays using the AZFinderTaxonomyIndexTidWidget.
   *
   * @return array
   *   An associative array where keys are view IDs and values are arrays of display IDs.
   */
  public function getViewsUsingWidget() {
    $views_using_widget = [];
    $all_views = $this->entityTypeManager->getStorage('view')->loadMultiple();

    foreach ($all_views as $view) {
      foreach ($view->get('display') as $display_id => $display) {
        $options = $display['display_options']['filters'] ?? [];
        foreach ($options as $filter) {
          if (isset($filter['plugin_id']) && $filter['plugin_id'] === 'az_finder_tid_widget') {
            $views_using_widget[$view->id()][] = $display_id;
          }
        }
      }
    }

    return $views_using_widget;
  }

}
