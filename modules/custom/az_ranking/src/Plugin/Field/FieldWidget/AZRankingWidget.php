<?php

namespace Drupal\az_ranking\Plugin\Field\FieldWidget;

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

    // We may have had a deleted row. This shouldn't be necessary to check, but
    // The experimental paragraphs widget extracts values before the submit
    // handler.
    if (isset($widget_state['original_deltas'][$delta]) && ($widget_state['original_deltas'][$delta] !== $delta)) {
      $delta = $widget_state['original_deltas'][$delta];
    }

    // New field values shouldn't be considered collapsed.
    if ($item->isEmpty()) {
      $status = TRUE;
    }

    // Determine current ranking style needed for both preview and open edit mode.
    $ranking_classes = 'card';
    $parent = $item->getEntity();

    // Get settings from parent paragraph.
    if ($parent instanceof ParagraphInterface) {
      // Get the behavior settings for the parent.
      $parent_config = $parent->getAllBehaviorSettings();

      // See if the parent behavior defines some ranking-specific settings.
      if (!empty($parent_config['az_rankings_paragraph_behavior'])) {
        $ranking_defaults = $parent_config['az_rankings_paragraph_behavior'];
        $ranking_classes = $ranking_defaults['ranking_hover_style'] ?? 'card';
      }
    }

    // Add ranking class from options.
    if (!empty($item->options['class'])) {
      $ranking_classes .= ' ' . $item->options['class'];
    }
    if (!empty($item->options_hover_effect['class'])) {
      $ranking_classes .= ' ' . $item->options_hover_effect['class'];
    }

    // Create summary for details element (what shows when collapsed)
    $summary_text = '';
    if (!$item->isEmpty()) {
      $summary_parts = array_filter([
        $item->ranking_heading,
        $item->ranking_description,
        $item->ranking_source ? $this->t('Source: @source', ['@source' => substr($item->ranking_source, 0, 50) . '...']) : NULL,
      ]);
      $summary_text = implode(' | ', $summary_parts) ?: $this->t('Ranking @delta', ['@delta' => $delta + 1]);
    }
    else {
      $summary_text = $this->t('New Ranking @delta', ['@delta' => $delta + 1]);
    }

    // Wrap everything in a details element.
    $element['details'] = [
      '#type' => 'details',
      '#title' => $summary_text,
    // Open when in edit mode, closed when in preview mode.
      '#open' => $status,
      '#attributes' => ['class' => ['az-ranking-widget']],
    ];

    // When closed, add a preview of the ranking after the summary.
    if (!$status) {
      $element['preview_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['widget-preview-wrapper'],
          'style' => 'max-width: 320px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;',
        ],
        // Show before the details element.
        '#weight' => -10,
      ];

     
      $element['preview_wrapper']['preview'] = [
        '#theme' => 'az_ranking',
        '#ranking_heading' => $item->ranking_heading ?? '',
        '#ranking_description' => $item->ranking_description ?? '',
        '#ranking_source' => $item->ranking_source ?? '',
        '#attributes' => [
          'class' => $ranking_classes . ' widget-preview-ranking',
          'style' => 'transform: scale(0.8); transform-origin: center;',
        ],
      ];

      // Add media to preview if available.
      $media_id = $item->media ?? NULL;
      if (!empty($media_id)) {
        if ($media = $this->entityTypeManager->getStorage('media')->load($media_id)) {
          $media_render_array = $this->rankingImageHelper->generateImageRenderArray($media);
          if (!empty($media_render_array)) {
            $element['preview_wrapper']['preview']['#media'] = $media_render_array;
          }
        }
      }

      // Add link to preview if available.
      if ($item->link_title || $item->link_uri) {
        if (!empty($item->link_uri) && str_starts_with($item->link_uri, '/' . PublicStream::basePath())) {
          $link_url = Url::fromUri(urldecode('base:' . $item->link_uri));
        }
        else {
          $link_url = $this->pathValidator->getUrlIfValid($item->link_uri ?? '<none>');
        }
        $element['preview_wrapper']['preview']['#link'] = [
          '#type' => 'link',
          '#title' => $item->ranking_source ?? '',
          '#url' => $link_url ?: Url::fromRoute('<none>'),
          '#attributes' => ['class' => ['btn', 'btn-default', 'w-100', 'az-ranking-no-follow']],
        ];
      }
    }

    // Create a globally unique ID that includes
    // parent entity info and field parents.
    $parent_entity = $item->getEntity();
    $parent_id = $parent_entity ? $parent_entity->id() : 'new';
    $field_parents_string = implode('-', $field_parents);

    // Set ids for fields that are dependent on Type and Background Color.
    $ranking_type_unique_id = 'ranking-type-' . $parent_id . '-' . $field_parents_string . '-' . $delta;
    $ranking_background_unique_id = 'ranking-bg-' . $parent_id . '-' . $field_parents_string . '-' . $delta;

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

    // Get ranking_width from parent config for help text calculation.
    // Default value.
    $ranking_width = 'col-lg-3';
    $parent = $item->getEntity();
    if ($parent instanceof ParagraphInterface) {
      $parent_config = $parent->getAllBehaviorSettings();
      if (!empty($parent_config['az_rankings_paragraph_behavior']['ranking_width'])) {
        $ranking_width = $parent_config['az_rankings_paragraph_behavior']['ranking_width'];
      }
    }

    $element['details']['media'] = [
      '#type' => 'az_media_library',
      '#default_value' => $item->media ?? NULL,
      '#allowed_bundles' => ['az_image'],
      '#delta' => $delta,
      '#cardinality' => 1,
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
        2 => $this->t('2 cards'),
        3 => $this->t('3 cards'),
        4 => $this->t('4 cards'),
      ],
      '#title' => $this->t('Image Width Span'),
      '#description' => $this->t('How many cards do you want this image to span (in multiples of ranking-card width)?') . '<br><br><div class="aspect-ratio-help" data-current-ranking-width="' . $ranking_width . '">' . $this->getAspectRatioHelpText($ranking_width) . '</div>',
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

    // All other fields are NOT visible if the Ranking Type is "image_only".
    $element['details']['options'] = [
      '#type' => 'select',
      '#options' => [
        'text-bg-chili' => $this->t('Chili'),
        'text-bg-blue' => $this->t('Arizona Blue'),
        'text-bg-sky' => $this->t('Sky'),
        'text-bg-oasis' => $this->t('Oasis'),
        'text-bg-azurite' => $this->t('Azurite'),
        'text-bg-cool-gray' => $this->t('Cool Gray'),
        'text-bg-warm-gray' => $this->t('Warm Gray'),
        'text-bg-white' => $this->t('White'),
        'bg-transparent' => $this->t('Transparent'),
      ],
      '#required' => TRUE,
      '#attributes' => ['data-az-ranking-bg-input-id' => $ranking_background_unique_id],
      '#title' => $this->t('Ranking Background'),
      '#default_value' => (!empty($item->options['class'])) ? $item->options['class'] : 'text-bg-white',
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
          'and',
          ':input[name*="[behavior_plugins][az_rankings_paragraph_behavior][ranking_hover_effect]"]' => [
            ['checked' => FALSE],
          ],
        ],
      ],
    ];

    // All other fields are NOT visible if the Ranking Type is "image_only".
    $element['details']['options_hover_effect'] = [
      '#type' => 'select',
      '#options' => [
        'text-bg-chili' => $this->t('Chili'),
        'text-bg-blue' => $this->t('Arizona Blue'),
        'text-bg-sky' => $this->t('Sky'),
        'text-bg-cool-gray' => $this->t('Cool Gray'),
      ],
      '#required' => TRUE,
      '#attributes' => ['data-az-ranking-bg-input-id' => $ranking_background_unique_id],
      '#title' => $this->t('Ranking Background with Hover Effect'),
      '#default_value' => (!empty($item->options_hover_effect['class'])) ? $item->options_hover_effect['class'] : 'text-bg-chili',
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
          'and',
          ':input[name*="[behavior_plugins][az_rankings_paragraph_behavior][ranking_hover_effect]"]' => [
            ['checked' => TRUE],
          ],
        ],
      ],
    ];

    $element['details']['ranking_font_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Ranking Font Color'),
      '#options' => [
        'ranking-text-black' => $this->t('Black'),
        'ranking-text-white' => $this->t('White'),
        'ranking-text-az-blue' => $this->t('Arizona Blue'),
      ],
      '#default_value' => $item->ranking_font_color ?? 'ranking-text-black',
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
          'and',
          // Ranking Background (options) must be "bg-transparent".
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
      '#title' => $this->t('Link Title'),
      '#default_value' => $item->link_title ?? NULL,
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          // If whole ranking is clickable, hide the title.
          ':input[name*="[behavior_plugins][az_rankings_paragraph_behavior][ranking_hover_effect]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $element['details']['link_uri'] = [
      '#type' => 'linkit',
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => 'az_linkit',
      ],
      '#title' => $this->t('Link URL'),
      '#element_validate' => [[$this, 'validateRankingLink']],
      '#default_value' => $item->link_uri ?? NULL,
      '#maxlength' => 2048,
      // Don't use server-side required - let #states handle it dynamically.
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
        ],
        // Link URI is required when Ranking Type is 'standard',
        // AND the hover style is NOT static.
        'required' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
          ':input[name*="[behavior_plugins][az_rankings_paragraph_behavior][ranking_clickable]"]' => [
            ['checked' => TRUE],
          ],
        ],
      ],
    ];

    $element['details']['ranking_link_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Link Style'),
      '#options' => [
        'd-none' => $this->t('Hidden Link Title'),
        'link' => $this->t('Text Link'),
        'w-100 btn btn-red' => $this->t('Red Button'),
        'w-100 btn btn-blue' => $this->t('Blue Button'),
        'w-100 btn btn-outline-red' => $this->t('Red Outline Button'),
        'w-100 btn btn-outline-blue' => $this->t('Blue Outline Button'),
        'w-100 btn btn-outline-white' => $this->t('White Outline Button'),
      ],
      '#default_value' => $item->ranking_link_style ?? 'w-100 btn btn-red',
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
          'and',
          ':input[name*="[behavior_plugins][az_rankings_paragraph_behavior][ranking_hover_effect]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // Attach the library and return the element.
    $element['#attached']['library'][] = 'az_ranking/az_ranking';
    $element['#attached']['library'][] = 'az_ranking/az_ranking_dynamic_helptext';

    // Store delta and field name for reference.
    $element['#delta'] = $delta;
    $element['#field_name'] = $field_name;

    // Pass aspect ratio data to JavaScript.
    $element['#attached']['drupalSettings']['azRanking']['aspectRatios'] = $this->getAspectRatioData();

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
        $elements[$delta]['ranking_actions']['delete']['#attributes']['class'][] = 'ml-3';
      }
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
   * Get aspect ratio data array.
   *
   * @return array
   *   Array of aspect ratio data keyed by ranking width.
   */
  protected function getAspectRatioData() {
    return [
      'col-lg-12' => [
        'any' => '5:1',
      ],
      'col-lg-6' => [
        '1' => '2.45:1',
        '2+' => '5:1',
      ],
      'col-lg-4' => [
        '1' => '1.6:1',
        '2' => '3.3:1',
        '3+' => '5:1',
      ],
      'col-lg-3' => [
        '1' => '1.2:1',
        '2' => '2.45:1',
        '3' => '3.8:1',
        '4' => '5:1',
      ],
    ];
  }

  /**
   * Get aspect ratio help text based on ranking_width and column_span.
   *
   * @param string $ranking_width
   *   The ranking width setting from parent paragraph behavior.
   *
   * @return string
   *   The help text with recommended aspect ratios.
   */
  protected function getAspectRatioHelpText($ranking_width) {
    $aspect_ratios = $this->getAspectRatioData();

    if (!isset($aspect_ratios[$ranking_width])) {
      // No help text for unknown ranking_width.
      return '';
    }

    $help_text = '<strong>' . $this->t('Your image will be automatically cropped to these ratios (W:H):') . '</strong><br>';
    $ratios = $aspect_ratios[$ranking_width];
    $lines = [];

    foreach ($ratios as $key => $ratio) {
      if ($key === 'any') {
        $label = $this->t('Any column span');
      }
      else {
        $label = $key . ' ' . $this->t('column') . (strpos($key, '+') !== FALSE || (is_numeric($key) && intval($key) > 1) ? 's' : '');
      }
      $lines[] = $label . ': <strong>' . $ratio . '</strong>';
    }

    $help_text .= implode('<br>', $lines);

    return $help_text;
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
          'class' => $details_values['options_hover_effect'] ?? '',
          'ranking_type' => $details_values['ranking_type'] ?? '',
          'column_span' => $details_values['column_span'] ?? '',
        ];
      }

      // Remove the details wrapper from the final values.
      unset($values[$delta]['details']);
    }
    return $values;
  }

}
