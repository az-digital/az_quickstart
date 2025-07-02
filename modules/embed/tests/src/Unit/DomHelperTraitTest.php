<?php

declare(strict_types=1);

namespace Drupal\Tests\embed\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\embed\DomHelperTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests \Drupal\embed\DomHelperTrait.
 *
 * @group embed
 */
class DomHelperTraitTest extends UnitTestCase {
  use DomHelperTrait;

  /**
   * The DOM Document used for testing.
   *
   * @var \DOMDocument
   */
  protected $document;

  /**
   * The DOM Node used for testing.
   *
   * @var \DOMElement
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->document = Html::load('<outer><test foo="bar" namespace:foo="bar"><test bar="foo"></test></test></outer>');
    $this->node = $this->document->getElementsByTagName('body')->item(0)->firstChild->firstChild;
  }

  /**
   * Tests DomHelperTrait::changeNodeName().
   */
  public function testChangeNodeName() {
    $this->changeNodeName($this->node, 'tested');
    $this->assertEquals($this->node->tagName, 'tested');
    $this->assertEquals(Html::serialize($this->document), '<outer><tested foo="bar" namespace:foo="bar"><test bar="foo"></test></tested></outer>');
  }

  /**
   * Tests DomHelperTrait::setNodeContent().
   *
   * @dataProvider providerTestSetNodeContent
   */
  public function testSetNodeContent($content, $expected_output) {
    $this->setNodeContent($this->node, $content);
    $this->assertEquals(Html::serialize($this->document), $expected_output);
  }

  /**
   * Data provider for testSetNodeContent().
   */
  public static function providerTestSetNodeContent(): array {
    return [
      'empty' => [
        '',
        '<outer><test foo="bar" namespace:foo="bar"></test></outer>',
      ],
      'single node without children' => [
        '<div></div>',
        '<outer><test foo="bar" namespace:foo="bar"><div></div></test></outer>',
      ],
      'single node with children' => [
        '<div><replacement replaced="true" /></div>',
        '<outer><test foo="bar" namespace:foo="bar"><div><replacement replaced="true"></replacement></div></test></outer>',
      ],
      'multiple nodes' => [
        '<p>first</p><p>second</p>',
        '<outer><test foo="bar" namespace:foo="bar"><p>first</p><p>second</p></test></outer>',
      ],
      'multiple nodes, with a text node, comment node and element node' => [
        'Second <!-- comment --> <p>third</p>',
        '<outer><test foo="bar" namespace:foo="bar">Second <!-- comment --> <p>third</p></test></outer>',
      ],
    ];
  }

  /**
   * Test DomHelperTrait::replaceNodeContent().
   *
   * @dataProvider providerTestReplaceNodeContent
   */
  public function testReplaceNodeContent($content, $expected_output) {
    $this->replaceNodeContent($this->node, $content);
    $this->assertEquals($expected_output, Html::serialize($this->document));
  }

  /**
   * Data provider for testReplaceNodeContent().
   */
  public static function providerTestReplaceNodeContent(): array {
    return [
      'empty' => [
        '',
        '<outer></outer>',
      ],
      'single node without children' => [
        '<div></div>',
        '<outer><div></div></outer>',
      ],
      'single node with children' => [
        '<div><replacement replaced="true" /></div>',
        '<outer><div><replacement replaced="true"></replacement></div></outer>',
      ],
      'multiple nodes' => [
        '<p>first</p><p>second</p>',
        '<outer><p>first</p><p>second</p></outer>',
      ],
      'multiple nodes, with a text node, comment node and element node' => [
        'Second <!-- comment --> <p>third</p>',
        '<outer>Second <!-- comment --> <p>third</p></outer>',
      ],
    ];
  }

  /**
   * Test DomHelperTrait::getNodeAttributesAsArray().
   */
  public function testGetNodeAttributesAsArray() {
    $attributes = $this->getNodeAttributesAsArray($this->node);
    $this->assertEquals(
      [
        'foo' => 'bar',
        'namespace:foo' => 'bar',
      ],
      $attributes
    );

    // Test more complex attributes with special characters.
    $string = "TEST: A <complex> 'encoded' \"JSON\" string";
    $object = ['nested' => ['array' => TRUE], 'string' => $string];
    $html = '<test data-json-string=\'' . Json::encode($string) . '\' data-json-object=\'' . Json::encode($object) . '\'></test>';
    $document = Html::load($html);
    $node = $document->getElementsByTagName('body')->item(0)->firstChild;
    $attributes = $this->getNodeAttributesAsArray($node);
    $this->assertEquals(
      [
        'data-json-string' => $string,
        'data-json-object' => $object,
      ],
      $attributes
    );
  }

}
