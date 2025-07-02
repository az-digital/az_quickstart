<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_tools\Functional;

use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Execute drush on fully functional website.
 *
 * @group migrate_tools
 */
final class DrushCommandsTest extends BrowserTestBase {
  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate_tools_test',
    'migrate_tools',
    'migrate_plus',
    'taxonomy',
    'text',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests migrate:import with feedback.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testFeedback(): void {
    $this->drush('mim', ['fruit_terms'], ['feedback' => 2]);
    $this->assertMatchesRegularExpression('/1\/3[^\n]+\[notice\][^\n]+Processed 2 items \(2 created, 0 updated, 0 failed, 0 ignored\) - continuing with \'fruit_terms\'/', $this->getErrorOutput());
    $this->assertMatchesRegularExpression('/3\/3[^\n]+\[notice\][^\n]+Processed 1 item \(1 created, 0 updated, 0 failed, 0 ignored\) - done with \'fruit_terms\'/', $this->getErrorOutput());
    $this->assertStringNotContainsString('Processed 4 items', $this->getErrorOutput());
  }

  /**
   * Tests migrate:import with limit.
   */
  public function testLimit(): void {
    $this->drush('mim', ['fruit_terms'], ['limit' => 2]);
    $this->assertMatchesRegularExpression('/\[notice\][^\n]+Processed 2 items \(2 created, 0 updated, 0 failed, 0 ignored\)/', $this->getErrorOutput());
    $this->assertStringContainsString('done with \'fruit_terms\'', $this->getErrorOutput());
    $this->assertStringNotContainsString('Processed 3 items', $this->getErrorOutput());
  }

  /**
   * Test that migrations continue after a failure if the option is set.
   */
  public function testContinueOnFailure(): void {
    // Option not set, fruit_terms should not run.
    $this->drush('mim', ['invalid_plugin,fruit_terms'], [], NULL, NULL, 1);
    $this->assertStringNotContainsString("done with 'fruit_terms'", $this->getErrorOutput());
    // Option set, fruit_terms should run.
    $this->drush('mim', ['invalid_plugin,fruit_terms'], ['continue-on-failure' => NULL]);
    $this->assertStringContainsString("done with 'fruit_terms'", $this->getErrorOutput());
    // Option not set, fruit_terms should not run.
    $this->drush('mr', ['invalid_plugin,fruit_terms'], [], NULL, NULL, 1);
    $this->assertStringNotContainsString("done with 'fruit_terms'", $this->getErrorOutput());
    // Option set, fruit_terms should run.
    $this->drush('mr', ['invalid_plugin,fruit_terms'], ['continue-on-failure' => NULL]);
    $this->assertStringContainsString("done with 'fruit_terms'", $this->getErrorOutput());
    // Option not set, fruit_terms should not display.
    $this->drush('ms', ['invalid_plugin,fruit_terms'], ['format' => 'json'], NULL, NULL, 1);
    // This demonstrates we surface the exception but not as an error.
    $this->assertStringNotContainsString('[error]  The "does_not_exist" plugin does not exist', $this->getErrorOutput());
    $this->assertStringContainsString('The "does_not_exist" plugin does not exist', $this->getErrorOutput());
    $this->assertStringNotContainsString('fruit_terms    Idle     3', $this->getOutput());
    // Option set, fruit_terms should display.
    $this->drush('ms', ['invalid_plugin,fruit_terms'], ['continue-on-failure' => NULL]);
    $this->assertMatchesRegularExpression('/\[error\][^\n]+The "does_not_exist" plugin does not exist/', $this->getErrorOutput());
    $this->assertStringContainsString('fruit_terms    Idle     3', $this->getOutput());
  }

