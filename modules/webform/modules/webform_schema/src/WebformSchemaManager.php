<?php

namespace Drupal\webform_schema;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Render\Element\Email as EmailElement;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Plugin\WebformElement\BooleanBase;
use Drupal\webform\Plugin\WebformElement\DateBase;
use Drupal\webform\Plugin\WebformElement\NumericBase;
use Drupal\webform\Plugin\WebformElement\Textarea;
use Drupal\webform\Plugin\WebformElement\TextField;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformInterface;

/**
 * Provides a webform schema manager.
 */
class WebformSchemaManager implements WebformSchemaManagerInterface {

  use StringTranslationTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformSchema object.
   *
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   */
  public function __construct(ElementInfoManagerInterface $element_info, EntityFieldManagerInterface $entity_field_manager, WebformElementManagerInterface $element_manager) {
    $this->elementInfo = $element_info;
    $this->entityFieldManager = $entity_field_manager;
    $this->elementManager = $element_manager;
  }

  /**
   * Get webform scheme columns.
   *
   * @return array
   *   Associative array container webform scheme columns
   */
  public function getColumns() {
    return [
      'name' => $this->t('Name'),
      'title' => $this->t('Title'),
      'type' => $this->t('Type'),
      'datatype' => $this->t('Datatype'),
      'maxlength' => $this->t('Maxlength'),
      'required' => $this->t('Required'),
      'multiple' => $this->t('Multiple'),
      'options_text' => $this->t('Options text'),
      'options_value' => $this->t('Options value'),
      'notes' => $this->t('Notes/Comments'),
    ];
  }

  /**
   * Get a webform's scheme elements.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array
   *   An associative containing a webform's scheme elements.
   */
  public function getElements(WebformInterface $webform) {
    $records = [];

    $elements = $webform->getElementsInitializedFlattenedAndHasValue();
    foreach ($elements as $element_key => $element) {
      $element_plugin = $this->elementManager->getElementInstance($element);

      $records[$element_key] = $this->getElement($element_key, $element);
      if ($element_plugin instanceof WebformCompositeBase) {
        $composite_elements = $element_plugin->getInitializedCompositeElement($element);
        foreach ($composite_elements as $composite_element_key => $composite_element) {
          $records["$element_key.$composite_element_key"] = $this->getElement("$element_key.$composite_element_key", $composite_element);
        }
      }
    }

    $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions('webform_submission');
    foreach ($field_definitions as $field_name => $field_definition) {
      $records[$field_name] = $this->getDefinition($field_definition);
    }

    return $records;
  }

  /**
   * Get webform element schema.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition $definition
   *   A webform submission base field definition.
   *
   * @return array
   *   An array containing the schema for a webform submission
   *   base field definition.
   */
  protected function getDefinition(BaseFieldDefinition $definition) {
    $data = [];

    // Name.
    $data['name'] = $definition->getName();

    // Title.
    $data['title'] = $definition->getName();

    // Element type.
    $data['type'] = $definition->getType();

    // Datatype.
    $datatype = '';
    switch ($definition->getType()) {
      case 'created':
      case 'changed':
      case 'completed':
      case 'timestamp':
        $datatype = 'Timestamp';
        break;

      case 'language':
      case 'string':
      case 'uuid':
      case 'entity_reference':
        $datatype = 'Text';
        break;

      case 'string_long':
        $datatype = 'Blob';
        break;

      case 'integer':
        $datatype = 'Number';
        break;

      case 'boolean':
        $datatype = 'Boolean';
        break;
    }

    $data['datatype'] = $datatype;

    // Maxlength.
    $maxlength = $definition->getSetting('max_length');
    switch ($datatype) {
      case 'Blob':
        $maxlength = 'Unlimited';
    }
    $data['maxlength'] = $maxlength;

    // Required.
    $data['required'] = '';

    // Multiple.
    $data['multiple'] = $definition->getCardinality();

    // Options.
    $data['options_text'] = [];
    $data['options_value'] = [];

    return $data;
  }

