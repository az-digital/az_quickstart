<?php

namespace Drupal\Tests\metatag\Unit;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;
use Drupal\Tests\UnitTestCase;

/**
 * This class provides methods for testing the MetaNameBase class.
 *
 * @group metatag
 */
class MetaNameBaseTest extends UnitTestCase {

  /**
   * The MetaNameBase Mocked Object.
   *
   * @var \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $metaNameBase;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mocking cause it's an abstract class.
    $this->metaNameBase = $this->getMockBuilder(MetaNameBase::class)
      ->setConstructorArgs([[], 'test', []])
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
  }

  /**
   * Tests the tidy method.
   */
  public function testTidy() {
    $method = "tidy";
    $class = new \ReflectionClass(get_class($this->metaNameBase));
    $method = $class->getMethod($method);
    // Set the protected method tidy to be accessible.
    $method->setAccessible(TRUE);

    $filterResult1 = $method->invoke($this->metaNameBase, "  Test   123  ");
    $this->assertEquals('Test 123', $filterResult1);
    $filterResult2 = $method->invoke($this->metaNameBase, '  Test   123    Test');
    $this->assertEquals('Test 123 Test', $filterResult2);
    $filterResult3 = $method->invoke(
        $this->metaNameBase,
        "Test \n\n123\n  Test  \n  "
      );
    $this->assertEquals('Test 123 Test', $filterResult3);
    $filterResult4 = $method->invoke(
        $this->metaNameBase,
        "Test \r\n\r\n 123  \r\n "
      );
    $this->assertEquals('Test 123', $filterResult4);
    $filterResult5 = $method->invoke(
        $this->metaNameBase,
        "Test \t\t123  \tTest"
      );
    $this->assertEquals('Test 123 Test', $filterResult5);
  }

}
