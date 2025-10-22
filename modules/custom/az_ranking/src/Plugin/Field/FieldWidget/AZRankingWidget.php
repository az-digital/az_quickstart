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
    $ranking_classes = 'ranking card';
    $parent = $item->getEntity();

    // Get settings from parent paragraph.
    if ($parent instanceof ParagraphInterface) {
      // Get the behavior settings for the parent.
      $parent_config = $parent->getAllBehaviorSettings();

      // See if the parent behavior defines some ranking-specific settings.
      if (!empty($parent_config['az_rankings_paragraph_behavior'])) {
        $ranking_defaults = $parent_config['az_rankings_paragraph_behavior'];
        $ranking_classes = $ranking_defaults['ranking_hover_style'] ?? 'ranking card';
      }
    }

    // Add overflow-hidden class.
    $ranking_classes .= ' overflow-hidden';

    // Handle hover effect and background classes like the formatter does.
    $ranking_hover_effect = FALSE;
    if ($parent instanceof ParagraphInterface) {
      $parent_config = $parent->getAllBehaviorSettings();
      if (!empty($parent_config['az_rankings_paragraph_behavior'])) {
        $ranking_hover_effect = $parent_config['az_rankings_paragraph_behavior']['ranking_hover_effect'] ?? FALSE;
      }
    }

    // Hover effect takes precedence over non-hover-effect backgrounds.
    if ($ranking_hover_effect) {
      // Try to read hover-specific value from the item.
      $hover_class = '';
      if (!empty($item->options_hover_effect)) {
        if (is_array($item->options_hover_effect) && !empty($item->options_hover_effect['class'])) {
          $hover_class = $item->options_hover_effect['class'];
        }
        elseif (is_string($item->options_hover_effect)) {
          $hover_class = $item->options_hover_effect;
        }
      }
      // Fallback to the persisted background class if no hover-specific value present.
      if (empty($hover_class) && !empty($item->options['class'])) {
        $hover_class = $item->options['class'];
      }
      if (!empty($hover_class)) {
        $ranking_classes .= ' from-hover-effect ' . $hover_class;
      }
    }
    else {
      if (!empty($item->options['class'])) {
        $ranking_classes .= ' non-hover-effect ' . $item->options['class'];
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

    // When closed, show a preview of the ranking.
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

      // Build the preview using the helper method.
      $element['preview_wrapper']['preview'] = $this->buildRankingPreview($item, $ranking_classes);
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

    // All other fields are NOT visible if the Ranking Type is "image_only".
    $element['details']['options'] = [
      '#type' => 'select',
      '#options' => [
        'text-bg-chili' => $this->t('Chili (default)'),
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
      '#default_value' => (!empty($item->options['class'])) ? $item->options['class'] : 'text-bg-chili',
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
        'text-bg-chili' => $this->t('Chili (default)'),
        'text-bg-blue' => $this->t('Arizona Blue'),
        'text-bg-sky' => $this->t('Sky'),
        'text-bg-cool-gray' => $this->t('Cool Gray'),
        'text-bg-oasis' => $this->t('Oasis'),
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
        'ranking-text-black' => $this->t('Black (default)'),
        'ranking-text-white' => $this->t('White'),
        'ranking-text-az-blue' => $this->t('Arizona Blue'),
      ],
      '#default_value' => $item->ranking_font_color ?? 'ranking-text-black',
      '#states' => [
        'visible' => [
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
          'and',
          // Ranking Background (options) must be "bg-transparent".
          //':input[data-az-ranking-bg-input-id="' . $ranking_background_unique_id . '"]' => ['value' => 'bg-transparent'],
          ':input[name*="[options]"]' => ['value' => 'bg-transparent'],
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
      '#title' => $this->t('Ranking Link Title'),
      '#default_value' => $item->link_title ?? NULL,
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          // If whole ranking is clickable, hide the title.
          ':input[data-az-ranking-type-input-id="' . $ranking_type_unique_id . '"]' => ['value' => 'standard'],
          'and',
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
      '#title' => $this->t('Ranking Link URL'),
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
      '#title' => $this->t('Ranking Link Style'),
      '#options' => [
        'd-none' => $this->t('Hidden link title'),
        'link' => $this->t('Text link'),
        'w-100 btn btn-red' => $this->t('Red button (default)'),
        'w-100 btn btn-blue' => $this->t('Blue button'),
        'w-100 btn btn-outline-red' => $this->t('Red outline button'),
        'w-100 btn btn-outline-blue' => $this->t('Blue outline button'),
        'w-100 btn btn-outline-white' => $this->t('White outline button'),
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

    // Store delta and field name for reference.
    $element['#delta'] = $delta;
    $element['#field_name'] = $field_name;

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
        $elements[$delta]['ranking_actions']['delete']['#attributes']['class'][] = 'ml-3';
      }
      
      // Add a "Refresh Preview" button with AJAX.
      $elements[$delta]['ranking_actions']['refresh_preview'] = [
        '#type' => 'submit',
        '#value' => $this->t('Preview'),
        '#name' => 'refresh_preview_' . $delta,
        '#submit' => [[$this, 'refreshPreviewSubmit']],
        '#ajax' => [
          'callback' => [$this, 'rankingAjax'],
          'wrapper' => $wrapper_id,
        ],
        '#attributes' => [
          'class' => ['button--extrasmall', 'ml-3'],
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
          'ranking_type' => $details_values['ranking_type'] ?? '',
          'column_span' => $details_values['column_span'] ?? '',
        ];
      }
      if (!empty($details_values['options_hover_effect']) || !empty($details_values['ranking_type']) || !empty($details_values['column_span'])) {
        $values[$delta]['options_hover_effect'] = [
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

  /**
   * Build the preview render array for a ranking item.
   *
   * @param \Drupal\az_ranking\Plugin\Field\FieldType\AZRankingItem $item
   *   The ranking item.
   * @param string $ranking_classes
   *   The ranking CSS classes.
   *
   * @return array
   *   The preview render array.
   */
  protected function buildRankingPreview($item, $ranking_classes) {
    $parent = $item->getEntity();

    // Get ranking settings from parent paragraph.
    $ranking_hover_effect = FALSE;
    $ranking_clickable = FALSE;
    $ranking_title_style = NULL;
    if ($parent instanceof ParagraphInterface) {
      $parent_config = $parent->getAllBehaviorSettings();
      if (!empty($parent_config['az_rankings_paragraph_behavior'])) {
        $ranking_defaults = $parent_config['az_rankings_paragraph_behavior'];
        $ranking_hover_effect = $ranking_defaults['ranking_hover_effect'] ?? FALSE;
        $ranking_clickable = $ranking_defaults['ranking_clickable'] ?? FALSE;
        $ranking_title_style = $ranking_defaults['ranking_title_style'] ?? NULL;
      }
    }

    // Apply clickable ranking styles (like formatter does).
    $link_title = $item->link_title ?? '';
    $ranking_link_style = $item->ranking_link_style ?? 'w-100 btn btn-red';

    if (isset($ranking_clickable)) {
      if (!empty($ranking_clickable)) {
        // Whole card is clickable.
        $ranking_classes .= ' shadow';
        $link_title = '';
        $ranking_link_style = '';
        // Add hover effect to ranking card.
        if (!empty($ranking_hover_effect)) {
          $ranking_classes .= ' ranking-bold-hover';
        }
        if (!empty($item->link_uri)) {
          $ranking_classes .= ' ranking-with-link';
        }
      }
      else {
        // Ranking is not clickable.
        $link_title = $item->link_title ?? '';
        $ranking_link_style = $item->ranking_link_style ?? 'w-100 btn btn-red';
        $ranking_hover_effect = FALSE;
      }
    }

    // Determine font color and text color override.
    $ranking_font_color = $item->ranking_font_color ?? 'ranking-text-black';
    $text_color_override = '';

    // Determine source classes based on background color (like formatter does).
    $ranking_source_classes = '';
    $background_class = '';

    // Get the appropriate background class depending on hover effect.
    if ($ranking_hover_effect) {
      if (!empty($item->options_hover_effect)) {
        if (is_array($item->options_hover_effect) && !empty($item->options_hover_effect['class'])) {
          $background_class = $item->options_hover_effect['class'];
        }
        elseif (is_string($item->options_hover_effect)) {
          $background_class = $item->options_hover_effect;
        }
      }
      // Fallback to the persisted background class.
      if (empty($background_class) && !empty($item->options['class'])) {
        $background_class = $item->options['class'];
      }
    }
    else {
      $background_class = $item->options['class'] ?? '';
    }

    // Apply mt-auto if NOT transparent background.
    if (!str_contains($background_class, 'bg-transparent')) {
      $ranking_source_classes = 'mt-auto';
    }
    else {
      // For transparent backgrounds, apply font color to ranking_classes and ranking_font_color.
      $ranking_font_color = ' ' . $item->ranking_font_color;
      $ranking_classes .= ' ' . $item->ranking_font_color;
    }

    // Set text_color_override based on background color (like formatter does).
    if (!$ranking_hover_effect) {
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
    }
    else {
      // Override hover class.
      if (!empty($item->options_hover_effect['class'])) {
        switch (TRUE) {
          case str_contains($item->options_hover_effect['class'], 'bg-sky'):
            $text_color_override = 'text-midnight';
            break;

          case str_contains($item->options_hover_effect['class'], 'bg-cool-gray'):
            $text_color_override = 'text-azurite';
            break;

          case str_contains($item->options_hover_effect['class'], 'bg-oasis'):
            $text_color_override = 'text-white';
            break;
        }
      }
    }

    // Build media render array.
    $media_render_array = NULL;
    $media_id = $item->media ?? NULL;
    if (!empty($media_id)) {
      if ($media = $this->entityTypeManager->getStorage('media')->load($media_id)) {
        $media_render_array = $this->rankingImageHelper->generateImageRenderArray($media);
      }
    }

    // Build link render array and URL.
    $link_render_array = NULL;
    $link_url = NULL;
    if ($item->link_title || $item->link_uri) {
      if (!empty($item->link_uri) && str_starts_with($item->link_uri, '/' . PublicStream::basePath())) {
        $link_url = Url::fromUri(urldecode('base:' . $item->link_uri));
      }
      else {
        $link_url = $this->pathValidator->getUrlIfValid($item->link_uri ?? '<none>');
      }

      if ($link_url) {
        $link_classes = explode(' ', $ranking_link_style);

        // Add stretched-link class if ranking is clickable.
        if (!empty($ranking_clickable)) {
          $link_classes[] = 'stretched-link';
        }

        $link_render_array = [
          '#type' => 'link',
          '#title' => $link_title,
          '#url' => $link_url,
          '#attributes' => ['class' => $link_classes],
        ];
      }
    }

    return [
      '#theme' => 'az_ranking',
      '#media' => $media_render_array,
      '#ranking_heading' => $item->ranking_heading ?? '',
      '#ranking_description' => $item->ranking_description ?? '',
      '#ranking_source' => $item->ranking_source ?? '',
      '#ranking_title_style' => $ranking_title_style,
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
  }

  /**
   * #after_build callback to add az_ranking_context query parameter to media edit links.
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
    if (isset($element['media_edit']['#url']) && $element['media_edit']['#url'] instanceof \Drupal\Core\Url) {
      $url = $element['media_edit']['#url'];
      $query = $url->getOption('query') ?? [];
      $query['az_ranking_context'] = '1';
      $url->setOption('query', $query);
    }
    
    // Recursively process child elements.
    foreach (\Drupal\Core\Render\Element::children($element) as $key) {
      if (is_array($element[$key])) {
        $this->addQueryParamToMediaEditLinks($element[$key]);
      }
    }
  }

}
