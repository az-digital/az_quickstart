<?php

namespace Drupal\az_publication\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;

/**
 * Defines the 'az_entity_role_reference' entity field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 */
#[FieldType(
  id: "az_entity_role_reference",
  label: new TranslatableMarkup("Entity Role reference"),
  description: new TranslatableMarkup("An entity field containing an entity reference and a contributor role."),
  category: "reference",
  default_widget: "az_entity_role_inline_entity_form_complex",
  default_formatter: "az_entity_role_reference_label",
  list_class: EntityReferenceFieldItemList::class,
)]
class AZEntityRoleReferenceItem extends EntityReferenceItem implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['role'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('contributor Role'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['role'] = [
      'description' => 'The role of the target entity.',
      'type' => 'varchar_ascii',
      'length' => 255,
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values' => [],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);
    $allowed_roles = $this->getSetting('allowed_values');

    $element['allowed_values'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed Roles'),
      '#default_value' => $this->allowedRolesString($allowed_roles),
      '#rows' => 10,
      '#element_validate' => [[static::class, 'validateAllowedRoles']],
      '#description' => $this->t('The possible roles this field can contain. One value per line, in the format key|label.'),
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * Element_validate callback for options field allowed roles.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\Core\Render\Element\FormElement::processPattern()
   */
  public static function validateAllowedRoles($element, FormStateInterface $form_state) {
    $roles = static::extractAllowedRoles($element['#value']);

    if (!is_array($roles)) {
      $form_state->setError($element, new TranslatableMarkup('Allowed roles list: invalid input.'));
    }
    else {
      // Check that keys are valid for the field type.
      foreach ($roles as $key => $value) {
        if ($error = static::validateAllowedRole($key)) {
          $form_state->setError($element, $error);
          break;
        }
      }

      $form_state->setValueForElement($element, $roles);
    }
  }

  /**
   * Extracts the allowed roles array from the allowed_roles element.
   *
   * @param string $string
   *   The raw string to extract roles from.
   *
   * @return array|null
   *   The array of extracted key/value pairs, or NULL if the string is invalid.
   */
  protected static function extractAllowedRoles($string) {
    $roles = [];

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $position => $text) {
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim key and value to avoid unwanted spaces issues.
        $key = trim($matches[1]);
        $value = trim($matches[2]);
        $roles[$key] = $value;
      }
      else {
        return NULL;
      }
    }

    return $roles;
  }

  /**
   * Checks whether a candidate allowed role is valid.
   *
   * @param string $option
   *   The option value entered by the user.
   *
   * @return string|null
   *   The error message if the specified value is invalid, NULL otherwise.
   */
  protected static function validateAllowedRole($option): ?string {
    if (mb_strlen($option) > 255) {
      return new TranslatableMarkup('Allowed values list: each key must be a string at most 255 characters long.');
    }
    return NULL;
  }

  /**
   * Generates a string representation of an array of 'allowed roles'.
   *
   * This string format is suitable for edition in a textarea.
   *
   * @param array $roles
   *   An array of roles, where array keys are roles and array values are
   *   labels.
   *
   * @return string
   *   The string representation of the $roles array:
   *    - Roles are separated by a newline.
   *    - Each role is in the format "role|label"
   */
  protected function allowedRolesString($roles) {
    $lines = [];
    foreach ($roles as $key => $value) {
      $lines[] = "$key|$value";
    }
    return implode("\n", $lines);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleValues(?AccountInterface $account = NULL) {
    // Flatten options firstly, because Possible Options may contain group
    // arrays.
    $flatten_options = OptGroup::flattenOptions($this->getPossibleOptions($account));
    return array_keys($flatten_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleOptions(?AccountInterface $account = NULL) {
    return $this->getSettableOptions($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableValues(?AccountInterface $account = NULL) {
    // Flatten options firstly, because Settable Options may contain group
    // arrays.
    $flatten_options = OptGroup::flattenOptions($this->getSettableOptions($account));
    return array_keys($flatten_options);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(?AccountInterface $account = NULL) {
    $allowed_options = options_allowed_values($this->getFieldDefinition()->getFieldStorageDefinition(), $this->getEntity());
    return $allowed_options;
  }

}
