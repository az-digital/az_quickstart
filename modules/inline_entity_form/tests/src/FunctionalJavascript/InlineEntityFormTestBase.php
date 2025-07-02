<?php

namespace Drupal\Tests\inline_entity_form\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base Class for Inline Entity Form Tests.
 */
abstract class InlineEntityFormTestBase extends WebDriverTestBase {

  /**
   * User with permissions to create content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * Field config storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected $fieldStorageConfigStorage;

  /**
   * Field config storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $fieldConfigStorage;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fieldStorageConfigStorage = $this->container->get('entity_type.manager')->getStorage('field_storage_config');
    $this->fieldConfigStorage = $this->container->get('entity_type.manager')->getStorage('field_config');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareSettings() {
    $drupal_version = (float) substr(\Drupal::VERSION, 0, 3);
    if ($drupal_version < 8.8) {
      // Fix entity_reference_autocomplete match_limit schema errors.
      $this->strictConfigSchema = FALSE;
    }
    parent::prepareSettings();
  }

  /**
   * Gets IEF button name.
   *
   * @param string $xpath
   *   Xpath of the button.
   *
   * @return string
   *   The name of the button.
   */
  protected function getButtonName(string $xpath) {
    $retval = '';
    /** @var \SimpleXMLElement[] $elements */
    if ($elements = $this->xpath($xpath)) {
      foreach ($elements[0]->attributes() as $name => $value) {
        if ($name === 'name') {
          $retval = $value;
          break;
        }
      }
    }
    return $retval;
  }

  /**
   * Passes if no node is found for the title.
   *
   * @param string $title
   *   Node title to check.
   * @param string $message
   *   Message to display.
   */
  protected function assertNoNodeByTitle(string $title, $message = '') {
    if (!$message) {
      $message = "No node with title: $title";
    }
    $node = $this->getNodeByTitle($title, TRUE);

    $this->assertEmpty($node, $message);
  }

  /**
   * Passes if a node is found for the title.
   *
   * @param string $title
   *   Node title to check.
   * @param string $content_type
   *   The content type to check.
   * @param string $message
   *   Message to display.
   */
  protected function assertNodeByTitle(string $title, $content_type = NULL, $message = '') {
    if (!$message) {
      $message = "Node with title found: $title";
    }
    $node = $this->getNodeByTitle($title, TRUE);
    $this->assertNotEmpty($node, $message);
    if ($content_type) {
      $this->assertEquals($node->bundle(), $content_type, "Node is correct content type: $content_type");
    }
  }

  /**
   * Ensures that an entity with a specific label exists.
   *
   * @param string $label
   *   The label of the entity.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   (optional) The bundle this entity should have.
   */
  protected function assertEntityByLabel(string $label, $entity_type_id = 'node', $bundle = NULL) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_type = $entity_type_manager->getDefinition($entity_type_id);
    $label_key = $entity_type->getKey('label');
    $bundle_key = $entity_type->getKey('bundle');

    $query = $entity_type_manager->getStorage($entity_type_id)->getQuery()->accessCheck(FALSE);
    $query->condition($label_key, $label);

    if ($bundle && $bundle_key) {
      $query->condition($bundle_key, $bundle);
    }

