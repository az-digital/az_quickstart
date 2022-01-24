<?php

namespace Drupal\az_card\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
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
   * The AZCardImageHelper service.
   *
   * @var \Drupal\az_card\AZCardImageHelper
   */
  protected $cardImageHelper;

  /**
   * Drupal\Core\Path\PathValidator definition.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->cardImageHelper = $container->get('az_card.image');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->pathValidator = $container->get('path.validator');
    return $instance;
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

    // @todo Card style selection (based on custom config entities).
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
      if (!empty($item->media)) {
        if ($media = $this->entityTypeManager->getStorage('media')->load($item->media)) {
          $media_render_array = $this->cardImageHelper->generateImageRenderArray($media);
        }
      }

      // Link.
      $link_render_array = [];
      if ($item->link_title || $item->link_uri) {
        $link_url = $this->pathValidator->getUrlIfValid($item->link_uri);
        $link_render_array = [
          '#type' => 'link',
          '#title' => $item->link_title ?? '',
          '#url' => $link_url ? $link_url : '#',
          '#attributes' => ['class' => ['btn', 'btn-default', 'w-100']],
        ];
      }

      if (!empty($item->options['link_style'])) {
        $link_render_array['#attributes']['class'] = explode(' ', $item->options['link_style']);
      }

      $card_classes = 'card';
      $column_classes = [];
      $column_classes[] = 'col-md-4 col-lg-4';
      $parent = $item->getEntity();

      // Get settings from parent paragraph.
      if (!empty($parent)) {
        if ($parent instanceof ParagraphInterface) {
          // Get the behavior settings for the parent.
          $parent_config = $parent->getAllBehaviorSettings();
          // See if the parent behavior defines some card-specific settings.
          if (!empty($parent_config['az_cards_paragraph_behavior'])) {
            $card_defaults = $parent_config['az_cards_paragraph_behavior'];
            // Is the card clickable?
            if (isset($card_defaults['card_clickable']) && $card_defaults['card_clickable'] === TRUE) {
              $link_render_array['#attributes']['class'][] = 'stretched-link';
            }

            // Set card classes according to behavior settings.
            $column_classes = [];
            if (!empty($card_defaults['az_display_settings'])) {
              $column_classes[] = $card_defaults['az_display_settings']['card_width_xs'] ?? 'col-12';
              $column_classes[] = $card_defaults['az_display_settings']['card_width_sm'] ?? 'col-sm-12';
            }
            $column_classes[] = $card_defaults['card_width'] ?? 'col-md-4 col-lg-4';
            $card_classes = $card_defaults['card_style'] ?? 'card';
          }

        }
      }

      // Handle class keys that contained multiple classes.
      $column_classes = implode(' ', $column_classes);
      $column_classes = explode(' ', $column_classes);
      $column_classes[] = 'pb-4';
      if (!empty($item->options['class'])) {
        $card_classes .= ' ' . $item->options['class'];
      }

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

}
