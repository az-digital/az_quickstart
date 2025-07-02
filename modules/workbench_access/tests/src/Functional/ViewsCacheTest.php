<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\views\Views;
use Drupal\workbench_access\Entity\AccessScheme;

/**
 * Defines a class for testing workbench access views.
 *
 * @group workbench_access
 */
class ViewsCacheTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Test terms.
   *
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms = [];

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
   * User section storage.
   *
   * @var \Drupal\workbench_access\UserSectionStorage
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'workbench_access',
    'views',
    'node',
    'taxonomy',
    'system',
    'user',
    'filter',
    'workbench_access_test',
  ];

  /**
   * Access scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

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
      $this->terms[$section] = Term::create([
        'vid' => 'editorial_section',
        'name' => $section . ' term',
      ]);
      $this->terms[$section]->save();
      foreach ([' node 1', ' node 2'] as $stub) {
        $title = $section . $stub;
        $this->nodes[$title] = Node::create([
          'type' => 'article',
          'title' => $title,
          'status' => 1,
          'field_workbench_access' => $this->terms[$section],
        ]);
        $this->nodes[$title]->save();
      }
    }

    // Create a user who can access content etc.
    $permissions = [
      'create article content',
      'edit any article content',
      'access content',
      'delete any article content',
      'administer nodes',
      'access user profiles',
      'use workbench access',
    ];
    $this->user = $this->createUser($permissions);
    $this->user->save();
    $this->userStorage = \Drupal::service('workbench_access.user_section_storage');
    $this->scheme = AccessScheme::load('editorial_section');

    $values = array_values(array_map(function (TermInterface $term) {
      return $term->id();
    }, $this->terms));
    $this->userStorage->addUser($this->scheme, $this->user, $values);

    $roles = $this->user->getRoles(TRUE);
    $values = [
      'roles' => $roles,
    ];
    $this->user2 = $this->createUser([], NULL, FALSE, $values);
    $this->user2->save();
    $values = [reset($this->terms)->id()];
    $this->userStorage->addUser($this->scheme, $this->user2, $values);
  }

  /**
   * Tests that views cache as expected.
   *
   * See \Drupal\Tests\views\Functional\Plugin\CacheWebTest for a similar test.
   */
  public function testViewsCache() {
    // A view that does not contain a workbench_access element.
    $view = Views::getView('user_admin_people');
    $context = [
      'languages:language_content',
      'languages:language_interface',
      'url',
      'url.query_args',
      'user.permissions',
    ];
    $no_workbench_access = $view->render('page_1');
    $this->assertEquals($context, $no_workbench_access['#cache']['contexts']);

    // A view that does.
    $view2 = Views::getView('content_sections');
    // The context from the View.
    // tests/modules/workbench_access_test/config/install/views.view.content_sections.yml.
    $context2 = [
      'languages:language_content',
      'languages:language_interface',
      'url.query_args',
      'user.node_grants:view',
      'user.permissions',
    ];
    // What we add via hook_views_post_render().
    $context2[] = 'user';

    $workbench_access = $view2->render('page_1');
    $this->assertEquals($context2, $workbench_access['#cache']['contexts']);

    // Now test as user 1 who has access to all sections.
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/content/sections');

    $allowed = [];
    $disallowed = [];
    foreach ($this->terms as $section => $term) {
      $allowed[] = $section;
    }
    $this->assertSectionVisibility($allowed, $disallowed);

    // Now test as user 2 who only has access to the first section.
    $this->drupalLogin($this->user2);
    $this->drupalGet('admin/content/sections');
    $allowed_1 = [
      'Some section',
    ];
    $disallowed_1 = [
      'Another section',
      'More sections',
    ];
    $this->assertSectionVisibility($allowed_1, $disallowed_1);

    // Add user 2 to another section and re-test.
    $term = $this->terms['Another section']->id();
    $this->userStorage->addUser($this->scheme, $this->user2, [$term]);
    $this->drupalGet('admin/content/sections');
    $allowed_2 = [
      'Some section',
      'Another section',
    ];
    $disallowed_2 = [
      'More sections',
    ];
    $this->assertSectionVisibility($allowed_2, $disallowed_2);

    // Remove the user and re-test.
    $this->userStorage->removeUser($this->scheme, $this->user2, [$term]);
    $this->drupalGet('admin/content/sections');
    $this->assertSectionVisibility($allowed_1, $disallowed_1);

    // Add the user to a role and re-test.
    $roles = $this->user2->getRoles(TRUE);
    $role_storage = \Drupal::service('workbench_access.role_section_storage');
    $role_storage->addRole($this->scheme, $roles[0], [$term]);
    $this->drupalGet('admin/content/sections');
    $this->assertSectionVisibility($allowed_2, $disallowed_2);

    // Remove and re-test.
    $role_storage->removeRole($this->scheme, $roles[0], [$term]);
    $this->drupalGet('admin/content/sections');
    $this->assertSectionVisibility($allowed_1, $disallowed_1);
  }

  /**
   * Tests that edit links are correctly varied in cache per user/section.
   */
  public function testViewsEditLinkCache(): void {
    $this->drupalLogin($this->user);
    $this->drupalGet('edit-links');

    // First user should see all edit links.
    foreach ($this->nodes as $title => $node) {
      $this->assertSession()->pageTextContains($title);
      $this->assertSession()->linkByHrefExists($node->toUrl('edit-form')->toString());
    }

    // Second user should only see edit links for their section.
    $this->drupalLogin($this->user2);
    $this->drupalGet('edit-links');
    foreach ($this->nodes as $title => $node) {
      // User 2 only has edit access to "Some section" nodes.
      if (strpos($title, 'Some section') === FALSE) {
        $this->assertSession()->linkByHrefNotExists($node->toUrl('edit-form')->toString());
      }
      else {
        $this->assertSession()->linkByHrefExists($node->toUrl('edit-form')->toString());
      }
    }
  }

  /**
   * Asserts what can and cannot be seen by the user.
   *
   * @param array $allowed
   *   The items we should see.
   * @param array $disallowed
   *   The items we should not see.
   */
  public function assertSectionVisibility(array $allowed, array $disallowed) {
    $assert = $this->assertSession();
    foreach ($allowed as $item) {
      $assert->elementExists('css', '.views-row:contains("' . $item . ' node 1' . '")');
      $assert->elementExists('css', '.views-row:contains("' . $item . ' node 2' . '")');
    }
    foreach ($disallowed as $item) {
      $assert->elementNotExists('css', '.views-row:contains("' . $item . ' node 1' . '")');
      $assert->elementNotExists('css', '.views-row:contains("' . $item . ' node 2' . '")');
    }
    // Check the cache tags.
    $assert->responseHeaderContains('X-Drupal-Cache-Tags', 'workbench_access_view');
  }

}
