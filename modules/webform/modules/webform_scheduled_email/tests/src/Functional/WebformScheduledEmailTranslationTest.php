<?php

namespace Drupal\Tests\webform_scheduled_email\Functional;

use Drupal\Tests\webform_node\Functional\WebformNodeBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform scheduled email handler translation.
 *
 * @group webform_scheduled_email
 */
class WebformScheduledEmailTranslationTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_scheduled_email', 'webform_scheduled_email_test_translation'];

  /**
   * Tests webform schedule email handler translation.
   */
  public function testWebformScheduledEmailTranslation() {
    $webform_schedule = Webform::load('test_handler_scheduled_translate');

    /** @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $scheduled_manager */
    $scheduled_manager = \Drupal::service('webform_scheduled_email.manager');

    /* ********************************************************************** */

    // Scheduled English email.
    $this->drupalGet('/webform/' . $webform_schedule->id());
    $this->submitForm([], 'Submit');

    // Send email.
    $scheduled_manager->cron();

    // Check that scheduled English email as sent in English.
    $sent_email = $this->getLastEmail();
    $this->assertEquals($sent_email['subject'], 'English Subject');
    $this->assertEquals($sent_email['body'], 'English Body' . PHP_EOL);

    // Scheduled Spanish email.
    $this->drupalGet('/es/webform/' . $webform_schedule->id());
    $this->submitForm([], 'Submit');

    // Send email.
    $scheduled_manager->cron();

    // Check that scheduled Spanish email as sent in Spanish.
    $sent_email = $this->getLastEmail();
    $this->assertEquals($sent_email['subject'], 'Spanish Subject');
    $this->assertEquals($sent_email['body'], 'Spanish Body' . PHP_EOL);
  }

}
