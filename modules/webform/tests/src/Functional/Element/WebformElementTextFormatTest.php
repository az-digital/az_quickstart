<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for text format element.
 *
 * @group webform
 */
class WebformElementTextFormatTest extends WebformElementBrowserTestBase {

  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_text_format'];

  /**
   * The file usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileUsage = $this->container->get('file.usage');
  }

  /**
   * Test text format element.
   */
  public function testTextFormat() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_text_format');

    // Check that formats and tips are removed and/or hidden.
    $this->drupalGet('/webform/test_element_text_format');
    $assert_session->responseContains('<div class="js-filter-wrapper js-form-wrapper form-wrapper" data-drupal-selector="edit-text-format-format" style="display: none" data-webform-states-no-clear id="edit-text-format-format">');
    $assert_session->responseContains('<div data-drupal-selector="edit-text-format-format-help" style="display: none" id="edit-text-format-format-help" class="js-form-wrapper form-wrapper">');

    // Check description + more.
    $assert_session->responseContains('<div data-drupal-selector="edit-text-format-description-more" id="edit-text-format-description-more--description"><div class="webform-element-description">This is a description</div>');
    $assert_session->responseContains('<div id="edit-text-format-description-more--more" class="js-webform-element-more webform-element-more">');
    $assert_session->responseContains('<div class="webform-element-more--link"><a role="button" href="#edit-text-format-description-more--more--content">More</a></div>');
    $assert_session->responseContains('<div id="edit-text-format-description-more--more--content" class="webform-element-more--content">This is more</div>');

    // Check 'text_format' values.
    $this->drupalGet('/webform/test_element_text_format');
    $assert_session->fieldValueEquals('text_format[value]', 'The quick brown fox jumped over the lazy dog.');
    $assert_session->responseContains('No HTML tags allowed.');

    $text_format = [
      'value' => 'Custom value',
      'format' => 'custom_format',
    ];
    $form = $webform->getSubmissionForm(['data' => ['text_format' => $text_format]]);
    $this->assertEquals($form['elements']['text_format']['#default_value'], $text_format['value']);
    $this->assertEquals($form['elements']['text_format']['#format'], $text_format['format']);
  }

  /**
   * Tests webform text format element files.
   */
  public function testTextFormatFiles() {
    $this->createFilters();

    $webform = Webform::load('test_element_text_format');

    $this->drupalLogin($this->rootUser);

    // Create three test images.
    /** @var \Drupal\file\FileInterface[] $images */
    $images = $this->getTestFiles('image');
    $images = array_slice($images, 0, 5);
    foreach ($images as $index => $image_file) {
      $images[$index] = File::create((array) $image_file);
      $images[$index]->save();
    }
    // Check that all images are temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Upload the first image.
    $edit = [
      'text_format[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/>',
      'text_format[format]' => 'full_html',
    ];
    $sid = $this->postSubmission($webform, $edit);
    $this->reloadImages($images);

    // Check that first image is not temporary.
    $this->assertFalse($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check create first image file usage.
    $this->assertSame(['editor' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($images[0]), 'The file has 1 usage.');

    // Upload the second image.
    $this->drupalGet("/admin/structure/webform/manage/test_element_text_format/submission/$sid/edit");
    $edit = [
      'text_format[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[0]->uuid() . '"/><img data-entity-type="file" data-entity-uuid="' . $images[1]->uuid() . '"/>',
      'text_format[format]' => 'full_html',
    ];
    $this->submitForm($edit, 'Save');
    $this->reloadImages($images);

    // Check that first and second image are not temporary.
    $this->assertFalse($images[0]->isTemporary());
    $this->assertFalse($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check first and second image file usage.
    $this->assertSame(['editor' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($images[0]), 'The file has 1 usage.');
    $this->assertSame(['editor' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Remove the first image.
    $this->drupalGet("/admin/structure/webform/manage/test_element_text_format/submission/$sid/edit");
    $edit = [
      'text_format[value]' => '<img data-entity-type="file" data-entity-uuid="' . $images[1]->uuid() . '"/>',
      'text_format[format]' => 'full_html',
    ];
    $this->submitForm($edit, 'Save');
    $this->reloadImages($images);

    // Check that first is temporary and second image is not temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertFalse($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());

    // Check first and second image file usage.
    $this->assertSame([], $this->fileUsage->listUsage($images[0]), 'The file has 0 usage.');
    $this->assertSame(['editor' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Duplicate submission.
    $webform_submission = WebformSubmission::load($sid);
    $webform_submission_duplicate = $webform_submission->createDuplicate();
    $webform_submission_duplicate->save();

    // Check second image file usage.
    $this->assertSame(['editor' => ['webform_submission' => [$webform_submission->id() => '1', $webform_submission_duplicate->id() => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 2 usages.');

    // Delete the duplicate webform submission.
    $webform_submission_duplicate->delete();

    // Check second image file usage.
    $this->assertSame(['editor' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($images[1]), 'The file has 1 usage.');

    // Delete the webform submission.
    $this->drupalGet("/admin/structure/webform/manage/test_element_text_format/submission/$sid/delete");
    $this->submitForm([], 'Delete');
    $this->reloadImages($images);

    // Check that first and second image are temporary.
    $this->assertTrue($images[0]->isTemporary());
    $this->assertTrue($images[1]->isTemporary());
    $this->assertTrue($images[2]->isTemporary());
  }

  /* ************************************************************************ */
  // Helper functions.
  /* ************************************************************************ */

  /**
   * Reload images.
   *
   * @param array $images
   *   An array of image files.
   */
  protected function reloadImages(array &$images) {
    \Drupal::entityTypeManager()->getStorage('file')->resetCache();
    foreach ($images as $index => $image) {
      $images[$index] = File::load($image->id());
    }
  }

}
