<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform element private.
 *
 * @group webform
 */
class WebformElementPrivateTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_private'];

  /**
   * Test element access.
   */
  public function testElementAccess() {
    $assert_session = $this->assertSession();

    $normal_user = $this->drupalCreateUser(['view own webform submission']);

    $webform = Webform::load('test_element_private');

    /* ********************************************************************** */

    // Login as normal user.
    $this->drupalLogin($normal_user);

    // Create two webform submissions.
    $this->postSubmission($webform);
    $sid = $this->postSubmission($webform);

    // Check element with #private property hidden for normal user.
    $this->drupalGet('/webform/test_element_private');
    $assert_session->fieldNotExists('private');

    // Check submission data with #private property hidden for normal user.
    $this->drupalGet("/webform/test_element_private/submissions/$sid");
    $this->assertNoCssSelect('#test_element_private--private');
    $assert_session->responseNotContains('<label>private</label>');

    // Check user submissions columns excludes 'private' column.
    $this->drupalGet('/webform/test_element_private/submissions');
    $assert_session->responseNotContains('<th specifier="element__private">');

    // Login as root user.
    $this->drupalLogin($this->rootUser);

    // Check element with #private property visible for admin user.
    $this->drupalGet('/webform/test_element_private');
    $assert_session->fieldValueEquals('private', '');

    // Check submission data with #private property visible for admin user.
    $this->drupalGet("/webform/test_element_private/submissions/$sid");
    $this->assertCssSelect('#test_element_private--private');
    $assert_session->responseContains('<label>private</label>');

    // Check user submissions columns include 'private' column.
    $this->drupalGet('/webform/test_element_private/submissions');
    $assert_session->responseContains('<th specifier="element__private">');
  }

}
