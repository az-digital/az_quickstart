<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\Component\Utility\UrlHelper;

/**
 * Tests the CAS forced login subscriber.
 *
 * @group cas
 */
class CasForcedAuthSubscriberTest extends CasBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'cas',
    'path',
    'filter',
    'node',
    'page_cache',
    'dynamic_page_cache',
  ];

  /**
   * Test that the CasSubscriber properly forces CAS authentication as expected.
   */
  public function testForcedLoginPaths() {
    global $base_path;

    $admin = $this->drupalCreateUser(['administer account settings']);
    $this->drupalLogin($admin);

    // Create some dummy nodes so we have some content paths to work with
    // when triggering forced auth paths.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateNode();
    $this->drupalCreateNode();
    $this->drupalCreateNode([
      'path' => [
        ['alias' => '/my/path'],
      ],
    ]);

    // Configure CAS with forced auth enabled for some of our node paths.
    $edit = [
      'server[hostname]' => 'fakecasserver.localhost',
      'server[path]' => '/auth',
      'forced_login[enabled]' => TRUE,
      'forced_login[paths][pages]' => "/node/2\n/my/path",
    ];
    $this->drupalGet('/admin/config/people/cas');
    $this->submitForm($edit, 'Save configuration');

    $config = $this->config('cas.settings');
    $this->assertTrue($config->get('forced_login.enabled'));
    $this->assertEquals("/node/2\n/my/path", $config->get('forced_login.paths')['pages']);

    $this->drupalLogout();

    $this->disableRedirects();
    $this->prepareRequest();

    $session = $this->getSession();

    // Our forced login subscriber should not intervene when viewing node/1.
    $session->visit($this->buildUrl('node/1', ['absolute' => TRUE]));
    $this->assertEquals(200, $session->getStatusCode());

    // But for node/2 and the node/3 path alias, we should be redirected to
    // the CAS server to login with the proper service URL appended as a query
    // string parameter.
    $session->visit($this->buildUrl('node/2', ['absolute' => TRUE]));
    $this->assertEquals(302, $session->getStatusCode());
    $expected_redirect_url = 'https://fakecasserver.localhost/auth/login?' . UrlHelper::buildQuery(['service' => $this->buildServiceUrlWithParams(['destination' => $base_path . 'node/2'])]);
    $this->assertEquals($expected_redirect_url, $session->getResponseHeader('Location'));

    // For the node/3 path alias, also test that query params, including the
    // destination param, are preserved.
    $session->visit($this->buildUrl('my/path', [
      'absolute' => TRUE,
      'query' => ['foo' => 'bar', 'destination' => '/some/other/path'],
    ]));
    $this->assertEquals(302, $session->getStatusCode());
    $expected_redirect_url = 'https://fakecasserver.localhost/auth/login?' . UrlHelper::buildQuery(['service' => $this->buildServiceUrlWithParams(['destination' => $base_path . 'my/path?destination=%2Fsome%2Fother%2Fpath&foo=bar'])]);
    $this->assertEquals($expected_redirect_url, $session->getResponseHeader('Location'));

    // When we are already logged in, we should not be redirected to the CAS
    // server when hitting a forced login path.
    $this->enabledRedirects();
    $this->drupalLogin($admin);
    $session->visit($this->buildUrl('node/2', ['absolute' => TRUE]));
    $this->assertEquals(200, $session->getStatusCode());
  }

}
