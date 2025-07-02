<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\redirect\Entity\Redirect;
use Drupal\Core\Language\Language;
use Drupal\redirect\Exception\RedirectLoopException;
use Drupal\KernelTests\KernelTestBase;

/**
 * Redirect entity and redirect API test coverage.
 *
 * @group redirect
 */
class RedirectAPITest extends KernelTestBase {

  /**
   * The redirect storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['redirect', 'link', 'field', 'system', 'user', 'language', 'views', 'path_alias'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('redirect');
    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');
    $this->installConfig(['redirect']);

    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();

    $this->storage = $this->container->get('entity_type.manager')->getStorage('redirect');
  }

  /**
   * Test redirect entity logic.
   */
  public function testRedirectEntity() {
    // Create a redirect and test if hash has been generated correctly.
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->storage->create();
    $redirect->setSource('some-url', ['key' => 'val']);
    $redirect->setRedirect('node');

    $redirect->save();
    $this->assertEquals(Redirect::generateHash('some-url', ['key' => 'val'], Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());
    // Update the redirect source query and check if hash has been updated as
    // expected.
    $redirect->setSource('some-url', ['key1' => 'val1']);
    $redirect->save();
    $this->assertEquals(Redirect::generateHash('some-url', ['key1' => 'val1'], Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());
    // Update the redirect source path and check if hash has been updated as
    // expected.
    $redirect->setSource('another-url', ['key1' => 'val1']);
    $redirect->save();
    $this->assertEquals(Redirect::generateHash('another-url', ['key1' => 'val1'], Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());
    // Update the redirect language and check if hash has been updated as
    // expected.
    $redirect->setLanguage('de');
    $redirect->save();
    $this->assertEquals(Redirect::generateHash('another-url', ['key1' => 'val1'], 'de'), $redirect->getHash());
    // Create a few more redirects to test the select.
    for ($i = 0; $i < 5; $i++) {
      $redirect = $this->storage->create();
      $redirect->setSource($this->randomMachineName());
      $redirect->save();
    }
    /** @var \Drupal\redirect\RedirectRepository $repository */
    $repository = \Drupal::service('redirect.repository');
    $redirect = $repository->findMatchingRedirect('another-url', ['key1' => 'val1'], 'de');
    if (!empty($redirect)) {
      $this->assertEquals($redirect->getSourceUrl(), '/another-url?key1=val1');
    }
    else {
      $this->fail('Failed to find matching redirect.');
    }

    // Load the redirect based on url.
    $redirects = $repository->findBySourcePath('another-url');
    $redirect = array_shift($redirects);
    if (!empty($redirect)) {
      $this->assertEquals($redirect->getSourceUrl(), '/another-url?key1=val1');
    }
    else {
      $this->fail('Failed to find redirect by source path.');
    }

    // Test passthrough_querystring.
    $redirect = $this->storage->create();
    $redirect->setSource('a-different-url');
    $redirect->setRedirect('node');
    $redirect->save();
    $redirect = $repository->findMatchingRedirect('a-different-url', ['key1' => 'val1'], 'de');
    if (!empty($redirect)) {
      $this->assertEquals($redirect->getSourceUrl(), '/a-different-url');
    }
    else {
      $this->fail('Failed to find redirect by source path with query string.');
    }

    // Add another redirect to the same path, with a query. This should always
    // be found before the source without a query set.
    /** @var \Drupal\redirect\Entity\Redirect $new_redirect */
    $new_redirect = $this->storage->create();
    $new_redirect->setSource('a-different-url', ['foo' => 'bar']);
    $new_redirect->setRedirect('node');
    $new_redirect->save();
    $found = $repository->findMatchingRedirect('a-different-url', ['foo' => 'bar'], 'de');
    if (!empty($found)) {
      $this->assertEquals($found->getSourceUrl(), '/a-different-url?foo=bar');
    }
    else {
      $this->fail('Failed to find a redirect by source path with query string.');
    }

    // Add a redirect to an external URL.
    $external_redirect = $this->storage->create();
    $external_redirect->setSource('google');
    $external_redirect->setRedirect('https://google.com');
    $external_redirect->save();
    $found = $repository->findMatchingRedirect('google');
    if (!empty($found)) {
      $this->assertEquals($found->getRedirectUrl()->toString(), 'https://google.com');
    }
    else {
      $this->fail('Failed to find a redirect for google.');
    }

    // Hashes should be case-insensitive since the source paths are.
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->storage->create();
    $redirect->setSource('Case-Sensitive-Path');
    $redirect->setRedirect('node');
    $redirect->save();
    $found = $repository->findBySourcePath('case-sensitive-path');
    if (!empty($found)) {
      $found = reset($found);
      $this->assertEquals($found->getSourceUrl(), '/Case-Sensitive-Path');
    }
    else {
      $this->fail('findBySourcePath is case sensitive');
    }
    $found = $repository->findMatchingRedirect('case-sensitive-path');
    if (!empty($found)) {
      $this->assertEquals($found->getSourceUrl(), '/Case-Sensitive-Path');
    }
    else {
      $this->fail('findMatchingRedirect is case sensitive.');
    }
  }

  /**
   * Test slash is removed from source path in findMatchingRedirect.
   */
  public function testDuplicateRedirectEntry() {
    $redirect = $this->storage->create();
    // The trailing slash should be removed on pre-save.
    $redirect->setSource('/foo/foo/', []);
    $redirect->setRedirect('foo');
    $redirect->save();

    $redirect_repository = \Drupal::service('redirect.repository');
    $matched_redirect = $redirect_repository->findMatchingRedirect('/foo/foo', [], 'en-AU');
    $this->assertNotNull($matched_redirect);

    $null_redirect = $redirect_repository->findMatchingRedirect('/foo/foo-bar', [], 'en-AU');
    $this->assertNull($null_redirect);
  }

  /**
   * Test redirect_sort_recursive().
   */
  public function testSortRecursive() {
    $test_cases = [
      [
        'input' => ['b' => 'aa', 'c' => ['c2' => 'aa', 'c1' => 'aa'], 'a' => 'aa'],
        'expected' => ['a' => 'aa', 'b' => 'aa', 'c' => ['c1' => 'aa', 'c2' => 'aa']],
        'callback' => 'ksort',
      ],
    ];
    foreach ($test_cases as $index => $test_case) {
      $output = $test_case['input'];
      redirect_sort_recursive($output, $test_case['callback']);
      $this->assertSame($test_case['expected'], $output);
    }
  }

  /**
   * Test loop detection.
   */
  public function testLoopDetection() {
    // Add a chained redirect that isn't a loop.
    /** @var \Drupal\redirect\Entity\Redirect $one */
    $one = $this->storage->create();
    $one->setSource('my-path');
    $one->setRedirect('node');
    $one->save();
    /** @var \Drupal\redirect\Entity\Redirect $two */
    $two = $this->storage->create();
    $two->setSource('second-path');
    $two->setRedirect('my-path');
    $two->save();
    /** @var \Drupal\redirect\Entity\Redirect $three */
    $three = $this->storage->create();
    $three->setSource('third-path');
    $three->setRedirect('second-path');
    $three->save();

    /** @var \Drupal\redirect\RedirectRepository $repository */
    $repository = \Drupal::service('redirect.repository');
    $found = $repository->findMatchingRedirect('third-path');
    if (!empty($found)) {
      $this->assertEquals($found->getRedirectUrl()->toString(), '/node', 'Chained redirects properly resolved in findMatchingRedirect.');
    }
    else {
      $this->fail('Failed to resolve a chained redirect.');
    }

    // Create a loop.
    $one->setRedirect('third-path');
    $one->save();
    try {
      $repository->findMatchingRedirect('third-path');
      $this->fail('Failed to detect a redirect loop.');
    }
    catch (RedirectLoopException $e) {
    }
  }

  /**
   * Test loop detection reset.
   */
  public function testLoopDetectionReset() {
    // Add a chained redirect that isn't a loop.
    /** @var \Drupal\redirect\Entity\Redirect $source */
    $source = $this->storage->create();
    $source->setSource('source-redirect');
    $source->setRedirect('target');
    $source->save();

    /** @var \Drupal\redirect\Entity\Redirect $target */
    $target = $this->storage->create();
    $target->setSource('target');
    $target->setRedirect('second-target');
    $target->save();

    /** @var \Drupal\redirect\RedirectRepository $repository */
    $repository = \Drupal::service('redirect.repository');
    $found = $repository->findMatchingRedirect('target');
    $this->assertEquals($target->id(), $found->id());

    $found = $repository->findMatchingRedirect('source-redirect');
    $this->assertEquals($target->id(), $found->id());
  }

  /**
   * Test multilingual redirects.
   */
  public function testMultilanguageCases() {
    // Add a redirect for english.
    /** @var \Drupal\redirect\Entity\Redirect $en_redirect */
    $en_redirect = $this->storage->create();
    $en_redirect->setSource('langpath');
    $en_redirect->setRedirect('/about');
    $en_redirect->setLanguage('en');
    $en_redirect->save();

    // Add a redirect for germany.
    /** @var \Drupal\redirect\Entity\Redirect $en_redirect */
    $en_redirect = $this->storage->create();
    $en_redirect->setSource('langpath');
    $en_redirect->setRedirect('node');
    $en_redirect->setLanguage('de');
    $en_redirect->save();

    // Check redirect for english.
    /** @var \Drupal\redirect\RedirectRepository $repository */
    $repository = \Drupal::service('redirect.repository');

    $found = $repository->findBySourcePath('langpath');
    if (!empty($found)) {
      $this->assertEquals($found[1]->getRedirectUrl()->toString(), '/about', 'Multilingual redirect resolved properly.');
      $this->assertEquals($found[1]->get('language')[0]->value, 'en', 'Multilingual redirect resolved properly.');
    }
    else {
      $this->fail('Failed to resolve the multilingual redirect.');
    }

    // Check redirect for germany.
    \Drupal::configFactory()->getEditable('system.site')->set('default_langcode', 'de')->save();
    /** @var \Drupal\redirect\RedirectRepository $repository */
    $repository = \Drupal::service('redirect.repository');
    $found = $repository->findBySourcePath('langpath');
    if (!empty($found)) {
      $this->assertEquals($found[2]->getRedirectUrl()->toString(), '/node', 'Multilingual redirect resolved properly.');
      $this->assertEquals($found[2]->get('language')[0]->value, 'de', 'Multilingual redirect resolved properly.');
    }
    else {
      $this->fail('Failed to resolve the multilingual redirect.');
    }
  }

}
