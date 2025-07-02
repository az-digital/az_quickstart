<?php

namespace Drupal\Tests\workbench_access\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\UiHelperTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\Tests\workbench_access\Traits\WorkbenchAccessTestTrait;
use Drupal\workbench_access\Plugin\views\field\Section;

/**
 * Defines a class for testing config dependencies.
 *
 * @group workbench_access
 */
class ConfigDependenciesTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use UiHelperTrait;
  use UserCreationTrait;
  use WorkbenchAccessTestTrait;

  /**
   * Access vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
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
   * Access scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * Access scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $menuScheme;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installConfig(['filter', 'node', 'workbench_access', 'system']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('system', ['sequences']);
    $node_type = $this->createContentType(['type' => 'page']);
    $node_type2 = $this->createContentType(['type' => 'article']);
    $this->vocabulary = $this->setUpVocabulary();
    $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $this->vocabulary->id());
    $this->setUpTaxonomyFieldForEntityType('node', $node_type->id(), $this->vocabulary->id(), 'field_section');
    $this->scheme = $this->setUpTaxonomyScheme($node_type, $this->vocabulary);
    $configuration = $this->scheme->getAccessScheme()->getConfiguration();
    $configuration['fields'][] = [
      'field' => 'field_section',
      'entity_type' => 'node',
      'bundle' => 'page',
    ];
    $this->scheme->getAccessScheme()->setConfiguration($configuration);
    $this->scheme->save();
    $this->menuScheme = $this->setUpMenuScheme([
      $node_type->id(),
      $node_type2->id(),
    ], ['main'], 'menu_scheme');
  }

  /**
   * Tests views field dependencies.
   */
  public function testViewsFieldDependencies() {
    $configuration = [
      'scheme' => 'editorial_section',
    ];
    $handler = Section::create($this->container, $configuration, 'section:editorial_section', []);

    $dependencies = $handler->calculateDependencies();
    $this->assertEquals(['config' => ['workbench_access.access_scheme.editorial_section']], $dependencies);
  }

  /**
   * Tests scheme dependencies.
   */
  public function testSchemeDependencies() {
    $this->assertEquals([
      'config' => [
        'field.field.node.page.field_section',
        'field.field.node.page.field_workbench_access',
        'taxonomy.vocabulary.workbench_access',
      ],
    ], $this->scheme->getDependencies());
    $this->assertEquals([
      'config' => [
        'node.type.article',
        'node.type.page',
        'system.menu.main',
      ],
    ], $this->menuScheme->getDependencies());
    // Delete the article content type.
    NodeType::load('article')->delete();
    $this->menuScheme = $this->loadUnchangedScheme($this->menuScheme->id());
    $this->assertEquals([
      'config' => [
        'node.type.page',
        'system.menu.main',
      ],
    ], $this->menuScheme->getDependencies());
    $this->assertEquals(['page'], $this->menuScheme->getAccessScheme()->getConfiguration()['bundles']);
    FieldConfig::load('node.page.field_section')->delete();
    $this->scheme = $this->loadUnchangedScheme($this->scheme->id());
    $this->assertEquals([
      'config' => [
        'field.field.node.page.field_workbench_access',
        'taxonomy.vocabulary.workbench_access',
      ],
    ], $this->scheme->getDependencies());
  }

}
