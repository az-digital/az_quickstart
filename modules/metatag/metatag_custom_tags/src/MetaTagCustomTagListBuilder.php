<?php

namespace Drupal\metatag_custom_tags;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of custom tags.
 */
class MetaTagCustomTagListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Name');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\metatag_custom_tags\MetaTagCustomTagInterface $entity */
    $row['label'] = $entity->label();
    $row['description'] = $entity->get('description');
    return $row + parent::buildRow($entity);
  }

}
