<?php

namespace Drupal\devel\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\Core\Url;
use Drupal\devel\DevelDumperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for the entity types info page.
 */
class EntityTypeInfoController extends ControllerBase {

  /**
   * The dumper service.
   */
  protected DevelDumperManagerInterface $dumper;

  /**
   * The installed entity definition repository service.
   */
  protected EntityLastInstalledSchemaRepositoryInterface $entityLastInstalledSchemaRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->dumper = $container->get('devel.dumper');
    $instance->entityLastInstalledSchemaRepository = $container->get('entity.last_installed_schema.repository');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->stringTranslation = $container->get('string_translation');

    return $instance;
  }

  /**
   * Builds the entity types overview page.
   *
   * @return array
   *   A render array as expected by the renderer.
   */
  public function entityTypeList(): array {
    $headers = [
      $this->t('ID'),
      $this->t('Name'),
      $this->t('Provider'),
      $this->t('Class'),
      $this->t('Operations'),
    ];

    $rows = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $row['id'] = [
        'data' => $entity_type->id(),
        'filter' => TRUE,
      ];
      $row['name'] = [
        'data' => $entity_type->getLabel(),
        'filter' => TRUE,
      ];
      $row['provider'] = [
        'data' => $entity_type->getProvider(),
        'filter' => TRUE,
      ];
      $row['class'] = [
        'data' => $entity_type->getClass(),
        'filter' => TRUE,
      ];
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => [
          'devel' => [
            'title' => $this->t('Devel'),
            'url' => Url::fromRoute('devel.entity_info_page.detail', ['entity_type_id' => $entity_type_id]),
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 700,
                'minHeight' => 500,
              ]),
            ],
          ],
          'fields' => [
            'title' => $this->t('Fields'),
            'url' => Url::fromRoute('devel.entity_info_page.fields', ['entity_type_id' => $entity_type_id]),
            'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 700,
                'minHeight' => 500,
              ]),
            ],
          ],
        ],
      ];

      $rows[$entity_type_id] = $row;
    }

    ksort($rows);

    $output['entities'] = [
      '#type' => 'devel_table_filter',
      '#filter_label' => $this->t('Search'),
      '#filter_placeholder' => $this->t('Enter entity type id, provider or class'),
      '#filter_description' => $this->t('Enter a part of the entity type id, provider or class to filter by.'),
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No entity types found.'),
      '#sticky' => TRUE,
      '#attributes' => [
        'class' => ['devel-entity-type-list'],
      ],
    ];

    return $output;
  }

  /**
   * Returns a render array representation of the entity type.
   *
   * @param string $entity_type_id
   *   The name of the entity type to retrieve.
   *
   * @return array
   *   A render array containing the entity type.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the requested entity type is not defined.
   */
  public function entityTypeDetail($entity_type_id): array {
    if (!$entity_type = $this->entityTypeManager->getDefinition($entity_type_id, FALSE)) {
      throw new NotFoundHttpException();
    }

    return $this->dumper->exportAsRenderable($entity_type, $entity_type_id);
  }

  /**
   * Returns a render array representation of the entity type field definitions.
   *
   * @param string $entity_type_id
   *   The name of the entity type to retrieve.
   *
   * @return array
   *   A render array containing the entity type field definitions.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the requested entity type is not defined.
   */
  public function entityTypeFields($entity_type_id): array {
    if (!$this->entityTypeManager->getDefinition($entity_type_id, FALSE)) {
      throw new NotFoundHttpException();
    }

    $field_storage_definitions = $this->entityLastInstalledSchemaRepository->getLastInstalledFieldStorageDefinitions($entity_type_id);
    return $this->dumper->exportAsRenderable($field_storage_definitions, $entity_type_id);
  }

}
