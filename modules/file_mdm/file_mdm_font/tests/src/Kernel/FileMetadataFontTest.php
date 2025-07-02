<?php

declare(strict_types=1);

namespace Drupal\Tests\file_mdm_font\Kernel;

use Drupal\file_mdm\FileMetadataInterface;
use Drupal\file_mdm\FileMetadataManagerInterface;
use Drupal\Tests\file_mdm\Kernel\FileMetadataManagerTestBase;

/**
 * Tests that the file metadata 'font' plugin works properly.
 *
 * @group file_mdm
 */
class FileMetadataFontTest extends FileMetadataManagerTestBase {

  protected static $modules = [
    'system',
    'file_mdm',
    'file_mdm_font',
    'file_test',
    'vendor_stream_wrapper',
  ];

  public function setUp(): void {
    parent::setUp();
    $this->installConfig(['file_mdm_font']);
  }

  /**
   * Tests 'font' plugin.
   */
  public function testFontPlugin(): void {
    // The font files that will be tested.
    $font_files = [
      [
        'uri' => 'vendor://fileeye/linuxlibertine-fonts/LinLibertine_Rah.ttf',
        'count_keys' => 15,
        'test_keys' => [
          ['Version', 'Version 5.3.0 ; ttfautohint (v0.9)'],
          ['version', 'Version 5.3.0 ; ttfautohint (v0.9)'],
          ['VeRsIoN', 'Version 5.3.0 ; ttfautohint (v0.9)'],
          ['FontWeight', 400],
        ],
      ],
      [
        'uri' => 'vendor://fileeye/linuxlibertine-fonts/LinBiolinum_Kah.ttf',
        'count_keys' => 15,
        'test_keys' => [
          ['FullName', 'Linux Biolinum Keyboard'],
          ['fullname', 'Linux Biolinum Keyboard'],
          ['fUlLnAmE', 'Linux Biolinum Keyboard'],
        ],
      ],
    ];

    $fmdm = $this->container->get(FileMetadataManagerInterface::class);

    // Walk through test files.
    foreach ($font_files as $font_file) {
      $file_metadata = $fmdm->uri($font_file['uri']);
      if (!$file_metadata) {
        $this->fail("File not found: {$font_file['uri']}");
      }
      $this->assertEquals($font_file['count_keys'], $this->countMetadataKeys($file_metadata, 'font'));
      $this->assertSame(FileMetadataInterface::LOADED_FROM_FILE, $file_metadata->isMetadataLoaded('font'));
      foreach ($font_file['test_keys'] as $test) {
        $this->assertEquals($test[1], $file_metadata->getMetadata('font', $test[0]));
      }
    }
  }

  /**
   * Tests 'font' plugin supported keys.
   */
  public function testSupportedKeys(): void {
    $expected_keys = [
      'FontType',
      'FontWeight',
      'Copyright',
      'FontName',
      'FontSubfamily',
      'UniqueID',
      'FullName',
      'Version',
      'PostScriptName',
      'Trademark',
      'Manufacturer',
      'Designer',
      'Description',
      'FontVendorURL',
      'FontDesignerURL',
      'LicenseDescription',
      'LicenseURL',
      'PreferredFamily',
      'PreferredSubfamily',
      'CompatibleFullName',
      'SampleText',
    ];

    $fmdm = $this->container->get(FileMetadataManagerInterface::class);
    $file_md = $fmdm->uri('vendor://fileeye/linuxlibertine-fonts/LinLibertine_Rah.ttf');
    $this->assertEquals($expected_keys, $file_md->getSupportedKeys('font'));
  }

}
