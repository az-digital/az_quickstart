<?php

namespace Drupal\crop\Plugin\ImageEffect;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\crop\CropInterface;
use Drupal\crop\CropStorageInterface;
use Drupal\crop\Entity\Crop;
use Drupal\crop\Events\AutomaticCrop;
use Drupal\crop\Events\AutomaticCropProviders;
use Drupal\crop\Events\Events;
use Drupal\image\ConfigurableImageEffectBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Crops an image resource.
 *
 * @ImageEffect(
 *   id = "crop_crop",
 *   label = @Translation("Manual crop"),
 *   description = @Translation("Applies manually provided crop to the image.")
 * )
 */
class CropEffect extends ConfigurableImageEffectBase implements ContainerFactoryPluginInterface {

  /**
   * Crop entity storage.
   *
   * @var \Drupal\crop\CropStorageInterface
   */
  protected $storage;

  /**
   * Crop type entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $typeStorage;

  /**
   * Crop entity or Automated Crop Plugin.
   *
   * @var \Drupal\crop\CropInterface|false
   */
  protected $crop = FALSE;

  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * Event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The automatic crop providers list.
   *
   * @var array
   */
  protected $automaticCropProviders;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, CropStorageInterface $crop_storage, ConfigEntityStorageInterface $crop_type_storage, ImageFactory $image_factory, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
    $this->storage = $crop_storage;
    $this->typeStorage = $crop_type_storage;
    $this->imageFactory = $image_factory;
    $this->eventDispatcher = $event_dispatcher;
    $this->automaticCropProviders = $this->getAutomaticCropProvidersList();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('image'),
      $container->get('entity_type.manager')->getStorage('crop'),
      $container->get('entity_type.manager')->getStorage('crop_type'),
      $container->get('image.factory'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    if (empty($this->configuration['crop_type']) || !$this->typeStorage->load($this->configuration['crop_type'])) {
      $this->logger->error('Manual image crop failed due to misconfigured crop type on %path.', ['%path' => $image->getSource()]);
      return FALSE;
    }

    $this->getCrop($image);
    if (!$this->crop) {
      return FALSE;
    }

    $anchor = $this->crop->anchor();
    $size = $this->crop->size();

    if (!$image->crop($anchor['x'], $anchor['y'], $size['width'], $size['height'])) {
      $this->logger->error('Manual image crop failed using the %toolkit toolkit on %path (%mimetype, %width x %height)', [
        '%toolkit' => $image->getToolkitId(),
        '%path' => $image->getSource(),
        '%mimetype' => $image->getMimeType(),
        '%width' => $image->getWidth(),
        '%height' => $image->getHeight(),
      ]
      );
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summary = [
      '#theme' => 'crop_crop_summary',
      '#data' => $this->configuration,
    ];
    $summary += parent::getSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'crop_type' => NULL,
      'automatic_crop_provider' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['crop_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Crop type'),
      '#default_value' => $this->configuration['crop_type'],
      '#options' => $this->getCropTypeOptions(),
      '#description' => $this->t('Crop type to be used for the image style.'),
    ];

    if (!empty($this->automaticCropProviders)) {
      $form['automatic_crop_provider'] = [
        '#type' => 'select',
        '#title' => $this->t('Automatic crop provider'),
        '#empty_option' => $this->t("- Select a Provider -"),
        '#options' => $this->automaticCropProviders,
        '#default_value' => $this->configuration['automatic_crop_provider'],
        '#description' => $this->t('The name of automatic crop provider to use if crop is not set for an image.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['crop_type'] = $form_state->getValue('crop_type');
    $this->configuration['automatic_crop_provider'] = $form_state->getValue('automatic_crop_provider');
  }

  /**
   * Get the available cropType options list.
   *
   * @return array
   *   The cropType options list.
   */
  public function getCropTypeOptions() {
    $options = [];
    foreach ($this->typeStorage->loadMultiple() as $type) {
      $options[$type->id()] = $type->label();
    }

    return $options;
  }

  /**
   * Gets crop entity for the image.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   Image object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\crop\CropInterface|false
   *   Crop entity or FALSE.
   */
  protected function getCrop(ImageInterface $image) {
    if ($crop = Crop::findCrop($image->getSource(), $this->configuration['crop_type'])) {
      $this->crop = $crop;
    }

    if (!$this->crop && !empty($this->configuration['automatic_crop_provider'])) {
      /** @var \Drupal\crop\Entity\CropType $crop_type */
      $crop_type = $this->typeStorage->load($this->configuration['crop_type']);
      $automatic_crop_event = new AutomaticCrop($image, $crop_type, $this->configuration);
      $this->eventDispatcher->dispatch($automatic_crop_event, Events::AUTOMATIC_CROP);
      $this->crop = $automatic_crop_event->getCrop();
    }

    return $this->crop;
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions, $uri) {
    $crop = Crop::findCrop($uri, $this->configuration['crop_type']);
    if (!$crop instanceof CropInterface) {
      return;
    }

    // The new image will have the exact dimensions defined for the crop effect.
    $dimensions['width'] = $crop->size()['width'];
    $dimensions['height'] = $crop->size()['height'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    if (isset($this->configuration['crop_type']) && $crop_type = $this->typeStorage->load($this->configuration['crop_type'])) {
      $dependencies[$crop_type->getConfigDependencyKey()] = [$crop_type->getConfigDependencyName()];
    }

    return $dependencies;
  }

  /**
   * Collect automatic crop providers.
   *
   * @return array
   *   All provider
   */
  public function getAutomaticCropProvidersList() {
    $event = new AutomaticCropProviders();
    $this->eventDispatcher->dispatch($event, Events::AUTOMATIC_CROP_PROVIDERS);

    return $event->getProviders();
  }

}
