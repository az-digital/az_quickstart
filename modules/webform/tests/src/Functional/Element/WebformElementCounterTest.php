<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform (text) counter.
 *
 * @group webform
 */
class WebformElementCounterTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_counter'];

  /**
   * Tests text elements.
   */
  public function testCounter() {
    $assert_session = $this->assertSession();

    // Check counters.
    $this->drupalGet('/webform/test_element_counter');
    $assert_session->responseContains('<input data-counter-type="character" data-counter-minimum="5" data-counter-minimum-message="%d character(s) entered. This is custom text" class="js-webform-counter webform-counter form-text" minlength="5" data-drupal-selector="edit-counter-characters-min-message" type="text" id="edit-counter-characters-min-message" name="counter_characters_min_message" value="" size="60" maxlength="255" />');
    $assert_session->responseContains('<input data-counter-type="character" data-counter-maximum="10" data-counter-maximum-message="%d character(s) remaining. This is custom text" class="js-webform-counter webform-counter form-text" data-drupal-selector="edit-counter-characters-max-message" type="text" id="edit-counter-characters-max-message" name="counter_characters_max_message" value="" size="60" maxlength="10" />');
    $assert_session->responseContains('<textarea data-counter-type="word" data-counter-minimum="5" data-counter-minimum-message="%d word(s) entered. This is custom text" class="js-webform-counter webform-counter form-textarea" data-drupal-selector="edit-counter-words-min-message" id="edit-counter-words-min-message" name="counter_words_min_message" rows="5" cols="60"></textarea>');
    $assert_session->responseContains('<textarea data-counter-type="word" data-counter-maximum="10" data-counter-maximum-message="%d character(s) remaining. This is custom text" class="js-webform-counter webform-counter form-textarea" data-drupal-selector="edit-counter-words-max-message" id="edit-counter-words-max-message" name="counter_words_max_message" rows="5" cols="60"></textarea>');

    // Check counter for XSS.
    $assert_session->responseContains('<input data-counter-type="character" data-counter-minimum="5" data-counter-minimum-message="alert(&#039;XSS&#039;);&lt;em&gt;%d&lt;/em&gt; character(s) entered." data-counter-maximum="10" data-counter-maximum-message="alert(&#039;XSS&#039;);&lt;em&gt;%d&lt;/em&gt; character(s) remaining." class="js-webform-counter webform-counter form-text" minlength="5" data-drupal-selector="edit-counter-characters-xss" type="text" id="edit-counter-characters-xss" name="counter_characters_xss" value="" size="60" maxlength="10" />');

    // Check counter min/max validation error (min: 5 / max: 10).
    $this->drupalGet('/webform/test_element_counter');
    $edit = [
      'counter_characters_min' => '123',
      'counter_characters_max' => '1234567890xxx',
      'counter_words_min' => 'one two three',
      'counter_words_max' => 'one two three four five six seven eight nine ten eleven',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('counter_characters_min (5) must be at least <em class="placeholder">5</em> characters but is currently <em class="placeholder">3</em> characters long.');
    $assert_session->responseContains('counter_characters_max (10) cannot be longer than <em class="placeholder">10</em> characters but is currently <em class="placeholder">13</em> characters long.');
    $assert_session->responseContains('counter_words_min (5) must be at least <em class="placeholder">5</em> words but is currently <em class="placeholder">3</em> words long.');
    $assert_session->responseContains('counter_words_max (10) cannot be longer than <em class="placeholder">10</em> words but is currently <em class="placeholder">11</em> words long.');

    // Check counter validation passes (min: 5 / max: 10).
    $this->drupalGet('/webform/test_element_counter');
    $edit = [
      'counter_characters_min' => '12345',
      'counter_characters_max' => '1234567890',
      'counter_words_min' => 'one two three four five',
      'counter_words_max' => 'one two three four five six seven eight nine ten',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('counter_characters_min (5) must be at least <em class="placeholder">5</em> characters');
    $assert_session->responseNotContains('counter_characters_max (10) cannot be longer than <em class="placeholder">10</em> characters');
    $assert_session->responseNotContains('counter_words_min (5) must be at least <em class="placeholder">5</em> words');
    $assert_session->responseNotContains('counter_words_max (10) cannot be longer than <em class="placeholder">10</em> words');
  }

}
