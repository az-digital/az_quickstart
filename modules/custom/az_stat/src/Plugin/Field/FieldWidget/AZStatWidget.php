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

    // Generate a preview if we need one.
    if (!$status) {

      // Bootstrap wrapper.
      $element['preview_container'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' =>
            ['stat-preview'],
        ],
      ];

      $stat_classes = 'stat';
      $parent = $item->getEntity();

      // Get settings from parent paragraph.
      if ($parent instanceof ParagraphInterface) {
        // Get the behavior settings for the parent.
        $parent_config = $parent->getAllBehaviorSettings();

        // See if the parent behavior defines some stat-specific settings.
        if (!empty($parent_config['az_stats_paragraph_behavior'])) {
          $stat_defaults = $parent_config['az_stats_paragraph_behavior'];
          $stat_classes = $stat_defaults['stat_style'] ?? 'stat';
        }
      }

      // Add stat class from options.
      if (!empty($item->options['class'])) {
        $stat_classes .= ' ' . $item->options['class'];
      }

      // Add fields to the preview.
      $element['preview_container']['stat_preview'] = [
        '#theme' => 'az_stat',
        '#stat_heading' => $item->stat_heading ?? '',
        '#stat_description' => $item->stat_description ?? '',
        '#stat_source' => $item->stat_source ?? '',
        '#attributes' => ['class' => $stat_classes],
      ];

      // Check and see if we can construct a valid image to preview.
      $media_id = $item->media ?? NULL;
      if (!empty($media_id)) {
        if ($media = $this->entityTypeManager->getStorage('media')->load($media_id)) {
          $media_render_array = $this->statImageHelper->generateImageRenderArray($media);
          if (!empty($media_render_array)) {
            $element['preview_container']['stat_preview']['#media'] = $media_render_array;
          }
        }
      }

      // Check and see if there's a valid link to preview.
      if ($item->stat_source || $item->link_uri) {
//      if ($item->link_uri) {
        if (!empty($item->link_uri) && str_starts_with($item->link_uri, '/' . PublicStream::basePath())) {
          // Link to public file: use fromUri() to get the URL.
          $link_url = Url::fromUri(urldecode('base:' . $item->link_uri));
        }
        else {
          $link_url = $this->pathValidator->getUrlIfValid($item->link_uri ?? '<none>');
        }
        $element['preview_container']['stat_preview']['#link'] = [
          '#type' => 'link',
          '#title' => $item->stat_source ?? '',
          '#url' => $link_url ?: Url::fromRoute('<none>'),
          '#attributes' => ['class' => ['btn', 'btn-default', 'w-100']],
        ];
      }
    }

    // Add link class from options.
    if (!empty($item->options['link_style'])) {
      $element['preview_container']['stat_preview']['#link']['#attributes']['class'] = explode(' ', $item->options['link_style']);
    }

    if (!empty($element['preview_container']['stat_preview']['#link'])) {
      $element['preview_container']['stat_preview']['#link']['#attributes']['class'][] = 'az-stat-no-follow';
    }

    $stat_type_unique_id = Html::getUniqueId('az_stat_type_input');

    $element['stat_type'] = [
      '#type' => 'select',
      '#options' => [
        'standard' => $this->t('Standard'),
        'image_only' => $this->t('Image Only'),
      ],
      '#title' => $this->t('Stat Type'),
      '#default_value' => (!empty($item->options['stat_type'])) ? $item->options['stat_type'] : 'standard',
      '#attributes' => ['data-az-stat-type-input-id' => $stat_type_unique_id],
    ];

    $element['media'] = [
      '#type' => 'az_media_library',
      '#title' => $this->t('Stat Media'),
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
    
    $element['column_span'] = [
      '#type' => 'select',
      '#options' => [
        1 => $this->t('1 Stat Card Width'),
        2 => $this->t('2 Stat Card Width'),
        3 => $this->t('3 Stat Card Width'),
        4 => $this->t('4 Stat Card Width'),
      ],
      '#title' => $this->t('Column Span'),
      '#description' => $this->t('How many columns do you want this image to span (in multiples of card width)?'),
      '#default_value' => (!empty($item->options['column_span'])) ? $item->options['column_span'] : 2,
      '#states' => [
        'visible' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'image_only'],
        ],
      ],
    ];

    $element['options'] = [
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
      '#title' => $this->t('Stat Background'),
      '#default_value' => (!empty($item->options['class'])) ? $item->options['class'] : 'text-bg-white',
      '#states' => [
        'visible' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'standard'],
        ],
      ],
    ];

    $element['stat_heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stat Heading'),
      '#default_value' => $item->stat_heading ?? NULL,
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'standard'],
        ],
      ],
    ];

    $element['stat_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stat Description'),
      '#default_value' => $item->stat_description ?? NULL,
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'standard'],
        ],
      ],
    ];

    $element['stat_source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stat Source'),
      '#default_value' => $item->stat_source ?? NULL,
      '#maxlength' => 255,
      '#states' => [
        'visible' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'standard'],
        ],
      ],
    ];
   
    $element['link_uri'] = [
      '#type' => 'linkit',
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => 'az_linkit',
      ],
      '#title' => $this->t('Stat Source Link URL'),
      '#element_validate' => [[$this, 'validateStatLink']],
      '#default_value' => $item->link_uri ?? NULL,
      '#maxlength' => 2048,
      '#states' => [
        'visible' => [
          ':input[data-az-stat-type-input-id="' . $stat_type_unique_id . '"]' => ['value' => 'standard'],
        ],
      ],
    ];

    if (!$item->isEmpty()) {
      $button_name = implode('-', array_merge(
        $field_parents,
        [$field_name, $delta, 'toggle']
      ));
      // Extra stat_actions wrapper needed for core delete ajax submit nesting.
      $element['stat_actions']['toggle'] = [
        '#type' => 'submit',
        '#limit_validation_errors' => [],
        '#attributes' => ['class' => ['button--extrasmall', 'ml-3']],
        '#submit' => [[$this, 'statSubmit']],
        '#value' => ($status ? $this->t('Collapse Stat') : $this->t('Edit Stat')),
        '#name' => $button_name,
        '#ajax' => [
          'callback' => [$this, 'statAjax'],
          'wrapper' => $wrapper,
        ],
      ];
    }

    $element['#theme_wrappers'] = ['container', 'form_element'];
    $element['#attributes']['class'][] = 'az-stat-elements';
    $element['#attributes']['class'][] = $status ? 'az-stat-elements-open' : 'az-stat-elements-closed';
    $element['#attached']['library'][] = 'az_stat/az_stat';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);
    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $is_multiple = $this->fieldDefinition->getFieldStorageDefinition()->isMultiple();
    $is_unlimited_not_programmed = FALSE;
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
    $oops = $triggering_element['#array_parents'];
    $array_parents = array_slice($triggering_element['#array_parents'], 0, -3);
    $element = NestedArray::getValue($form, $array_parents);

    return $element;
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
      $form_state->setError($element, t('Stat Link Title field is required when a URL is provided. Stat Link Title may be visually hidden with a Stat Link Style selection.'));
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
      if ($url = $this->pathValidator->getUrlIfValid($element['#value'])) {
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
        ->setError($element, t('This link does not exist or you do not have permission to link to %path.', [
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
      if ($value['stat_heading'] === '') {
        $values[$delta]['stat_heading'] = NULL;
      }
      if ($value['stat_description'] === '') {
        $values[$delta]['stat_description'] = NULL;
      }
      if (empty($value['media'])) {
        $values[$delta]['media'] = NULL;
      }
      if ($value['stat_source'] === '') {
        $values[$delta]['stat_source'] = NULL;
      }
      if ($value['link_uri'] === '') {
        $values[$delta]['link_uri'] = NULL;
      }
      if (!empty($value['options']) || !empty($value['link_style']) || !empty($value['stat_alignment']) || !empty($value['stat_type']) || !empty($value['column_span'])) {
        $values[$delta]['options'] = [
          'class' => $value['options'],
          'link_style' => $value['link_style'],
          'stat_alignment' => $value['stat_alignment'],
          'stat_type' => $value['stat_type'],
          'column_span' => $value['column_span'],
        ];
      }
//      $values[$delta]['body'] = $value['body']['value'];
    }
    return $values;
  }

}