  /**
   * Tests many of the migrate drush commands.
   */
  public function testDrush(): void {
    $this->drush('ms', [], [], NULL, NULL, 1);
    $this->assertStringContainsString('The "does_not_exist" plugin does not exist.', $this->getErrorOutput());
    $this->container->get('config.factory')->getEditable('migrate_plus.migration.invalid_plugin')->delete();
    // Flush cache so the recently removed invalid migration is cleared.
    drupal_flush_all_caches();
    $this->drush('ms', [], ['format' => 'json']);
    $expected = [
      [
        'group' => 'Default (default)',
        'id' => 'fruit_terms',
        'imported' => 0,
        'status' => 'Idle',
        'total' => 3,
        'unprocessed' => 3,
        'message_count' => 0,
        'last_imported' => '',
      ],
      [
        'group' => 'Default (default)',
        'id' => 'source_exception',
        'imported' => 0,
        'status' => 'Idle',
        'total' => 0,
        'unprocessed' => 0,
        'message_count' => 0,
        'last_imported' => '',
      ],
    ];
    $this->assertEquals($expected, $this->getOutputFromJSON());
    $this->drush('mim', ['fruit_terms']);
    $this->assertStringContainsString('1/3', $this->getErrorOutput());
    $this->assertMatchesRegularExpression('/3\/3[^\n]+\[notice\][^\n]+Processed 3 items \(3 created, 0 updated, 0 failed, 0 ignored\) - done with \'fruit_terms\'/', $this->getErrorOutput());
    $this->assertStringNotContainsString('Processed 4 items', $this->getErrorOutput());
    $this->drush('mim', ['fruit_terms'], [
      'update' => NULL,
      'force' => NULL,
      'execute-dependencies' => NULL,
    ]);
    $this->assertStringContainsString('1/3', $this->getErrorOutput());
    $this->assertMatchesRegularExpression('/3\/3[^\n]+\[notice\][^\n]+Processed 3 items \(0 created, 3 updated, 0 failed, 0 ignored\) - done with \'fruit_terms\'/', $this->getErrorOutput());
    $this->assertStringNotContainsString('Processed 4 items', $this->getErrorOutput());
    $this->drush('mrs', ['fruit_terms']);
    $this->assertStringContainsString('Migration fruit_terms is already Idle', $this->getErrorOutput());
    $this->drush('mfs', ['fruit_terms'], ['format' => 'json']);
    $expected = [
      [
        'machine_name' => 'name',
        'description' => 'name',
      ],
    ];
    $this->assertEquals($expected, $this->getOutputFromJSON());
    $this->drush('mr', ['fruit_terms']);
    $this->assertStringContainsString('1/3', $this->getErrorOutput());
    $this->assertMatchesRegularExpression('/3\/3[^\n]+\[notice\][^\n]+Rolled back 3 items - done with \'fruit_terms\'/', $this->getErrorOutput());
    $this->assertStringNotContainsString('Processed 4 items', $this->getErrorOutput());
    $this->drush('migrate:stop', ['fruit_terms']);
    $this->assertMatchesRegularExpression('/warning\][^\n]+Migration fruit_terms is idle/', $this->getErrorOutput());

    $this->drush('mim', ['fruit_terms'], ['skip-progress-bar' => NULL]);
    $this->assertMatchesRegularExpression('/\[notice\][^\n]+Processed 3 items \(3 created, 0 updated, 0 failed, 0 ignored\) - done with \'fruit_terms\'/', $this->getErrorOutput());
    $this->drush('mr', ['fruit_terms'], ['skip-progress-bar' => NULL]);
    $this->assertMatchesRegularExpression('/\[notice\][^\n]+Rolled back 3 items - done with \'fruit_terms\'/', $this->getErrorOutput());
  }

  /**
   * Fully test migrate messages.
   */
  public function testMessages(): void {
    $this->drush('mim', ['fruit_terms']);
    $this->drush('mmsg', ['fruit_terms']);
    $this->assertMatchesRegularExpression('/\[notice\][^\n]+No messages for this migration/', $this->getErrorOutput());
    /** @var \Drupal\migrate\Plugin\MigrateIdMapInterface $id_map */
    $id_map = $this->container->get('plugin.manager.migration')->createInstance('fruit_terms')->getIdMap();
    $id_map->saveMessage(['name' => 'Apple'], 'You picked a bad one.');
    $this->drush('mmsg', ['fruit_terms'], ['format' => 'json']);
    $expected = [
      [
        'level' => 'Error',
        'message' => 'You picked a bad one.',
        'source_ids' => 'Apple',
        'destination_ids' => '1',
      ],
    ];
    $this->assertEquals($expected, $this->getOutputFromJSON());
    $this->drush('mmsg', ['fruit_terms'], ['format' => 'csv']);
    $expected = <<<EOT
"Source ID(s)","Destination ID(s)",Level,Message
Apple,1,Error,"You picked a bad one."
EOT;
    $this->assertEquals($expected, $this->getOutput());
  }

