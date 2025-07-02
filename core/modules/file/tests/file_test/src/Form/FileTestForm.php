<?php

namespace Drupal\file_test\Form;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * File test form class.
 */
class FileTestForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_file_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['file_test_upload'] = [
      '#type' => 'file',
      '#title' => t('Upload a file'),
    ];
    $form['file_test_replace'] = [
      '#type' => 'select',
      '#title' => t('Replace existing image'),
      '#options' => [
        FileExists::Rename->name => new TranslatableMarkup('Appends number until name is unique'),
        FileExists::Replace->name => new TranslatableMarkup('Replace the existing file'),
        FileExists::Error->name => new TranslatableMarkup('Fail with an error'),
      ],
      '#default_value' => FileExists::Rename->name,
    ];
    $form['file_subdir'] = [
      '#type' => 'textfield',
      '#title' => t('Subdirectory for test file'),
      '#default_value' => '',
    ];

    $form['extensions'] = [
      '#type' => 'textfield',
      '#title' => t('Allowed extensions.'),
      '#default_value' => '',
    ];

    $form['allow_all_extensions'] = [
      '#title' => t('Allow all extensions?'),
      '#type' => 'radios',
      '#options' => [
        'false' => 'No',
        'empty_array' => 'Empty array',
        'empty_string' => 'Empty string',
      ],
      '#default_value' => 'false',
    ];

    $form['is_image_file'] = [
      '#type' => 'checkbox',
      '#title' => t('Is this an image file?'),
      '#default_value' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Process the upload and perform validation. Note: we're using the
    // form value for the $replace parameter.
    if (!$form_state->isValueEmpty('file_subdir')) {
      $destination = 'temporary://' . $form_state->getValue('file_subdir');
      \Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY);
    }
    else {
      $destination = FALSE;
    }

    // Setup validators.
    $validators = [];
    if ($form_state->getValue('is_image_file')) {
      $validators['FileIsImage'] = [];
    }

    $allow = $form_state->getValue('allow_all_extensions');
    if ($allow === 'empty_array') {
      $validators['FileExtension'] = [];
    }
    elseif ($allow === 'empty_string') {
      $validators['FileExtension'] = ['extensions' => ''];
    }
    elseif (!$form_state->isValueEmpty('extensions')) {
      $validators['FileExtension'] = ['extensions' => $form_state->getValue('extensions')];
    }

    // The test for \Drupal::service('file_system')->moveUploadedFile()
    // triggering a warning is unavoidable. We're interested in what happens
    // afterwards in file_save_upload().
    if (\Drupal::state()->get('file_test.disable_error_collection')) {
      define('SIMPLETEST_COLLECT_ERRORS', FALSE);
    }

    $file = file_save_upload('file_test_upload', $validators, $destination, 0, static::fileExistsFromName($form_state->getValue('file_test_replace')));
    if ($file) {
      $form_state->setValue('file_test_upload', $file);
      \Drupal::messenger()->addStatus(t('File @filepath was uploaded.', ['@filepath' => $file->getFileUri()]));
      \Drupal::messenger()->addStatus(t('File name is @filename.', ['@filename' => $file->getFilename()]));
      \Drupal::messenger()->addStatus(t('File MIME type is @mimetype.', ['@mimetype' => $file->getMimeType()]));
      \Drupal::messenger()->addStatus(t('You WIN!'));
    }
    elseif ($file === FALSE) {
      \Drupal::messenger()->addError(t('Epic upload FAIL!'));
    }
  }

  /**
   * Get a FileExists enum from its name.
   */
  protected static function fileExistsFromName(string $name): FileExists {
    return match ($name) {
      FileExists::Replace->name => FileExists::Replace,
      FileExists::Error->name => FileExists::Error,
      default => FileExists::Rename,
    };
  }

}
