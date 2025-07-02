<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Tests\TestFileCreationTrait;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform element prepopulate.
 *
 * @group webform
 */
class WebformElementPrepopulateTest extends WebformElementBrowserTestBase {

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
  protected static $testWebforms = ['test_element_prepopulate'];

  /**
   * Test element prepopulate.
   */
  public function testElementPrepopulate() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_prepopulate');

    $files = $this->getTestFiles('text');

    // Check default value of elements on multiple_values.
    $this->drupalGet('/webform/test_element_prepopulate');
    $assert_session->fieldValueEquals('textfield_01', '');
    $assert_session->fieldValueEquals('textfield_prepopulate_01', '{default_value_01}');
    $field = $assert_session->fieldExists('files[managed_file_prepopulate_01]');
    $this->assertEmpty($field->getAttribute('value'));

    $this->drupalGet('/webform/test_element_prepopulate');
    $this->submitForm([], 'Next >');
    $assert_session->fieldValueEquals('textfield_02', '');
    $assert_session->fieldValueEquals('textfield_prepopulate_02', '{default_value_02}');

    // Check 'textfield' can not be prepopulated.
    $this->drupalGet('/webform/test_element_prepopulate', ['query' => ['textfield_01' => 'value']]);
    $assert_session->fieldValueNotEquals('textfield_01', 'value');

    // Check prepopulating textfield on multiple pages.
    $options = [
      'query' => [
        'textfield_prepopulate_01' => 'value_01',
        'textfield_prepopulate_02' => 'value_02',
      ],
    ];
    $this->drupalGet('/webform/test_element_prepopulate', $options);
    $assert_session->fieldValueEquals('textfield_prepopulate_01', 'value_01');
    $this->drupalGet('/webform/test_element_prepopulate', $options);
    $this->submitForm([], 'Next >');
    $assert_session->fieldValueEquals('textfield_prepopulate_02', 'value_02');

    // Check prepopulating textfield on multiple pages and changing the value.
    $options = [
      'query' => [
        'textfield_prepopulate_01' => 'value_01',
        'textfield_prepopulate_02' => 'value_02',
      ],
    ];
    $this->drupalGet('/webform/test_element_prepopulate', $options);
    $assert_session->fieldValueEquals('textfield_prepopulate_01', 'value_01');
    $this->drupalGet('/webform/test_element_prepopulate', $options);
    $edit = ['textfield_prepopulate_01' => 'edit_01'];
    $this->submitForm($edit, 'Next >');
    $assert_session->fieldValueEquals('textfield_prepopulate_02', 'value_02');
    $this->submitForm([], '< Previous');
    $assert_session->fieldValueNotEquals('textfield_prepopulate_01', 'value_01');
    $assert_session->fieldValueEquals('textfield_prepopulate_01', 'edit_01');

    // Check 'managed_file_prepopulate' can not be prepopulated.
    // The #prepopulate property is not available to managed file elements.
    // @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::defaultProperties
    $this->drupalGet('/webform/test_element_prepopulate');
    $edit = ['files[managed_file_prepopulate_01]' => \Drupal::service('file_system')->realpath($files[0]->uri)];
    $this->submitForm($edit, 'Next >');
    $this->submitForm([], 'Submit');
    $sid = $this->getLastSubmissionId($webform);
    $webform_submission = WebformSubmission::load($sid);
    $fid = $webform_submission->getElementData('managed_file_prepopulate_01');
    $this->drupalGet('/webform/test_element_prepopulate', ['query' => ['managed_file_prepopulate_01' => $fid]]);
    $field = $assert_session->fieldExists('files[managed_file_prepopulate_01]');
    $this->assertEmpty($field->getAttribute('value'));
  }

}