  /**
   * Get webform element schema.
   *
   * @param string $element_key
   *   The webform element key.
   * @param array $element
   *   The webform element.
   *
   * @return array
   *   An array containing the schema for the webform element.
   */
  protected function getElement($element_key, array $element) {
    $element_info = $this->elementInfo->getInfo($element['#type']);
    $element_plugin = $this->elementManager->getElementInstance($element);

    $data = [];

    // Name.
    $data['name'] = $element_key;

    // Title.
    if (isset($element['#admin_title'])) {
      $title = $element['#admin_title'];
    }
    elseif (isset($element['#title'])) {
      $title = $element['#title'];
    }
    else {
      $title = $element_key;
    }
    $data['title'] = $title;

    // Element type.
    $data['type'] = $element['#type'];

    // Datatype.
    if ($element_plugin instanceof WebformCompositeBase) {
      $datatype = 'Composite';
    }
    elseif ($element_plugin instanceof BooleanBase) {
      $datatype = 'Boolean';
    }
    elseif ($element_plugin instanceof DateBase) {
      $datatype = 'Date';
    }
    elseif ($element_plugin instanceof NumericBase) {
      $datatype = 'Number';
    }
    elseif ($element_plugin instanceof Textarea) {
      $datatype = 'Blob';
    }
    elseif ($element_plugin instanceof WebformManagedFileBase) {
      $datatype = 'Number';
    }
    elseif ($element_plugin instanceof WebformElementEntityReferenceInterface) {
      $datatype = 'Number';
    }
    else {
      $datatype = 'Text';
    }
    $data['datatype'] = $datatype;

    // Maxlength.
    if (isset($element['#maxlength'])) {
      $maxlength = $element['#maxlength'];
    }
    elseif (isset($element['#options'])) {
      $maxlength = $this->getOptionsMaxlength($element);
    }
    elseif ($element_plugin instanceof TextField) {
      // @see \Drupal\webform\Plugin\WebformElement\TextField::prepare
      $maxlength = '255';
    }
    elseif (isset($element_info['#maxlength'])) {
      $maxlength = $element_info['#maxlength'];
    }
    else {
      switch ($element['#type']) {
        case 'color':
          $maxlength = 7;
          break;

        case 'email':
        case 'webform_email_confirm':
          $maxlength = EmailElement::EMAIL_MAX_LENGTH;
          break;

        case 'password_confirm':
          $maxlength = $this->elementInfo->getInfo('password')['#maxlength'];
          break;

        case 'textarea':
        case 'text_format':
        case 'webform_signature':
        case 'webform_codemirror':
        case 'webform_email_multiple':
          $maxlength = $this->t('Unlimited');
          break;

        default:
          $maxlength = '';
          break;
      }
    }
    $data['maxlength'] = $maxlength;

    // Required.
    $data['required'] = (!empty($element['#required'])) ? $this->t('Yes') : $this->t('No');

    // Multiple.
    if (isset($element['#multiple'])) {
      $multiple = ($element['#multiple'] > 1) ? $element['#multiple'] : $this->t('Unlimited');
    }
    else {
      $multiple = '1';
    }
    $data['multiple'] = $multiple;

    if (isset($element['#options'])) {
      $data['options_text'] = OptGroup::flattenOptions($element['#options']);
      $data['options_value'] = array_keys(OptGroup::flattenOptions($element['#options']));
    }
    else {
      $data['options_text'] = [];
      $data['options_value'] = [];
    }

    $data['notes'] = WebformHtmlEditor::checkMarkup($element['#admin_notes'] ?? '');
    return $data;
  }

  /**
   * Get element options maxlength from option values.
   *
   * @param array $element
   *   An element.
   *
   * @return int
   *   An element options maxlength from option values.
   */
  protected function getOptionsMaxlength(array $element) {
    $options = OptGroup::flattenOptions($element['#options']);
    $maxlength = 0;
    foreach ($options as $option_value => $option_text) {
      $maxlength = max(mb_strlen($option_value), $maxlength);
    }

    // Check element w/ other value maxlength.
    if (preg_match('/_other$/', $element['#type'])) {
      if (isset($element['#other__maxlength'])) {
        $maxlength = max($element['#other__maxlength'], $maxlength);
      }
      else {
        // @see \Drupal\webform\Plugin\WebformElement\TextField::prepare
        $maxlength = max(255, $maxlength);
      }
    }

    return $maxlength;
  }

}
