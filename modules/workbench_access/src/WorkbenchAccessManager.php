<?php

namespace Drupal\workbench_access;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Defines a class for interacting with content and fields.
 */
class WorkbenchAccessManager extends DefaultPluginManager implements WorkbenchAccessManagerInterface {
  use StringTranslationTrait;

  /**
   * The access tree array.
   *
   * @var array
   */
  public static $tree;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * User section storage.
   *
   * @var \Drupal\workbench_access\UserSectionStorageInterface
   */
  protected $userSectionStorage;

  /**
   * Module config.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new WorkbenchAccessManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\workbench_access\UserSectionStorageInterface $user_section_storage
   *   User section storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entityTypeManager, UserSectionStorageInterface $user_section_storage, ConfigFactoryInterface $configFactory, AccountInterface $currentUser) {
    parent::__construct('Plugin/AccessControlHierarchy', $namespaces, $module_handler, 'Drupal\workbench_access\AccessControlHierarchyInterface', 'Drupal\workbench_access\Annotation\AccessControlHierarchy');

    $this->alterInfo('workbench_access_info');
    $this->setCacheBackend($cache_backend, 'workbench_access_plugins');
    $this->moduleHandler = $module_handler;
    $this->namespaces = $namespaces;
    $this->userSectionStorage = $user_section_storage;
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function checkTree(AccessSchemeInterface $scheme, array $entity_sections, array $user_sections) {
    $list = array_flip($user_sections);
    foreach ($entity_sections as $section) {
      // Simple check first: is there an exact match?
      if (isset($list[$section])) {
        return TRUE;
      }
      // Check for section on the tree.
      // Static cache to prevent looping on each request.
      if (!isset(self::$tree[$scheme->id()])) {
        self::$tree[$scheme->id()] = $scheme->getAccessScheme()->getTree();
      }
      foreach (self::$tree[$scheme->id()] as $info) {
        if (isset($list[$section]) && isset($info[$section])) {
          return TRUE;
        }
        // Recursive check for parents.
        if (!empty($info[$section]['parents'])) {
          $parents = array_flip($info[$section]['parents']);
          // Check for parents.
          foreach ($list as $uid => $data) {
            if (isset($parents[$uid])) {
              return TRUE;
            }
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getAllSections(AccessSchemeInterface $scheme, $root_only = FALSE) {
    $sections = [];
    foreach ($scheme->getAccessScheme()->getTree() as $root => $item) {
      if ($root_only) {
        $sections[] = $root;
      }
      else {
        foreach ($item as $id => $data) {
          $sections[] = $id;
        }
      }
    }
    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  public function userInAll(AccessSchemeInterface $scheme, ?AccountInterface $account = NULL) {
    // Get the information from the account.
    if (!$account) {
      $account = $this->currentUser;
    }
    if ($account->hasPermission('bypass workbench access')) {
      return TRUE;
    }
    else {
      // If the user is assigned to all the top-level sections, treat as admin.
      $user_sections = $this->userSectionStorage->getUserSections($scheme, $account);
      foreach (array_keys($scheme->getAccessScheme()->getTree()) as $root) {
        if (!in_array($root, $user_sections)) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

}
