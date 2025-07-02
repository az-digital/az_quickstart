<?php

namespace Drupal\Tests\smart_title\Functional;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;

/**
 * Tests the module's title hide functionality.
 *
 * @group smart_title
 */
class SmartTitleConfigTest extends SmartTitleBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->adminUser);

    // Enable Smart Title for the test_page content type's teaser.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/types/manage/test_page/display/teaser');
    $this->submitForm([
      'smart_title__enabled' => TRUE,
    ], 'Save');
    $this->submitForm([
      'fields[smart_title][weight]' => '-5',
      'fields[smart_title][region]' => 'content',
    ], 'Save');
    $teaser_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load('node.' . $this->testPageNode->getType() . '.teaser');
    assert($teaser_display instanceof EntityViewDisplayInterface);
    $smart_title_enabled = $teaser_display->getThirdPartySetting('smart_title', 'enabled', FALSE);
    $this->assertTrue($smart_title_enabled);
  }

  /**
   * Test saved configuration.
   *
   * @dataProvider providerSettingsTestCases
   */
  public function testSavedConfiguration($input, $expectation) {
    $invalid_values = [];
    $this->drupalGet('admin/structure/types/manage/test_page/display/teaser');

    foreach ($input as $setting_key => $setting_value) {
      switch ($setting_key) {
        case 'smart_title__tag':
          if (!isset(_smart_title_tag_options()[$input['smart_title__tag']])) {
            $invalid_values[] = $setting_key;
          }
          break;
      }
    }

    // Open Smart Title settings edit.
    $this->click('[name="smart_title_settings_edit"]');

    if (!empty($invalid_values)) {
      // Test that exception is thrown.
      try {
        $this->submitForm([
          "fields[smart_title][settings_edit_form][settings][smart_title__tag]" => $input['smart_title__tag'],
          "fields[smart_title][settings_edit_form][settings][smart_title__classes]" => $input['smart_title__classes'],
          "fields[smart_title][settings_edit_form][settings][smart_title__link]" => $input['smart_title__link'],
        ], 'Save');
        $this->fail('Expected exception has not been thrown.');
      }
      catch (\Exception $e) {
      }

      // Let's save the other values.
      $edit = [];

      foreach ($input as $key => $value) {
        if (in_array($key, $invalid_values)) {
          continue;
        }
        $edit["fields[smart_title][settings_edit_form][settings][$key]"] = $value;
      }

      $this->submitForm($edit, 'Save');
    }
    else {
      $this->submitForm([
        "fields[smart_title][settings_edit_form][settings][smart_title__tag]" => $input['smart_title__tag'],
        "fields[smart_title][settings_edit_form][settings][smart_title__classes]" => $input['smart_title__classes'],
        "fields[smart_title][settings_edit_form][settings][smart_title__link]" => $input['smart_title__link'],
      ], 'Save');
    }

    // Verify saved settings.
    $this->assertSmartTitleExpectedConfigs($expectation);

    // Re-save form again.
    $this->drupalGet('admin/structure/types/manage/test_page/display/teaser');
    $this->submitForm([], 'Save');

    // Verify saved settings again.
    $this->assertSmartTitleExpectedConfigs($expectation);
  }

  /**
   * Assert Smart Title expected configs.
   *
   * @param array $expected_settings
   *   Settings to verify (teaser view mode).
   */
  public function assertSmartTitleExpectedConfigs(array $expected_settings) {
    // Verify saved settings.
    $teaser_display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load('node.' . $this->testPageNode->getType() . '.teaser');
    assert($teaser_display instanceof EntityViewDisplayInterface);
    $saved_settings = $teaser_display->getThirdPartySetting('smart_title', 'settings', []);
    $this->assertEquals($saved_settings, [
      'smart_title__tag' => $expected_settings['smart_title__tag'],
      'smart_title__classes' => $expected_settings['smart_title__classes'],
      'smart_title__link' => $expected_settings['smart_title__link'],
    ]);

    // Verify expected field settings summary.
    $web_assert = $this->assertSession();
    $web_assert->elementTextContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', _smart_title_defaults('', NULL, 'smart_title__tag')['label'] . ': ' . $expected_settings['smart_title__tag']);
    // Css classes.
    if ((bool) $expected_settings['smart_title__classes']) {
      $web_assert->elementTextContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', _smart_title_defaults('', NULL, 'smart_title__classes')['label'] . ': ' . implode(', ', $expected_settings['smart_title__classes']));
    }
    else {
      $web_assert->elementTextNotContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', _smart_title_defaults('', NULL, 'smart_title__classes')['label']);
    }
    // Link.
    if ((bool) $expected_settings['smart_title__link']) {
      $web_assert->elementTextContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', _smart_title_defaults('', NULL, 'smart_title__link')['label']);
    }
    else {
      $web_assert->elementTextNotContains('css', '[data-drupal-selector="edit-fields-smart-title"] .field-plugin-summary', _smart_title_defaults('', NULL, 'smart_title__link')['label']);
    }

    // Test that Smart Title is displayed on the /node page (teaser view mode)
    // for admin user.
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains($this->testPageNode->label());
    $css_selector_components = $expected_settings['smart_title__classes'];
    array_unshift($css_selector_components, $expected_settings['smart_title__tag']);
    $article_title = $this->xpath($this->cssSelectToXpath('article ' . implode('.', $css_selector_components)));
    $this->assertEquals($this->testPageNode->label(), $article_title[0]->getText());
  }

  /**
   * Returns the settings test cases.
   *
   * @return array[]
   *   Array of data sets to test, each of which is a 'label' indexed array
   *   with the following elements:
   *   - An array of input data, with smart_title__tag, smart_title__classes and
   *     smart_title__link submission values.
   *   - An array of expected settings of the configuration keys mentioned
   *     above.
   */
  public static function providerSettingsTestCases() {
    return [
      'No class, no link' => [
        'input' => [
          'smart_title__tag' => 'span',
          'smart_title__classes' => '',
          'smart_title__link' => 0,
        ],
        'expectation' => [
          'smart_title__tag' => 'span',
          'smart_title__classes' => [],
          'smart_title__link' => FALSE,
        ],
      ],
      'Single class without link' => [
        'input' => [
          'smart_title__tag' => 'h3',
          'smart_title__classes' => 'smart-title__test',
          'smart_title__link' => 0,
        ],
        'expectation' => [
          'smart_title__tag' => 'h3',
          'smart_title__classes' => ['smart-title__test'],
          'smart_title__link' => FALSE,
        ],
      ],
      'Multiple classes, link' => [
        'input' => [
          'smart_title__tag' => 'div',
          'smart_title__classes' => 'smart-title__test with   multiple classes  and space',
          'smart_title__link' => 1,
        ],
        'expectation' => [
          'smart_title__tag' => 'div',
          'smart_title__classes' => [
            'smart-title__test',
            'with',
            'multiple',
            'classes',
            'and',
            'space',
          ],
          'smart_title__link' => TRUE,
        ],
      ],
      'Invalid tag and link values' => [
        'input' => [
          'smart_title__tag' => 'invalid',
          'smart_title__classes' => 'valid',
          'smart_title__link' => 'invalid',
        ],
        'expectation' => [
          'smart_title__tag' => 'h2',
          'smart_title__classes' => ['valid'],
          'smart_title__link' => TRUE,
        ],
      ],
    ];
  }

}
