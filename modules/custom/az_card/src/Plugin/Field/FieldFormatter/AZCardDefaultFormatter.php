<?php

namespace Drupal\az_card\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class AZCardDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface
{

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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
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
  public static function defaultSettings()
  {
    return ['interactive_links' => TRUE] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state)
  {
    $settings = $this->getSettings();

    $element['interactive_links'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Interactive Links'),
      '#default_value' => $settings['interactive_links'],
      '#description' => $this->t('If set, card links are clickable. Uncheck this setting to disable all card links. A common use-case is on the "Preview" view mode to prevent users from losing edit data if accidentally clicking on cards from the edit form.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary()
  {
    $settings = $this->getSettings();

    $interactive = 'No';
    if (!empty($settings['interactive_links'])) {
      $interactive = 'Yes';
    }
    $summary[] = $this->t('Interactive: @interactive', ['@interactive' => $interactive]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $settings = $this->getSettings();
    $element = [];

    /** @var \Drupal\az_card\Plugin\Field\FieldType\AZCardItem $item */
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

      $attached = [];

      // Link.
      $link_render_array = [];
      if ($item->link_title || $item->link_uri) {
        if (str_starts_with($item->link_uri ?? '', '/' . PublicStream::basePath())) {
          // Link to public file: use fromUri() to get the URL.
          $link_url = Url::fromUri(urldecode('base:' . $item->link_uri));
        } else {
          $link_url = $this->pathValidator->getUrlIfValid($item->link_uri ?? '<none>');
        }
        $link_render_array = [
          '#type' => 'link',
          '#title' => $item->link_title ?? '',
          '#url' => $link_url ?: Url::fromRoute('<none>'),
          '#attributes' => ['class' => ['btn', 'btn-default', 'w-100']],
        ];
        if (!empty($item->options['link_style'])) {
          $link_render_array['#attributes']['class'] = explode(' ', $item->options['link_style']);
        }
        if (empty($settings['interactive_links'])) {
          $link_render_array['#attributes']['class'][] = 'az-card-no-follow';
          $attached['library'][] = 'az_card/az_card_no_follow';
        }
      }

      // Title alignment.
      if (!empty($item->options['title_alignment'])) {
        $title_alignment = $item->options['title_alignment'];
      }

      // Title display.
      if (!empty($item->options['title_display'])) {
        $title_display = $item->options['title_display'];
      }

      $card_classes = 'card';
      $column_classes = [];
      $column_classes[] = 'col-md-4 col-lg-4';
      $parent = $item->getEntity();

      // Get settings from parent paragraph.
      if ($parent instanceof ParagraphInterface) {
        // Get the behavior settings for the parent.
        $parent_config = $parent->getAllBehaviorSettings();
        // See if the parent behavior defines some card-specific settings.
        if (!empty($parent_config['az_cards_paragraph_behavior'])) {
          $card_defaults = $parent_config['az_cards_paragraph_behavior'];

          // Set card classes according to behavior settings.
          $column_classes = [];
          if (!empty($card_defaults['az_display_settings'])) {
            $column_classes[] = $card_defaults['az_display_settings']['card_width_xs'] ?? 'col-12';
            $column_classes[] = $card_defaults['az_display_settings']['card_width_sm'] ?? 'col-sm-12';
          }
          $column_classes[] = $card_defaults['card_width'] ?? 'col-md-4 col-lg-4';
          $card_classes = $card_defaults['card_style'] ?? 'card';

          // Is the card clickable?
          if (isset($card_defaults['card_clickable']) && $card_defaults['card_clickable']) {
            if (!empty($link_render_array)) {
              $link_render_array['#attributes']['class'][] = 'stretched-link';
            }
            $card_classes .= ' shadow';
            if ($item->link_uri) {
              $card_classes .= ' card-with-link';
              $attached['library'][] = 'az_card/az_card_title_hover';
            }
          }

          // Title style.
          if (isset($card_defaults['card_title_style'])) {
            $title_style = $card_defaults['card_title_style'];
            if (!empty($media_render_array)) {
              if ($item->title && $title_style === 'title-on-image') {
                array_push($media_render_array['#item_attributes']['class'], 'img-fluid', 'image-style-az-card-image');
              }
            }
            // Force default title style if media field is not populated.
            else {
              $title_style = 'default';
            }
          }

          // Title level.
          if (isset($card_defaults['card_title_level'])) {
            $title_level = $card_defaults['card_title_level'];
          }

          // Title display.
          if (isset($card_defaults['card_title_display'])) {
            $title_display = $card_defaults['card_title_display'];
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

      $element[$delta] = [
        '#theme' => 'az_card',
        '#media' => $media_render_array,
        '#title' => $title,
        // The ProcessedText element handles cache context & tag bubbling.
        // @see \Drupal\filter\Element\ProcessedText::preRenderText()
        '#body' => [
          '#type' => 'processed_text',
          '#text' => $item->body ?? '',
          '#format' => $item->body_format,
          '#langcode' => $item->getLangcode(),
        ],
        '#link' => $link_render_array,
        '#link_url' => $this->pathValidator->getUrlIfValid($item->link_url ?? '<none>'),
        '#title_style' => $title_style ?? 'default',
        '#title_level' => $title_level ?? 'h3',
        '#title_alignment' => $title_alignment ?? 'text-left',
        '#title_display' => $title_display ?? 'h5',
        '#attributes' => ['class' => $card_classes],
        '#attached' => $attached,
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
