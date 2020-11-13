<?php

namespace Drupal\az_card\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Plugin implementation of the 'az_card_default' formatter.
 *
 * @FieldFormatter(
 *   id = "az_card_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "az_card"
 *   }
 * )
 */
class AZCardDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a FormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['foo' => 'bar'] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    // TODO: Card style selection (based on custom config entities).
    $element['foo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Foo'),
      '#default_value' => $settings['foo'],
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary[] = $this->t('Foo: @foo', ['@foo' => $settings['foo']]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {

      // Format title.
      $title = $item->title ?? '';

      // Media.
      $media_render_array = [];
      if ($media = $this->entityTypeManager->getStorage('media')->load($item->media)) {
        switch ($media->bundle()) {
          case 'az_image':
            $media_render_array = $this->generateImageRenderArray($media);
            break;
        }
      }

      // Link.
      $link_render_array = [];
      if ($item->link_title || $item->link_uri) {
        $link_render_array = [
          '#type' => 'link',
          '#title' => $item->link_title ?? '',
          '#url' => $item->link_uri ? Url::fromUri($item->link_uri) : '#',
          '#attributes' => ['class' => ['btn', 'btn-default', 'w-100']],
        ];
      }

      $card_classes = 'card';
      $column_classes = 'col-xs-12 col-sm-12 col-md-6 col-lg-4';
      $parent = $item->getEntity();

      // Get settings from parent paragraph.
      if (!empty($parent)) {
        if ($parent instanceof ParagraphInterface) {
          // Get the behavior settings for the parent.
          $parent_config = $parent->getAllBehaviorSettings();

          // See if the parent behavior defines some card-specific settings.
          if (!empty($parent_config['az_cards_paragraph_behavior'])) {
            $card_defaults = $parent_config['az_cards_paragraph_behavior'];
            $column_classes = $card_defaults['card_width'] ?? 'col-xs-12 col-sm-12 col-md-6 col-lg-4';
            $card_classes = $card_defaults['card_border'] ?? 'card';
          }

        }
      }

      $column_classes = explode(' ', $column_classes);
      $column_classes[] = 'pb-4';

      $element[] = [
        '#theme' => 'az_card',
        '#media' => $media_render_array,
        '#title' => $title,
        '#body' => check_markup($item->body, $item->body_format),
        '#link' => $link_render_array,
        '#attributes' => ['class' => $card_classes],
      ];

      $element['#items'][$delta] = new \stdClass();
      $element['#items'][$delta]->_attributes = [
        'class' => $column_classes,
      ];

      $element['#attributes']['class'][] = 'content';
      $element['#attributes']['class'][] = 'h-100';
      $element['#attributes']['class'][] = 'row';
      $element['#attributes']['class'][] = 'd-flex';
      $element['#attributes']['class'][] = 'flex-wrap';
    }

    return $element;
  }

  /**
   * Prepare an image render array.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A Drupal media entity object.
   *
   * @return string[]
   *   An image render array.
   */
  protected function generateImageRenderArray(MediaInterface $media) {
    $media_render_array = [];
    $media_attributes = $media->get('field_media_az_image')->getValue();
    if ($file = $this->entityTypeManager->getStorage('file')->load($media_attributes[0]['target_id'])) {
      $image = new \stdClass();
      $image->title = NULL;
      $image->alt = $media_attributes[0]['alt'];
      $image->entity = $file;
      $image->uri = $file->getFileUri();
      $image->width = NULL;
      $image->height = NULL;

      // TODO: replace with responsive_image_formatter (?),
      // add image style(s), add cache tags, add image classes(?).
      $media_render_array = [
        '#theme' => 'image_formatter',
        '#item' => $image,
        '#image_style' => 'az_card_image',
        // '#item_attributes' => [
        // 'class' => '',
        // ],
        // '#url' => '',
      ];
      // Add the file entity to the cache dependencies.
      // This will clear our cache when this entity updates.
      $this->renderer->addCacheableDependency($media_render_array, $file);
    }
    return $media_render_array;
  }

}
