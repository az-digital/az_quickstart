<?php

namespace Drupal\Tests\antibot\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests Antibot in browsers with JavaScript.
 *
 * @group antibot
 */
class AntibotJavaScriptTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['antibot'];

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests antibot in browsers with JavaScript.
   */
  public function testJavaScript() {
    $this->drupalGet('/user/password');
    $page = $this->getSession()->getPage();
    $driver = $this->getSession()->getDriver();

    // Fill the name field with an arbitrary value.
    $page->fillField('Username or email address', $this->randomMachineName());

    // Post the form by using JavaScript, as if we were a bot. We avoid using
    // Mink methods that are simulating mouse or keyboard interaction.
    $session = $this->getSession();
    $session->executeScript('document.forms["user-pass"].submit()');

    // Check that we reached the antibot closed road when the form is posted by
    // a bot even having JavaScript capabilities and no mouse or keyboard were
    // performed.
    $this->assertSession()->waitForText('Submission failed');
    $this->assertSession()->pageTextContains('You have reached this page because you submitted a form that required JavaScript to be enabled on your browser. This protection is in place to attempt to prevent automated submissions made on forms. Please return to the page that you came from and enable JavaScript on your browser before attempting to submit the form again.');

    // Mimic a human behavior.
    $this->drupalGet('/user/password');

    // Fill the name field with an arbitrary value.
    $name = $this->randomMachineName();
    $page->fillField('Username or email address', $name);

    // Do the same post, via JavaScript but move the mouse before.
    $driver->mouseOver('//h1[text() = "Reset your password"]');
    $session->executeScript('document.forms["user-pass"].submit()');
    // Check that the form has been posted (even with a validation error).
    $this->assertSession()->waitForText("{$name} is not recognized as a username or an email address.");

    // Do the same post, via JavaScript but do a drag-and-drop before.
    $this->drupalGet('/user/password');
    $driver->dragTo("//h1[text()='Reset your password']", '//p[text() = "Password reset instructions will be sent to your registered email address."]');
    $name = $this->randomMachineName();
    $page->fillField('Username or email address', $name);
    $session->executeScript('document.forms["user-pass"].submit()');
    // Check that the form has been posted (even with a validation error).
    $this->assertSession()->waitForText("{$name} is not recognized as a username or an email address.");

    // @todo Testing ENTER and TAB keys it's not easy because is hard to emulate
    // a key press in Selenium2. Keep trying.
    // @see https://stackoverflow.com/questions/17333842/can-i-send-raw-keyboard-input-using-mink-and-selenium2/37025784
  }

}
