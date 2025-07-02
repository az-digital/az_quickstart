<?php

namespace Drupal\Tests\migmag\Unit;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\migmag\Traits\MigMagKernelTestDxTrait;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Tests MigMagKernelTestDxTrait.
 *
 * @coversDefaultClass \Drupal\Tests\migmag\Traits\MigMagKernelTestDxTrait
 *
 * @group migmag
 */
class MigMagKernelTestDxTraitTest extends UnitTestCase {

  use MigMagKernelTestDxTrait;

  /**
   * A two-dimensional array of messages.
   *
   * The first key is the type of message, the second is just numeric. Values
   * are the messages.
   *
   * @var array
   *
   * @see \Drupal\Tests\migrate\Kernel\MigrateTestBase
   */
  protected array $migrateMessages = [];

  /**
   * The source database connection, if any.
   *
   * @var \Drupal\Core\Database\Connection|null
   *
   * @see \Drupal\Tests\migrate\Kernel\MigrateTestBase
   */
  protected $sourceDatabase;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Tests assertNoUnexpectedMigrationMessages.
   *
   * @param array[] $messages
   *   The actual migration messages.
   * @param array[] $expected_messages
   *   The expected diff of the comparison failure.
   * @param string $expected_failure_message
   *   The expected diff of the comparison failure.
   *
   * @dataProvider providerAssertExpectedMigrationMessages
   *
   * @covers ::assertNoUnexpectedMigrationMessages
   */
  public function testAssertExpectedMigrationMessages(array $messages, array $expected_messages = [], string $expected_failure_message = '') {
    $this->migrateMessages = $messages;

    if ($expected_failure_message) {
      $this->expectException(ExpectationFailedException::class);
      $this->expectExceptionMessage($expected_failure_message);
    }
    $this->assertExpectedMigrationMessages($expected_messages);
  }

  /**
   * Test data provider for ::testAssertNoUnexpectedMigrationMessages.
   *
   * @return array
   *   The test cases.
   */
  public function providerAssertExpectedMigrationMessages() {
    return [
      'no actual or expected messages' => [
        'messages' => [],
      ],
      'no actual messages, a single expected message' => [
        'messages' => [],
        'expected messages' => [
          'error' => ['foo'],
        ],
      ],
      'actual message matches expected message' => [
        'messages' => [
          'error' => ['foo'],
        ],
        'expected messages' => [
          'error' => ['foo'],
        ],
      ],
      'single error, no expected messages' => [
        'messages' => [
          'error' => ['an error'],
        ],
        'expected messages' => [],
        // Starting with core 11.1, Variable::export uses short array syntax.
        'expected diff' => DeprecationHelper::backwardsCompatibleCall(
          \Drupal::VERSION,
          '11.1',
          fn () => <<<EOF
Unexpected migrate messages are present:
[
  'error' => [
    'an error',
  ],
]
Failed asserting that an array is empty.
EOF
          ,
          fn () => <<<EOF
Unexpected migrate messages are present:
array(
  'error' => array(
    'an error',
  ),
)
Failed asserting that an array is empty.
EOF
          ,
        ),
      ],

      'multiple errors with translatable markup' => [
        'messages' => [
          'error' => [
            'an another error',
            new TranslatableMarkup('translatable markup error'),
          ],
        ],
        'expected messages' => [],
        // Starting with core 11.1, Variable::export uses short array syntax.
        'expected diff' => DeprecationHelper::backwardsCompatibleCall(
          \Drupal::VERSION,
          '11.1',
          fn () => <<<EOF
Unexpected migrate messages are present:
[
  'error' => [
    'an another error',
    'translatable markup error',
  ],
]
Failed asserting that an array is empty.
EOF
          ,
          fn () => <<<EOF
Unexpected migrate messages are present:
array(
  'error' => array(
    'an another error',
    'translatable markup error',
  ),
)
Failed asserting that an array is empty.
EOF
          ,
        ),
      ],
    ];
  }

  /**
   * Tests assertNoMigrationMessages.
   *
   * @param array[] $messages
   *   The actual migration messages.
   * @param string $expected_diff
   *   The expected diff of the comparison failure.
   *
   * @dataProvider providerAssertNoMigrationMessages
   *
   * @covers ::assertNoMigrationMessages
   *
   * @group legacy
   */
  public function testAssertNoMigrationMessages(array $messages, string $expected_diff = '') {
    $this->migrateMessages = $messages;
    $this->expectDeprecation('MigMagKernelTestDxTrait::assertNoMigrationMessages() is deprecated in migmag:1.7.0 and is removed from migmag:2.0.0. Use MigMagKernelTestDxTrait::assertExpectedMigrationMessages() instead. See https://www.drupal.org/node/3264723');

    // Try to get the diff of the comparison failure we expect.
    try {
      $this->assertNoMigrationMessages();
    }
    catch (ExpectationFailedException $e) {
      $difference = $e->getComparisonFailure()->getDiff();
      $this->assertEquals($expected_diff, $difference);
    }

    if ($expected_diff) {
      $this->expectException(ExpectationFailedException::class);
      $this->expectExceptionMessage('Failed asserting that two arrays are equal.');
    }
    $this->assertNoMigrationMessages();
  }

  /**
   * Test data provider for ::testAssertNoMigrationMessages.
   *
   * @return array
   *   The test cases.
   */
  public function providerAssertNoMigrationMessages() {
    return [
      'no messages' => [
        'messages' => [],
      ],
      'single error' => [
        'messages' => [
          'error' => ['an error'],
        ],
        'expected diff' => <<<EOF

--- Expected
+++ Actual
@@ @@
 Array (
     'error' => Array (
-        0 => '…'
+        0 => 'an error'
     )
 )

EOF
      ],
      'multiple errors with translatable markup' => [
        'messages' => [
          'error' => [
            'an another error',
            new TranslatableMarkup('translatable markup error'),
          ],
        ],
        'expected diff' => <<<EOF

--- Expected
+++ Actual
@@ @@
 Array (
     'error' => Array (
-        0 => '…'
-        1 => '…'
+        0 => 'an another error'
+        1 => 'translatable markup error'
     )
 )

EOF
      ],
    ];
  }

}
