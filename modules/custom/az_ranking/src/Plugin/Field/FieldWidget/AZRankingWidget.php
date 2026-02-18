<?php

namespace Drupal\az_ranking\Plugin\Field\FieldWidget;

use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Defines the 'az_ranking' field widget.
 */
#[FieldWidget(
  id: 'az_ranking',
  label: new TranslatableMarkup('Ranking'),
  field_types: ['az_ranking'],
)]
class AZRankingWidget extends WidgetBase {

  // Default initial text format for rankings.
  const AZ_RANKING_DEFAULT_TEXT_FORMAT = 'az_standard';

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
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
    $instance->pathValidator = $container->get('path.validator');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {

    // Create shared settings for widget elements.
    // This is necessary because widgets have to be AJAX replaced together,
    // And in general we need a place to store shared settings.
    $wrapper_id = Html::getUniqueId('az-ranking-wrapper');
    $field_name = $this->fieldDefinition->getName();
    $field_parents = $form['#parents'];
    $field_state = static::getWidgetState($field_parents, $field_name, $form_state);
    $field_state['ajax_wrapper_id'] = $wrapper_id;

    // Remove extra field added on form instantiation for existing content.
    $count = count($items);
    $field_state['items_count'] = (!empty($field_state['items_count'])) ? $field_state['items_count'] : max(0, $count - 1);

    $field_state['array_parents'] = [];
    if (empty($field_state['open_status'])) {
      $field_state['open_status'] = [];
    }

    // Persist the widget state so formElement() can access it.
    static::setWidgetState($field_parents, $field_name, $form_state, $field_state);

    $container = parent::form($items, $form, $form_state, $get_delta);
    $container['widget']['#prefix'] = ($container['widget']['#prefix'] ?? '') . '<div id="' . $wrapper_id . '">';
    $container['widget']['#suffix'] = '</div>' . ($container['widget']['#suffix'] ?? '');

    if (isset($container['widget']['add_more']['#ajax']['wrapper'])) {
      $container['widget']['add_more']['#ajax']['wrapper'] = $wrapper_id;
    }
    return $container;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\az_ranking\Plugin\Field\FieldType\AZRankingItem $item */
    $item = $items[$delta];

    // Get current collapse status.
    $field_name = $this->fieldDefinition->getName();
    $field_parents = $element['#field_parents'];
    $widget_state = static::getWidgetState($field_parents, $field_name, $form_state);
    $status = (isset($widget_state['open_status'][$delta])) ? $widget_state['open_status'][$delta] : FALSE;

    // New field values shouldn't be collapsed.
    if ($item->isEmpty()) {
      $status = TRUE;
    }

    // Gather parent paragraph config for preview building in #after_build.
    $parent = $item->getEntity();
    $ranking_parent_config = [];
    if ($parent instanceof ParagraphInterface) {
      $parent_config = $parent->getAllBehaviorSettings();
      if (!empty($parent_config['az_rankings_paragraph_behavior'])) {
        $ranking_parent_config = $parent_config['az_rankings_paragraph_behavior'];
      }
    }

    // Wrap everything in a details element.
    $element['details'] = [
      '#type' => 'details',
      '#title' => $this->t('Edit Ranking'),
      // Open when in edit mode, closed when in preview mode.
      '#open' => $status,
      '#attributes' => ['class' => ['az-ranking-widget']],
    ];

    // When closed, add a preview wrapper.
    if (!$status) {
      $element['preview_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['widget-preview-wrapper'],
          'style' => 'max-width: 320px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; height: 260px;',
        ],
        // Show before the details element.
        '#weight' => -10,
      ];

      // Empty preview placeholder — afterBuildRebuildPreview will populate.
      $element['preview_wrapper']['preview'] = [
        '#theme' => 'az_ranking',
      ];
    }

    // Store parent config on the element for the #after_build callback.
    $element['#ranking_parent_config'] = $ranking_parent_config;

    // Create a globally unique ID that includes
    // parent entity info and field parents.
    $parent_entity = $item->getEntity();
    $parent_id = $parent_entity ? $parent_entity->id() : 'new';
    $field_parents_string = implode('-', $field_parents);

    // Set ids for fields that are dependent on Type and Background Color.
    $ranking_type_unique_id = 'ranking-type-' . $parent_id . '-' . $field_parents_string . '-' . $delta;
    $ranking_background_unique_id = 'ranking-bg-' . $parent_id . '-' . $field_parents_string . '-' . $delta;

