<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Unit\data_fetcher;

use org\bovigo\vfs\vfsStreamDirectory;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\File;
use Drupal\Tests\migrate\Unit\MigrateTestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @file
 * PHPUnit tests for the Migrate Plus File 'data fetcher' plugin.
 */

/**
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\File
 *
 * @group migrate_plus
 */
final class FileTest extends MigrateTestCase {

  /**
   * Directory where test data will be created.
   *
   * @var string
   */
  public const BASE_DIRECTORY = 'migration_data';

  /**
   * Minimal migration configuration data.
   */
  private array $specificMigrationConfig = [
    'source' => 'url',
    'data_fetcher_plugin' => 'file',
    'data_parser_plugin' => 'json',
    'item_selector' => 0,
    'fields' => [],
    'ids' => [
      'id' => [
        'type' => 'integer',
      ],
    ],
  ];

  /**
   * The data fetcher plugin ID being tested.
   */
  private string $dataFetcherPluginId = 'file';

  /**
   * The data fetcher plugin definition.
   */
  private array $pluginDefinition = [
    'id' => 'file',
    'title' => 'File',
  ];

  /**
   * Test data to populate a file with.
   */
  private string $testData = '[
    {
      "id": 1,
      "name": "Joe Bloggs"
    }
  ]';

  /**
   * Define virtual dir where we'll be creating files in/fetching files from.
   */
  private vfsStreamDirectory $baseDir;

  /**
   * Set up test environment.
   */
  public function setUp(): void {
    parent::setUp();
    $this->baseDir = vfsStream::setup(self::BASE_DIRECTORY);
  }

  /**
   * Test fetching a valid file.
   */
  public function testFetchFile(): void {
    $file_name = 'file.json';
    $file_path = vfsStream::url(implode(DIRECTORY_SEPARATOR, [
      self::BASE_DIRECTORY,
      $file_name,
    ]));
    $migration_config = $this->specificMigrationConfig + [
      'urls' => [$file_path],
    ];

    $plugin = new File(
      $migration_config,
      $this->dataFetcherPluginId,
      $this->pluginDefinition
    );

    $tree = [
      $file_name => $this->testData,
    ];

    vfsStream::create($tree, $this->baseDir);

    $expected = json_decode($this->testData, TRUE, 512, JSON_THROW_ON_ERROR);
    $retrieved = json_decode($plugin->getResponseContent($file_path), TRUE, 512, JSON_THROW_ON_ERROR);

    $this->assertEquals($expected, $retrieved);
  }

  /**
   * Test fetching multiple valid files.
   */
  public function testFetchMultipleFiles(): void {
    $number_of_files = 3;
    $file_paths = [];
    $file_names = [];

    for ($i = 0; $i < $number_of_files; $i++) {
      $file_name = 'file_' . $i . '.json';
      $file_names[] = $file_name;
      $file_paths[] = vfsStream::url(implode(DIRECTORY_SEPARATOR, [
        self::BASE_DIRECTORY,
        $file_name,
      ]));
    }

    $migration_config = $this->specificMigrationConfig + [
      'urls' => $file_paths,
    ];

    $plugin = new File(
      $migration_config,
      $this->dataFetcherPluginId,
      $this->pluginDefinition
    );

    for ($i = 0; $i < $number_of_files; $i++) {
      $file_name = $file_names[$i];
      $file_path = $file_paths[$i];

      $tree = [
        $file_name => $this->testData,
      ];

      vfsStream::create($tree, $this->baseDir);

      $expected = json_decode($this->testData, NULL, 512, JSON_THROW_ON_ERROR);
      $retrieved = json_decode($plugin->getResponseContent($file_path), NULL, 512, JSON_THROW_ON_ERROR);

      $this->assertEquals($expected, $retrieved);
    }
  }

  /**
   * Test trying to fetch an unreadable file results in exception.
   */
  public function testFetchUnreadableFile(): void {
    $file_name = 'file.json';
    $file_path = vfsStream::url(implode(DIRECTORY_SEPARATOR, [
      self::BASE_DIRECTORY,
      $file_name,
    ]));
    $migration_config = $this->specificMigrationConfig + [
      'urls' => [$file_path],
    ];

    $plugin = new File(
      $migration_config,
      $this->dataFetcherPluginId,
      $this->pluginDefinition
    );

    // Create an unreadable file.
    vfsStream::newFile($file_name, 0300)
      ->withContent($this->testData)
      ->at($this->baseDir);

    // Trigger exception trying to read the non-readable file.
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('file parser plugin: could not retrieve data from vfs://migration_data/file.json');
    $plugin->getResponseContent($file_path);
  }

}
