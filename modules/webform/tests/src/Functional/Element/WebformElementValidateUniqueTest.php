<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform validate unique.
 *
 * @group webform
 */
class WebformElementValidateUniqueTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_validate_unique'];

  /**
   * Tests element validate unique.
   */
  public function testValidateUnique() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_element_validate_unique');

    $edit = [
      'unique_textfield' => '{unique_textfield}',
      'unique_textfield_multiple[items][0][_item_]' => '{unique_textfield_multiple}',
      'unique_user_textfield' => '{unique_user_textfield}',
      'unique_entity_textfield' => '{unique_entity_textfield}',
      'unique_error' => '{unique_error}',
      'unique_error_html' => '{unique_error}',
      'unique_multiple[1]' => TRUE,
    ];

    // Check post submission with default values does not trigger
    // unique errors.
    $sid = $this->postSubmission($webform, $edit);
    $assert_session->responseNotContains('The value <em class="placeholder">{unique_textfield}</em> has already been submitted once for the <em class="placeholder">unique_textfield</em> element. You may have already submitted this webform, or you need to use a different value.</li>');
    $assert_session->responseNotContains('unique_textfield_multiple error message.');
    $assert_session->responseNotContains('unique_user_textfield error message.');
    $assert_session->responseNotContains('unique_entity_textfield error message.');
    $assert_session->responseNotContains('unique_error error message.');
    $assert_session->responseNotContains('unique_error <em>error message</em>.');
    $assert_session->responseNotContains('unique_multiple error message.');

    // Check post duplicate submission with default values does trigger
    // unique errors.
    $this->postSubmission($webform, $edit);
    $assert_session->responseContains('The value <em class="placeholder">{unique_textfield}</em> has already been submitted once for the <em class="placeholder">unique_textfield</em> element. You may have already submitted this webform, or you need to use a different value.</li>');
    $assert_session->responseContains('unique_textfield_multiple error message.');
    $assert_session->responseContains('unique_user_textfield error message.');
    $assert_session->responseContains('unique_entity_textfield error message.');
    $assert_session->responseContains('unique_error error message.');
    $assert_session->responseContains('unique_error <em>error message</em>.');
    $assert_session->responseContains('unique_multiple error message.');

    // Check #unique element can be updated.
    $this->drupalGet("admin/structure/webform/manage/test_element_validate_unique/submission/$sid/edit");
    $this->submitForm([], 'Save');
    $assert_session->responseNotContains('The value <em class="placeholder">{unique_textfield}</em> has already been submitted once for the <em class="placeholder">unique_textfield</em> element. You may have already submitted this webform, or you need to use a different value.</li>');
    $assert_session->responseNotContains('unique_user_textfield error message.');
    $assert_session->responseNotContains('unique_entity_textfield error message.');
    $assert_session->responseNotContains('unique_error error message.');
    $assert_session->responseNotContains('unique_error <em>error message</em>.');
    $assert_session->responseNotContains('unique_multiple error message.');

    // Check #unique multiple validation within the same element.
    // @see \Drupal\webform\Plugin\WebformElementBase::validateUniqueMultiple
    // Add 2 more items.
    $this->drupalGet('/webform/test_element_validate_unique');
    $edit = ['unique_textfield_multiple[add][more_items]' => 2];
    $this->submitForm($edit, 'unique_textfield_multiple_table_add');

    $edit = [
      'unique_textfield_multiple[items][0][_item_]' => '{same}',
      'unique_textfield_multiple[items][2][_item_]' => '{same}',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('unique_textfield_multiple error message.');

    // Check #unique multiple validation within the same element with
    // different cases.
    $edit = [
      'unique_textfield_multiple[items][0][_item_]' => '{same}',
      'unique_textfield_multiple[items][2][_item_]' => '{SAME}',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('unique_textfield_multiple error message.');

    // Purge existing submissions.
    $this->purgeSubmissions();

    // Check #unique_user triggers for anonymous users.
    $edit = ['unique_user_textfield' => '{unique_user_textfield}'];
    $this->postSubmission($webform, $edit);
    $assert_session->responseNotContains('unique_user_textfield error message.');
    $this->postSubmission($webform, $edit);
    $assert_session->responseContains('unique_user_textfield error message.');
    $edit = ['unique_user_textfield' => '{Unique_User_textfield}'];
    $this->postSubmission($webform, $edit);
    $assert_session->responseNotContains('unique_user_textfield error message.');

    // Create a user that is used as the source entity.
    $account = $this->drupalCreateUser();

    // Check #unique_entity triggers with source entity.
    $edit = ['unique_entity_textfield' => '{unique_entity_textfield}'];
    $options = ['query' => ['source_entity_type' => 'user', 'source_entity_id' => $account->id()]];
    $this->postSubmission($webform, $edit, NULL, $options);
    $assert_session->responseNotContains('unique_entity_textfield error message.');
    $this->postSubmission($webform, $edit, NULL, $options);
    $assert_session->responseContains('unique_entity_textfield error message.');

    // Check #unique_entity triggers without source entity.
    $edit = ['unique_entity_textfield' => '{unique_entity_textfield}'];
    $this->postSubmission($webform, $edit);
    $assert_session->responseNotContains('unique_entity_textfield error message.');
    $this->postSubmission($webform, $edit);
    $assert_session->responseContains('unique_entity_textfield error message.');
  }

}