    // Generate unique IDs that match the paragraph behavior.
    $ranking_clickable_unique_id = '';
    $ranking_hover_effect_unique_id = '';
    if ($parent instanceof ParagraphInterface) {
      // Build a deterministic ID based on the paragraph's position in the form.
      // Filter out 'subform' to match what's in $form['#parents'].
      $filtered_parents = array_filter($field_parents, function ($key) {
        return $key !== 'subform';
      });
      $behavior_form_parents = array_merge($filtered_parents, ['behavior_plugins', 'az_rankings_paragraph_behavior']);
      $id_suffix = implode('-', $behavior_form_parents);

      $ranking_clickable_unique_id = 'ranking-clickable--' . $id_suffix;
      $ranking_hover_effect_unique_id = 'ranking-hover-effect--' . $id_suffix;
    }

    // Add all form fields inside the details element.
    $element['details']['ranking_type'] = [
      '#type' => 'select',
      '#options' => [
        'standard' => $this->t('Standard'),
        'image_only' => $this->t('Image Only'),
      ],
      '#title' => $this->t('Ranking Type'),
      '#default_value' => (!empty($item->options['ranking_type'])) ? $item->options['ranking_type'] : 'standard',
      '#attributes' => ['data-az-ranking-type-input-id' => $ranking_type_unique_id],
    ];

