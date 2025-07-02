<?php

namespace Drupal\migrate_queue_importer\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * List builder for cron migration entity.
 */
class CronMigrationListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['status'] = $this->t('Status');
    $header['interval'] = $this->t('Interval');
    $header['update'] = $this->t('Update');
    $header['sync'] = $this->t('Sync');
    $header['ignore_dependencies'] = $this->t('Ignore dependencies');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\migrate_queue_importer\Entity\CronMigration $entity */
    $row['label'] = $entity->label();
    $row['status'] = $entity->get('status') ? '✔' : '✖';
    $row['interval'] = ($entity->get('time') / 60) . ' min';
    $row['update'] = $entity->get('update') ? '✔' : '✖';
    $row['sync'] = $entity->get('sync') ? '✔' : '✖';
    $row['ignore_dependencies'] = $entity->get('ignore_dependencies') ? '✔' : '✖';
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

}
