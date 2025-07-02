<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform entity.
 *
 * @group webform
 */
class WebformEntityTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'webform', 'webform_test_submissions'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_submissions'];

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Storage.
    $this->submissionStorage = \Drupal::entityTypeManager()->getStorage('webform_submission');
  }

  /**
   * Tests webform entity.
   */
  public function testWebform() {
    /** @var \Drupal\webform\WebformInterface $webform_contact */
    $webform_contact = Webform::load('contact');
    $this->assertEquals($webform_contact->getElementsDefaultData(), [
      'name' => '[current-user:display-name]',
      'email' => '[current-user:mail]',
    ]);

    /** @var \Drupal\webform\WebformInterface $webform_test_submissions */
    $webform_test_submissions = Webform::load('test_submissions');

    // Check get elements.
    $elements = $webform_test_submissions->getElementsInitialized();
    $this->assertIsArray($elements);

    // Check getElements.
    $columns = $webform_test_submissions->getElementsInitializedFlattenedAndHasValue();
    $this->assertEquals(array_keys($columns), ['first_name', 'last_name', 'sex', 'dob', 'node', 'colors', 'likert', 'address']);

    // Set invalid elements.
    $webform_test_submissions->set('elements', "not\nvalid\nyaml")->save();

    // Check invalid elements.
    $this->assertEquals($webform_test_submissions->getElementsInitialized(), []);

    // Check invalid element columns.
    $this->assertEquals($webform_test_submissions->getElementsInitializedFlattenedAndHasValue(), []);

    // Check for 3 submissions.
    $this->assertEquals($this->submissionStorage->getTotal($webform_test_submissions), 4);

    // Check delete.
    $webform_test_submissions->delete();

    // Check all 3 submissions deleted.
    $this->assertEquals($this->submissionStorage->getTotal($webform_test_submissions), 0);

    // Check that 'test' state was deleted with the webform.
    $this->assertEquals(\Drupal::state()->get('webform.webform.' . $webform_test_submissions->id()), NULL);
  }

}
