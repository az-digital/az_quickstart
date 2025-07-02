<?php

declare(strict_types=1);

namespace Drupal\Tests\imagemagick\Functional;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\imagemagick\ImagemagickExecManagerInterface;
use Drupal\imagemagick\PackageSuite;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests ImageMagick subform and settings.
 *
 * @group imagemagick
 */
class ToolkitImagemagickFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'imagemagick', 'file_mdm'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The admin user.
   */
  protected AccountInterface $adminUser;

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
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test ImageMagick subform and settings.
   */
  public function testFormAndSettings(): void {
    $admin_path = 'admin/config/media/image-toolkit';

    // Change the toolkit.
    \Drupal::configFactory()->getEditable('system.image')
      ->set('toolkit', 'imagemagick')
      ->save();

    // Test form is accepting wrong binaries path while setting toolkit to GD.
    $this->drupalGet($admin_path);
    $this->assertSession()->fieldValueEquals('image_toolkit', 'imagemagick');
    $edit = [
      'image_toolkit' => 'gd',
      'imagemagick[suite][path_to_binaries]' => '/foo/bar/',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->fieldValueEquals('image_toolkit', 'gd');

    // Change the toolkit via form.
    $this->drupalGet($admin_path);
    $this->assertSession()->fieldValueEquals('image_toolkit', 'gd');
    $edit = [
      'image_toolkit' => 'imagemagick',
      'imagemagick[suite][path_to_binaries]' => '',
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->fieldValueEquals('image_toolkit', 'imagemagick');

    // Test Imagemagick 7 is recognized if available and selected.
    $status = \Drupal::service(ImagemagickExecManagerInterface::class)->checkPath('', PackageSuite::Imagemagick, 'v7');
    if (empty($status['errors'])) {
      $edit = [
        'imagemagick[suite][imagemagick_version]' => 'v7',
      ];
      $this->submitForm($edit, 'Save configuration');
      $this->assertSession()->elementTextContains('css', 'details[data-drupal-selector="edit-imagemagick-suite-version"]', 'ImageMagick 7.');
    }

    // Test default supported image extensions.
    $this->assertSession()->responseNotContains('Image format errors');
    $this->assertSession()->responseContains('GIF, JPEG, PNG');
    $this->assertSession()->responseContains('gif, jfif, jpe, jpeg, jpg, png');

    $config = \Drupal::configFactory()->getEditable('imagemagick.settings');

    // Enable TIFF.
    $image_formats = $config->get('image_formats');
    $image_formats['TIFF']['enabled'] = TRUE;
    $config->set('image_formats', $image_formats)->save();
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseNotContains('Image format errors');
    $this->assertSession()->responseContains('GIF, JPEG, PNG, TIFF');
    $this->assertSession()->responseContains('gif, jfif, jpe, jpeg, jpg, png, tif, tiff');

    // Enable BMP.
    $image_formats['BMP']['enabled'] = TRUE;
    $config->set('image_formats', $image_formats)->save();
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseNotContains('Image format errors');
    $this->assertSession()->responseContains('BMP, GIF, JPEG, PNG, TIFF');
    $this->assertSession()->responseContains('bmp, dib, gif, jfif, jpe, jpeg, jpg, png, tif, tiff');

    // Disable PNG.
    $image_formats['PNG']['enabled'] = FALSE;
    $config->set('image_formats', $image_formats)->save();
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseNotContains('Image format errors');
    $this->assertSession()->responseContains('BMP, GIF, JPEG, TIFF');
    $this->assertSession()->responseContains('bmp, dib, gif, jfif, jpe, jpeg, jpg, tif, tiff');

    // Disable some extensions.
    $image_formats['TIFF']['exclude_extensions'] = 'tif, gif';
    $config->set('image_formats', $image_formats)->save();
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseNotContains('Image format errors');
    $this->assertSession()->responseContains('BMP, GIF, JPEG, TIFF');
    $this->assertSession()->responseContains('bmp, dib, gif, jfif, jpe, jpeg, jpg, tiff');
    $image_formats['JPEG']['exclude_extensions'] = 'jpe, jpg';
    $config->set('image_formats', $image_formats)->save();
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseNotContains('Image format errors');
    $this->assertSession()->responseContains('BMP, GIF, JPEG, TIFF');
    $this->assertSession()->responseContains('bmp, dib, gif, jfif, jpeg, tiff');

    // Add a format with missing mimetype.
    $image_formats['BAX']['mime_type'] = 'foo/bar';
    $config->set('image_formats', $image_formats)->save();
    $this->drupalGet($admin_path);
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('Image format errors');
  }

  /**
   * Test status report.
   */
  public function testStatusReport(): void {
    $statusReportPath = 'admin/reports/status';

    // Change the toolkit.
    \Drupal::configFactory()->getEditable('system.image')
      ->set('toolkit', 'imagemagick')
      ->save();

    // Test status report.
    $this->drupalGet($statusReportPath);
    $this->assertSession()->statusCodeEquals(200);

    // There should be no warning about rotate effects.
    $this->assertSession()->responseNotContains('ImageMagick rotate');

    // Enable the 'image' module.
    $this->assertFalse(\Drupal::entityTypeManager()->hasDefinition('image_style'));
    \Drupal::service('module_installer')->install(['image']);
    $this->assertTrue(\Drupal::entityTypeManager()->hasDefinition('image_style'));
    $roles = $this->adminUser->getRoles(TRUE);
    Role::load(reset($roles))
      ->grantPermission('administer image styles')
      ->save();

    // Create a test image style with Rotate effect.
    $testImageStyle = ImageStyle::create([
      'name' => 'test_rotate',
      'label' => 'Test image style with Rotate effect',
    ]);
    $this->assertEquals(SAVED_NEW, $testImageStyle->save());
    $this->drupalGet('admin/config/media/image-styles/manage/test_rotate');
    $this->submitForm(['new' => 'image_rotate'], 'Add');
    $effectEdit = [];
    $effectEdit['data[degrees]'] = 25;
    $this->submitForm($effectEdit, 'Add effect');

    // Test status report again, should show the warning about existing rotate
    // effects.
    $this->drupalGet($statusReportPath);
    $this->assertSession()->statusCodeEquals(200);

    // There should be no warning about rotate effects.
    $this->assertSession()->responseContains('ImageMagick rotate');
  }

}
