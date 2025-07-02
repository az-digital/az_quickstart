<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;

/**
 * Tests for deleting a user and removing associated data.
 *
 * @group workbench_access
 */
class DeleteUserTest extends BrowserTestBase {

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
   * Tests that deleting a user clears their data from storage.
   */
  public function testUserDelete() {
    // Add page content type.
    $this->setUpContentType();

    // Set up a content type and menu scheme.
    $scheme = $this->setUpMenuScheme(['page'], ['main']);
    $user_storage = $this->container->get('workbench_access.user_section_storage');

    // Set up an editor.
    $editor = $this->setUpEditorUser();

    // Set up a second editor.
    $admin = $this->setUpAdminUser([
      'create page content',
      'edit any page content',
      'administer menu',
      'delete any page content',
      'use workbench access',
    ]);

    // Set up a menu link for this test.
    $base_link = MenuLinkContent::create([
      'title' => 'Link 1',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $base_link->save();

    // Add the first user to the base section.
    $user_storage->addUser($scheme, $editor, [$base_link->getPluginId()]);
    // Add the second user to the base section.
    $user_storage->addUser($scheme, $admin, [$base_link->getPluginId()]);

    // Get assigned users.
    $existing_users = $user_storage->getEditors($scheme, $base_link->getPluginId());

    // Assert that these are the same.
    $this->assertEquals([$editor->id(), $admin->id()], array_keys($existing_users));

    // Delete the first user.
    $editor->delete();

    // Get assigned users.
    $existing_users = $user_storage->getEditors($scheme, $base_link->getPluginId());

    // Assert that these are the same.
    $this->assertEquals([$admin->id()], array_keys($existing_users));
  }

}
