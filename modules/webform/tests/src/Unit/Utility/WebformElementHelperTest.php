<?php

namespace Drupal\Tests\webform\Unit\Utility;

use Drupal\Core\Render\Markup;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Tests webform element utility.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformElementHelper
 */
class WebformElementHelperTest extends UnitTestCase {

  /**
   * Tests WebformElementHelper::isTitleDisplayed().
   *
   * @param array $element
   *   The element to run through WebformElementHelper::IsTitleDisplayed().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformElementHelper::isTitleDisplayed()
   *
   * @dataProvider providerIsTitleDisplayed
   */
  public function testIsTitleDisplayed(array $element, $expected) {
    $result = WebformElementHelper::IsTitleDisplayed($element);
    $this->assertEquals($expected, $result, serialize($element));
  }

  /**
   * Data provider for testIsTitleDisplayed().
   *
   * @see testIsTitleDisplayed()
   */
  public function providerIsTitleDisplayed() {
    $tests[] = [['#title' => 'Test'], TRUE];
    $tests[] = [['#title' => 'Test', '#title_display' => 'above'], TRUE];
    $tests[] = [[], FALSE];
    $tests[] = [['#title' => ''], FALSE];
    $tests[] = [['#title' => NULL], FALSE];
    $tests[] = [['#title' => 'Test', '#title_display' => 'invisible'], FALSE];
    return $tests;
  }

