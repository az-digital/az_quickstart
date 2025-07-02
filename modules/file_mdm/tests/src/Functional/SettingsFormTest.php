<?php

declare(strict_types=1);

namespace Drupal\Tests\file_mdm\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\file_mdm\FileMetadataManagerInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Settings form test.
 *
 * @group image_effects
 */
class SettingsFormTest extends BrowserTestBase {

  protected static $modules = [
    'dblog',
    'file_mdm',
    'file_mdm_exif',
    'file_mdm_font',
    'vendor_stream_wrapper',
  ];

  protected $defaultTheme = 'stark';

  public function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
    ]));
  }

  /**
   * Tests changing the missing file log setting.
   */
  public function testMissingFileLogging(): void {
    $admin_path = '/admin/config/system/file_mdm';

    // Get the settings form.
    $this->drupalGet($admin_path);
    $this->assertSession()->statusCodeEquals(200);

    // Verify that by default the log level is ERROR.
    $this->assertSession()->fieldValueEquals('missing_file_log_level', (string) RfcLogLevel::ERROR);
    $this->assertEquals(RfcLogLevel::ERROR, $this->config('file_mdm.settings')->get('missing_file_log_level'));

    // Try loading metadata for a non-existing file. We should get an entry in
    // the log.
    $fmdm = $this->container->get(FileMetadataManagerInterface::class);
    $file_metadata = $fmdm->uri('vendor://fileeye/linuxlibertine-fonts/a_not_existing_font_file.ttf');
    $this->assertNull($file_metadata->getMetadata('font', 'FullName'));
    $query = Database::getConnection()->select('watchdog')
      ->condition('type', 'file_mdm');
    $query->addExpression('MAX([wid])');
    $wid = $query->execute()->fetchField();
    $this->assertNotNull($wid);

    // Test changing the log level to None.
    $edit = ['missing_file_log_level' => -1];
    $this->submitForm($edit, 'Save configuration');
    $this->assertEquals(-1, $this->config('file_mdm.settings')->get('missing_file_log_level'));

    // Try loading metadata for another non-existing file. We should not get an
    // entry in the log.
    $file_metadata = $fmdm->uri('vendor://fileeye/linuxlibertine-fonts/another_not_existing_font_file.ttf');
    $this->assertNull($file_metadata->getMetadata('font', 'FullName'));
    $wid_1 = $query->execute()->fetchField();
    $this->assertEquals($wid, $wid_1);

    // Test changing the log level to INFO.
    $edit = ['missing_file_log_level' => RfcLogLevel::INFO];
    $this->submitForm($edit, 'Save configuration');
    $this->assertEquals(RfcLogLevel::INFO, $this->config('file_mdm.settings')->get('missing_file_log_level'));

    // Try loading metadata for yet another non-existing file. We should get an
    // entry in the log.
    $file_metadata = $fmdm->uri('vendor://fileeye/linuxlibertine-fonts/yet_another_not_existing_font_file.ttf');
    $this->assertNull($file_metadata->getMetadata('font', 'FullName'));
    $wid_2 = $query->execute()->fetchField();
    $this->assertGreaterThan($wid, $wid_2);
  }

}
