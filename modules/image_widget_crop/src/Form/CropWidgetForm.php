<?php

namespace Drupal\image_widget_crop\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crop\Entity\CropType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure ImageWidgetCrop general settings for this site.
 */
class CropWidgetForm extends ConfigFormBase {

  /**
   * The settings of image_widget_crop configuration.
   *
   * @var array
   *
   * @see \Drupal\Core\Config\Config
   */
  protected $settings;

  /**
   * Instance of ImageWidgetCropManager object.
   *
   * @var \Drupal\image_widget_crop\ImageWidgetCropInterface
   */
  protected $imageWidgetCropManager;

  /**
   * The module handler to use to load modules.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->settings = $container->get('config.factory')->getEditable('image_widget_crop.settings');
    $instance->imageWidgetCropManager = $container->get('image_widget_crop.manager');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->httpClient = $container->get('http_client');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_widget_crop_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['image_widget_crop.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $url = 'https://cdnjs.com/libraries/cropper';
    $cdn_js = IMAGE_WIDGET_CROP_JS_CDN;
    $cdn_css = IMAGE_WIDGET_CROP_CSS_CDN;

    $form['library'] = [
      '#type' => 'details',
      '#title' => $this->t('Cropper library settings'),
      '#description' => $this->t('Changes here require a cache rebuild to take full effect.'),
    ];

    $form['library']['library_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Cropper library'),
      '#description' => $this->t('Set the URL or local path for the file, or leave empty to use the installed library (if present) or a <a href="@file">CDN</a> fallback. You can retrieve the library file from <a href="@url">Cropper CDN</a>.', [
        '@url' => $url,
        '@file' => $cdn_js,
      ]),
      '#default_value' => $this->settings->get('settings.library_url'),
    ];

    $form['library']['css_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Cropper CSS file'),
      '#description' => $this->t('Set the URL or local path for the file, or leave empty to use installed library (if installed) or a <a href="@file">CDN</a> fallback. You can retrieve the CSS file from <a href="@url">Cropper CDN</a>.', [
        '@url' => $url,
        '@file' => $cdn_css,
      ]),
      '#default_value' => $this->settings->get('settings.css_url'),
    ];

    // Indicate which files are used when custom urls are not set.
    if ($this->moduleHandler->moduleExists('libraries')
      && ($info = libraries_detect('cropper')) && $info['installed']) {
      $form['library']['library_url']['#attributes']['placeholder'] = $info['library path'] . '/dist/' . key($info['files']['js']);
      $form['library']['css_url']['#attributes']['placeholder'] = $info['library path'] . '/dist/' . key($info['files']['css']);
    }
    else {
      $form['library']['library_url']['#attributes']['placeholder'] = $cdn_js;
      $form['library']['css_url']['#attributes']['placeholder'] = $cdn_css;
    }

    $form['image_crop'] = [
      '#type' => 'details',
      '#title' => $this->t('General configuration'),
    ];

    $form['image_crop']['crop_preview_image_style'] = [
      '#title' => $this->t('Crop preview image style'),
      '#type' => 'select',
      '#options' => image_style_options(FALSE),
      '#default_value' => $this->settings->get('settings.crop_preview_image_style'),
      '#description' => $this->t('The preview image will be shown while editing the content.'),
      '#weight' => 15,
    ];

    $form['image_crop']['crop_list'] = [
      '#title' => $this->t('Crop Type'),
      '#type' => 'select',
      '#options' => $this->imageWidgetCropManager->getAvailableCropType(CropType::getCropTypeNames()),
      '#empty_option' => $this->t('<@no-preview>', ['@no-preview' => $this->t('no preview')]),
      '#default_value' => $this->settings->get('settings.crop_list'),
      '#multiple' => TRUE,
      '#description' => $this->t('The type of crop to apply to your image. If your Crop Type not appear here, set an image style use your Crop Type'),
      '#weight' => 16,
    ];

    $form['image_crop']['show_crop_area'] = [
      '#title' => $this->t('Always expand crop area'),
      '#type' => 'checkbox',
      '#default_value' => $this->settings->get('settings.show_crop_area'),
    ];

    $form['image_crop']['warn_multiple_usages'] = [
      '#title' => $this->t('Warn user when a file have multiple usages'),
      '#type' => 'checkbox',
      '#default_value' => $this->settings->get('settings.warn_multiple_usages'),
    ];

    $form['image_crop']['show_default_crop'] = [
      '#title' => $this->t('Show default crop area'),
      '#type' => 'checkbox',
      '#default_value' => $this->settings->get('settings.show_default_crop'),
    ];

    $form['image_crop']['crop_types_required'] = [
      '#title' => $this->t('Set Crop Type as required'),
      '#type' => 'select',
      '#options' => $this->imageWidgetCropManager->getAvailableCropType(CropType::getCropTypeNames()),
      '#empty_option' => $this->t("- Any crop selected -"),
      '#default_value' => $this->settings->get('settings.crop_types_required'),
      '#multiple' => TRUE,
      '#description' => $this->t('Set active crop as required.'),
      '#weight' => 16,
    ];

    $form['image_crop']['notify'] = [
      '#type' => 'details',
      '#title' => $this->t('Cropping Notifications'),
    ];

    $form['image_crop']['notify']['notify_apply'] = [
      '#title' => $this->t('Crop apply'),
      '#type' => 'checkbox',
      '#default_value' => $this->settings->get('settings.notify_apply'),
    ];

    $form['image_crop']['notify']['notify_update'] = [
      '#title' => $this->t('Crop update'),
      '#type' => 'checkbox',
      '#default_value' => $this->settings->get('settings.notify_update'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validation for cropper library.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (!empty($form_state->getValue('library_url')) || !empty($form_state->getValue('css_url'))) {
      $files = [
        'library' => $form_state->getValue('library_url'),
        'css' => $form_state->getValue('css_url'),
      ];
      if (empty($files['library']) || empty($files['css'])) {
        $form_state->setErrorByName('plugin', $this->t('Please provide both a library and a CSS file when using custom URLs.'));
      }
      else {
        foreach ($files as $type => $file) {
          // Verify that both files exist.
          $is_local = parse_url($file, PHP_URL_SCHEME) === NULL && strpos($file, '//') !== 0;
          if ($is_local && !file_exists($file)) {
            $form_state->setErrorByName($type . '_url', $this->t('The provided local file does not exist.'));
          }
          elseif (!$is_local) {
            try {
              $result = $this->httpClient->request('GET', $file);
              if ($result->getStatusCode() != 200) {
                throw new \Exception($result->getReasonPhrase(), 1);
              }
            }
            catch (\Exception $e) {
              $form_state->setErrorByName($type . '_url', $this->t('The remote URL for the library does not appear to be valid: @message.', [
                '@message' => $e->getMessage(),
              ]));
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // We need to rebuild the library cache if we switch from remote to local
    // library or vice-versa.
    Cache::invalidateTags(['library_info']);

    // Set Default (CDN) libraries urls if empty.
    $this->setDefaultLibrariesUrls($form_state);

    $this->settings
      ->set("settings.library_url", $form_state->getValue('library_url'))
      ->set("settings.css_url", $form_state->getValue('css_url'))
      ->set("settings.crop_preview_image_style", $form_state->getValue('crop_preview_image_style'))
      ->set("settings.show_default_crop", $form_state->getValue('show_default_crop'))
      ->set("settings.show_crop_area", $form_state->getValue('show_crop_area'))
      ->set("settings.warn_multiple_usages", $form_state->getValue('warn_multiple_usages'))
      ->set("settings.crop_list", $form_state->getValue('crop_list'))
      ->set("settings.crop_types_required", $form_state->getValue('crop_types_required'))
      ->set("settings.notify_apply", $form_state->getValue('notify_apply'))
      ->set("settings.notify_update", $form_state->getValue('notify_update'));
    $this->settings->save();
  }

  /**
   * Set the default state of cropper libraries files url.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function setDefaultLibrariesUrls(FormStateInterface $form_state) {
    if (empty($form_state->getValue('library_url')) || empty($form_state->getValue('css_url'))) {
      $form_state->setValue('library_url', $form_state->getCompleteForm()['library']['library_url']['#attributes']['placeholder']);
      $form_state->setValue('css_url', $form_state->getCompleteForm()['library']['css_url']['#attributes']['placeholder']);
    }
  }

}
