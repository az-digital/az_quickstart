<?php

namespace Drupal\Tests\config_ignore\Unit;

use Drupal\config_ignore\ConfigIgnoreConfig;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the config ignore pattern resolver.
 *
 * @coversDefaultClass \Drupal\config_ignore\EventSubscriber\ConfigIgnoreEventSubscriber
 *
 * @group config_ignore
 */
class ConfigIgnorePatternResolverTest extends UnitTestCase {

  /**
   * Tests the config ignore pattern resolver.
   *
   * @covers ::getIgnoredConfigs
   */
  public function testGetIgnoredConfigs() {
    $ignoredConfigs = $this->getIgnoredConfigs(
      // Ignored config patterns.
      [
        // Non-existing simple ignore pattern.
        'non.existing',
        // Simple ignore pattern.
        'foo.bar',
        // Suffix wildcard ignore pattern.
        'foo.bar.*',
        // Excluding foo.bar.suffix4.
        '~foo.bar.suffix4',
        // Excluding with a wildcard.
        '~foo.bar.suffix-*',
        // Prefix wildcard ignore pattern.
        '*.foo.bar',
        // Excluding prefix2.foo.bar.
        '~prefix2.foo.bar',
        // Middle wildcard ignore pattern.
        'foo.*.bar',
        // Excluding foo.middle1.bar.
        '~foo.middle1.bar',
        // Ignore pattern with key.
        'foo.baz.qux:path.to.key',
        // A 2nd key of the same config is appended and sorted.
        'foo.baz.qux:a.second.*.key',
        // A 3rd key of the same config is appended and sorted.
        '~foo.baz.qux:not.a.*.key',
        // Ignore pattern with key when the same config has been already added.
        'foo.bar:some.key',
        // Ignore pattern with key that will be overwritten later with the same
        // config but without key.
        'baz.qux:with.some.key',
        // Only this will be outputted as it covers also the one with a key.
        'baz.qux',
      ],
      // All configs.
      [
        'foo.bar',
        'foo.bar.suffix1',
        'foo.bar.suffix2',
        'foo.bar.suffix3',
        'foo.bar.suffix4',
        'foo.bar.suffix-other',
        'prefix1.foo.bar',
        'prefix2.foo.bar',
        'prefix3.foo.bar',
        'foo.middle1.bar',
        'foo.middle2.bar',
        'foo.middle3.bar',
        'foo.baz.qux',
        'baz.qux',
      ]
    );

    $this->assertSame([
      'foo.bar' => TRUE,
      'foo.bar.suffix1' => TRUE,
      'foo.bar.suffix2' => TRUE,
      'foo.bar.suffix3' => TRUE,
      'prefix1.foo.bar' => TRUE,
      'prefix3.foo.bar' => TRUE,
      'foo.middle2.bar' => TRUE,
      'foo.middle3.bar' => TRUE,
      'foo.baz.qux' => [
        '~not.a.*.key',
        'a.second.*.key',
        'path.to.key',
      ],
      'baz.qux' => TRUE,
    ], $ignoredConfigs);
  }

  /**
   * Returns all ignored configs by expanding the wildcards.
   *
   * Basically, it provides mocked services and it's a wrapper around the
   * protected method ConfigIgnoreEventSubscriber::getIgnoredConfigs().
   *
   * @param array $ignore_config_patterns
   *   A list of config ignore patterns.
   * @param array $all_configs
   *   A list of names of all configs.
   *
   * @return array
   *   A list of ignored configs as is returned by
   *   ConfigIgnoreEventSubscriber::getIgnoredConfigs()
   *
   * @see \Drupal\config_ignore\EventSubscriber\ConfigIgnoreEventSubscriber::getIgnoredConfigs()
   */
  protected function getIgnoredConfigs(array $ignore_config_patterns, array $all_configs) {
    $config = new ConfigIgnoreConfig('simple', $ignore_config_patterns);
    $returned = [];
    foreach ($all_configs as $name) {
      $returned[$name] = $config->isIgnored('', $name, 'import', 'update');
    }

    return array_filter($returned);
  }

}
