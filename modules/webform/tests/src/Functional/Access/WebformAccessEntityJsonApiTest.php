<?php

namespace Drupal\Tests\webform\Functional\Access;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform entity JSON API access.
 *
 * @group webform
 */
class WebformAccessEntityJsonApiTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'jsonapi'];

  /**
   * Tests webform entity REST access.
   */
  public function testRestAccess() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('contact');
    $uuid = $webform->uuid();

    $account = $this->drupalCreateUser();

    $configuration_account = $this->drupalCreateUser([
      'access any webform configuration',
    ]);

    /* ********************************************************************** */

    // Check anonymous access denied to webform.
    $this->drupalGet("jsonapi/webform/webform/$uuid");
    $assert_session->responseContains('"title":"Forbidden","status":"403","detail":"The current user is not allowed to GET the selected resource. Access to webform configuration is required."');

    // Login authenticated user.
    $this->drupalLogin($account);

    // Check authenticated access allowed to webform.
    $this->drupalGet('/webform/contact');
    $assert_session->fieldExists('subject');

    // Check authenticated access denied to webform via _format=hal_json.
    $this->drupalGet("jsonapi/webform/webform/$uuid");
    $assert_session->responseContains('"title":"Forbidden","status":"403","detail":"The current user is not allowed to GET the selected resource. Access to webform configuration is required."');

    // Login rest (permission) user.
    $this->drupalLogin($configuration_account);

    // Check rest access allowed to webform.
    $this->drupalGet("jsonapi/webform/webform/$uuid");
    $assert_session->responseNotContains('"title":"Forbidden","status":"403","detail":"The current user is not allowed to GET the selected resource. Access to webform configuration is required."');
    $assert_session->responseContains('"title":"Contact",');

    // Allow anonymous role to access webform configuration.
    $access_rules = $webform->getAccessRules();
    $access_rules['configuration']['roles'] = ['anonymous', 'authenticated'];
    $webform->setAccessRules($access_rules);
    $webform->save();

    // Login out and switch to anonymous user.
    $this->drupalLogout();

    // Check anonymous access allowed to webform.
    $this->drupalGet("jsonapi/webform/webform/$uuid");
    $assert_session->responseNotContains('"title":"Forbidden","status":"403","detail":"The current user is not allowed to GET the selected resource. Access to webform configuration is required."');

    // Login authenticated user.
    $this->drupalLogin($account);

    // Check authenticated access allowed to webform.
    $this->drupalGet("jsonapi/webform/webform/$uuid");
    $assert_session->responseNotContains('"title":"Forbidden","status":"403","detail":"The current user is not allowed to GET the selected resource. Access to webform configuration is required."');
  }

}
