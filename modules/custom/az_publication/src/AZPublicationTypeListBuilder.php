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
    $row['status'] = $entity->status();
    $row['attributes']['class'][] = [$entity->status() ? 'views-ui-list-enabled' : 'views-ui-list-disabled'];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = [
      'enabled' => [],
      'disabled' => [],
    ];
    foreach (parent::load() as $entity) {
      if ($entity->status()) {
        $entities['enabled'][] = $entity;
      } else {
        $entities['disabled'][] = $entity;
      }
    }
    return $entities;

  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this
      ->getStorage()
      ->getQuery()
      ->sort('label', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
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
        'attributes' => [
          'class' => array(RESPONSIVE_PRIORITY_LOW),
        ]
      ),
      'label' => array(
        'data' => $this->t('Label'),
        'field' => 'label',
        'specifier' => 'label',
        'attributes' => [],
      ),
      'type' => array(
        'data' => $this->t('Mapped Type'),
        'field' => 'type',
        'specifier' => 'type',
        'attributes' => [],
      ),
      'status' => array(
        'data' => $this->t('Status'),
        'field' => 'status',
        'specifier' => 'status',
        'attributes' => [],
      ),
    );

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    // Add AJAX functionality to enable/disable operations.
    foreach (['enable', 'disable'] as $op) {
      if (isset($operations[$op])) {
        $operations[$op]['url'] = $entity->toUrl($op);
        // Enable and disable operations should use AJAX.
        $operations[$op]['attributes']['class'][] = 'use-ajax';
      }
    }

    // ajax.js focuses automatically on the data-drupal-selector element. When
    // enabling the type again, focusing on the disable link doesn't work, as it
    // is hidden. We assign data-drupal-selector to every link, so it focuses
    // on the edit link.
    foreach ($operations as &$operation) {
      $operation['attributes']['data-drupal-selector'] = 'az-publication-type-listing-' . $entity->id();
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    $list['#type'] = 'container';
    $list['#attributes']['id'] = 'az-publication-type-entity-list';
    $list['#attached']['library'][] = 'core/drupal.ajax';
    $list['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];
    $list['enabled']['heading']['#markup'] = '<h2>' . $this->t('Enabled', [], ['context' => 'Plural']) . '</h2>';
    $list['disabled']['heading']['#markup'] = '<h2>' . $this->t('Disabled', [], ['context' => 'Plural']) . '</h2>';
    foreach (['enabled', 'disabled'] as $status) {
      $list[$status]['#type'] = 'container';
      $list[$status]['#attributes'] = ['class' => ['views-list-section', $status]];
      $list[$status]['table'] = [
        '#theme' => 'az_publication_type_listing_table',
        '#headers' => $this->buildHeader(),
        '#attributes' => ['class' => ['views-listing-table', $status]],
      ];
      foreach ($entities[$status] as $entity) {
        $list[$status]['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }
    }
    $list['enabled']['table']['#empty'] = $this->t('There are no enabled views.');
    $list['disabled']['table']['#empty'] = $this->t('There are no disabled views.');

    return $list;
  }

}
