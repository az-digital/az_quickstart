<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\workbench_access\Entity\AccessScheme;

/**
 * Defines a class for testing workbench access views.
 *
 * @group workbench_access
 */
class ViewsFieldTest extends BrowserTestBase {

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

    $this->user2 = $this->createUser($permissions);
    $this->user2->save();
    $values = [reset($this->terms)->id()];
    $this->userStorage->addUser($this->scheme, $this->user2, $values);
  }

  /**
   * Tests field and filter.
   */
  public function testFieldAndFilter() {
    $this->drupalLogin($this->user);
    $this->drupalGet('admin/content/sections');
    $assert = $this->assertSession();
    foreach ($this->terms as $section => $term) {
      $assert->elementExists('css', '.views-row:contains("' . $term->label() . '")');
      $assert->elementExists('css', '.views-row:contains("' . $section . ' node 1' . '")');
    }
    // Now filter the page.
    $this->drupalGet('admin/content/sections', [
      'query' => [
        'section' => $this->terms['Some section']->id(),
      ],
    ]);
    $assert->pageTextContains('Some section node 1');
    $assert->pageTextContains('Some section node 2');
    $assert->elementNotExists('css', '.views-row:contains("Another section")');
    $assert->elementNotExists('css', '.views-row:contains("More sections")');

    $this->drupalGet('admin/people/sections');
    $row = $assert->elementExists('css', '.views-row:contains("' . $this->user->label() . '")');

    // User 1 has all sections.
    foreach ($this->terms as $section => $term) {
      $assert->elementExists('css', '.views-row:contains("' . $section . '")', $row);
    }

    // User 2 only has one.
    $row = $assert->elementExists('css', '.views-row:contains("' . $this->user2->label() . '")');
    $assert->elementExists('css', '.views-row:contains("Some section")', $row);

    // Now filter.
    $this->drupalGet('admin/people/sections', [
      'query' => [
        'section' => $this->terms['Some section']->id(),
      ],
    ]);
    $assert->elementExists('css', '.views-row:contains("' . $this->user->label() . '")');
    $assert->elementExists('css', '.views-row:contains("' . $this->user2->label() . '")');
    $this->drupalGet('admin/people/sections', [
      'query' => [
        'section' => $this->terms['Another section']->id(),
      ],
    ]);
    $assert->elementExists('css', '.views-row:contains("' . $this->user->label() . '")');
    $assert->elementNotExists('css', '.views-row:contains("' . $this->user2->label() . '")');

    // Now test as user 2 who only has access to the first section.
    $this->drupalLogin($this->user2);
    $this->drupalGet('admin/content/sections');
    $assert->pageTextContains('Some section node 1');
    $assert->pageTextContains('Some section node 2');
    $assert->elementNotExists('css', '.views-row:contains("Another section")');
    $assert->elementNotExists('css', '.views-row:contains("More sections")');
  }

}
