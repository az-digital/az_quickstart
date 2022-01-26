<?php

namespace Drupal\az_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;
use Drupal\paragraphs\ParagraphInterface;

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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'full_width' => '',
      'style' => '',
      'bg_color' => '',
      'position' => '',
      'text_media_spacing' => 'y-5',
      'bg_attachment' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $default_settings = $this->defaultSettings();
    $config = $this->getSettings($paragraph);
    $config += $default_settings;
    $style_unique_id = Html::getUniqueId('az-text-media-style');

    $form['full_width'] = [
      '#title' => $this->t('Full width'),
      '#type' => 'checkbox',
      '#default_value' => $config['full_width'],
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
      '#default_value' => $config['style'],
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
      '#default_value' => $config['bg_color'],
      '#description' => $this->t('The color of the content background.'),
    ];
    $form['position'] = [
      '#title' => $this->t('Content position'),
      '#type' => 'select',
      '#options' => [
        'col-md-8 col-lg-6' => $this->t('Position left'),
        'col-md-8 col-lg-6 col-md-offset-2 col-lg-offset-3' => $this->t('Position center'),
        'col-md-8 col-lg-6 col-md-offset-4 col-lg-offset-6' => $this->t('Position right'),
        'col-xs-12' => $this->t('Full-width'),
      ],
      '#default_value' => $config['position'],
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
      '#default_value' => $config['bg_attachment'],
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
        'y-20' => $this->t('20 (16.0rem | ~256px)'),
        'y-30' => $this->t('30 (24.0rem | ~384px)'),
        'az-aspect-ratio' => $this->t('Media Aspect Ratio'),
      ],
      '#default_value' => $config['text_media_spacing'],
      '#description' => $this->t('Adds spacing above and below the text.'),
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
    // Get plugin configuration.
    $default_settings = $this->defaultSettings();
    $config = $this->getSettings($paragraph);
    $config += $default_settings;
    $variables['text_on_media'] = $config;
    $az_background_media = [];

    $style = '';
    if (!empty($config['style']) && $config['style'] !== 'bottom') {
      $style = $config['style'];
    }
    $paragraph_status = $paragraph->status->value ? 'published' : 'unpublished';
    $variables['attributes']['id'] = HTML::getId($paragraph->bundle() . '-' . $paragraph->id());
    if (!empty($variables['attributes']) && !empty($variables['attributes']['class']) && !is_array($variables['attributes']['class'])) {
      $variables['attributes']['class'] = [$variables['attributes']['class']];
    }
    $variables['attributes']['class'][] = 'paragraph';
    $variables['attributes']['class'][] = 'position-relative';
    $variables['attributes']['class'][] = HTML::getClass('paragraph--type--' . $paragraph->bundle());
    if (!empty($variables['content']['field_az_media']['#media_type'])) {
      $variables['attributes']['class'][] = 'media--type--' . HTML::getClass($variables['content']['field_az_media']['#media_type']);
    }
    $variables['attributes']['class'][] = HTML::getClass('paragraph--view-mode--' . $variables['view_mode']);
    $variables['attributes']['class'][] = HTML::getClass('paragraph--' . $paragraph_status);
    $variables['attributes']['class'][] = HTML::getClass($style);
    $variables['attributes']['class'][] = HTML::getClass($config['full_width']);
    $variables['attributes']['class'][] = HTML::getClass($config['bg_attachment']);
    // Get column classes.
    $column_classes = ['col'];
    if (!empty($config['style']) && $config['style'] === 'bottom') {
      $column_classes[] = 'col-md-10 col-md-offset-1';
    }
    else {
      $column_classes[] = $config['position'];
    }
    // Set column classes.
    $variables['elements']['#fieldgroups']['group_az_column']->format_settings['classes'] = implode(' ', $column_classes);
    // Get content classes.
    $content_classes = [
      'content',
      'az-full-width-column-content',
      HTML::getClass($config['bg_color']),
      HTML::getClass($config['style']),
    ];

    // Add responsive spacing classes.
    if (!empty($config['style']) && $config['style'] !== 'bottom') {
      $spacing_prefix = '';
      if ($config['style'] === 'column') {
        $spacing_prefix = 'p';
      }
      elseif ($config['style'] === 'box') {
        $spacing_prefix = 'm';
      }
      switch ($config['text_media_spacing']) {
        case 'y-20':
          $content_classes[] = HTML::getClass($spacing_prefix . 'y-10');
          $content_classes[] = HTML::getClass($spacing_prefix . 'y-md-20');
          break;

        case 'y-30':
          $content_classes[] = HTML::getClass($spacing_prefix . 'y-10');
          $content_classes[] = HTML::getClass($spacing_prefix . 'y-md-30');
          break;

        case 'az-aspect-ratio':
          $variables['attributes']['class'][] = 'az-aspect-ratio';
          break;

        default:
          $content_classes[] = HTML::getClass($spacing_prefix . $config['text_media_spacing']);
      }
    }

    // Set content classes.
    $variables['elements']['#fieldgroups']['group_az_content']->format_settings['classes'] = implode(' ', $content_classes);
    // Get title classes.
    $title_classes = [
      'mt-0',
      'bold',
    ];
    if (!empty($config['bg_color']) && $config['bg_color'] !== 'dark') {
      $title_classes[] = 'text-blue';
    }
    // Set title classes.
    $variables['elements']['#fieldgroups']['group_az_title']->format_settings['classes'] = implode(' ', $title_classes);
  }

}
