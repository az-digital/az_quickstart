<?php

namespace Drupal\ib_dam_media\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ib_dam_media\MediaTypeMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MediaConfigurationForm.
 *
 * Form class to store ib_dam_media configuration like media types mapping.
 *
 * @package Drupal\ib_dam_media\Form
 */
final class MediaConfigurationForm extends ConfigFormBase {

  /**
   * Media Type Matcher instance.
   *
   * @var \Drupal\ib_dam_media\MediaTypeMatcher
   */
  protected $mediaTypeMatcher;

  /**
   * Entity Type Manager instance.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a MediaConfigurationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\ib_dam_media\MediaTypeMatcher $media_type_matcher
   *   The media type matcher to find right media types matches.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, MediaTypeMatcher $media_type_matcher) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->mediaTypeMatcher  = $media_type_matcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('ib_dam_media.media_type_matcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ib_dam_media_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ib_dam_media.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ib_dam_media.settings');

    $form['media_types_table'] = [
      '#type' => 'details',
      '#title' => $this->t('Mapping options'),
      '#description' => $this->t('Map media type of a remote resource with a local media type.'),
      '#attributes' => ['id' => 'mapping-options-wrapper'],
      '#open' => TRUE,
    ];

    $mapping = [
      '#type' => 'table',
      '#header' => [
        'source_type' => $this->t('Source Asset Type'),
        'media_type' => $this->t('Media Type'),
        'action' => '',
      ],
      '#title' => 'Type Mapping',
    ];

    $form['dialog_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Media Library Dialog Mode'),
      '#default_value' => $config->get('dialog_mode') ?? 'regular',
      '#options' => [
        'regular' => $this->t('Regular'),
        'stacked' => $this->t('Stacked'),
      ],
      'regular' => [
        '#description' => $this->t('Use this option if you are not sure what it is for.'),
      ],
      'stacked' => [
        '#description' => $this->t('Do not close Media Library modal, but open on top of it Asset Browser modal window. See <a href=":url">issue</a>.', [':url' => 'https://www.drupal.org/project/media_library_form_element/issues/3155697#comment-13725927']),
      ],
    ];

    $asset_types           = $this->mediaTypeMatcher->getSupportedSourceTypes();
    $saved_media_types     = $config->get('media_types');
    $submitted_media_types = $form_state->getValue('media_types', []);

    // Get setting from config or submitted values.
    if (!empty($saved_media_types) && empty($submitted_media_types)) {
      $map = static::extractMappingFromConfig($saved_media_types);
    }
    else {
      $map = static::extractMappingFromTable($submitted_media_types);
    }

    foreach ($asset_types as $type_id => $type) {
      $mapping[$type_id] = [
        'source_type' => [
          'id'    => ['#type' => 'value', '#value' => $type_id],
          'label' => ['#type' => 'item', '#markup' => $type['label']],
        ],
        'media_type' => [
          'id' => [
            '#type' => 'select',
            '#options' => $this->mediaTypeMatcher->getSupportedMediaTypes($type_id),
            '#default_value' => isset($map[$type_id]) ? $map[$type_id] : FALSE,
            '#empty_option' => '--',
            '#empty_value' => '',
          ],
        ],
      ];
    }

    $form['media_types_table']['media_types'] = $mapping;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $types = [];

    foreach ($form_state->getValue('media_types', []) as $type_id => $type) {
      if (empty($type['media_type']['id'])) {
        continue;
      }
      $types[$type_id] = [
        'source_type' => $type_id,
        'media_type' => $type['media_type']['id'],
      ];
    }

    $this->config('ib_dam_media.settings')
      ->set('media_types', $types)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Internal helper function to get mapping values from table data structure.
   *
   * @param array $form_values
   *   The form values of the table form element.
   *
   * @return array
   *   Array of mapped items: source type <--> media type id.
   */
  private static function extractMappingFromTable(array $form_values = []) {
    $mapping = [];
    foreach ($form_values as $type => $item) {
      if (!empty($item['media_type']['id'])) {
        $mapping[$type] = $item['media_type']['id'];
      }
    }
    return $mapping;
  }

  /**
   * Internal helper function to get mapping values config.
   *
   * @param array $values
   *   The config values.
   *
   * @return array
   *   Array of mapped items: source type <--> media type id.
   */
  private static function extractMappingFromConfig(array $values = []) {
    $mapping = [];
    foreach ($values as $item) {
      $mapping[$item['source_type']] = $item['media_type'];
    }
    return $mapping;
  }

}
