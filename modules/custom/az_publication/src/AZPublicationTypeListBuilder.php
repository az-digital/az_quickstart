<?php

declare(strict_types=1);

namespace Drupal\az_publication;

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
    $current_user = \Drupal::currentUser();
    if (!$current_user->hasPermission('delete publication type entities') && isset($operations['delete'])) {
      unset($operations['delete']);
    }
    if (!$current_user->hasPermission('disable publication type entities') && isset($operations['disable'])) {
      unset($operations['disable']);
    }
    if (!$current_user->hasPermission('enable publication type entities') && isset($operations['enable'])) {
      unset($operations['enable']);
    }
    // Ensure ajax.js focuses on appropriate element by setting
    // data-drupal-selector.
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
      /** @var \Drupal\az_publication\Entity\AZPublicationTypeInterface $entity */
      if ($entity->get('status')) {
        $entities['enabled'][] = $entity;
      }
      else {
        $entities['disabled'][] = $entity;
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\az_publication\Entity\AZPublicationTypeInterface $entity */
    $row = parent::buildRow($entity);
    return [
      'data' => [
        'label' => [
          'data' => [
            '#plain_text' => $entity->label(),
          ],
        ],
        'id' => [
          'data' => [
            '#plain_text' => $entity->id(),
          ],
        ],
        'type' => [
          'data' => [
            '#plain_text' => $entity->get('type'),
          ],
        ],
        'operations' => $row['operations'],
      ],
      '#attributes' => [
        'class' => [$entity->get('status') ? 'az-publication-ui-list-enabled' : 'az-publication-ui-list-disabled'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'label' => [
        'data' => $this->t('Label'),
        'field' => 'label',
        'specifier' => 'label',
        '#attributes' => [
          'class' => [
            RESPONSIVE_PRIORITY_LOW,
            'az-publication-type-label',
          ],
        ],
      ],
      'id' => [
        'data' => $this->t('Machine name'),
        'field' => 'id',
        'specifier' => 'id',
        '#attributes' => [
          'class' => [
            RESPONSIVE_PRIORITY_LOW,
            'az-publication-type-machine-name',
          ],
        ],
      ],
      'type' => [
        'data' => $this->t('Mapped CSL Type'),
        'field' => 'type',
        'specifier' => 'type',
        '#attributes' => [
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
    $list['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Tailor publication types to meet specific requirements and disable unused types to streamline the interface. For full documentation on how to use this feature, visit the <a href=":link" target="_blank">Quickstart documentation</a>.', [
        ':link' => 'https://quickstart.arizona.edu/node/220',
      ]),
      '#prefix' => '<div class="az-publication-type-description">',
      '#suffix' => '</div>',
    ];

    $list['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'table-filter',
          'js-show',
        ],
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
        '#rows' => [],
      ];

      if (!empty($entities[$status]) && is_array($entities[$status])) {
        foreach ($entities[$status] as $entity) {
          $row = $this->buildRow($entity);
          if (is_array($row)) {
            $list[$status]['table']['#rows'][$entity->id()] = $row;
          }
        }
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
