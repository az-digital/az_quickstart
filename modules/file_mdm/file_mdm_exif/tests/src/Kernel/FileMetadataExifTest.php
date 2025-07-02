<?php

declare(strict_types=1);

namespace Drupal\Tests\file_mdm_exif\Kernel;

use Drupal\Core\File\FileExists;
use Drupal\file_mdm\FileMetadataInterface;
use Drupal\file_mdm\FileMetadataManagerInterface;
use Drupal\file_mdm_exif\ExifTagMapperInterface;
use Drupal\Tests\file_mdm\Kernel\FileMetadataManagerTestBase;
use lsolesen\pel\PelEntryAscii;
use lsolesen\pel\PelEntryRational;
use lsolesen\pel\PelEntryShort;
use lsolesen\pel\PelEntrySRational;

/**
 * Tests that File Metadata EXIF plugin works properly.
 *
 * @group file_mdm
 */
class FileMetadataExifTest extends FileMetadataManagerTestBase {

  protected static $modules = [
    'system',
    'file_mdm',
    'file_mdm_exif',
    'file_test',
  ];

  public function setUp(): void {
    parent::setUp();
    $this->installConfig(['file_mdm_exif']);
  }

  /**
   * Tests EXIF plugin.
   */
  public function testExifPlugin(): void {
    // Prepare a copy of test files.
    $this->fileSystem->copy('core/tests/fixtures/files/image-test.jpg', 'public://', FileExists::Replace);
    $this->fileSystem->copy('core/tests/fixtures/files/image-test.png', 'public://', FileExists::Replace);
    $this->fileSystem->copy($this->moduleList->getPath('file_mdm') . '/tests/files/test-exif.jpeg', 'public://', FileExists::Replace);
    $this->fileSystem->copy($this->moduleList->getPath('file_mdm') . '/tests/files/test-exif.jpeg', 'temporary://', FileExists::Replace);
    // The image files that will be tested.
    $image_files = [
      [
        // Pass a path instead of the URI.
        'uri' => $this->moduleList->getPath('file_mdm') . '/tests/files/test-exif.jpeg',
        'count_keys' => 47,
        'test_keys' => [
          ['Orientation', 8],
          ['orientation', 8],
          ['OrIeNtAtIoN', 8],
          ['ShutterSpeedValue', [106, 32]],
          ['ApertureValue', [128, 32]],
          [['exif', 'aperturevalue'], [128, 32]],
          [[2, 'aperturevalue'], [128, 32]],
          [['exif', 0x9202], [128, 32]],
          [[2, 0x9202], [128, 32]],
        ],
      ],
      [
        // Pass a URI.
        'uri' => 'public://test-exif.jpeg',
        'count_keys' => 47,
        'test_keys' => [
          ['Orientation', 8],
          ['ShutterSpeedValue', [106, 32]],
        ],
      ],
      [
        // Remote storage file. Let the file be copied to a local temp copy.
        'uri' => 'dummy-remote://test-exif.jpeg',
        'copy_to_temp' => TRUE,
        'count_keys' => 47,
        'test_keys' => [
          ['Orientation', 8],
          ['ShutterSpeedValue', [106, 32]],
        ],
      ],
      [
        // JPEG Image with GPS data.
        'uri' => $this->moduleList->getPath('file_mdm') . '/tests/files/1024-2006_1011_093752.jpg',
        'count_keys' => 59,
        'test_keys' => [
          ['Orientation', 1],
          ['FocalLength', [8513, 256]],
          ['GPSLatitudeRef', 'S'],
          ['GPSLatitude', [[33, 1], [51, 1], [2191, 100]]],
          ['GPSLongitudeRef', 'E'],
          ['GPSLongitude', [[151, 1], [13, 1], [1173, 100]]],
        ],
      ],
      [
        // JPEG Image with no EXIF data.
        'uri' => 'public://image-test.jpg',
        'count_keys' => 0,
        'test_keys' => [],
      ],
      [
        // TIFF image.
        'uri' => $this->moduleList->getPath('file_mdm') . '/tests/files/sample-1.tiff',
        'count_keys' => 15,
        'test_keys' => [
          ['Orientation', 1],
          ['BitsPerSample', [8, 8, 8, 8]],
        ],
      ],
      [
        // PNG should not have any data.
        'uri' => 'public://image-test.png',
        'count_keys' => 0,
        'test_keys' => [],
      ],
    ];

    $fmdm = $this->container->get(FileMetadataManagerInterface::class);

    // Walk through test files.
    foreach ($image_files as $image_file) {
      $file_metadata = $fmdm->uri($image_file['uri']);
      if (!$file_metadata) {
        $this->fail("File not found: {$image_file['uri']}");
      }
      if (isset($image_file['copy_to_temp'])) {
        $file_metadata->copyUriToTemp();
      }
      $this->assertEquals($image_file['count_keys'], $this->countMetadataKeys($file_metadata, 'exif'));
      foreach ($image_file['test_keys'] as $test) {
        $entry = $file_metadata->getMetadata('exif', $test[0]);
        $this->assertEquals($test[1], $entry ? $entry['value'] : NULL);
      }
    }

    // Test loading metadata from an in-memory object.
    $file_metadata_from = $fmdm->uri($image_files[0]['uri']);
    $metadata = $file_metadata_from->getMetadata('exif');
    $new_file_metadata = $fmdm->uri('public://test-output.jpeg');
    $new_file_metadata->loadMetadata('exif', $metadata);
    $this->assertEquals($image_files[0]['count_keys'], $this->countMetadataKeys($new_file_metadata, 'exif'));
    foreach ($image_files[0]['test_keys'] as $test) {
      $entry = $file_metadata->getMetadata('exif', $test[0]);
      $this->assertEquals($test[1], $new_file_metadata->getMetadata('exif', $test[0])['value']);
    }

    // Test removing metadata.
    $fmdm->release($image_files[0]['uri']);
    $this->assertFalse($fmdm->has($image_files[0]['uri']));
    $file_metadata = $fmdm->uri($image_files[0]['uri']);
    $this->assertEquals($image_files[0]['count_keys'], $this->countMetadataKeys($file_metadata, 'exif'));
    $this->assertTrue($file_metadata->removeMetadata('exif', 'shutterspeedValue'));
    $this->assertTrue($file_metadata->removeMetadata('exif', 'apertureValue'));
    $this->assertFalse($file_metadata->removeMetadata('exif', 'bar'));
    $this->assertEquals($image_files[0]['count_keys'] - 2, $this->countMetadataKeys($file_metadata, 'exif'));
    $this->assertNull($file_metadata->getMetadata('exif', 'shutterspeedValue'));
    $this->assertNull($file_metadata->getMetadata('exif', 'apertureValue'));
    $this->assertNotNull($file_metadata->getMetadata('exif', 'orientation'));
  }

