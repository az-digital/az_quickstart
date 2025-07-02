<?php

namespace Drupal\block_class\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for Block Class Settings.
 */
class BlockClassSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_class_admin_settings';
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

    $config = $this->config('block_class.settings');

    // Default value.
    $default_case = 'standard';

    // Verify if there is a value in the database.
    if (!empty($config->get('default_case'))) {
      $default_case = $config->get('default_case');
    }

    $form['global_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Global Settings'),
      '#open' => TRUE,
    ];

    $form['global_settings']['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#open' => TRUE,
    ];

    $form['global_settings']['general']['enable_auto_complete'] = [
      '#title' => $this->t('Enable auto-complete'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('enable_auto_complete'),
    ];

    $form['global_settings']['general']['enable_special_chars'] = [
      '#title' => $this->t('Enable special chars'),
      '#type' => 'checkbox',
      '#description' => $this->t('If checked will be possible to insert special chars in the class, like #$%&. If unchecked will be allow letters, numbers, hyphen and underlines'),
      '#default_value' => $config->get('enable_special_chars'),
    ];

    $form['global_settings']['general']['default_case'] = [
      '#title' => $this->t('Default Case'),
      '#type' => 'select',
      '#description' => $this->t('If you select "Uppercase and Lowercase" but cases will be accepted. If you select "Uppercase" all classes will be added using uppercase and if you select "Lowercase" all classes added will be added using lowercase.'),
      '#options' => [
        'standard' => $this->t('Uppercase and Lowercase (Standard)'),
        'uppercase' => $this->t('Uppercase'),
        'lowercase' => $this->t('Lowercase'),
      ],
      '#default_value' => $default_case,
    ];

    $form['global_settings']['class'] = [
      '#type' => 'details',
      '#title' => $this->t('Class'),
      '#open' => TRUE,
    ];

    $field_type = 'textfield';

    if (!empty($config->get('field_type'))) {
      $field_type = $config->get('field_type');
    }

    $form['global_settings']['class']['field_type'] = [
      '#title' => $this->t('Field Type'),
      '#type' => 'select',
      '#options' => [
        'multiple_textfields' => $this->t('Multiple textfields'),
        'textfield' => $this->t('textfield'),
      ],
      '#default_value' => $field_type,
    ];

    // Get default value of quantity per block.
    $qty_classes_per_block = 10;

    // If there is a config for that use this.
    if (!empty($config->get('qty_classes_per_block'))) {
      $qty_classes_per_block = $config->get('qty_classes_per_block');
    }

    // Define the settings for quantity per block.
    $form['global_settings']['class']['qty_classes_per_block'] = [
      '#title' => $this->t('Quantity of classes per block'),
      '#type' => 'number',
      '#default_value' => $qty_classes_per_block,
      // Show the qty_classes_per_block only when the field type is multiple
      // because is the only type that is used.
      '#states' => [
        'visible' => [
          ':input[name="field_type"]' => ['value' => 'multiple_textfields'],
        ],
      ],
    ];

    $maxlength_block_class_field = 255;

    if (!empty($config->get('maxlength_block_class_field'))) {
      $maxlength_block_class_field = $config->get('maxlength_block_class_field');
    }

    $form['global_settings']['class']['maxlength_block_class_field'] = [
      '#title' => $this->t('Maxlength'),
      '#type' => 'number',
      '#description' => $this->t('This will be the default maxlength value for the "maxlength" field. The default is 255.'),
      '#default_value' => $maxlength_block_class_field,
    ];

    // Get default value for weight of class item.
    $weight_class = FALSE;

    // If there is a config for that use this.
    if (!empty($config->get('weight_class'))) {
      $weight_class = $config->get('weight_class');
    }

    // Define the settings for weight of class item.
    $form['global_settings']['class']['weight_class'] = [
      '#title' => $this->t('Weight'),
      '#type' => 'number',
      '#default_value' => $weight_class,
    ];

    $form['global_settings']['attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Attributes'),
      '#open' => TRUE,
    ];

    $form['global_settings']['attributes']['enable_attributes'] = [
      '#title' => $this->t('Enable attributes'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('enable_attributes'),
    ];

    $qty_attributes_per_block = 10;

    // If there is a settings for that use this.
    if (!empty($config->get('qty_attributes_per_block'))) {
      $qty_attributes_per_block = $config->get('qty_attributes_per_block');
    }

    // Define the settings for quantity per block.
    $form['global_settings']['attributes']['qty_attributes_per_block'] = [
      '#title' => $this->t('Quantity of attributes per block'),
      '#type' => 'number',
      '#default_value' => $qty_attributes_per_block,
      '#states' => [
        'visible' => [
          ':input[name="enable_attributes"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['global_settings']['attributes']['maxlength_attributes'] = [
      '#title' => $this->t('Maxlength'),
      '#type' => 'number',
      '#description' => $this->t('This will be the default maxlength value for the attributes field'),
      '#default_value' => $config->get('maxlength_attributes'),
      '#states' => [
        'visible' => [
          ':input[name="enable_attributes"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Get default value for weight of attributes.
    $weight_attributes = FALSE;

    // If there is a config for that use this.
    if (!empty($config->get('weight_attributes'))) {
      $weight_attributes = $config->get('weight_attributes');
    }

    // Define the settings for weight of attributes item.
    $form['global_settings']['attributes']['weight_attributes'] = [
      '#title' => $this->t('Weight'),
      '#type' => 'number',
      '#default_value' => $weight_attributes,
      '#states' => [
        'visible' => [
          ':input[name="enable_attributes"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['global_settings']['id'] = [
      '#type' => 'details',
      '#title' => $this->t('ID'),
      '#open' => TRUE,
    ];

    $form['global_settings']['id']['enable_id_replacement'] = [
      '#title' => $this->t('Enable id replacement'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('enable_id_replacement'),
    ];

    $maxlength_id = 255;

    if (!empty($config->get('maxlength_id'))) {
      $maxlength_id = $config->get('maxlength_id');
    }

    $form['global_settings']['id']['maxlength_id'] = [
      '#title' => $this->t('Maxlength'),
      '#type' => 'number',
      '#description' => $this->t('This will be the default maxlength value for the replacement id field'),
      '#default_value' => $maxlength_id,
      '#states' => [
        'visible' => [
          ':input[name="enable_id_replacement"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Get default value for weight of id.
    $weight_id = FALSE;

    // If there is a config for that use this.
    if (!empty($config->get('weight_id'))) {
      $weight_id = $config->get('weight_id');
    }

    // Define the settings for weight of id to be replaced.
    $form['global_settings']['id']['weight_id'] = [
      '#title' => $this->t('Weight'),
      '#type' => 'number',
      '#default_value' => $weight_id,
      '#states' => [
        'visible' => [
          ':input[name="enable_id_replacement"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['global_settings']['block_class_list'] = [
      '#type' => 'details',
      '#title' => $this->t('Block Class List'),
      '#open' => FALSE,
    ];

    $items_per_page = 50;

    if (!empty($config->get('items_per_page'))) {
      $items_per_page = $config->get('items_per_page');
    }

    $form['global_settings']['block_class_list']['items_per_page'] = [
      '#title' => $this->t('Items per page'),
      '#type' => 'number',
      '#description' => $this->t('This number will be used in the pagination to define the items per page'),
      '#default_value' => $items_per_page,
    ];

    $form['global_settings']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => FALSE,
    ];

    $form['global_settings']['advanced']['filter_html_clean_css_identifier'] = [
      '#title' => $this->t('Filter to HTML Clean CSS Identifier'),
      '#type' => 'textarea',
      '#description' => $this->t('You can use this field to insert the configuration of <a href="https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Component%21Utility%21Html.php/function/Html%3A%3AcleanCssIdentifier/">Filter to HTML Clean CSS Identifier</a>. You can insert to replace special chars in the class. Use key|value format, and one per line. E.g.<br>#|-<br>%|-'),
      '#default_value' => $config->get('filter_html_clean_css_identifier'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;

  }

  /**
   * Validate the maxlength field.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);

    $qty_classes_per_block = $form_state->getValue('qty_classes_per_block');

    // Verify if the quantity per block is a positive value.
    if ($qty_classes_per_block < 1) {
      $form_state->setErrorByName('qty_classes_per_block', $this->t('The quantity of classes per block should be an positive value'));
    }

    $qty_attributes_per_block = $form_state->getValue('qty_attributes_per_block');

    if ($qty_attributes_per_block < 1) {
      $form_state->setErrorByName('qty_attributes_per_block', $this->t('The quantity of attributes per block should be an positive value'));
    }

    // Get the $items_per_page field.
    $items_per_page = $form_state->getValue('items_per_page');

    // Verify if the maxlength is a positive value.
    if ($items_per_page < 1) {
      $form_state->setErrorByName('items_per_page', $this->t('The items per page should be an positive value'));
    }

    // Get the maxlength_block_class_field field.
    $maxlength_block_class_field = $form_state->getValue('maxlength_block_class_field');

    // Verify if the maxlength is a positive value.
    if ($maxlength_block_class_field < 1) {
      $form_state->setErrorByName('maxlength_block_class_field', $this->t('The maxlength should be an positive value'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get the config object.
    $config = $this->config('block_class.settings');

    $enable_auto_complete = $form_state->getValue('enable_auto_complete');

    // Get the variable to enable special chars.
    $enable_special_chars = $form_state->getValue('enable_special_chars');

    $previous_default_case = $config->get('default_case');

    // Get the default case.
    $default_case = $form_state->getValue('default_case');

    // Get the field type.
    $field_type = $form_state->getValue('field_type');

    // Get the quantity per block.
    $qty_classes_per_block = $form_state->getValue('qty_classes_per_block');

    // Set the quantity per block in the settings.
    $config->set('qty_classes_per_block', $qty_classes_per_block);

    $qty_attributes_per_block = $form_state->getValue('qty_attributes_per_block');

    // Get the maxlength_block_class_field field.
    $maxlength_block_class_field = $form_state->getValue('maxlength_block_class_field');

    // Set the default value.
    $maxlength_id = 255;

    // Verify if there is a value in the settings and use that.
    if (!empty($form_state->getValue('maxlength_id'))) {
      $maxlength_id = $form_state->getValue('maxlength_id');
    }

    $maxlength_attributes = $form_state->getValue('maxlength_attributes');

    $enable_attributes = $form_state->getValue('enable_attributes');

    $enable_id_replacement = $form_state->getValue('enable_id_replacement');

    $items_per_page = $form_state->getValue('items_per_page');

    $filter_html_clean_css_identifier = $form_state->getValue('filter_html_clean_css_identifier');

    $weight_class = $form_state->getValue('weight_class');

    $weight_attributes = $form_state->getValue('weight_attributes');

    $weight_id = $form_state->getValue('weight_id');

    // Set the values.
    $config->set('default_case', $default_case);
    $config->set('enable_attributes', $enable_attributes);
    $config->set('enable_auto_complete', $enable_auto_complete);
    $config->set('enable_id_replacement', $enable_id_replacement);
    $config->set('enable_special_chars', $enable_special_chars);
    $config->set('field_type', $field_type);
    $config->set('filter_html_clean_css_identifier', $filter_html_clean_css_identifier);
    $config->set('items_per_page', $items_per_page);
    $config->set('qty_attributes_per_block', $qty_attributes_per_block);
    $config->set('qty_classes_per_block', $qty_classes_per_block);
    $config->set('maxlength_attributes', $maxlength_attributes);
    $config->set('maxlength_block_class_field', $maxlength_block_class_field);
    $config->set('maxlength_id', $maxlength_id);
    $config->set('weight_attributes', $weight_attributes);
    $config->set('weight_class', $weight_class);
    $config->set('weight_id', $weight_id);

    // Save that.
    $config->save();

    parent::submitForm($form, $form_state);

    if ($previous_default_case != $default_case) {

      $this->messenger()->addStatus($this->t('Now you are using @default_case@ as a default case. If you want to convert all classes stored, feel free run the <a href="/admin/config/content/block-class/bulk-operations">Bulk Update</a> to convert all to @default_case@', [
        '@default_case@' => $default_case,
      ]));

    }

  }

}
