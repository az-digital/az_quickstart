<?php

namespace Drupal\webform;

use Drupal\Core\Form\OptGroup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webform\Plugin\WebformElement\WebformElement;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformOptionsHelper;

/**
 * Webform submission conditions (#states) validator.
 *
 * @see \Drupal\webform\Element\WebformElementStates
 * @see \Drupal\Core\Form\FormHelper::processStates
 */
class WebformEntityConditionsManager implements WebformEntityConditionsManagerInterface {

  use StringTranslationTrait;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a WebformEntityConditionsManager object.
   *
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   */
  public function __construct(WebformElementManagerInterface $element_manager) {
    $this->elementManager = $element_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function toText(WebformInterface $webform, array $states, array $options = []) {
    // Set default options.
    $options += [
      'name' => $this->t('element'),
      'states' => [],
      'triggers' => [],
      'logic' => [],
    ];
    $options['states'] += [
      'visible' => $this->t('visible'),
      'invisible' => $this->t('hidden'),
      'visible-slide' => $this->t('visible'),
      'invisible-slide' => $this->t('hidden'),
      'enabled' => $this->t('enabled'),
      'disabled' => $this->t('disabled'),
      'readwrite' => $this->t('read/write'),
      'readonly' => $this->t('read-only'),
      'expanded' => $this->t('expanded'),
      'collapsed' => $this->t('collapsed'),
      'required' => $this->t('required'),
      'optional' => $this->t('optional'),
      'checked' => $this->t('checked', [], ['context' => 'Add check mark']),
      'unchecked' => $this->t('unchecked', [], ['context' => 'Remove check mark']),
    ];
    $options['triggers'] += [
      'empty' => $this->t('is empty'),
      'filled' => $this->t('is filled'),
      'checked' => $this->t('is checked'),
      'unchecked' => $this->t('is not checked'),
      'value' => '=',
      '!value' => '!=',
      'pattern' => $this->t('matches'),
      '!pattern' => $this->t('does not match'),
      'less' => '<',
      'less_equal' => '<=',
      'greater' => '>',
      'greater_equal' => '>=',
      'between' => $this->t('is between'),
      '!between' => $this->t('is not between'),
    ];
    $options['logic'] += [
      'and' => $this->t('all'),
      'or' => $this->t('any'),
      'xor' => $this->t('one'),
    ];

    $build = [];
    foreach ($states as $state => $conditions) {
      $t_args = [
        '@name' => $options['name'],
        '@state' => $options['states'][$state] ?? $state,
      ];
      $build[$state] = [
        'state' => [
          '#markup' => $this->t('This @name is <strong>@state</strong>', $t_args),
          '#suffix' => ' ',
        ],
        'conditions' => $this->buildConditions($webform, $conditions, $options),
      ];
    }
    return $build;
  }

  /**
   * Convert a webform's conditions into a human read-able format.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param array $conditions
   *   A webform's state conditions.
   * @param array $options
   *   An associative array of configuration options.
   *
   * @return array
   *   A renderable array containing the webform's conditions in
   *   a human read-able format.
   */
  protected function buildConditions(WebformInterface $webform, array $conditions, array $options) {
    // Determine condition logic.
    // @see Drupal.states.Dependent.verifyConstraints
    if (WebformArrayHelper::isSequential($conditions)) {
      $logic = (in_array('xor', $conditions)) ? 'xor' : 'or';
    }
    else {
      $logic = 'and';
    }

    $condition_items = [];
    foreach ($conditions as $index => $value) {
      // Skip and, or, and xor.
      if (is_string($value) && in_array($value, ['and', 'or', 'xor'])) {
        continue;
      }

      if (is_int($index) && is_array($value) && (WebformArrayHelper::isSequential($value) || count($value) > 1)) {
        $condition_items[] = $this->buildConditions($webform, $value, $options + ['nested' => TRUE]);
      }
      else {
        if (is_int($index)) {
          $selector = key($value);
          $condition = $value[$selector];
        }
        else {
          $selector = $index;
          $condition = $value;
        }

        $condition_items[] = $this->buildConditionItem($webform, $selector, $condition, $options + ['nested' => TRUE]);
      }
    }

    $t_args = [
      '@logic' => $options['logic'][$logic],
    ];
    $build = [];
    $build['logic'] = [
      '#markup' => (empty($options['nested']))
        ? $this->t('when <strong>@logic</strong> of the following conditions are met:', $t_args)
        : $this->t('When <strong>@logic</strong> of the following (nested) conditions are met:', $t_args),
    ];
    $build['condition'] = [
      '#theme' => 'item_list',
      '#items' => $condition_items,
    ];
    return $build;
  }

  /**
   * Convert a condition's select, trigger, and value into a human read-able format.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $selector
   *   The condition's selector (i.e. :input[name="{element_key}").
   * @param array $condition
   *   The condition's trigger and value.
   * @param array $options
   *   An associative array of configuration options.
   *
   * @return array
   *   A renderable array containing a condition's select, trigger, and value in
   *   a human read-able format.
   */
  protected function buildConditionItem(WebformInterface $webform, $selector, array $condition, array $options) {
    if (WebformArrayHelper::isSequential($condition)) {
      $sub_condition_items = [];
      foreach ($condition as $sub_condition) {
        $sub_condition_items[] = $this->buildConditionItem($webform, $selector, $sub_condition, $options);
      }
      return $sub_condition_items;
    }

    // Ignore invalid selector and return an empty render array.
    $input_name = WebformSubmissionConditionsValidator::getSelectorInputName($selector);
    if (!$input_name) {
      return [];
    }

    $element_key = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 0);
    $element_option_key = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 1);
    $element = $webform->getElement($element_key);

