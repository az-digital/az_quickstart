<?php

declare(strict_types=1);

namespace Drupal\az_publication;

use Drupal\az_publication\Entity\AZPublicationType;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Publication Type entities.
 */
class AZPublicationTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this
      ->getStorage()
      ->getQuery()
      ->sort('id', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
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
  public function load() {
    $entities = [
      'enabled' => [],
      'disabled' => [],
    ];

    foreach (parent::load() as $entity) {
      if ($entity instanceof AZPublicationType) {
        if ($entity->status()) {
          $entities['enabled'][] = $entity;
        }
        else {
          $entities['disabled'][] = $entity;
        }
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if (!$entity instanceof AZPublicationType) {
      return [];
    }
    $row = parent::buildRow($entity);
    return [
      'data' => [
        'id' => [
          'data' => [
            '#plain_text' => $entity->id(),
          ],
        ],
        'label' => [
          'data' => [
            '#plain_text' => $entity->label(),
          ],
        ],
        'type' => [
          'data' => [
            '#plain_text' => $entity->getType(),
          ],
        ],
        'operations' => $row['operations'],
      ],
      '#attributes' => [
        'class' => [$entity->status() ? 'az-publication-ui-list-enabled' : 'az-publication-ui-list-disabled'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'id' => [
        'data' => $this->t('Machine name'),
        'field' => 'id',
        'specifier' => 'id',
        'attributes' => [
          'class' => [
            RESPONSIVE_PRIORITY_LOW,
            'az-publication-type-machine-name',
          ],
        ],
      ],
      'label' => [
        'data' => $this->t('Label'),
        'field' => 'label',
        'specifier' => 'label',
        'attributes' => [
          'class' => [
            RESPONSIVE_PRIORITY_LOW,
            'az-publication-type-label',
          ],
        ],
      ],
      'type' => [
        'data' => $this->t('Mapped Type'),
        'field' => 'type',
        'specifier' => 'type',
        'attributes' => [
          'class' => [
            RESPONSIVE_PRIORITY_LOW,
            'az-publication-type',
          ],
        ],
      ],
      'operations' => [
        'data' => $this->t('Operations'),
        '#attributes' => [
          'class' => ['views-ui-operations'],
        ],
      ],
    ];
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
      $list[$status]['#attributes'] = [
        'class' => [
          'az-publication-type-list-section',
          $status,
        ],
      ];
      $list[$status]['table'] = [
        '#theme' => 'az_publication_type_listing_table',
        '#headers' => $this->buildHeader(),
        '#attributes' => [
          'class' => [
            'az-publication-type-listing-table',
            $status,
          ],
        ],
      ];
      foreach ($entities[$status] as $entity) {
        $list[$status]['table']['#rows'][$entity->id()] = $this->buildRow($entity);
      }
    }
    $list['enabled']['table']['#empty'] = $this->t('There are no enabled publication types.');
    $list['disabled']['table']['#empty'] = $this->t('There are no disabled publication types.');
    $list['table']['#empty'] = $this->t('No publication types available. <a href=":url">Add publication type</a>.', [
      ':url' => Url::fromRoute('entity.az_publication_type.add_form')->toString(),
    ]);

    return $list;
  }

}
