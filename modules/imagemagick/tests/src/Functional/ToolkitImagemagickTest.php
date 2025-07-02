<?php

declare(strict_types=1);

namespace Drupal\Tests\imagemagick\Functional;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileExists;
use Drupal\Core\Image\ImageInterface;
use Drupal\imagemagick\EventSubscriber\ImagemagickEventSubscriber;
use Drupal\imagemagick\PackageSuite;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\imagemagick\Kernel\ToolkitSetupTrait;

/**
 * Tests that core image manipulations work properly through Imagemagick.
 *
 * @group imagemagick
 */
class ToolkitImagemagickTest extends BrowserTestBase {

  use ToolkitSetupTrait;

  // Colors that are used in testing.
  // @codingStandardsIgnoreStart
  protected array $black             = [  0,   0,   0,   0];
  protected array $red               = [255,   0,   0,   0];
  protected array $green             = [  0, 255,   0,   0];
  protected array $blue              = [  0,   0, 255,   0];
  protected array $yellow            = [255, 255,   0,   0];
  protected array $fuchsia           = [255,   0, 255,   0];
  protected array $cyan              = [  0, 255, 255,   0];
  protected array $white             = [255, 255, 255,   0];
  protected array $grey              = [128, 128, 128,   0];
  protected array $transparent       = [  0,   0,   0, 127];
  protected array $rotateTransparent = [255, 255, 255, 127];

  protected int $width = 40;
  protected int $height = 20;
  // @codingStandardsIgnoreEnd

  /**
   * Modules to enable.
   *
   * Enable 'file_test' to be able to work with dummy_remote:// stream wrapper.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'imagemagick',
    'file_mdm',
    'file_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Provides a list of available modules.
   */
  protected ModuleExtensionList $moduleList;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->moduleList = \Drupal::service('extension.list.module');

    // Create an admin user.
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test removal of temporary files created during operations on remote files.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testTemporaryRemoteCopiesDeletion(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $this->prepareImageFileHandling();

    // Get metadata from a remote file.
    $image = $this->imageFactory->get('dummy-remote://image-test.png');
    /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
    $toolkit = $image->getToolkit();
    $toolkit->getExifOrientation();
    $this->assertCount(1, $this->fileSystem->scanDirectory('temporary://', '/ima.*/'), 'A temporary file has been created for getting metadata from a remote file.');

    // Simulate Drupal shutdown.
    $callbacks = drupal_register_shutdown_function();
    foreach ($callbacks as $callback) {
      if ($callback['callback'] === [
        ImagemagickEventSubscriber::class,
        'removeTemporaryRemoteCopy',
      ]) {
        call_user_func_array($callback['callback'], $callback['arguments']);
      }
    }

    // Ensure we have no leftovers in the temporary directory.
    $this->assertCount(0, $this->fileSystem->scanDirectory('temporary://', '/ima.*/'), 'No files left in the temporary directory after the Drupal shutdown.');
  }

