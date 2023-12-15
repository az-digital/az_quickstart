<?php

declare(strict_types=1);

namespace Drupal\az_publication;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use \Drupal\Core\Url;
/**
 * Provides a listing of Quickstart Citation Style entities.
 */
class AZQuickstartCitationStyleListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Style name');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No publication types available. <a href=":url">Add publication type</a>.', [
      ':url' => Url::fromRoute('entity.az_publication.type.add_form')->toString(),
    ]);
    return $build;
  }

}
