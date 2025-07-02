<?php

namespace Drupal\config_distro;

use Drupal\config_distro\Event\ConfigDistroEvents;
use Drupal\config_distro\Event\DistroStorageImportEvent;
use Drupal\Core\Config\Importer\ConfigImporterBatch;

/**
 * Custom methods for running the ConfigImporter in a batch.
 *
 * @see \Drupal\Core\Config\ConfigImporterBatch
 */
class ConfigDistroConfigImporterBatch extends ConfigImporterBatch {

  /**
   * {@inheritdoc}
   */
  public static function finish($success, $results, $operations) {
    parent::finish($success, $results, $operations);
    if ($success) {
      // Dispatch an event to notify modules about the successful import.
      \Drupal::service('event_dispatcher')->dispatch(new DistroStorageImportEvent(), ConfigDistroEvents::IMPORT);
    }
  }

}
