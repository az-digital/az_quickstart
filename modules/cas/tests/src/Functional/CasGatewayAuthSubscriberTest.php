<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\cas\Service\CasHelper;
use Drupal\Component\Utility\UrlHelper;

/**
 * Tests the CAS gateway login subscriber.
 *
 * @group cas
 */
class CasGatewayAuthSubscriberTest extends CasBrowserTestBase {

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
   * Test that the gateway auth works as expected.
   */
  public function testGatewayPaths() {
    global $base_path;
    $admin = $this->drupalCreateUser(['administer account settings']);
    $this->drupalLogin($admin);

    // Create some dummy nodes so we have some content paths to work with.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateNode();
    $this->drupalCreateNode();

    // Configure CAS with server-side gateway auth enabled for both nodes.
    $edit = [
      'server[hostname]' => 'fakecasserver.localhost',
      'server[path]' => '/auth',
      'gateway[enabled]' => TRUE,
      'gateway[recheck_time]' => 720,
      'gateway[paths][pages]' => "/node/1\n/node/2",
      'gateway[method]' => CasHelper::GATEWAY_SERVER_SIDE,
    ];
    $this->drupalGet('/admin/config/people/cas');
    $this->submitForm($edit, 'Save configuration');

    // Check that settings saved correctly.
    $config = $this->config('cas.settings');
    $this->assertEquals(TRUE, $config->get('gateway.enabled'));
    $this->assertEquals(720, $config->get('gateway.recheck_time'));
    $this->assertEquals(CasHelper::GATEWAY_SERVER_SIDE, $config->get('gateway.method'));
    $this->assertEquals("/node/1\n/node/2", $config->get('gateway.paths')['pages']);

    $session = $this->getSession();

    // Ensure that after visiting a node once and redirected, we are not
    // redirected AGAIN after visiting another gateway enabled path again.
    // This is because we are set to only check once per X amount of time for
    // user session.
    // We disable redirects so the browser doesn't attempt to actually go
    // to the fake CAS server and so we can inspect the redirect.
    $this->drupalLogout();
    $this->disableRedirects();
    $this->prepareRequest();
    $session->visit($this->buildUrl('node/1', ['absolute' => TRUE]));
    $this->assertEquals(302, $session->getStatusCode());
    $this->assertEquals($this->getExpectedRedirectUrl(1), $session->getResponseHeader('Location'));
    // A cookie should have been set indicating we've checked for gateway.
    $this->assertNotEmpty($this->getSession()->getCookie('cas_gateway_checked_ss'));
    // Visiting another page that has gateway enabled should NOT redirect there
    // because of the cookie that's set.
    // Revisit same page again first to flush the session var that gateway
    // subscriber uses to disable next request.
    $session->visit($this->buildUrl('node/1', ['absolute' => TRUE]));
    $session->visit($this->buildUrl('node/2', ['absolute' => TRUE]));
    $this->assertEquals(200, $session->getStatusCode());
    $this->assertNotEmpty($this->getSession()->getCookie('cas_gateway_checked_ss'));

    // But if we clear cookies between requests, we should be redirected both
    // times.
    $session->reset();
    $session->visit($this->buildUrl('node/1', ['absolute' => TRUE]));
    $this->assertEquals(302, $session->getStatusCode());
    $this->assertEquals($this->getExpectedRedirectUrl(1), $session->getResponseHeader('Location'));
    $this->assertNotEmpty($this->getSession()->getCookie('cas_gateway_checked_ss'));
    $session->reset();
    $session->visit($this->buildUrl('node/2', ['absolute' => TRUE]));
    $this->assertEquals(302, $session->getStatusCode());
    $this->assertEquals($this->getExpectedRedirectUrl(2), $session->getResponseHeader('Location'));

    // Now configure it so that it checks EVERY Request and confirm that it
    // works, even without resetting cookies.
    $session->reset();
    $this->drupalLogin($admin);
    $edit = [
      'gateway[recheck_time]' => "-1",
    ];
    $this->drupalGet('/admin/config/people/cas');
    $this->submitForm($edit, 'Save configuration');
    $this->drupalLogout();
    $this->disableRedirects();
    $this->prepareRequest();

    $session->visit($this->buildUrl('node/1', ['absolute' => TRUE]));
    $this->assertEquals(302, $session->getStatusCode());
    $this->assertEquals($this->getExpectedRedirectUrl(1), $session->getResponseHeader('Location'));
    $this->assertEmpty($this->getSession()->getCookie('cas_gateway_checked_ss'));

    // Revisit same page again first to flush the session var that gateway
    // subscriber uses to disable next request.
    $session->visit($this->buildUrl('node/1', ['absolute' => TRUE]));
    $session->visit($this->buildUrl('node/2', ['absolute' => TRUE]));
    $this->assertEquals(302, $session->getStatusCode());
    $this->assertEquals($this->getExpectedRedirectUrl(2), $session->getResponseHeader('Location'));
    $this->assertEmpty($this->getSession()->getCookie('cas_gateway_checked_ss'));

    // Restrict the redirects to only the first node and not the second node,
    // confirm we aren't redirected at all for second node.
    $session->reset();
    $this->drupalLogin($admin);
    $edit = [
      'gateway[paths][pages]' => '/node/1',
    ];
    $this->drupalGet('/admin/config/people/cas');
    $this->submitForm($edit, 'Save configuration');
    $this->drupalLogout();
    $this->disableRedirects();
    $this->prepareRequest();
    $session->visit($this->buildUrl('node/2', ['absolute' => TRUE]));
    $this->assertEquals(200, $session->getStatusCode());

    // Disable gateway auth and verify it does NOT redirect the user when
    // visiting the configured path.
    $session->reset();
    $this->drupalLogin($admin);
    $edit = [
      'gateway[enabled]' => FALSE,
    ];
    $this->drupalGet('/admin/config/people/cas');
    $this->submitForm($edit, 'Save configuration');
    $this->drupalLogout();
    $this->disableRedirects();
    $this->prepareRequest();

    $session = $this->getSession();
    $session->visit($this->buildUrl('node/1', ['absolute' => TRUE]));
    $this->assertEquals(200, $session->getStatusCode());

    // Test that client side redirect cannot be enabled when using the
    // "every page request" recheck option.
    $this->drupalLogin($admin);
    $edit = [
      'gateway[enabled]' => TRUE,
      'gateway[recheck_time]' => "-1",
      'gateway[method]' => CasHelper::GATEWAY_CLIENT_SIDE,
    ];
    $this->drupalGet('/admin/config/people/cas');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The "Every page request" recheck time is not compatible with the "Client-side" method');

    // Enable client side redirect and confirm that the user is not redirected
    // to CAS via serverside, but the page contains the JS library to redirect.
    // @todo test with a JS driver as well so we know the JS code works?
    $edit = [
      'gateway[enabled]' => TRUE,
      'gateway[recheck_time]' => 720,
      'gateway[method]' => CasHelper::GATEWAY_CLIENT_SIDE,
    ];
    $this->drupalGet('/admin/config/people/cas');
    $this->submitForm($edit, 'Save configuration');
    $this->drupalLogout();
    $this->disableRedirects();
    $this->prepareRequest();
    $session->visit($this->buildUrl('node/1', ['absolute' => TRUE]));
    $this->assertEquals(200, $session->getStatusCode());

    // Drupal settings should be populated with the redirect URL.
    $drupalSettings = $this->getDrupalSettings();
    $this->assertEquals($this->getExpectedRedirectUrl(1), $drupalSettings['cas']['gatewayRedirectUrl']);
    $this->assertEquals(720, $drupalSettings['cas']['recheckTime']);

    // The JS data should not be added on paths that aren't configured to have
    // gateway enabled.
    $session->visit($this->buildUrl('node/2', ['absolute' => TRUE]));
    $this->assertEquals(200, $session->getStatusCode());
    $this->assertEmpty($this->getDrupalSettings());

    // @todo Test that visting page as a bot does NOT trigger a redirect.
    // We cannot do this at the moment because we can't spoof a user agent!
    // See https://www.drupal.org/node/2820515.
  }

  /**
   * Returns the expected redirect URL.
   *
   * @param int $nid
   *   The node ID.
   *
   * @return string
   *   The expected redirect URL.
   */
  protected function getExpectedRedirectUrl(int $nid): string {
    return 'https://fakecasserver.localhost/auth/login?' . UrlHelper::buildQuery([
      'gateway' => 'true',
      'service' => $this->buildServiceUrlWithParams([
        'destination' => "{$GLOBALS['base_path']}node/{$nid}",
        'from_gateway' => 1,
      ]),
    ]);
  }

}
