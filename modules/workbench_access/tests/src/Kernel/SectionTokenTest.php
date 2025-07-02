<?php

namespace Drupal\Tests\workbench_access\Kernel;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\UiHelperTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\workbench_access\WorkbenchAccessManagerInterface;

/**
 * Tests workbench_access integration with tokens.
 *
 * @group workbench_access
 */
class SectionTokenTest extends KernelTestBase {

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
   * Access vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Taxonomy scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $taxonomyScheme;

  /**
   * Menu scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $menuScheme;

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
    'node',
    'link',
    'menu_link_content',
    'menu_ui',
    'text',
    'system',
    'user',
    'workbench_access',
    'field',
    'filter',
    'taxonomy',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['filter', 'node', 'workbench_access', 'system']);
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('section_association');
    $this->installSchema('system', ['sequences']);
    $this->vocabulary = $this->setUpVocabulary();
    $node_type = $this->setUpContentType();
    // Set a field on the node type.
    $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $this->vocabulary->id(), WorkbenchAccessManagerInterface::FIELD_NAME, 'Section', $cardinality = 3);
    $this->taxonomyScheme = $this->setupTaxonomyScheme($node_type, $this->vocabulary);

    // Add a menu to the node type.
    $this->menu = Menu::load('main');
    $node_type->setThirdPartySetting('menu_ui', 'available_menus', ['main']);
    $node_type->save();
    $this->menuScheme = $this->setupMenuScheme([$node_type->id()], ['main'], 'menu_section');
    $this->userStorage = \Drupal::service('workbench_access.user_section_storage');
  }

  /**
   * Tests the user section tokens.
   */
  public function testUserSectionTokens() {
    $user = $this->createUser();
    $link = MenuLinkContent::create([
      'title' => 'Test menu link',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => $this->menu->id(),
    ]);
    $link->save();
    $this->userStorage->addUser($this->menuScheme, $user, [$link->getPluginId()]);

    $tokens = [
      'workbench-access-sections' => 'Test menu link',
    ];
    $bubbleable_metadata = new BubbleableMetadata();
    $this->assertTokens('user', ['user' => $user], $tokens, $bubbleable_metadata);
    $this->assertContains($this->menuScheme->getCacheTags()[0], $bubbleable_metadata->getCacheTags());

    $term = Term::create([
      'name' => 'Test term',
      'vid' => $this->vocabulary->id(),
    ]);
    $term->save();
    $this->userStorage->addUser($this->taxonomyScheme, $user, [$term->id()]);
    $tokens = [
      'workbench-access-sections' => 'Test term, Test menu link',
    ];
    $this->assertTokens('user', ['user' => $user], $tokens, $bubbleable_metadata);
    $this->assertContains($this->taxonomyScheme->getCacheTags()[0], $bubbleable_metadata->getCacheTags());
    $term = Term::create([
      'name' => 'Test term 2',
      'vid' => $this->vocabulary->id(),
    ]);
    $term->save();
    $this->userStorage->addUser($this->taxonomyScheme, $user, [$term->id()]);
    $tokens = [
      'workbench-access-sections' => 'Test term, Test term 2, Test menu link',
    ];
    $this->assertTokens('user', ['user' => $user], $tokens, $bubbleable_metadata);

    $this->setCurrentUser($user);
    $this->assertTokens('current-user', [], $tokens, $bubbleable_metadata);
  }

