<?php

namespace Drupal\Tests\webform\Functional\Field;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests the webform (entity reference) field.
 *
 * @group webform
 */
class WebformFieldTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field_ui'];

  /**
   * Tests the webform (entity reference) field.
   */
  public function testWebformField() {
    $assert_session = $this->assertSession();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $this->drupalCreateContentType(['type' => 'page']);

    FieldStorageConfig::create([
      'field_name' => 'field_webform',
      'type' => 'webform',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_webform',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'webform',
    ])->save();
    $form_display = $display_repository->getFormDisplay('node', 'page');
    $form_display->setComponent('field_webform', [
      'type' => 'webform_entity_reference_select',
      'settings' => [],
    ]);
    $form_display->save();

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */

    // Check that webform select menu is visible.
    $this->drupalGet('/node/add/page');
    $this->assertNoCssSelect('#edit-field-webform-0-target-id optgroup');
    $assert_session->optionExists('edit-field-webform-0-target-id', 'contact');

    // Add category to 'contact' webform.
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');
    $webform->set('categories', ['{Some category}']);
    $webform->save();

    // Check that webform select menu included optgroup.
    $this->drupalGet('/node/add/page');
    $this->assertCssSelect('#edit-field-webform-0-target-id optgroup[label="{Some category}"]');

    // Create a second webform.
    $webform_2 = $this->createWebform();

    // Check that webform 2 is included in the select menu.
    $this->drupalGet('/node/add/page');
    $assert_session->optionExists('edit-field-webform-0-target-id', 'contact');
    $assert_session->optionExists('edit-field-webform-0-target-id', $webform_2->id());

    // Limit the webform select menu to only the contact form.
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $this->submitForm([], 'field_webform_settings_edit');
    $this->submitForm(['fields[field_webform][settings_edit_form][settings][webforms][]' => ['contact']], 'Save');

    // Check that webform 2 is NOT included in the select menu.
    $this->drupalGet('/node/add/page');
    $assert_session->optionExists('edit-field-webform-0-target-id', 'contact');
    $assert_session->optionNotExists('edit-field-webform-0-target-id', $webform_2->id());
  }

}
