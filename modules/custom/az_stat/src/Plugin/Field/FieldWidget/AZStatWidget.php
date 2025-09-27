<?php

namespace Drupal\az_stat\Plugin\Field\FieldWidget;

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
 * Defines the 'az_stat' field widget.
 */
#[FieldWidget(
  id: 'az_stat',
  label: new TranslatableMarkup('Stat'),
  field_types: ['az_stat'],
)]
class AZStatWidget extends WidgetBase {

  // Default initial text format for stats.
  const AZ_STAT_DEFAULT_TEXT_FORMAT = 'az_standard';

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

    $instance->statImageHelper = $container->get('az_stat.image');
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
    $wrapper_id = Html::getUniqueId('az-stat-wrapper');
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
    // We need to be sure not to clobber the parent class ajax wrapper.
    $container['widget']['#prefix'] = ($container['widget']['#prefix'] ?? '') . '<div id="' . $wrapper_id . '">';
    $container['widget']['#suffix'] = '</div>' . ($container['widget']['#suffix'] ?? '');

    if (isset($container['widget']['add_more']['#ajax']['wrapper'])) {
      
      $container['widget']['add_more']['#ajax']['wrapper'] = $wrapper_id;
      //$container['widget']['add_more']['#ajax']['callback'] = [$this, 'statAjax'];
    }
    return $container;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\az_stat\Plugin\Field\FieldType\AZStatItem $item */
    $item = $items[$delta];

    // Get current collapse status.
    $field_name = $this->fieldDefinition->getName();
    $field_parents = $element['#field_parents'];
    $widget_state = static::getWidgetState($field_parents, $field_name, $form_state);
    $wrapper = $widget_state['ajax_wrapper_id'];
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

    // Determine current stat style - needed for both preview and open edit mode.
    $stat_classes = 'card';
    $parent = $item->getEntity();

    // Get settings from parent paragraph.
    if ($parent instanceof ParagraphInterface) {
      // Get the behavior settings for the parent.
      $parent_config = $parent->getAllBehaviorSettings();

      // See if the parent behavior defines some stat-specific settings.
      if (!empty($parent_config['az_stats_paragraph_behavior'])) {
        $stat_defaults = $parent_config['az_stats_paragraph_behavior'];
        $stat_classes = $stat_defaults['stat_style'] ?? 'card';
      }
    }

    // Add stat class from options.
    if (!empty($item->options['class'])) {
      $stat_classes .= ' ' . $item->options['class'];
    }

    // Determine whether link should be required by default (based on current stat style).
    $link_required_default = !empty($stat_classes) && str_starts_with($stat_classes, 'card stat-bold-hover');

    // Create summary for details element (what shows when collapsed)
    $summary_text = '';
    if (!$item->isEmpty()) {
      $summary_parts = array_filter([
        $item->stat_heading,
        $item->stat_description,
        $item->stat_source ? $this->t('Source: @source', ['@source' => substr($item->stat_source, 0, 50) . '...']) : NULL,
      ]);
      $summary_text = implode(' | ', $summary_parts) ?: $this->t('Stat @delta', ['@delta' => $delta + 1]);
    } else {
      $summary_text = $this->t('New Stat @delta', ['@delta' => $delta + 1]);
    }

    // Wrap everything in a details element
    $element['details'] = [
      '#type' => 'details',
      '#title' => $summary_text,
      '#open' => $status, // Open when in edit mode, closed when in preview mode
      '#attributes' => ['class' => ['az-stat-widget']],
    ];

    // When closed, add a preview of the stat after the summary
    if (!$status) {
      $element['preview_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['widget-preview-wrapper'],
          'style' => 'max-width: 320px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;'
        ],
        '#weight' => -10, // Show before the details element
      ];