    $element['details']['media'] = [
      '#type' => 'az_media_library',
      '#default_value' => $item->media ?? NULL,
      '#allowed_bundles' => ['az_image'],
      '#delta' => $delta,
      '#cardinality' => 1,
      '#after_build' => [[$this, 'addAzRankingContextToMediaEdit']],
      '#states' => [
        // Media is only visible when Ranking Type is "image_only".
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'image_only'],
        ],
      ],
    ];

    $element['details']['column_span'] = [
      '#type' => 'select',
      '#options' => [
        1 => $this->t('1 card'),
        2 => $this->t('2 cards (default)'),
        3 => $this->t('3 cards'),
        4 => $this->t('4 cards'),
      ],
      '#title' => $this->t('Image Width Span'),
      '#description' => $this->t('How many cards do you want this image to span (in multiples of ranking-card width)?'),
      '#default_value' => (!empty($item->options['column_span'])) ? $item->options['column_span'] : 2,
      '#states' => [
        // Column Span is only visible when Ranking Type is "image_only".
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'image_only'],
        ],
      ],
      '#attributes' => [
        'data-ranking-width-target' => 'true',
      ],
    ];

    // All other fields are only visible when Ranking Type is "standard".
    $element['details']['options'] = [
      '#type' => 'select',
      '#options' => [
        'text-bg-chili' => $this->t('Chili (default)'),
        'text-bg-blue' => $this->t('Arizona Blue'),
        'bg-sky' => $this->t('Sky'),
        'bg-oasis' => $this->t('Oasis'),
        'text-bg-azurite' => $this->t('Azurite'),
        'bg-cool-gray' => $this->t('Cool Gray'),
        'bg-warm-gray' => $this->t('Warm Gray'),
        'bg-white' => $this->t('White'),
        'bg-transparent' => $this->t('Transparent'),
      ],
      '#required' => TRUE,
      '#attributes' => ['data-az-ranking-bg-input-id' => $ranking_background_unique_id],
      '#title' => $this->t('Ranking Background'),
      '#default_value' => (!empty($item->options['class'])) ? $item->options['class'] : 'text-bg-chili',
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
          ':input[data-az-ranking-hover-effect-input-id="' . $ranking_hover_effect_unique_id . '"]' => [
            ['checked' => FALSE],
          ],
        ],
      ],
    ];

    $element['details']['options_hover_effect'] = [
      '#type' => 'select',
      '#options' => [
        'text-bg-chili' => $this->t('Chili (default)'),
        'text-bg-blue' => $this->t('Arizona Blue'),
        'bg-sky' => $this->t('Sky'),
        'bg-cool-gray' => $this->t('Cool Gray'),
        'bg-oasis' => $this->t('Oasis'),
      ],
      '#required' => TRUE,
      '#title' => $this->t('Ranking Background with Hover Effect'),
      '#default_value' => (!empty($item->options['hover_class'])) ? $item->options['hover_class'] : 'text-bg-chili',
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
          ':input[data-az-ranking-hover-effect-input-id="' . $ranking_hover_effect_unique_id . '"]' => [
            ['checked' => TRUE],
          ],
        ],
      ],
    ];

    $element['details']['ranking_font_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Ranking Font Color'),
      '#options' => [
        'ranking-text-midnight' => $this->t('Midnight (default)'),
        'ranking-text-black' => $this->t('Black'),
        'ranking-text-white' => $this->t('White'),
        'ranking-text-az-blue' => $this->t('Arizona Blue'),
      ],
      '#default_value' => $item->ranking_font_color ?? 'ranking-text-midnight',
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
          ':input[data-az-ranking-bg-input-id="' . $ranking_background_unique_id . '"]' => ['value' => 'bg-transparent'],
        ],
      ],
    ];

    $element['details']['ranking_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ranking Heading'),
      '#default_value' => $item->ranking_heading ?? NULL,
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
        ],
      ],
    ];

    $element['details']['ranking_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ranking Description'),
      '#default_value' => $item->ranking_description ?? NULL,
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
        ],
      ],
    ];

    $element['details']['ranking_source'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ranking Source'),
      '#description' => $this->t('Optionally, cite where the ranking came from. This will be displayed below the ranking.'),
      '#default_value' => $item->ranking_source ?? NULL,
      '#rows' => 3,
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
        ],
      ],
    ];

    $element['details']['link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ranking Link Title'),
      '#default_value' => $item->link_title ?? NULL,
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          // If ranking is clickable, hide the title.
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
          ':input[data-az-ranking-hover-effect-input-id="' . $ranking_hover_effect_unique_id . '"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $element['details']['link_uri'] = [
      '#type' => 'linkit',
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => 'az_linkit',
      ],
      '#title' => $this->t('Ranking Link URL'),
      '#element_validate' => [[$this, 'validateRankingLink'], [$this, 'validateRankingLinkRequired']],
      '#default_value' => $item->link_uri ?? NULL,
      '#maxlength' => 2048,
      // Don't use server-side required - let #states handle it dynamically.
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
        ],
        // Link URI is required when Ranking Type is 'standard',
        // AND the ranking is clickable.
        'required' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
          ':input[data-az-ranking-clickable-input-id="' . $ranking_clickable_unique_id . '"]' => [
            ['checked' => TRUE],
          ],
        ],
      ],
    ];

    $element['details']['ranking_link_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Ranking Link Style'),
      '#options' => [
        'visually-hidden' => $this->t('Hidden link title'),
        'link mt-2' => $this->t('Text link'),
        'w-100 btn btn-red mt-2' => $this->t('Red button (default)'),
        'w-100 btn btn-blue mt-2' => $this->t('Blue button'),
        'w-100 btn btn-outline-red mt-2' => $this->t('Red outline button'),
        'w-100 btn btn-outline-blue mt-2' => $this->t('Blue outline button'),
        'w-100 btn btn-outline-white mt-2' => $this->t('White outline button'),
      ],
      '#default_value' => $item->ranking_link_style ?? 'w-100 btn btn-red mt-2',
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
          ':input[data-az-ranking-hover-effect-input-id="' . $ranking_hover_effect_unique_id . '"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // Attach the library and return the element.
    $element['#attached']['library'][] = 'az_ranking/az_ranking';

    // Store delta and field name for reference.
    $element['#delta'] = $delta;
    $element['#field_name'] = $field_name;

    // Rebuild the preview in #after_build so it uses the form API-populated
    // field values (which reflect drag-and-drop reorder) instead of $items.
    $element['#after_build'][] = [static::class, 'afterBuildRebuildPreview'];

    return $element;
  }

  /**
   * After-build callback to rebuild preview from form field values.
   *
   * Rebuilds the preview from the same Form API-populated #value as the
   * form fields, ensuring the preview stays in sync after drag-and-drop
   * reorder.
   */
  public static function afterBuildRebuildPreview(array $element, FormStateInterface $form_state) {
    // Only rebuild if there is a preview to update.
    if (!isset($element['preview_wrapper']['preview'])) {
      return $element;
    }

    $parent_config = $element['#ranking_parent_config'] ?? [];
    $details = $element['details'] ?? [];

    // Read values from this element's form fields. At this point,
    // the Form API has set #value from user input (keyed by original delta).
    $heading = $details['ranking_heading']['#value']
      ?? $details['ranking_heading']['#default_value']
      ?? '';
    $description = $details['ranking_description']['#value']
      ?? $details['ranking_description']['#default_value']
      ?? '';
    $source = $details['ranking_source']['#value']
      ?? $details['ranking_source']['#default_value']
      ?? '';
    $link_title = $details['link_title']['#value']
      ?? $details['link_title']['#default_value']
      ?? '';
    $link_uri = $details['link_uri']['#value']
      ?? $details['link_uri']['#default_value']
      ?? '';
    $ranking_font_color = $details['ranking_font_color']['#value']
      ?? $details['ranking_font_color']['#default_value']
      ?? 'ranking-text-midnight';
    $ranking_link_style = $details['ranking_link_style']['#value']
      ?? $details['ranking_link_style']['#default_value']
      ?? 'w-100 btn btn-red mt-2';
    $background_class = $details['options']['#value']
      ?? $details['options']['#default_value']
      ?? 'text-bg-chili';
    $hover_class = $details['options_hover_effect']['#value']
      ?? $details['options_hover_effect']['#default_value']
      ?? 'text-bg-chili';
    $ranking_type = $details['ranking_type']['#value']
      ?? $details['ranking_type']['#default_value']
      ?? 'standard';
    // Media needs special handling: $items gets reordered by weight during
    // form processing, so #default_value (from $items[$delta]) is in the wrong
    // order. Read from user input instead (same order as text field #values).
    $media_id = NULL;
    $delta = $element['#delta'];
    $field_name = $element['#field_name'];
    $field_parents = $element['#field_parents'];
    $user_input = $form_state->getUserInput();
    $input_path = array_merge($field_parents, [$field_name, $delta, 'details', 'media']);
    $media_input = NestedArray::getValue($user_input, $input_path);
    if (is_array($media_input)) {
      // Media library widget stores selection in various formats.
      $media_id = $media_input['selection'][0]['target_id']
        ?? $media_input['media_library_selection']
        ?? NULL;
      if (empty($media_id) && !empty($media_input['target_id'])) {
        $media_id = $media_input['target_id'];
      }
    }
    elseif (is_numeric($media_input)) {
      $media_id = $media_input;
    }
    // Fallback for initial load (no user input yet).
    if ($media_id === NULL && !$form_state->isRebuilding()) {
      $media_id = $details['media']['#default_value'] ?? NULL;
    }

    // Parent paragraph behavior settings.
    $ranking_hover_effect = !empty($parent_config['ranking_hover_effect']);
    $ranking_clickable = !empty($parent_config['ranking_clickable']);
    $ranking_header_style = $parent_config['ranking_header_style'] ?? NULL;
    $ranking_alignment = $parent_config['ranking_alignment'] ?? 'text-left';

    // Build base ranking classes (same logic as was in formElement).
    $ranking_classes = $parent_config['ranking_hover_style'] ?? 'ranking card';
    $ranking_classes .= ' overflow-hidden';

    // Apply alignment for non-image-only types.
    if ($ranking_type !== 'image_only') {
      $ranking_classes .= ' ' . $ranking_alignment;
    }

    // Handle hover effect and background classes.
    $effective_bg = $background_class;
    if ($ranking_hover_effect) {
      $effective_bg = !empty($hover_class) ? $hover_class : $background_class;
      if (!empty($effective_bg) && $ranking_type !== 'image_only') {
        $ranking_classes .= ' from-hover-effect ' . $effective_bg;
      }
    }
    else {
      if (!empty($background_class) && $ranking_type !== 'image_only') {
        $ranking_classes .= ' non-hover-effect ' . $background_class;
      }
    }

    // Clickable ranking styles.
    if ($ranking_clickable) {
      $ranking_classes .= ' shadow';
      if (!empty($ranking_hover_effect) && $ranking_type !== 'image_only') {
        $ranking_classes .= ' ranking-bold-hover';
      }
    }
    else {
      $ranking_hover_effect = FALSE;
    }

    // Link color override.
    if (str_contains($ranking_link_style, 'link')) {
      if (str_contains($effective_bg, 'bg-oasis') ||
        str_contains($effective_bg, 'bg-sky')) {
        $ranking_link_style .= ' text-midnight';
      }
    }

    // Determine text color override based on background.
    $text_color_override = '';
    $check_bg = $ranking_hover_effect ? $effective_bg : $background_class;
    if (!empty($check_bg)) {
      $bg_text_map = [
        'bg-sky' => 'text-midnight',
        'bg-cool-gray' => 'text-azurite',
        'bg-warm-gray' => 'text-midnight',
        'bg-white' => 'text-midnight',
        'bg-oasis' => 'text-midnight',
      ];
      foreach ($bg_text_map as $bg_key => $text_class) {
        if (str_contains($check_bg, $bg_key)) {
          $text_color_override = $text_class;
          break;
        }
      }
    }

    // Determine source classes based on background.
    $ranking_source_classes = '';
    if (!str_contains($background_class, 'bg-transparent')) {
      $ranking_source_classes = 'mt-auto';
    }
    else {
      // Transparent: apply font color to ranking classes.
      $ranking_font_color = ' ' . $ranking_font_color;
      $ranking_classes .= ' ' . trim($ranking_font_color);
    }

    // Build media render array.
    $media_render_array = NULL;
    if (!empty($media_id)) {
      $media_entity = \Drupal::entityTypeManager()->getStorage('media')->load($media_id);
      if ($media_entity) {
        /** @var \Drupal\az_ranking\AZRankingImageHelper $image_helper */
        $image_helper = \Drupal::service('az_ranking.image');
        $media_render_array = $image_helper->generateImageRenderArray($media_entity);
      }
    }

    // Build link render array and URL.
    $link_render_array = NULL;
    $link_url = NULL;
    if (!empty($link_uri)) {
      if (str_starts_with($link_uri, '/' . PublicStream::basePath())) {
        $link_url = Url::fromUri(urldecode('base:' . $link_uri));
      }
      else {
        $link_url = \Drupal::service('path.validator')->getUrlIfValid($link_uri);
      }
      if ($link_url) {
        $link_classes = explode(' ', $ranking_link_style);
        if ($ranking_clickable) {
          $link_classes[] = 'stretched-link';
        }
        $link_render_array = [
          '#type' => 'link',
          '#title' => $link_title ?: $source,
          '#url' => $link_url,
          '#attributes' => ['class' => $link_classes],
        ];
      }
    }

    // Build the complete preview.
    $element['preview_wrapper']['preview'] = [
      '#theme' => 'az_ranking',
      '#media' => $media_render_array,
      '#ranking_heading' => $heading,
      '#ranking_description' => $description,
      '#ranking_source' => $source,
      '#ranking_header_style' => $ranking_header_style,
      '#ranking_alignment' => $ranking_alignment,
      '#ranking_hover_effect' => $ranking_hover_effect,
      '#ranking_clickable' => $ranking_clickable,
      '#ranking_font_color' => $ranking_font_color,
      '#text_color_override' => $text_color_override,
      '#ranking_link_style' => $ranking_link_style,
      '#ranking_source_classes' => $ranking_source_classes,
      '#link' => $link_render_array,
      '#link_url' => $link_url,
      '#link_title' => $link_title,
      '#attributes' => [
        'class' => $ranking_classes . ' widget-preview-ranking',
        'style' => 'transform: scale(0.8); transform-origin: center;',
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $parents = $form['#parents'];

    $max = 0;
    // Determine the number of widgets.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        // $is_unlimited_not_programmed = !$form_state->isProgrammed();
        break;

      default:
        $max = $cardinality - 1;
        break;
    }

    // Get the wrapper ID for AJAX.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $wrapper_id = $field_state['ajax_wrapper_id'] ?? NULL;

    // Check to see if we have delete buttons.
    for ($delta = 0; $delta <= $max; $delta++) {
      // Let's relocate the core remove button if we can.
      if (!empty($elements[$delta]['_actions']['delete'])) {
        $remove = $elements[$delta]['_actions']['delete'];
        unset($elements[$delta]['_actions']['delete']);
        // Relocate the delete button alongside our field collapse button.
        $elements[$delta]['ranking_actions']['delete'] = $remove;
        // Attempt to style it like collapse button.
        $elements[$delta]['ranking_actions']['delete']['#attributes']['class'][] = 'button--extrasmall';
        $elements[$delta]['ranking_actions']['delete']['#attributes']['class'][] = 'ms-3';
      }

      // Add a "Refresh Preview" button with AJAX.
      $elements[$delta]['ranking_actions']['refresh_preview'] = [
        '#type' => 'submit',
        '#value' => $this->t('Update Preview'),
        '#name' => 'refresh_preview_' . $delta,
        '#submit' => [[$this, 'refreshPreviewSubmit']],
        '#ajax' => [
          'callback' => [$this, 'rankingAjax'],
          'wrapper' => $wrapper_id,
        ],
        '#attributes' => [
          'class' => ['button--extrasmall', 'ms-3'],
        ],
        '#limit_validation_errors' => [],
      ];
    }
    return $elements;
  }

  /**
   * Submit handler for toggle button.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function rankingSubmit(array $form, FormStateInterface $form_state) {

    // Get triggering element.
    $triggering_element = $form_state->getTriggeringElement();
    $array_parents = $array_parents = array_slice($triggering_element['#array_parents'], 0, -2);

    // Determine delta.
    $delta = array_pop($array_parents);

    // Get the widget.
    $element = NestedArray::getValue($form, $array_parents);
    $field_name = $element['#field_name'];
    $field_parents = $element['#field_parents'];

    // Load current widget settings.
    $settings = static::getWidgetState($field_parents, $field_name, $form_state);

    // Prepare to toggle state.
    $status = TRUE;
    if (isset($settings['open_status'][$delta])) {
      $status = !$settings['open_status'][$delta];
    }
    $settings['open_status'][$delta] = $status;

    // Save new state and rebuild form.
    static::setWidgetState($field_parents, $field_name, $form_state, $settings);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for refresh preview button.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function refreshPreviewSubmit(array $form, FormStateInterface $form_state) {
    // This submit handler doesn't need to do anything special.
    // It just triggers a form rebuild via AJAX, which will update the preview.
    $form_state->setRebuild();
  }

  /**
   * Ajax callback returning list widget container for ajax submit.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Ajax response as render array.
   */
  public function rankingAjax(array &$form, FormStateInterface $form_state) {

    // Find the widget and return it.
    $element = [];
    $triggering_element = $form_state->getTriggeringElement();
    $array_parents = array_slice($triggering_element['#array_parents'], 0, -3);
    $element = NestedArray::getValue($form, $array_parents);

    return $element;
  }

  /**
   * Form element validation handler for the 'link_title' field.
   *
   * Makes field required if link_uri is provided.
   */
  public function validateRankingLinkTitle(&$element, FormStateInterface $form_state, &$complete_form) {
    $parents = $element['#array_parents'];
    array_pop($parents);
    $parent_element = NestedArray::getValue($complete_form, $parents);
    if (empty($element['#value']) && !empty($parent_element['link_uri']['#value'])) {
      $form_state->setError($element, $this->t('Ranking Link Title field is required when a URL is provided. Ranking Link Title may be visually hidden with a Ranking Link Style selection.'));
    }
  }

  /**
   * Form element validation handler for the 'link_url' field.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public function validateRankingLink(&$element, FormStateInterface $form_state, &$complete_form) {

    if (!empty($element['#value'])) {
      // Check to make sure the path can be found.
      if ($this->pathValidator->getUrlIfValid($element['#value'])) {
        // Url is valid, no conversion required.
        return;
      }
      if (
        str_starts_with($element['#value'], '/' . PublicStream::basePath()) &&
        file_exists('public:' . urldecode(str_replace(PublicStream::basePath(), '', $element['#value'])))
      ) {
        // Link to a public file which is confirmed to exist.
        return;
      }
      $form_state
        ->setError($element, $this->t('This link does not exist or you do not have permission to link to %path.', [
          '%path' => $element['#value'],
        ]));
    }
  }

  /**
   * Validate link_uri is filled when clickable is enabled.
   */
  public function validateRankingLinkRequired(&$element, FormStateInterface $form_state, &$complete_form) {
    // Get the ranking item's form values.
    $parents = $element['#array_parents'];

    // Remove 'link_uri' from the end to get the ranking item's parents.
    array_pop($parents);

    // Filter out 'widget' keys to build correct values path.
    $values_path = [];
    foreach ($parents as $key) {
      if ($key !== 'widget') {
        $values_path[] = $key;
      }
    }

    // Get the ranking item's values to check ranking_type.
    $ranking_values = NestedArray::getValue($form_state->getValues(), $values_path);

    // Check if this is a standard ranking (not image_only).
    $ranking_type = $ranking_values['ranking_type'] ?? 'standard';
    if ($ranking_type !== 'standard') {
      return;
    }

    // Get the paragraph's form values to check clickable setting.
    // Navigate to paragraph level: [field_az_main_content, 0].
    $paragraph_parents = array_slice($values_path, 0, 2);
    $paragraph_values = NestedArray::getValue($form_state->getValues(), $paragraph_parents);

    // Check if ranking_clickable is enabled in the paragraph behavior.
    $clickable = $paragraph_values['behavior_plugins']['az_rankings_paragraph_behavior']['ranking_clickable'] ?? FALSE;

    // If clickable is enabled and link_uri is empty, set an error.
    if ($clickable && empty($element['#value'])) {
      $form_state->setError($element, $this->t('Ranking Link URL field is required when Clickable rankings is enabled.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return isset($violation->arrayPropertyPath[0]) ? $element[$violation->arrayPropertyPath[0]] : $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      // Extract values from the details element structure.
      $details_values = $value['details'] ?? [];

      if (($details_values['ranking_heading'] ?? '') === '') {
        $values[$delta]['ranking_heading'] = NULL;
      }
      else {
        $values[$delta]['ranking_heading'] = $details_values['ranking_heading'] ?? NULL;
      }

      if (($details_values['ranking_description'] ?? '') === '') {
        $values[$delta]['ranking_description'] = NULL;
      }
      else {
        $values[$delta]['ranking_description'] = $details_values['ranking_description'] ?? NULL;
      }

      if (empty($details_values['media'])) {
        $values[$delta]['media'] = NULL;
      }
      else {
        $values[$delta]['media'] = $details_values['media'];
      }

      if (($details_values['ranking_source'] ?? '') === '') {
        $values[$delta]['ranking_source'] = NULL;
      }
      else {
        $values[$delta]['ranking_source'] = $details_values['ranking_source'] ?? NULL;
      }

      if (($details_values['link_uri'] ?? '') === '') {
        $values[$delta]['link_uri'] = NULL;
      }
      else {
        $values[$delta]['link_uri'] = $details_values['link_uri'] ?? NULL;
      }

      if (($details_values['link_title'] ?? '') === '') {
        $values[$delta]['link_title'] = NULL;
      }
      else {
        $values[$delta]['link_title'] = $details_values['link_title'];
      }

      if (($details_values['ranking_font_color'] ?? '') === '') {
        $values[$delta]['ranking_font_color'] = NULL;
      }
      else {
        $values[$delta]['ranking_font_color'] = $details_values['ranking_font_color'] ?? NULL;
      }

      if (($details_values['ranking_link_style'] ?? '') === '') {
        $values[$delta]['ranking_link_style'] = NULL;
      }
      else {
        $values[$delta]['ranking_link_style'] = $details_values['ranking_link_style'];
      }

      if (!empty($details_values['options']) || !empty($details_values['options_hover_effect']) || !empty($details_values['ranking_type']) || !empty($details_values['column_span'])) {

        $values[$delta]['options'] = [
          'class' => $details_values['options'] ?? '',
          'hover_class' => $details_values['options_hover_effect'] ?? '',
          'ranking_type' => $details_values['ranking_type'] ?? '',
          'column_span' => $details_values['column_span'] ?? '',
        ];
      }
      // Remove the details wrapper from the final values.
      unset($values[$delta]['details']);
    }
    return $values;
  }

  /**
   * Add az_ranking_context query parameter to media edit links.
   */
  public function addAzRankingContextToMediaEdit(array $element, FormStateInterface $form_state) {
    // Recursively search for media_edit links and add the query parameter.
    $this->addQueryParamToMediaEditLinks($element);
    return $element;
  }

  /**
   * Recursively add query parameter to media edit links.
   */
  protected function addQueryParamToMediaEditLinks(array &$element) {
    // Check if this element has a media_edit link.
    if (isset($element['media_edit']['#url']) && $element['media_edit']['#url'] instanceof Url) {
      $url = $element['media_edit']['#url'];
      $query = $url->getOption('query') ?? [];
      $query['az_ranking_context'] = '1';
      $url->setOption('query', $query);
    }

    // Recursively process child elements.
    foreach (Element::children($element) as $key) {
      if (is_array($element[$key])) {
        $this->addQueryParamToMediaEditLinks($element[$key]);
      }
    }
  }

}
