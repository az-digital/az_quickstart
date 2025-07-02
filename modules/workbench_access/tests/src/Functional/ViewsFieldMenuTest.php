<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Defines a class for testing workbench access views.
 *
 * @group workbench_access
 */
class ViewsFieldMenuTest extends BrowserTestBase {

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Test links.
   *
   * @var \Drupal\menu_link_content\MenuLinkContentInterface[]
   */
  protected $links = [];

  /**
   * Test nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = [];

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user2;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workbench_access',
    'views',
    'node',
    'taxonomy',
    'menu_link_content',
    'menu_ui',
    'system',
    'user',
    'filter',
    'workbench_access_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create some sections and some nodes in them.
    $sections = [
      'Some section',
      'Another section',
      'More sections',
    ];
    foreach ($sections as $section) {
      $this->links[$section] = MenuLinkContent::create([
        'title' => $section,
        'link' => [['uri' => 'route:<front>']],
        'menu_name' => 'main',
      ]);
      $this->links[$section]->save();
      foreach ([' node 1', ' node 2'] as $stub) {
        $title = $section . $stub;
        $this->nodes[$title] = Node::create([
          'type' => 'article',
          'title' => $title,
          'status' => 1,
        ]);
        $this->nodes[$title]->save();
        _menu_ui_node_save($this->nodes[$title], [
          'title' => $title,
          'menu_name' => 'main',
          'description' => 'view bar',
          'parent' => $this->links[$section]->getPluginId(),
        ]);
      }
    }

    // Create a user who can access content etc.
    $permissions = [
      'create article content',
      'edit any article content',
      'access content',
      'delete any article content',
      'administer nodes',
      'use workbench access',
      'access user profiles',
    ];
    $this->user = $this->createUser($permissions);

    $user_storage = \Drupal::service('workbench_access.user_section_storage');
    $scheme_storage = \Drupal::service('entity_type.manager')->getStorage('access_scheme');

    $scheme = $scheme_storage->load('menu');

    $ids = array_values(array_map(function (MenuLinkContentInterface $link) {
      return $link->getPluginId();
    }, $this->links));
    $user_storage->addUser($scheme, $this->user, $ids);

    // Check data loading.
    $expected = sort($ids);
    $existing = $user_storage->getUserSections($scheme, $this->user);
    $this->assertEquals($expected, sort($existing));

    $this->user2 = $this->createUser($permissions);
    $ids = [reset($this->links)->getPluginId()];
    $user_storage->addUser($scheme, $this->user2, $ids);

    // Check data loading.
    $expected = sort($ids);
    $existing = $user_storage->getUserSections($scheme, $this->user2);
    $this->assertEquals($expected, sort($existing));
  }

  /**
   * Tests field and filter.
   */
  public function testFieldAndFilter() {
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/content/sections/menu');
    $assert = $this->assertSession();
    foreach ($this->links as $section => $link) {
      $row = $assert->elementExists('css', '.views-row:contains("' . $link->label() . '")');
      $assert->pageTextContains($section . ' node 1');
    }
    // Now filter the page.
    $this->drupalGet('admin/content/sections/menu', [
      'query' => [
        'workbench_access_section' => $this->links['Some section']->getPluginId(),
      ],
    ]);
    $assert->pageTextContains('Some section node 1');
    $assert->pageTextContains('Some section node 2');
    $assert->elementNotExists('css', '.views-row:contains("Another section")');
    $assert->elementNotExists('css', '.views-row:contains("More sections")');

    $this->drupalGet('admin/people/sections/menu');
    $row = $assert->elementExists('css', '.views-row:contains("' . $this->user->label() . '")');

    // User 1 has all sections.
    foreach ($this->links as $section => $link) {
      $assert->elementExists('css', '.views-row:contains("' . $section . '")', $row);
    }

    // User 2 only has one.
    $row = $assert->elementExists('css', '.views-row:contains("' . $this->user2->label() . '")');
    $assert->elementExists('css', '.views-row:contains("Some section")', $row);

    // Now filter.
    $this->drupalGet('admin/people/sections/menu', [
      'query' => [
        'section' => $this->links['Some section']->getPluginId(),
      ],
    ]);
    $assert->elementExists('css', '.views-row:contains("' . $this->user->label() . '")');
    $assert->elementExists('css', '.views-row:contains("' . $this->user2->label() . '")');
    $this->drupalGet('admin/people/sections/menu', [
      'query' => [
        'section' => $this->links['Another section']->getPluginId(),
      ],
    ]);
    $assert->elementExists('css', '.views-row:contains("' . $this->user->label() . '")');
    $assert->elementNotExists('css', '.views-row:contains("' . $this->user2->label() . '")');

    // Now test as user 2 who only has access to the first section.
    $this->drupalLogin($this->user2);
    $this->drupalGet('admin/content/sections/menu');
    $assert->pageTextContains('Some section node 1');
    $assert->pageTextContains('Some section node 2');
    $assert->elementNotExists('css', '.views-row:contains("Another section")');
    $assert->elementNotExists('css', '.views-row:contains("More sections")');
  }

}
