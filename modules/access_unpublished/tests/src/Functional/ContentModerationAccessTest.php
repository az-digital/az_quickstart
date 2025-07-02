<?php

namespace Drupal\Tests\access_unpublished\Functional;

use Drupal\access_unpublished\Entity\AccessToken;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Tests the article creation.
 *
 * @group access_unpublished
 */
class ContentModerationAccessTest extends BrowserTestBase {

  use NodeCreationTrait;
  use ContentModerationTestTrait;

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
    'content_moderation',
  ];

  /**
   * The entity to test.
   *
   * @var \Drupal\node\Entity\Node
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

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();

    // Create an unpublished entity.
    $this->entity = $this->createNode();

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

    // Create a token for the entity.
    $validToken = AccessToken::create([
      'entity_type' => 'node',
      'entity_id' => $this->entity->id(),
      'value' => 'iAmValid',
      'expire' => \Drupal::time()->getRequestTime() + 10000,
    ]);
    $validToken->save();

    // Verify that entity is accessible, but only with the correct hash.
    $this->drupalGet($this->entity->toUrl('canonical'), ['query' => ['auHash' => 'iAmValid']]);
    $assert_session->statusCodeEquals(200);
    $this->drupalGet($this->entity->toUrl('canonical'), ['query' => ['auHash' => 123456]]);
    $assert_session->statusCodeEquals(403);
    $this->drupalGet($this->entity->toUrl());
    $assert_session->statusCodeEquals(403);

    $this->entity->set('moderation_state', 'published');
    $this->entity->save();

    $this->entity->set('moderation_state', 'draft');
    $this->entity->save();

    $this->drupalGet($this->entity->toUrl('latest-version'), ['query' => ['auHash' => 'iAmValid']]);
    $assert_session->statusCodeEquals(200);
    $this->drupalGet($this->entity->toUrl('latest-version'), ['query' => ['auHash' => 123456]]);
    $assert_session->statusCodeEquals(403);
    $this->drupalGet($this->entity->toUrl());
    $assert_session->statusCodeEquals(200);

    // Delete the token.
    $validToken->delete();

    // Verify that the entity is not accessible.
    $this->drupalGet($this->entity->toUrl('latest-version'), ['query' => ['auHash' => 'iAmValid']]);
    $assert_session->statusCodeEquals(403);
  }

}
