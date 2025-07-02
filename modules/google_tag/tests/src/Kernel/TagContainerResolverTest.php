<?php

namespace Drupal\Tests\google_tag\Kernel;

use Drupal\google_tag\Entity\TagContainer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @coversDefaultClass \Drupal\google_tag\TagContainerResolver
 * @group google_tag
 */
final class TagContainerResolverTest extends GoogleTagTestCase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['google_tag_test', 'path_alias'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('path_alias');
  }

  /**
   * Tests google tag resolve per request.
   */
  public function testResolve(): void {
    $request1 = Request::create('/');
    $request2 = Request::create('/');
    $sut = $this->container->get('google_tag.tag_container_resolver');
    self::assertNull($sut->resolve());

    $config1 = TagContainer::create([
      'id' => 'foo',
      'weight' => 10,
    ]);
    $config1->save();
    // With respect to the change record https://www.drupal.org/node/3337193,
    // Add a mock session on the request before pushing it on the stack.
    if (version_compare(\Drupal::VERSION, '10.3', '>=')) {
      $request1->setSession(new Session(new MockArraySessionStorage()));
    }
    $this->container->get('request_stack')->push($request1);
    $resolved = $sut->resolve();
    self::assertNotNull($resolved);
    self::assertEquals($config1->id(), $resolved->id());

    $config2 = TagContainer::create([
      'id' => 'bar',
      'weight' => 5,
    ]);
    $config2->save();

    // With respect to the change record https://www.drupal.org/node/3337193,
    // Add a mock session on the request before pushing it on the stack.
    if (version_compare(\Drupal::VERSION, '10.3', '>=')) {
      $request2->setSession(new Session(new MockArraySessionStorage()));
    }
    $this->container->get('request_stack')->push($request2);
    $resolved = $sut->resolve();
    self::assertNotNull($resolved);
    self::assertEquals($config2->id(), $resolved->id());
  }

  /**
   * Tests resolving with conditions set.
   */
  public function testResolveWithConditions(): void {
    $config = TagContainer::create([
      'id' => 'foo',
      'weight' => 10,
      'events' => [
        'route_name' => [],
      ],
      'conditions' => [
        'request_path' => [
          'id' => 'request_path',
          'negate' => FALSE,
          'pages' => '<front>',
        ],
      ],
    ]);
    $config->save();
    $request1 = Request::create('/');
    $this->doRequest($request1);
    $this->assertGoogleTagEvents([
      [
        'name' => 'route_name',
        'data' => [
          'route_name' => 'user.login',
        ],
      ],
    ]);
    $request2 = Request::create('/foo');
    $this->doRequest($request2);
    $this->assertGoogleTagEvents([]);
  }

}
