<?php

namespace Drupal\az_stat\Plugin\Field\FieldFormatter;

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
 * Plugin implementation of the 'az_stat_default' formatter.
 */
#[FieldFormatter(
  id: 'az_stat_default',
  label: new TranslatableMarkup('Default'),
  field_types: [
    'az_stat',
  ],
)]
class AZStatDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The AZStatImageHelper service.
   *
   * @var \Drupal\az_stat\AZStatImageHelper
   */
  protected $statImageHelper;

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

    $instance->statImageHelper = $container->get('az_stat.image');
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
      '#description' => $this->t('If set, stat links are clickable. Uncheck this setting to disable all stat links. A common use-case is on the "Preview" view mode to prevent users from losing edit data if accidentally clicking on stats from the edit form.'),
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

    /** @var \Drupal\az_stat\Plugin\Field\FieldType\AZStatItem $item */
    foreach ($items as $delta => $item) {

      // Format title.
      $stat_heading = $item->stat_heading ?? '';
      $stat_description = $item->stat_description ?? '';

      // Media.
      $media_render_array = [];
      if (!empty($item->media)) {
        if ($media = $this->entityTypeManager->getStorage('media')->load($item->media)) {
          $media_render_array = $this->statImageHelper->generateImageRenderArray($media);
        }
      }

      $attached = [];

      // Link.
      $link_render_array = [];
      $link_url = '';
      if ($item->stat_source || $item->link_uri) {
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
          '#title' => $item->stat_source ?? '',
          '#url' => $link_url ?: Url::fromRoute('<none>'),
          '#attributes' => ['class' => ['btn', 'btn-default', 'w-100']],
        ];
        if (!empty($item->options['link_style'])) {
          $link_render_array['#attributes']['class'] = explode(' ', $item->options['link_style']);
        }
        if (empty($settings['interactive_links'])) {
          $link_render_array['#attributes']['class'][] = 'az-stat-no-follow';
          $attached['library'][] = 'az_stat/az_stat_no_follow';
        }
        $attached['library'][] = 'az_stat/az_stat_title_hover';
      }

      // Title alignment.
      if (!empty($item->options['title_alignment'])) {
        $title_alignment = $item->options['title_alignment'];
      }

      // Title display.
      if (!empty($item->options['title_display'])) {
        $title_display = $item->options['title_display'];
      }

      $stat_classes = 'stat';
      $column_classes = [];
      $column_classes[] = 'col-md-4 col-lg-4';
      $parent = $item->getEntity();

      // Get settings from parent paragraph.
      if ($parent instanceof ParagraphInterface) {
        // Get the behavior settings for the parent.
        $parent_config = $parent->getAllBehaviorSettings();
        // See if the parent behavior defines some stat-specific settings.
        if (!empty($parent_config['az_stats_paragraph_behavior'])) {
          $stat_defaults = $parent_config['az_stats_paragraph_behavior'];

          // Set stat classes according to behavior settings.
          $column_classes = [];
          if (!empty($stat_defaults['az_display_settings'])) {
            $column_classes[] = $stat_defaults['az_display_settings']['stat_width_xs'] ?? 'col-12';
            $column_classes[] = $stat_defaults['az_display_settings']['stat_width_sm'] ?? 'col-sm-12';
          }
          $column_classes[] = $stat_defaults['stat_width'] ?? 'col-md-4 col-lg-4';
          $stat_classes = $stat_defaults['stat_style'] ?? 'stat';

          // Is the stat clickable?
          if (isset($stat_defaults['stat_clickable']) && $stat_defaults['stat_clickable']) {
            if (!empty($link_render_array)) {
              $link_render_array['#attributes']['class'][] = 'stretched-link';
            }
            $stat_classes .= ' shadow overflow-hidden';
            if ($item->link_uri) {
              $stat_classes .= ' stat-with-link';
            }
          }

          // Title style.
          if (isset($stat_defaults['stat_title_style'])) {
            $title_style = $stat_defaults['stat_title_style'];
            if (!empty($media_render_array)) {
              if ($item->stat_heading && $title_style === 'title-on-image') {
                array_push($media_render_array['#item_attributes']['class'], 'img-fluid', 'image-style-az-stat-image');
              }
            }
            // Force default title style if media field is not populated.
            else {
              $title_style = 'default';
            }
          }

          // Title level.
          if (isset($stat_defaults['stat_title_level'])) {
            $title_level = $stat_defaults['stat_title_level'];
          }

          // Title display.
          if (isset($stat_defaults['stat_title_display'])) {
            $title_display = $stat_defaults['stat_title_display'];
          }
        }
      }

      // Handle class keys that contained multiple classes.
      $column_classes = implode(' ', $column_classes);
      $column_classes = explode(' ', $column_classes);
      $column_classes[] = 'pb-4';
      if (!empty($item->options['class'])) {
        $stat_classes .= ' ' . $item->options['class'];
      }

      $element[$delta] = [
        '#theme' => 'az_stat',
        '#media' => $media_render_array,
        '#stat_heading' => $stat_heading,
        // The ProcessedText element handles cache context & tag bubbling.
        // @see \Drupal\filter\Element\ProcessedText::preRenderText()
        '#stat_description' => $stat_description,
        '#stat_source' => $link_render_array,
        '#link_url' => $link_url,
        '#title_style' => $title_style ?? 'default',
        '#title_level' => $title_level ?? 'h3',
        '#title_alignment' => $title_alignment ?? 'text-start',
        '#title_display' => $title_display ?? 'h5',
        '#attributes' => ['class' => $stat_classes],
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
