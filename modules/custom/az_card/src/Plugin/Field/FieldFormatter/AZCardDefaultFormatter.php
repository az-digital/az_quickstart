<?php

namespace Drupal\az_card\Plugin\Field\FieldFormatter;

use Drupal\az_card\Plugin\Field\FieldType\AZCardItem;
use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'az_card_default' formatter.
 */
#[FieldFormatter(
  id: 'az_card_default',
  label: new TranslatableMarkup('Default'),
  field_types: [
    'az_card',
  ],
)]
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
    return ['interactive_links' => TRUE] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
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
  public function settingsSummary() {
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
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    $element = [];

    foreach ($items as $delta => $item) {
      assert($item instanceof AZCardItem);

      $title_classes = [];
      $body_classes = [];
      $title_style = 'default';
      $title_link_stretched = FALSE;
      $image_only = FALSE;
      $hover_colors = FALSE;
      $fit_content = FALSE;
      // If Card Background is "Cloud (Contrast Hover)", its saved value is
      // bg-cloud, the class that Arizona Bootstrap gives card hover colors to.
      $contrast_hover = ($item->options['class'] ?? '') === 'bg-cloud';

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
      $link_url = '';
      if ($item->link_title || $item->link_uri) {
        if (str_starts_with($item->link_uri ?? '', '/' . PublicStream::basePath())) {
          // Link to public file: use fromUri() to get the URL.
          $link_url = Url::fromUri(urldecode('base:' . $item->link_uri));
        }
        else {
          // Check if the link is an anchor within the current page.
          if (str_starts_with($item->link_uri ?? '', "#")) {
            $link_url = Url::fromUserInput($item->link_uri);
          }
          else {
            $link_url = $this->pathValidator->getUrlIfValid($item->link_uri ?? '<none>');
          }
        }
        $link_render_array = [
          '#type' => 'link',
          '#title' => $item->link_title ?? '',
          '#url' => $link_url ?: Url::fromRoute('<none>'),
        ];
        $link_render_array['#attributes']['class'] =
          empty($item->options['link_style']) ?
          ['btn', 'w-100', 'btn-red'] :
          explode(' ', $item->options['link_style']);
        if (empty($settings['interactive_links'])) {
          $link_render_array['#attributes']['class'][] = 'az-card-no-follow';
          $attached['library'][] = 'az_card/az_card_no_follow';
        }
        $attached['library'][] = 'az_card/az_card_title_hover';
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
          $clickable = !empty($card_defaults['card_clickable']);
          $fit_content = !empty($card_defaults['card_fit_content']);

          // Title style.
          if (isset($card_defaults['card_title_style'])) {
            $title_style = $card_defaults['card_title_style'];
            // Force default title style if media field is not populated.
            if (empty($media_render_array)) {
              $title_style = 'default';
            }
          }
          $title_on_image = $item->title && $title_style === 'title-on-image';

          // If a title on image card shows nothing under its image (no body,
          // and a link that is hidden or absent), it is an image tile: a
          // square image with the title on it. For example, a card with an
          // empty body and a "Red button" link is not a tile, because the
          // button shows under the image.
          $link_hidden = empty($item->link_title) || in_array('visually-hidden', $link_render_array['#attributes']['class'] ?? [], TRUE);
          $image_only = $title_on_image && ($item->body ?? '') === '' && $link_hidden;

          // If a clickable card with a "Contrast Hover" background has a link,
          // it gets the Arizona Bootstrap card hover colors. For example,
          // .card.bg-cloud.hover is Cloud, then Arizona Blue on hover. An
          // image tile is left out, because its image covers the background.
          $hover_colors = $contrast_hover && $clickable && !empty($item->link_uri) && !$image_only;

          // Is the card clickable?
          if ($clickable) {
            if (!empty($link_render_array)) {
              $link_render_array['#attributes']['class'][] = 'stretched-link';
            }
            // "Contrast Hover" cards and image tiles have no shadow in the
            // brand design.
            $card_classes .= ($contrast_hover || $image_only) ? ' overflow-hidden' : ' shadow overflow-hidden';
            if ($item->link_uri) {
              $card_classes .= ' hover';
              // The Arizona Bootstrap hover colors include an underline on
              // the title.
              if (!$hover_colors) {
                $title_classes[] = 'hover-text-underline';
              }
              if (!empty($media_render_array)) {
                $media_render_array['#item_attributes']['class'][] = 'hover-img-zoom-in';
              }
              // An image tile shows no link under the image, so the link in
              // its title covers the card.
              $title_link_stretched = $image_only;
            }
          }

          if ($title_on_image) {
            if ($image_only) {
              // Square image that fills the card, with no card border.
              // h-100 keeps img-fluid from overriding the ratio's height.
              array_push($media_render_array['#item_attributes']['class'], 'object-fit-cover', 'h-100', 'image-style-az-card-image');
              $card_classes = implode(' ', array_unique(array_merge(explode(' ', $card_classes), ['border-0'])));
            }
            else {
              array_push($media_render_array['#item_attributes']['class'], 'img-fluid', 'image-style-az-card-image');
            }
            // Clip the image to the card's round corners. Rationale: when
            // no card body follows the image, the image is the bottom of
            // the card, and .card-img-top rounds only its top corners.
            if (!str_contains($card_classes, 'overflow-hidden')) {
              $card_classes .= ' overflow-hidden';
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
      $card_attributes = [];
      if (!empty($item->options['class'])) {
        $card_classes .= ' ' . $item->options['class'];
      }
      // A Cloud card has the brand's Cloud card colors: Tinta text and an
      // Azurite title. The card's .text-bg-* class sets black text, so the
      // classes go on the card body and the title.
      if (in_array($item->options['class'] ?? '', AZCardItem::CLOUD_BACKGROUND_CLASSES, TRUE)) {
        $body_classes[] = 'text-tinta';
        // A title on the image stays white, on its gradient.
        if ($title_style === 'default') {
          $title_classes[] = 'text-azurite';
        }
      }
      // If a hover colors card has the "Bordered cards" style, keep its
      // border. Arizona Bootstrap sets the border width of a hover colors
      // card to 0.
      if ($hover_colors && !in_array('border-0', explode(' ', $card_classes), TRUE)) {
        $card_attributes['style'] = '--bs-card-border-width: var(--bs-border-width);';
      }
      $card_attributes['class'] = $card_classes;

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
        '#link_url' => $link_url,
        '#title_style' => $title_style,
        '#title_level' => $title_level ?? 'h3',
        '#title_alignment' => $title_alignment ?? 'text-start',
        '#title_display' => $title_display ?? 'h5',
        '#title_classes' => $title_classes,
        '#body_classes' => $body_classes,
        '#title_link_stretched' => $title_link_stretched,
        '#image_only' => $image_only,
        '#fit_content' => $fit_content,
        '#icon' => $item->options['icon'] ?? '',
        '#attributes' => $card_attributes,
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
