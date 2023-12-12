<?php

declare(strict_types=1);

namespace Drupal\az_publication;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Publication Type entities.
 */
class AZPublicationTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $row['type'] = $entity->getType();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load() {

    // Get the entity type manager service.
    $entity_type_manager = \Drupal::service('entity_type.manager');

    // Get the entity storage for your 'az_publication_type' entity type.
    $entity_storage = $entity_type_manager->getStorage('az_publication_type');

    // Create a query.
    $entity_query = $entity_storage->getQuery();

    $header = $this->buildHeader();

    $entity_query->pager(50);
    $entity_query->tableSort($header);

    $tids = $entity_query->execute();

    return $this->storage->loadMultiple($tids);
  }
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header = array(
      'id' => array(
        'data' => $this->t('Machine name'),
        'field' => 'id',
        'specifier' => 'id',
        'class' => array(RESPONSIVE_PRIORITY_LOW),
      ),
      'label' => array(
        'data' => $this->t('Label'),
        'field' => 'label',
        'specifier' => 'label',
      ),
      'type' => array(
        'data' => $this->t('Mapped Type'),
        'field' => 'type',
        'specifier' => 'type',
      ),

    );

    return $header + parent::buildHeader();
  }

}
