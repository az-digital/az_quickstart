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
class JsonApiAccessTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'access_unpublished',
    'node',
    'jsonapi',
  ];

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
    $this->rebuildAll();

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
   * Checks entity access with and without token.
   */
  public function testJsonApiAccess() {
    $assert_session = $this->assertSession();

    // Create tokens for the entity.
    $requestTime = \Drupal::time()->getRequestTime();
    $validToken = AccessToken::create([
      'entity_type' => 'node',
      'entity_id' => $this->entity->id(),
      'value' => 'iAmValid',
      'expire' => $requestTime + 100,
    ]);
    $validToken->save();

    $this->drupalGet('/jsonapi/node/page/' . $this->entity->uuid(), ['query' => ['auHash' => 'iAmValid']]);
    $assert_session->statusCodeEquals(200);

    $this->drupalGet('/jsonapi/node/page/' . $this->entity->uuid());
    $assert_session->statusCodeEquals(403);

    $this->drupalGet('/jsonapi/node/page/' . $this->entity->uuid(), ['query' => ['auHash' => 'iAmValid']]);
    $assert_session->statusCodeEquals(200);
  }

}
