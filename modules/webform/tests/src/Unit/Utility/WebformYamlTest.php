<?php

namespace Drupal\Tests\webform\Unit\Utility;

use Drupal\Core\Serialization\Yaml;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\Utility\WebformYaml;

/**
 * Tests webform tidy utility.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformYaml
 */
class WebformYamlTest extends UnitTestCase {

  /**
   * Tests WebformYaml tidy with WebformYaml::tidy().
   *
   * @param array $data
   *   The array to run through WebformYaml::tidy().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformYaml::tidy()
   *
   * @dataProvider providerTidy
   */
  public function testTidy(array $data, $expected) {
    $result = WebformYaml::tidy(Yaml::encode($data));
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testTidy().
   *
   * @see testTidy()
   */
  public function providerTidy() {
    $tests[] = [
      ['simple' => 'value'],
      "simple: value",
    ];
    $tests[] = [
      ['returns' => "line 1\nline 2"],
      "returns: |-\n  line 1\n  line 2",
    ];
    $tests[] = [
      ['one two' => "line 1\nline 2"],
      "'one two': |-\n  line 1\n  line 2",
    ];
    $tests[] = [
      ['one two' => "line 1\r\nline 2"],
      "'one two': |-\n  line 1\n  line 2",
    ];
    $tests[] = [
      ['array' => ['one', 'two']],
      "array:\n  - one\n  - two",
    ];
    $tests[] = [
      [['one' => 'One'], ['two' => 'Two']],
      "- one: One\n- two: Two",
    ];
    return $tests;
  }

  /**
   * Tests WebformYaml decode with WebformYaml::decode().
   *
   * @param string $yaml
   *   The string to run through WebformYaml::decode().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformYaml::decode()
   *
   * @dataProvider providerDecode
   */
  public function testDecode($yaml, $expected) {
    $result = WebformYaml::decode($yaml);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testDecode().
   *
   * @see testDecode()
   */
  public function providerDecode() {
    $tests[] = [
      "simple: value",
      ['simple' => 'value'],
    ];
    $tests[] = [
      "returns: |\n  line 1\n  line 2",
      ['returns' => "line 1\nline 2"],
    ];
    $tests[] = [
      "'one two': |\n  line 1\n  line 2",
      ['one two' => "line 1\nline 2"],
    ];
    $tests[] = [
      "array:\n  - one\n  - two",
      ['array' => ['one', 'two']],
    ];
    $tests[] = [
      "- one: One\n- two: Two",
      [['one' => 'One'], ['two' => 'Two']],
    ];
    $tests[] = [
      FALSE,
      [],
    ];
    $tests[] = [
      NULL,
      [],
    ];
    $tests[] = [
      [],
      [],
    ];
    $tests[] = [
      0,
      [],
    ];
    return $tests;
  }

  /**
   * Tests WebformYaml encode with WebformYaml::encode().
   *
   * @param string $yaml
   *   The string to run through WebformYaml::encode().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformYaml::encode()
   *
   * @dataProvider providerEncode
   */
  public function testEncode($yaml, $expected) {
    $result = WebformYaml::encode($yaml);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testEncode().
   *
   * @see testEncode()
   */
  public function providerEncode() {
    $tests[] = [
      ['simple' => 'value'],
      "simple: value",
    ];
    $tests[] = [
      ['returns' => "line 1\nline 2"],
      "returns: |-\n  line 1\n  line 2",
    ];
    $tests[] = [
      ['one two' => "line 1\nline 2"],
      "'one two': |-\n  line 1\n  line 2",
    ];
    $tests[] = [
      ['array' => ['one', 'two']],
      "array:\n  - one\n  - two",
    ];
    $tests[] = [
      [['one' => 'One'], ['two' => 'Two']],
      "- one: One\n- two: Two",
    ];
    $tests[] = [
      [],
      '',
    ];
    $tests[] = [
      '',
      "''",
    ];
    $tests[] = [
      0,
      '0',
    ];
    return $tests;
  }

}
