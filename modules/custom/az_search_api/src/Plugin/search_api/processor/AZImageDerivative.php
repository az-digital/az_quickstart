<?php

namespace Drupal\az_search_api\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\image\ImageStyleInterface;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;
use Drupal\search_api\Utility\Utility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates absolute links to image derivatives.
 */
#[SearchApiProcessor(
  id: 'az_image_derivative',
  label: new TranslatableMarkup('Quickstart Image Derivative'),
  description: new TranslatableMarkup('Transform a file URI into an image derivative.'),
  stages: [
    'preprocess_index' => -20,
  ],
)]
class AZImageDerivative extends FieldsProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * The entity type manager.
   */
  protected ?EntityTypeManagerInterface $entityTypeManager = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->setEntityTypeManager($container->get('entity_type.manager'));
    return $processor;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager(): EntityTypeManagerInterface {
    return $this->entityTypeManager ?: \Drupal::entityTypeManager();
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager): static {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'image_style' => 'az_enterprise_thumbnail',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $options = array_map(function (ImageStyleInterface $style) {
      return Utility::escapeHtml($style->label());
    }, $this->getEntityTypeManager()->getStorage('image_style')->loadMultiple());

    $form['image_style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Image Style'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => (int) $this->configuration['image_style'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function process(&$value) {
    // Fetch the configured image style.
    $style = $this->getEntityTypeManager()->getStorage('image_style')->load($this->configuration['image_style']);
    if ($style) {
      // @todo check for valid scheme on value.
      // Render absolute url to image.
      $value = $style->buildUrl($value);
    }
  }

}
