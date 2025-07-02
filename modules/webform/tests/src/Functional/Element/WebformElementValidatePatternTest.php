<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform pattern validation.
 *
 * @group webform
 */
class WebformElementValidatePatternTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_validate_pattern'];

  /**
   * Tests pattern validation.
   */
  public function testPattern() {
    $assert_session = $this->assertSession();

    // Check rendering.
    $this->drupalGet('/webform/test_element_validate_pattern');
    $assert_session->responseContains('<input pattern="Hello" data-drupal-selector="edit-pattern" aria-describedby="edit-pattern--description" type="text" id="edit-pattern" name="pattern" value="" size="60" maxlength="255" class="form-text" />');
    $assert_session->responseContains('<input pattern="Hello" data-webform-pattern-error="You did not enter &#039;Hello&#039;" data-drupal-selector="edit-pattern-error" aria-describedby="edit-pattern-error--description" type="text" id="edit-pattern-error" name="pattern_error" value="" size="60" maxlength="255" class="form-text" />');
    $assert_session->responseContains('<input pattern="Hello" data-webform-pattern-error="You did not enter Hello" data-drupal-selector="edit-pattern-error-html" aria-describedby="edit-pattern-error-html--description" type="text" id="edit-pattern-error-html" name="pattern_error_html" value="" size="60" maxlength="255" class="form-text" />');
    $assert_session->responseContains('<input pattern="\u2E8F" data-drupal-selector="edit-pattern-unicode" aria-describedby="edit-pattern-unicode--description" type="text" id="edit-pattern-unicode" name="pattern_unicode" value="" size="60" maxlength="255" class="form-text" />');

    // Check validation.
    $this->drupalGet('/webform/test_element_validate_pattern');
    $edit = [
      'pattern' => 'GoodBye',
      'pattern_error' => 'GoodBye',
      'pattern_error_html' => 'GoodBye',
      'pattern_unicode' => 'Unicode',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('<li><em class="placeholder">pattern</em> field is not in the right format.</li>');
    $assert_session->responseContains('<li>You did not enter &#039;Hello&#039;</li>');
    $assert_session->responseContains('<li>You did not enter <strong>Hello</strong></li>');
    $assert_session->responseContains('<li><em class="placeholder">pattern_unicode</em> field is not in the right format.</li>');

    // Check validation.
    $this->drupalGet('/webform/test_element_validate_pattern');
    $edit = [
      'pattern' => 'Hello',
      'pattern_error' => 'Hello',
      'pattern_error_html' => 'Hello',
      'pattern_unicode' => '⺏',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('<li><em class="placeholder">pattern</em> field is not in the right format.</li>');
    $assert_session->responseNotContains('<li>You did not enter &#039;Hello&#039;</li>');
    $assert_session->responseNotContains('<li>You did not enter <strong>Hello</strong></li>');
    $assert_session->responseNotContains('<li><em class="placeholder">pattern_unicode</em> field is not in the right format.</li>');
  }

}
