<?php

namespace Drupal\Tests\metatag\Kernel\Migrate\d7;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Tests migration of per-entity data from Metatag-D7.
 *
 * @group metatag
 */
class MetatagEntitiesTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Core modules.
    // @see testAvailableConfigEntities
    'comment',
    'content_translation',
    'datetime',
    'datetime_range',
    'filter',
    'image',
    'language',
    'link',
    'menu_link_content',
    'menu_ui',
    'node',
    'taxonomy',
    'telephone',
    'text',

    // Contrib modules.
    'token',

    // This module.
    'metatag',
  ];

  /**
   * Prepare the file migration for running.
   *
   * Copied from FileMigrationSetupTrait from 8.4 so that this doesn't have to
   * then also extend getFileMigrationInfo().
   */
  protected function fileMigrationSetup(): void {
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('file');
    $this->container->get('stream_wrapper_manager')
      ->registerWrapper('public', PublicStream::class, StreamWrapperInterface::NORMAL);

    $fs = \Drupal::service('file_system');
    // The public file directory active during the test will serve as the
    // root of the fictional Drupal 7 site we're migrating.
    $fs->mkdir('public://sites/default/files', NULL, TRUE);
    file_put_contents('public://sites/default/files/cube.jpeg', str_repeat('*', 3620));

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->getMigration('d7_file');
    // Set the source plugin's source_base_path configuration value, which
    // would normally be set by the user running the migration.
    $source = $migration->getSourceConfiguration();
    $source['constants']['source_base_path'] = $fs->realpath('public://');
    $migration->set('source', $source);
    $this->executeMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture(__DIR__ . '/../../../../fixtures/d7_metatag.php');

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('menu_link_content');
    $this->installConfig(static::$modules);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('metatag_defaults');

    // Run each migration to avoid problems. No, it's not clear why.
    $this->executeMigrations(['language']);
    $this->executeMigrations(['d7_metatag_field']);
    $this->executeMigrations(['d7_node_type']);
    $this->executeMigrations(['d7_taxonomy_vocabulary']);
    $this->executeMigrations(['d7_metatag_field_instance']);
    $this->executeMigrations(['d7_metatag_field_instance_widget_settings']);
    $this->executeMigrations(['d7_user_role']);
    $this->executeMigrations(['d7_user']);
    $this->executeMigrations(['d7_comment_type']);
    $this->executeMigrations(['d7_field']);
    $this->executeMigrations(['d7_field_instance']);
    $this->executeMigrations(['d7_language_content_settings']);

    $this->fileMigrationSetup();
    $this->executeMigrations([
      'd7_node_complete',
      'd7_taxonomy_term',
    ]);
  }

  /**
   * Test Metatag entity data migration from Drupal 7 to 8.
   */
  public function testMetatagEntities() {
    /** @var \Drupal\node\Entity\Node $node */
    $node = Node::load(998);
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertTrue($node->hasField('field_metatag'));
    // This should have the "current revision" keywords value, indicating it is
    // the current revision.
    $expected = [
      'canonical_url' => 'the-node',
      'keywords' => 'current revision',
      'robots' => 'noindex, nofollow',
    ];
    $this->assertSame(Json::encode($expected), $node->field_metatag->value);

    $node_storage_manager = \Drupal::entityTypeManager()
      ->getStorage('node');
    $node = $node_storage_manager->loadRevision(998);
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertTrue($node->hasField('field_metatag'));
    // This should have the "old revision" keywords value, indicating it is
    // a non-current revision.
    $expected = [
      'canonical_url' => 'the-node',
      'keywords' => 'old revision',
      'robots' => 'noindex, nofollow',
    ];
    $this->assertSame(Json::encode($expected), $node->field_metatag->value);

    /** @var \Drupal\user\Entity\User $user */
    $user = User::load(2);
    $this->assertInstanceOf(UserInterface::class, $user);
    $this->assertTrue($user->hasField('field_metatag'));
    // This should have the Utf8 converted description value.
    $expected = [
      'canonical_url' => 'the-user',
      'description' => 'Drupal™ user',
      'keywords' => 'a user',
    ];
    $this->assertSame(Json::encode($expected), $user->field_metatag->value);

    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = Term::load(152);
    $this->assertInstanceOf(TermInterface::class, $term);
    $this->assertTrue($term->hasField('field_metatag'));
    $expected = [
      'canonical_url' => 'the-term',
      'keywords' => 'a taxonomy',
    ];
    $this->assertSame(Json::encode($expected), $term->field_metatag->value);
  }

}
