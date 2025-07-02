<?php

namespace Drupal\embed\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure embed settings for this site.
 */
final class EmbedSettingsForm extends ConfigFormBase {

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  private $streamWrapperManager;

  /**
   * Constructs a EmbedSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager, StreamWrapperManagerInterface $stream_wrapper_manager) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'embed_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['embed.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('embed.settings');

    $scheme_options = $this->streamWrapperManager->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    $form['file_scheme'] = [
      '#type' => 'radios',
      '#title' => $this->t('Upload destination'),
      '#options' => $scheme_options,
      '#default_value' => $config->get('file_scheme'),
      '#description' => $this->t('Select where the uploaded button icon files should be stored.'),
    ];

    $form['upload_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File directory'),
      '#default_value' => $config->get('upload_directory'),
      '#description' => $this->t('Optional subdirectory within the upload destination where files will be stored. Do not include preceding or trailing slashes.'),
      '#element_validate' => [[get_class($this), 'validateDirectory']],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form API callback.
   *
   * Removes slashes from the beginning and end of the destination value and
   * ensures that the file directory path is not included at the beginning of
   * the value.
   *
   * This function is assigned as an #element_validate callback in
   * fieldSettingsForm().
   */
  public static function validateDirectory($element, FormStateInterface $form_state) {
    // Strip slashes from the beginning and end of $element['file_directory'].
    $value = trim($element['#value'], '\\/');
    $form_state->setValueForElement($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('embed.settings');
    $config->set('file_scheme', $form_state->getValue('file_scheme'));
    $config->set('upload_directory', $form_state->getValue('upload_directory'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
