<?php

namespace Drupal\Tests\access_unpublished\Kernel;

use Drupal\access_unpublished\Entity\AccessToken;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test various token use cases.
 *
 * @group access_unpublished
 */
class TokenTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['access_unpublished', 'entity_test', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('access_token');
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_mulrevpub');
  }

  /**
   * Tests that tokens are deleted if associated entity gets deleted.
   */
  public function testTokensDeletedWithEntity() {
    $entity1 = EntityTestMulRevPub::create([
      'title' => 'Foo',
      'status' => 0,
    ]);
    $entity1->save();
    $entity2 = EntityTestMulRevPub::create([
      'title' => 'Foo',
      'status' => 0,
    ]);
    $entity2->save();

    // Create tokens for the entity.
    $requestTime = \Drupal::time()->getRequestTime();
    $token1 = AccessToken::create([
      'entity_type' => 'entity_test_mulrevpub',
      'entity_id' => $entity1->id(),
      'value' => 'iAmExpired',
      'expire' => $requestTime - 100,
    ]);
    $token1->save();
    $token2 = AccessToken::create([
      'entity_type' => 'entity_test_mulrevpub',
      'entity_id' => $entity1->id(),
      'value' => 'iAmValid',
      'expire' => $requestTime + 100,
    ]);
    $token2->save();
    $token3 = AccessToken::create([
      'entity_type' => 'entity_test_mulrevpub',
      'entity_id' => $entity2->id(),
      'value' => 'iAmValid',
      'expire' => $requestTime + 100,
    ]);
    $token3->save();

    $entity1->delete();

    $this->assertNull(AccessToken::load($token1->id()));
    $this->assertNull(AccessToken::load($token2->id()));
    $this->assertInstanceOf(AccessToken::class, AccessToken::load($token3->id()));
  }

  /**
   * @covers \Drupal\access_unpublished\AccessTokenManager::getAccessTokensByEntity
   */
  public function testGetAccessTokensByEntity() {
    $entity1 = EntityTestMulRevPub::create([
      'title' => 'Foo',
      'status' => 0,
    ]);
    $entity1->save();
    $entity2 = EntityTestMulRevPub::create([
      'title' => 'Foo',
      'status' => 0,
    ]);
    $entity2->save();

    // Create tokens for the entity.
    $requestTime = \Drupal::time()->getRequestTime();
    $token1 = AccessToken::create([
      'entity_type' => 'entity_test_mulrevpub',
      'entity_id' => $entity1->id(),
      'value' => 'iAmExpired',
      'expire' => $requestTime - 100,
    ]);
    $token1->save();
    $token2 = AccessToken::create([
      'entity_type' => 'entity_test_mulrevpub',
      'entity_id' => $entity1->id(),
      'value' => 'iAmValid',
      'expire' => $requestTime + 100,
    ]);
    $token2->save();
    $token3 = AccessToken::create([
      'entity_type' => 'entity_test_mulrevpub',
      'entity_id' => $entity2->id(),
      'value' => 'iAmExpired',
      'expire' => $requestTime - 100,
    ]);
    $token3->save();
    $token4 = AccessToken::create([
      'entity_type' => 'entity_test_mulrevpub',
      'entity_id' => $entity2->id(),
      'value' => 'iAmValid',
      'expire' => $requestTime + 100,
    ]);
    $token4->save();

    /** @var \Drupal\access_unpublished\AccessTokenManager $manager */
    $manager = \Drupal::service('access_unpublished.access_token_manager');

    $tokens = $manager->getAccessTokensByEntity($entity1);
    $uuids = array_map(function (AccessToken $token) {
      return $token->uuid();
    }, $tokens);

    $this->assertContains($token1->uuid(), $uuids);
    $this->assertContains($token2->uuid(), $uuids);
    $this->assertNotContains($token3->uuid(), $uuids);
    $this->assertNotContains($token4->uuid(), $uuids);

    $tokens = $manager->getAccessTokensByEntity($entity1, 'active');
    $uuids = array_map(function (AccessToken $token) {
      return $token->uuid();
    }, $tokens);

    $this->assertNotContains($token1->uuid(), $uuids);
    $this->assertContains($token2->uuid(), $uuids);
    $this->assertNotContains($token3->uuid(), $uuids);
    $this->assertNotContains($token4->uuid(), $uuids);

    $tokens = $manager->getAccessTokensByEntity($entity1, 'expired');
    $uuids = array_map(function (AccessToken $token) {
      return $token->uuid();
    }, $tokens);

    $this->assertContains($token1->uuid(), $uuids);
    $this->assertNotContains($token2->uuid(), $uuids);
    $this->assertNotContains($token3->uuid(), $uuids);
    $this->assertNotContains($token4->uuid(), $uuids);
  }

  /**
   * @covers \Drupal\access_unpublished\AccessTokenManager::getAccessTokens
   */
  public function testGetAccessTokens() {
    $entity1 = EntityTestMulRevPub::create([
      'title' => 'Foo',
      'status' => 0,
    ]);
    $entity1->save();
    $entity2 = EntityTestMulRevPub::create([
      'title' => 'Foo',
      'status' => 0,
    ]);
    $entity2->save();

    // Create tokens for the entity.
    $requestTime = \Drupal::time()->getRequestTime();
    $token1 = AccessToken::create([
      'entity_type' => 'entity_test_mulrevpub',
      'entity_id' => $entity1->id(),
      'value' => 'iAmExpired',
      'expire' => $requestTime - 100,
    ]);
    $token1->save();
    $token2 = AccessToken::create([
      'entity_type' => 'entity_test_mulrevpub',
      'entity_id' => $entity1->id(),
      'value' => 'iAmValid',
      'expire' => $requestTime + 100,
    ]);
    $token2->save();
    $token3 = AccessToken::create([
      'entity_type' => 'entity_test_mulrevpub',
      'entity_id' => $entity2->id(),
      'value' => 'iAmExpired',
      'expire' => $requestTime - 100,
    ]);
    $token3->save();
    $token4 = AccessToken::create([
      'entity_type' => 'entity_test_mulrevpub',
      'entity_id' => $entity2->id(),
      'value' => 'iAmValid',
      'expire' => $requestTime + 100,
    ]);
    $token4->save();
    $token5 = AccessToken::create([
      'entity_type' => 'entity_test_mulrevpub',
      'entity_id' => $entity1->id(),
      'value' => 'iDontExpire',
      'expire' => -1,
    ]);
    $token5->save();

    /** @var \Drupal\access_unpublished\AccessTokenManager $manager */
    $manager = \Drupal::service('access_unpublished.access_token_manager');

    $tokens = $manager->getAccessTokens($entity1);
    $uuids = array_map(function (AccessToken $token) {
      return $token->uuid();
    }, $tokens);

    $this->assertContains($token1->uuid(), $uuids);
    $this->assertContains($token2->uuid(), $uuids);
    $this->assertContains($token3->uuid(), $uuids);
    $this->assertContains($token4->uuid(), $uuids);
    $this->assertContains($token5->uuid(), $uuids);

    $tokens = $manager->getAccessTokens('active');
    $uuids = array_map(function (AccessToken $token) {
      return $token->uuid();
    }, $tokens);

    $this->assertNotContains($token1->uuid(), $uuids);
    $this->assertContains($token2->uuid(), $uuids);
    $this->assertNotContains($token3->uuid(), $uuids);
    $this->assertContains($token4->uuid(), $uuids);

    $tokens = $manager->getAccessTokens('expired');
    $uuids = array_map(function (AccessToken $token) {
      return $token->uuid();
    }, $tokens);

    $this->assertContains($token1->uuid(), $uuids);
    $this->assertNotContains($token2->uuid(), $uuids);
    $this->assertContains($token3->uuid(), $uuids);
    $this->assertNotContains($token4->uuid(), $uuids);
    $this->assertNotContains($token5->uuid(), $uuids);
  }

}