  /**
   * Tests that double cropping returns an image of expected dimensions.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testDoubleCropping(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $this->prepareImageFileHandling();

    // Prepare a test image.
    $image = $this->imageFactory->get('public://image-test.png');
    $image->resize(5833, 3889);
    $image->save('public://3204498-test.png');

    // Process the image.
    $test_image = $this->imageFactory->get('public://3204498-test.png');
    $this->assertSame(5833, $test_image->getWidth());
    $this->assertSame(3889, $test_image->getHeight());
    $test_image->crop(0, 1601, 5826, 1456);
    $test_image->resize(1601, 400);
    $test_image->crop(100, 0, 1400, 400);
    $test_image->save('public://3204498-test-derived.png');

    // Check the resulting image.
    $derived_test_image = $this->imageFactory->get('public://3204498-test-derived.png');
    $this->assertSame(1400, $derived_test_image->getWidth());
    $this->assertSame(400, $derived_test_image->getHeight());
  }

  /**
   * Test image toolkit operations.
   *
   * Since PHP can't visually check that our images have been manipulated
   * properly, build a list of expected color values for each of the corners and
   * the expected height and widths for the final images.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testManipulations(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $this->prepareImageFileHandling();

    // Typically the corner colors will be unchanged. These colors are in the
    // order of top-left, top-right, bottom-right, bottom-left.
    $default_corners = [
      $this->red,
      $this->green,
      $this->blue,
      $this->transparent,
    ];

    // A list of files that will be tested.
    $files = [
      'image-test.png',
      'image-test.gif',
      'image-test-no-transparency.gif',
      'image-test.jpg',
      'img-test.webp',
    ];

    // Setup a list of tests to perform on each type.
    $operations = [
      'resize' => [
        'function' => 'resize',
        'arguments' => ['width' => 20, 'height' => 10],
        'width' => 20,
        'height' => 10,
        'corners' => $default_corners,
        'tolerance' => 0,
      ],
      'scale_x' => [
        'function' => 'scale',
        'arguments' => ['width' => 20],
        'width' => 20,
        'height' => 10,
        'corners' => $default_corners,
        'tolerance' => 0,
      ],
      'scale_y' => [
        'function' => 'scale',
        'arguments' => ['height' => 10],
        'width' => 20,
        'height' => 10,
        'corners' => $default_corners,
        'tolerance' => 0,
      ],
      'upscale_x' => [
        'function' => 'scale',
        'arguments' => ['width' => 80, 'upscale' => TRUE],
        'width' => 80,
        'height' => 40,
        'corners' => $default_corners,
        'tolerance' => 0,
      ],
      'upscale_y' => [
        'function' => 'scale',
        'arguments' => ['height' => 40, 'upscale' => TRUE],
        'width' => 80,
        'height' => 40,
        'corners' => $default_corners,
        'tolerance' => 0,
      ],
      'crop' => [
        'function' => 'crop',
        'arguments' => ['x' => 12, 'y' => 4, 'width' => 16, 'height' => 12],
        'width' => 16,
        'height' => 12,
        'corners' => array_fill(0, 4, $this->white),
        'tolerance' => 0,
      ],
      'scale_and_crop' => [
        'function' => 'scale_and_crop',
        'arguments' => ['width' => 10, 'height' => 8],
        'width' => 10,
        'height' => 8,
        'corners' => array_fill(0, 4, $this->black),
        'tolerance' => 100,
      ],
      'convert_jpg' => [
        'function' => 'convert',
        'width' => 40,
        'height' => 20,
        'arguments' => ['extension' => 'jpeg'],
        'mimetype' => 'image/jpeg',
        'corners' => $default_corners,
        'tolerance' => 0,
      ],
      'convert_gif' => [
        'function' => 'convert',
        'width' => 40,
        'height' => 20,
        'arguments' => ['extension' => 'gif'],
        'mimetype' => 'image/gif',
        'corners' => $default_corners,
        'tolerance' => 15,
      ],
      'convert_png' => [
        'function' => 'convert',
        'width' => 40,
        'height' => 20,
        'arguments' => ['extension' => 'png'],
        'mimetype' => 'image/png',
        'corners' => $default_corners,
        'tolerance' => 5,
      ],
      'convert_webp' => [
        'function' => 'convert',
        'width' => 40,
        'height' => 20,
        'arguments' => ['extension' => 'webp'],
        'mimetype' => 'image/webp',
        'corners' => $default_corners,
        'tolerance' => 27,
      ],
      'desaturate' => [
        'function' => 'desaturate',
        'arguments' => [],
        'height' => 20,
        'width' => 40,
        // Grayscale corners are a bit funky. Each of the corners are a shade of
        // gray. The values of these were determined simply by looking at the
        // final image to see what desaturated colors end up being.
        'corners' => [
          array_fill(0, 3, 76) + [3 => 0],
          array_fill(0, 3, 149) + [3 => 0],
          array_fill(0, 3, 29) + [3 => 0],
          array_fill(0, 3, 225) + [3 => 127],
        ],
        // @todo tolerance here is too high. Check reasons.
        'tolerance' => 17000,
      ],
    ];

    foreach ($files as $file) {
      $image_uri = 'public://' . $file;
      foreach ($operations as $op => $values) {
        // Load up a fresh image.
        $image = $this->imageFactory->get($image_uri);
        /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
        $toolkit = $image->getToolkit();
        if (!$image->isValid()) {
          // WEBP may be not supported by the binaries.
          if ($file === 'img-test.webp') {
            continue 2;
          }
          $this->fail("Could not load image $file.");
        }

        // Check that no multi-frame information is set.
        $this->assertSame(1, $toolkit->getFrames());

        // Perform our operation.
        $image->apply($values['function'], $values['arguments']);

        // Save and reload image.
        $file_path = $this->testDirectory . '/' . $op . substr($file, -4);
        $save_result = $image->save($file_path);
        // WEBP may be not supported by the binaries.
        if (!$save_result && $op === 'convert_webp') {
          continue 2;
        }
        $this->assertTrue($save_result);
        $image = $this->imageFactory->get($file_path);
        $this->assertTrue($image->isValid());

        // @todo Suite specifics, temporarily adjust tests.
        $package = $toolkit->getExecManager()->getPackageSuite();
        if ($package === PackageSuite::Graphicsmagick) {
          // @todo Issues with crop and convert on GIF files, investigate.
          if (in_array($file, [
            'image-test.gif', 'image-test-no-transparency.gif',
          ]) && in_array($op, [
            'crop', 'scale_and_crop', 'convert_png',
          ])) {
            continue;
          }
        }
        if ($package === PackageSuite::Imagemagick) {
          // @todo Issues with crop and convert on GIF files, investigate.
          if (in_array($file, [
            'image-test.gif', 'image-test-no-transparency.gif',
          ]) && in_array($op, [
            'crop', 'scale_and_crop',
          ])) {
            continue;
          }
        }

        // Reload with GD to be able to check results at pixel level.
        $image = $this->imageFactory->get($file_path, 'gd');
        /** @var \Drupal\system\Plugin\ImageToolkit\GDToolkit $toolkit */
        $toolkit = $image->getToolkit();
        $toolkit->getImage();
        $this->assertTrue($image->isValid());

