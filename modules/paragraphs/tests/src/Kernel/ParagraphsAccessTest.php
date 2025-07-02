<?php

namespace Drupal\Tests\paragraphs\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\paragraphs\ParagraphAccessControlHandler
 * @group paragraphs
 */
class ParagraphsAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'paragraphs',
  ];

  /**
   * @covers ::checkCreateAccess
   *
   * @dataProvider createAccessTestCases
   */
  public function testCreateAccess($request_format, AccessResult $expected_result) {

    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens()->willReturn(TRUE);
    $cache_contexts_manager->reveal();
    $this->container->set('cache_contexts_manager', $cache_contexts_manager);

    $expected_result->addCacheContexts(['request_format']);

    $request = new Request();
    $request->setRequestFormat($request_format);
    $this->container->get('request_stack')->push($request);
    $result = $this->container->get('entity_type.manager')->getAccessControlHandler('paragraph')->createAccess(NULL, NULL, [], TRUE);
    $this->assertEquals($expected_result, $result);
    $this->container->get('request_stack')->pop();
  }

  /**
   * Test cases for ::testCreateAccess.
   */
  public static function createAccessTestCases() {
    return [
      'Allowed HTML request format' => [
        'html',
        AccessResult::allowed(),
      ],
      'Forbidden other formats' => [
        'json',
        AccessResult::neutral(),
      ],
    ];
  }

}
