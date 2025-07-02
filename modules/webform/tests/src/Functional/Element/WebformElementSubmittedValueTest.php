<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission value.
 *
 * @group webform
 */
class WebformElementSubmittedValueTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_submitted_value'];

  /**
   * Tests submitted value.
   */
  public function testSubmittedValue() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Create a submission.
    $webform = Webform::load('test_element_submitted_value');
    $sid = $this->postSubmission($webform);

    // Check the option 'three' is selected.
    $this->drupalGet("/webform/test_element_submission_value/submissions/$sid/edit");
    $this->assertEquals($assert_session->optionExists('edit-select', 'three')->getText(), 'Three');
    $this->assertTrue($assert_session->optionExists('edit-select', 'three')->hasAttribute('selected'));
    $this->assertTrue($assert_session->optionExists('edit-select-multiple', 'three')->hasAttribute('selected'));
    $assert_session->checkboxChecked('edit-checkboxes-three');

    // Remove option 'three' from all elements.
    $elements = $webform->getElementsDecoded();
    foreach ($elements as &$element) {
      unset($element['#options']['three']);
    }
    $webform->setElements($elements);
    $webform->save();

    // Check the option 'three' is still available and selected but
    // the label is now just the value.
    $this->drupalGet("/webform/test_element_submission_value/submissions/$sid/edit");
    $this->assertNotEquals($assert_session->optionExists('edit-select', 'three')->getText(), 'Three');
    $this->assertEquals($assert_session->optionExists('edit-select', 'three')->getText(), 'three');
    $this->assertTrue($assert_session->optionExists('edit-select', 'three')->hasAttribute('selected'));
    $this->assertTrue($assert_session->optionExists('edit-select-multiple', 'three')->hasAttribute('selected'));
    $assert_session->checkboxChecked('edit-checkboxes-three');
  }

}