    $result = $query->execute();
    $this->assertNotEmpty($result);
  }

  /**
   * Checks for check correct fields on form displays.
   *
   * This checks based on exported config in the
   * inline_entity_form_test module.
   *
   * @param string $form_display
   *   The form display to check.
   * @param string $prefix
   *   The config prefix.
   */
  protected function checkFormDisplayFields(string $form_display, string $prefix) {
    $assert_session = $this->assertSession();
    $form_display_fields = [
      'node.ief_test_custom.default' => [
        'expected' => [
          '[title][0][value]',
          '[uid][0][target_id]',
          '[created][0][value][date]',
          '[created][0][value][time]',
          '[promote][value]',
          '[sticky][value]',
          '[positive_int][0][value]',
        ],
        'unexpected' => [],
      ],
      'node.ief_test_custom.inline' => [
        'expected' => [
          '[title][0][value]',
          '[positive_int][0][value]',
        ],
        'unexpected' => [
          '[uid][0][target_id]',
          '[created][0][value][date]',
          '[created][0][value][time]',
          '[promote][value]',
          '[sticky][value]',
        ],
      ],
    ];

    if (empty($form_display_fields[$form_display])) {
      throw new \Exception('Form display not found: ' . $form_display);
    }

    $fields = $form_display_fields[$form_display];
    foreach ($fields['expected'] as $expected_field) {
      $assert_session->fieldExists($prefix . $expected_field);
    }
    foreach ($fields['unexpected'] as $unexpected_field) {
      $assert_session->fieldNotExists($prefix . $unexpected_field);
    }
  }

  /**
   * Wait for an IEF table row to appear.
   *
   * @param string $title
   *   The title of the row for which to wait.
   */
  protected function waitForRowByTitle(string $title) {
    $this->assertNotEmpty($this->assertSession()->waitForElement('xpath', '//td[@class="inline-entity-form-node-label" and text()="' . $title . '"]'));
  }

  /**
   * Wait for an IEF table row to disappear.
   *
   * @param string $title
   *   The title of the row for which to wait.
   */
  protected function waitForRowRemovedByTitle(string $title) {
    $this->assertNotEmpty($this->assertSession()->waitForElementRemoved('xpath', '//td[@class="inline-entity-form-node-label" and text()="' . $title . '"]'));
  }

  /**
   * Asserts that an IEF table row exists.
   *
   * @param string $title
   *   The title of the row to check.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The <td> element containing the label for the IEF row.
   */
  protected function assertRowByTitle(string $title) {
    $this->assertNotEmpty($element = $this->assertSession()->elementExists('xpath', '//td[@class="inline-entity-form-node-label" and text()="' . $title . '"]'));
    return $element;
  }

  /**
   * Asserts that an IEF table row does not exist.
   *
   * @param string $title
   *   The title of the row to check.
   */
  protected function assertNoRowByTitle(string $title) {
    $this->assertSession()->elementNotExists('xpath', '//td[@class="inline-entity-form-node-label" and text()="' . $title . '"]');
  }

  /**
   * Returns xpath selector to the index-th input with label.
   *
   * Note: index starts at 1.
   *
   * @param string $label
   *   The label text to select.
   * @param int $index
   *   The index of the input to select.
   *
   * @return string
   *   The xpath selector for the input to select.
   */
  protected function getXpathForNthInputByLabelText(string $label, int $index) {
    return "(//*[@id=string((//label[.='$label']/@for)[$index])])";
  }

  /**
   * Returns xpath selector to the first input with an auto-complete.
   *
   * @return string
   *   The xpath selector for the first input with an auto-complete.
   */
  protected function getXpathForAutoCompleteInput() {
    return '(//input[@data-autocomplete-path])';
  }

  /**
   * Returns xpath selector to the index-th button with button text value.
   *
   * Note: index starts at 1.
   *
   * @param string $value
   *   The text on the button to select.
   * @param int $index
   *   The index of the button to select.
   *
   * @return string
   *   The xpath selector for the button to select.
   */
  protected function getXpathForButtonWithValue(string $value, int $index) {
    return "(//input[@type='submit' and @value='$value'])[$index]";
  }

  /**
   * Returns xpath selector for fieldset label.
   *
   * @param string $label
   *   The label text to select.
   * @param int $index
   *   The index of the fieldset label to select.
   *
   * @return string
   *   The xpath selector for the fieldset label to select.
   */
  protected function getXpathForFieldsetLabel(string $label, int $index) {
    return "(//fieldset/legend/span[.='{$label}'])[$index]";
  }

}