  /**
   * Tests synced import with and without update enforced.
   */
  public function testSyncImport(): void {
    $this->drush('mim', ['fruit_terms']);
    $this->assertStringContainsString('1/3', $this->getErrorOutput());
    $this->assertStringContainsString('3/3', $this->getErrorOutput());
    $this->assertMatchesRegularExpression('/3\/3[^\n]+\[notice\][^\n]+Processed 3 items \(3 created, 0 updated, 0 failed, 0 ignored\) - done with \'fruit_terms\'/', $this->getErrorOutput());
    $this->assertStringNotContainsString('Processed 4 items', $this->getErrorOutput());
    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load(2);
    $this->assertEquals('Banana', $term->label());
    $this->assertEquals(3, \Drupal::entityTypeManager()->getStorage('taxonomy_term')->getQuery()->accessCheck(TRUE)->count()->execute());
    $source = $this->container->get('config.factory')->getEditable('migrate_plus.migration.fruit_terms')->get('source');
    unset($source['data_rows'][1]);
    $source['data_rows'][] = ['name' => 'Grape'];
    $this->container->get('config.factory')->getEditable('migrate_plus.migration.fruit_terms')->set('source', $source)->save();
    // Flush cache so the recently changed migration can be refreshed.
    drupal_flush_all_caches();
    $this->drush('mim', ['fruit_terms'], ['sync' => NULL, 'update' => NULL]);
    $this->assertStringContainsString('1/3', $this->getErrorOutput());
    $this->assertMatchesRegularExpression('/4\/4[^\n]+\[notice\][^\n]+Processed 3 items \(1 created, 2 updated, 0 failed, 0 ignored\) - done with \'fruit_terms\'/', $this->getErrorOutput());
    $this->assertStringNotContainsString('Processed 5 items', $this->getErrorOutput());
    $this->assertEquals(3, \Drupal::entityTypeManager()->getStorage('taxonomy_term')->getQuery()->accessCheck(TRUE)->count()->execute());
    $this->assertEmpty(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->load(2));

    unset($source['data_rows'][2]);
    $source['data_rows'][] = ['name' => 'Pear'];
    $this->container->get('config.factory')->getEditable('migrate_plus.migration.fruit_terms')->set('source', $source)->save();
    // Flush cache so the recently changed migration can be refreshed.
    drupal_flush_all_caches();
    $this->drush('mim', ['fruit_terms'], ['sync' => NULL]);
    $this->assertMatchesRegularExpression('/1\/3[^\n]+\[notice\][^\n]+Rolled back 1 item - done with \'fruit_terms\'/', $this->getErrorOutput());
    $this->assertMatchesRegularExpression('/\[notice\][^\n]+Processed 1 item \(1 created, 0 updated, 0 failed, 0 ignored\) - done with \'fruit_terms\'/', $this->getErrorOutput());
    $this->assertStringNotContainsString('2 updated', $this->getErrorOutput());
    $this->assertStringNotContainsString('5', $this->getErrorOutput());
    $this->assertEquals(3, \Drupal::entityTypeManager()->getStorage('taxonomy_term')->getQuery()->accessCheck(TRUE)->count()->execute());
    $this->assertEmpty(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->load(3));

    /** @var \Drupal\migrate\Plugin\MigrateIdMapInterface $id_map */
    $id_map = $this->container->get('plugin.manager.migration')->createInstance('fruit_terms')->getIdMap();
    $this->assertCount(3, $id_map);
  }

}
