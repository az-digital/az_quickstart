<?php

namespace Drupal\Tests\webform\Functional\States;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform states manager.
 *
 * @group webform
 */
class WebformStatesManagerTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_states'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_states_to_text'];

  /**
   * Tests states to text.
   */
  public function testStatesToText() {
    $assert_session = $this->assertSession();

    // Check converting #states to human readable text.
    $this->drupalGet('/webform/test_states_to_text/confirmation');
    $assert_session->responseContains('<label>textfield_and</label>');
    $assert_session->responseContains('This element is <strong>required</strong> when <strong>all</strong> of the following conditions are met:<ul><li><strong>some_trigger</strong> is checked.</li><li><strong>some_value</strong> = <strong>one</strong>.</li><li><strong>some_number</strong> &gt;= <strong>1</strong>.</li></ul>');
    $assert_session->responseContains('<label>textfield_or</label>');
    $assert_session->responseContains('This element is <strong>required</strong> when <strong>any</strong> of the following conditions are met:<ul><li><strong>some_trigger</strong> is checked.</li><li><strong>some_value</strong> = <strong>one</strong>.</li><li><strong>some_number</strong> &gt;= <strong>1</strong>.</li></ul>');
    $assert_session->responseContains('<label>textfield_or</label>');
    $assert_session->responseContains('This element is <strong>required</strong> when <strong>any</strong> of the following conditions are met:<ul><li><strong>some_trigger</strong> is not checked.</li><li>When <strong>any</strong> of the following (nested) conditions are met:<ul><li><strong>some_value</strong> = <strong>one</strong>.</li><li><strong>some_number</strong> is between <strong>1</strong> and <strong>10</strong>.</li></ul></li></ul>');
  }

}
