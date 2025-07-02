<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Tests for deleting a role and removing associated data.
 *
 * @group workbench_access
 */
class DeleteRoleTest extends BrowserTestBase {

  use WorkbenchAccessTestTrait;

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workbench_access',
    'node',
    'menu_ui',
    'link',
    'menu_link_content',
    'options',
    'user',
    'system',
  ];

  /**
   * Tests that deleting a role clears their data from storage.
   */
  public function testRoleDelete() {
    $this->setUpContentType();

    $scheme = $this->setUpMenuScheme(['page'], ['main']);

    $base_link = MenuLinkContent::create([
      'title' => 'Link 1',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $base_link->save();

    $section_id = $base_link->getPluginId();

    $this->setUpRole('role_a');
    $this->setUpRole('role_b');

    /** @var \Drupal\workbench_access\RoleSectionStorageInterface $role_section_storage */
    $role_section_storage = $this->container->get('workbench_access.role_section_storage');

    $role_section_storage->addRole($scheme, 'role_a', [$section_id]);
    $role_section_storage->addRole($scheme, 'role_b', [$section_id]);

    $assigned_roles = $this->getStoredRoles($scheme, $section_id);

    $this->assertCount(2, $assigned_roles, 'The test roles are not assigned to the section.');

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $role_storage = $entity_type_manager->getStorage('user_role');
    $role_b = $role_storage->load('role_b');
    $role_b->delete();

    $assigned_roles = $this->getStoredRoles($scheme, $section_id);

    $this->assertCount(1, $assigned_roles, 'The test roles are not assigned to the section.');
  }

  /**
   * Sets up role that has access to content.
   *
   * @param string $name
   *   The machine name for the role: the role id.
   */
  public function setUpRole($name) {
    $this->createRole([
      'access administration pages',
      'create page content',
      'edit any page content',
      'administer menu',
      'delete any page content',
      'use workbench access',
    ], $name);
  }

  /**
   * Tests the storage of role assignments.
   *
   * This method is a version of RoleSectionStorage::getRoles() that does not
   * ensure the roles still exist.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param string $id
   *   The section id.
   *
   * @return array
   *   An array of role ids
   */
  public function getStoredRoles(AccessSchemeInterface $scheme, $id) {
    $query = \Drupal::entityTypeManager()->getStorage('section_association')->getAggregateQuery()
      ->condition('access_scheme', $scheme->id())
      ->condition('section_id', $id)
      ->accessCheck(FALSE)
      ->groupBy('role_id.target_id')->execute();
    $rids = array_column($query, 'role_id_target_id');
    return array_keys($rids);
  }

}
