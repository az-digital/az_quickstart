<?php

namespace Drupal\Tests\Kernel\masquerade;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests permission config dependencies.
 *
 * @group masquerade
 */
class MasqueradeConfigDependencyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system', 'masquerade'];

  /**
   * Tests permission config dependencies.
   */
  public function testConfigDependencies() {
    $editor_role = Role::create([
      'id' => 'editor',
      'label' => 'Editor',
    ]);
    $editor_role->save();

    $admin_role = Role::create([
      'id' => 'admin',
      'label' => 'Admin',
    ]);
    $admin_role->grantPermission('masquerade as editor');
    $admin_role->save();

    $this->assertEquals(['masquerade'], $admin_role->getDependencies()['module']);
    $this->assertEquals(['user.role.editor'], $admin_role->getDependencies()['config']);

    // Create a role that grants the masquerade permission on itself.
    $recursion_role = Role::create([
      'id' => 'recursion',
      'label' => 'Recursion',
    ]);
    $recursion_role->save();
    // Adding this permission is not possible before saving the role.
    $recursion_role->grantPermission('masquerade as recursion');
    $recursion_role->save();

    $this->assertEquals(['masquerade'], $recursion_role->getDependencies()['module']);
    $this->assertEquals(['user.role.recursion'], $recursion_role->getDependencies()['config']);
  }

}
