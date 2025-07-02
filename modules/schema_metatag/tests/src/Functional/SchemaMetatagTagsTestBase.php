<?php

namespace Drupal\Tests\schema_metatag\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class to test all of the meta tags that are in a specific module.
 */
abstract class SchemaMetatagTagsTestBase extends BrowserTestBase {

  /**
   * The Property Type Manager.
   *
   * @var \Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeManage
   */
  protected $propertyTypeManager;

  /**
   * The Metatag Manager.
   *
   * @var \Drupal\metatag\MetatagTagPluginManager
   */
  protected $metatagTagManager;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // For submodules that extend this it isn't possible to easily extend this
    // array with more modules like was possible with Drupal 7's setUp() method.
    // Instead the dependencies for this test class are simplified by first
    // enabling the 'node' module in 'schema_metatag_test' so that the access
    // permission noted below is made available, and then by adding the relevant
    // submodule for the individual test.
    //
    // This is needed for the 'access content' permission.
    'node',

    // Dependencies.
    'token',
    'metatag',

    // This module.
    'schema_metatag',
    'schema_metatag_test',
  ];

  /**
   * The name of the module being tested.
   *
   * @var string
   */
  public $moduleName = '';

  /**
   * The group being tested.
   *
   * @var string
   */
  public $groupName = '';

  /**
   * All of the property types which will be tested.
   *
   * @var array
   *   A key/value array of the id of the tag and the property type used to
   *   create it.
   *
   * @see \Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeInterface.
   */
  public $propertyTypes = [];

  /**
   * Find all of the property types which will be tested.
   */
  public function getPropertyTypes() {
    $property_types = [];
    $definitions = $this->metatagTagManager()->getDefinitions();
    foreach ($definitions as $tag_name => $definition) {
      if ($definition['group'] == $this->groupName) {
        $property_types[$tag_name] = $definition['property_type'];
      }
    }
    return $property_types;
  }

  /**
   * Specific tree parents for tests.
   *
   * @var array
   *   A key/value array of the id of the tag and the tree parent used to
   *   create it.
   */
  public $treeParent = [];

  /**
   * Find tree parents for tests.
   */
  public function getTreeParent() {
    $property_types = [];
    $definitions = $this->metatagTagManager()->getDefinitions();
    foreach ($definitions as $tag_name => $definition) {
      if ($definition['group'] == $this->groupName) {
        if (!empty($definition['tree_parent'])) {
          $property_types[$tag_name] = array_shift($definition['tree_parent']);
        }
      }
    }
    return $property_types;
  }

  /**
   * The PropertyTypeManager.
   *
   * @var Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeManager
   *   The Property Type Manager service.
   */
  public function propertyTypeManager() {
    return $this->propertyTypeManager;
  }

  /**
   * The Metatag Tag Manager.
   *
   * @var \Drupal\metatag\MetatagTagPluginManager
   *   The Metatag Tag Manager service.
   */
  public function metatagTagManager() {
    return $this->metatagTagManager;
  }

  /**
   * Convert the tag_name into the camelCase key used in the JSON array.
   *
   * @param string $tag_name
   *   The name of the tag.
   *
   * @return string
   *   The key used in the JSON array for this tag.
   */
  public function getKey($tag_name) {
    $key = str_replace($this->moduleName . '_', '', $tag_name);
    $parts = explode('_', $key);
    foreach ($parts as $i => $part) {
      $parts[$i] = $i > 0 ? ucfirst($part) : $part;
    }
    $key = implode($parts);
    if (in_array($key, ['type', 'id'])) {
      $key = '@' . $key;
    }
    return $key;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->propertyTypeManager = \Drupal::service('plugin.manager.schema_property_type');
    $this->metatagTagManager = \Drupal::service('plugin.manager.metatag.tag');
    $this->propertyTypes = $this->getPropertyTypes();
    $this->treeParent = $this->getTreeParent();

    // Initiate session with a user who can manage metatags and access content.
    $permissions = [
      'administer site configuration',
      'administer meta tags',
      'access content',
    ];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Create a content type to test with.
    $this->createContentType(['type' => 'page']);
    $this->drupalCreateNode([
      'title' => 'Node 1!',
      'type' => 'page',
      'promote' => 1,
    ]);

    // Make sure the home page is a valid route in case we want to test it.
    $this->config('system.site')->set('page.front', '/node')->save();
    $this->clear();
  }

  /**
   * Confirm that tags can be saved and that the output of each tag is correct.
   */
  public function testTagsInputOutput() {

    if (empty($this->propertyTypes)) {
      $this->markTestSkipped('Not enough information to test.');
      return;
    }

    $paths = $this->getPaths();
    foreach ($paths as $item) {
      [$config_path, $rendered_path, $save_message] = $item;

      // Load the config page.
      $this->drupalGet($config_path);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->elementExists('xpath', '//input[@type="submit"][@value="Save"]');

      // Configure all the tag values and post the results.
      $expected_output_values = $raw_values = $form_values = [];
      $form_values = [];
      foreach ($this->propertyTypes as $tag_name => $property_type) {

        // Transform the tag_name to the camelCase key used in the form.
        $key = $this->getKey($tag_name);

        // Find the name of the property type and use it to
        // identify a valid test value, and determine what the rendered output
        // should look like. Store the rendered value so we can compare it to
        // the output. Store the raw value so we can check that it exists in the
        // config form.
        $property_plugin = $this->propertyTypeManager()->createInstance($property_type);
        $type = array_key_exists($tag_name, $this->treeParent) ? $this->treeParent[$tag_name] : $property_plugin->getTreeParent();
        $test_type = is_array($type) ? array_shift($type) : $type;
        $test_value = $property_plugin->testValue($test_type);
        // Store the input value.
        $raw_values[$tag_name] = $test_value;
        // Adjust the input value as necessary to transform it to the
        // expected output value, and store that.
        $processed_value = $property_plugin->processedTestValue($test_value);
        $expected_output_values[$key] = $property_plugin->outputValue($processed_value);

        // Rewrite the test values to match the way the form elements are
        // structured.
        // @todo Refactor as a recursive function with unlimited depth.
        if (!is_array($test_value)) {
          $form_values[$tag_name] = $test_value;
        }
        else {
          foreach ($test_value as $key => $value) {
            if (is_array($value)) {
              foreach ($value as $key2 => $value2) {
                if (is_array($value2)) {
                  foreach ($value2 as $key3 => $value3) {
                    if (is_array($value3)) {
                      foreach ($value3 as $key4 => $value4) {
                        $keystring = implode('][', [$key, $key2, $key3, $key4]);
                        $form_values[$tag_name . '[' . $keystring . ']'] = $value4;
                      }
                    }
                    else {
                      $keystring = implode('][', [$key, $key2, $key3]);
                      $form_values[$tag_name . '[' . $keystring . ']'] = $value3;
                    }
                  }
                }
                else {
                  $keystring = implode('][', [$key, $key2]);
                  $form_values[$tag_name . '[' . $keystring . ']'] = $value2;
                }
              }
            }
            else {
              $keystring = implode('][', [$key]);
              $form_values[$tag_name . '[' . $keystring . ']'] = $value;
            }
          }
        }
      }

      $this->submitForm($form_values, 'Save');
      $this->assertSession()->pageTextContains($save_message);

      // Load the config page to confirm the settings got saved.
      $this->drupalGet($config_path);
      foreach ($this->propertyTypes as $tag_name => $property_type) {
        // Check that simple string test values exist in the form to see that
        // form values were saved accurately. Don't try to recurse through all
        // arrays, more complicated values will be tested from the JSON output
        // they create.
        if (is_string($raw_values[$tag_name])) {
          $string = strtr('//*[@name=":tag_name"]', [':tag_name' => $tag_name]);
          $elements = $this->xpath($string);
          $value = count($elements) ? $elements[0]->getValue() : NULL;
          $this->assertEquals($value, $raw_values[$tag_name]);
        }
      }

      // Load the rendered page to see if the JSON-LD is displayed correctly.
      $this->drupalGet($rendered_path);
      $this->assertSession()->statusCodeEquals(200);

      // Make sure JSON-LD is present and can be decoded.
      $this->assertSession()->elementExists('xpath', '//script[@type="application/ld+json"]');
      $elements = $this->xpath('//script[@type="application/ld+json"]');
      $this->assertEquals(count($elements), 1);
      $json = json_decode($elements[0]->getHtml(), TRUE);
      $this->assertNotEmpty($json);
      $output_values = $json['@graph'][0];

      // Compare input and output values.
      foreach ($this->propertyTypes as $tag_name => $property_type) {
        $key = $this->getKey($tag_name);
        $this->assertEquals($output_values[$key], $expected_output_values[$key]);
      }

    }

    $this->drupalLogout();
  }

  /**
   * Paths to test.
   *
   * Tags that need to be tested on other paths can extend this method.
   *
   * [$config_path, $rendered_path, $message]
   *
   * Examples:
   * // Global options.
   * [
   *   'admin/config/search/metatag/global',
   *   'path/that/must/exist',
   *   'Saved the Global Metatag defaults.',
   * ],
   * // The front page.
   * [
   *   'admin/config/search/metatag/front',
   *   '<front>',
   *   'Saved the Front page Metatag defaults.',
   * ],
   */
  public function getPaths() {
    return [
      // The node page.
      [
        'admin/config/search/metatag/node',
        'node/1',
        'Saved the Content Metatag defaults',
      ],
    ];
  }

  /**
   * A way to clear caches.
   */
  protected function clear() {
    $this->rebuildContainer();
  }

}