  /**
   * Tests WebformElementHelper::GetIgnoredProperties().
   *
   * @param array $element
   *   The array to run through WebformElementHelper::GetIgnoredProperties().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformElementHelper::GetIgnoredProperties()
   *
   * @dataProvider providerGetIgnoredProperties
   */
  public function testGetIgnoredProperties(array $element, $expected) {
    $result = WebformElementHelper::getIgnoredProperties($element);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testGetIgnoredProperties().
   *
   * @see testGetIgnoredProperties()
   */
  public function providerGetIgnoredProperties() {
    // Nothing ignored.
    $tests[] = [
      ['#value' => 'text'],
      [],
    ];
    // Ignore #tree.
    $tests[] = [
      ['#tree' => TRUE],
      ['#tree' => '#tree'],
    ];
    // Ignore #tree and #element_validate.
    $tests[] = [
      ['#tree' => TRUE, '#value' => 'text', '#element_validate' => 'some_function'],
      ['#tree' => '#tree', '#element_validate' => '#element_validate'],
    ];
    // Ignore #subelement__tree and #subelement__element_validate,
    // but not '#subelement__weight'.
    $tests[] = [
      ['#subelement__tree' => TRUE, '#value' => 'text', '#subelement__element_validate' => 'some_function', '#subelement__weight' => 0],
      ['#subelement__tree' => '#subelement__tree', '#subelement__element_validate' => '#subelement__element_validate'],
    ];
    return $tests;
  }

  /**
   * Tests WebformElementHelper::removeIgnoredProperties().
   *
   * @param array $element
   *   The array to run through WebformElementHelper::removeIgnoredProperties().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformElementHelper::removeIgnoredProperties()
   *
   * @dataProvider providerRemoveIgnoredProperties
   */
  public function testRemoveIgnoredProperties(array $element, $expected) {
    $result = WebformElementHelper::removeIgnoredProperties($element);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testRemoveIgnoredProperties().
   *
   * @see testRemoveIgnoredProperties()
   */
  public function providerRemoveIgnoredProperties() {
    // Nothing removed.
    $tests[] = [
      ['#value' => 'text'],
      ['#value' => 'text'],
    ];
    // Remove #tree.
    $tests[] = [
      ['#tree' => TRUE],
      [],
    ];
    // Remove #tree and #element_validate.
    $tests[] = [
      ['#tree' => TRUE, '#value' => 'text', '#element_validate' => 'some_function'],
      ['#value' => 'text'],
    ];
    // Remove #ajax: string.
    $tests[] = [
      ['#ajax' => 'some_function'],
      [],
    ];
    // Don't remove #ajax: FALSE.
    // @see @see \Drupal\webform\Element\WebformComputedBase
    $tests[] = [
      ['#ajax' => FALSE],
      ['#ajax' => FALSE],
    ];
    // Remove #subelement__tree and #subelement__element_validate.
    $tests[] = [
      ['#subelement__tree' => TRUE, '#value' => 'text', '#subelement__element_validate' => 'some_function', '#subelement__equal_stepwise_validate' => TRUE],
      ['#value' => 'text', '#subelement__equal_stepwise_validate' => TRUE],
    ];
    // Remove random nested #element_validate.
    $tests[] = [
      ['random' => ['#element_validate' => 'some_function']],
      ['random' => []],
    ];
    $tests[] = [
      ['#prefix' => ['#markup' => 'some_markup', '#element_validate' => 'some_function', '#equal_stepwise_validate' => TRUE]],
      ['#prefix' => ['#markup' => 'some_markup', '#equal_stepwise_validate' => TRUE]],
    ];
    // Remove any *_validate(s) and *_callback(s).
    $tests[] = [
      ['random' => ['#some_random_validate' => 'some_function']],
      ['random' => []],
    ];
    $tests[] = [
      ['random' => ['#some_random_callbacks' => 'some_function']],
      ['random' => []],
    ];
    // Remove #weight but not subelement__weight.
    $tests[] = [
      ['#weight' => 1, '#subelement__weight' => 1],
      ['#subelement__weight' => 1],
    ];
    return $tests;
  }

  /**
   * Tests WebformElementHelper::convertRenderMarkupToStrings().
   *
   * @param array $elements
   *   The array to run through WebformElementHelper::convertRenderMarkupToStrings().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformElementHelper::convertRenderMarkupToStrings()
   *
   * @dataProvider providerConvertRenderMarkupToStrings
   */
  public function testConvertRenderMarkupToStrings(array $elements, $expected) {
    WebformElementHelper::convertRenderMarkupToStrings($elements);
    $this->assertEquals($expected, $elements);
  }

  /**
   * Data provider for testConvertRenderMarkupToStrings().
   *
   * @see testConvertRenderMarkupToStrings()
   */
  public function providerConvertRenderMarkupToStrings() {
    return [
      [
        ['test' => Markup::create('markup')],
        ['test' => 'markup'],
      ],
      [
        ['test' => ['nested' => Markup::create('markup')]],
        ['test' => ['nested' => 'markup']],
      ],
    ];
  }

  /**
   * Tests WebformElementHelper::hasProperty().
   *
   * @param array $arguments
   *   The array of arguments to run through hasProperty().
   * @param bool $expected
   *   The expected result from calling the function.
   *
   * @see WebformElementHelper::HasProperty()
   *
   * @dataProvider providerHasProperty
   */
  public function testHasProperty(array $arguments, $expected) {
    $result = WebformElementHelper::hasProperty($arguments[0], $arguments[1], $arguments[2]);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testConvertRenderMarkupToStrings().
   *
   * @see testHasProperty()
   */
  public function providerHasProperty() {
    return [
      [
        [[], '#required', NULL],
        FALSE,
        'Does not have #required',
      ],
      [
        [['#required' => TRUE], '#required', NULL],
        TRUE,
        'Has #required',
      ],
      [
        [['#required' => TRUE], '#required', 'value'],
        FALSE,
        '#required !== value',
      ],
      [
        [['#required' => 'value'], '#required', 'value'],
        TRUE,
        '#required === value',
      ],
      [
        [['nested' => ['#required' => TRUE]], '#required', NULL],
        TRUE,
        'Has nested #required',
      ],
      [
        [['nested' => ['#required' => 'value']], '#required', 'value'],
        TRUE,
        'nested #required === value',
      ],

    ];
  }

}
