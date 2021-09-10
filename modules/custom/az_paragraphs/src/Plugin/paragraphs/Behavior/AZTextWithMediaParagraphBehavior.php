<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a behavior for text with media.
 *
 * @ParagraphsBehavior(
 *   id = "az_text_media_paragraph_behavior",
 *   label = @Translation("Quickstart Text with media Paragraph Behavior"),
 *   description = @Translation("Provides class selection for text with media."),
 *   weight = 0
 * )
 */
class AZTextWithMediaParagraphBehavior extends AZDefaultParagraphsBehavior {

  /**
   * The VideoEmbedHelper.
   *
   * @var \Drupal\az_paragraphs\AZVideoEmbedHelper
   */
  protected $videoEmbedHelper;

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

    $instance->videoEmbedHelper = ($container->get('az_paragraphs.az_video_embed_helper'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getSettings($paragraph);

    $style_unique_id = Html::getUniqueId('az-text-media-style');
    $form['full_width'] = [
      '#title' => $this->t('Full width'),
      '#type' => 'checkbox',
      '#default_value' => $config['full_width'] ?? '',
      '#description' => $this->t('Makes media full width if checked.'),
      '#return_value' => 'full-width-background',
    ];

    $form['style'] = [
      '#title' => $this->t('Content style'),
      '#type' => 'select',
      '#options' => [
        'column' => $this->t('Column style'),
        'box' => $this->t('Box style'),
        'bottom' => $this->t('Bottom style'),
      ],
      '#default_value' => $config['style'] ?? '',
      '#description' => $this->t('The style of the content background.'),
      '#attributes' => [
        'id' => $style_unique_id,
      ],
    ];

    $form['bg_color'] = [
      '#title' => $this->t('Content background color'),
      '#type' => 'select',
      '#options' => [
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
        'transparent' => $this->t('Transparent'),
      ],
      '#default_value' => $config['bg_color'] ?? '',
      '#description' => $this->t('The color of the content background.'),
    ];

    $form['position'] = [
      '#title' => $this->t('Content position'),
      '#type' => 'select',
      '#options' => [
        'col-md-8 col-lg-6' => $this->t('Position left'),
        'col-md-8 col-lg-6 col-md-offset-2 col-lg-offset-3' => $this->t('Position center'),
        'col-md-8 col-lg-6 col-md-offset-4 col-lg-offset-6' => $this->t('Position right'),
        'col-xs-12' => $this->t('None'),
      ],
      '#default_value' => $config['position'] ?? '',
      '#description' => $this->t('The position of the content on the media.'),
      '#states' => [
        'invisible' => [
          ':input[id="' . $style_unique_id . '"]' => ['value' => 'bottom'],
        ],
      ],
    ];

    $form['bg_attachment'] = [
      '#title' => $this->t('Media attachment'),
      '#type' => 'select',
      '#options' => [
        'bg-fixed' => $this->t('Fixed'),
      ],
      '#empty_option' => $this->t('Scroll'),
      '#default_value' => $config['bg_attachment'] ?? '',
      '#description' => $this->t('<strong>Scroll:</strong> The media will scroll along with the page.<br> <strong>Fixed:</strong> The media will be fixed and the page will scroll over it.'),
      '#states' => [
        'invisible' => [
          ':input[id="' . $style_unique_id . '"]' => ['value' => 'bottom'],
        ],
      ],
    ];

    $form['text_media_spacing'] = [
      '#title' => $this->t('Space Around Content'),
      '#type' => 'select',
      '#options' => [
        'y-0' => $this->t('Zero'),
        'y-1' => $this->t('1 (0.25rem | ~4px)'),
        'y-2' => $this->t('2 (0.5rem | ~8px)'),
        'y-3' => $this->t('3 (1.0rem | ~16px)'),
        'y-4' => $this->t('4 (1.5rem | ~24px)'),
        'y-5' => $this->t('5 (3.0rem | ~48px) - Default'),
        'y-6' => $this->t('6 (4.0rem | ~64px)'),
        'y-7' => $this->t('7 (5.0rem | ~80px)'),
        'y-8' => $this->t('8 (6.0rem | ~96px)'),
        'y-9' => $this->t('9 (7.0rem | ~112px)'),
        'y-10' => $this->t('10 (8.0rem | ~128px)'),
      ],
      '#default_value' => $config['text_media_spacing'] ?? 'y-5',
      '#description' => $this->t('Adds spacing above and below the text.'),
    ];

    parent::buildBehaviorForm($paragraph, $form, $form_state);

    // This places the form fields on the content tab rather than behavior tab.
    // Note that form is passed by reference.
    // @see https://www.drupal.org/project/paragraphs/issues/2928759
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(&$variables) {
    parent::preprocess($variables);
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = $variables['paragraph'];
    // Get plugin configuration.
    $config = $this->getSettings($paragraph);
    $variables['text_on_media'] = $config;
    if ($paragraph->hasField('field_az_media')) {
      /** @var \Drupal\media\Entity\Media $media */
      foreach ($paragraph->get('field_az_media')->referencedEntities() as $media) {
        $variables['text_on_media']['media_type'] = $media->bundle();
        switch ($media->bundle()) {
          case 'az_remote_video':
            $this->remoteVideo($variables, $paragraph, $media);
            break;

          case 'az_image':
            $this->image($variables, $paragraph, $media);
            break;

          default:
            return $variables;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {

    // Get plugin configuration.
    $config = $this->getSettings($paragraph);
    // Apply bottom spacing if set.
    if (!empty($config['az_display_settings']['bottom_spacing'])) {
      $build['#attributes']['class'] = $config['az_display_settings']['bottom_spacing'];
    }

  }

  /**
   * Prepare markup for remote video.
   */
  private function remoteVideo(array &$variables, ParagraphInterface $paragraph, MediaInterface $media) {

    /** @var \Drupal\media\Plugin\media\Source\OEmbed $media_oembed */
    $media_oembed = $media->getSource();
    $config = $this->getSettings($paragraph);
    $view_builder = $this->entityTypeManager->getViewBuilder('media');
    $background_media = $view_builder->view($media, 'az_background');
    $provider = $media_oembed->getMetadata($media, 'provider_name');
    $html = $media_oembed->getMetadata($media, 'html');
    $thumb = $media_oembed->getMetadata($media, 'thumbnail_uri');
    if ($provider === 'YouTube') {
      $source_url = $media->get('field_media_az_oembed_video')->value;
      $video_oembed_id = $this->videoEmbedHelper->getYoutubeIdFromUrl($source_url);
      $style_element = [
        'style' => [
          '#type' => 'inline_template',
          '#template' => "<style type='text/css'>#{{ id }} {background-image: url({{filepath}});} #{{ id }}.az-video-playing, #{{ id }}.az-video-paused {background-image:none;}</style>",
          '#context' => [
            'filepath' => file_create_url($thumb),
            'id' => $paragraph->bundle() . "-" . $paragraph->id(),
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
            'data-style' => $config['style'],
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
      if ($variables['text_on_media']['style'] !== 'bottom') {
        $variables['style_element'] = $style_element;
      }
      elseif ($variables['text_on_media']['style'] === 'bottom') {
        $style_element['style']['#template'] = "<style type='text/css'>#{{ id }} .az-video-loading {background-image: url({{filepath}});background-repeat: no-repeat;background-attachment:fixed;background-size:cover;}</style>";
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
          'video' => $style_element,
          '#attributes' => [
            'class' => ['text-on-media-bottom', 'text-on-video'],
          ],
        ];
        $variables['text_on_bottom'] = $text_on_bottom;
      }
      return $variables;
    }
  }

  /**
   * Prepare markup for image.
   */
  private function image(array &$variables, ParagraphInterface $paragraph, MediaInterface $media) {
    $file_uri = $media->field_media_az_image->entity->getFileUri();
    if ($variables['text_on_media']['style'] !== 'bottom') {
      $style_element = [
        'style' => [
          '#type' => 'inline_template',
          '#template' => "<style type='text/css'>#{{ id }} {background-image: url({{filepath}}); }</style>",
          '#context' => [
            'filepath' => file_create_url($file_uri),
            'id' => $paragraph->bundle() . "-" . $paragraph->id(),
          ],
        ],
      ];
      $variables['style_element'] = $style_element;
    }
    elseif ($variables['text_on_media']['style'] === 'bottom') {
      $image_renderable = [
        '#theme' => 'image',
        '#uri' => file_create_url($file_uri),
        '#alt' => $media->field_media_az_image->alt,
        '#attributes' => [
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
      $variables['text_on_bottom'] = $text_on_bottom;
    }
    return $variables;
  }

}
