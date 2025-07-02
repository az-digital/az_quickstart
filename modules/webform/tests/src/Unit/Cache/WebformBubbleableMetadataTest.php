<?php

namespace Drupal\Tests\webform\Unit\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\Cache\WebformBubbleableMetadata;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests webform bubbleable metadata.
 *
 * @coversDefaultClass \Drupal\webform\Cache\WebformBubbleableMetadata
 * @group Cache
 */
class WebformBubbleableMetadataTest extends UnitTestCase {

  /**
   * Tests appendTo renderable array.
   *
   * @param \Drupal\webform\Cache\WebformBubbleableMetadata $bubbleable_metadata
   *   Bubbleable metadata.
   * @param array $build
   *   A render array.
   * @param array $expected
   *   The expected render array.
   *
   * @covers ::appendTo
   * @dataProvider providerTestAppendTo
   * @see \Drupal\Tests\Core\Cache\CacheableMetadataTest
   */
  public function testAppendTo(WebformBubbleableMetadata $bubbleable_metadata, array $build, array $expected) {
    // Mock CacheContextsManager::assertValidTokens.
    // @see \Drupal\Core\Cache\Cache::mergeContexts
    $cache_contexts_manager = $this->createMock('Drupal\Core\Cache\Context\CacheContextsManager');
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);

    /* ********************************************************************** */

    $bubbleable_metadata->appendTo($build);
    $this->assertEqualsCanonicalizing($expected, $build);
  }

  /**
   * Provides test data for testAppendTo().
   *
   * @return array
   *   Test data
   */
  public function providerTestAppendTo() {
    return [
      [
        (new WebformBubbleableMetadata())->setCacheContexts(['bar']),
        [],
        ['#cache' => ['contexts' => ['bar'], 'tags' => [], 'max-age' => Cache::PERMANENT], '#attached' => []],
      ],
      [
        (new WebformBubbleableMetadata())->setCacheContexts(['bar']),
        ['#cache' => ['contexts' => ['bar']]],
        ['#cache' => ['contexts' => ['bar'], 'tags' => [], 'max-age' => Cache::PERMANENT], '#attached' => []],
      ],
      [
        (new WebformBubbleableMetadata())->setCacheContexts(['bar', 'foo']),
        ['#cache' => ['contexts' => ['bar']]],
        ['#cache' => ['contexts' => ['bar', 'foo'], 'tags' => [], 'max-age' => Cache::PERMANENT], '#attached' => []],
      ],
      [
        (new WebformBubbleableMetadata())->setCacheMaxAge(99),
        [],
        ['#cache' => ['contexts' => [], 'tags' => [], 'max-age' => 99], '#attached' => []],
      ],
      [
        (new WebformBubbleableMetadata())->setCacheContexts(['bar']),
        ['#cache' => ['max-age' => 99]],
        ['#cache' => ['contexts' => ['bar'], 'tags' => [], 'max-age' => 99], '#attached' => []],
      ],
    ];
  }

}
