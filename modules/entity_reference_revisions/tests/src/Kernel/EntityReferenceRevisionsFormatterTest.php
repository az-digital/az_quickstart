<?php

namespace Drupal\Tests\entity_reference_revisions\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\entity_composite_relationship_test\Entity\EntityTestCompositeRelationship;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * @coversDefaultClass \Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter
 * @group entity_reference_revisions
 */
class EntityReferenceRevisionsFormatterTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'user',
    'system',
    'field',
    'entity_reference_revisions',
    'entity_composite_relationship_test',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create article content type.
    $values = ['type' => 'article', 'name' => 'Article'];
    $node_type = NodeType::create($values);
    $node_type->save();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('entity_test_composite');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);

    // Add the entity_reference_revisions field to article.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'composite_reference',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'settings' => [
        'target_type' => 'entity_test_composite'
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ]);
    $field->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'settings' => [
        'target_type' => 'entity_test',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ]);
    $field->save();

    $user = $this->createUser(['administer entity_test composite relationship']);
    \Drupal::currentUser()->setAccount($user);
  }

  public function testFormatterWithDeletedReference() {
    // Create the test composite entity.
    $text = 'Dummy text';
    $entity_test = EntityTestCompositeRelationship::create([
      'uuid' => $text,
      'name' => $text,
    ]);
    $entity_test->save();

    $text = 'Clever text';
    // Set the name to a new text.
    /** @var \Drupal\entity_composite_relationship_test\Entity\EntityTestCompositeRelationship $entity_test */
    $entity_test->name = $text;
    $entity_test->setNeedsSave(TRUE);

    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $entity_test,
    ]);
    $node->save();

    // entity_reference_revisions_entity_view
    $result = $node->composite_reference->view(['type' => 'entity_reference_revisions_entity_view']);
    $this->setRawContent($this->render($result));
    $this->assertText('Clever text');

    // Remove the referenced entity.
    $entity_test->delete();

    $node = Node::load($node->id());
    $result = $node->composite_reference->view(['type' => 'entity_reference_revisions_entity_view']);
    $this->render($result);
    $this->assertNoText('Clever text');
  }

  /**
   * Tests the label formatter.
   */
  public function testLabelFormatter() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $formatter = 'entity_reference_revisions_label';

    // Create the test composite entity.
    $entity_test_composite = EntityTestCompositeRelationship::create([
      'name' => $this->randomMachineName(),
    ]);
    $entity_test_composite->save();

    // Create the test entity.
    $entity_test = EntityTest::create([
      'name' => $this->randomMachineName(),
    ]);
    $entity_test->save();

    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'composite_reference' => $entity_test_composite,
      'field_test' => $entity_test,
    ]);
    $node->save();

    // The 'link' settings is TRUE by default.
    $build = $node->get('field_test')->view([
      'type' => $formatter,
      'settings' => [],
    ]);

    $expected_field_cacheability = [
      'contexts' => [],
      'tags' => [],
      'max-age' => Cache::PERMANENT,
    ];
    $this->assertEquals($build['#cache'], $expected_field_cacheability, 'The field render array contains the entity access cacheability metadata');
    $expected_item = [
      '#type' => 'link',
      '#title' => $entity_test->label(),
      '#url' => $entity_test->toUrl(),
      '#options' => $entity_test->toUrl()->getOptions(),
      '#cache' => [
        'contexts' => [
          'user.permissions',
        ],
        'tags' => $entity_test->getCacheTags(),
      ],
    ];
    $this->assertEquals($renderer->renderRoot($build[0]), $renderer->renderRoot($expected_item), sprintf('The markup returned by the %s formatter is correct for an item with a saved entity.', $formatter));
    $this->assertEquals(CacheableMetadata::createFromRenderArray($build[0]), CacheableMetadata::createFromRenderArray($expected_item));

    // Test with the 'link' setting set to FALSE.
    $build = $node->get('field_test')->view([
      'type' => $formatter,
      'settings' => ['link' => FALSE],
    ]);
    $this->assertEquals($build[0]['#plain_text'], $entity_test->label(), sprintf('The markup returned by the %s formatter is correct.', $formatter));

    // Test an entity type that doesn't have any link templates, which means
    // \Drupal\Core\Entity\EntityInterface::toUrl() will throw an exception
    // and the label formatter will output only the label instead of a link.
    $build = $node->get('composite_reference')->view([
      'type' => $formatter,
      'settings' => ['link' => TRUE],
    ]);
    $this->assertEquals($build[0]['#plain_text'], $entity_test_composite->label(), sprintf('The markup returned by the %s formatter is correct for an entity type with no valid link template.', $formatter));
  }

}
