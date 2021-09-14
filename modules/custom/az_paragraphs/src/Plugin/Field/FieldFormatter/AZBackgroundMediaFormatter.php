<?php

namespace Drupal\az_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * Plugin implementation of the 'az_background_media_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "az_background_media_formatter",
 *   label = @Translation("Background Media"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class AZBackgroundMediaFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
      ContainerInterface $container,
      array $configuration,
      $plugin_id,
      $plugin_definition
    ) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'image_style' => '',
      'css_settings' => [
        'bg_image_selector' => 'body',
        'bg_image_color' => '#FFFFFF',
        'bg_image_x' => 'left',
        'bg_image_y' => 'top',
        'bg_image_attachment' => 'scroll',
        'bg_image_repeat' => 'no-repeat',
        'bg_image_background_size' => '',
        'bg_image_background_size_ie8' => 0,
        'bg_image_gradient' => '',
        'bg_image_media_query' => 'all',
        'bg_image_important' => 1,
        'bg_image_z_index' => 'auto',
        'bg_image_path_format' => 'absolute',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $responsive_image_style_options = $this->getResponsiveImageStyles(TRUE);
    $form['image_style'] = [
      '#title' => $this->t('Responsive image style.'),
      '#description' => $this->t(
        'Select <a href="@href_image_style">the responsive image style</a> to use.',
        [
          '@href_image_style' => Url::fromRoute('entity.responsive_image_style.collection')->toString(),
        ]
      ),
      '#type' => 'select',
      '#options' => $responsive_image_style_options,
      '#default_value' => $this->getSetting('image_style'),
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $options = $this->getResponsiveImageStyles();

    if (isset($options[$settings['image_style']])) {
      $summary[] = $this->t('URL for image style: @style', ['@style' => $options[$settings['image_style']]]);
    } else {
      $summary[] = $this->t('Original image style');
    }

    return $summary;
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];
    dpm('test');

    $media_items = $this->referencedEntities();
    dpm($media_items);

    // // Early opt-out if the field is empty.
    // if (empty($media_items)) {
    //   return $elements;
    // }

    /** @var \Drupal\media\MediaInterface[] $media_items */
    foreach ($items as $delta => $media) {
      // foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {

      // dpm($this->getMediaThumbnailUrl($media, $items->getEntity()));

      $elements[$delta] = [

        '#theme' => 'az_card',
        // '#media' => $media_render_array,
        // '#title' => $title,
        // '#body' => check_markup($item->body, $item->body_format),
        // '#link' => $link_render_array,
        // '#attributes' => ['class' => $card_classes],
    ];

    // Add cacheability of each item in the field.
    // $this->renderer->addCacheableDependency($elements[$delta], $media);

    // template_preprocess_responsive_image (
    //   [
    //       'uri' => $this->getMediaURI($media, $items->getEntity()),
    //       'responsive_image_style_id' => $this->getSetting['image_style']
    //   ]
    // );

    //   // Media.
    //   $media_render_array = [];
    //   if (!empty($item->media)) {
    //     if ($media = $this->entityTypeManager->getStorage('media')->load($item->media)) {
    //       $media_render_array = $this->cardImageHelper->generateImageRenderArray($media);
    //     }
    //   }

    //   // Link.
    //   $link_render_array = [];
    //   if ($item->link_title || $item->link_uri) {
    //     $link_url = $this->pathValidator->getUrlIfValid($item->link_uri);
    //     $link_render_array = [
    //       '#type' => 'link',
    //       '#title' => $item->link_title ?? '',
    //       '#url' => $link_url ? $link_url : '#',
    //       '#attributes' => ['class' => ['btn', 'btn-default', 'w-100']],
    //     ];
    //   }

    //   $card_classes = 'card';
    //   $column_classes = [];
    //   $column_classes[] = 'col-md-4 col-lg-4';
    //   $parent = $item->getEntity();

    //   // Get settings from parent paragraph.
    //   if (!empty($parent)) {
    //     if ($parent instanceof ParagraphInterface) {
    //       // Get the behavior settings for the parent.
    //       $parent_config = $parent->getAllBehaviorSettings();

    //       // See if the parent behavior defines some card-specific settings.
    //       if (!empty($parent_config['az_cards_paragraph_behavior'])) {
    //         $card_defaults = $parent_config['az_cards_paragraph_behavior'];

    //         // Set card classes according to behavior settings.
    //         $column_classes = [];
    //         if (!empty($card_defaults['az_display_settings'])) {
    //           $column_classes[] = $card_defaults['az_display_settings']['card_width_xs'] ?? 'col-12';
    //           $column_classes[] = $card_defaults['az_display_settings']['card_width_sm'] ?? 'col-sm-6';
    //         }
    //         $column_classes[] = $card_defaults['card_width'] ?? 'col-md-4 col-lg-4';
    //         $card_classes = $card_defaults['card_style'] ?? 'card';
    //       }

    //     }
    //   }
    }

    return $elements;
  }

  /**
   * Get the possible responsive image styles.
   *
   * @param bool $withNone
   *   True to include the 'None' option, false otherwise.
   *
   * @return array
   *   The select options.
   */
  protected function getResponsiveImageStyles($withNone = FALSE) {
    $styles = ResponsiveImageStyle::loadMultiple();
    $options = [];

    if ($withNone && empty($styles)) {
      $options[''] = t('- Defined None -');
    }

    foreach ($styles as $name => $style) {
      $options[$name] = $style->label();
    }

    return $options;
  }

  /**
   * Get the URI for the media item.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media item.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that the field belongs to.
   *
   * @return \Drupal\Core\Url|null
   *   The URL object for the media item or null if we don't want to add
   *   a link.
   */
  // protected function getMediaURI(MediaInterface $media, EntityInterface $entity) {
  //   $uri = NULL;
  //   $uri = $media->getFileUri();
  //   return $uri;
  // }

}
