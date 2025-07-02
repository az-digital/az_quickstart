<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;

/**
 * Tests for the node form.
 *
 * @group workbench_access
 */
class NodeFormMenuTest extends BrowserTestBase {

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
   * Tests that the user can see all valid options on the node form.
   */
  public function testNodeForm() {
    // Set up a content type and menu scheme.
    $node_type_values = [
      'type' => 'page',
      'third_party_settings' => [
        'menu_ui' => [
          'available_menus' => [
            'main',
            'account',
          ],
        ],
      ],
    ];

    $this->createContentType($node_type_values);
    $scheme = $this->setUpMenuScheme(['page'], ['main', 'account']);

    $user_storage = $this->container->get('workbench_access.user_section_storage');
    $role_storage = $this->container->get('workbench_access.role_section_storage');

    // Set up an editor and log in as them.
    $editor = $this->setUpEditorUser();
    $this->drupalLogin($editor);

    // Set up some roles and menu links for this test.
    $staff_link = MenuLinkContent::create([
      'title' => 'Link 1',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $staff_link->save();
    $super_staff_link = MenuLinkContent::create([
      'title' => 'Link 2',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $super_staff_link->save();
    $base_link = MenuLinkContent::create([
      'title' => 'Link 3',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $base_link->save();
    $deny_link = MenuLinkContent::create([
      'title' => 'Link 4',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $deny_link->save();
    $user_link = MenuLinkContent::create([
      'title' => 'User link 1',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'account',
    ]);
    $user_link->save();

    // Add the user to the base section.
    $user_storage->addUser($scheme, $editor, [$base_link->getPluginId()]);
    $expected = [$editor->id()];
    $existing_users = $user_storage->getEditors($scheme, $base_link->getPluginId());
    $this->assertEquals($expected, array_keys($existing_users));

    $expected = [$base_link->getPluginId()];
    $existing = $user_storage->getUserSections($scheme, $editor);
    $this->assertEquals($expected, $existing);

    $staff_rid = $this->createRole([], 'staff');
    $super_staff_rid = $this->createRole([], 'super_staff');
    // Set the role -> menu mapping. But don't add this user.
    $role_storage->addRole($scheme, $staff_rid, [$staff_link->getPluginId()]);
    $role_storage->addRole($scheme, $super_staff_rid, [$super_staff_link->getPluginId()]);

    $this->drupalGet('node/add/page');

    // Check data loading.
    $expected = [$base_link->getPluginId()];
    $existing = $user_storage->getUserSections($scheme, $editor);
    $this->assertEquals($expected, $existing);

    // Check form handling.
    $web_assert = $this->assertSession();
    $select = $web_assert->elementExists('named',
      ['select', 'menu[menu_parent]']);
    // Throughout this test we use ::elementExists' and ::elementNotExists for
    // testing the presence/absence of options instead of ::optionExists and
    // ::optionNotExists because the later uses the 'named_exact' selector type,
    // which requires us to include the '--' prefix used in the menu parent
    // selector to signify depth.
    $web_assert->elementExists('named', ['option', $base_link->label()], $select);
    // Assert we can't see the other options yet.
    $web_assert->elementNotExists('named', ['option', $staff_link->label()], $select);
    $web_assert->elementNotExists('named',
      ['option', $super_staff_link->label()], $select);

    // Add the staff role and check the option exists.
    $editor->addRole($staff_rid);
    $editor->save();
    $user_storage->resetCache($scheme, $editor->id());
    $this->container->get('entity_type.manager')->getStorage('user')->resetCache();

    $expected = [
      $base_link->getPluginId(),
      $staff_link->getPluginId(),
    ];
    $existing = $user_storage->getUserSections($scheme, $editor);
    $this->assertEquals($expected, $existing);

    $this->drupalGet('node/add/page');
    $web_assert->elementExists('named', ['option', $base_link->label()], $select);
    $web_assert->elementExists('named', ['option', $staff_link->label()], $select);
    $web_assert->elementNotExists('named',
      ['option', $super_staff_link->label()], $select);

    // Add the super staff role and check both options exist.
    $editor->addRole($super_staff_rid);
    $editor->save();
    $user_storage->resetCache($scheme, $editor->id());

    $expected = [
      $base_link->getPluginId(),
      $staff_link->getPluginId(),
      $super_staff_link->getPluginId(),
    ];
    $existing = $user_storage->getUserSections($scheme, $editor);
    $this->assertEquals($expected, $existing);

    $this->drupalGet('node/add/page');
    $web_assert->elementExists('named', ['option', $base_link->label()], $select);
    $web_assert->elementExists('named', ['option', $staff_link->label()], $select);
    $web_assert->elementExists('named', ['option', $super_staff_link->label()], $select);

    // Add the user to the account menu section.
    $user_storage->addUser($scheme, $editor, ['account']);
    $expected2 = [
      'account',
      $base_link->getPluginId(),
      $staff_link->getPluginId(),
      $super_staff_link->getPluginId(),
    ];
    $existing2 = $user_storage->getUserSections($scheme, $editor);
    $this->assertEquals(sort($expected2), sort($existing2));

    $this->drupalGet('node/add/page');
    $web_assert->elementExists('named', ['option', 'account:'], $select);

    // Explicit testing for issue
    // https://www.drupal.org/project/workbench_access/issues/3024159
    // Add the user to the root menu section.
    $user_storage->addUser($scheme, $editor, ['main']);
    $this->drupalGet('node/add/page');
    $web_assert->elementExists('named', ['option', 'main:'], $select);
    $web_assert->elementExists('named', ['option', $base_link->label()], $select);
    $web_assert->elementExists('named', ['option', $staff_link->label()], $select);
    $web_assert->elementExists('named', ['option', $super_staff_link->label()], $select);

    // Save the node.
    $edit['title[0][value]'] = 'Test node';
    $edit['menu[title]'] = 'Test node';
    $edit['menu[menu_parent]'] = 'main:' . $base_link->getPluginId();
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, 'Save');

    $this->drupalGet('node/1/edit');
    $web_assert->elementExists('named', ['option', 'main:'], $select);
    $web_assert->elementExists('named', ['option', $base_link->label()], $select);
    $web_assert->elementExists('named', ['option', $staff_link->label()], $select);
    $web_assert->elementExists('named', ['option', $super_staff_link->label()], $select);
    // May not declare self as parent.
    $web_assert->elementNotExists('named', ['option', 'Test node'], $select);

    // Explicit test for menu default value not being available.
    // https://www.drupal.org/project/workbench_access/issues/2988119.
    // Set the default value to a menu item this user cannot access.
    $node_type_config = $this->config('node.type.page');
    $node_type_config->set('third_party_settings.menu_ui.parent', 'main:' . $deny_link->getPluginId());
    $node_type_config->save();

    // Remove user from the main section.
    // Add the user to the root menu section.
    $user_storage->removeUser($scheme, $editor, ['main']);

    $expected3 = [
      'account',
      $base_link->getPluginId(),
      $staff_link->getPluginId(),
      $super_staff_link->getPluginId(),
    ];
    $existing3 = $user_storage->getUserSections($scheme, $editor);
    $this->assertEquals(sort($expected3), sort($existing3));

    $this->drupalGet('node/add/page');
    $web_assert->elementNotExists('named', ['option', 'main:'], $select);
    $web_assert->elementExists('named', ['option', $base_link->label()], $select);
    $web_assert->elementExists('named', ['option', $staff_link->label()], $select);
    $web_assert->elementExists('named', ['option', $super_staff_link->label()], $select);
    $web_assert->elementNotExists('named', ['option', $deny_link->label()], $select);
    $web_assert->responseContains('-- ' . $staff_link->label() . '</option>');
  }

}
