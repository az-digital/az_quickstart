<?php

namespace Drupal\az_ranking\Plugin\Field\FieldFormatter;

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
 * Plugin implementation of the 'az_ranking_default' formatter.
 */
#[FieldFormatter(
  id: 'az_ranking_default',
  label: new TranslatableMarkup('Default'),
  field_types: [
    'az_ranking',
  ],
)]
class AZRankingDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The AZRankingImageHelper service.
   *
   * @var \Drupal\az_ranking\AZRankingImageHelper
   */
  protected $rankingImageHelper;

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

    $instance->rankingImageHelper = $container->get('az_ranking.image');
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
      '#description' => $this->t('If set, ranking links are clickable. Uncheck this setting to disable all ranking links. A common use-case is on the "Preview" view mode to prevent users from losing edit data if accidentally clicking on rankings from the edit form.'),
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

    /** @var \Drupal\az_ranking\Plugin\Field\FieldType\AZRankingItem $item */
    foreach ($items as $delta => $item) {

      // Format title.
      $ranking_heading = $item->ranking_heading ?? '';
      $ranking_description = $item->ranking_description ?? '';

      $attached = [];
      $attached['library'][] = 'az_ranking/az_ranking';

      // Media.
      $column_span = $item->options['column_span'] ?? '';
      $media_render_array = [];
      if (!empty($item->media)) {
        if ($media = $this->entityTypeManager->getStorage('media')->load($item->media)) {
          $media_render_array = $this->rankingImageHelper->generateImageRenderArray($media);
          $attached['library'][] = 'az_ranking/az_ranking_image';
        }
      }

      // Link.
      $link_render_array = [];
      $link_url = '';
      if ($item->link_uri) {
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
          '#title' => $item->ranking_source ?? '',
          '#url' => $link_url ?: Url::fromRoute('<none>'),
          '#attributes' => ['class' => ['btn', 'btn-default', 'w-100']],
        ];
        if (!empty($item->options['link_style'])) {
          $link_render_array['#attributes']['class'] = explode(' ', $item->options['link_style']);
        }
        if (empty($settings['interactive_links'])) {
          $link_render_array['#attributes']['class'][] = 'az-ranking-no-follow';
          $attached['library'][] = 'az_ranking/az_ranking_no_follow';
        }
        $attached['library'][] = 'az_ranking/az_ranking_title_hover';
      }

      // Define Ranking Variabbles.
      $ranking_classes = 'ranking';
      $ranking_hover_style = '';
      $ranking_source_classes = '';
      $ranking_font_color = '';
      $ranking_defaults = [];
      $column_classes = [];
      $column_classes[] = 'col-md-4 col-lg-4';
      $parent = $item->getEntity();

      // Get settings from parent paragraph.
      if ($parent instanceof ParagraphInterface) {
        // Get the behavior settings for the parent.
        $parent_config = $parent->getAllBehaviorSettings();
        // See if the parent behavior defines some ranking-specific settings.
        if (!empty($parent_config['az_rankings_paragraph_behavior'])) {
          $ranking_defaults = $parent_config['az_rankings_paragraph_behavior'];

          // Set ranking classes according to behavior settings.
          $column_classes = [];
          if (!empty($ranking_defaults['az_display_settings'])) {
            $column_classes[] = $ranking_defaults['az_display_settings']['ranking_width_xs'] ?? 'col-6';
            $column_classes[] = $ranking_defaults['az_display_settings']['ranking_width_sm'] ?? 'col-md-4';
          }
          $column_classes[] = $ranking_defaults['ranking_width'] ?? 'col-md-4 col-lg-3';
          $ranking_classes = $ranking_defaults['ranking_hover_style'] ?? 'ranking';

          $ranking_classes .= ' ' . $ranking_defaults['ranking_alignment'] ?? 'text-left';
          // Calculate column classes for image based on column_span.
          if ($item->options['ranking_type'] === 'image_only' &&
            !empty($item->options['column_span']) &&
            ($item->options['column_span'] != '')) {

            // Multiply column classes by column_span value.
            $column_span_multiplier = (int) $item->options['column_span'];
            if ($column_span_multiplier > 1) {
              foreach ($column_classes as $key => $class_string) {
                // Handle single classes AND space-separated multiple classes.
                $classes = explode(' ', $class_string);
                $multiplied_classes = [];

                foreach ($classes as $class) {
                  if (preg_match('/^col(-\w+)?-(\d+)$/', $class, $matches)) {
                    $prefix = $matches[1] ?? '';
                    $current_width = (int) $matches[2];
                    $new_width = min(12, $current_width * $column_span_multiplier);
                    $multiplied_classes[] = 'col' . $prefix . '-' . $new_width;
                  }
                  else {
                    // Keep non-column classes as-is.
                    $multiplied_classes[] = $class;
                  }
                }
                $column_classes[$key] = implode(' ', $multiplied_classes);
              }
            }
          }
          else {
            $column_classes[] = $ranking_defaults['ranking_width'] ?? 'col-md-4 col-lg-4';
          }

          // Format pg style.
          $ranking_hover_style = $ranking_defaults['ranking_hover_style'] ?? '';
          if ($ranking_hover_style == 'card ranking-bold-hover' || $ranking_hover_style == 'card ranking-subtle-hover') {
            $attached['library'][] = 'az_ranking/az_ranking_bold_hover';
          }

          // Is the ranking clickable?
          if (isset($ranking_defaults['ranking_clickable']) && $ranking_defaults['ranking_clickable']) {
            if (!empty($link_render_array)) {
              $link_render_array['#attributes']['class'][] = 'stretched-link';
            }
            if ($item->link_uri) {
              $ranking_classes .= ' ranking-with-link';
            }
          }
        }
      }

      // Add .az-ranking-responsive to the column for image-only ranking cards.
      if ($item->options['ranking_type'] === 'image_only') {
        $column_classes[] = 'az-ranking-responsive';
      }

      // Shadow class should NOT be applied on transparent.
      $ranking_classes .= ' overflow-hidden';
      if (!str_contains($item->options['class'], 'bg-transparent')) {
        $ranking_classes .= ' shadow';
        // No mt-auto on bg-transparent rankings.
        $ranking_source_classes = 'mt-auto';
        $ranking_font_color = ' HELLO ';
      }
      else {
        $ranking_font_color = ' ' . $item->ranking_font_color;
        $ranking_classes .= ' ' . $item->ranking_font_color;
      }

      // Handle class keys that contained multiple classes.
      $column_classes = implode(' ', $column_classes);
      $column_classes = explode(' ', $column_classes);
      $column_classes[] = 'pb-4';
      if (!empty($item->options['class'])) {
        $ranking_classes .= ' ' . $item->options['class'];
      }

      // Set custom text classes based on background color.
      $text_color_override = '';
      if (!empty($item->options['class'])) {
        switch (TRUE) {
          case str_contains($item->options['class'], 'bg-sky'):
            $text_color_override = 'text-midnight';
            break;

          case str_contains($item->options['class'], 'bg-cool-gray'):
            $text_color_override = 'text-azurite';
            break;

          case str_contains($item->options['class'], 'bg-oasis'):
            $text_color_override = 'text-white';
            break;

        }
      }

      $element[$delta] = [
        '#theme' => 'az_ranking',
        '#media' => $media_render_array,
        '#column_span' => $column_span,
        '#ranking_heading' => $ranking_heading,
        '#ranking_hover_style' => $ranking_hover_style,
        '#ranking_title_style' => $ranking_defaults['ranking_title_style'],
        // The ProcessedText element handles cache context & tag bubbling.
        // @see \Drupal\filter\Element\ProcessedText::preRenderText()
        '#ranking_description' => $ranking_description,
        '#ranking_source' => $item->ranking_source,
        '#link_url' => $link_url,
        '#ranking_source_classes' => $ranking_source_classes,
        '#ranking_font_color' => $ranking_font_color,
        '#text_color_override' => $text_color_override,
        '#attributes' => ['class' => $ranking_classes],
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
