<?php

namespace Drupal\Tests\migmag_process\Unit\Plugin;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;
use Drupal\migmag_process\Plugin\migrate\process\MigMagUuidGenerate;

/**
 * Tests the migmag_uuid_generate process plugin.
 *
 * @coversDefaultClass \Drupal\migmag_process\Plugin\migrate\process\MigMagUuidGenerate
 *
 * @group migmag_process
 */
class MigMagUuidGenerateTest extends MigrateProcessTestCase {

  /**
   * The UUID service mock.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $uuidGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->uuidGenerator = $this->getMockBuilder(UuidInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
  }

  /**
   * Tests the transformation of the provided values.
   *
   * @param mixed $value
   *   The incoming value to test the transformation with.
   * @param string|null $generated_uuid
   *   The UUID which should be "generated" by the actual UUID service. If NULL,
   *   then the test expects that the UUID generator shouldn't be invoked.
   * @param string $expected_result
   *   The expected generated UUID returned by the plugin.
   *
   * @covers ::transform
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform($value, ?string $generated_uuid, string $expected_result): void {
    if (is_string($generated_uuid)) {
      $this->uuidGenerator
        ->expects($this->once())
        ->method('generate')
        ->willReturn($generated_uuid);
    }
    else {
      $this->uuidGenerator
        ->expects($this->never())
        ->method('generate');
    }

    $plugin = new MigMagUuidGenerate(
      ['plugin' => 'migmag_uuid_generate'],
      'migmag_uuid_generate',
      [],
      $this->uuidGenerator
    );

    $actual_result = $plugin->transform(
      $value,
      $this->migrateExecutable,
      $this->row,
      'destination_property'
    );

    $this->assertSame($expected_result, $actual_result);
  }

  /**
   * Data provider for ::testTransform.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerTestTransform(): array {
    // cspell:disable
    return [
      'no value' => [
        'value' => NULL,
        'generated_uuid' => 'e4492b24-2e92-4913-8b61-a037305290e0',
        'expected_result' => 'e4492b24-2e92-4913-8b61-a037305290e0',
      ],
      'array value' => [
        'value' => [1],
        'generated_uuid' => 'e4492b24-2e92-4913-8b61-a037305290e0',
        'expected_result' => 'e4492b24-2e92-4913-8b61-a037305290e0',
      ],
      'object value' => [
        'value' => (object) [1],
        'generated_uuid' => 'e4492b24-2e92-4913-8b61-a037305290e0',
        'expected_result' => 'e4492b24-2e92-4913-8b61-a037305290e0',
      ],
      'invalid UUID' => [
        'value' => 'abcdef01-2345-6789-abcd-ef0123456789',
        'generated_uuid' => 'e4492b24-2e92-4913-8b61-a037305290e0',
        'expected_result' => 'e4492b24-2e92-4913-8b61-a037305290e0',
      ],
      'valid UUID' => [
        'value' => 'bfcdcc46-d0ab-486d-b2b1-ceed954e1f2a',
        'generated_uuid' => NULL,
        'expected_result' => 'bfcdcc46-d0ab-486d-b2b1-ceed954e1f2a',
      ],
      'valid UUID at the beginning' => [
        'value' => 'bfcdcc46-d0ab-486d-b2b1-ceed954e1f2foo-bar-baz',
        'generated_uuid' => NULL,
        'expected_result' => 'bfcdcc46-d0ab-486d-b2b1-ceed954e1f2f',
      ],
      'valid UUID at the end' => [
        'value' => 'foo-bar-bazbfcdcc46-d0ab-486d-b2b1-ceed954e1f2a',
        'generated_uuid' => NULL,
        'expected_result' => 'bfcdcc46-d0ab-486d-b2b1-ceed954e1f2a',
      ],
      'valid UUID in the middle' => [
        'value' => 'foo-bafcdcc46-d0ab-486d-b2b1-ceed954e1f2ar-baz',
        'generated_uuid' => NULL,
        'expected_result' => 'afcdcc46-d0ab-486d-b2b1-ceed954e1f2a',
      ],
    ];
    // cspell:enable
  }

}
