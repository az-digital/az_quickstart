<?php

declare(strict_types=1);

namespace Drupal\Tests\config_split\Unit;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\config_split\Config\ConfigPatch;
use Drupal\config_split\Config\ConfigPatchMerge;
use Drupal\config_split\Config\ConfigSorter;
use Drupal\Core\Config\Schema\Undefined;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\TypedData\DataDefinition;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

/**
 * Test patch creation and merging without sorting.
 *
 * @group config_split_patch
 */
class ConfigPatchTest extends TestCase {

  use ProphecyTrait;
  /**
   * The patch merge service under test.
   *
   * @var \Drupal\config_split\Config\ConfigPatchMerge
   */
  protected $patchMerge;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Prophesize the sorter.
    $sorter = $this->prophesize(ConfigSorter::class);
    // This is a unit test, we sort everything.
    $aggressiveSorter = $this->getSorter();
    // @phpstan-ignore-next-line
    $sorter->sort(Argument::type('string'), Argument::type('array'))->will(function ($args) use ($aggressiveSorter) {
      return $aggressiveSorter->sort(...$args);
    });

    $typedConfigManager = $this->prophesize(TypedConfigManagerInterface::class);
    // @phpstan-ignore-next-line
    $typedConfigManager->createFromNameAndData(Argument::type('string'), Argument::type('array'))->will(function ($args) {
      return Undefined::createInstance(DataDefinition::create('any'));
    });

