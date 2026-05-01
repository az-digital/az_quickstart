<?php

declare(strict_types=1);

namespace Drupal\az_opportunity_trellis;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of trellis opportunity imports.
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
    /** @var \Drupal\az_opportunity_trellis\AZRecurringImportRuleInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();

    // Get entity's query parameters.
    $params = $entity->getQueryParameters();
    unset($params['publish']);

    // Map API param names to views exposed filter identifiers.
    $api_to_view_identifier = [
      'name' => 'property',
      'account_id' => 'property_2',
    ];
    foreach ($api_to_view_identifier as $api_key => $view_key) {
      if (array_key_exists($api_key, $params)) {
        $params[$view_key] = $params[$api_key];
        unset($params[$api_key]);
      }
    }

    if (!empty($params)) {
      // Generate link to results page.
      $row['result']['data'] = [
        '#type' => 'link',
        '#title' => $this->t('View Results'),
        '#url' => Url::fromRoute('view.az_opportunity_trellis_import.page_1'),
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
