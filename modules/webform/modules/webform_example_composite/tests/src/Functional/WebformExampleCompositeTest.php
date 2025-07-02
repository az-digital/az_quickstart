<?php

namespace Drupal\Tests\webform_example_composite\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform example composite.
 *
 * @group webform_example_composite
 */
class WebformExampleCompositeTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_example_composite'];

  /**
   * Tests webform example element.
   */
  public function testWebformExampleComposite() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('webform_example_composite');

    // Check form element rendering.
    $this->drupalGet('/webform/webform_example_composite');
    // NOTE:
    // This is a very lazy but easy way to check that the element is rendering
    // as expected.
    $assert_session->responseContains('<label for="edit-webform-example-composite-first-name">First name</label>');
    $assert_session->fieldExists('edit-webform-example-composite-first-name');
    $assert_session->responseContains('<label for="edit-webform-example-composite-last-name">Last name</label>');
    $assert_session->fieldExists('edit-webform-example-composite-last-name');
    $assert_session->responseContains('<label for="edit-webform-example-composite-date-of-birth">Date of birth</label>');
    $assert_session->fieldExists('edit-webform-example-composite-date-of-birth');
    $assert_session->responseContains('<label for="edit-webform-example-composite-sex">Sex</label>');
    $assert_session->fieldExists('edit-webform-example-composite-sex');

    // Check webform element submission.
    $edit = [
      'webform_example_composite[first_name]' => 'John',
      'webform_example_composite[last_name]' => 'Smith',
      'webform_example_composite[sex]' => 'Male',
      'webform_example_composite[date_of_birth]' => '1910-01-01',
      'webform_example_composite_multiple[items][0][first_name]' => 'Jane',
      'webform_example_composite_multiple[items][0][last_name]' => 'Doe',
      'webform_example_composite_multiple[items][0][sex]' => 'Female',
      'webform_example_composite_multiple[items][0][date_of_birth]' => '1920-12-01',
    ];
    $sid = $this->postSubmission($webform, $edit);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals($webform_submission->getElementData('webform_example_composite'), [
      'first_name' => 'John',
      'last_name' => 'Smith',
      'sex' => 'Male',
      'date_of_birth' => '1910-01-01',
    ]);
    $this->assertEquals($webform_submission->getElementData('webform_example_composite_multiple'), [
      [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'sex' => 'Female',
        'date_of_birth' => '1920-12-01',
      ],
    ]);
  }

}
