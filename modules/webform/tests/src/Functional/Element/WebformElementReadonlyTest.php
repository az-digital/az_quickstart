<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform element readonly attribute.
 *
 * @group webform
 */
class WebformElementReadonlyTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_readonly'];

  /**
   * Tests element readonly.
   */
  public function testReadonly() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_readonly');

    $this->assertCssSelect('.js-form-item-textfield.form-item-textfield');
    $assert_session->responseContains('<input readonly="readonly" data-drupal-selector="edit-textfield" type="text" id="edit-textfield" name="textfield" value="" size="60" maxlength="255" class="form-text" />');
    $this->assertCssSelect('.js-form-item-textarea.form-item-textarea');
    $assert_session->responseContains('<textarea readonly="readonly" data-drupal-selector="edit-textarea" id="edit-textarea" name="textarea" rows="5" cols="60" class="form-textarea"></textarea>');
  }

}