    // If no element is found try checking file uploads which use
    // :input[name="files[ELEMENT_KEY].
    // @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::getElementSelectorOptions
    if (!$element && strpos($selector, ':input[name="files[') === 0) {
      $element_key = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 1);
      $element = $webform->getWebform()->getElement($element_key);
    }

    // Ignore missing dependee element and return an empty render array.
    if (!$element) {
      return [];
    }

    $trigger_state = key($condition);
    $trigger_value = $condition[$trigger_state];

    $element_plugin = $this->elementManager->getElementInstance($element);

    // Ignored conditions for generic webform elements.
    if ($element_plugin instanceof WebformElement) {
      return [];
    }

    // Process trigger sub state used for custom #states API validation.
    // @see Drupal.behaviors.webformStatesComparisons
    // @see http://drupalsun.com/julia-evans/2012/03/09/extending-form-api-states-regular-expressions
    if ($trigger_state === 'value' && is_array($trigger_value)) {
      $trigger_substate = key($trigger_value);
      if (in_array($trigger_substate, ['pattern', '!pattern', 'less', 'less_equal', 'greater', 'greater_equal', 'between', '!between'])) {
        $trigger_state = $trigger_substate;
        $trigger_value = reset($trigger_value);
      }
    }

    // Get element options.
    $element_options = (isset($element['#options'])) ? OptGroup::flattenOptions($element['#options']) : [];

    // Set element title.
    $element_title = $element['#admin_title'];

    // Set trigger value and suffix element title with the trigger's option value.
    if ($element_option_key) {
      $element_title .= ': ' . WebformOptionsHelper::getOptionText($element_option_key, $element_options, TRUE);
    }

    // Checked 'checked: false' to 'unchecked: true' and vice-versa.
    if ($trigger_state === 'checked' && $trigger_value === FALSE) {
      $trigger_state = 'unchecked';
      $trigger_value = TRUE;
    }
    elseif ($trigger_state === 'unchecked' && $trigger_value === FALSE) {
      $trigger_state = 'checked';
      $trigger_value = TRUE;
    }

    // Build the condition.
    $t_args = [
      '@name' => $element_title,
      '@trigger' => $options['triggers'][$trigger_state],
    ];

    // Do not return the value boolean value for empty or checked states.
    switch ($trigger_state) {
      case 'empty':
      case 'filled':
      case 'checked':
      case 'unchecked':
        return [
          '#markup' => $this->t('<strong>@name</strong> @trigger.', $t_args),
        ];

      case 'between':
        $range = explode(':', $trigger_value);
        $t_args['@min'] = $range[0];
        $t_args['@max'] = $range[1];
        return [
          '#markup' => $this->t('<strong>@name</strong> @trigger <strong>@min</strong> and <strong>@max</strong>.', $t_args),
        ];

      default:
        $t_args['@value'] = $element_options[$trigger_value] ?? $trigger_value;
        return [
          '#markup' => $this->t('<strong>@name</strong> @trigger <strong>@value</strong>.', $t_args),
        ];
    }
  }

}
