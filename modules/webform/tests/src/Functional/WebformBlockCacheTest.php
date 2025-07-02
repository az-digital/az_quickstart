<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\node\Entity\Node;
use Drupal\webform\Entity\Webform;

/**
 * Test block caching.
 *
 * These tests proof that the webform block which
 * renders the webform as a block provides the correct
 * cache tags / cache contexts so that cacheability works.
 *
 * @group webform_browser
 */
class WebformBlockCacheTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'webform', 'page_cache', 'dynamic_page_cache', 'node'];

  /**
   * Authenticated user.
   *
   * @var \Drupal\user\Entity\User
   */
  private $authenticatedUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->authenticatedUser = $this->createUser([
      'access content',
    ]);

    $this->createContentType(['type' => 'page']);

    Node::create([
      'title' => $this->randomString(),
      'type' => 'page',
    ])->save();

    $this->drupalPlaceBlock('webform_block', [
      'webform_id' => 'contact',
      'region' => 'footer',
    ])->save();
  }

  /**
   * Test that an anonymous can visit the webform block and the page is cacheable.
   */
  public function testAnonymousVisitIsCacheable() {
    $session = $this->getSession();
    $assert_session = $this->assertSession();

    $this->drupalGet('/node/1');
    $assert_session->responseContains('Contact');
    $this->assertEquals('MISS', $session->getResponseHeader('X-Drupal-Cache'));
    $this->drupalGet('/node/1');
    $this->assertEquals('HIT', $session->getResponseHeader('X-Drupal-Cache'));
  }

  /**
   * Test that admin user can visit the page and the it is cacheable.
   */
  public function testAuthenticatedVisitIsCacheable() {
    $session = $this->getSession();
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->authenticatedUser);

    $this->drupalGet('/node/1');
    $assert_session->responseContains('Contact');
    $this->assertEquals('MISS', $session->getResponseHeader('X-Drupal-Dynamic-Cache'));
    $this->drupalGet('/node/1');
    $this->assertEquals('HIT', $session->getResponseHeader('X-Drupal-Dynamic-Cache'));
  }

  /**
   * Test that if an Webform is access restricted the page can still be cached.
   */
  public function testAuthenticatedAndRestrictedVisitIsCacheable() {
    $session = $this->getSession();
    $assert_session = $this->assertSession();

    /** @var \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager */
    $access_rules_manager = \Drupal::service('webform.access_rules_manager');
    $default_access_rules = $access_rules_manager->getDefaultAccessRules();

    $access_rules = [
      'create' => [
        'roles' => [],
        'users' => [],
        'permissions' => ['access content'],
      ],
    ] + $default_access_rules;

    Webform::load('contact')->setAccessRules($access_rules)->save();

    $this->drupalLogin($this->authenticatedUser);

    $this->drupalGet('/node/1');
    $assert_session->responseContains('Contact');
    $this->assertEquals('MISS', $session->getResponseHeader('X-Drupal-Dynamic-Cache'));
    $this->drupalGet('/node/1');
    $this->assertEquals('HIT', $session->getResponseHeader('X-Drupal-Dynamic-Cache'));
  }

}