      $element['preview_wrapper']['preview'] = [
        '#theme' => 'az_stat',
        '#stat_heading' => $item->stat_heading ?? '',
        '#stat_description' => $item->stat_description ?? '',
        '#stat_source' => $item->stat_source ?? '',
        '#attributes' => [
          'class' => $stat_classes . ' widget-preview-stat',
          'style' => 'transform: scale(0.8); transform-origin: center;'
        ],
      ];

      // Add media to preview if available
      $media_id = $item->media ?? NULL;
      if (!empty($media_id)) {
        if ($media = $this->entityTypeManager->getStorage('media')->load($media_id)) {
          $media_render_array = $this->statImageHelper->generateImageRenderArray($media);
          if (!empty($media_render_array)) {
            $element['preview_wrapper']['preview']['#media'] = $media_render_array;
          }
        }
      }

      // Add link to preview if available
      if ($item->stat_source || $item->link_uri) {
        if (!empty($item->link_uri) && str_starts_with($item->link_uri, '/' . PublicStream::basePath())) {
          $link_url = Url::fromUri(urldecode('base:' . $item->link_uri));
        }
        else {
          $link_url = $this->pathValidator->getUrlIfValid($item->link_uri ?? '<none>');
        }
        $element['preview_wrapper']['preview']['#link'] = [
          '#type' => 'link',
          '#title' => $item->stat_source ?? '',
          '#url' => $link_url ?: Url::fromRoute('<none>'),
          '#attributes' => ['class' => ['btn', 'btn-default', 'w-100', 'az-stat-no-follow']],
        ];
      }
    }

    $stat_type_unique_id = Html::getUniqueId('az_stat_type_input');

    // Add all form fields inside the details element
    $element['details']['stat_type'] = [
      '#type' => 'select',
      '#options' => [
        'standard' => $this->t('Standard'),
        'image_only' => $this->t('Image Only'),
      ],
      '#title' => $this->t('Ranking Type'),
      '#default_value' => (!empty($item->options['stat_type'])) ? $item->options['stat_type'] : 'standard',
      '#attributes' => ['data-az-stat-type-input-id' => $stat_type_unique_id],
    ];

    // Get stat_width from parent config for help text calculation
    $stat_width = 'col-lg-3'; // Default value
    $parent = $item->getEntity();
    if ($parent instanceof ParagraphInterface) {
      $parent_config = $parent->getAllBehaviorSettings();
      if (!empty($parent_config['az_stats_paragraph_behavior']['stat_width'])) {
        $stat_width = $parent_config['az_stats_paragraph_behavior']['stat_width'];
      }
    }

    $element['details']['column_span'] = [
      '#type' => 'select',
      '#options' => [
        1 => $this->t('1 column'),
        2 => $this->t('2 columns'),
        3 => $this->t('3 columns'),
        4 => $this->t('4 columns'),
      ],
      '#title' => $this->t('Column Span'),
      '#description' => $this->t('How many columns do you want this image to span (in multiples of stat-card width)?') . '<br><br><div class="aspect-ratio-help" data-current-stat-width="' . $stat_width . '">' . $this->getAspectRatioHelpText($stat_width) . '</div>',
      '#default_value' => (!empty($item->options['column_span'])) ? $item->options['column_span'] : 2,
      '#states' => [
        'visible' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'image_only'],
        ],
      ],
      '#attributes' => [
        'data-stat-width-target' => 'true',
      ],
    ];

    $element['details']['media'] = [
      '#type' => 'az_media_library',
      '#default_value' => $item->media ?? NULL,
      '#allowed_bundles' => ['az_image'],
      '#delta' => $delta,
      '#cardinality' => 1,
      '#states' => [
        'visible' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'image_only'],
        ],
      ],
    ];

    $element['details']['options'] = [
      '#type' => 'select',
      '#options' => [
        'text-bg-white' => $this->t('White'),
        'bg-transparent' => $this->t('Transparent'),
        'text-bg-red' => $this->t('Arizona Red'),
        'text-bg-blue' => $this->t('Arizona Blue'),
        'text-bg-sky' => $this->t('Sky'),
        'text-bg-oasis' => $this->t('Oasis'),
        'text-bg-azurite' => $this->t('Azurite'),
        'text-bg-midnight' => $this->t('Midnight'),
        'text-bg-bloom' => $this->t('Bloom'),
        'text-bg-chili' => $this->t('Chili'),
        'text-bg-cool-gray' => $this->t('Cool Gray'),
        'text-bg-warm-gray' => $this->t('Warm Gray'),
        'text-bg-gray-100' => $this->t('Gray 100'),
        'text-bg-gray-200' => $this->t('Gray 200'),
        'text-bg-gray-300' => $this->t('Gray 300'),
        'text-bg-leaf' => $this->t('Leaf'),
        'text-bg-river' => $this->t('River'),
        'text-bg-silver' => $this->t('Silver'),
        'text-bg-ash' => $this->t('Ash'),
        'text-bg-mesa' => $this->t('Mesa'),
      ],
      '#required' => TRUE,
      '#title' => $this->t('Ranking Background'),
      '#default_value' => (!empty($item->options['class'])) ? $item->options['class'] : 'text-bg-white',
      '#states' => [
        'visible' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'standard'],
        ],
      ],
    ];

    $element['details']['stat_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ranking Heading'),
      '#default_value' => $item->stat_heading ?? NULL,
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'standard'],
        ],
      ],
    ];

    $element['details']['stat_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ranking Description'),
      '#default_value' => $item->stat_description ?? NULL,
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'standard'],
        ],
      ],
    ];

    $element['details']['stat_source'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ranking Source'),
      '#default_value' => $item->stat_source ?? NULL,
      '#rows' => 3,
      '#states' => [
        'visible' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'standard'],
        ],
      ],
    ];

    // Determine whether link should be required (based on current parent paragraph setting).
    // This is evaluated server-side on each form build, so it will work correctly with AJAX
    $stat_type = (!empty($item->options['stat_type'])) ? $item->options['stat_type'] : 'standard';
    $link_required = ($stat_type === 'standard') && !empty($stat_classes) && str_starts_with($stat_classes, 'card stat-bold-hover');
    
    $element['details']['link_uri'] = [
      '#type' => 'linkit',
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => 'az_linkit',
      ],
      '#title' => $this->t('Ranking Source Link URL'),
      '#element_validate' => [[$this, 'validateStatLink']],
      '#default_value' => $item->link_uri ?? NULL,
      '#maxlength' => 2048,
      '#required' => FALSE, // Don't use server-side required - let #states handle it dynamically
      '#states' => [
        // Visible only for standard stat type.
        'visible' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'standard'],
        ],
        // Required when BOTH visible (standard stat type) AND stat_style is 'card stat-bold-hover'
        'required' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'standard'],
          ':input[name*="[behavior_plugins][az_stats_paragraph_behavior][stat_style]"]' => ['value' => 'card stat-bold-hover'],
        ],
      ],
    ];

    // Attach the library and return the element
    $element['#attached']['library'][] = 'az_stat/az_stat';
    $element['#attached']['library'][] = 'az_stat/az_stat_dynamic_helptext';

      // Store delta and field name for reference
    $element['#delta'] = $delta;
    $element['#field_name'] = $field_name;

    // Pass aspect ratio data to JavaScript
    $element['#attached']['drupalSettings']['azStat']['aspectRatios'] = $this->getAspectRatioData();

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    // $is_multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    //    $is_unlimited_not_programmed = FALSE;
    $parents = $form['#parents'];

    $max = 0;
    // Determine the number of widgets.
    switch ($cardinality) {
      case FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED:
        $field_state = static::getWidgetState($parents, $field_name, $form_state);
        $max = $field_state['items_count'];
        $is_unlimited_not_programmed = !$form_state->isProgrammed();
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
        $elements[$delta]['stat_actions']['delete'] = $remove;
        // Attempt to style it like collapse button.
        $elements[$delta]['stat_actions']['delete']['#attributes']['class'][] = 'button--extrasmall';
        $elements[$delta]['stat_actions']['delete']['#attributes']['class'][] = 'ml-3';
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
  public function statSubmit(array $form, FormStateInterface $form_state) {

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
  public function statAjax(array &$form, FormStateInterface $form_state) {

    // Find the widget and return it.
    $element = [];
    $triggering_element = $form_state->getTriggeringElement();
    // $oops = $triggering_element['#array_parents'];
    $array_parents = array_slice($triggering_element['#array_parents'], 0, -3);
    $element = NestedArray::getValue($form, $array_parents);

    return $element;
  }

  /**
   * Get aspect ratio data array.
   *
   * @return array
   *   Array of aspect ratio data keyed by stat width.
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
   * Get aspect ratio help text based on stat_width and column_span.
   *
   * @param string $stat_width
   *   The stat width setting from parent paragraph behavior.
   *
   * @return string
   *   The help text with recommended aspect ratios.
   */
  protected function getAspectRatioHelpText($stat_width) {
    $aspect_ratios = $this->getAspectRatioData();

    if (!isset($aspect_ratios[$stat_width])) {
      // No help text for unknown stat_width
      return '';
    }

    $help_text = '<strong>' . $this->t('Recommended aspect ratios (W:H):') . '</strong><br>';
    $ratios = $aspect_ratios[$stat_width];
    $lines = [];

    foreach ($ratios as $key => $ratio) {
      if ($key === 'any') {
        $label = $this->t('Any column span');
      } else {
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
  public function validateStatLinkTitle(&$element, FormStateInterface $form_state, &$complete_form) {
    $parents = $element['#array_parents'];
    array_pop($parents);
    $parent_element = NestedArray::getValue($complete_form, $parents);
    if (empty($element['#value']) && !empty($parent_element['link_uri']['#value'])) {
      $form_state->setError($element, $this->t('Stat Link Title field is required when a URL is provided. Stat Link Title may be visually hidden with a Stat Link Style selection.'));
    }
  }

  /**
   * Form element validation handler for the 'link_url' field.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public function validateStatLink(&$element, FormStateInterface $form_state, &$complete_form) {

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
      // Extract values from the details element structure
      $details_values = $value['details'] ?? [];

      if (($details_values['stat_heading'] ?? '') === '') {
        $values[$delta]['stat_heading'] = NULL;
      } else {
        $values[$delta]['stat_heading'] = $details_values['stat_heading'] ?? NULL;
      }

      if (($details_values['stat_description'] ?? '') === '') {
        $values[$delta]['stat_description'] = NULL;
      } else {
        $values[$delta]['stat_description'] = $details_values['stat_description'] ?? NULL;
      }

      if (empty($details_values['media'])) {
        $values[$delta]['media'] = NULL;
      } else {
        $values[$delta]['media'] = $details_values['media'];
      }

      if (($details_values['stat_source'] ?? '') === '') {
        $values[$delta]['stat_source'] = NULL;
      } else {
        $values[$delta]['stat_source'] = $details_values['stat_source'] ?? NULL;
      }

      if (($details_values['link_uri'] ?? '') === '') {
        $values[$delta]['link_uri'] = NULL;
      } else {
        $values[$delta]['link_uri'] = $details_values['link_uri'] ?? NULL;
      }

      if (!empty($details_values['options']) || !empty($details_values['stat_type']) || !empty($details_values['column_span'])) {
        $values[$delta]['options'] = [
          'class' => $details_values['options'] ?? '',
          'stat_type' => $details_values['stat_type'] ?? '',
          'column_span' => $details_values['column_span'] ?? '',
        ];
      }

      // Remove the details wrapper from the final values
      unset($values[$delta]['details']);
    }
    return $values;
  }

}
