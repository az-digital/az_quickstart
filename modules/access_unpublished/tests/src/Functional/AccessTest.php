<?php

namespace Drupal\Tests\access_unpublished\Functional;

use Drupal\access_unpublished\Entity\AccessToken;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Tests the article creation.
 *
 * @group access_unpublished
 */
class AccessTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['access_unpublished', 'node'];

  /**
   * Node entity that is used in all tests.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    user_role_grant_permissions(RoleInterface::ANONYMOUS_ID, [
      'access content',
      'access_unpublished node page',
    ]);

    // Create an unpublished entity.
    $this->entity = $this->createNode(['status' => FALSE]);

    $assert_session = $this->assertSession();

    // Verify that the entity is not accessible.
    $this->drupalGet($this->entity->toUrl());
    $assert_session->statusCodeEquals(403);
  }

  /**
   * Checks entity access before and after token creation.
   */
  public function testAccessWithValidToken() {
    $assert_session = $this->assertSession();

    // Create tokens for the entity.
    $requestTime = \Drupal::time()->getRequestTime();
    $expiredToken = AccessToken::create([
      'entity_type' => 'node',
      'entity_id' => $this->entity->id(),
      'value' => 'iAmExpired',
      'expire' => $requestTime - 100,
    ]);
    $expiredToken->save();
    $validToken = AccessToken::create([
      'entity_type' => 'node',
      'entity_id' => $this->entity->id(),
      'value' => 'iAmValid',
      'expire' => $requestTime + 100,
    ]);
    $validToken->save();

    // Verify that entity is accessible, but only with the correct hash.
    $this->drupalGet($this->entity->toUrl('canonical'), ['query' => ['auHash' => 'iAmValid']]);
    $assert_session->statusCodeEquals(200);
    $this->drupalGet($this->entity->toUrl('canonical'), ['query' => ['auHash' => 123456]]);
    $assert_session->statusCodeEquals(403);
    $this->drupalGet($this->entity->toUrl());
    $assert_session->statusCodeEquals(403);

    // Delete the token.
    $validToken->delete();

    // Verify that the entity is not accessible.
    $this->drupalGet($this->entity->toUrl('canonical'), ['query' => ['auHash' => 'iAmValid']]);
    $assert_session->statusCodeEquals(403);
  }

  /**
   * Checks entity access before and after token creation.
   */
  public function testAccessWithExpiredToken() {
    $assert_session = $this->assertSession();

    // Create a token for the entity.
    $token = AccessToken::create([
      'entity_type' => 'node',
      'entity_id' => $this->entity->id(),
      'value' => '12345',
      'expire' => \Drupal::time()->getRequestTime() - 100,
    ]);
    $token->save();

    // Verify that entity is accessible, but only with the correct hash.
    $this->drupalGet($this->entity->toUrl('canonical'), ['query' => ['auHash' => 12345]]);
    $assert_session->statusCodeEquals(403);
  }

  /**
   * Checks entity access before and after token creation.
   */
  public function testAccessModifiedHeader() {
    $assert_session = $this->assertSession();

    // Create a token for the entity.
    $validToken = AccessToken::create([
      'entity_type' => 'node',
      'entity_id' => $this->entity->id(),
      'value' => 'iAmValid',
      'expire' => -1,
    ]);
    $validToken->save();

    $this->drupalGet($this->entity->toUrl(), ['query' => ['auHash' => 'iAmValid']]);
    $assert_session->statusCodeEquals(200);
    $assert_session->responseHeaderNotContains('X-Robots-Tag', 'noindex');

    \Drupal::configFactory()->getEditable('access_unpublished.settings')
      ->set('modify_http_headers', ['X-Robots-Tag' => 'noindex'])
      ->save();

    $this->drupalGet($this->entity->toUrl(), ['query' => ['auHash' => 'iAmValid']]);
    $assert_session->statusCodeEquals(200);
    $assert_session->responseHeaderContains('X-Robots-Tag', 'noindex');
  }

}
