<?php

namespace Drupal\workbench_access;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Access scheme entities.
 */
class AccessSchemeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Access scheme');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    return parent::getDefaultOperations($entity) + [
      'sections' => [
        'title' => $this->t('Sections'),
        'weight' => 0,
        'url' => $entity->toUrl('sections'),
      ],
    ];
  }

}
