<?php

namespace Drupal\flag\Controller;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\flag\FlagInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a entity list page for Flags.
 */
class FlagListBuilder extends DraggableListBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FlagListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'flags';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flag_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Flag');
    $header['flag_type'] = $this->t('Flag Type');
    $header['roles'] = $this->t('Roles');
    $header['bundles'] = $this->t('Entity Bundles');
    $header['global'] = $this->t('Scope');
    $header['status'] = $this->t('Status');

    return $header + parent::buildHeader();
  }

  /**
   * Creates a render array of roles that may use the flag.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   *
   * @return array
   *   A render array of flag roles for the entity.
   */
  protected function getFlagRoles(FlagInterface $flag) {
    $all_roles = [];

    $user_roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    foreach (array_keys($flag->actionPermissions()) as $perm) {
      $roles = array_filter($user_roles, fn(RoleInterface $role) => $role->hasPermission($perm));

      foreach ($roles as $rid => $role) {
        $all_roles[$rid] = $role->label();
      }
    }

    $out = implode(', ', $all_roles);

    if (empty($out)) {
      return [
        '#markup' => '<em>' . $this->t('None') . '</em>',
        '#allowed_tags' => ['em'],
      ];
    }

    return [
      '#markup' => rtrim($out, ', '),
    ];
  }

  /**
   * Gets the flag type label for the given flag.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   *
   * @return array
   *   A render array of the flag type label.
   */
  protected function getFlagType(FlagInterface $flag) {
    // Get the flaggable entity type definition.
    $flaggable_entity_type = $this->entityTypeManager->getDefinition($flag->getFlaggableEntityTypeId());

    return [
      '#markup' => $flaggable_entity_type->getLabel(),
    ];
  }

  /**
   * Generates a render array of the applicable bundles for the flag..
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   *
   * @return array
   *   A render array of the applicable bundles for the flag..
   */
  protected function getBundles(FlagInterface $flag) {
    $bundles = $flag->getBundles();

    if (empty($bundles)) {
      return [
        '#markup' => '<em>' . $this->t('All') . '</em>',
        '#allowed_tags' => ['em'],
      ];
    }

    return [
      '#markup' => implode(', ', $bundles),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\flag\FlagInterface $flag */
    $flag = $entity;

    $row['label'] = $flag->label();

    $row['flag_type'] = $this->getFlagType($flag);

    $row['roles'] = $this->getFlagRoles($flag);

    $row['bundles'] = $this->getBundles($flag);

    $row['global'] = [
      '#markup' => $flag->isGlobal() ? $this->t('Global') : $this->t('Personal'),
    ];

    $row['status'] = [
      '#markup' => $flag->status() ? $this->t('Enabled') : $this->t('Disabled'),
    ];

    return $row + parent::buildRow($flag);
  }

}
