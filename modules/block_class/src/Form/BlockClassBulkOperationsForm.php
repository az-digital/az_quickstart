<?php

namespace Drupal\block_class\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form for Block Class Settings.
 */
class BlockClassBulkOperationsForm extends ConfigFormBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_class_admin_bulk_operations';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'block_class.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get config object.
    $config = $this->config('block_class.settings');

    $enable_attributes = $config->get('enable_attributes', FALSE);

    $operation_options = [];

    $operation_options['insert'] = $this->t('Insert class(es)');

    // Insert the "Insertion attributes" only if attributes is enabled.
    if ($enable_attributes) {
      $operation_options['insert_attributes'] = $this->t('Insert attributes');
      $operation_options['convert_attributes_to_uppercase'] = $this->t('Convert all attributes to uppercase');
      $operation_options['convert_attributes_to_lowercase'] = $this->t('Convert all attributes to lowercase');
    }

    $operation_options['convert_to_uppercase'] = $this->t('Convert all block classes to uppercase');
    $operation_options['convert_to_lowercase'] = $this->t('Convert all block classes to lowercase');
    $operation_options['update'] = $this->t('Update class');

    // Insert the update attributes only if attributes is enabled.
    if ($enable_attributes) {
      $operation_options['update_attributes'] = $this->t('Update attributes');
    }

    $operation_options['delete'] = $this->t('Delete all block classes');

    // Insert the clean attributes only if attributes is enabled.
    if ($enable_attributes) {
      $operation_options['delete_attributes'] = $this->t('Delete all attributes');
    }

    $enable_attributes = $config->get('enable_id_replacement', FALSE);

    if (!empty($enable_attributes)) {
      $operation_options['remove_all_custom_ids'] = $this->t("Remove all custom ids (will revert to block's original id)");
    }

    $form['operation'] = [
      '#title' => $this->t("Operation"),
      '#type' => 'select',
      '#options' => $operation_options,
    ];

    // Default field type.
    $field_type = 'textfield';

    // Default value for maxlength.
    $maxlength_block_class_field = 255;

    // Get the field type if exists.
    if (!empty($config->get('field_type'))) {
      $field_type = $config->get('field_type');

      // If the field type is multiple, should be used the textfield by default.
      if ($field_type == 'multiple_textfields') {
        $field_type = 'textfield';
      }

    }

    // Get maxlength if exists.
    if (!empty($config->get('maxlength_block_class_field'))) {
      $maxlength_block_class_field = $config->get('maxlength_block_class_field');
    }

    $form['classes_to_be_added'] = [
      '#title' => $this->t("Classes to be added"),
      '#type' => $field_type,
      '#description' => $this->t("Customize the styling of all blocks by adding CSS classes. Separate multiple classes by spaces."),
      '#maxlength' => $maxlength_block_class_field,
      '#states' => [
        'visible' => [
          ':input[name="operation"]' => [
            'value' => 'insert',
          ],
        ],
        'required' => [
          ':input[name="operation"]' => [
            'value' => 'insert',
          ],
        ],
      ],
    ];

    $form['classes_to_be_added']['#attributes']['class'][] = 'block-class-bulk-operations-insert-classes_to_be_added';

    $form['attributes_to_be_added'] = [
      '#title' => $this->t("Attributes to be added"),
      '#type' => 'textarea',
      '#description' => $this->t('Here you can insert any attributes, use one per line. For example: data-block-type|info'),
      '#maxlength' => $config->get('maxlength_attributes'),
      '#states' => [
        'visible' => [
          ':input[name="operation"]' => [
            'value' => 'insert_attributes',
          ],
        ],
        'required' => [
          ':input[name="operation"]' => [
            'value' => 'insert_attributes',
          ],
        ],
      ],
    ];

    $form['current_class'] = [
      '#title' => $this->t("Current class"),
      '#type' => 'textfield',
      '#states' => [
        'visible' => [
          ':input[name="operation"]' => [
            'value' => 'update',
          ],
        ],
        'required' => [
          ':input[name="operation"]' => [
            'value' => 'update',
          ],
        ],
      ],
    ];

    $form['new_class'] = [
      '#title' => $this->t("New class"),
      '#type' => 'textfield',
      '#states' => [
        'visible' => [
          ':input[name="operation"]' => [
            'value' => 'update',
          ],
        ],
        'required' => [
          ':input[name="operation"]' => [
            'value' => 'update',
          ],
        ],
      ],
    ];

    $form['new_class']['#attributes']['class'][] = 'block-class-bulk-operations-update-class-new-class';

    $form['current_attribute'] = [
      '#title' => $this->t("Current attribute"),
      '#type' => 'textfield',
      '#states' => [
        'visible' => [
          ':input[name="operation"]' => [
            'value' => 'update_attributes',
          ],
        ],
        'required' => [
          ':input[name="operation"]' => [
            'value' => 'update_attributes',
          ],
        ],
      ],
    ];

    $form['new_attribute'] = [
      '#title' => $this->t("New attribute"),
      '#type' => 'textfield',
      '#states' => [
        'visible' => [
          ':input[name="operation"]' => [
            'value' => 'update_attributes',
          ],
        ],
        'required' => [
          ':input[name="operation"]' => [
            'value' => 'update_attributes',
          ],
        ],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run'),
    ];

    return $form;

  }

  /**
   * Validate the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // Get config object.
    $config = $this->config('block_class.settings');

    switch ($form_state->getValue('operation')) {

      case 'insert':

        if (empty($form_state->getValue('classes_to_be_added'))) {
          return;
        }

        // If there is a settings to allow only letters and numbers, validate
        // this.
        if (!empty($config->get('enable_special_chars'))) {
          return FALSE;
        }

        $classes_to_be_added = $form_state->getValue('classes_to_be_added');

        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬]/', $classes_to_be_added)) {

          $form_state->setErrorByName('classes_to_be_added', $this->t("In class is allowed only letters, numbers, hyphen and underline"));

          return FALSE;

        }

        break;

      case 'insert_attributes':

        if (empty($form_state->getValue('attributes_to_be_added'))) {
          return FALSE;
        }

        $attributes_to_be_added = $form_state->getValue('attributes_to_be_added');

        $pipe_found = strpos($attributes_to_be_added, '|');

        if ($pipe_found === FALSE) {

          $form_state->setErrorByName('attributes_to_be_added', $this->t("You need to insert a pipe on attributes field"));

          return FALSE;

        }

        // Prevent attribute id.
        if (str_contains($attributes_to_be_added, 'id|')) {

          $form_state->setErrorByName('attributes_to_be_added', $this->t("You can't the attribute id"));

          return FALSE;

        }

        // Prevent attribute class.
        if (str_contains($attributes_to_be_added, 'class|')) {

          $form_state->setErrorByName('attributes_to_be_added', $this->t("You can't use class. Use the field class instead"));

          return FALSE;

        }

        // If it's only one line, skip.
        if (!str_contains($attributes_to_be_added, PHP_EOL)) {
          return FALSE;
        }

        $attributes_to_be_added = explode(PHP_EOL, $attributes_to_be_added);

        foreach ($attributes_to_be_added as $attribute) {

          if (!str_contains($attribute, '|')) {

            $form_state->setErrorByName('attributes_to_be_added', $this->t("You need to insert a pipe on attributes field"));

            return FALSE;

          }

        }

        break;

      case 'convert_to_uppercase':

        // If the default case is "standard" with lowercase and uppercase, skip.
        if (empty($config->get('default_case')) || $config->get('default_case') == 'standard') {
          return FALSE;
        }

        // Get the default case in the settings area.
        $default_case = $config->get('default_case');

        // If is configure lowercase we can't run the bulk operation to
        // Uppercase so show this message.
        if ($default_case == 'lowercase') {

          $form_state->setErrorByName('operation', $this->t('You selected to convert all classes to lowercase but your configuration is to use all with Uppercase. Please see <a href="/admin/config/content/block-class/settings">your settings here</a>'));

        }

        break;

      case 'convert_to_lowercase':

        // If the default case is "standard" with lowercase and uppercase, skip.
        if (empty($config->get('default_case')) || $config->get('default_case') == 'standard') {
          return FALSE;
        }

        // Get the default case in the settings area.
        $default_case = $config->get('default_case');

        // If is configure uppercase we can't run the bulk operation to
        // lowercase so show this message.
        if ($default_case == 'uppercase') {

          $form_state->setErrorByName('operation', $this->t('You selected to convert all classes to lowercase but your configuration is to use all with Uppercase. Please see <a href="/admin/config/content/block-class/settings">your settings here</a>'));

        }

        break;

    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the operation type.
    $operation = $form_state->getValue('operation');

    $parameters = [
      'operation' => $operation,
      'classes_to_be_added' => 0,
      'current_class' => 0,
      'new_class' => 0,
      'attributes_to_be_added' => 0,
      'current_attribute' => 0,
      'new_attribute' => 0,
    ];

    // Get the classes to be added.
    if (!empty($form_state->getValue('classes_to_be_added'))) {
      $classes_to_be_added = $form_state->getValue('classes_to_be_added');
      $parameters['classes_to_be_added'] = $classes_to_be_added;
    }

    // Get the current class.
    if (!empty($form_state->getValue('current_class'))) {
      $current_class = $form_state->getValue('current_class');
      $parameters['current_class'] = $current_class;
    }

    // Get the new class.
    if (!empty($form_state->getValue('new_class'))) {
      $new_class = $form_state->getValue('new_class');
      $parameters['new_class'] = $new_class;
    }

    // Get the attributes to be added.
    if (!empty($form_state->getValue('attributes_to_be_added'))) {
      $attributes_to_be_added = $form_state->getValue('attributes_to_be_added');
      $parameters['attributes_to_be_added'] = base64_encode($attributes_to_be_added);
    }

    // Get the current attribute.
    if (!empty($form_state->getValue('current_attribute'))) {
      $current_attribute = $form_state->getValue('current_attribute');
      $parameters['current_attribute'] = base64_encode($current_attribute);
    }

    // Get the new attribute.
    if (!empty($form_state->getValue('new_attribute'))) {
      $new_attribute = $form_state->getValue('new_attribute');
      $parameters['new_attribute'] = base64_encode($new_attribute);
    }

    // Get path confirmation bulk operation.
    $path_confirm_bulk_operation = Url::fromRoute('block_class.confirm_bulk_operation', $parameters)->toString();

    // Get response.
    $response = new RedirectResponse($path_confirm_bulk_operation);

    // Send to confirmation.
    $response->send();
    exit;

  }

}
