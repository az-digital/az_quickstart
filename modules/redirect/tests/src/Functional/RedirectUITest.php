<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect\Functional;

use Drupal\Core\Language\Language;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\BrowserTestBase;

/**
 * UI tests for redirect module.
 *
 * @group redirect
 */
class RedirectUITest extends BrowserTestBase {

  use AssertRedirectTrait;

  /**
   * The admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * The redirect repository.
   *
   * @var \Drupal\redirect\RedirectRepository
   */
  protected $repository;

  /**
   * The Sql conntent entity storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $storage;

  /**
   * The maximum redirects.
   *
   * @var int
   */
  public $maximumRedirects;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'redirect',
    'node',
    'path',
    'dblog',
    'views',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->adminUser = $this->drupalCreateUser([
      'administer redirects',
      'administer redirect settings',
      'access content',
      'bypass node access',
      'create url aliases',
      'administer taxonomy',
      'administer url aliases',
    ]);

    $this->repository = \Drupal::service('redirect.repository');

    $this->storage = \Drupal::entityTypeManager()->getStorage('redirect');
  }

  /**
   * Tests redirects being automatically created upon path alias change.
   */
  public function testAutomaticRedirects() {
    $this->drupalLogin($this->adminUser);

    // Create a node and update its path alias which should result in a redirect
    // being automatically created from the old alias to the new one.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'path' => ['alias' => '/node_test_alias'],
    ]);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->pageTextContains('No URL redirects available.');
    $this->submitForm(['path[0][alias]' => '/node_test_alias_updated'], 'Save');

    $redirect = $this->repository->findMatchingRedirect('node_test_alias', [], Language::LANGCODE_NOT_SPECIFIED);
    $this->assertEquals(Url::fromUri('base:node_test_alias_updated')->toString(), $redirect->getRedirectUrl()->toString());
    // Test if the automatically created redirect works.
    $this->assertRedirect('node_test_alias', 'node_test_alias_updated');

    // Test that changing the path back deletes the first redirect, creates
    // a new one and does not result in a loop.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm(['path[0][alias]' => '/node_test_alias'], 'Save');
    $redirect = $this->repository->findMatchingRedirect('node_test_alias', [], Language::LANGCODE_NOT_SPECIFIED);
    $this->assertTrue(empty($redirect));

    \Drupal::service('path_alias.manager')->cacheClear();
    $redirect = $this->repository->findMatchingRedirect('node_test_alias_updated', [], Language::LANGCODE_NOT_SPECIFIED);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->pageTextContains($redirect->getSourcePathWithQuery());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('entity.redirect.edit_form', ['redirect' => $redirect->id()])->toString());
    $this->assertSession()->linkByHrefExists(Url::fromRoute('entity.redirect.delete_form', ['redirect' => $redirect->id()])->toString());

    $this->assertEquals(Url::fromUri('base:node_test_alias')->toString(), $redirect->getRedirectUrl()->toString());
    // Test if the automatically created redirect works.
    $this->assertRedirect('node_test_alias_updated', 'node_test_alias');

    // Test that the redirect will be deleted upon node deletion.
    $this->drupalGet('node/' . $node->id() . '/delete');
    $this->submitForm([], 'Delete');
    $redirect = $this->repository->findMatchingRedirect('node_test_alias_updated', [], Language::LANGCODE_NOT_SPECIFIED);
    $this->assertTrue(empty($redirect));

    // Create a term and update its path alias and check if we have a redirect
    // from the previous path alias to the new one.
    $term = $this->createTerm($this->createVocabulary());
    $this->drupalGet('taxonomy/term/' . $term->id() . '/edit');
    $this->submitForm(['path[0][alias]' => '/term_test_alias_updated'], 'Save');
    $redirect = $this->repository->findMatchingRedirect('term_test_alias');
    $this->assertEquals(Url::fromUri('base:term_test_alias_updated')->toString(), $redirect->getRedirectUrl()->toString());
    // Test if the automatically created redirect works.
    $this->assertRedirect('term_test_alias', 'term_test_alias_updated');

    if (version_compare(\Drupal::VERSION, '8.8', '>=')) {
      $path_field = 'path[0][value]';
      $alias_field = 'alias[0][value]';
    }
    else {
      $path_field = 'source';
      $alias_field = 'alias';
    }

    // Test the path alias update via the admin path form.
    $this->drupalGet('admin/config/search/path/add');
    $this->submitForm([
      $path_field => '/node',
      $alias_field => '/aaa_path_alias',
    ], 'Save');
    // Note that here we rely on fact that we land on the path alias list page
    // and the default sort is by the alias, which implies that the first edit
    // link leads to the edit page of the aaa_path_alias.
    $this->clickLink('Edit');
    $this->submitForm([$alias_field => '/aaa_path_alias_updated'], 'Save');
    $redirect = $this->repository->findMatchingRedirect('aaa_path_alias', [], 'en');
    $this->assertEquals(Url::fromUri('base:aaa_path_alias_updated')->toString(), $redirect->getRedirectUrl()->toString());
    // Test if the automatically created redirect works.
    $this->assertRedirect('aaa_path_alias', 'aaa_path_alias_updated');

    // Test the automatically created redirect shows up in the form correctly.
    $this->drupalGet('admin/config/search/redirect/edit/' . $redirect->id());
    $this->assertSession()->fieldValueEquals('redirect_source[0][path]', 'aaa_path_alias');
    $this->assertSession()->fieldValueEquals('redirect_redirect[0][uri]', '/node');
  }

  /**
   * Test the redirect loop protection and logging.
   */
  public function testRedirectLoop() {
    // Redirect loop redirection only works when page caching is disabled.
    \Drupal::service('module_installer')->uninstall(['page_cache']);

    /** @var \Drupal\redirect\Entity\Redirect $redirect1 */
    $redirect1 = $this->storage->create();
    $redirect1->setSource('node');
    $redirect1->setRedirect('admin');
    $redirect1->setStatusCode(301);
    $redirect1->save();

    /** @var \Drupal\redirect\Entity\Redirect $redirect2 */
    $redirect2 = $this->storage->create();
    $redirect2->setSource('admin');
    $redirect2->setRedirect('node');
    $redirect2->setStatusCode(301);
    $redirect2->save();

    $this->maximumRedirects = 10;
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains('Service unavailable');
    $this->assertSession()->statusCodeEquals(503);

    $log = \Drupal::database()->select('watchdog')->fields('watchdog')->condition('type', 'redirect')->execute()->fetchAll();
    if (count($log) == 0) {
      $this->fail('Redirect loop has not been logged');
    }
    else {
      $log = reset($log);
      $this->assertEquals(RfcLogLevel::WARNING, $log->severity);
      $this->assertEquals('Redirect loop identified at %path for redirect %rid', $log->message);
      $this->assertEquals(['%path' => '/node', '%rid' => $redirect1->id()], unserialize($log->variables));
    }
  }

  /**
   * Returns a new vocabulary with random properties.
   */
  public function createVocabulary() {
    // Create a vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => $this->randomMachineName(),
      'description' => $this->randomMachineName(),
      'vid' => mb_strtolower($this->randomMachineName()),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'weight' => mt_rand(0, 10),
    ]);
    $vocabulary->save();
    return $vocabulary;
  }

  /**
   * Returns a new term with random properties in vocabulary $vid.
   */
  public function createTerm($vocabulary) {
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);
    $term = Term::create([
      'name' => $this->randomMachineName(),
      'description' => [
        'value' => $this->randomMachineName(),
        // Use the first available text format.
        'format' => $format->id(),
      ],
      'vid' => $vocabulary->id(),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'path' => ['alias' => '/term_test_alias'],
    ]);
    $term->save();
    return $term;
  }

  /**
   * Test cache tags.
   *
   * @todo Not sure this belongs in a UI test, but a full web test is needed.
   */
  public function testCacheTags() {
    /** @var \Drupal\redirect\Entity\Redirect $redirect1 */
    $redirect1 = $this->storage->create();
    $redirect1->setSource('test-redirect');
    $redirect1->setRedirect('node');
    $redirect1->setStatusCode(301);
    $redirect1->save();

    $response = $this->assertRedirect('test-redirect', 'node');
    // Note, self::assertCacheTag() cannot be used here since it only looks at
    // the final set of headers.
    $expected = 'http_response ' . implode(' ', $redirect1->getCacheTags());
    $this->assertEquals($expected, $response->getHeader('x-drupal-cache-tags')[0], 'Redirect cache tags properly set.');

    // First request should be a cache MISS.
    $this->assertEquals('MISS', $response->getHeader('x-drupal-cache')[0], 'First request to the redirect was not cached.');

    // Second request should be cached.
    $response = $this->assertRedirect('test-redirect', 'node');
    $this->assertEquals('HIT', $response->getHeader('x-drupal-cache')[0], 'The second request to the redirect was cached.');

    // Ensure that the redirect has been cleared from cache when deleted.
    $redirect1->delete();
    $this->drupalGet('test-redirect');
    $this->assertSession()->statusCodeEquals(404);
  }

  /**
   * Test external destinations.
   */
  public function testExternal() {
    $redirect = $this->storage->create();
    $redirect->setSource('a-path');
    // @todo Redirect::setRedirect() assumes that all redirects are internal.
    $redirect->redirect_redirect->set(0, ['uri' => 'https://www.example.org']);
    $redirect->setStatusCode(301);
    $redirect->save();
    $this->assertRedirect('a-path', 'https://www.example.org');
    $this->drupalLogin($this->adminUser);
  }

}