  /**
   * Tests the node section tokens.
   */
  public function testNodeSectionTokens() {
    // Test a node that is not assigned to a section.
    $node1 = $this->createNode(['type' => 'page', 'title' => 'foo']);

    $tokens = [];
    $bubbleable_metadata = new BubbleableMetadata();
    $this->assertTokens('node', ['node' => $node1], $tokens, $bubbleable_metadata);

    $term = Term::create([
      'name' => 'Test term',
      'vid' => $this->vocabulary->id(),
    ]);
    $term->save();

    // Create a node that is assigned to a term section.
    $node2 = $this->createNode([
      'type' => 'page',
      'title' => 'bar',
      WorkbenchAccessManagerInterface::FIELD_NAME => $term->id(),
    ]);

    $tokens = [
      'workbench-access-sections' => 'Test term',
    ];
    $bubbleable_metadata = new BubbleableMetadata();
    $this->assertTokens('node', ['node' => $node2], $tokens, $bubbleable_metadata);
    $this->assertContains($this->taxonomyScheme->getCacheTags()[0], $bubbleable_metadata->getCacheTags());

    // Assign to multiple terms.
    $term2 = Term::create([
      'name' => 'Test term two',
      'vid' => $this->vocabulary->id(),
    ]);
    $term2->save();

    // Create a node that is assigned to a term section.
    $node3 = $this->createNode([
      'type' => 'page',
      'title' => 'bar',
      WorkbenchAccessManagerInterface::FIELD_NAME => [$term->id(), $term2->id()],
    ]);

    $tokens = [
      'workbench-access-sections' => 'Test term, Test term two',
    ];
    $bubbleable_metadata = new BubbleableMetadata();
    $this->assertTokens('node', ['node' => $node3], $tokens, $bubbleable_metadata);
    $this->assertContains($this->taxonomyScheme->getCacheTags()[0], $bubbleable_metadata->getCacheTags());

    // Create a node that is assigned to a menu section.
    $link = MenuLinkContent::create([
      'title' => 'Test menu link',
      'link' => [['uri' => 'route:<front>']],
      'menu_name' => $this->menu->id(),
    ]);
    $link->save();

    // Create a node that is in a menu section.
    $node4 = $this->createNode(['type' => 'page', 'title' => 'bar']);
    _menu_ui_node_save($node4, [
      'title' => 'Menu test',
      'menu_name' => 'main',
      'description' => 'view bar',
      'parent' => $link->getPluginId(),
    ]);

    $tokens = [
      'workbench-access-sections' => 'Menu test',
    ];
    $bubbleable_metadata = new BubbleableMetadata();
    $this->assertTokens('node', ['node' => $node4], $tokens, $bubbleable_metadata);
    $this->assertContains($this->menuScheme->getCacheTags()[0], $bubbleable_metadata->getCacheTags());

    // Create a node that is assigned to both sections.
    $node5 = $this->createNode([
      'type' => 'page',
      'title' => 'bar',
      'field_workbench_access' => $term->id(),
    ]
    );
    _menu_ui_node_save($node5, [
      'title' => 'Test another menu link',
      'menu_name' => 'main',
      'description' => 'view bar',
      'parent' => $link->getPluginId(),
    ]);
    $tokens = [
      'workbench-access-sections' => 'Test term, Test another menu link',
    ];
    $this->assertTokens('node', ['node' => $node5], $tokens, $bubbleable_metadata);
    $this->assertContains($this->taxonomyScheme->getCacheTags()[0], $bubbleable_metadata->getCacheTags());
    $this->assertContains($this->menuScheme->getCacheTags()[0], $bubbleable_metadata->getCacheTags());
  }

  /**
   * Helper function to assert tokens.
   *
   * @param string $type
   *   The token type.
   * @param array $data
   *   The input data.
   * @param array $tokens
   *   The tokens.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The cache metadata.
   *
   * @return array
   *   The array of replacements.
   */
  protected function assertTokens($type, array $data, array $tokens, BubbleableMetadata $bubbleable_metadata) {
    $input = array_reduce(array_keys($tokens), function ($carry, $token) use ($type) {
      $carry[$token] = "[$type:$token]";
      return $carry;
    }, []);

    $replacements = \Drupal::token()->generate($type, $input, $data, [], $bubbleable_metadata);
    foreach ($tokens as $name => $expected) {
      $token = $input[$name];
      $this->assertEquals($replacements[$token], $expected);
    }

    return $replacements;
  }

}