        // Check MIME type if needed.
        if (isset($values['mimetype'])) {
          $this->assertEquals($values['mimetype'], $toolkit->getMimeType(), "Image '$file' after '$op' action has proper MIME type ({$values['mimetype']}).");
        }

        // To keep from flooding the test with assert values, make a general
        // value for whether each group of values fail.
        $correct_dimensions_real = TRUE;
        $correct_dimensions_object = TRUE;

        // Check the real dimensions of the image first.
        $actual_toolkit_width = imagesx($toolkit->getImage());
        $actual_toolkit_height = imagesy($toolkit->getImage());
        if ($actual_toolkit_height != $values['height'] || $actual_toolkit_width != $values['width']) {
          $correct_dimensions_real = FALSE;
        }

        // Check that the image object has an accurate record of the dimensions.
        $actual_image_width = $image->getWidth();
        $actual_image_height = $image->getHeight();
        if ($actual_image_width != $values['width'] || $actual_image_height != $values['height']) {
          $correct_dimensions_object = FALSE;
        }

        $this->assertTrue($correct_dimensions_real, "Image '$file' after '$op' action has proper dimensions. Expected {$values['width']}x{$values['height']}, actual {$actual_toolkit_width}x{$actual_toolkit_height}.");
        $this->assertTrue($correct_dimensions_object, "Image '$file' object after '$op' action is reporting the proper height and width values.  Expected {$values['width']}x{$values['height']}, actual {$actual_image_width}x{$actual_image_height}.");

