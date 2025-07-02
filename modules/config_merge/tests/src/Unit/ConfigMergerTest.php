<?php

namespace Drupal\Tests\config_merge\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\config_merge\ConfigMerger;

/**
 * @coversDefaultClass \Drupal\config_merge\ConfigMerger
 * @group config_merge
 */
class ConfigMergerTest extends UnitTestCase {

  /**
   * Configuration merge object.
   *
   * @var \Drupal\config_merge\ConfigMerger
   */
  public $configMerger;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->configMerger = new ConfigMerger();
  }

  /**
   * Provides associative data for previous, current, and active states.
   *
   * @return array
   *   An array of three arrays representing previous, current, and active
   *   states of a piece of configuration.
   */
  protected static function getAssociativeStates() {
    $previous = [
      'first' => 1,
      'second' => [
        'one',
        'two',
      ],
      'third' => [
        'one' => 'first',
        'two' => 'second',
      ],
      'fourth' => 'fourth',
    ];

    $current = $previous;

    $active = $previous;

    $active['fifth'] = 'fifth';

    return [$previous, $current, $active];
  }

  /**
   * Provides indexed data for previous, current, and active states.
   *
   * @return array
   *   An array of three arrays representing previous, current, and active
   *   states of a piece of configuration.
   */
  protected static function getIndexedStates() {
    $previous = [
      0 => 1,
      1 => [
        'one',
        'two',
      ],
      2 => [
        'one' => 'first',
        'two' => 'second',
      ],
      3 => 'fourth',
    ];

    $current = $previous;

    $active = $previous;

    return [$previous, $current, $active];
  }

  /**
   * Provides data to ::testMergeConfigItemStates().
   */
  public static function statesProvider() {
    $data = [];

    // Provide associative data.
    // Test the case that there is no change between previous and current.
    [$previous, $current, $active] = self::getAssociativeStates();

    // If there is no difference between previous and current, no changes should
    // be made to active.
    $expected = $active;

    $data['associative no difference'] = [
      $previous,
      $current,
      $active,
      $expected,
    ];

    // Test additions.
    [$previous, $current, $active] = self::getAssociativeStates();

    $current['second'][] = 'three';
    $current['third']['third'] = 'three';

    $current = array_merge(
      array_slice($current, 0, 1),
      ['another' => 'test'],
      array_slice($current, 1)
    );

    // Additions should be merged into active.
    $expected = $active;
    $expected['second'][] = 'three';
    $expected['third']['third'] = 'three';
    // The new array key should be merged at the same position.
    $expected = array_merge(
      array_slice($expected, 0, 1),
      ['another' => 'test'],
      array_slice($expected, 1)
    );

    $data['associative additions'] = [
      $previous,
      $current,
      $active,
      $expected,
    ];

    // Test deletions.
    [$previous, $current, $active] = self::getAssociativeStates();

    unset($current['first']);
    unset($current['second'][array_search('two', $current['second'])]);
    unset($current['third']['one']);

    // Deletions should be made to active.
    $expected = $active;
    unset($expected['first']);
    unset($expected['second'][array_search('two', $expected['second'])]);
    unset($expected['third']['one']);

    $data['associative deletions'] = [
      $previous,
      $current,
      $active,
      $expected,
    ];

    // Test deletions when the value has been customized.
    // Expected is unchanged because a customized value should not be
    // deleted.
    $active['fifth'] = 'customized';
    unset($current['fifth']);
    $expected['fifth'] = 'customized';

    $data['associative deletions with customization'] = [
      $previous,
      $current,
      $active,
      $expected,
    ];

    // Test changes.
    [$previous, $current, $active] = self::getAssociativeStates();
    $current['third']['one'] = 'change';
    $current['fourth'] = 'change';

    $expected = $active;
    $expected['third']['one'] = 'change';
    $expected['fourth'] = 'change';

    $data['associative changes'] = [
      $previous,
      $current,
      $active,
      $expected,
    ];

    // Test changes with customization.
    // In this case, the active value should be retained despite the
    // availability of an update.
    $active['third']['one'] = 'active';
    $expected['third']['one'] = 'active';

    $data['associative changes with customization'] = [
      $previous,
      $current,
      $active,
      $expected,
    ];

    // Provide indexed data.
    // Test the case that there is no change between previous and current.
    [$previous, $current, $active] = self::getIndexedStates();

    $active[4] = 'fifth';

    // If there is no difference between previous and current, no changes should
    // be made to active.
    $expected = $active;

    $data['indexed no difference'] = [
      $previous,
      $current,
      $active,
      $expected,
    ];

    // Test additions.
    [$previous, $current, $active] = self::getIndexedStates();

    $current[1][] = 'three';
    $current[2]['three'] = 'third';
    $current[] = 'test';

    // Current is changed but active is not, so we expect current.
    $expected = $current;

    $data['indexed additions current'] = [
      $previous,
      $current,
      $active,
      $expected,
    ];

    $current[2]['three'] = 'third';
    $active[1][] = 'something';

    // Current is changed but active is also so we expect active.
    $expected = $active;

    $data['indexed additions active'] = [
      $previous,
      $current,
      $active,
      $expected,
    ];

    // Test deletions.
    [$previous, $current, $active] = self::getIndexedStates();

    unset($current[0]);
    unset($current[1][array_search('two', $current[1])]);
    unset($current[2]['one']);

    // Deletions should be made to active.
    $expected = $active;
    unset($expected[0]);
    unset($expected[1][array_search('two', $expected[1])]);
    unset($expected[2]['one']);

    $data['indexed deletions'] = [
      $previous,
      $current,
      $active,
      $expected,
    ];

    // Test changes.
    [$previous, $current, $active] = self::getIndexedStates();
    $current[2]['one'] = 'change';
    $current[3] = 'change';

    $expected = $active;
    $expected[2]['one'] = 'change';
    $expected[3] = 'change';

    $data['indexed changes'] = [
      $previous,
      $current,
      $active,
      $expected,
    ];

    return $data;
  }

  /**
   * @covers ::mergeConfigItemStates
   * @dataProvider statesProvider
   */
  public function testMergeConfigItemStates($previous, $current, $active, $expected) {
    $result = $this->configMerger->mergeConfigItemStates($previous, $current, $active);

    $this->assertSame($expected, $result);
  }

}
