<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class to test all of the meta tags that are in a specific module.
 */
abstract class TagsTestBase extends BrowserTestBase {

  use MetatagHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // This is needed for the 'access content' permission.
    'node',

    // Dependencies.
    'token',

    // Metatag itself.
    'metatag',

    // This module will be used to load a static page which will inherit the
    // global defaults, without loading values from other configs.
    'metatag_test_custom_route',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Debug mode.
   *
   * Used for local testing to see the xpath strings used.
   *
   * @var bool
   */
  protected $debugMode = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Use the test page as the front page.
    $this->config('system.site')->set('page.front', '/test-page')->save();

    // Initiate session with a user who can manage meta tags and access content.
    $permissions = [
      'administer site configuration',
      'administer meta tags',
      'access content',
    ];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);
  }

  /**
   * Confirm that each tag works.
   */
  public function testTags() {
    // Create a content type to test with.
    $this->createContentType(['type' => 'page']);
    $this->drupalCreateNode([
      'title' => 'Hello, world!',
      'type' => 'page',
    ]);

    // Build a list of all tag objects that will be used later on.
    $tag_manager = \Drupal::service('plugin.manager.metatag.tag');
    $all_tags = [];
    foreach ($tag_manager->getDefinitions() as $tag_name => $tag_spec) {
      $all_tags[$tag_name] = $tag_spec['class']::create($this->container, [], $tag_name, $tag_spec);
    }
    if ($this->debugMode) {
      dump(array_keys($all_tags));
    }

    // Test a non-entity path and an entity path. The non-entity path inherits
    // the global meta tags, the entity path inherits from its entity config.
    $paths = [
      [
        // The config form that affects this page.
        'admin/config/search/metatag/global',
        // The message that is shown when the config page is saved.
        'Saved the Global Metatag defaults.',
        // The custom route that exposes this form.
        'metatag_test_custom_route',
      ],
      [
        'admin/config/search/metatag/node',
        'Saved the Content Metatag defaults',
        'node/1',
      ],
    ];

    foreach ($paths as $item) {
      [$form_path, $save_message, $page_path] = $item;

      // Load the global config.
      $this->drupalGet($form_path);
      $this->assertSession()->statusCodeEquals(200);

      $all_values = $tag_values = [];

      // Loop over all of the available meta tags, make sure that they're
      // available on the form.
      foreach ($all_tags as $tag_name => $tag) {
        // Look for each form field.
        foreach ($tag->getTestFormXpath() as $form_field_xpath) {
          if ($this->debugMode) {
            dump([$tag_name => $form_field_xpath]);
          }
          $xpath = $this->xpath($form_field_xpath);
          $this->assertCount(1, $xpath, new FormattableMarkup('One @tag tag form field found using: @xpath', [
            '@tag' => $tag_name,
            '@xpath' => $form_field_xpath,
          ]));
        }

        // Get the key value(s) that will be identified for this tag. Make sure
        // there's a default value so that meta tags that don't return test data
        // don't cause a failure later on; in each case a @todo task will be
        // noted to be completed later.
        $tag_values[$tag_name] = [];
        foreach ($tag->getTestFormData() as $field_name => $field_value) {
          if ($this->debugMode) {
            dump([$field_name => $field_value]);
          }
          $all_values[$field_name] = $field_value;
          $tag_values[$tag_name][$field_name] = $field_value;
        }
      }

      // Submit all of the meta tag values.
      $this->submitForm($all_values, 'Save');

      // Note: if this line fails then check that the failing meta tag has a
      // definition in the relevant *.metatag_tag.schema.yml file.
      $this->assertSession()->pageTextContains($save_message);

      // Load the test page.
      $this->drupalGet($page_path);
      $this->assertSession()->statusCodeEquals(200);

      // First check that the meta tag is present on the page, then check to see
      // if it has the expected output. This helps verify that the meta tag is
      // present, in case the tag is present but the value is incorrect.
      foreach ($all_tags as $tag_name => $tag) {
        foreach ($tag->getTestOutputExistsXpath() as $tag_string) {
          if ($this->debugMode) {
            dump([$tag_name => $tag_string]);
          }
          $xpath = $this->xpath($tag_string);
          $this->assertCount(1, $xpath, new FormattableMarkup('One @tag tag found using: @xpath', [
            '@tag' => $tag_name,
            '@xpath' => $tag_string,
          ]));
        }
        foreach ($tag->getTestOutputValuesXpath($tag_values[$tag_name]) as $output_string) {
          if ($this->debugMode) {
            dump([$tag_name => $output_string]);
          }
          $xpath = $this->xpath($output_string);
          $this->assertCount(1, $xpath, new FormattableMarkup('Tag output for @tag found using: @xpath', [
            '@tag' => $tag_name,
            '@xpath' => $output_string,
          ]));
        }
      }
      continue;
    }
  }

}
