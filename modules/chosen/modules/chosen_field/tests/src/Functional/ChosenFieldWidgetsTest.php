<?php

namespace Drupal\Tests\chosen_field\Functional;

use Drupal\Tests\field\Functional\FieldTestBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Test the Chosen widgets.
 *
 * @group Chosen
 */
class ChosenFieldWidgetsTest extends FieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'options',
    'entity_test',
    'taxonomy',
    'field_ui',
    'options_test',
    'chosen_field',
  ];

  /**
   * A field with cardinality 1 to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $card_1;

  /**
   * A field with cardinality 2 to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $card_2;

  /**
   * Function used to setup before running the test.
   */
  protected function setUp(): void {
    parent::setUp();

    // Field storage with cardinality 1.
    $this->card_1 = \Drupal::entityTypeManager()->getStorage('field_storage_config')->create([
      'field_name' => 'card_1',
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 1,
      'settings' => [
        'allowed_values' => [
          // Make sure that 0 works as an option.
          0 => 'Zero',
          1 => 'One',
          // Make sure that option text is properly sanitized.
          2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>',
          // Make sure that HTML entities in option text are not double-encoded.
          3 => 'Some HTML encoded markup with &lt; &amp; &gt;',
        ],
      ],
    ]);
    $this->card_1->save();

    // Field storage with cardinality 2.
    $this->card_2 = \Drupal::entityTypeManager()->getStorage('field_storage_config')->create([
      'field_name' => 'card_2',
      'entity_type' => 'entity_test',
      'type' => 'list_integer',
      'cardinality' => 2,
      'settings' => [
        'allowed_values' => [
          // Make sure that 0 works as an option.
          0 => 'Zero',
          1 => 'One',
          // Make sure that option text is properly sanitized.
          2 => 'Some <script>dangerous</script> & unescaped <strong>markup</strong>',
        ],
      ],
    ]);
    $this->card_2->save();

    // Create a web user.
    $this->drupalLogin($this->drupalCreateUser(['view test entity', 'administer entity_test content']));
  }

  /**
   * Tests the 'chosen_select' widget (single select).
   */
  public function testSelectListSingle() {
    // Create an instance of the 'single value' field.
    $instance = \Drupal::entityTypeManager()->getStorage('field_config')->create([
      'field_storage' => $this->card_1,
      'bundle' => 'entity_test',
    ]);
    $instance->setRequired(TRUE);
    $instance->save();

    \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('entity_test.entity_test.default')
      ->setComponent($this->card_1->getName(), [
        'type' => 'chosen_select',
      ])
      ->save();

    // Create an entity.
    $entity = \Drupal::entityTypeManager()->getStorage('entity_test')->create([
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();
    $entity_init = clone $entity;

    // Display form.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    // A required field without any value has a "none" option.
    $this->assertSession()->elementExists('xpath', '//select[@id="edit-card-1"]//option[@value="_none" and text()="- Select a value -"]');

    // With no field data, nothing is selected.
    $options = ['_none', 0, 1, 2];
    $id = 'edit-card-1';
    foreach ($options as $option) {
      $option_field = $this->assertSession()->optionExists($id, $option);
      $message = "Option $option for field $id is not selected.";
      $this->assertEmpty($option_field->hasAttribute('selected'), $message);
    }

    $this->assertSession()->responseContains('Some dangerous &amp; unescaped markup');

    // Submit form: select invalid 'none' option.
    $edit = ['card_1' => '_none'];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->responseContains((string) new FormattableMarkup('@title field is required.', ['@title' => $instance->getName()]));

    // Submit form: select first option.
    $edit = ['card_1' => 0];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_1', [0]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    // A required field with a value has no 'none' option.
    $this->assertSession()->elementNotExists('xpath', '//select[@id="edit-card-1"]//option[@value="_none"]');

    $id = 'edit-card-1';
    $option = 0;
    $option_field = $this->assertSession()->optionExists($id, $option);
    $message = "Option $option for field $id is selected.";
    $this->assertNotEmpty($option_field->hasAttribute('selected'), $message);

    $options = [1, 2];
    $id = 'edit-card-1';
    foreach ($options as $option) {
      $option_field = $this->assertSession()->optionExists($id, $option);
      $message = "Option $option for field $id is not selected.";
      $this->assertEmpty($option_field->hasAttribute('selected'), $message);
    }

    // Make the field non required.
    $instance->setRequired(FALSE);
    $instance->save();

    // Display form.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    // A non-required field has a 'none' option.
    $this->assertSession()->elementExists('xpath', '//select[@id="edit-card-1"]//option[@value="_none" and text()="- None -"]');
    // Submit form: Unselect the option.
    $edit = ['card_1' => '_none'];
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_1', []);

    // Test optgroups.
    $this->card_1->setSetting('allowed_values', []);
    $this->card_1->setSetting('allowed_values_function', 'options_test_allowed_values_callback');
    $this->card_1->save();

    // Display form: with no field data, nothing is selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');

    $options = [0, 1, 2];
    $id = 'edit-card-1';
    foreach ($options as $option) {
      $option_field = $this->assertSession()->optionExists($id, $option);
      $message = "Option $option for field $id is not selected.";
      $this->assertEmpty($option_field->hasAttribute('selected'), $message);
    }

    $this->assertSession()->responseContains('Some dangerous &amp; unescaped markup');
    $this->assertSession()->responseContains('Group 1');

    // Submit form: select first option.
    $edit = ['card_1' => 0];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_1', [0]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');

    $id = 'edit-card-1';
    $option = 0;
    $option_field = $this->assertSession()->optionExists($id, $option);
    $message = "Option $option for field $id is selected.";
    $this->assertNotEmpty($option_field->hasAttribute('selected'), $message);

    $options = [1, 2];
    $id = 'edit-card-1';
    foreach ($options as $option) {
      $option_field = $this->assertSession()->optionExists($id, $option);
      $message = "Option $option for field $id is not selected.";
      $this->assertEmpty($option_field->hasAttribute('selected'), $message);
    }

    // Submit form: Unselect the option.
    $edit = ['card_1' => '_none'];
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_1', []);
  }

  /**
   * Tests the 'options_select' widget (multiple select).
   */
  function testSelectListMultiple() {
    // Create an instance of the 'multiple values' field.
    $instance = \Drupal::entityTypeManager()->getStorage('field_config')->create([
      'field_storage' => $this->card_2,
      'bundle' => 'entity_test',
    ]);
    $instance->save();

    \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('entity_test.entity_test.default')
      ->setComponent($this->card_2->getName(), [
        'type' => 'chosen_select',
      ])
      ->save();

    // Create an entity.
    $entity = \Drupal::entityTypeManager()->getStorage('entity_test')->create([
      'user_id' => 1,
      'name' => $this->randomMachineName(),
    ]);
    $entity->save();
    $entity_init = clone $entity;

    // Display form: with no field data, nothing is selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');

    $options = [0, 1, 2];
    $id = 'edit-card-2';
    foreach ($options as $option) {
      $option_field = $this->assertSession()->optionExists($id, $option);
      $message = "Option $option for field $id is not selected.";
      $this->assertEmpty($option_field->hasAttribute('selected'), $message);
    }

    $this->assertSession()->responseContains('Some dangerous &amp; unescaped markup');

    // Submit form: select first and third options.
    $edit = ['card_2[]' => [0 => 0, 2 => 2]];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_2', [0, 2]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');

    $id = 'edit-card-2';
    $option = 0;
    $option_field = $this->assertSession()->optionExists($id, $option);
    $message = "Option $option for field $id is selected.";
    $this->assertNotEmpty($option_field->hasAttribute('selected'), $message);

    $option = 1;
    $id = 'edit-card-2';
    $option_field = $this->assertSession()->optionExists($id, $option);
    $message = "Option $option for field $id is not selected.";
    $this->assertEmpty($option_field->hasAttribute('selected'), $message);

    $id = 'edit-card-2';
    $option = 2;
    $option_field = $this->assertSession()->optionExists($id, $option);
    $message = "Option $option for field $id is selected.";
    $this->assertNotEmpty($option_field->hasAttribute('selected'), $message);

    // Submit form: select only first option.
    $edit = ['card_2[]' => [0 => 0]];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_2', [0]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');

    $id = 'edit-card-2';
    $option = 0;
    $option_field = $this->assertSession()->optionExists($id, $option);
    $message = "Option $option for field $id is selected.";
    $this->assertNotEmpty($option_field->hasAttribute('selected'), $message);

    $options = [1, 2];
    $id = 'edit-card-2';
    foreach ($options as $option) {
      $option_field = $this->assertSession()->optionExists($id, $option);
      $message = "Option $option for field $id is not selected.";
      $this->assertEmpty($option_field->hasAttribute('selected'), $message);
    }

    // Submit form: select the three options while the field accepts only 2.
    $edit = ['card_2[]' => [0 => 0, 1 => 1, 2 => 2]];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('this field cannot hold more than 2 values');

    // Submit form: uncheck all options.
    $edit = ['card_2[]' => []];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_2', []);

    // A required select list does not have an empty key.
    $instance->setRequired(TRUE);
    $instance->save();
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');
    $this->assertSession()->elementNotExists('xpath', '//select[@id="edit-card-2"]//option[@value=""]');

    // We do not have to test that a required select list with one option is
    // auto-selected because the browser does it for us.
    // Test optgroups.
    // Use a callback function defining optgroups.
    $this->card_2->setSetting('allowed_values', []);
    $this->card_2->setSetting('allowed_values_function', 'options_test_allowed_values_callback');
    $this->card_2->save();

    $instance->setRequired(FALSE);
    $instance->save();

    // Display form: with no field data, nothing is selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');

    $options = [0, 1, 2];
    $id = 'edit-card-2';
    foreach ($options as $option) {
      $option_field = $this->assertSession()->optionExists($id, $option);
      $message = "Option $option for field $id is not selected.";
      $this->assertEmpty($option_field->hasAttribute('selected'), $message);
    }

    $this->assertSession()->responseContains('Some dangerous &amp; unescaped markup');
    $this->assertSession()->responseContains('Group 1');

    // Submit form: select first option.
    $edit = ['card_2[]' => [0 => 0]];
    $this->submitForm($edit, 'Save');
    $this->assertFieldValues($entity_init, 'card_2', [0]);

    // Display form: check that the right options are selected.
    $this->drupalGet('entity_test/manage/' . $entity->id() . '/edit');

    $id = 'edit-card-2';
    $option = 0;
    $option_field = $this->assertSession()->optionExists($id, $option);
    $message = "Option $option for field $id is selected.";
    $this->assertNotEmpty($option_field->hasAttribute('selected'), $message);

    $options = [1, 2];
    $id = 'edit-card-2';
    foreach ($options as $option) {
      $option_field = $this->assertSession()->optionExists($id, $option);
      $message = "Option $option for field $id is not selected.";
      $this->assertEmpty($option_field->hasAttribute('selected'), $message);
    }
  }

}
