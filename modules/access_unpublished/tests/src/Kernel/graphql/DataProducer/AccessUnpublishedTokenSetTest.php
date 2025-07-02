<?php

namespace Drupal\Tests\access_unpublished\Kernel\graphql\DataProducer;

use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * Data producers AccessUnpublishedTokenSet test class.
 *
 * @group graphql
 * @legacy
 */
class AccessUnpublishedTokenSetTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['access_unpublished'];

  /**
   * @covers \Drupal\access_unpublished\Plugin\GraphQL\DataProducer\AccessUnpublishedTokenSet::resolve
   */
  public function testUnpublishedRouteLoad(): void {
    $this->assertNull(\Drupal::service('access_unpublished.token_getter')->getToken());

    $this->executeDataProducer('access_unpublished_token_set', [
      'token' => 'TEST',
    ]);

    $this->assertEquals('TEST', \Drupal::service('access_unpublished.token_getter')->getToken());
  }

}
