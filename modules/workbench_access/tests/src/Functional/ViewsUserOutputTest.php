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
class ViewsUserOutputTest extends BrowserTestBase {

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
    'menu_link_content',
    'menu_ui',
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

    // Store taxonomy schemes.
    $values = array_values(array_map(function (TermInterface $term) {
      return $term->id();
    }, $this->terms));
    $this->userStorage->addUser($this->scheme, $this->user, $values);

    $this->user2 = $this->createUser($permissions);
    $this->user2->save();
    $this->userStorage->addUser($this->scheme, $this->user2, $values);
  }

  /**
   * Tests field and filter.
   */
  public function testFieldOutput() {
    $this->drupalLogin($this->user);
    $this->drupalGet('user-sections');
    $assert = $this->assertSession();
    foreach ($this->terms as $section => $term) {
      $row = $assert->elementExists('css', '.views-row:contains("' . $this->user->label() . '")');
      $assert->elementExists('css', '.views-row:contains("' . $section . '")', $row);
      $this->assertSession()->linkByHrefNotExists('/taxonomy/term/' . $term->id());
    }
    $this->drupalGet('user-sections-2');
    foreach ($this->terms as $section => $term) {
      $row = $assert->elementExists('css', '.views-row:contains("' . $this->user->label() . '")');
      $assert->elementExists('css', '.views-row:contains("' . $section . '")', $row);
      $this->assertSession()->linkByHrefExists('/taxonomy/term/' . $term->id());
    }
  }

}
