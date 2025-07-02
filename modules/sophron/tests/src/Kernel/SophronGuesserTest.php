<?php

declare(strict_types=1);

namespace Drupal\Tests\sophron\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\sophron_guesser\SophronMimeTypeGuesser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for Sophron guesser.
 *
 * @coversDefaultClass \Drupal\sophron_guesser\SophronMimeTypeGuesser
 *
 * @group sophron
 */
#[CoversClass(SophronMimeTypeGuesser::class)]
#[Group('sophron')]
class SophronGuesserTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['sophron', 'system'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installConfig(['sophron', 'system']);
  }

  /**
   * Tests guesser not installed.
   *
   * @legacy-covers ::guessMimeType
   */
  public function testGuesserNotInstalled(): void {
    $guesser = \Drupal::service('file.mime_type.guesser.extension');
    $this->assertNull($guesser->guessMimeType('fake.jp2'));
  }

  /**
   * Tests guesser installed.
   *
   * @legacy-covers ::guessMimeType
   */
  public function testGuesserInstalled(): void {
    \Drupal::service('module_installer')->install(['sophron_guesser']);
    $guesser = \Drupal::service('file.mime_type.guesser.extension');
    $this->assertEquals('image/jp2', $guesser->guessMimeType('fake.jp2'));
  }

  /**
   * Tests guesser install and uninstall.
   *
   * @legacy-covers ::guessMimeType
   */
  public function testGuesserInstallUninstall(): void {
    $guesser = \Drupal::service('file.mime_type.guesser.extension');
    $this->assertNull($guesser->guessMimeType('fake.jp2'));
    \Drupal::service('module_installer')->install(['sophron_guesser']);
    $guesser = \Drupal::service('file.mime_type.guesser.extension');
    $this->assertEquals('image/jp2', $guesser->guessMimeType('fake.jp2'));
    \Drupal::service('module_installer')->uninstall(['sophron_guesser']);
    $guesser = \Drupal::service('file.mime_type.guesser.extension');
    $this->assertNull($guesser->guessMimeType('fake.jp2'));
  }

  /**
   * Test mapping of mimetypes from filenames.
   *
   * Mostly a copy of the equivalent method at
   * \Drupal\KernelTests\Core\File\MimeTypeTest::testFileMimeTypeDetection.
   */
  public function testFileMimeTypeDetection(): void {
    \Drupal::service('module_installer')->install(['sophron_guesser']);

    $prefixes = ['public://', 'private://', 'temporary://', 'dummy-remote://'];

    $test_case = [
      'test.jar' => 'application/java-archive',
      'test.jpeg' => 'image/jpeg',
      'test.JPEG' => 'image/jpeg',
      'test.jpg' => 'image/jpeg',
      'test.jar.jpg' => 'image/jpeg',
      'test.jpg.jar' => 'application/java-archive',
      'test.pcf.Z' => 'application/x-font',
      'pcf.z' => 'application/x-compress',
      'jar' => 'application/octet-stream',
      'some.junk' => 'application/octet-stream',
      'foo.file_test_1' => 'application/octet-stream',
      'foo.file_test_2' => 'application/octet-stream',
      'foo.doc' => 'application/msword',
      'test.ogg' => 'audio/ogg',
      'foobar.0.zip' => 'application/zip',
      'foobar..zip' => 'application/zip',
    ];

    $guesser = $this->container->get('file.mime_type.guesser');
    // Test using default mappings.
    foreach ($test_case as $input => $expected) {
      // Test stream [URI].
      foreach ($prefixes as $prefix) {
        $output = $guesser->guessMimeType($prefix . $input);
        $this->assertSame($expected, $output, sprintf("Mimetype for '%s' is '%s' (expected: '%s').", $prefix . $input, $output, $expected));
      }

      // Test normal path equivalent.
      $output = $guesser->guessMimeType($input);
      $this->assertSame($expected, $output, sprintf("Mimetype (using default mappings) for '%s' is '%s' (expected: '%s').", $input, $output, $expected));
    }
  }

}
