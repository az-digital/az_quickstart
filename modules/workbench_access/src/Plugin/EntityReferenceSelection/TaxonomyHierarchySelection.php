<?php

namespace Drupal\workbench_access\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Plugin\EntityReferenceSelection\TermSelection;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\UserSectionStorageInterface;
use Drupal\workbench_access\WorkbenchAccessManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides specific access control for the taxonomy_term entity type.
 *
 * @EntityReferenceSelection(
 *   id = "workbench_access:taxonomy_term",
 *   label = @Translation("Restricted Taxonomy Term selection"),
 *   entity_types = {"taxonomy_term"},
 *   group = "workbench_access",
 *   weight = 1,
 *   base_plugin_label = @Translation("Workbench Access: Restricted term selection"),
 *   deriver = "\Drupal\workbench_access\Plugin\Deriver\TaxonomyHierarchySelectionDeriver",
 * )
 * @todo Investigate if this can be enforced in the field settings instead of
 *   via an alter hook.
 */
class TaxonomyHierarchySelection extends TermSelection {

  /**
   * Scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
      ->setCurrentUser($container->get('current_user'))
      ->setUserSectionStorage($container->get('workbench_access.user_section_storage'));
  }

  /**
   * Sets currentUser.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   New value for currentUser.
   *
   * @return $this
   */
  public function setCurrentUser(AccountInterface $currentUser) {
    $this->currentUser = $currentUser;
    return $this;
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
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    // Get the base options list from the normal handler. We will filter later.
    if ($match || $limit) {
      $options = parent::getReferenceableEntities($match, $match_operator, $limit);
    }
    else {
      $options = [];

      $bundles = $this->entityTypeBundleInfo->getBundleInfo('taxonomy_term');
      $bundle_names = array_keys($bundles);

      // If we have specific handler settings, use them.
      if (isset($this->configuration['handler_settings'])) {
        $handler_settings = $this->configuration['handler_settings'];
        $bundle_names = $handler_settings['target_bundles'];
      }

      foreach ($bundle_names as $bundle) {
        if ($vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($bundle)) {
          if ($terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary->id(), 0, NULL, TRUE)) {
            foreach ($terms as $term) {
              $options[$vocabulary->id()][$term->id()] = str_repeat('-', $term->depth) . Html::escape($this->entityRepository->getTranslationFromContext($term)->label());
            }
          }
        }
      }
    }
    // Now, filter the options by permission.
    // If assigned to the top level or a superuser, no alteration.
    if ($this->currentUser->hasPermission('bypass workbench access')) {
      return $options;
    }
    // Check each section for access.
    $user_sections = $this->userSectionStorage->getUserSections($this->scheme);
    foreach ($options as $key => $values) {
      if (WorkbenchAccessManager::checkTree($this->scheme, [$key], $user_sections)) {
        continue;
      }
      else {
        foreach ($values as $id => $value) {
          if (!WorkbenchAccessManager::checkTree($this->scheme, [$id], $user_sections)) {
            unset($options[$key][$id]);
          }
        }
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $valid = [];
    if ($allowed = $this->getReferenceableEntities()) {
      foreach ($ids as $id) {
        foreach ($allowed as $item) {
          if (isset($item[$id])) {
            $valid[$id] = $id;
            break;
          }
        }
      }
    }
    return $valid;
  }

}
