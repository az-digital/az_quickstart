<?php

namespace Drupal\environment_indicator;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of environments.
 */
class EnvironmentIndicatorListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'environment_indicator_overview_environments';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row['name'] = $this->t('Environment name');
    $row['url'] = $this->t('Environment url');
    $row += parent::buildHeader();
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\environment_indicator\Entity\EnvironmentIndicator $entity */
    $row = [
      'style' => 'color: ' . $entity->getFgColor() . '; background-color: ' . $entity->getBgColor() . ';',
    ];

    $row['data']['name'] = [
      'data' => $entity->label(),
    ];
    $row['data']['url'] = [
      'data' => $entity->getUrl(),
    ];

    $row['data'] += parent::buildRow($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['action_header']['#markup'] = '<h3>' . $this->t('Available actions:') . '</h3>';
    $entities = $this->load();
    // If there are not multiple vocabularies, disable dragging by unsetting the
    // weight key.
    if (count($entities) <= 1) {
      unset($this->weightKey);
    }
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No environment switchers available. <a href=":link">Add environment</a>.', [':link' => Url::fromRoute('entity.environment_indicator.add')->toString()]);
    return $build;
  }

}
