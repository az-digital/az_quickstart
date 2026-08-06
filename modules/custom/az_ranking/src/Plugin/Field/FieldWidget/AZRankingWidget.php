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
   * Drupal\Core\Path\PathValidator definition.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * The AZRankingComponentBuilder service.
   *
   * @var \Drupal\az_ranking\AZRankingComponentBuilder
   */
  protected $componentBuilder;

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

    $instance->pathValidator = $container->get('path.validator');
    $instance->componentBuilder = $container->get('az_ranking.component_builder');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {

    // Every row has to be AJAX-replaced as one block, so the wrapper id has
    // to be agreed on before any row is built. Widget state is the only
    // place both this method and formElement() can reach it.
    $wrapper_id = Html::getUniqueId('az-ranking-wrapper');
    $field_name = $this->fieldDefinition->getName();
    $field_parents = $form['#parents'];
    $field_state = static::getWidgetState($field_parents, $field_name, $form_state);
    $field_state['ajax_wrapper_id'] = $wrapper_id;

    // Remove extra field added on form instantiation for existing content.
    $count = count($items);
    $field_state['items_count'] = (!empty($field_state['items_count'])) ? $field_state['items_count'] : max(0, $count - 1);

    $field_state['array_parents'] = [];

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

    $field_name = $this->fieldDefinition->getName();

    // Start a row open only if it has nothing in it yet, so a new ranking is
    // ready to type into and saved ones stay out of the way.
    $status = $item->isEmpty();

    // Needed for the unique-ID generation (behavior-settings lookup) and
    // for rebuildRankingPreview() (see AZRankingItemElement).
    $parent = $item->getEntity();
    $ranking_defaults = [];
    if ($parent instanceof ParagraphInterface) {
      // Get the behavior settings for the parent.
      $parent_config = $parent->getAllBehaviorSettings();
      $ranking_defaults = $parent_config['az_rankings_paragraph_behavior'] ?? [];
    }

    // Building the actual fields + preview is delegated to a real Element
    // plugin (AZRankingItemElement) instead of happening directly here -
    // see that class's docblock for why. #widget carries the widget
    // instance through so its own instance methods (element_validate/
    // after_build callbacks used by individual fields, and the two methods
    // below) are reachable from the Element class's static callbacks. This
    // is safe to cache/serialize across AJAX rebuilds because WidgetBase ->
    // PluginBase uses DependencySerializationTrait.
    $element['#type'] = 'az_ranking_item';
    $element['#widget'] = $this;
    $element['#az_item'] = $item;
    $element['#ranking_defaults'] = $ranking_defaults;
    $element['#status'] = $status;
    $element['#delta'] = $delta;
    $element['#field_name'] = $field_name;

    $element['#attached']['library'][] = 'az_ranking/az_ranking';

    return $element;
  }

  /**
   * Builds the details fields and preview placeholder for a ranking item.
   *
   * Called from AZRankingItemElement::processRankingItem() (this widget's
   * #type => az_ranking_item elements stash $this on #widget for exactly
   * this purpose).
   */
  public function buildRankingItemElement(array $element, FormStateInterface $form_state): array {
    /** @var \Drupal\az_ranking\Plugin\Field\FieldType\AZRankingItem $item */
    $item = $element['#az_item'];
    $status = $element['#status'];
    $delta = $element['#delta'];
    $field_parents = $element['#field_parents'];
    $parent = $item->getEntity();

    // Wrap everything in a details element.
    $element['details'] = [
      '#type' => 'details',
      '#title' => $this->t('Edit Ranking'),
      // Closed rows show the preview instead; see below.
      '#open' => $status,
      '#attributes' => ['class' => ['az-ranking-widget']],
    ];

    // A closed row shows a rendered card in place of its fields.
    if (!$status) {
      $element['preview_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['widget-preview-wrapper'],
          'style' => 'max-width: 320px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; height: 260px;',
        ],
        // Negative weight puts the preview above the details element.
        '#weight' => -10,
      ];

      // Left empty on purpose. rebuildRankingPreview() fills it in later,
      // from the Form API #values rather than from $item. Rationale: after a
      // drag-and-drop reorder, $items still holds the stored order, so a
      // preview built here would show the card that used to be in this
      // position. #value reflects the row the editor is actually looking at.
      $element['preview_wrapper']['preview'] = [
        '#type' => 'component',
        '#component' => 'az_quickstart:ranking',
        '#props' => [],
      ];
    }

    // Create a globally unique ID that includes
    // parent entity info and field parents.
    $parent_entity = $item->getEntity();
    $parent_id = $parent_entity ? $parent_entity->id() : 'new';
    $field_parents_string = implode('-', $field_parents);

    // Set ids for fields that are dependent on Type and Background Color.
    $ranking_type_unique_id = 'ranking-type-' . $parent_id . '-' . $field_parents_string . '-' . $delta;
    $ranking_background_unique_id = 'ranking-bg-' . $parent_id . '-' . $field_parents_string . '-' . $delta;

    // These IDs have to match the ones AZRankingsParagraphBehavior builds
    // independently, because the #states selectors below reference its
    // checkboxes by data attribute. Both sides derive them from the form
    // parents so they agree without either passing anything to the other.
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

    return $element;
  }

  /**
   * Rebuilds the preview from the Form API-populated field values.
   *
   * Called from AZRankingItemElement::afterBuildRebuildPreview() (this
   * widget's #type => az_ranking_item elements declare that as their own
   * #after_build in AZRankingItemElement::getInfo()).
   *
   * Rebuilds preview_wrapper.preview from the same Form API-populated
   * #value the form fields themselves use, instead of $item, so the
   * preview stays in sync with its row after drag-and-drop reorder + an
   * AJAX rebuild. #after_build runs after this element's children
   * (including details' fields) have already gone through the Form API's
   * own value-population, so #value here is already correct for wherever
   * this element currently sits in the (possibly just-reordered) tree.
   *
   * @see https://github.com/az-digital/az_quickstart/pull/5309
   */
  public function rebuildRankingPreview(array $element, FormStateInterface $form_state) {
    // Nothing to rebuild when the details are open (no preview shown).
    if (!isset($element['preview_wrapper']['preview'])) {
      return $element;
    }

    $details = $element['details'] ?? [];
    $ranking_defaults = $element['#ranking_defaults'] ?? [];

    $values = [
      'ranking_heading' => $details['ranking_heading']['#value'] ?? $details['ranking_heading']['#default_value'] ?? '',
      'ranking_description' => $details['ranking_description']['#value'] ?? $details['ranking_description']['#default_value'] ?? '',
      'ranking_source' => $details['ranking_source']['#value'] ?? $details['ranking_source']['#default_value'] ?? '',
      'link_uri' => $details['link_uri']['#value'] ?? $details['link_uri']['#default_value'] ?? '',
      'link_title' => $details['link_title']['#value'] ?? $details['link_title']['#default_value'] ?? '',
      'ranking_link_style' => $details['ranking_link_style']['#value'] ?? $details['ranking_link_style']['#default_value'] ?? 'w-100 btn btn-red mt-2',
      'ranking_font_color' => $details['ranking_font_color']['#value'] ?? $details['ranking_font_color']['#default_value'] ?? 'ranking-text-midnight',
      'options' => [
        'class' => $details['options']['#value'] ?? $details['options']['#default_value'] ?? 'text-bg-chili',
        'hover_class' => $details['options_hover_effect']['#value'] ?? $details['options_hover_effect']['#default_value'] ?? 'text-bg-chili',
        'ranking_type' => $details['ranking_type']['#value'] ?? $details['ranking_type']['#default_value'] ?? 'standard',
        'column_span' => $details['column_span']['#value'] ?? $details['column_span']['#default_value'] ?? 2,
      ],
    ];

    // az_media_library implements a real #value_callback (see
    // Drupal\media_library_form_element\Element\MediaLibrary::valueCallback,
    // confirmed by reading it directly), so #value should already be
    // reorder-correct here just like the fields above. Still read from raw
    // user input as a fallback/cross-check - PR #5309 found the equivalent
    // field unreliable via #value/#default_value alone on this same widget
    // family, and this is cheap insurance against the same class of bug.
    $media_id = NULL;
    $delta = $element['#delta'];
    $field_name = $element['#field_name'];
    $field_parents = $element['#field_parents'];
    $user_input = $form_state->getUserInput() ?? [];
    $input_path = array_merge($field_parents, [$field_name, $delta, 'details', 'media']);
    $media_input = NestedArray::getValue($user_input, $input_path);
    if (is_array($media_input) && !empty($media_input['media_library_selection'])) {
      $ids = array_filter(explode(',', $media_input['media_library_selection']));
      $media_id = $ids ? (int) reset($ids) : NULL;
    }
    elseif (is_numeric($media_input)) {
      $media_id = (int) $media_input;
    }
    // First render of a fresh form - no user input exists yet, so fall back
    // to what was stored.
    if ($media_id === NULL && $media_input === NULL) {
      $media_id = $details['media']['#value'] ?? $details['media']['#default_value'] ?? NULL;
    }
    $values['media'] = $media_id;

    $ranking_type = $values['options']['ranking_type'] ?? 'standard';
    if ($ranking_type === 'image_only') {
      // width_span_* has no visual effect here (this preview is a single
      // box, not a grid), but computing deck_props the same way the
      // formatter does keeps the props themselves accurate, in case that
      // ever changes.
      $deck_props = $this->componentBuilder->buildDeckProps($ranking_defaults);
      $preview = $this->componentBuilder->buildImageComponent($values, $deck_props);
    }
    else {
      $preview = $this->componentBuilder->buildRankingComponent($values, $ranking_defaults);
    }

    $preview['#attributes']['class'][] = 'widget-preview-ranking';
    $preview['#attributes']['style'] = 'transform: scale(0.8); transform-origin: center;';

    $element['preview_wrapper']['preview'] = $preview;

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
        break;

      default:
        $max = $cardinality - 1;
        break;
    }

    // Get the wrapper ID for AJAX.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $wrapper_id = $field_state['ajax_wrapper_id'] ?? NULL;

    for ($delta = 0; $delta <= $max; $delta++) {
      // Keep the buttons below the "Edit Ranking" details element. Rationale:
      // details is built later, by AZRankingItemElement's #process callback,
      // so ranking_actions is the first child at this point and would tie
      // with it on weight 0 and win. An explicit weight stops the render
      // order depending on which of the two happens to be added first.
      $elements[$delta]['ranking_actions']['#weight'] = 10;

      // Check to see if we have delete buttons.
      //
      // Move core's remove button into our own actions area, and match its
      // sizing, so it sits beside the Update Preview button added below
      // instead of landing in a separate actions area of its own.
      if (!empty($elements[$delta]['_actions']['delete'])) {
        $remove = $elements[$delta]['_actions']['delete'];
        unset($elements[$delta]['_actions']['delete']);
        $elements[$delta]['ranking_actions']['delete'] = $remove;
        $elements[$delta]['ranking_actions']['delete']['#attributes']['class'][] = 'button--extrasmall';
        $elements[$delta]['ranking_actions']['delete']['#attributes']['class'][] = 'ms-3';
      }

      // The preview only refreshes on an AJAX round trip, so an editor needs
      // a way to ask for one without saving. #limit_validation_errors is
      // empty because refreshing a half-filled row should show what is there,
      // not refuse until every required field is valid.
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
