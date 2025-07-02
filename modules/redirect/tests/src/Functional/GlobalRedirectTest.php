<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect\Functional;

use Drupal\Core\Language\Language;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Global redirect test cases.
 *
 * @group redirect
 */
class GlobalRedirectTest extends BrowserTestBase {

  use PathAliasTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'path',
    'node',
    'redirect',
    'taxonomy',
    'views',
    'language',
    'content_translation',
  ];

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $normalUser;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $term;

  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function initMink() {
    $session = parent::initMink();

    /** @var \Behat\Mink\Driver\BrowserKitDriver $driver */
    $driver = $session->getDriver();
    // Since we are testing low-level redirect stuff, the HTTP client should
    // NOT automatically follow redirects sent by the server.
    $driver->getClient()->followRedirects(FALSE);

    return $session;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config = $this->config('redirect.settings');

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Create a users for testing the access.
    $this->normalUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'create url aliases',
      'access administration pages',
    ]);
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'administer languages',
      'administer content types',
      'administer content translation',
      'create page content',
      'edit own page content',
      'create content translations',
    ]);

    // Save the node.
    $this->node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Test Page Node',
      'path' => ['alias' => '/test-node'],
      'language' => Language::LANGCODE_NOT_SPECIFIED,
    ]);

    // Create an alias for the create story path - this is used in the
    // "redirect with permissions testing" test.
    $this->createPathAlias('/admin/config/system/site-information', '/site-info');

    // Create another taxonomy vocabulary with a term.
    $vocab = Vocabulary::create([
      'name' => 'test vocab',
      'vid' => 'test-vocab',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocab->save();
    $term = Term::create([
      'name' => 'Test Term',
      'vid' => $vocab->id(),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'path' => ['alias' => '/test-term'],
    ]);
    $term->save();

    $this->term = $term;
  }

  /**
   * Will test the redirects.
   */
  public function testRedirects() {
    // First, test that redirects can be disabled.
    $this->config->set('route_normalizer_enabled', FALSE)->save();
    $this->assertNoRedirect('index.php/node/' . $this->node->id());
    $this->assertNoRedirect('index.php/test-node');
    $this->assertNoRedirect('test-node/');
    $this->assertNoRedirect('Test-node/');
    $this->config->set('route_normalizer_enabled', TRUE)->save();

    // Test alias normalization.
    $this->assertRedirect('node/' . $this->node->id(), 'test-node');
    $this->assertRedirect('Test-node', 'test-node');

    // Test redirects for non-clean urls.
    $this->assertRedirect('index.php/node/' . $this->node->id(), 'test-node');
    $this->assertRedirect('index.php/test-node', 'test-node');

    // Test deslashing.
    $this->assertRedirect('test-node/', 'test-node');

    // Test front page redirects.
    $this->config('system.site')->set('page.front', '/node')->save();
    $this->assertRedirect('node', '/');

    // Test front page redirects with an alias.
    $this->createPathAlias('/node', '/node-alias');
    $this->assertRedirect('node-alias', '/');

    // Test a POST request. It should stay on the same path and not try to
    // redirect. Because Mink does not provide methods to do plain POSTs, we
    // need to use the underlying Guzzle HTTP client directly.
    /** @var \Behat\Mink\Driver\BrowserKitDriver $driver */
    $driver = $this->getSession()->getDriver();
    $response = $driver->getClient()
      ->getClient()
      ->post($this->getAbsoluteUrl('Test-node'), [
        // Do not follow redirects. This way, we can assert that the server did
        // not even _try_ to redirect us
        'allow_redirects' => FALSE,
        'headers' => [
          'Accept' => 'application/json',
        ],
      ]);
    // Does not do a redirect, stays in the same path.
    $this->assertSame(200, $response->getStatusCode());
    $this->assertEmpty($response->getHeader('Location'));
    $this->assertStringNotContainsString('http-equiv="refresh', (string) $response->getBody());

    // Test the access checking.
    $this->config->set('access_check', TRUE)->save();
    $this->assertNoRedirect('admin/config/system/site-information', 403);

    $this->config->set('access_check', FALSE)->save();
    // @todo Here it seems that the access check runs prior to our redirecting
    //   check why so and enable the test.
    //   $this->assertRedirect('admin/config/system/site-information', 'site-info');

    // Test original query string is preserved with alias normalization.
    $this->assertRedirect('Test-node?&foo&.bar=baz', 'test-node?&foo&.bar=baz');

    // Test alias normalization with trailing ?.
    // @todo \GuzzleHttp\Psr7\Uri strips away the trailing ?, this should
    //   actually be a redirect but can't be tested with Guzzle. Improve in
    //   https://www.drupal.org/project/redirect/issues/3119503.
    $this->assertNoRedirect('test-node?');
    $this->assertRedirect('Test-node?', 'test-node');

    // Test alias normalization still works without trailing ?.
    $this->assertNoRedirect('test-node');
    $this->assertRedirect('Test-node', 'test-node');

    // Login as user with admin privileges.
    $this->drupalLogin($this->adminUser);

    // Test ignoring admin paths.
    $this->config->set('ignore_admin_path', FALSE)->save();
    $this->assertRedirect('admin/config/system/site-information', 'site-info');

    // Test alias normalization again with ignore_admin_path false.
    $this->assertRedirect('Test-node', 'test-node');

    $this->config->set('ignore_admin_path', TRUE)->save();
    $this->assertNoRedirect('admin/config/system/site-information');

    // Test alias normalization again with ignore_admin_path true.
    $this->assertRedirect('Test-node', 'test-node');
  }

  /**
   * Test that redirects work properly with content_translation enabled.
   */
  public function testLanguageRedirects() {
    $this->drupalLogin($this->adminUser);

    // Add a new language.
    ConfigurableLanguage::createFromLangcode('es')
      ->save();

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');

    // Set page content type to use multilingual support.
    $edit = [
      'language_configuration[language_alterable]' => TRUE,
      'language_configuration[content_translation]' => TRUE,
    ];
    $this->drupalGet('admin/structure/types/manage/page');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->responseContains('The content type <em class="placeholder">Page</em> has been updated.');

    $spanish_node = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'Spanish Test Page Node',
      'path' => ['alias' => '/spanish-test-node'],
      'langcode' => 'es',
    ]);

    // Test multilingual redirect.
    $this->assertRedirect('es/node/' . $spanish_node->id(), 'es/spanish-test-node');
  }

  /**
   * Visits a path and asserts that it is a redirect.
   *
   * @param string $path
   *   The request path.
   * @param string $expected_destination
   *   The path where we expect it to redirect. If NULL value provided, no
   *   redirect is expected.
   * @param int $status_code
   *   The status we expect to get with the first request.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function assertRedirect($path, $expected_destination, $status_code = 301) {
    // Always just use getAbsolutePath() so that generating the link does not
    // alter special requests.
    $url = $this->getAbsoluteUrl($path);
    $this->getSession()->visit($url);

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();

    $assert_session = $this->assertSession();
    $assert_session->responseHeaderEquals('Location', $this->getAbsoluteUrl($expected_destination));
    $assert_session->statusCodeEquals($status_code);
  }

  /**
   * Visits a path and asserts that it is NOT a redirect.
   *
   * @param string $path
   *   The path to visit.
   * @param int $status_code
   *   (optional) The expected HTTP status code. Defaults to 200.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertNoRedirect($path, $status_code = 200) {
    $url = $this->getAbsoluteUrl($path);
    $this->getSession()->visit($url);

    $assert_session = $this->assertSession();
    $assert_session->statusCodeEquals($status_code);
    $assert_session->responseHeaderDoesNotExist('Location');
    $assert_session->responseNotContains('http-equiv="refresh');
    $assert_session->addressEquals($path);

    // Ensure that any changes to variables in the other thread are picked up.
    $this->refreshVariables();
  }

}
