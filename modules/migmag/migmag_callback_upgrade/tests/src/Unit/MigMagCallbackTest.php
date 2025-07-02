<?php

namespace Drupal\Tests\migmag_callback_upgrade\Unit\process;

use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;
use Drupal\migrate\MigrateException;
use PHPUnit\Util\Test;

/**
 * Tests the actual callback process plugin.
 *
 * @covers \Drupal\migmag_callback_upgrade\MigMagCallback
 *
 * @group migmag_callback_upgrade
 */
class MigMagCallbackTest extends MigrateProcessTestCase {

  /**
   * Tests callback with valid "callable".
   *
   * @dataProvider providerCallback
   */
  public function testCallback($callable) {
    $this->plugin = $this->createPlugin(
      ['callable' => $callable],
      'callback'
    );
    $value = $this->plugin->transform('FooBar', $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame('foobar', $value);
  }

  /**
   * Data provider for ::testCallback().
   */
  public static function providerCallback() {
    return [
      'function' => ['strtolower'],
      'class method' => [[self::class, 'strtolower']],
    ];
  }

  /**
   * Test callback with valid "callable" and multiple arguments.
   *
   * @dataProvider providerCallbackArray
   */
  public function testCallbackArray($callable, $args, $result) {
    $this->plugin = $this->createPlugin(
      ['callable' => $callable, 'unpack_source' => TRUE],
      'callback'
    );
    $value = $this->plugin->transform($args, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($result, $value);
  }

  /**
   * Data provider for ::testCallbackArray().
   */
  public static function providerCallbackArray() {
    return [
      'date format' => [
        'date',
        ['Y-m-d', 995328000],
        '2001-07-17',
      ],
      'rtrim' => [
        'rtrim',
        ['https://www.example.com/', '/'],
        'https://www.example.com',
      ],
      'str_replace' => [
        'str_replace',
        [['One', 'two'], ['1', '2'], 'One, two, three!'],
        '1, 2, three!',
      ],
    ];
  }

  /**
   * Tests callback exceptions.
   *
   * @param string $message
   *   The expected exception message.
   * @param array $configuration
   *   The plugin configuration being tested.
   * @param string $class
   *   (optional) The expected exception class.
   * @param mixed $args
   *   (optional) Arguments to pass to the transform() method.
   *
   * @dataProvider providerCallbackExceptions
   */
  public function testCallbackExceptions($message, array $configuration, $class = 'InvalidArgumentException', $args = NULL) {
    $this->expectException($class);
    $this->expectExceptionMessage($message);
    $this->plugin = $this->createPlugin(
      $configuration,
      'callback'
    );
    $this->plugin->transform($args, $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Data provider for ::testCallbackExceptions().
   */
  public static function providerCallbackExceptions() {
    return [
      'not set' => [
        'message' => 'The "callable" must be set.',
        'configuration' => [],
      ],
      'invalid method' => [
        'message' => 'The "callable" must be a valid function or method.',
        'configuration' => ['callable' => 'nonexistent_callable'],
      ],
      'array required' => [
        'message' => "When 'unpack_source' is set, the source must be an array. Instead it was of type 'string'",
        'configuration' => ['callable' => 'count', 'unpack_source' => TRUE],
        'class' => MigrateException::class,
        'args' => 'This string is not an array.',
      ],
    ];
  }

  /**
   * Makes a string lowercase for testing purposes.
   *
   * @param string $string
   *   The input string.
   *
   * @return string
   *   The lowercased string.
   *
   * @see \Drupal\Tests\migrate\Unit\process\CallbackTest::providerCallback()
   */
  public static function strToLower($string) {
    return mb_strtolower($string);
  }

  /**
   * Returns the plugin class to be tested by @covers annotation.
   *
   * @return string
   *   The FQCN of the plugin.
   */
  protected function getPluginClass(): string {
    if (method_exists(Test::class, 'parseTestMethodAnnotations')) {
      $annotations = Test::parseTestMethodAnnotations(
        static::class,
        // @phpstan-ignore-next-line
        $this->getName()
      );

      if (isset($annotations['class']['covers'])) {
        return $annotations['class']['covers'][0];
      }
      else {
        $this->fail('No plugin class was specified');
      }
    }

    $covers = $this->getTestClassCovers();
    if (!empty($covers)) {
      return $covers[0];
    }
    $this->fail('No plugin class was specified');
  }

  /**
   * Instantiates the plugin being tested.
   *
   * @return \Drupal\migrate\Plugin\MigrateProcessInterface
   *   The plugin being tested.
   */
  protected function createPlugin(array $plugin_configuration, string $plugin_id, array $plugin_definition = []) {
    $plugin_class = $this->getPluginClass();
    return new $plugin_class(
      $plugin_configuration,
      $plugin_id,
      $plugin_definition
    );
  }

}
