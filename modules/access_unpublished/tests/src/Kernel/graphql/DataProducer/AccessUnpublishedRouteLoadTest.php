<?php

namespace Drupal\Tests\access_unpublished\Kernel\graphql\DataProducer;

use Drupal\access_unpublished\Entity\AccessToken;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\graphql\Kernel\GraphQLTestBase;

/**
 * Data producers AccessUnpublishedRouteLoad test class.
 *
 * @group graphql
 * @legacy
 */
class AccessUnpublishedRouteLoadTest extends GraphQLTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['access_unpublished'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('access_token');
  }

  /**
   * @covers \Drupal\access_unpublished\Plugin\GraphQL\DataProducer\AccessUnpublishedRouteLoad::resolveUnpublished
   */
  public function testUnpublishedRouteLoad(): void {
    $node = Node::create([
      'title' => 'Unpublished',
      'type' => 'test',
      'status' => FALSE,
    ]);
    $node->save();

    $access_token = AccessToken::create([
      'entity_type' => 'node',
      'entity_id' => $node->id(),
      'expire' => -1,
    ]);
    $access_token->save();

    $result = $this->executeDataProducer('access_unpublished_route_load', [
      'path' => $node->toUrl()->toString(),
      'token' => $access_token->value->value,
    ]);

    $this->assertNotNull($result);
    $this->assertEquals('entity.node.canonical', $result->getRouteName());

    $this->markTestIncomplete(
      'Incomplete until https://www.drupal.org/project/drupal/issues/3180960 is fixed.'
    );

    $result = $this->executeDataProducer('access_unpublished_route_load', [
      'path' => $node->toUrl()->toString(),
    ]);
    $this->assertEmpty($result);
  }

  /**
   * {@inheritdoc}
   */
  protected function userPermissions(): array {
    NodeType::create([
      'type' => 'test',
      'name' => 'Test',
    ])->save();

    return array_merge(parent::userPermissions(), ['access_unpublished node test']);
  }

}
