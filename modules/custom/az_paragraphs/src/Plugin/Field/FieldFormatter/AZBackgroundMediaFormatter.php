<?php

namespace Drupal\az_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\media\MediaInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Html;
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
class AZBackgroundMediaFormatter extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The ResponsiveBackgroundImageCssHelper.
   *
   * @var \Drupal\az_paragraphs\AZResponsiveBackgroundImageCssHelper
   */
  protected $responsiveBackroundImageCssHelper;

  /**
   * The VideoEmbedHelper.
   *
   * @var \Drupal\az_paragraphs\AZVideoEmbedHelper
   */
  protected $videoEmbedHelper;

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
    $instance->videoEmbedHelper = ($container->get('az_paragraphs.az_video_embed_helper'));
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->responsiveBackroundImageCssHelper = (
        $container->get('az_paragraphs.az_responsive_background_image_css_helper')
    );

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCssSelector($item) {
    $parent = $item->getEntity();
    $css_selector = '';
    // Get entity keys from parent paragraph.
    if (!empty($parent)) {
      if ($parent instanceof ParagraphInterface) {
        $referencing_paragraph_id = $parent->id();
        $referencing_paragraph_bundle = $parent->getType();
        $css_selector = "#" . HTML::getId($referencing_paragraph_bundle . "-" . $referencing_paragraph_id);
      }
    }

    return $css_selector;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'text_media_spacing' => '',
      'image_style' => '',
      'css_settings' => [
        'bg_image_selector' => 'body',
        'bg_image_color' => '#FFFFFF',
        'bg_image_x' => 'center',
        'bg_image_y' => 'center',
        'bg_image_attachment' => 'scroll',
        'bg_image_repeat' => 'no-repeat',
        'bg_image_background_size' => 'cover',
        'bg_image_background_size_ie8' => 0,
        'bg_image_gradient' => '',
        'bg_image_media_query' => 'all',
        'bg_image_important' => TRUE,
        'bg_image_z_index' => '-999',
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
    // Fieldset for css settings.
    $form['css_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default CSS Settings'),
      '#description' => $this->t(
        'Default CSS settings for outputting the background property.
                These settings will be concatenated to form a complete css statementthat uses the "background"
                property. For more information on the css background property see
                http://www.w3schools.com/css/css_background.asp"'
      ),
    ];

    // The selector for the background property.
    $form['css_settings']['bg_image_z_index'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Z Index'),
      '#description' => $this->t(
        'The z-index property specifies the stack order of an element. An element with greater stack order is
                      always in front of an element with a lower stack order. Note: z-index only works on positioned
                      elements (position:absolute, position:relative, or position:fixed)'
      ),
      '#default_value' => $settings['css_settings']['bg_image_z_index'],
    ];

    // The selector for the background property.
    $form['css_settings']['bg_image_color'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Color'),
      '#description' => $this->t(
        'The background color formatted as any valid css color format (e.g. hex, rgb, text, hsl)
                      [<a href="@url">css property: background-color</a>]. One per line. If the field is a multivalue
                      field, the first line will be applied to the first value, the second to the second value...
                      and so on.',
        ['@url' => 'https://developer.mozilla.org/en-US/docs/Web/CSS/linear-gradient']
      ),
      '#default_value' => $settings['css_settings']['bg_image_color'],
    ];

    // The selector for the background property.
    $form['css_settings']['bg_image_x'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Horizontal Alignment'),
      '#description' => $this->t(
        'The horizontal alignment of the background image formatted as any valid css alignment.
                      [<a href="http://www.w3schools.com/css/pr_background-position.asp">
                      css property: background-position
                      </a>]'
      ),
      '#default_value' => $settings['css_settings']['bg_image_x'],
    ];
    // The selector for the background property.
    $form['css_settings']['bg_image_y'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vertical Alignment'),
      '#description' => $this->t(
        'The vertical alignment of the background image formatted as any valid css alignment.
                      [<a href="http://www.w3schools.com/css/pr_background-position.asp">
                      css property: background-position
                      </a>]'
      ),
      '#default_value' => $settings['css_settings']['bg_image_y'],
    ];
    // The selector for the background property.
    $form['css_settings']['bg_image_attachment'] = [
      '#type' => 'radios',
      '#title' => $this->t('Background Attachment'),
      '#description' => $this->t(
        'The attachment setting for the background image.
                      [<a href="http://www.w3schools.com/css/pr_background-attachment.asp">
                      css property: background-attachment
                      </a>]'
      ),
      '#options' => [
        FALSE => $this->t('Ignore'),
        'scroll' => 'Scroll',
        'fixed' => 'Fixed',
      ],
      '#default_value' => $settings['css_settings']['bg_image_attachment'],
    ];
    // The background-repeat property.
    $form['css_settings']['bg_image_repeat'] = [
      '#type' => 'radios',
      '#title' => $this->t('Background Repeat'),
      '#description' => $this->t(
        'Define the repeat settings for the background image.
                      [<a href="http://www.w3schools.com/css/pr_background-repeat.asp">
                      css property: background-repeat
                      </a>]'
      ),
      '#options' => [
        FALSE => $this->t('Ignore'),
        'no-repeat' => $this->t('No Repeat'),
        'repeat' => $this->t('Tiled (repeat)'),
        'repeat-x' => $this->t('Repeat Horizontally (repeat-x)'),
        'repeat-y' => $this->t('Repeat Vertically (repeat-y)'),
      ],
      '#default_value' => $settings['css_settings']['bg_image_repeat'],
    ];
    // The background-size property.
    $form['css_settings']['bg_image_background_size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background Size'),
      '#description' => $this->t(
        'The size of the background (NOTE: CSS3 only. Useful for responsive designs)
                      [<a href="http://www.w3schools.com/cssref/css3_pr_background-size.asp">
                      css property: background-size
                      </a>]'
      ),
      '#default_value' => $settings['css_settings']['bg_image_background_size'],
    ];
    // background-size:cover suppor for IE8.
    $form['css_settings']['bg_image_background_size_ie8'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add background-size:cover support for ie8'),
      '#description' => $this->t(
        'The background-size css property is only supported on browsers that support CSS3.
                      However, there is a workaround for IE using Internet Explorer\'s built-in filters
                      (http://msdn.microsoft.com/en-us/library/ms532969%28v=vs.85%29.aspx).
                      Check this box to add the filters to the css. Sometimes it works well, sometimes it doesn\'t.
                      Use at your own risk'
      ),
      '#default_value' => $settings['css_settings']['bg_image_background_size_ie8'],
    ];
    // Add gradient to background-image.
    $form['css_settings']['bg_image_gradient'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gradient'),
      '#description' => $this->t(
        'Apply this background image gradient css.
                  Example: linear-gradient(red, yellow)
                  [<a href="https://www.w3schools.com/css/css3_gradients.asp">Read about gradients</a>]'
      ),
      '#default_value' => $settings['css_settings']['bg_image_gradient'],
    ];
    $form['css_settings']['bg_image_important'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add "!important" to the background property.'),
      '#description' => $this->t(
        'This can be helpful to override any existing background image or color properties added by the theme.'
      ),
      '#default_value' => $settings['css_settings']['bg_image_important'],
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
    }
    else {
      $summary[] = $this->t('Original image style');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $settings = $this->getAllSettings($items);
    $css_settings = $settings['css_settings'];
    $az_background_media = [];
    $full_width = '';
    $marquee_style = $settings['style'];
    if (!empty($settings['full_width'])) {
      $full_width = $settings['full_width'];
    }
    $media_items = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($media_items)) {
      return $elements;
    }
    $paragraph = $items->getEntity();

    /** @var \Drupal\media\MediaInterface[] $media_items */
    foreach ($media_items as $delta => $media) {

        switch ($media->bundle()) {
        case 'az_remote_video':
            $az_background_media = $this->remoteVideo($settings, $media);
            break;

        case 'az_image':
            $az_background_media = $this->image($settings, $media);
            break;

        default:
            return $az_background_media;
        }
    }

    return $az_background_media;
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
   *
   * @return \Drupal\Core\Uri|null
   *   The URI object for the media item's thumbnail image.
   */
  protected function getMediaThumbFile(MediaInterface $media) {

    $uri = NULL;
    $file = $media->getSource();
    $uri = $file->getMetadata($media, 'thumbnail_uri');
    /** @var \Drupal\file\FileInterface[] $files */
    $files = $this->entityTypeManager
      ->getStorage('file')
      ->loadByProperties(['uri' => $uri]);
    /** @var \Drupal\file\FileInterface|null $file */
    $file = reset($files) ?: NULL;

    return $file;
  }

  /**
   * Get the paragraph instance settings.
   *
   * @return array
   *   The paragraph behavior settings.
   */
  protected function getParagraphSettings(FieldItemListInterface $items) {
    $paragraph_settings = NULL;
    $parent = $items->getEntity();
    // Get settings from parent paragraph.
    if (!empty($parent)) {
      if ($parent instanceof ParagraphInterface) {
        $paragraph_settings = $parent->getAllBehaviorSettings();
        if (!empty($paragraph_settings['az_text_media_paragraph_behavior'])) {
          $paragraph_settings_all = $paragraph_settings['az_text_media_paragraph_behavior'];
        }
      }
    }
    return $paragraph_settings;
  }

  /**
   * Get and merge all settings needed to output a paragraph with a background image.
   *
   * @return array
   *   The paragraph behavior settings,
   *   field formatter settings, and default settings merged.
   */
  protected function getAllSettings(FieldItemListInterface $items) {
    $all_settings = [];
    // Paragraph instance settings override everything.
    $paragraph_settings = $this->getParagraphSettings($items);
    $all_settings += $paragraph_settings['az_text_media_paragraph_behavior'];
    // Field formatter settings.
    $all_settings += $this->getSettings();
    // Fill in all the rest of the required settings.
    $all_settings += $this->defaultSettings();

    $all_settings['css_settings']['bg_image_selector'] = $this->getCssSelector($items);
    // Get settings from parent paragraph and transforming to what the field formatter requires.
    if (!empty($all_settings['bg_attachment'])) {
      switch ($all_settings['bg_attachment']) {
        case 'bg-fixed':
          $all_settings['css_settings']['bg_image_attachment'] = 'fixed';
          break;

        default:
          $all_settings['css_settings']['bg_image_attachment'] = $default_settings['css_settings']['bg_image_attachment'];
      }
    }
    return $all_settings;
  }

  /**
   * Prepare markup for remote video. (YouTube is the only supported provider.)
   */
  private function remoteVideo(array $settings, MediaInterface $media) {

    $az_background_media = [];
    $css_settings = $settings['css_settings'];
    /** @var \Drupal\media\Plugin\media\Source\OEmbed $media_oembed */
    $media_oembed = $media->getSource();
    $view_builder = $this->entityTypeManager->getViewBuilder('media');
    $background_media = $view_builder->view($media, 'az_background');
    $provider = $media_oembed->getMetadata($media, 'provider_name');
    $html = $media_oembed->getMetadata($media, 'html');
    $thumb = $media_oembed->getMetadata($media, 'thumbnail_uri');
    $file = $this->getMediaThumbFile($media);
    $css = $this->responsiveBackroundImageCssHelper->getResponsiveBackgroundImageCss($file, $css_settings, $settings['image_style']);

    if ($provider === 'YouTube') {

      $source_url = $media->get('field_media_az_oembed_video')->value;
      $video_oembed_id = $this->videoEmbedHelper->getYoutubeIdFromUrl($source_url);

      if ($settings['style'] !== 'bottom') {
        $responsive_image_style_element = [
          'style' => [
            '#type' => 'inline_template',
            '#template' => "<style type='text/css'>{{css}}</style>",
            '#context' => [
              'css' => $css,
            ],
          ],
          $background_video = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#allowed_tags' => ['iframe', 'img'],
            '#attributes' => [
              'id' => [$video_oembed_id . '-bg-video-container'],
              'class' => [
                'az-video-loading',
                'az-video-background',
                'az-js-video-background',
              ],
              'data-youtubeid' => $video_oembed_id,
              'data-style' => $settings['style'],
            ],
            'child' => $background_media,
            '#attached' => [
              'library' => 'az_paragraphs_text_media/az_paragraphs_text_media.youtube',
              'drupalSettings' => [
                'azFieldsMedia' => [
                  'bgVideos' => [
                    $video_oembed_id => [
                      'videoId' => $video_oembed_id,
                      'start' => 0,
                    ],
                  ],
                ],
              ],
            ],
          ],
        ];
        $az_background_media[] = $responsive_image_style_element;
      }
      elseif ($settings['style'] === 'bottom') {
        $responsive_image_style_element = [
          'style' => [
            '#type' => 'inline_template',
            '#template' => "<style type='text/css'>{{css}}</style>",
            '#context' => [
              'css' => $css,
            ],
          ],
        ];
        $image_renderable = [
          '#theme' => 'image',
          '#uri' => file_create_url($thumb),
          '#alt' => $media->field_media_az_image->alt,
          '#attributes' => [
            'class' => ['img-fluid'],
          ],
        ];
        $text_on_bottom = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          'img' => $image_renderable,
          'video' => $responsive_image_style_element,
          '#attributes' => [
            'class' => ['text-on-media-bottom', 'text-on-video'],
          ],
        ];
        $az_background_media[] = $text_on_bottom;
      }
      return $az_background_media;
    }
  }

  /**
   * Prepare markup for image.
   */
  private function image(array $settings, MediaInterface $media) {
    $az_background_media = [];
    $css_settings = $settings['css_settings'];

    if ($settings['style'] !== 'bottom') {
      $file = $this->getMediaThumbFile($media);
      $css = $this->responsiveBackroundImageCssHelper->getResponsiveBackgroundImageCss($file, $css_settings, $settings['image_style']);
      $responsive_image_style_element = [
        'style' => [
          '#type' => 'inline_template',
          '#template' => "<style type='text/css'>{{css}}</style>",
          '#context' => [
            'css' => $css,
            ],
        ],
      ];

      $az_background_media[] = $responsive_image_style_element;
    }
    elseif ($settings['style'] === 'bottom') {

      $fields = $media->getFieldDefinitions();
      $image_renderable = [
        '#theme' => 'responsive_image_formatter',
        '#responsive_image_style_id' => 'az_full_width_background',
        '#item' => $media->field_media_az_image,
        '#item_attributes' => [
          'class' => ['img-fluid'],
        ],
      ];
      $text_on_bottom = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        'child' => $image_renderable,
        '#attributes' => [
          'class' => ['text-on-media-bottom'],
        ],
      ];
      $az_background_media[] = $text_on_bottom;
    }
    return $az_background_media;
  }

}