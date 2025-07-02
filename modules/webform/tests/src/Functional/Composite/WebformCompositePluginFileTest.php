<?php

namespace Drupal\Tests\webform\Functional\Composite;

use Drupal\file\Entity\File;
use Drupal\Tests\webform\Functional\Element\WebformElementManagedFileTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for composite plugin file upload.
 *
 * @group webform
 */
class WebformCompositePluginFileTest extends WebformElementManagedFileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_test_element'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_comp_file_plugin'];

  /**
   * Test composite plugin.
   */
  public function testPlugin() {
    $webform = Webform::load('test_element_comp_file_plugin');

    $first_file = $this->files[0];
    $second_file = $this->files[1];

    /* ********************************************************************** */
    // Single composite with file upload.
    /* ********************************************************************** */

    // Create submission with file.
    $edit = [
      'webform_test_composite_file[textfield]' => '{textfield}',
      'files[webform_test_composite_file_managed_file]' => \Drupal::service('file_system')->realpath($first_file->uri),
    ];
    $sid = $this->postSubmission($webform, $edit);
    $webform_submission = WebformSubmission::load($sid);

    $fid = $this->getLastFileId();
    $file = File::load($fid);

    // Check file permanent.
    $this->assertTrue($file->isPermanent());

    // Check file upload.
    $element_data = $webform_submission->getElementData('webform_test_composite_file');
    $this->assertEquals($element_data['managed_file'], $fid, 'Test file was upload to the current submission');

    // Check test file file usage.
    $this->assertSame(['webform' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($file), 'The file has 1 usage.');

    // Check test file uploaded file path.
    $this->assertEquals($file->getFileUri(), 'private://webform/test_element_comp_file_plugin/' . $sid . '/' . $first_file->filename);

    // Check that test file exists.
    $this->assertFileExists($file->getFileUri());

    /* ********************************************************************** */
    // Multiple composite with file upload.
    /* ********************************************************************** */

    // Create submission with file.
    $edit = [
      'webform_test_composite_file_multiple_header[items][0][textfield]' => '{textfield}',
      'files[webform_test_composite_file_multiple_header_items_0_managed_file]' => \Drupal::service('file_system')->realpath($second_file->uri),
    ];
    $sid = $this->postSubmission($webform, $edit);
    $webform_submission = WebformSubmission::load($sid);

    $fid = $this->getLastFileId();
    $file = File::load($fid);

    // Check file permanent.
    $this->assertTrue($file->isPermanent());

    // Check file upload.
    $element_data = $webform_submission->getElementData('webform_test_composite_file_multiple_header');
    $this->assertEquals($element_data[0]['managed_file'], $fid, 'Test file was upload to the current submission');

    // Check test file file usage.
    $this->assertSame(['webform' => ['webform_submission' => [$sid => '1']]], $this->fileUsage->listUsage($file), 'The file has 1 usage.');

    // Check test file uploaded file path.
    $this->assertEquals($file->getFileUri(), 'private://webform/test_element_comp_file_plugin/' . $sid . '/' . $second_file->filename);

    // Check that test file exists.
    $this->assertFileExists($file->getFileUri());
  }

}
