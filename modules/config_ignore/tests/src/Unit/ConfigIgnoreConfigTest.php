<?php

namespace Drupal\Tests\config_ignore\Unit;

use Drupal\config_ignore\ConfigIgnoreConfig;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the config ignore config object.
 *
 * @coversDefaultClass \Drupal\config_ignore\ConfigIgnoreConfig
 *
 * @group config_ignore
 */
class ConfigIgnoreConfigTest extends UnitTestCase {

  /**
   * Shortcut to pattern matching test with collections.
   *
   * @param array $pattern
   *   The simple pattern.
   * @param string $collection
   *   The collection to test with.
   * @param string $name
   *   The config name.
   * @param bool|array $expected
   *   The expected result of what is ignored.
   *
   * @dataProvider ignorePatternProvider
   */
  public function testIgnorePatternMatching(array $pattern, string $collection, string $name, bool|array $expected): void {
    $config = new ConfigIgnoreConfig('simple', $pattern);
    self::assertSame($expected, $config->isIgnored($collection, $name, 'import', 'update'));
  }

  /**
   * Data provider for simple pattern matching test.
   *
   * @return \Generator
   *   The patterns.
   */
  public function ignorePatternProvider() {
    yield 'direct' => [['hello'], $this->randomMachineName(), 'hello', TRUE];
    yield 'direct not' => [['hello'], $this->randomMachineName(), 'world', FALSE];

    // Test some patterns with collections.
    $pattern = ['a', 'b|c', 'd*|*', 'de|~f*', '~de|g*'];
    yield 'a' => [$pattern, $this->randomMachineName(), 'a', TRUE];
    yield '!a' => [$pattern, 'r' . $this->randomMachineName(), 'b', FALSE];
    yield 'explicit collection' => [$pattern, 'b', 'c', TRUE];
    yield 'explicit collection, other' => [$pattern, 'b', 'other', FALSE];
    yield 'wildcard collection' => [$pattern, 'd', 'other', TRUE];
    yield 'wildcard collection again' => [$pattern, 'dddd', 'other', TRUE];
    yield 'with collection not exclusion' => [$pattern, 'de', 'other', TRUE];
    yield 'collection exclusion f' => [$pattern, 'de', 'fa', FALSE];
    yield 'collection exclusion g' => [$pattern, 'de', 'ga', FALSE];
  }

}
