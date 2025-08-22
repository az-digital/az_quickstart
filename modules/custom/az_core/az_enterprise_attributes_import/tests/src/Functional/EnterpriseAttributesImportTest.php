<?php

namespace Drupal\Tests\az_core\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_tools\MigrateExecutable;

/**
 * Test of attribute import functionality.
 *
 * @group az_enterprise_attributes_import
 */
class EnterpriseAttributesImportTest extends BrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'az_quickstart';

  /**
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'az_core',
    'az_enterprise_attributes_import',
    'migrate',
    'migrate_tools',
  ];

  /**
   * Tests that our event subscriber can unpublish terms.
   */
  public function testUnpublishAttributes() {
    // Get term storage interface.
    $term_storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');
    // Remove initial attributes from enabling az_enterprise_attributes_import.
    $manager = $this->container->get('plugin.manager.migration');
    $migration = $manager->createInstance('az_enterprise_attributes_import');
    $migrate_exec = new MigrateExecutable(
      $migration,
      new MigrateMessage(),
      $this->container->get('keyvalue'),
      $this->container->get('datetime.time'),
      $this->container->get('string_translation'),
    );
    $migrate_exec->rollback();

    // Build a test attribute list.
    $options = [
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          [
            'key' => 'example_attribute',
            'value' => 'Example Attribute',
            'type' => 'multi-select picklist',
          ],
        ],
      ],
    ];
    // Run migration.
    $migration = $manager->createInstance('az_enterprise_attributes_import', $options);
    $migrate_exec = new MigrateExecutable(
      $migration,
      new MigrateMessage(),
      $this->container->get('keyvalue'),
      $this->container->get('datetime.time'),
      $this->container->get('string_translation'),
    );
    $migrate_exec->import();
    // Get published terms.
    $terms = $term_storage->loadByProperties([
      'vid' => 'az_enterprise_attributes',
      'status' => 1,
    ]);
    // Assert we have exactly one published term.
    $this->assertSame(count($terms), 1);

    // Change source to have no terms available.
    // We want to check if the imported term can be unpublished.
    $options['source']['data_rows'] = [];
    // Rerun modified migration.
    $migration = $manager->createInstance('az_enterprise_attributes_import', $options);
    // Sync the migration to test unpublishing the missing term.
    $sync = [
      'sync' => 1,
      'update' => 1,
    ];
    $migrate_exec = new MigrateExecutable(
      $migration,
      new MigrateMessage(),
      $this->container->get('keyvalue'),
      $this->container->get('datetime.time'),
      $this->container->get('string_translation'),
      $sync,
    );
    $migrate_exec->import();
    // Get unpublished terms.
    $terms = $term_storage->loadByProperties([
      'vid' => 'az_enterprise_attributes',
      'status' => 0,
    ]);
    // Assert we have exactly one unpublished term.
    $this->assertSame(count($terms), 1);
  }

}
