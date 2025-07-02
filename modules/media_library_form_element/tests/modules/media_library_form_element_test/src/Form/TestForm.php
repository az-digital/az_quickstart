<?php

namespace Drupal\media_library_form_element_test\Form;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A form for testing the Media Library Form Element.
 */
class TestForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_library_form_element_test_form';
  }

  /**
   * Returns a the media library form element.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('media_library_form_element_test.settings');
    $form['media_single'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['type_one', 'type_two'],
      '#title' => $this->t('Upload your image'),
      '#default_value' => $config->get('media_single') ?? NULL,
      '#description' => $this->t('Upload or select your profile image.'),
      '#cardinality' => 1,
    ];
    $form['media_multiple'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['type_one'],
      '#title' => $this->t('Upload your images'),
      '#default_value' => $config->get('media_multiple') ?? NULL,
      '#description' => $this->t('Upload or select multiple images.'),
      '#cardinality' => 2,
    ];
    $form['media_unlimited'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['type_one'],
      '#title' => $this->t('Upload infinite images'),
      '#default_value' => $config->get('media_unlimited') ?? NULL,
      '#description' => $this->t('Upload or select unlimited images.'),
      '#cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('media_library_form_element_test.settings');
    $config->set('media_single', $form_state->getValue('media_single'));
    $config->set('media_multiple', $form_state->getValue('media_multiple'));
    $config->set('media_unlimited', $form_state->getValue('media_unlimited'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'media_library_form_element_test.settings',
    ];
  }

}
