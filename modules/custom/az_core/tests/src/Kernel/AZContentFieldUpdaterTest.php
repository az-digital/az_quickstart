<?php

namespace Drupal\Tests\az_core\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests AZContentFieldUpdater on paragraphs, with new revisions.
 *
 * @see \Drupal\az_core\AZContentFieldUpdater
 */
#[Group('az_core')]
#[RunTestsInSeparateProcesses]
class AZContentFieldUpdaterTest extends KernelTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'az_core',
    'config_filter',
    'config_normalizer',
    'config_provider',
    'config_sync',
    'config_update',
    'content_moderation',
    'entity_reference_revisions',
    'field',
    'file',
    'filter',
    'node',
    'paragraphs',
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
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('content_moderation_state');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['content_moderation']);

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    ParagraphsType::create(['id' => 'text', 'label' => 'Text'])->save();
    $this->createField('paragraph', 'text', 'field_text', 'text_long', 1);
    $this->createField('node', 'page', 'field_main_content', 'entity_reference_revisions', -1, ['target_type' => 'paragraph']);
  }

  /**
   * Tests that a paragraph and its page get new revisions.
   */
  public function testParagraph(): void {
    $node = $this->createPage('<p>Old text</p>');
    $paragraph_id = $node->get('field_main_content')->target_id;

    $sandbox = $this->runUpdate([$paragraph_id]);

    $this->assertSame(1, $sandbox['updated_count']);
    $node = $this->reload($node);
    $this->assertSame(2, $this->countRevisions($node));
    $this->assertSame('<p>New text</p>', $this->loadParagraphRevision($node->get('field_main_content')->target_revision_id)->get('field_text')->value);
    $this->assertStringStartsWith('test: Updated child text paragraph ' . $paragraph_id, $node->getRevisionLogMessage());
  }

  /**
   * Tests a page whose draft came from reverting to a draft revision.
   *
   * Reverting to a draft revision makes the draft's copy of the paragraph its
   * current revision. The live page gets the update in its own copy, and the
   * draft's unfinished edit doesn't go live.
   */
  public function testRevertedDraftParagraph(): void {
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'page');
    $node = $this->createPage('<p>Old text</p>', ['moderation_state' => 'published']);
    $paragraph_id = $node->get('field_main_content')->target_id;

    // Save a draft on a clone, as the edit form does
    // (EntityForm::buildEntity()).
    $draft = clone $this->reload($node);
    $paragraph = $draft->get('field_main_content')->entity;
    $this->assertInstanceOf(ParagraphInterface::class, $paragraph);
    $paragraph->set('field_text', '<p>Old text</p><p>Unfinished edit</p>');
    $paragraph->setNeedsSave(TRUE);
    $draft->set('moderation_state', 'draft');
    $draft->setNewRevision(TRUE);
    $draft->save();

    // Revert to the draft, as the Revisions tab does.
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $reverted = $storage->createRevision($this->loadRevision($draft->getRevisionId()));
    $reverted->save();
    $draft_paragraph_revision_id = $reverted->get('field_main_content')->target_revision_id;
    $this->assertEquals($draft_paragraph_revision_id, Paragraph::load($paragraph_id)->getRevisionId());

    $sandbox = $this->runUpdate([$paragraph_id]);

    $this->assertSame(1, $sandbox['updated_count']);
    $published = $this->reload($node);
    $this->assertSame('published', $published->get('moderation_state')->value);
    $this->assertSame('<p>New text</p>', $this->loadParagraphRevision($published->get('field_main_content')->target_revision_id)->get('field_text')->value);
    // The draft keeps its own copy of the paragraph.
    $draft = $this->loadRevision($reverted->getRevisionId());
    $this->assertSame('draft', $draft->get('moderation_state')->value);
    $this->assertEquals($draft_paragraph_revision_id, $draft->get('field_main_content')->target_revision_id);
    $this->assertSame('<p>Old text</p><p>Unfinished edit</p>', $this->loadParagraphRevision($draft_paragraph_revision_id)->get('field_text')->value);
  }

  /**
   * Tests that a paragraph that a page no longer uses is skipped.
   */
  public function testUnusedParagraph(): void {
    $node = $this->createPage('<p>Old text</p>');
    $paragraph_id = $node->get('field_main_content')->target_id;
    $node->set('field_main_content', []);
    $node->setNewRevision(TRUE);
    $node->save();

    $sandbox = $this->runUpdate([$paragraph_id]);

    $this->assertSame(0, $sandbox['updated_count']);
    $this->assertSame(1, $sandbox['skipped']);
    $this->assertSame(2, $this->countRevisions($node));
    $this->assertSame('<p>Old text</p>', Paragraph::load($paragraph_id)->get('field_text')->value);
  }

  /**
   * Runs the updater on the text field of paragraphs until it finishes.
   *
   * It replaces "Old" with "New", and makes new revisions.
   *
   * @param array $paragraph_ids
   *   The paragraph IDs.
   *
   * @return array
   *   The sandbox, with the updater's counts.
   */
  protected function runUpdate(array $paragraph_ids): array {
    $sandbox = [
      'ids' => $paragraph_ids,
      'max' => count($paragraph_ids),
    ];
    do {
      $this->container->get('az_core.content_field_updater')->processFieldUpdates(
        'paragraph',
        'field_text',
        fn (string $value): string => str_replace('Old', 'New', $value),
        $sandbox,
        ['create_revisions' => TRUE, 'prefix' => 'test'],
      );
    } while ($sandbox['#finished'] < 1);
    return $sandbox;
  }

  /**
   * Adds a field to a bundle.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The field name.
   * @param string $type
   *   The field type.
   * @param int $cardinality
   *   The number of values the field can hold, or -1 for unlimited.
   * @param array $settings
   *   The field storage settings.
   */
  protected function createField(string $entity_type_id, string $bundle, string $field_name, string $type, int $cardinality, array $settings = []): void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type_id,
      'type' => $type,
      'cardinality' => $cardinality,
      'settings' => $settings,
    ])->save();
    // Like Quickstart's paragraph fields on content types, these fields aren't
    // translatable. Without this, a revert wouldn't save the paragraphs again,
    // and testRevertedDraftParagraph() wouldn't test anything.
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'translatable' => FALSE,
    ])->save();
  }

  /**
   * Creates a page with a text paragraph.
   *
   * @param string $text
   *   The paragraph's text.
   * @param array $values
   *   More field values for the page.
   *
   * @return \Drupal\node\NodeInterface
   *   The saved page.
   */
  protected function createPage(string $text, array $values = []): NodeInterface {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Page',
      'field_main_content' => [
        Paragraph::create([
          'type' => 'text',
          'field_text' => $text,
        ]),
      ],
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
   * Loads a paragraph revision.
   *
   * @param int|string $revision_id
   *   The revision ID.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   The paragraph revision.
   */
  protected function loadParagraphRevision($revision_id): ParagraphInterface {
    $revision = $this->container->get('entity_type.manager')->getStorage('paragraph')->loadRevision($revision_id);
    $this->assertInstanceOf(ParagraphInterface::class, $revision);
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
