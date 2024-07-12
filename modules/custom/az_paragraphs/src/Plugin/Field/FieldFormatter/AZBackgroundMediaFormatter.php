<?php

namespace Drupal\az_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
//phpcs:ignore Security.BadFunctions.FilesystemFunctions.WarnWeirdFilesystem
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'az_background_media_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "az_background_media",
 *   label = @Translation("Background Media"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   description = @Translation("This formatter can be enabled on any entity reference
 *   field, but will only create a background image for media entities of
 *   bundle type az_image, or az_remote_video.
 *   For az_remote_video, it must be a youtube video."),
 * )
 */
class AZBackgroundMediaFormatter extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

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
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->token = $container->get('token');
    $instance->currentUser = $container->get('current_user');
    $instance->videoEmbedHelper = $container->get('az_paragraphs.az_video_embed_helper');
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'image_style' => '',
      'css_settings' => [
        'z_index' => 'auto',
        'color' => '#FFFFFF',
        'x' => 'center',
        'y' => 'center',
        'attachment' => 'scroll',
        'repeat' => 'no-repeat',
        'size' => 'cover',
        'important' => FALSE,
        'selector' => 'body',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->getSettings();
    $responsive_image_style_options = $this->getResponsiveImageStyles(TRUE);

    $form['image_style'] = [
      '#title' => $this->t('Responsive image style'),
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
                These settings will be concatenated to form a complete css statement that uses the "background"
                property. For more information on the css background property see
                http://www.w3schools.com/css/css_background.asp"'
      ),
    ];
    // The z-index property.
    $form['css_settings']['z_index'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Z Index'),
      '#description' => $this->t(
        'The z-index property specifies the stack order of an element. An element with greater stack order is
                      always in front of an element with a lower stack order. Note: z-index only works on positioned
                      elements (position:absolute, position:relative, or position:fixed)'
      ),
      '#default_value' => $settings['css_settings']['z_index'],
    ];
    // The background-color property.
    $form['css_settings']['color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Color'),
      '#description' => $this->t(
        'The background color formatted as any valid css color format (e.g. hex, rgb, text, hsl)
                      [<a href="@url">css property: background-color</a>].',
        ['@url' => 'https://developer.mozilla.org/en-US/docs/Web/CSS/linear-gradient']
      ),
      '#default_value' => $settings['css_settings']['color'],
    ];
    // The background-size x property.
    $form['css_settings']['x'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Horizontal Alignment'),
      '#description' => $this->t(
        'The horizontal alignment of the background image formatted as any valid css alignment.
                      [<a href="http://www.w3schools.com/css/pr_background-position.asp">
                      css property: background-position
                      </a>]'
      ),
      '#default_value' => $settings['css_settings']['x'],
    ];
    // The background-size y property.
    $form['css_settings']['y'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vertical Alignment'),
      '#description' => $this->t(
        'The vertical alignment of the background image formatted as any valid css alignment.
                      [<a href="http://www.w3schools.com/css/pr_background-position.asp">
                      css property: background-position
                      </a>]'
      ),
      '#default_value' => $settings['css_settings']['y'],
    ];
    // The background-attachemetn property.
    $form['css_settings']['attachment'] = [
      '#type' => 'radios',
      '#title' => $this->t('Background Attachment'),
      '#description' => $this->t(
        'The attachment setting for the background image.
                      [<a href="http://www.w3schools.com/css/pr_background-attachment.asp">
                      css property: background-attachment
                      </a>]'
      ),
      '#options' => [
        'scroll' => $this->t('Scroll'),
        'fixed' => $this->t('Fixed'),
      ],
      '#empty_option' => $this->t('Ignore'),
      '#default_value' => $settings['css_settings']['attachment'],
    ];
    // The background-repeat property.
    $form['css_settings']['repeat'] = [
      '#type' => 'radios',
      '#title' => $this->t('Background Repeat'),
      '#description' => $this->t(
        'Define the repeat settings for the background image.
                      [<a href="http://www.w3schools.com/css/pr_background-repeat.asp">
                      css property: background-repeat
                      </a>]'
      ),
      '#options' => [
        'no-repeat' => $this->t('No Repeat'),
        'repeat' => $this->t('Tiled (repeat)'),
        'repeat-x' => $this->t('Repeat Horizontally (repeat-x)'),
        'repeat-y' => $this->t('Repeat Vertically (repeat-y)'),
      ],
      '#empty_option' => $this->t('Ignore'),
      '#default_value' => $settings['css_settings']['repeat'],
    ];
    // The background-size property.
    $form['css_settings']['size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background Size'),
      '#description' => $this->t(
        'The size of the background (NOTE: CSS3 only. Useful for responsive designs)
                      [<a href="http://www.w3schools.com/cssref/css3_pr_background-size.asp">
                      css property: background-size
                      </a>]'
      ),
      '#default_value' => $settings['css_settings']['size'],
    ];
    $form['css_settings']['important'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add "!important" to the background property.'),
      '#description' => $this->t(
        'This can be helpful to override any existing background image or color properties added by the theme.'
      ),
      '#default_value' => $settings['css_settings']['important'],
    ];
    // The selector for the background property.
    $form['css_settings']['selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Selector'),
      '#description' => $this->t(
        'A valid CSS selector that will be used to apply the background image. Tokens are supported.'
      ),
      '#default_value' => $settings['css_settings']['selector'],
    ];
    // The token help relevant to this entity type.
    if (isset($form['#entity_type'])) {
      $form['css_settings']['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['user', $form['#entity_type']],
      ];
    }
    else {
      $form['css_settings']['token_help'] = [
        '#theme' => 'token_tree_link',
      ];
    }

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $settings = $this->getSettings();
    $options = $this->getResponsiveImageStyles();

    if (isset($options[$settings['image_style']])) {
      $summary[] = $this->t('Responsive image style: @style', ['@style' => $options[$settings['image_style']]]);
    }
    else {
      $summary[] = $this->t('Original image style');
    }
    $summary[] = Markup::create('<p></p><strong>CSS settings:</strong>');
    if (isset($settings['css_settings']['selector'])) {
      $summary[] = $this->t('CSS selector: @selector', ['@selector' => $settings['css_settings']['selector']]);
    }
    if (isset($settings['z_index'])) {
      $summary[] = $this->t('z-index: @z-index', ['@z-index' => $settings['css_settings']['z_index']]);
    }
    if (isset($settings['css_settings']['important']) && $settings['css_settings']['important']) {
      $summary[] = $this->t('Add !important: True');
    }
    if (isset($settings['css_settings']['size'])) {
      $summary[] = $this->t('background-size: @size', ['@size' => $settings['css_settings']['size']]);
    }
    if (isset($settings['css_settings']['repeat'])) {
      $summary[] = $this->t('background-repeat: @repeat', ['@repeat' => $settings['css_settings']['repeat']]);
    }
    if (isset($settings['css_settings']['attachment'])) {
      $summary[] = $this->t('background-attachment: @attachment', ['@attachment' => $settings['css_settings']['attachment']]);
    }
    if (isset($settings['css_settings']['y'])) {
      $summary[] = $this->t('background-position-y: @y', ['@y' => $settings['css_settings']['y']]);
    }
    if (isset($settings['css_settings']['x'])) {
      $summary[] = $this->t('background-position-x: @x', ['@x' => $settings['css_settings']['x']]);
    }
    if (isset($settings['css_settings']['color'])) {
      $summary[] = $this->t('background-color: @color', ['@color' => $settings['css_settings']['color']]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $settings = $this->getAllSettings($items);
    $element = [];
    $full_width = '';
    $marquee_style = $settings['style'];
    /** @var \Drupal\media\MediaInterface[] $media_items */
    $media_items = $this->getEntitiesToView($items, $langcode);
    $paragraph = $items->getEntity();

    // Early opt-out if the field is empty.
    if (empty($media_items)) {
      return $element;
    }

    if (!empty($settings['full_width'])) {
      $full_width = $settings['full_width'];
    }

    // Prepare token data in bg image CSS selector.
    $token_data = [
      'user' => $this->currentUser,
      $items->getEntity()->getEntityTypeId() => $items->getEntity(),
    ];
    // Replace token with value.
    $settings['css_settings']['selector'] = $this->token->replace(
      $settings['css_settings']['selector'],
      $token_data
    );
    // Replace underscores with hyphens in selector.
    $settings['css_settings'] = str_replace(['_'], '-', $settings['css_settings']);

    /** @var \Drupal\media\MediaInterface $media */
    foreach ($media_items as $delta => $media) {
      $element['#media_type'] = $media->bundle();

      switch ($media->bundle()) {
        case 'az_remote_video':
          $element[$delta] = $this->remoteVideo($settings, $media);
          break;

        case 'az_image':
          $element[$delta] = $this->image($settings, $media);
          break;

        default:
          return $element;
      }

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
  protected function getResponsiveImageStyles($withNone = FALSE): array {

    $styles = $this->entityTypeManager->getStorage('responsive_image_style')->loadMultiple();
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
   * @return \Drupal\file\FileInterface|null
   *   The URI object for the media item's thumbnail image.
   */
  protected function getMediaThumbFile(MediaInterface $media): ?FileInterface {
    $fid = $media->get('thumbnail')->target_id;
    $file = $this->entityTypeManager->getStorage('file')->load($fid);

    return $file;

  }

  /**
   * Get the paragraph instance settings.
   *
   * @return array
   *   The paragraph behavior settings.
   */
  protected function getParagraphSettings(FieldItemListInterface $items): array {
    $paragraph_settings = [];
    $parent = $items->getEntity();
    // Get settings from parent paragraph.
    if ($parent instanceof ParagraphInterface) {
      $paragraph_settings = $parent->getAllBehaviorSettings();
      if (!empty($paragraph_settings['az_text_media_paragraph_behavior'])) {
        $paragraph_settings_all = $paragraph_settings['az_text_media_paragraph_behavior'];
      }
    }
    return $paragraph_settings;
  }

  /**
   * Get and merge all settings needed.
   *
   * @return array
   *   The paragraph behavior settings,
   *   field formatter settings, and default settings merged.
   */
  protected function getAllSettings(FieldItemListInterface $items): array {
    $all_settings = [];
    // Paragraph instance settings override everything.
    $paragraph_settings = $this->getParagraphSettings($items);
    $all_settings += $paragraph_settings['az_text_media_paragraph_behavior'] ?? [];
    // Field formatter settings.
    $all_settings += $this->getSettings();
    // Fill in all the rest of the required settings.
    $all_settings += $this->defaultSettings();

    // Get settings from parent paragraph and transforming to
    // what the field formatter requires.
    if (!empty($all_settings['bg_attachment'])) {
      switch ($all_settings['bg_attachment']) {
        case 'bg-fixed':
          $all_settings['css_settings']['attachment'] = 'fixed';
          break;

        default:
          $all_settings['css_settings']['attachment'] = $this->defaultSettings()['css_settings']['attachment'];
      }
    }
    return $all_settings;

  }

  /**
   * Prepare markup for remote video.
   *
   * YouTube is currently the only supported provider.
   *
   * @param array $settings
   *   The merged paragraph behavior settings,
   *   field formatter settings, and default settings.
   * @param \Drupal\media\MediaInterface $media
   *   The media item.
   *
   * @return array
   *   The remote video render array for az_background_media element.
   */
  protected function remoteVideo(array $settings, MediaInterface $media): array {

    $az_background_media = [];
    $css_settings = $settings['css_settings'];
    /** @var \Drupal\media\Plugin\media\Source\OEmbed $media_oembed */
    $media_oembed = $media->getSource();
    $view_builder = $this->entityTypeManager->getViewBuilder('media');
    $background_media = $view_builder->view($media, 'az_background');
    $provider = $media_oembed->getMetadata($media, 'provider_name');
    $file_uri = $this->getMediaThumbFile($media)->getFileUri();

    if ($settings['style'] === 'bottom') {
      $css_settings['selector'] = $css_settings['selector'] . ' .text-on-video';
    }
    if ($provider === 'YouTube') {
      $source_url = $media->get('field_media_az_oembed_video')->value;
      $video_oembed_id = $this->videoEmbedHelper->getYoutubeIdFromUrl($source_url);
      $attached_library = [
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
      ];
      $responsive_image_style_element = [
        '#theme' => 'az_responsive_background_image',
        '#selector' => $css_settings['selector'],
        '#repeat' => $css_settings['repeat'],
        '#important' => $css_settings['important'],
        '#color' => $css_settings['color'],
        '#x' => $css_settings['x'],
        '#y' => $css_settings['y'],
        '#size' => $css_settings['size'],
        '#attachment' => $css_settings['attachment'],
        '#responsive_image_style_id' => $settings['image_style'],
        '#uri' => $file_uri,
        '#z_index' => $css_settings['z_index'],
      ];
      $background_video = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#allowed_tags' => ['iframe', 'img'],
        '#attributes' => [
          'id' => [$video_oembed_id . '-bg-video-container'],
          'class' => [
            'az-video-background',
            'az-js-video-background',
          ],
          'data-youtubeid' => $video_oembed_id,
          'data-style' => $settings['style'],
          'data-parentid' => HTML::getId($settings['css_settings']['selector']),
        ],
        'child' => $background_media,
        '#attached' => $attached_library,
      ];

      if ($settings['style'] !== 'bottom') {
        $az_background_media[] = $responsive_image_style_element;
        $az_background_media[] = $background_video;
        if ($settings['text_media_spacing'] === 'az-aspect-ratio' && isset($settings['full_width']) && $settings['full_width'] === 'full-width-background') {
          $image_renderable = [
            '#theme' => 'responsive_image_formatter',
            '#responsive_image_style_id' => 'az_full_width_background',
            '#item' => $media->get('thumbnail'),
            '#item_attributes' => [
              'class' => ['img-fluid', ' w-100', 'invisible'],
            ],
          ];
          $az_background_media[] = $image_renderable;
        }
      }
      elseif ($settings['style'] === 'bottom') {
        $image_renderable = [
          '#theme' => 'responsive_image_formatter',
          '#responsive_image_style_id' => 'az_full_width_background',
          '#item' => $media->get('thumbnail'),
          '#item_attributes' => [
            'class' => ['img-fluid'],
          ],
        ];
        $text_on_bottom = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          'img' => $image_renderable,
          'style' => $responsive_image_style_element,
          'video' => $background_video,
          '#attributes' => [
            'class' => ['text-on-media-bottom', 'text-on-video'],
          ],
        ];
        $az_background_media[] = $text_on_bottom;

      }
    }
    return $az_background_media;
  }

  /**
   * Prepare markup for image.
   *
   * @param array $settings
   *   The merged paragraph behavior settings,
   *   field formatter settings, and default settings.
   * @param \Drupal\media\MediaInterface $media
   *   The media item.
   *
   * @return array
   *   The image render array for az_background_media element.
   */
  protected function image(array $settings, MediaInterface $media): array {
    $az_background_media = [];
    $css_settings = $settings['css_settings'];
    $fid = $media->getSource()->getSourceFieldValue($media);
    $file = $this->entityTypeManager->getStorage('file')->load($fid);
    if (empty($file)) {
      return $az_background_media;
    }
    $file_uri = $file->getFileUri();

    if ($settings['style'] !== 'bottom') {
      $responsive_image_style_element = [
        '#theme' => 'az_responsive_background_image',
        '#selector' => $css_settings['selector'],
        '#repeat' => $css_settings['repeat'],
        '#important' => $css_settings['important'],
        '#color' => $css_settings['color'],
        '#x' => $css_settings['x'],
        '#y' => $css_settings['y'],
        '#size' => $css_settings['size'],
        '#attachment' => $css_settings['attachment'],
        '#responsive_image_style_id' => $settings['image_style'],
        '#uri' => $file_uri,
        '#z_index' => $css_settings['z_index'],
      ];
      if ($settings['text_media_spacing'] === 'az-aspect-ratio' && isset($settings['full_width']) && $settings['full_width'] === 'full-width-background') {
        $image_renderable = [
          '#theme' => 'responsive_image_formatter',
          '#responsive_image_style_id' => 'az_full_width_background',
          '#item' => $media->get('field_media_az_image'),
          '#item_attributes' => [
            'class' => ['img-fluid', ' w-100', 'invisible'],
          ],
        ];
        $az_background_media[] = $image_renderable;
      }

      $az_background_media[] = $responsive_image_style_element;
    }
    elseif ($settings['style'] === 'bottom') {
      $image_renderable = [
        '#theme' => 'responsive_image_formatter',
        '#responsive_image_style_id' => 'az_full_width_background',
        '#item' => $media->get('field_media_az_image'),
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
