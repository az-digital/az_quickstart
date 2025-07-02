<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform submission form settings.
 *
 * @group webform
 */
class WebformSettingsScheduleTest extends WebformBrowserTestBase {


  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_form_opening',
    'test_form_closed',
  ];

  /**
   * Tests webform settings opening and closed schedule.
   */
  public function testSchedule() {
    $assert_session = $this->assertSession();

    /* ********************************************************************** */
    /* Test webform opening (status=scheduled) */
    /* ********************************************************************** */

    $webform_opening = Webform::load('test_form_opening');

    // Check webform open message is displayed.
    $this->assertTrue($webform_opening->isClosed());
    $this->assertTrue($webform_opening->isOpening());
    $this->drupalGet('/webform/test_form_opening');
    $assert_session->responseNotContains('This message should not be displayed)');
    $assert_session->responseContains('This form is opening soon.');

    // Check webform closed message is displayed.
    $webform_opening->setSetting('form_open_message', '');
    $webform_opening->save();
    $this->drupalGet('/webform/test_form_opening');
    $assert_session->responseNotContains('This form is opening soon.');
    $assert_session->responseContains('This form has not yet been opened to submissions.');

    $this->drupalLogin($this->rootUser);

    // Check webform is not closed for admins and warning is displayed.
    $this->drupalGet('/webform/test_form_opening');
    $assert_session->responseContains('This message should not be displayed');
    $assert_session->responseNotContains('This form has not yet been opened to submissions.');
    $assert_session->responseContains('Only submission administrators are allowed to access this webform and create new submissions.');

    // Check webform opening message is not displayed.
    $webform_opening->set('status', WebformInterface::STATUS_OPEN);
    $webform_opening->save();
    $this->assertFalse($webform_opening->isClosed());
    $this->assertTrue($webform_opening->isOpen());
    $this->drupalGet('/webform/test_form_opening');
    $assert_session->responseContains('This message should not be displayed');
    $assert_session->responseNotContains('This form has not yet been opened to submissions.');
    $assert_session->responseNotContains('Only submission administrators are allowed to access this webform and create new submissions.');

    /* ********************************************************************** */
    /* Test webform closed (status=closed) */
    /* ********************************************************************** */

    $webform_closed = Webform::load('test_form_closed');

    $this->drupalLogout();

    // Check webform closed message is displayed.
    $this->assertTrue($webform_closed->isClosed());
    $this->assertFalse($webform_closed->isOpen());
    $this->drupalGet('/webform/test_form_closed');
    $assert_session->responseNotContains('This message should not be displayed)');
    $assert_session->responseContains('This form is closed.');

    // Check webform closed message is displayed.
    $webform_closed->setSetting('form_close_message', '');
    $webform_closed->save();
    $this->drupalGet('/webform/test_form_closed');
    $assert_session->responseNotContains('This form is closed.');
    $assert_session->responseContains('Sorry… This form is closed to new submissions.');

    $this->drupalLogin($this->rootUser);

    // Check webform is not closed for admins and warning is displayed.
    $this->drupalGet('/webform/test_form_closed');
    $assert_session->responseContains('This message should not be displayed');
    $assert_session->responseNotContains('This form is closed.');
    $assert_session->responseContains('Only submission administrators are allowed to access this webform and create new submissions.');

    // Check webform closed message is not displayed.
    $webform_closed->set('status', WebformInterface::STATUS_OPEN);
    $webform_closed->save();
    $this->assertFalse($webform_closed->isClosed());
    $this->assertTrue($webform_closed->isOpen());
    $this->drupalGet('/webform/test_form_closed');
    $assert_session->responseContains('This message should not be displayed');
    $assert_session->responseNotContains('This form is closed.');
    $assert_session->responseNotContains('Only submission administrators are allowed to access this webform and create new submissions.');
  }

}
