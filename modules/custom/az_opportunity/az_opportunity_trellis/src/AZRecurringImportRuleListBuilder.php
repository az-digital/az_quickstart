<?php

declare(strict_types=1);

namespace Drupal\az_event_trellis;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of trellis event imports.
 */
final class AZRecurringImportRuleListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['results'] = $this->t('Results');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\az_event_trellis\AZRecurringImportRuleInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();

    // Get entity's query parameters.
    $params = $entity->getQueryParameters();
    unset($params['publish']);

    if (!empty($params)) {
      // Generate link to results page.
      $row['result']['data'] = [
        '#type' => 'link',
        '#title' => $this->t('View Results'),
        '#url' => Url::fromRoute('view.az_event_trellis_import.page_1'),
        '#options' => ['query' => $params],
        '#attributes' => ['class' => 'button'],
      ];
    }
    else {
      $row['result'] = '';
    }
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

}
