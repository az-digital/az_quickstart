<?php

namespace Drupal\Tests\webform\FunctionalJavascript\States;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests for webform submission conditions (#states) validator.
 *
 * @group webform_javascript
 */
class WebformStatesCustomJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_states_server_custom',
  ];

  /**
   * Tests custom states.
   */
  public function testCustomStates() {
    $session = $this->getSession();
    $page = $session->getPage();

    $this->drupalGet('/webform/test_states_server_custom');

    /* ********************************************************************** */
    // Pattern (^[a-z]+$).
    /* ********************************************************************** */

    // Check pattern dependent is not visible.
    $dependent_pattern = $page->findField('edit-dependent-pattern');
    $this->assertFalse($dependent_pattern->isVisible());

    // Check pattern dependent is visible.
    $page->fillField('edit-trigger-pattern', 'a');
    $this->assertTrue($dependent_pattern->isVisible());

    // Check pattern dependent is not visible.
    $page->fillField('edit-trigger-pattern', '1');
    $this->assertFalse($dependent_pattern->isVisible());

    /* ********************************************************************** */
    // !Pattern (^$).
    /* ********************************************************************** */

    // Check !pattern dependent is not visible.
    $dependent_not_pattern = $page->findField('edit-dependent-not-pattern');
    $this->assertFalse($dependent_not_pattern->isVisible());

    // Check !pattern dependent is visible.
    $page->fillField('edit-trigger-not-pattern', 'a');
    $this->assertTrue($dependent_not_pattern->isVisible());

    // Check !pattern dependent is not visible.
    $page->fillField('edit-trigger-not-pattern', '');
    $this->assertFalse($dependent_not_pattern->isVisible());

    /* ********************************************************************** */
    // Less (< 10).
    /* ********************************************************************** */

    // Check less dependent is not visible.
    $dependent_less = $page->findField('edit-dependent-less');
    $this->assertFalse($dependent_less->isVisible());

    // Check less dependent is visible.
    $page->fillField('edit-trigger-less', '5');
    $this->assertTrue($dependent_less->isVisible());

    // Check less dependent is not visible.
    $page->fillField('edit-trigger-less', '11');
    $this->assertFalse($dependent_less->isVisible());

    /* ********************************************************************** */
    // Less/Equal (<= 10).
    /* ********************************************************************** */

    // Check less dependent is not visible.
    $dependent_less_equal = $page->findField('edit-dependent-less-equal');
    $this->assertFalse($dependent_less_equal->isVisible());

    // Check less dependent is visible.
    $page->fillField('edit-trigger-less-equal', '10');
    $this->assertTrue($dependent_less_equal->isVisible());

    // Check less dependent is not visible.
    $page->fillField('edit-trigger-less-equal', '11');
    $this->assertFalse($dependent_less_equal->isVisible());

    /* ********************************************************************** */
    // Greater (> 10).
    /* ********************************************************************** */

    // Check greater dependent is not visible.
    $dependent_greater = $page->findField('edit-dependent-greater');
    $this->assertFalse($dependent_greater->isVisible());

    // Check greater dependent is visible.
    $page->fillField('edit-trigger-greater', '11');
    $this->assertTrue($dependent_greater->isVisible());

    // Check greater dependent is not visible.
    $page->fillField('edit-trigger-greater', '5');
    $this->assertFalse($dependent_greater->isVisible());

    /* ********************************************************************** */
    // Greater/Equal (> 10).
    /* ********************************************************************** */

    // Check greater dependent is not visible.
    $dependent_greater_equal = $page->findField('edit-dependent-greater-equal');
    $this->assertFalse($dependent_greater_equal->isVisible());

    // Check greater dependent is visible.
    $page->fillField('edit-trigger-greater-equal', '10');
    $this->assertTrue($dependent_greater_equal->isVisible());

    // Check greater dependent is not visible.
    $page->fillField('edit-trigger-greater-equal', '5');
    $this->assertFalse($dependent_greater_equal->isVisible());

    /* ********************************************************************** */
    // Between (10 > & < 20).
    /* ********************************************************************** */

    // Check between dependent is not visible.
    $dependent_between = $page->findField('edit-dependent-between');
    $dependent_not_between = $page->findField('edit-dependent-not-between');
    $this->assertFalse($dependent_between->isVisible());
    $this->assertFalse($dependent_not_between->isVisible());

    // Check between dependent is visible.
    $page->fillField('edit-trigger-between', '11');
    $this->assertTrue($dependent_between->isVisible());
    $this->assertFalse($dependent_not_between->isVisible());

    // Check between dependent is not visible.
    $page->fillField('edit-trigger-between', '5');
    $this->assertFalse($dependent_between->isVisible());
    $this->assertTrue($dependent_not_between->isVisible());

    // Check between dependent is not visible.
    $page->fillField('edit-trigger-between', '');
    $this->assertFalse($dependent_between->isVisible());
    $this->assertFalse($dependent_not_between->isVisible());

    // Check between dependent is not visible.
    $page->fillField('edit-trigger-between', '21');
    $this->assertFalse($dependent_between->isVisible());
    $this->assertTrue($dependent_not_between->isVisible());
  }

}