        // GraphicsMagick on WEBP requires higher tolerance.
        if ($file === 'img-test.webp' && $package === PackageSuite::Graphicsmagick) {
          $values['tolerance'] += 4800;
        }

        // JPEG colors will always be messed up due to compression.
        if ($toolkit->getType() != IMAGETYPE_JPEG) {
          // Now check each of the corners to ensure color correctness.
          foreach ($values['corners'] as $key => $corner) {
            // The test gif that does not have transparency has yellow where the
            // others have transparent.
            if ($file === 'image-test-no-transparency.gif' && $corner === $this->transparent) {
              $corner = $this->yellow;
            }
            // The test jpg when converted to other formats has yellow where the
            // others have transparent.
            if ($file === 'image-test.jpg' && $corner === $this->transparent && in_array($op, [
              'convert_gif',
              'convert_png',
              'convert_webp',
            ])) {
              $corner = $this->yellow;
            }
            // Get the location of the corner.
            switch ($key) {
              case 0:
                $x = 0;
                $y = 0;
                break;

              case 1:
                $x = $image->getWidth() - 1;
                $y = 0;
                break;

              case 2:
                $x = $image->getWidth() - 1;
                $y = $image->getHeight() - 1;
                break;

              case 3:
                $x = 0;
                $y = $image->getHeight() - 1;
                break;

            }
            $this->assertColorsAreClose($corner, $this->getPixelColor($image, $x, $y), $values['tolerance'], $file, $op);
          }
        }
      }
    }

    // Test loading non-existing image files.
    $this->assertFalse($this->imageFactory->get('/generic.png')->isValid());
    $this->assertFalse($this->imageFactory->get('public://generic.png')->isValid());

    // Test creation of image from scratch, and saving to storage.
    foreach ([IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_JPEG] as $type) {
      $image = $this->imageFactory->get();
      $image->createNew(50, 20, image_type_to_extension($type, FALSE), '#ffff00');
      $file = 'from_null' . image_type_to_extension($type);
      $file_path = $this->testDirectory . '/' . $file;
      $this->assertEquals(50, $image->getWidth(), "Image file '$file' has the correct width.");
      $this->assertEquals(20, $image->getHeight(), "Image file '$file' has the correct height.");
      $this->assertEquals(image_type_to_mime_type($type), $image->getMimeType(), "Image file '$file' has the correct MIME type.");
      $this->assertTrue($image->save($file_path), "Image '$file' created anew from a null image was saved.");

      // Reload saved image.
      $image_reloaded = $this->imageFactory->get($file_path, 'gd');
      /** @var \Drupal\system\Plugin\ImageToolkit\GDToolkit $gd_toolkit */
      $gd_toolkit = $image_reloaded->getToolkit();
      if (!$image_reloaded->isValid()) {
        $this->fail("Could not load image '$file'.");
      }
      $this->assertEquals(50, $image_reloaded->getWidth(), "Image file '$file' has the correct width.");
      $this->assertEquals(20, $image_reloaded->getHeight(), "Image file '$file' has the correct height.");
      $this->assertEquals(image_type_to_mime_type($type), $image_reloaded->getMimeType(), "Image file '$file' has the correct MIME type.");
      if ($gd_toolkit->getType() == IMAGETYPE_GIF) {
        $this->assertEquals('#ffff00', $gd_toolkit->getTransparentColor(), "Image file '$file' has the correct transparent color channel set.");
      }
      else {
        $this->assertEquals(NULL, $gd_toolkit->getTransparentColor(), "Image file '$file' has no color channel set.");
      }
    }

    // Test handling a file stored through a remote stream wrapper.
    $image = $this->imageFactory->get('dummy-remote://image-test.png');
    /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
    $toolkit = $image->getToolkit();
    // Source file should be equal to the copied local temp source file.
    $this->assertEquals(filesize('dummy-remote://image-test.png'), filesize($toolkit->arguments()->getSourceLocalPath()));
    $image->desaturate();
    $this->assertTrue($image->save('dummy-remote://remote-image-test.png'));
    // Destination file should exists, and destination local temp file should
    // have been reset.
    $this->assertTrue(file_exists($toolkit->arguments()->getDestination()));
    $this->assertEquals('dummy-remote://remote-image-test.png', $toolkit->arguments()->getDestination());
    $this->assertSame('', $toolkit->arguments()->getDestinationLocalPath());

    // Test retrieval of EXIF information.
    $this->fileSystem->copy($this->moduleList->getPath('imagemagick') . '/misc/test-exif.jpeg', 'public://', FileExists::Replace);
    // The image files that will be tested.
    $image_files = [
      [
        'path' => $this->moduleList->getPath('imagemagick') . '/misc/test-exif.jpeg',
        'orientation' => 8,
      ],
      [
        'path' => 'public://test-exif.jpeg',
        'orientation' => 8,
      ],
      [
        'path' => 'dummy-remote://test-exif.jpeg',
        'orientation' => 8,
      ],
      [
        'path' => 'public://image-test.jpg',
        'orientation' => NULL,
      ],
      [
        'path' => 'public://image-test.png',
        'orientation' => NULL,
      ],
      [
        'path' => 'public://image-test.gif',
        'orientation' => NULL,
      ],
      [
        'path' => NULL,
        'orientation' => NULL,
      ],
    ];
    foreach ($image_files as $image_file) {
      $image = $this->imageFactory->get($image_file['path']);
      /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
      $toolkit = $image->getToolkit();
      $this->assertSame($image_file['orientation'], $toolkit->getExifOrientation());
    }

    // Test multi-frame GIF image.
    $image_files = [
      [
        'source' => $this->moduleList->getPath('imagemagick') . '/misc/test-multi-frame.gif',
        'destination' => $this->testDirectory . '/test-multi-frame.gif',
        'width' => 60,
        'height' => 29,
        'frames' => 13,
        'scaled_width' => 30,
        'scaled_height' => 15,
      ],
    ];
    foreach ($image_files as $image_file) {
      $image = $this->imageFactory->get($image_file['source']);
      /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
      $toolkit = $image->getToolkit();
      $this->assertSame($image_file['width'], $image->getWidth());
      $this->assertSame($image_file['height'], $image->getHeight());
      $this->assertSame($image_file['frames'], $toolkit->getFrames());

      // Scaling should preserve frames.
      $image->scale(30);
      $this->assertTrue($image->save($image_file['destination']));
      $image = $this->imageFactory->get($image_file['destination']);
      $this->assertSame($image_file['scaled_width'], $image->getWidth());
      $this->assertSame($image_file['scaled_height'], $image->getHeight());
      $this->assertSame($image_file['frames'], $toolkit->getFrames());

      // Converting to PNG should drop frames.
      $image->convert('png');
      $this->assertTrue($image->save($image_file['destination']));
      $image = $this->imageFactory->get($image_file['destination']);
      /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
      $toolkit = $image->getToolkit();
      $this->assertSame(1, $toolkit->getFrames());
      $this->assertSame($image_file['scaled_width'], $image->getWidth());
      $this->assertSame($image_file['scaled_height'], $image->getHeight());
      $this->assertSame(1, $toolkit->getFrames());
    }
  }

  /**
   * Test saving image files with filenames having non-ascii characters.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testNonAsciiFileNames(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    // @todo on Windows, GraphicsMagick fails.
    if (substr(PHP_OS, 0, 3) === 'WIN' && $toolkit_settings['binaries'] === 'graphicsmagick') {
      $this->markTestSkipped('On Windows, GraphicsMagick fails.');
    }

    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $this->prepareImageFileHandling();

    $file_names = [
      'ascii_test.png',
      'εικόναδοκιμής.png',
      'greek εικόνα δοκιμής.png',
      'russian Тестовое изображение.png',
      'simplified chinese 测试图片.png',
      'japanese 試験画像.png',
      'arabic صورة الاختبار.png',
      'armenian փորձարկման պատկերը.png',
      'bengali পরীক্ষা ইমেজ.png',
      'hebraic תמונת בדיקה.png',
      'hindi परीक्षण छवि.png',
      'viet hình ảnh thử nghiệm.png',
      'viet \'with quotes\' hình ảnh thử nghiệm.png',
      'viet "with double quotes" hình ảnh thử nghiệm.png',
    ];
    foreach ($file_names as $file) {
      // On Windows, skip filenames with non-allowed characters.
      if (substr(PHP_OS, 0, 3) === 'WIN' && preg_match('/[:*?"<>|]/', $file)) {
        continue;
      }
      $image = $this->imageFactory->get();
      $this->assertTrue($image->createNew(50, 20, 'png'));
      $file_path = $this->testDirectory . '/' . $file;
      $this->assertTrue($image->save($file_path), $file);
      $image_reloaded = $this->imageFactory->get($file_path, 'gd');
      $this->assertTrue($image_reloaded->isValid(), "Image file '$file' loaded successfully.");
    }
  }

  /**
   * Tests parsing an invalid image.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testInvalidImage(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);

    // Load up an invalid image.
    $image = $this->imageFactory->get(\Drupal::root() . '/core/tests/fixtures/files/README.txt');

    /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
    $toolkit = $image->getToolkit();

    $this->assertFalse($toolkit->isValid());
    $this->assertNull($toolkit->getWidth());
    $this->assertNull($toolkit->getHeight());
    $this->assertSame([], $toolkit->getProfiles());
    $this->assertNull($toolkit->getFrames());
    $this->assertNull($toolkit->getExifOrientation());
    $this->assertNull($toolkit->getColorspace());
    $this->assertSame('', $toolkit->getMimeType());
  }

  /**
   * Function for finding a pixel's RGBa values.
   */
  protected function getPixelColor(ImageInterface $image, int $x, int $y): array {
    /** @var \Drupal\system\Plugin\ImageToolkit\GDToolkit $toolkit */
    $toolkit = $image->getToolkit();
    $color_index = imagecolorat($toolkit->getImage(), $x, $y);

    $transparent_index = imagecolortransparent($toolkit->getImage());
    if ($color_index == $transparent_index) {
      return [0, 0, 0, 127];
    }

    return array_values(imagecolorsforindex($toolkit->getImage(), $color_index));
  }

  /**
   * Asserts equality of two colors by RGBa, within a tolerance.
   *
   * Very basic, just compares the sum of the squared differences for each of
   * the R, G, B, A components of two colors against a 'tolerance' value.
   *
   * @param int[] $expected
   *   The expected RGBA array.
   * @param int[] $actual
   *   The actual RGBA array.
   * @param int $tolerance
   *   The acceptable difference between the colors.
   * @param string $file
   *   The image file being tested.
   * @param string $op
   *   The image operation being tested.
   */
  protected function assertColorsAreClose(array $expected, array $actual, int $tolerance, string $file, string $op): void {
    // Fully transparent colors are equal, regardless of RGB.
    if ($actual[3] == 127 && $expected[3] == 127) {
      return;
    }
    $distance = pow(($actual[0] - $expected[0]), 2) + pow(($actual[1] - $expected[1]), 2) + pow(($actual[2] - $expected[2]), 2) + pow(($actual[3] - $expected[3]), 2);
    $this->assertLessThanOrEqual($tolerance, $distance, "Actual: {" . implode(',', $actual) . "}, Expected: {" . implode(',', $expected) . "}, Distance: " . $distance . ", Tolerance: " . $tolerance . ", File: " . $file . ", Operation: " . $op);
  }

}