    $this->patchMerge = new ConfigPatchMerge($sorter->reveal(), $typedConfigManager->reveal());
  }

  /**
   * Test that a patch is created correctly and can be applied again.
   *
   * @param array $configA
   *   The first config.
   * @param array $configB
   *   The second config.
   * @param \Drupal\config_split\Config\ConfigPatch|null $expectedAB
   *   The expected patch from A to B.
   *
   * @dataProvider standardPatchMergeProvider
   */
  public function testStandardPatchMerge(array $configA, array $configB, ?ConfigPatch $expectedAB = NULL) {
    $patchAB = $this->patchMerge->createPatch($configA, $configB, 'test');
    $patchBA = $this->patchMerge->createPatch($configB, $configA, 'test');

    // Applying the patch gives the other config.
    self::assertEquals($configA, $this->patchMerge->mergePatch($configB, $patchBA, 'test'));
    self::assertEquals($configB, $this->patchMerge->mergePatch($configA, $patchAB, 'test'));
    self::assertEquals($patchAB, $patchBA->invert());

    // Applying the patch to itself doesn't change anything (To be falsified).
    self::assertEquals($configA, $this->patchMerge->mergePatch($configA, $patchBA, 'test'));
    self::assertEquals($configB, $this->patchMerge->mergePatch($configB, $patchAB, 'test'));

    if ($expectedAB !== NULL) {
      self::assertEquals($expectedAB, $patchAB);
    }
  }

  /**
   * The data provider for the standard patch creation.
   *
   * @return \Generator
   *   The test cases.
   */
  public static function standardPatchMergeProvider() {
    yield 'deep merge' => [
      'configA' => [
        'dependencies' => ['a', 'b'],
        'children' => [
          'child1',
          'child2',
          'child3',
        ],
      ],
      'configB' => [
        'dependencies' => ['a', 'b'],
        'children' => [
          'child1',
          'child3',
        ],
      ],
    ];

    yield 'NestedArrayDiff' => [
      'configA' => [
        'title' => 'test',
        'type' => 'diff',
        'has_children' => [
          'child',
          'child1',
          'child2',
        ],
      ],
      'configB' => [
        'title' => 'test',
        'type' => 'array',
        'has_children' => [
          'child1',
          'child3',
        ],
        'something_else' => TRUE,
      ],
      'expectedAB' => ConfigPatch::fromArray([
        'added' => [
          'type' => 'array',
          'has_children' => [
            'child3',
          ],
          'something_else' => TRUE,
        ],
        'removed' => [
          'type' => 'diff',
          'has_children' => [
            'child',
            'child2',
          ],
        ],
      ]),
    ];

    // Sequence merge test.
    // Active config.
    $active = [
      'permissions' => [
        'access content',
        'create content',
        'delete content',
        'edit content',
      ],
    ];

    // Synced (default) config.
    $sync = [
      'permissions' => [
        'create content',
        'delete content',
        'edit content',
      ],
    ];

    // This is what we expect to be added in the created patch.
    $expected = [
      'permissions' => [
        'access content',
      ],
    ];

    yield 'sequence merge test' => [
      'configA' => $sync,
      'configB' => $active,
      'expectedAB' => ConfigPatch::fromArray([
        'added' => $expected,
        'removed' => [],
      ]),
    ];

    yield 'numeric non sequence test' => [
      'configA' => [
        'error_handlers' => [
          1 => 1,
          2 => 2,
          3 => 3,
        ],
      ],
      'configB' => [
        'error_handlers' => [
          1 => 1,
          3 => 3,
        ],
      ],
    ];

    yield 'test with duplicates' => [
      'configA' => [
        'list A' => [
          'A',
          'A',
          'A',
          'B',
        ],
      ],
      'configB' => [
        'list A' => [
          'A',
          'A',
          'A',
          'A',
          'A',
          'C',
        ],
      ],
    ];

    yield 'test with nested lists' => [
      'configA' => [
        'list A' => [
          [
            'uuid' => '1111',
            'value' => 'A',
          ],
          [
            'uuid' => '2222',
            'value' => 'A',
          ],
          [
            'uuid' => '3333',
            'value' => 'A',
          ],
        ],
      ],
      'configB' => [
        'list A' => [
          [
            'uuid' => '1111',
            'value' => 'A',
          ],
          [
            'uuid' => '2222',
            'value' => 'B',
          ],
          [
            'uuid' => '3333',
            'value' => 'A',
          ],
        ],
      ],
    ];
  }

  /**
   * Test some simplified config patch and merge workflow.
   */
  public function testUpdateMergeExample() {
    // This is a much simplified version of some config. We use the complete
    // split to split off the module 'a' but we also partially split the config.
    // This is the active config.
    $active = [
      'dependencies' => ['a', 'b'],
      'something' => 'A',
    ];

    // This is the config in the sync storage before changes were made.
    $sync = [
      'dependencies' => ['a', 'b'],
      'something_else' => 'B',
    ];

    // This is the config which was updated by removing 'a'.
    // The patch already created by the complete split would contain this.
    $updated = [
      'dependencies' => ['b'],
      'something' => 'A',
    ];

    // This is what we expect to be exported at then end.
    $expected = [
      'dependencies' => ['b'],
      'something_else' => 'B',
    ];

    // This is the patch which is already in the split storage.
    $patch1 = $this->patchMerge->createPatch($active, $updated, 'test');
    // This is the "fixed" sync storage so that we can create a merged patch.
    $fixed = $this->patchMerge->mergePatch($sync, $patch1, 'test');

    // This is the patch we want to export.
    $patch2 = $this->patchMerge->createPatch($active, $fixed, 'test');
    // This is what we export.
    $export = $this->patchMerge->mergePatch($active, $patch2, 'test');
    self::assertEquals($expected, $export);

    // When doing the reverse we expect it to work again.
    $import = $this->patchMerge->mergePatch($sync, $patch2->invert(), 'test');
    self::assertEquals($active, $import);
    $import = $this->patchMerge->mergePatch($export, $patch2->invert(), 'test');
    self::assertEquals($active, $import);
  }

  /**
   * Test that old split patches will work.
   */
  public function testPatchFormats() {
    $added = [
      'dependencies' => ['a', 'b'],
    ];

    $removed = [
      'test' => [
        'something',
      ],
    ];

    $exportPerspective = [
      'added' => $added,
      'removed' => $removed,
    ];

    $importPerspective = [
      'removing' => $added,
      'adding' => $removed,
    ];

    $patch1 = ConfigPatch::fromArray($exportPerspective);
    $patch2 = ConfigPatch::fromArray($importPerspective);

    self::assertSame($added, $patch1->getAdded());
    self::assertSame($added, $patch2->getAdded());
    self::assertSame($removed, $patch1->getRemoved());
    self::assertSame($removed, $patch2->getRemoved());

    self::assertEquals($patch1, $patch2);
  }

  /**
   * Test merging objects. This is a core bug and should never happen.
   */
  public function testArrayWithObjectDiff(): void {
    // Testing using a class with a ::fromArray() method.
    $attributes = new Attribute([
      'id' => 'test',
      'class' => [
        'test1',
        'test2',
      ],
    ]);

    $array1 = [
      0 => 'test',
      'attributes' => $attributes,
    ];

    // Test using a generic object for now.
    $object = new \stdClass();
    $object->id = 20;
    $object->name = 'something';

    $array2 = [
      0 => 'test',
      'object' => $object,
    ];

    // Expected differences in $array1 versus $array2.
    $removed = [
      'attributes' => $attributes->toArray(),
    ];

    // Expected differences in $array2 versus $array1.
    $added = [
      'object' => (array) $object,
    ];

    $patch1 = $this->patchMerge->createPatch($array1, $array2, 'test');

    self::assertEquals($removed, $patch1->getRemoved());
    self::assertEquals($added, $patch1->getAdded());
  }

  /**
   * Get an anonymous sorter that can be used to stub the real sorter.
   *
   * @return object
   *   The sorter
   */
  private function getSorter() {
    return new class() {

      /**
       * Sort config independent of its name.
       *
       * @param string $name
       *   The name, not used.
       * @param array $data
       *   The config array to sort.
       *
       * @return array
       *   The sorted data.
       */
      public function sort(string $name, array $data): array {
        if (is_array($data)) {
          foreach ($data as $key => $value) {
            if (is_array($value)) {
              // Recursively sort everything.
              $data[$key] = $this->sort($name, $value);
            }
          }
          // If it is a sequence, sort by value otherwise sort by key.
          if (array_keys($data) === range(0, count($data) - 1, 1)) {
            sort($data);
          }
          else {
            ksort($data);
          }
        }

        return $data;
      }

    };
  }

}
