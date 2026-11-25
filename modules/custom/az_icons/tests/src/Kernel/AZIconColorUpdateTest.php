<?php

namespace Drupal\Tests\az_icons\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests renaming the Arizona Bootstrap color names in icons.
 *
 * @see az_icons_post_update_rename_icon_colors()
 */
#[Group('az_icons')]
#[RunTestsInSeparateProcesses]
class AZIconColorUpdateTest extends KernelTestBase {

  use ContentModerationTestTrait;

  /**
   * The start of the revision log message the update writes.
   */
  const LOG_PREFIX = 'az_icons_post_update_rename_icon_colors: ';

  /**
   * The end of the revision log message the update writes.
   */
  const LOG_SUFFIX = ' (for Arizona Bootstrap 5.3 color names)';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'az_card',
    'az_core',
    'az_icons',
    'az_person',
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
    'image',
    'language',
    'media',
    'node',
    'paragraphs',
    'system',
    'text',
    'ui_icons',
    'ui_icons_font',
    'ui_icons_text',
    'user',
    'workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('module_handler')->loadInclude('az_icons', 'php', 'az_icons.post_update');

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('content_moderation_state');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['content_moderation', 'language']);
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Only az_standard shows icons.
    FilterFormat::create([
      'format' => 'az_standard',
      'name' => 'Standard',
      'filters' => [
        'icon_embed' => ['status' => TRUE],
      ],
    ])->save();
    FilterFormat::create([
      'format' => 'plain_text',
      'name' => 'Plain text',
    ])->save();

    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();
    $this->createField('node', 'page', 'body', 'text_with_summary', 1);
    $this->createField('node', 'page', 'field_cards', 'az_card', -1);
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
    // The update saves the node once for each column and language with an
    // old name: the English body, the Spanish body, the summary, and the
    // cards.
    $this->assertSame('Renamed the Arizona Bootstrap color names in icons in 4 field values.', (string) $result);
    $this->assertSame(5, $this->countRevisions($node));
    $this->assertSame(self::LOG_PREFIX . 'Updated field field_cards on node ' . $node->id() . ' for English language' . self::LOG_SUFFIX, $node->getRevisionLogMessage());
    // Old revisions are left as they are.
    $this->assertSame($this->icon('sky'), $this->loadRevision($revision_id)->get('body')->summary);
  }

  /**
   * Tests that content with no old color names, or no icons, is not saved.
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
    // Plain text shows the tag as text, so it's not an icon.
    $plain_text = $this->createNode('page', [
      'body' => [
        'value' => $this->icon('sky'),
        'format' => 'plain_text',
      ],
    ]);

    $result = $this->runUpdate();

    $this->assertSame('Renamed the Arizona Bootstrap color names in icons in 0 field values.', (string) $result);
    $this->assertSame($this->icon('rain'), $this->reload($new_names)->get('body')->value);
    $this->assertSame('<p class="text-sky">"color":"sky"</p>', $this->reload($no_icon)->get('body')->value);
    $this->assertSame($this->icon('sky'), $this->reload($plain_text)->get('body')->value);
    foreach ([$new_names, $no_icon, $plain_text] as $node) {
      $this->assertSame(1, $this->countRevisions($node));
    }
  }

  /**
   * Tests that a moderated page's published revision is renamed.
   *
   * The draft keeps the old names. The new published revision is newer than
   * the draft, so the Edit form opens it instead of the draft. The draft is
   * still in the Revisions tab.
   */
  public function testModeratedPage(): void {
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'page');
    $node = $this->createNode('page', [
      'body' => [
        'value' => $this->icon('sky'),
        'format' => 'az_standard',
      ],
      'moderation_state' => 'published',
    ]);
    $node->set('body', [
      'value' => $this->icon('sky') . '<p>Unfinished edit</p>',
      'format' => 'az_standard',
    ]);
    $node->set('moderation_state', 'draft');
    $node->save();
    $draft_revision_id = $node->getRevisionId();

    $this->runUpdate();

    $published = $this->reload($node);
    $this->assertSame($this->icon('rain'), $published->get('body')->value);
    $this->assertSame('published', $published->get('moderation_state')->value);
    $this->assertStringStartsWith(self::LOG_PREFIX, $published->getRevisionLogMessage());
    $this->assertGreaterThan($draft_revision_id, $published->getRevisionId());
    $this->assertSame($this->icon('sky') . '<p>Unfinished edit</p>', $this->loadRevision($draft_revision_id)->get('body')->value);
    $this->assertSame(3, $this->countRevisions($node));
  }

  /**
   * Tests that a person gets a published revision, not an import draft.
   *
   * @see az_person_node_presave()
   */
  public function testPerson(): void {
    NodeType::create(['type' => 'az_person', 'name' => 'Person'])->save();
    $this->createField('node', 'az_person', 'field_az_body', 'text_long', 1);
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'az_person');
    $person = $this->createNode('az_person', [
      'field_az_body' => [
        'value' => $this->icon('sky'),
        'format' => 'az_standard',
      ],
      'moderation_state' => 'published',
    ]);

    $this->runUpdate();

    $person = $this->reload($person);
    $this->assertSame($this->icon('rain'), $person->get('field_az_body')->value);
    $this->assertSame('published', $person->get('moderation_state')->value);
    $this->assertStringStartsWith(self::LOG_PREFIX, $person->getRevisionLogMessage());
    $this->assertSame(2, $this->countRevisions($person));
  }

  /**
   * Tests that a paragraph's page gets a revision that uses the new paragraph.
   */
  public function testParagraph(): void {
    $this->createParagraphField();
    $paragraph = Paragraph::create([
      'type' => 'text',
      'field_text' => [
        'value' => $this->icon('sky'),
        'format' => 'az_standard',
      ],
    ]);
    $node = $this->createNode('page', ['field_main_content' => [$paragraph]]);

    $this->runUpdate();

    $node = $this->reload($node);
    $item = $node->get('field_main_content')->first()->getValue();
    $this->assertEquals($paragraph->id(), $item['target_id']);
    $this->assertNotEquals($paragraph->getRevisionId(), $item['target_revision_id']);
    $revision = $this->container->get('entity_type.manager')->getStorage('paragraph')->loadRevision($item['target_revision_id']);
    $this->assertInstanceOf(ParagraphInterface::class, $revision);
    $this->assertSame($this->icon('rain'), $revision->get('field_text')->value);
    $this->assertStringStartsWith(self::LOG_PREFIX . 'Updated child text paragraph ' . $paragraph->id(), $node->getRevisionLogMessage());
  }

  /**
   * Tests a published page with a newer draft that edits its paragraph.
   *
   * The live paragraph is renamed, and the page gets 2 published revisions
   * that are newer than the draft, 1 for each paragraph field with an old
   * name. The draft keeps its own copy of the paragraph, with the old names
   * and the unfinished edit.
   */
  public function testDraftParagraph(): void {
    $page = $this->createPageWithDraft();

    $result = $this->runUpdate();

    $this->assertSame('Renamed the Arizona Bootstrap color names in icons in 2 field values.', (string) $result);
    $this->assertSame(4, $this->countRevisions($page['node']));
    $published = $this->reload($page['node']);
    $this->assertSame('published', $published->get('moderation_state')->value);
    $this->assertGreaterThan($page['draft_revision_id'], $published->getRevisionId());
    $paragraph = $this->loadParagraphRevision($published->get('field_main_content')->target_revision_id);
    $this->assertSame($this->icon('rain'), $paragraph->get('field_text')->value);
    $this->assertSame($this->icon('tinta'), $paragraph->get('field_cards')->getValue()[0]['body']);
    $this->assertDraftUnchanged($page);
  }

  /**
   * Tests an entity type that has no revisions.
   */
  public function testNoRevisions(): void {
    $this->createField('user', 'user', 'field_bio', 'text_long', 1);
    $user = User::create([
      'name' => 'person',
      'field_bio' => [
        'value' => $this->icon('sky'),
        'format' => 'az_standard',
      ],
    ]);
    $user->save();

    $result = $this->runUpdate();

    $this->assertSame('Renamed the Arizona Bootstrap color names in icons in 1 field value.', (string) $result);
    $this->assertSame($this->icon('rain'), User::load($user->id())->get('field_bio')->value);
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
    $this->assertSame('Renamed the Arizona Bootstrap color names in icons in 30 field values.', (string) $result);

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
    $this->assertSame('Renamed the Arizona Bootstrap color names in icons in 0 field values.', (string) $result);
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
   * @param bool $translatable
   *   Whether the field is translatable.
   */
  protected function createField(string $entity_type_id, string $bundle, string $field_name, string $type, int $cardinality, array $settings = [], bool $translatable = TRUE): void {
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type_id,
      'type' => $type,
      'cardinality' => $cardinality,
      'settings' => $settings,
    ])->save();
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type_id,
      'bundle' => $bundle,
      'translatable' => $translatable,
    ])->save();
  }

  /**
   * Adds a paragraph field to pages, with a text paragraph type.
   */
  protected function createParagraphField(): void {
    ParagraphsType::create(['id' => 'text', 'label' => 'Text'])->save();
    $this->createField('paragraph', 'text', 'field_text', 'text_long', 1);
    // Quickstart's paragraph fields on content types aren't translatable.
    $this->createField('node', 'page', 'field_main_content', 'entity_reference_revisions', -1, ['target_type' => 'paragraph'], FALSE);
  }

  /**
   * Creates a published page with a paragraph, and a newer draft.
   *
   * The draft is saved the way the edit form saves it: on a copy of the
   * page, with an unfinished edit to the paragraph.
   *
   * @return array
   *   The page, keyed by 'node', and its draft's revision IDs, keyed by
   *   'draft_revision_id' and 'draft_paragraph_revision_id'.
   */
  protected function createPageWithDraft(): array {
    $workflow = $this->createEditorialWorkflow();
    $this->addEntityTypeAndBundleToWorkflow($workflow, 'node', 'page');
    $this->createParagraphField();
    $this->createField('paragraph', 'text', 'field_cards', 'az_card', -1);
    $node = $this->createNode('page', [
      'moderation_state' => 'published',
      'field_main_content' => [
        Paragraph::create([
          'type' => 'text',
          'field_text' => [
            'value' => $this->icon('sky'),
            'format' => 'az_standard',
          ],
          'field_cards' => [
            [
              'title' => 'Card',
              'body' => $this->icon('midnight'),
              'body_format' => 'az_standard',
            ],
          ],
        ]),
      ],
    ]);
    $page = ['node' => $node];

    // Save a clone, as the edit form does (EntityForm::buildEntity()).
    // Saving the page object from the entity cache instead makes the draft's
    // copy of the paragraph its current revision, which the edit form doesn't
    // do.
    $draft = clone $this->reload($node);
    $paragraph = $draft->get('field_main_content')->entity;
    $this->assertInstanceOf(ParagraphInterface::class, $paragraph);
    $paragraph->set('field_text', [
      'value' => $this->icon('sky') . '<p>Unfinished edit</p>',
      'format' => 'az_standard',
    ]);
    $paragraph->setNeedsSave(TRUE);
    $draft->set('moderation_state', 'draft');
    $draft->setNewRevision(TRUE);
    $draft->save();
    $page['draft_revision_id'] = $draft->getRevisionId();
    $page['draft_paragraph_revision_id'] = $draft->get('field_main_content')->target_revision_id;
    return $page;
  }

  /**
   * Asserts that a page's draft and its copy of the paragraph are unchanged.
   *
   * @param array $page
   *   The page and its revision IDs, as createPageWithDraft() returns them.
   */
  protected function assertDraftUnchanged(array $page): void {
    $draft = $this->loadRevision($page['draft_revision_id']);
    $this->assertSame('draft', $draft->get('moderation_state')->value);
    $this->assertEquals($page['draft_paragraph_revision_id'], $draft->get('field_main_content')->target_revision_id);
    $this->assertSame($this->icon('sky') . '<p>Unfinished edit</p>', $this->loadParagraphRevision($page['draft_paragraph_revision_id'])->get('field_text')->value);
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