  /**
   * Tests writing metadata to JPEG file.
   */
  public function testJpegExifSaveToFile(): void {
    $fmdm = $this->container->get(FileMetadataManagerInterface::class);

    // Copy test file to public://.
    $this->fileSystem->copy($this->moduleList->getPath('file_mdm') . '/tests/files/portrait-painting.jpg', 'public://', FileExists::Replace);
    $file_uri = 'public://portrait-painting.jpg';
    $file_metadata = $fmdm->uri($file_uri);

    // Check values via exif_read_data before operations.
    $data = @exif_read_data($file_uri);
    $this->assertEquals(8, $data['Orientation']);
    $this->assertFalse(isset($data['Artist']));
    $this->assertEquals('Canon', $data['Make']);
    $this->assertEquals(800, $data['ISOSpeedRatings']);

    // Change the Orientation tag from IFD0.
    $this->assertEquals(8, $file_metadata->getMetadata('exif', 'orientation')['value']);
    $data['Orientation'] = 4;
    $this->assertTrue($file_metadata->setMetadata('exif', 'orientation', $data['Orientation']));
    $this->assertEquals($data['Orientation'], $file_metadata->getMetadata('exif', 'orientation')['value']);
    // Add the Artist tag to IFD0.
    $data['Artist'] = 'shot by foo!';
    $this->assertEquals(47, $this->countMetadataKeys($file_metadata, 'exif'));
    $this->assertNull($file_metadata->getMetadata('exif', 'artist'));
    $artist_tag = $this->container->get(ExifTagMapperInterface::class)->resolveKeyToIfdAndTag('artist');
    $artist = new PelEntryAscii($artist_tag['tag'], $data['Artist']);
    $file_metadata->setMetadata('exif', 'artist', $artist);
    $this->assertNotNull($file_metadata->getMetadata('exif', 'artist'));
    $this->assertEquals($data['Artist'], $file_metadata->getMetadata('exif', 'artist')['value']);
    $this->assertEquals(48, $this->countMetadataKeys($file_metadata, 'exif'));
    // Setting an unknown tag leads to failure.
    $this->assertFalse($file_metadata->setMetadata('exif', 'bar', 'qux'));
    // Remove the ResolutionUnit tag from IFD0.
    unset($data['ResolutionUnit']);
    $this->assertEquals(2, $file_metadata->getMetadata('exif', 'ResolutionUnit')['value']);
    $this->assertTrue($file_metadata->removeMetadata('exif', 'ResolutionUnit'));
    $this->assertNull($file_metadata->getMetadata('exif', 'ResolutionUnit'));
    $this->assertEquals(47, $this->countMetadataKeys($file_metadata, 'exif'));

    // Add the ImageDescription tag to IFD1.
    $data['THUMBNAIL']['ImageDescription'] = 'awesome!';
    $this->assertNull($file_metadata->getMetadata('exif', [1, 'imagedescription']));
    $desc_tag = $this->container->get(ExifTagMapperInterface::class)->resolveKeyToIfdAndTag([1, 'imagedescription']);
    $desc = new PelEntryAscii($desc_tag['tag'], $data['THUMBNAIL']['ImageDescription']);
    $file_metadata->setMetadata('exif', [1, 'imagedescription'], $desc);
    $this->assertNotNull($file_metadata->getMetadata('exif', [1, 'imagedescription']));
    $this->assertEquals($data['THUMBNAIL']['ImageDescription'], $file_metadata->getMetadata('exif', [
      1,
      'imagedescription',
    ])['value']);
    $this->assertEquals(48, $this->countMetadataKeys($file_metadata, 'exif'));
    // Remove the Compression tag from IFD1.
    unset($data['THUMBNAIL']['Compression']);
    $this->assertEquals(6, $file_metadata->getMetadata('exif', [1, 'compression'])['value']);
    $this->assertTrue($file_metadata->removeMetadata('exif', [1, 'compression']));
    $this->assertNull($file_metadata->getMetadata('exif', [1, 'compression']));
    $this->assertEquals(47, $this->countMetadataKeys($file_metadata, 'exif'));

    // Add the BrightnessValue tag to EXIF.
    $data['BrightnessValue'] = '12/4';
    $this->assertNull($file_metadata->getMetadata('exif', ['exif', 'brightnessvalue']));
    $brightness_tag = $this->container->get(ExifTagMapperInterface::class)->resolveKeyToIfdAndTag([
      'exif',
      'brightnessvalue',
    ]);
    $brightness = new PelEntrySRational($brightness_tag['tag'], [12, 4]);
    $file_metadata->setMetadata('exif', ['exif', 'brightnessvalue'], $brightness);
    $this->assertNotNull($file_metadata->getMetadata('exif', ['exif', 'brightnessvalue']));
    $this->assertEquals($data['BrightnessValue'], $file_metadata->getMetadata('exif', ['exif', 'brightnessvalue'])['text']);
    $this->assertEquals(48, $this->countMetadataKeys($file_metadata, 'exif'));
    // Remove the ISOSpeedRatings tag from EXIF.
    unset($data['ISOSpeedRatings']);
    $this->assertEquals(800, $file_metadata->getMetadata('exif', ['exif', 'isospeedratings'])['value']);
    $this->assertTrue($file_metadata->removeMetadata('exif', ['exif', 'isospeedratings']));
    $this->assertNull($file_metadata->getMetadata('exif', ['exif', 'isospeedratings']));
    $this->assertEquals(47, $this->countMetadataKeys($file_metadata, 'exif'));

    // Add the RelatedImageFileFormat tag to INTEROP.
    $data['RelatedFileFormat'] = 'qux';
    $this->assertNull($file_metadata->getMetadata('exif', ['interop', 'RelatedImageFileFormat']));
    $ff_tag = $this->container->get(ExifTagMapperInterface::class)->resolveKeyToIfdAndTag([
      'interop',
      'RelatedImageFileFormat',
    ]);
    $ff = new PelEntryAscii($ff_tag['tag'], $data['RelatedFileFormat']);
    $file_metadata->setMetadata('exif', ['interop', 'RelatedImageFileFormat'], $ff);
    $this->assertNotNull($file_metadata->getMetadata('exif', ['interop', 'RelatedImageFileFormat']));
    $this->assertEquals(
      $data['RelatedFileFormat'],
      $file_metadata->getMetadata('exif', ['interop', 'RelatedImageFileFormat'])['text']
    );
    $this->assertEquals(48, $this->countMetadataKeys($file_metadata, 'exif'));
    // Remove the InteroperabilityIndex tag from INTEROP.
    unset($data['InterOperabilityIndex']);
    $this->assertEquals('R98', $file_metadata->getMetadata('exif', ['interop', 'InteroperabilityIndex'])['value']);
    $this->assertTrue($file_metadata->removeMetadata('exif', ['interop', 'InteroperabilityIndex']));
    $this->assertNull($file_metadata->getMetadata('exif', ['interop', 'InteroperabilityIndex']));
    $this->assertEquals(47, $this->countMetadataKeys($file_metadata, 'exif'));

    // Add Longitude/Latitude tags to GPS.
    $this->assertNull($file_metadata->getMetadata('exif', 'GPSLatitudeRef'));
    $this->assertNull($file_metadata->getMetadata('exif', 'GPSLatitude'));
    $this->assertNull($file_metadata->getMetadata('exif', 'GPSLongitudeRef'));
    $this->assertNull($file_metadata->getMetadata('exif', 'GPSLongitude'));
    $atr_tag = $this->container->get(ExifTagMapperInterface::class)->resolveKeyToIfdAndTag('GPSLatitudeRef');
    $at_tag = $this->container->get(ExifTagMapperInterface::class)->resolveKeyToIfdAndTag('GPSLatitude');
    $otr_tag = $this->container->get(ExifTagMapperInterface::class)->resolveKeyToIfdAndTag('GPSLongitudeRef');
    $ot_tag = $this->container->get(ExifTagMapperInterface::class)->resolveKeyToIfdAndTag('GPSLongitude');
    $data['GPSLatitudeRef'] = 'N';
    $atr = new PelEntryAscii($atr_tag['tag'], $data['GPSLatitudeRef']);
    $data['GPSLatitude'] = ['46/1', '37/1', '59448/10000'];
    $at = new PelEntryRational($at_tag['tag'], [46, 1], [37, 1], [59448, 10000]);
    $data['GPSLongitudeRef'] = 'E';
    $otr = new PelEntryAscii($otr_tag['tag'], $data['GPSLongitudeRef']);
    $data['GPSLongitude'] = ['12/1', '17/1', '488112/10000'];
    $ot = new PelEntryRational($ot_tag['tag'], [12, 1], [17, 1], [488112, 10000]);
    $file_metadata->setMetadata('exif', 'GPSLatitudeRef', $atr);
    $file_metadata->setMetadata('exif', 'GPSLatitude', $at);
    $file_metadata->setMetadata('exif', 'GPSLongitudeRef', $otr);
    $file_metadata->setMetadata('exif', 'GPSLongitude', $ot);
    $this->assertNotNull($file_metadata->getMetadata('exif', 'GPSLatitudeRef'));
    $this->assertNotNull($file_metadata->getMetadata('exif', 'GPSLatitude'));
    $this->assertNotNull($file_metadata->getMetadata('exif', 'GPSLongitudeRef'));
    $this->assertNotNull($file_metadata->getMetadata('exif', 'GPSLongitude'));
    $this->assertEquals($data['GPSLatitudeRef'], $file_metadata->getMetadata('exif', 'GPSLatitudeRef')['text']);
    $this->assertEquals('46° 37\' 5.9448" (46.62°)', $file_metadata->getMetadata('exif', 'GPSLatitude')['text']);
    $this->assertEquals($data['GPSLongitudeRef'], $file_metadata->getMetadata('exif', 'GPSLongitudeRef')['text']);
    $this->assertEquals('12° 17\' 48.8112" (12.30°)', $file_metadata->getMetadata('exif', 'GPSLongitude')['text']);
    $this->assertEquals(51, $this->countMetadataKeys($file_metadata, 'exif'));

    // Save metadata to file.
    $this->assertTrue($file_metadata->saveMetadataToFile('exif'));

    // Check results via exif_read_data. A complete check would be through
    // $this->assertEquals($data, $data_reloaded).
    $data_reloaded = @exif_read_data($file_uri);
    $this->assertEquals($data['Orientation'], $data_reloaded['Orientation']);
    $this->assertEquals($data['Artist'], $data_reloaded['Artist']);
    $this->assertFalse(isset($data_reloaded['ResolutionUnit']));
    $this->assertEquals($data['THUMBNAIL']['ImageDescription'], $data_reloaded['THUMBNAIL']['ImageDescription']);
    $this->assertFalse(isset($data_reloaded['THUMBNAIL']['Compression']));
    $this->assertEquals($data['BrightnessValue'], $data_reloaded['BrightnessValue']);
    $this->assertFalse(isset($data_reloaded['ISOSpeedRatings']));
    $this->assertEquals($data['RelatedFileFormat'], $data_reloaded['RelatedFileFormat']);
    $this->assertFalse(isset($data_reloaded['InterOperabilityIndex']));
    $this->assertEquals($data['GPSLatitudeRef'], $data_reloaded['GPSLatitudeRef']);
    $this->assertEquals($data['GPSLatitude'], $data_reloaded['GPSLatitude']);
    $this->assertEquals($data['GPSLongitudeRef'], $data_reloaded['GPSLongitudeRef']);
    $this->assertEquals($data['GPSLongitude'], $data_reloaded['GPSLongitude']);

    // Test writing metadata to a file that has no EXIF info.
    $this->fileSystem->copy('core/tests/fixtures/files/image-2.jpg', 'public://', FileExists::Replace);
    $test_2 = $fmdm->uri('public://image-2.jpg');
    $this->assertEquals(0, $this->countMetadataKeys($test_2, 'exif'));
    // Load EXIF metadata from previous file processed.
    $test_2->loadMetadata('exif', $file_metadata->getMetadata('exif'));
    // Save metadata to file.
    $this->assertTrue($test_2->saveMetadataToFile('exif'));
    $this->assertEquals(51, $this->countMetadataKeys($test_2, 'exif'));

    // Check results via exif_read_data. A complete check would be through
    // $this->assertEquals($data, $data_reloaded).
    $data_reloaded = @exif_read_data('public://image-2.jpg');
    $this->assertEquals($data['Orientation'], $data_reloaded['Orientation']);
    $this->assertEquals($data['Artist'], $data_reloaded['Artist']);
    $this->assertEquals($data['THUMBNAIL']['ImageDescription'], $data_reloaded['THUMBNAIL']['ImageDescription']);
    $this->assertEquals($data['BrightnessValue'], $data_reloaded['BrightnessValue']);
    $this->assertEquals($data['RelatedFileFormat'], $data_reloaded['RelatedFileFormat']);
    $this->assertEquals($data['GPSLatitudeRef'], $data_reloaded['GPSLatitudeRef']);
    $this->assertEquals($data['GPSLatitude'], $data_reloaded['GPSLatitude']);
    $this->assertEquals($data['GPSLongitudeRef'], $data_reloaded['GPSLongitudeRef']);
    $this->assertEquals($data['GPSLongitude'], $data_reloaded['GPSLongitude']);

    // Check that after save, file metadata is retrieved from file first time,
    // then from cache in further requests.
    $file_metadata = NULL;
    $this->assertTrue($fmdm->release($file_uri));
    $file_metadata = $fmdm->uri($file_uri);
    $this->assertEquals(51, $this->countMetadataKeys($file_metadata, 'exif'));
    $this->assertSame(FileMetadataInterface::LOADED_FROM_FILE, $file_metadata->isMetadataLoaded('exif'));
    $file_metadata = NULL;
    $this->assertTrue($fmdm->release($file_uri));
    $file_metadata = $fmdm->uri($file_uri);
    $this->assertEquals(51, $this->countMetadataKeys($file_metadata, 'exif'));
    $this->assertSame(FileMetadataInterface::LOADED_FROM_CACHE, $file_metadata->isMetadataLoaded('exif'));
  }

