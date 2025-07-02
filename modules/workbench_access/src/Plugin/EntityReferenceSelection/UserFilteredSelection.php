<?php

namespace Drupal\workbench_access\Plugin\EntityReferenceSelection;

use Drupal\user\Plugin\EntityReferenceSelection\UserSelection;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\UserSectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides specific access control for the user entity type.
 *
 * @EntityReferenceSelection(
 *   id = "workbench_access:user",
 *   label = @Translation("Filtered user selection"),
 *   entity_types = {"user"},
 *   group = "workbench_access",
 *   weight = 1,
 *   base_plugin_label = @Translation("Workbench Access: Filtered user selection"),
 *   deriver = "\Drupal\workbench_access\Plugin\Deriver\UserFilteredSelectionDeriver",
 * )
 */
class UserFilteredSelection extends UserSelection {

  /**
   * Scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * User section storage.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return $instance
      ->setScheme($container->get('entity_type.manager')->getStorage('access_scheme')->load($plugin_definition['scheme']))
      ->setUserSectionStorage($container->get('workbench_access.user_section_storage'));
  }

  /**
   * Sets userSectionStorage.
   *
   * @param \Drupal\workbench_access\UserSectionStorageInterface $userSectionStorage
   *   New value for userSectionStorage.
   *
   * @return $this
   */
  public function setUserSectionStorage(UserSectionStorageInterface $userSectionStorage) {
    $this->userSectionStorage = $userSectionStorage;
    return $this;
  }

  /**
   * Sets access scheme.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   *
   * @return $this
   */
  public function setScheme(AccessSchemeInterface $scheme) {
    $this->scheme = $scheme;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    if (isset($this->configuration)) {
      $handler_settings = $this->configuration;
    }

    // Filter out the already referenced users.
    if (isset($handler_settings['filter']['section_id'])) {
      $id = $handler_settings['filter']['section_id'];
      $editors = $this->userSectionStorage->getEditors($this->scheme, $id);
      if (count($editors)) {
        $query->condition('uid', array_keys($editors), 'NOT IN');
      }
    }

    return $query;
  }

}
