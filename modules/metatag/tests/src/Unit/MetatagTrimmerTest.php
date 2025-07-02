<?php

namespace Drupal\Tests\metatag\Unit;

use Drupal\metatag\MetatagTrimmer;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Exception;

/**
 * This class provides methods for testing the MetaTagtrimmer service.
 *
 * @group metatag
 */
class MetatagTrimmerTest extends UnitTestCase {

  /**
   * The Metatagtrimmer Object.
   *
   * @var \Drupal\metatag\MetatagTrimmer
   */
  protected $metatagTrimmer;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->metatagTrimmer = new MetatagTrimmer();
  }

  /**
   * Tests the trimBeforeValue method.
   */
  public function testTrimBeforeValue() {
    $trimResult1 = $this->metatagTrimmer->trimBeforeValue('Test 123', 7);
    $this->assertEquals('Test', $trimResult1);
    $trimResult2 = $this->metatagTrimmer->trimBeforeValue('Test 123 123', 8);
    $this->assertEquals('Test 123', $trimResult2);
    $trimResult3 = $this->metatagTrimmer->trimBeforeValue('Test', 2);
    $this->assertEquals('Test', $trimResult3);
    $trimResult4 = $this->metatagTrimmer->trimBeforeValue('Test 123 123', 10);
    $this->assertEquals('Test 123', $trimResult4);
    $trimResult5 = $this->metatagTrimmer->trimBeforeValue('Test 123 123', 20);
    $this->assertEquals('Test 123 123', $trimResult5);
  }

  /**
   * Tests the trimAfterValue method.
   */
  public function testTrimAfterValue() {
    $trimResult1 = $this->metatagTrimmer->trimAfterValue('Test 123', 7);
    $this->assertEquals($trimResult1, 'Test 123');
    $trimResult2 = $this->metatagTrimmer->trimAfterValue('Test 123 123', 8);
    $this->assertEquals($trimResult2, 'Test 123');
    $trimResult3 = $this->metatagTrimmer->trimAfterValue('Test 123', 5);
    $this->assertEquals($trimResult3, 'Test');
    $trimResult4 = $this->metatagTrimmer->trimAfterValue('Test 123 123', 10);
    $this->assertEquals('Test 123 123', $trimResult4);
    $trimResult5 = $this->metatagTrimmer->trimAfterValue('Test 123 123', 20);
    $this->assertEquals('Test 123 123', $trimResult5);
  }

  /**
   * Tests the trimOnValue method.
   */
  public function testTrimOnValue() {
    $trimResult1 = $this->metatagTrimmer->trimByMethod('Test 123', 7, 'onValue');
    $this->assertEquals('Test 12', $trimResult1);
    $trimResult2 = $this->metatagTrimmer->trimByMethod('Test 123 123', 5, 'onValue');
    $this->assertEquals('Test', $trimResult2);
  }

  /**
   * Tests if trimByMethod will throw an error when given a non existing method.
   */
  public function testTrimByMethodError() {
    $this->expectException(Exception::class);
    $this->metatagTrimmer->trimByMethod('test', 4, 'noValue');
  }

  /**
   * Tests the testTrimByMethod method.
   */
  public function testTrimByMethod() {
    $trimResult1 = $this->metatagTrimmer->trimByMethod("Test 123", 7, 'beforeValue');
    $this->assertEquals('Test', $trimResult1);
    $trimResult2 = $this->metatagTrimmer->trimByMethod("Test 123", 7, 'onValue');
    $this->assertEquals('Test 12', $trimResult2);
    $trimResult3 = $this->metatagTrimmer->trimByMethod("Test 123", 7, 'afterValue');
    $this->assertEquals('Test 123', $trimResult3);
  }

  /**
   * Tests how the end of the string is trimmed.
   */
  public function testEndOfTheWordTrimming() {
    // Test standard end char trimming:
    $trimResult = $this->metatagTrimmer->trimEndChars('Test ');
    $this->assertEquals('Test', $trimResult);

    $trimResult = $this->metatagTrimmer->trimEndChars("Test\n");
    $this->assertEquals('Test', $trimResult);

    // Test end char trimming with specific chars provided:
    $trimEndChars = '|"';
    $trimResult = $this->metatagTrimmer->trimEndChars('Test|', $trimEndChars);
    $this->assertEquals('Test', $trimResult);

    $trimEndChars .= "\\n";
    $trimResult = $this->metatagTrimmer->trimEndChars("Test\\n", $trimEndChars);
    $this->assertEquals("Test", $trimResult);

    $trimResult = $this->metatagTrimmer->trimEndChars('Test"', $trimEndChars);
    $this->assertEquals('Test', $trimResult);

    $trimEndChars .= "'";
    $trimResult = $this->metatagTrimmer->trimEndChars("Test'", $trimEndChars);
    $this->assertEquals('Test', $trimResult);

    $trimEndChars .= '&';
    $trimResult = $this->metatagTrimmer->trimEndChars("Test&'", $trimEndChars);
    $this->assertEquals('Test', $trimResult);

    $trimResult = $this->metatagTrimmer->trimEndChars("Test&|'", $trimEndChars);
    $this->assertEquals('Test', $trimResult);
  }

}
