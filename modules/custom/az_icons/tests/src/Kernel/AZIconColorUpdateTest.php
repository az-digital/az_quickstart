<?php

namespace Drupal\Tests\az_icons\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the update that renames the Arizona Bootstrap color names in icons.
 *
 * @see az_icons_post_update_rename_icon_colors()
 */
#[Group('az_icons')]
#[RunTestsInSeparateProcesses]
class AZIconColorUpdateTest extends KernelTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'az_card',
    'az_icons',
    'az_person',
    'content_moderation',
    'field',
    'file',
    'filter',
    'image',
    'language',
    'media',
    'node',
    'system',
    'text',
    'user',
    'workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('content_moderation_state');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['content_moderation', 'language']);
    ConfigurableLanguage::createFromLangcode('es')->save();

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    $this->createField('page', 'body', 'text_with_summary', 1);
    $this->createField('page', 'field_cards', 'az_card', -1);

    $this->container->get('module_handler')->loadInclude('az_icons', 'php', 'az_icons.post_update');
  }

  /**
   * Tests that old color names are renamed in every formatted text column.
   */
  public function testRenamesColors(): void {
    $node = $this->createNode('page', [
      'body' => [
        'value' => '<p class="text-sky">Sky</p><drupal-icon data-icon-id="material_symbols:person" data-icon-settings="{&quot;size&quot;:&quot;90&quot;,&quot;color&quot;:&quot;cool-gray&quot;}">&nbsp;</drupal-icon>',
        'summary' => $this->icon('sky'),
        'format' => 'az_standard',
      ],
      'field_cards' => [
        [
          'title' => 'First card',
          'body' => $this->icon('midnight'),
          'body_format' => 'az_standard',
        ],
        [
          'title' => 'Second card',
          'body' => $this->icon('rain'),
          'body_format' => 'az_standard',
        ],
      ],
    ]);
    $node->addTranslation('es', [
      'title' => 'Página',
      'body' => [
        'value' => $this->icon('leaf'),
        'format' => 'az_standard',
      ],
    ])->save();
    $revision_id = $node->getRevisionId();

    $result = $this->runUpdate();

    $node = $this->reload($node);
    // Only the color value changes. The Styles dropdown class stays.
    $this->assertSame('<p class="text-sky">Sky</p><drupal-icon data-icon-id="material_symbols:person" data-icon-settings="{&quot;size&quot;:&quot;90&quot;,&quot;color&quot;:&quot;cloud&quot;}">&nbsp;</drupal-icon>', $node->get('body')->value);
    $this->assertSame($this->icon('rain'), $node->get('body')->summary);
    $this->assertSame($this->icon('tinta'), $node->get('field_cards')->getValue()[0]['body']);
    $this->assertSame($this->icon('rain'), $node->get('field_cards')->getValue()[1]['body']);
    $this->assertSame($this->icon('saguaro'), $node->getTranslation('es')->get('body')->value);
    // The current revision is updated, rather than a new revision created.
    $this->assertEquals($revision_id, $node->getRevisionId());
    // The node is saved as a sync, so its changed date stays the same.
    $this->assertEquals(1000000000, $node->getChangedTime());
    $this->assertSame('Renamed the Arizona Bootstrap color names in icons in 1 entity.', (string) $result);
  }

  /**
   * Tests that content with no old color names is not saved.
   */
  public function testLeavesOtherContent(): void {
    $new_names = $this->createNode('page', [
      'body' => [
        'value' => $this->icon('rain'),
        'format' => 'az_standard',
      ],
    ]);
    $no_icon = $this->createNode('page', [
      'body' => [
        'value' => '<p class="text-sky">"color":"sky"</p>',
        'format' => 'az_standard',
      ],
    ]);

    $result = $this->runUpdate();

    $this->assertSame('Renamed the Arizona Bootstrap color names in icons in 0 entities.', (string) $result);
    $this->assertSame($this->icon('rain'), $this->reload($new_names)->get('body')->value);
    $this->assertSame('<p class="text-sky">"color":"sky"</p>', $this->reload($no_icon)->get('body')->value);
  }

  /**
   * Tests that a moderated page keeps its draft, and gets no new revision.
   */
  public function testKeepsDraft(): void {
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'page');
    $node = $this->createNode('page', [
      'body' => [
        'value' => $this->icon('sky'),
        'format' => 'az_standard',
      ],
      'moderation_state' => 'published',
    ]);
    $published_revision_id = $node->getRevisionId();
    $node->set('body', [
      'value' => $this->icon('sky') . '<p>Unfinished edit</p>',
      'format' => 'az_standard',
    ]);
    $node->set('moderation_state', 'draft');
    $node->save();
    $draft_revision_id = $node->getRevisionId();
    $this->assertNotEquals($published_revision_id, $draft_revision_id);

    $this->runUpdate();

    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $storage->resetCache();
    // The published revision is renamed in place.
    $published = $storage->load($node->id());
    $this->assertEquals($published_revision_id, $published->getRevisionId());
    $this->assertSame($this->icon('rain'), $published->get('body')->value);
    $this->assertSame('published', $published->get('moderation_state')->value);
    // The draft is unchanged, and it is still the latest revision.
    $this->assertEquals($draft_revision_id, $storage->getLatestRevisionId($node->id()));
    $this->assertSame($this->icon('sky') . '<p>Unfinished edit</p>', $this->loadRevision($draft_revision_id)->get('body')->value);
    $this->assertSame(2, $this->countRevisions($node));
  }

  /**
   * Tests that a person is renamed in place, and imports still make drafts.
   *
   * @see az_person_node_presave()
   */
  public function testPerson(): void {
    NodeType::create(['type' => 'az_person', 'name' => 'Person'])->save();
    $this->createField('az_person', 'field_az_body', 'text_long', 1);
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'az_person');
    $person = $this->createNode('az_person', [
      'field_az_body' => [
        'value' => $this->icon('sky'),
        'format' => 'az_standard',
      ],
      'moderation_state' => 'published',
    ]);
    $revision_id = $person->getRevisionId();

    $this->runUpdate();

    $person = $this->reload($person);
    $this->assertSame($this->icon('rain'), $person->get('field_az_body')->value);
    $this->assertEquals($revision_id, $person->getRevisionId());
    $this->assertSame('published', $person->get('moderation_state')->value);
    $this->assertEquals(1000000000, $person->getChangedTime());
    $this->assertSame(1, $this->countRevisions($person));

    // An import is a sync save, and it still makes a draft.
    $person->set('field_az_body', [
      'value' => '<p>Imported</p>',
      'format' => 'az_standard',
    ]);
    $person->setSyncing(TRUE);
    $person->save();
    $this->assertSame(2, $this->countRevisions($person));
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $latest = $this->loadRevision($storage->getLatestRevisionId($person->id()));
    $this->assertSame('draft', $latest->get('moderation_state')->value);
    $this->assertStringStartsWith('Updated via automated import.', $latest->getRevisionLogMessage());
  }

  /**
   * Tests that the update processes entities in batches.
   */
  public function testBatches(): void {
    $nodes = [];
    for ($i = 0; $i < 30; $i++) {
      $nodes[] = $this->createNode('page', [
        'body' => [
          'value' => $this->icon('chili'),
          'format' => 'az_standard',
        ],
      ]);
    }

    $sandbox = [];
    az_icons_post_update_rename_icon_colors($sandbox);
    $this->assertLessThan(1, $sandbox['#finished']);
    $result = az_icons_post_update_rename_icon_colors($sandbox);
    $this->assertEquals(1, $sandbox['#finished']);
    $this->assertSame('Renamed the Arizona Bootstrap color names in icons in 30 entities.', (string) $result);

    foreach ($nodes as $node) {
      $this->assertSame($this->icon('sonoran-red'), $this->reload($node)->get('body')->value);
    }
  }

  /**
   * Tests that the update finishes when there is nothing to update.
   */
  public function testNoEntities(): void {
    $sandbox = [];
    $result = az_icons_post_update_rename_icon_colors($sandbox);
    $this->assertEquals(1, $sandbox['#finished']);
    $this->assertSame('Renamed the Arizona Bootstrap color names in icons in 0 entities.', (string) $result);
  }

  /**
   * Runs the update until it finishes.
   *
   * @return string|\Stringable|null
   *   The message the update returns when it finishes.
   */
  protected function runUpdate() {
    $sandbox = [];
    do {
      $result = az_icons_post_update_rename_icon_colors($sandbox);
    } while ($sandbox['#finished'] < 1);
    return $result;
  }

  /**
   * Returns the HTML for an icon with a color, as CKEditor 5 can save it.
   *
   * @param string $color
   *   The saved color name.
   *
   * @return string
   *   The <drupal-icon> tag.
   */
  protected function icon(string $color): string {
    return '<drupal-icon data-icon-id="material_symbols:home" data-icon-settings=\'{"color":"' . $color . '"}\'></drupal-icon>';
  }

  /**
   * Adds a translatable field to a content type.
   *
   * @param string $bundle
   *   The content type.
   * @param string $field_name
   *   The field name.
   * @param string $type
   *   The field type.
   * @param int $cardinality
   *   The number of values the field can hold, or -1 for unlimited.
   */
  protected function createField(string $bundle, string $field_name, string $type, int $cardinality): void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $type,
      'cardinality' => $cardinality,
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $bundle,
      'translatable' => TRUE,
    ])->save();
  }

  /**
   * Creates and saves a node.
   *
   * @param string $bundle
   *   The content type.
   * @param array $values
   *   The field values.
   *
   * @return \Drupal\node\NodeInterface
   *   The saved node.
   */
  protected function createNode(string $bundle, array $values): NodeInterface {
    $node = Node::create([
      'type' => $bundle,
      'title' => 'Node',
      'changed' => 1000000000,
    ] + $values);
    $node->save();
    return $node;
  }

  /**
   * Counts the revisions of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return int
   *   The number of revisions.
   */
  protected function countRevisions(NodeInterface $node): int {
    return (int) $this->container->get('entity_type.manager')->getStorage('node')->getQuery()
      ->allRevisions()
      ->condition('nid', $node->id())
      ->accessCheck(FALSE)
      ->count()
      ->execute();
  }

  /**
   * Loads a node revision.
   *
   * @param int|string $revision_id
   *   The revision ID.
   *
   * @return \Drupal\node\NodeInterface
   *   The node revision.
   */
  protected function loadRevision($revision_id): NodeInterface {
    $revision = $this->container->get('entity_type.manager')->getStorage('node')->loadRevision($revision_id);
    $this->assertInstanceOf(NodeInterface::class, $revision);
    return $revision;
  }

  /**
   * Loads a fresh copy of a node from storage.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\node\NodeInterface
   *   The reloaded node.
   */
  protected function reload(NodeInterface $node): NodeInterface {
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $storage->resetCache([$node->id()]);
    return $storage->load($node->id());
  }

}
