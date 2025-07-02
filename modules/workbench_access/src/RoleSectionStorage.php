<?php

namespace Drupal\workbench_access;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Defines a role-section storage that uses the State API.
 */
class RoleSectionStorage implements RoleSectionStorageInterface {

  use DependencySerializationTrait;

  /**
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RoleSectionStorage object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(StateInterface $state, EntityTypeManagerInterface $entityTypeManager) {
    $this->state = $state;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Gets section storage.
   *
   * @return \Drupal\workbench_access\SectionAssociationStorageInterface
   *   Section storage.
   */
  protected function sectionStorage() {
    // The entity build process takes place too early in the call stack and we
    // have test fails if we add this to the __construct().
    return $this->entityTypeManager->getStorage('section_association');
  }

  /**
   * {@inheritdoc}
   */
  public function addRole(AccessSchemeInterface $scheme, $role_id, array $sections = []) {
    foreach ($sections as $id) {
      if ($section_association = $this->sectionStorage()->loadSection($scheme->id(), $id)) {
        if ($new_values = $section_association->getCurrentRoleIds()) {
          $new_values[] = $role_id;
          $section_association->set('role_id', array_unique($new_values));
        }
        else {
          $section_association->set('role_id', [$role_id]);
        }
        $section_association->setNewRevision();
      }
      else {
        $values = [
          'access_scheme' => $scheme->id(),
          'section_id' => $id,
          'role_id' => [$role_id],
        ];
        $section_association = $this->sectionStorage()->create($values);
      }
      $section_association->save();
      // @phpstan-ignore-next-line
      \Drupal::service('workbench_access.user_section_storage')->resetCache($scheme);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeRole(AccessSchemeInterface $scheme, $role_id, array $sections = []) {
    foreach ($sections as $id) {
      $new_values = [];
      if ($section_association = $this->sectionStorage()->loadSection($scheme->id(), $id)) {
        if ($values = $section_association->getCurrentRoleIds()) {
          foreach ($values as $value) {
            // Deliberate use of != because some ids are strings.
            if ($value != $role_id) {
              $new_values[] = $value;
            }
          }
          $section_association->set('role_id', array_unique($new_values));
        }
        $section_association->save();
      }
      // @phpstan-ignore-next-line
      \Drupal::service('workbench_access.user_section_storage')->resetCache($scheme);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRoleSections(AccessSchemeInterface $scheme, ?AccountInterface $account = NULL) {
    $sections = [];
    if ($account) {
      $results = $this->sectionStorage()->loadByProperties([
        'role_id' => $account->getRoles(),
        'access_scheme' => $scheme->id(),
      ]);
      foreach ($results as $result) {
        $sections[] = $result->get('section_id')->value;
      }
    }
    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  public function getPotentialRoles($id) {
    $list = [];
    $roles = $this->roleStorage()->loadMultiple();
    foreach ($roles as $rid => $role) {
      $list[$rid] = $role->label();
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getPotentialRolesFiltered($id) {
    $list = [];
    /** @var \Drupal\user\RoleInterface $roles */
    $roles = $this->roleStorage()->loadMultiple();
    foreach ($roles as $rid => $role) {
      if ($role->hasPermission('use workbench access')) {
        $list[$rid] = $role->label();
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles(AccessSchemeInterface $scheme, $id) {
    $roles = [];
    $query = $this->sectionStorage()->getAggregateQuery()
      ->condition('access_scheme', $scheme->id())
      ->condition('section_id', $id)
      ->accessCheck(FALSE)
      ->groupBy('role_id.target_id')->execute();
    $rids = array_column($query, 'role_id_target_id');
    if (!empty(current($rids))) {
      $roles = $this->roleStorage()->loadMultiple($rids);
    }
    // @todo filter by permission?
    return array_keys($roles);
  }

  /**
   * Saves the role sections for a given role ID.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param string $role_id
   *   The role ID.
   * @param array $settings
   *   Sections for the role.
   *
   * @todo refactor.
   */
  public function saveRoleSections(AccessSchemeInterface $scheme, $role_id, array $settings = []) {

  }

  /**
   * Delete the saved sections for this role.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param string $role_id
   *   The role ID.
   */
  public function deleteRoleSections(AccessSchemeInterface $scheme, $role_id) {
    $section_ids = [];
    if ($sections = $this->sectionStorage()->loadByProperties(['role_id' => $role_id])) {
      foreach ($sections as $section) {
        $section_ids[] = $section->get('section_id')->value;
      }
      $this->removeRole($scheme, $role_id, $section_ids);
    }
  }

  /**
   * Gets user_role storage handler.
   *
   * The entity build process takes place too early in the call stack so we
   * end up with a stale reference to the user_role storage handler if we do
   * this in the constructor.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   Role storage.
   */
  protected function roleStorage() {
    return $this->entityTypeManager->getStorage('user_role');
  }

}
