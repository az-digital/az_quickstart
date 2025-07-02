<?php

namespace Drupal\Tests\image_widget_crop\FunctionalJavascript;

use Drupal\crop\Entity\CropType;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Minimal test case for the image_widget_crop module.
 *
 * @group image_widget_crop
 *
 * @ingroup media
 */
class ImageWidgetCropTest extends WebDriverTestBase {

  use ImageFieldCreationTrait {
    createImageField as traitCreateImageField;
  }
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'image_widget_crop',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['name' => 'Crop test', 'type' => 'crop_test']);

    $user = $this->createUser([
      'access content overview',
      'administer content types',
      'edit any crop_test content',
      'create crop_test content',
      'administer site configuration',
    ]);
    $this->drupalLogin($user);

    // Visit the status report to confirm that the Cropper library is available.
    $this->drupalGet('/admin/reports/status');
    $this->assertSession()->pageTextContains('ImageWidgetCrop libraries files are correctly configured');

    // Create a crop type so that the cropping widget will actually appear.
    CropType::create([
      'label' => 'Widescreen',
      'id' => 'crop_16_9',
      'aspect_ratio' => '16:9',
    ])->save();

    $this->createImageField('field_image_crop_test', 'crop_test', [], [], [
      'crop_list' => [
        'crop_16_9' => 'crop_16_9',
      ],
      'crop_types_required' => [],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function createImageField($name, $type_name, $storage_settings = [], $field_settings = [], $widget_settings = [], $formatter_settings = [], $description = '') {
    if (version_compare(\Drupal::VERSION, '10.3', '>=')) {
      $this->traitCreateImageField($name, 'node', $type_name, $storage_settings, $field_settings, $widget_settings, $formatter_settings, $description);
    }
    else {
      $this->traitCreateImageField($name, $type_name, $storage_settings, $field_settings, $widget_settings, $formatter_settings, $description);
    }

    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', $type_name)
      ->setComponent($name, [
        'type' => 'image_widget_crop',
        'settings' => $widget_settings,
      ])
      ->save();
  }

  /**
   * Tests that when a crop has more than one usage we have a warning.
   */
  public function testCropUi() {
    $images = $this->getTestFiles('image');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('node/add/crop_test');

    $page->fillField('Title', $this->randomString());
    $page->attachFileToField('files[field_image_crop_test_0]', $this->container->get('file_system')->realpath(reset($images)->uri));
    $this->assertNotEmpty($assert_session->waitForField('Alternative text'));
    $page->fillField('Alternative text', $this->randomString());
    $page->pressButton('Save');

    $files = File::loadMultiple();
    $this->assertCount(1, $files);

    $node = $this->drupalCreateNode([
      'title' => '2nd node using it',
      'type' => 'crop_test',
      'field_image_crop_test' => key($files),
      'alt' => $this->randomMachineName(),
    ]);

    /** @var \Drupal\file\FileUsage\FileUsageInterface $usage */
    $usage = \Drupal::service('file.usage');
    $usage->add(reset($files), 'image_widget_crop', 'node', $node->id());

    $this->drupalGet('node/1/edit');
    $this->assertSession()->responseContains('This crop definition affects more usages of this image');
  }

  /**
   * Test Image Widget Crop.
   */
  public function testImageWidgetCrop() {
    $images = $this->getTestFiles('image');
    $image_path = $this->container->get('file_system')
      ->realpath(reset($images)->uri);

    $this->drupalGet('node/add/crop_test');

    // Assert that there is no crop widget, neither 'Alternative text' text
    // filed nor 'Remove' button yet.
    $assert_session = $this->assertSession();
    $assert_session->elementNotExists('css', 'summary:contains(Crop image)');
    $assert_session->fieldNotExists('Alternative text');
    $assert_session->buttonNotExists('Remove');

    // Upload an image in field_image_crop_test_0.
    $page = $this->getSession()->getPage();
    $page->attachFileToField('files[field_image_crop_test_0]', $image_path);

    // Assert that now crop widget and 'Alternative text' text field appear and
    // that 'Remove' button exists.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'summary:contains(Crop image)'));
    $assert_session->fieldExists('Alternative text');
    $assert_session->buttonExists('Remove');

    // Set title and 'Alternative text' text field and save.
    $title = $this->randomMachineName();
    $page->fillField('Title', $title);
    $page->fillField('Alternative text', $this->randomString());
    $page->pressButton('Save');

    $assert_session->pageTextContains('Crop test ' . $title . ' has been created.');
    $url = $this->getUrl();
    $nid = substr($url, -1, strrpos($url, '/'));

    // Edit crop image.
    $this->drupalGet('node/' . $nid . '/edit');

    // Verify that the 'Remove' button works properly.
    $assert_session->fieldExists('Alternative text');
    $page->pressButton('Remove');
    $this->assertTrue($assert_session->waitForElementRemoved('named', ['field', 'Alternative text']));

    $page->attachFileToField('files[field_image_crop_test_0]', $image_path);
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'summary:contains(Crop image)'));
    // The form cannot be submitted without alt text filled in.
    $page->fillField('Alternative text', $this->randomString());

    // Verify that the 'Preview' button works properly.
    $page->pressButton('Preview');
    $page->clickLink('Back to content editing');

    // Verify that there is an image style preview.
    $assert_session->elementExists('css', 'input[name^="field_image_crop_test"][name$="[width]"]');
    $assert_session->elementExists('css', 'input[name^="field_image_crop_test"][name$="[height]"]');
  }

}
