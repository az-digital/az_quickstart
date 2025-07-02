<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform validate minlength.
 *
 * @group webform
 */
class WebformElementValidateMinlengthTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_validate_minlength'];

  /**
   * Tests element validate minlength.
   */
  public function testValidateMinlength() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_validate_minlength');

    /* Render */

    $this->drupalGet('/webform/test_element_validate_minlength');

    // Check minlength attribute.
    $this->assertCssSelect('#edit-minlength-textfield[minlength="5"]');
    $this->assertCssSelect('#edit-minlength-textfield-required[minlength="5"]');

    /* Validate */

    // Check minlength validation.
    $this->postSubmission($webform, ['minlength_textfield' => 'X']);
    $assert_session->responseContains('<em class="placeholder">minlength_textfield</em> cannot be less than <em class="placeholder">5</em> characters but is currently <em class="placeholder">1</em> characters long.');

    // Check minlength not required validation.
    $this->postSubmission($webform, ['minlength_textfield' => '']);
    $assert_session->responseNotContains('<em class="placeholder">minlength_textfield</em> cannot be less than <em class="placeholder">5</em> characters but is currently <em class="placeholder">0</em> characters long.');

    // Check minlength required validation.
    $this->postSubmission($webform, ['minlength_textfield_required' => '']);
    $assert_session->responseNotContains('<em class="placeholder">minlength_textfield_required</em> cannot be less than <em class="placeholder">5</em> characters but is currently <em class="placeholder">0</em> characters long.');
    $assert_session->responseContains('minlength_textfield_required field is required.');
  }

}
