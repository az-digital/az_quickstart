<?php

namespace Drupal\az_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * Plugin implementation of the 'az_card_default' formatter.
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

//   /**
//    * The AZCardImageHelper service.
//    *
//    * @var \Drupal\az_card\AZCardImageHelper
//    */
//   protected $cardImageHelper;

//   /**
//    * Drupal\Core\Path\PathValidator definition.
//    *
//    * @var \Drupal\Core\Path\PathValidator
//    */
//   protected $pathValidator;

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

    // $instance->cardImageHelper = $container->get('az_card.image');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    // $instance->pathValidator = $container->get('path.validator');
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

    $element['image_style']['#options'] = $this->getResponsiveImageStyles(TRUE);
    $element['image_style']['#description'] = $this->t(
      'Select <a href="@href_image_style">the responsive image style</a> to use.',
      [
        '@href_image_style' => Url::fromRoute('entity.responsive_image_style.collection')->toString(),
      ]
    );

    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $options = $this->getResponsiveImageStyles();

    if (isset($options[$settings['image_style']])) {
      $summary[1] = $this->t('URL for image style: @style', ['@style' => $options[$settings['image_style']]]);
    } else {
      $summary[1] = $this->t('Original image style');
    }

    return $summary;
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $files = $this->getEntitiesToView($items, $langcode);


    // Need an empty element so views renderer will see something to render.
    $elements[0] = [];


    foreach ($files as $delta => $file) {

        template_preprocess_responsive_image(
            [
                'uri' => $file->getFileUri(),
                'responsive_image_style_id' => $settings['image_style'],
            ]
        );


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

    //   // Handle class keys that contained multiple classes.
    //   $column_classes = implode(' ', $column_classes);
    //   $column_classes = explode(' ', $column_classes);
    //   $column_classes[] = 'pb-4';
    //   if (!empty($item->options['class'])) {
    //     $card_classes .= ' ' . $item->options['class'];
    //   }

    //   $element[] = [
    //     '#theme' => 'az_card',
    //     '#media' => $media_render_array,
    //     '#title' => $title,
    //     '#body' => check_markup($item->body, $item->body_format),
    //     '#link' => $link_render_array,
    //     '#attributes' => ['class' => $card_classes],
    //   ];

    //   $element['#items'][$delta] = new \stdClass();
    //   $element['#items'][$delta]->_attributes = [
    //     'class' => $column_classes,
    //   ];

    //   $element['#attributes']['class'][] = 'content';
    //   $element['#attributes']['class'][] = 'h-100';
    //   $element['#attributes']['class'][] = 'row';
    //   $element['#attributes']['class'][] = 'd-flex';
    //   $element['#attributes']['class'][] = 'flex-wrap';
    }

    return $element;
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

}
