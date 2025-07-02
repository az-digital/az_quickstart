<?php

namespace Drupal\Tests\webform_ui\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform UI actions element.
 *
 * @group webform_ui
 */
class WebformUiElementActionsTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_ui'];

  /**
   * Tests actions element.
   */
  public function testActionsElements() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    $values = ['id' => 'test'];
    $elements = [
      'text_field' => [
        '#type' => 'textfield',
        '#title' => 'textfield',
      ],
    ];
    $this->createWebform($values, $elements);

    // Check that submit buttons are customizable.
    $this->drupalGet('/admin/structure/webform/manage/test');
    $assert_session->linkExists('Customize');

    // Disable actions element.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.excluded_elements.webform_actions', 'webform_actions')
      ->save();

    // Check that submit buttons are not customizable.
    $this->drupalGet('/admin/structure/webform/manage/test');
    $assert_session->linkNotExists('Customize');
  }

}
