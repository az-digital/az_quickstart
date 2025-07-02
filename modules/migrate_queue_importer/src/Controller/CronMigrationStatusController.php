<?php

namespace Drupal\migrate_queue_importer\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Status controller for enabling and disabling a cron migration.
 */
class CronMigrationStatusController extends ControllerBase {

  /**
   * Enable callback.
   *
   * @param string $cron_migration
   *   The Cron migration entity id.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect back to the collection list.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function enable(string $cron_migration) {
    /** @var \Drupal\migrate_queue_importer\Entity\CronMigration $entity */
    $entity = $this->entityTypeManager()->getStorage('cron_migration')->load($cron_migration);
    $entity->set('status', TRUE);
    $entity->save();
    return $this->redirect('entity.cron_migration.collection');
  }

  /**
   * Disable callback.
   *
   * @param string $cron_migration
   *   The Cron migration entity id.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect back to the collection list.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function disable(string $cron_migration) {
    /** @var \Drupal\migrate_queue_importer\Entity\CronMigration $entity */
    $entity = $this->entityTypeManager()->getStorage('cron_migration')->load($cron_migration);
    $entity->set('status', FALSE);
    $entity->save();
    return $this->redirect('entity.cron_migration.collection');
  }

}