  /**
   * Tests writing metadata to TIFF file.
   */
  public function testTiffExifSaveToFile(): void {
    $fmdm = $this->container->get(FileMetadataManagerInterface::class);

    // Copy test file to public://.
    $this->fileSystem->copy($this->moduleList->getPath('file_mdm') . '/tests/files/sample-1.tiff', 'public://', FileExists::Replace);
    $file_uri = 'public://sample-1.tiff';
    $file_metadata = $fmdm->uri($file_uri);

    // Check values via exif_read_data before operations.
    $data = @exif_read_data($file_uri);
    $this->assertEquals(1, $data['Orientation']);
    $this->assertEquals(2, $data['PhotometricInterpretation']);
    $this->assertEquals([8, 8, 8, 8], $data['BitsPerSample']);

    // Change tags from IFD0.
    $this->assertEquals(15, $this->countMetadataKeys($file_metadata, 'exif'));

    $this->assertEquals(1, $file_metadata->getMetadata('exif', 'orientation')['value']);
    $data['Orientation'] = 4;
    $this->assertTrue($file_metadata->setMetadata('exif', 'orientation', $data['Orientation']));
    $this->assertEquals($data['Orientation'], $file_metadata->getMetadata('exif', 'orientation')['value']);

    $this->assertEquals(2, $file_metadata->getMetadata('exif', 'PhotometricInterpretation')['value']);
    $data['PhotometricInterpretation'] = 4;
    $this->assertTrue($file_metadata->setMetadata('exif', 'PhotometricInterpretation', $data['PhotometricInterpretation']));
    $this->assertEquals($data['PhotometricInterpretation'], $file_metadata->getMetadata('exif', 'PhotometricInterpretation')['value']);

    $this->assertEquals([8, 8, 8, 8], $file_metadata->getMetadata('exif', 'BitsPerSample')['value']);
    $data['BitsPerSample'] = [7, 6, 5, 4];
    $bps_tag = $this->container->get(ExifTagMapperInterface::class)->resolveKeyToIfdAndTag('BitsPerSample');
    $bps = new PelEntryShort($bps_tag['tag'], $data['BitsPerSample']);
    $this->assertTrue($file_metadata->setMetadata('exif', 'BitsPerSample', $bps));
    $this->assertEquals($data['BitsPerSample'], $file_metadata->getMetadata('exif', 'BitsPerSample')['value']);

    // Save metadata to file.
    $this->assertTrue($file_metadata->saveMetadataToFile('exif'));

    // Check results via exif_read_data.
    $data_reloaded = @exif_read_data($file_uri);
    $this->assertEquals(4, $data_reloaded['Orientation']);
    $this->assertEquals(4, $data_reloaded['PhotometricInterpretation']);
    $this->assertEquals([7, 6, 5, 4], $data_reloaded['BitsPerSample']);
  }

}
