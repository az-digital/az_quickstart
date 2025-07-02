<?php

namespace Drupal\field_group_migrate\Plugin\migrate\destination\d7;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This class imports one field_group of an entity form display.
 *
 * @MigrateDestination(
 *   id = "d7_field_group"
 * )
 */
class FieldGroup extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The entity views displays repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    EntityDisplayRepositoryInterface $entity_display_repository,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $values = [];
    // array_intersect_key() won't work because the order is important because
    // this is also the return value.
    foreach (array_keys($this->getIds()) as $id) {
      $values[$id] = $row->getDestinationProperty($id);
    }

    $entity = $this->getEntity($values['entity_type'], $values['bundle'], $values['mode'], $values['type']);
    $settings = $row->getDestinationProperty('settings');
    $settings += [
      'region' => 'content',
    ];
    $entity->setThirdPartySetting('field_group', $row->getDestinationProperty('group_name'), $settings);
    if (isset($settings['format_type']) && ($settings['format_type'] == 'hidden')) {
      $entity->unsetThirdPartySetting('field_group', $row->getDestinationProperty('group_name'));
    }
    $entity->save();

    return array_values($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['entity_type']['type'] = 'string';
    $ids['bundle']['type'] = 'string';
    $ids['mode']['type'] = 'string';
    $ids['type']['type'] = 'string';
    $ids['group_name']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    $entity = $this->getEntity($destination_identifier['entity_type'], $destination_identifier['bundle'], $destination_identifier['mode'], $destination_identifier['type']);
    if (!$entity->isNew()) {
      $entity->unsetThirdPartySetting('field_group', $destination_identifier['group_name']);
      $entity->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    // This is intentionally left empty.
    return $migration;
  }

  /**
   * Gets the entity.
   *
   * @param string $entity_type
   *   The entity type to retrieve.
   * @param string $bundle
   *   The entity bundle.
   * @param string $mode
   *   The display mode.
   * @param string $type
   *   The destination type.
   *
   * @return \Drupal\Core\Entity\Display\EntityDisplayInterface
   *   The entity display object.
   */
  protected function getEntity($entity_type, $bundle, $mode, $type) {
    $function = $type == 'entity_form_display' ? 'getFormDisplay' : 'getViewDisplay';
    return $this->entityDisplayRepository->$function($entity_type, $bundle, $mode);
  }

}
