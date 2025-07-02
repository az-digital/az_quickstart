<?php

declare(strict_types=1);

namespace src\Kernel;

use Drupal\config_split\Config\ConfigPatch;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the API of extending the config schema with "patch index callback".
 *
 * @group config_split
 */
class PatchSequenceTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_split',
    'config_split_sequence_test',
  ];

  /**
   * The patch merge service.
   *
   * @var \Drupal\config_split\Config\ConfigPatchMerge
   */
  protected $patchMerge;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->patchMerge = \Drupal::service('config_split.patch_merge');
  }

  /**
   * Test the patch creation and merge behavior.
   *
   * @param string $name
   *   The name of the config.
   * @param array $configA
   *   The first config.
   * @param array $configB
   *   The second config.
   * @param \Drupal\config_split\Config\ConfigPatch|null $expectedAB
   *   The expected patch.
   *
   * @dataProvider sequenceProvider
   */
  public function testSequencePatch(string $name, array $configA, array $configB, ?ConfigPatch $expectedAB = NULL) {
    // Create patches in both directions.
    $patchAB = $this->patchMerge->createPatch($configA, $configB, $name);
    $patchBA = $this->patchMerge->createPatch($configB, $configA, $name);

    // Applying the patch gives the other config.
    self::assertEquals($configA, $this->patchMerge->mergePatch($configB, $patchBA, $name));
    self::assertEquals($configB, $this->patchMerge->mergePatch($configA, $patchAB, $name));
    self::assertEquals($patchAB, $patchBA->invert());

    // Applying the patch to itself doesn't change anything (To be falsified).
    self::assertEquals($configA, $this->patchMerge->mergePatch($configA, $patchBA, $name));
    self::assertEquals($configB, $this->patchMerge->mergePatch($configB, $patchAB, $name));

    if ($expectedAB !== NULL) {
      self::assertEquals($expectedAB, $patchAB);
    }

  }

  /**
   * Data provider for complex examples with a real schema.
   */
  public static function sequenceProvider() {
    $a = [
      'nested' => [
        [
          ['A'],
        ],
        [
          ['B', 'C'],
          ['B', 'C'],
        ],
        [
          ['B', 'C'],
          ['B', 'C'],
        ],
        [
          ['D', 'E', 'F'],
          ['D', 'E', 'F'],
          ['D', 'H', 'I'],
        ],
      ],
    ];

    $b = [
      'nested' => [
        [
          ['A'],
        ],
        [
          ['B', 'C'],
          ['B', 'C'],
        ],
        [
          ['D', 'E', 'F'],
          ['D', 'E', 'I'],
          ['G', 'H', 'I'],
        ],
      ],
    ];

    // cSpell:disable
    $patch = ConfigPatch::fromArray([
      'added' => [
        'nested' => [
          'config_split_sequence_326c6dj6gm1df0q4' => [
            // The first element is still the same so the inner value changes.
            'config_split_sequence_1_4vdqmvepaaqlr894' => ['I'],
            // The first element is different so the whole element is considered
            // to be replaced due to the patch index callback.
            'config_split_sequence_7986rer67f0e7uh' => ['G', 'H', 'I'],
          ],
        ],
      ],
      'removed' => [
        'nested' => [
          'config_split_sequence_1_7aogf3t6u82fn7bf' => [
            ['B', 'C'],
            ['B', 'C'],
          ],
          'config_split_sequence_326c6dj6gm1df0q4' => [
            // The first element is still the same so the inner value changes.
            'config_split_sequence_1_4vdqmvepaaqlr894' => ['F'],
            // The first element is different so the whole element is considered
            // to be replaced due to the patch index callback.
            'config_split_sequence_2_4vdqmvepaaqlr894' => ['D', 'H', 'I'],
          ],
        ],
      ],
    ]);
    // cSpell:enable
    yield 'first test' => [
      'name' => 'config_split_sequence_test.nested_sequences',
      'configA' => $a,
      'configB' => $b,
      'expectedAB' => $patch,
    ];
  }

}
