<?php

namespace Drupal\embed;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of all Embed Button entities.
 */
class EmbedButtonListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['label'] = $this->t('Embed button');
    $header['embed_type'] = $this->t('Embed type');
    $header['icon'] = [
      'data' => $this->t('Icon'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\embed\EmbedButtonInterface $entity */
    $row = [];
    $row['label'] = $entity->label();
    $row['embed_type'] = $entity->getTypeLabel();
    if ($icon_url = $entity->getIconUrl()) {
      $row['icon']['data'] = [
        '#theme' => 'image',
        '#uri' => $icon_url,
        '#alt' => $this->t('Icon for the @label button.', ['@label' => $entity->label()]),
        '#height' => 32,
        '#width' => 32,
      ];
    }
    else {
      $row['icon'] = $this->t('None');
    }

    return $row + parent::buildRow($entity);
  }

}
