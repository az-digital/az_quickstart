<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\Component\Utility\UrlHelper;

/**
 * Tests the CAS forced login controller.
 *
 * @group cas
 */
class CasForcedLoginControllerTest extends CasBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['cas', 'page_cache', 'dynamic_page_cache'];

  /**
   * Tests the the forced login route that redirects users authenticate.
   */
  public function testForcedLoginRoute() {
    $admin = $this->drupalCreateUser(['administer account settings']);
    $this->drupalLogin($admin);

    $edit = [
      'server[hostname]' => 'fakecasserver.localhost',
      'server[path]' => '/auth',
    ];
    $this->drupalGet('/admin/config/people/cas');
    $this->submitForm($edit, 'Save configuration');

    $this->drupalLogout();

    $this->disableRedirects();
    $this->prepareRequest();
    $session = $this->getSession();

    // We want to test that query string parameters that are present on the
    // request to the forced login route are passed along to the service
    // URL as well, so test each of these cases individually.
    $params_to_test = [
      [],
      ['destination' => 'node/1'],
      ['foo' => 'bar', 'buzz' => 'baz'],
    ];
    foreach ($params_to_test as $params) {
      $path = $this->buildUrl('cas', ['query' => $params, 'absolute' => TRUE]);

      $session->visit($path);

      $this->assertEquals(302, $session->getStatusCode());
      $expected_redirect_location = 'https://fakecasserver.localhost/auth/login?' . UrlHelper::buildQuery(['service' => $this->buildServiceUrlWithParams($params)]);
      $this->assertEquals($expected_redirect_location, $session->getResponseHeader('Location'));
    }
  }

}
