<?php

namespace Drupal\Tests\workbench_access\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\UiHelperTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;

/**
 * Tests workbench_access integration with node access via menu plugin.
 *
 * @group workbench_access
 */
class NodeMenuTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;
  use UiHelperTrait;
  use UserCreationTrait;
  use WorkbenchAccessTestTrait;

  /**
   * Access menu.
   *
   * @var \Drupal\system\MenuInterface
   */
  protected $menu;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'text',
    'link',
    'system',
    'menu_link_content',
    'menu_ui',
    'user',
    'workbench_access',
    'field',
    'filter',
    'options',
  ];

  /**
   * Access handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;


  /**
   * Access control scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * User section storage.
   *
   * @var \Drupal\workbench_access\UserSectionStorage
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installConfig(['filter', 'node', 'workbench_access', 'system']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('section_association');
    $this->installSchema('system', ['sequences']);
    $node_type = $this->createContentType(['type' => 'page']);
    $this->createContentType(['type' => 'article']);
    // This is created by system module.
    $this->menu = Menu::load('main');
    $this->accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('node');
    $node_type->setThirdPartySetting('menu_ui', 'available_menus', ['main']);
    $node_type->save();
    $this->scheme = $this->setupMenuScheme([$node_type->id()], ['main']);
    $this->userStorage = \Drupal::service('workbench_access.user_section_storage');
  }

  /**
   * Test create access integration.
   */
  public function testCreateAccess() {
    // The first user in a kernel test gets UID 1, so we need to make sure we're
    // not testing with that user.
    $this->createUser();
    // Create a menu link.
    $link = MenuLinkContent::create([
      'title' => 'Home',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $link->save();
    // Create two users with equal permissions but assign one of them to the
    // section.
    $permissions = [
      'create page content',
      'create article content',
      'edit any page content',
      'access content',
      'delete any page content',
      'administer nodes',
    ];
    $allowed_editor = $this->createUser($permissions);
    $allowed_editor->save();
    $this->userStorage->addUser($this->scheme, $allowed_editor, [$link->getPluginId()]);

    $editor_with_no_access = $this->createUser($permissions);
    $permissions[] = 'bypass workbench access';
    $editor_with_bypass_access = $this->createUser($permissions);

    $this->assertTrue($this->accessHandler->createAccess('page', $allowed_editor));
    $this->assertFalse($this->accessHandler->createAccess('page', $editor_with_no_access));
    $this->assertTrue($this->accessHandler->createAccess('page', $editor_with_bypass_access));

    // Non access controlled bundles should be allowed.
    $this->assertTrue($this->accessHandler->createAccess('article', $allowed_editor));
    $this->assertTrue($this->accessHandler->createAccess('article', $editor_with_no_access));
    $this->assertTrue($this->accessHandler->createAccess('article', $editor_with_bypass_access));
  }

  /**
   * Test edit access integration.
   */
  public function testEditAccess() {
    // The first user in a kernel test gets UID 1, so we need to make sure we're
    // not testing with that user.
    $this->createUser();
    // Create a menu link.
    $link = MenuLinkContent::create([
      'title' => 'Home',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => 'main',
    ]);
    $link->save();
    // Create two users with equal permissions but assign one of them to the
    // section.
    $permissions = [
      'create page content',
      'create article content',
      'edit any page content',
      'edit any article content',
      'delete any article content',
      'access content',
      'delete any page content',
    ];
    $allowed_editor = $this->createUser($permissions);
    $allowed_editor->save();
    $this->userStorage->addUser($this->scheme, $allowed_editor, [$link->getPluginId()]);

    $editor_with_no_access = $this->createUser($permissions);

    // Test a node that is not assigned to a section. Both should be allowed
    // because we do not assert access control by default.
    $node1 = $this->createNode(['type' => 'page', 'title' => 'foo']);
    $this->assertTrue($this->accessHandler->access($node1, 'update', $allowed_editor));
    $this->assertTrue($this->accessHandler->access($node1, 'update', $editor_with_no_access));
    $this->assertTrue($this->accessHandler->access($node1, 'delete', $allowed_editor));
    $this->assertTrue($this->accessHandler->access($node1, 'delete', $editor_with_no_access));

    // Create a node that is a child of the section.
    $node2 = $this->createNode(['type' => 'page', 'title' => 'bar']);
    _menu_ui_node_save($node2, [
      'title' => 'bar',
      'menu_name' => 'main',
      'description' => 'view bar',
      'parent' => $link->getPluginId(),
    ]);
    $this->assertTrue($this->accessHandler->access($node2, 'update', $allowed_editor));
    $this->assertFalse($this->accessHandler->access($node2, 'update', $editor_with_no_access));
    $this->assertTrue($this->accessHandler->access($node2, 'delete', $allowed_editor));
    $this->assertFalse($this->accessHandler->access($node2, 'delete', $editor_with_no_access));

    // With strict checking, nodes that are not assigned to a section return
    // false.
    $this->config('workbench_access.settings')
      ->set('deny_on_empty', 1)
      ->save();

    // Test a new node because the results for $node1 are cached.
    $node3 = $this->createNode(['type' => 'page', 'title' => 'baz']);
    $this->assertFalse($this->accessHandler->access($node3, 'update', $allowed_editor));
    $this->assertFalse($this->accessHandler->access($node3, 'update', $editor_with_no_access));

    $node1 = $this->createNode(['type' => 'article', 'title' => 'foo']);
    $this->assertTrue($this->accessHandler->access($node1, 'update', $allowed_editor));
    $this->assertTrue($this->accessHandler->access($node1, 'update', $editor_with_no_access));
    $this->assertTrue($this->accessHandler->access($node1, 'delete', $allowed_editor));
    $this->assertTrue($this->accessHandler->access($node1, 'delete', $editor_with_no_access));
  }

}
