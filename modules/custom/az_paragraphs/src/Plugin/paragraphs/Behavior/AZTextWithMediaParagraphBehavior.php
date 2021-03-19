<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\file\FileInterface;

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
    // Get plugin configuration and save in vars for twig to use.
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

  private function remoteVideo(array &$variables, ParagraphInterface $paragraph, MediaInterface $media) {
    /** @var \Drupal\media\Plugin\media\Source\OEmbed $media_oembed */
    $media_oembed = $media->getSource();
    $provider = $media_oembed->getMetadata($media, 'provider_name');
    $html = $media_oembed->getMetadata($media, 'html');
    $thumb = $media_oembed->getMetadata($media, 'thumbnail_uri');
    if ($provider == 'YouTube') {
      $source_url = $media->get('field_media_az_oembed_video')->value;
      $video_oembed_id = $this->getYouTubeid($source_url);
      $js['#attached']['drupalSettings']['azFieldsMedia']['bgVideos'][$video_oembed_id] = [
        'videoId' => $video_oembed_id,
        'start' => 0,
      ];
      $text_on_bottom = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#markup' => $html,
        '#allowed_tags' => ['iframe'],
        '#attributes' => [
          'id' => [$video_oembed_id . '-bg-video-container'],
        ],
      ];
      $variables['style_element'] = $text_on_bottom;
      \Drupal::service('renderer')->render($js);
      return $variables;
    }
  }

  private function image(array &$variables, ParagraphInterface $paragraph, MediaInterface $media) {
    $file_uri = $media->field_media_az_image->entity->getFileUri();
    if ($variables['text_on_media']['style'] !== 'bottom') {
      $style_element = array('style' => [
        '#type' => 'inline_template',
        '#template' => "<style type='text/css'>#{{ id }} {background-image: url({{filepath}}); }</style>",
        '#context' => [
          'filepath' => file_create_url($file_uri),
          'id' => $paragraph->bundle() . "-" . $paragraph->id(),
        ]
      ]);
      $variables['style_element'] = $style_element;
    }
    else if ($variables['text_on_media']['style'] === 'bottom') {
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
        '#value' =>  render($image_renderable),
        '#attributes' => [
          'class' => ['text-on-media-bottom'],
        ],
      ];
      $variables['inner_media'] = $text_on_bottom;
    }
    return $variables;
  }

  /**
   * This function returns a video id from a url
   */
  private function getYouTubeid($url) {
    $shortUrlRegex = '/youtu.be\/([a-zA-Z0-9_-]+)\??/i';
    $longUrlRegex = '/youtube.com\/((?:embed)|(?:watch))((?:\?v\=)|(?:\/))([a-zA-Z0-9_-]+)/i';

    if (preg_match($longUrlRegex, $url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }

    if (preg_match($shortUrlRegex, $url, $matches)) {
        $youtube_id = $matches[count($matches) - 1];
    }
    return $youtube_id ;
  }

}
