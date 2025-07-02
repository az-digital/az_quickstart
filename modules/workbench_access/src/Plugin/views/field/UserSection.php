<?php

namespace Drupal\workbench_access\Plugin\views\field;

use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;
use Drupal\views\ResultRow;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\UserSectionStorageInterface;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to present the section assigned to the user.
 *
 * @ViewsField("workbench_access_user_section")
 */
class UserSection extends Section implements MultiItemsFieldHandlerInterface {

  /**
   * Scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * Manager.
   *
   * @var \Drupal\workbench_access\WorkbenchAccessManagerInterface
   */
  protected $manager;

  /**
   * User storage.
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
    $instance->additional_fields['uid'] = 'uid';
    $instance->aliases['uid'] = 'uid';
    return $instance->setScheme($container->get('entity_type.manager')->getStorage('access_scheme')->load($configuration['scheme']))
      ->setManager($container->get('plugin.manager.workbench_access.scheme'))
      ->setUserSectionStorage($container->get('workbench_access.user_section_storage'));
  }

  /**
   * Sets manager.
   *
   * @param \Drupal\workbench_access\WorkbenchAccessManagerInterface $manager
   *   Manager.
   *
   * @return $this
   */
  public function setManager(WorkbenchAccessManagerInterface $manager) {
    $this->manager = $manager;
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
   * Sets user section storage.
   *
   * @param \Drupal\workbench_access\UserSectionStorageInterface $userSectionStorage
   *   User section storage.
   *
   * @return $this
   */
  public function setUserSectionStorage(UserSectionStorageInterface $userSectionStorage) {
    $this->userSectionStorage = $userSectionStorage;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $this->items = [];
    $user = $this->getEntity($values);
    $all = $this->scheme->getAccessScheme()->getTree();
    if ($this->manager->userInAll($this->scheme, $user)) {
      $sections = $this->manager->getAllSections($this->scheme, TRUE);
    }
    else {
      $sections = $this->userSectionStorage->getUserSections($this->scheme, $user);
    }
    foreach ($sections as $id) {
      foreach ($all as $data) {
        if (isset($data[$id])) {
          // Check for link.
          if ($this->options['make_link'] && isset($data[$id]['path'])) {
            $this->items[$id]['path'] = $data[$id]['path'];
            $this->items[$id]['make_link'] = TRUE;
          }
          $this->items[$id]['value'] = $this->sanitizeValue($data[$id]['label']);
        }
      }
    }
    return $this->items;
  }

}
