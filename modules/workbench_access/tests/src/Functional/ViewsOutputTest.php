<?php

namespace Drupal\Tests\workbench_access\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\BrowserTestBase;

/**
 * Defines a class for testing workbench access views.
 *
 * @group workbench_access
 */
class ViewsOutputTest extends BrowserTestBase {

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
      $this->links[$section] = MenuLinkContent::create([
        'title' => $section,
        'link' => [['uri' => 'route:<front>']],
        'menu_name' => 'main',
      ]);
      foreach ([' node 1', ' node 2'] as $stub) {
        $title = $section . $stub;
        $this->nodes[$title] = Node::create([
          'type' => 'article',
          'title' => $title,
          'status' => 1,
          'field_workbench_access' => $this->terms[$section],
        ]);
        $this->nodes[$title]->save();
        _menu_ui_node_save($this->nodes[$title], [
          'title' => $title . '-menu',
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
      'access user profiles',
      'use workbench access',
    ];
    $this->user = $this->createUser($permissions);
    $this->user->save();
  }

  /**
   * Tests field and filter.
   */
  public function testFieldOutput() {
    $this->drupalLogin($this->user);
    $this->drupalGet('content-sections');
    $assert = $this->assertSession();
    foreach ($this->terms as $section => $term) {
      $row = $assert->elementExists('css', '.views-row:contains("' . $term->label() . '")');
      $assert->elementExists('css', '.views-row:contains("' . $section . ' node' . '")', $row);
      $this->assertSession()->linkByHrefNotExists('/taxonomy/term/' . $term->id());
    }
    $this->drupalGet('content-sections-2');
    foreach ($this->terms as $section => $term) {
      $row = $assert->elementExists('css', '.views-row:contains("' . $term->label() . '")');
      $assert->elementExists('css', '.views-row:contains("' . $section . ' node' . '")', $row);
      $this->assertSession()->linkByHrefExists('/taxonomy/term/' . $term->id());
    }
    $this->drupalGet('content-sections-3');
    $assert = $this->assertSession();
    foreach ($this->links as $section => $link) {
      $row = $assert->elementExists('css', '.views-row:contains("' . $section . '")');
      $assert->elementExists('css', '.views-row:contains("' . $section . ' node' . '")', $row);
      $this->assertSession()->linkNotExists($section . ' node 1-menu');
    }
    $this->drupalGet('content-sections-4');
    foreach ($this->links as $section => $link) {
      $row = $assert->elementExists('css', '.views-row:contains("' . $section . '")');
      $assert->elementExists('css', '.views-row:contains("' . $section . ' node' . '")', $row);
      $this->assertSession()->linkExists($section . ' node 1-menu');
    }
  }

}
