<?php

namespace Drupal\image_widget_crop_examples\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\file\Entity\File;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\image_widget_crop\ImageWidgetCropInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure ImageWidgetCrop general settings for this site.
 */
class ImageWidgetCropExamplesForm extends ConfigFormBase {

  /**
   * The settings of image_widget_crop configuration.
   *
   * @var array
   *
   * @see \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * File usage interface to configure a file object.
   *
   * @var Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * Created file entity.
   *
   * @var \Drupal\file\Entity\File|null
   */
  protected $file = NULL;

  /**
   * Instance of API ImageWidgetCropManager.
   *
   * @var \Drupal\image_widget_crop\ImageWidgetCropInterface
   */
  protected $imageWidgetCropManager;

  /**
   * Constructs a CropWidgetForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManager $config_typed
   *   The typed config object.
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   *   File usage service.
   * @param \Drupal\image_widget_crop\ImageWidgetCropInterface $iwc_manager
   *   The ImageWidgetCrop manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManager $config_typed, FileUsageInterface $file_usage, ImageWidgetCropInterface $iwc_manager) {
    parent::__construct($config_factory, $config_typed);
    $this->settings = $this->config('image_widget_crop_examples.settings');
    $this->fileUsage = $file_usage;
    $this->imageWidgetCropManager = $iwc_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('file.usage'),
      $container->get('image_widget_crop.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_widget_crop_examples_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['image_widget_crop_examples.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $this->settings->get('settings.title'),
    ];

    $form['file'] = [
      '#title' => $this->t('Background Pictures'),
      '#type' => 'managed_file',
      '#description' => $this->t('The uploaded image will be displayed on this page using the image style chosen below.'),
      '#default_value' => $this->settings->get('settings.file'),
      '#upload_location' => 'public://image_widget_crop_examples/pictures',
      '#multiple' => FALSE,
    ];

    // In this example we haven't an ajax form element to load it after upload,
    // we need to upload file, save and crop file to provide a more simple,
    // and explicit example.
    $fid = isset($this->settings->get('settings.file')[0]) ? $this->settings->get('settings.file')[0] : NULL;
    if ($fid) {
      /* @var \Drupal\file\FileInterface $file */
      $file = File::load($fid);
      // The key of element are hardcoded into buildCropToForm function,
      // ATM that is mandatory but can change easily.
      $form['image_crop'] = [
        '#type' => 'image_crop',
        '#file' => $file,
        '#crop_type_list' => ['crop_16_9'],
        '#crop_preview_image_style' => 'crop_thumbnail',
        '#show_default_crop' => TRUE,
        '#show_crop_area' => FALSE,
        '#warn_mupltiple_usages' => TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Helper to expose file entity element.
   *
   * This method is mandatory to works with "buildCropToForm",
   * for Uniqueness with File entity compatibility.
   *
   * @return \Drupal\file\Entity\File|null
   *   File saved by file_manager element.
   *
   * @see \Drupal\image_widget_crop\ImageWidgetCropManager::buildCropToForm
   */
  public function getEntity() {
    return $this->file;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->settings
      ->set("settings.file", $form_state->getValue('file'))
      ->set("settings.title", $form_state->getValue('title'))
      ->set("settings.image_crop", $form_state->getValue('image_crop'));

    /* @var \Drupal\file\FileInterface $file */
    $file = !empty($form_state->getValue('file')[0]) ? File::load($form_state->getValue('file')[0]) : NULL;
    if (!empty($file)) {
      $this->fileUsage->add($file, 'image_widget_crop_examples', 'form', $file->id());
      $file->setPermanent();
      $file->save();
      $this->file = $file;
    }
    $this->settings->save();

    if (!empty($form_state->getValue('image_crop'))) {
      // Call IWC manager to attach crop defined into image file.
      $this->imageWidgetCropManager->buildCropToForm($form_state);
    }
  }

}
