<?php

namespace Drupal\az_card\Plugin\Field\FieldWidget;

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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Defines the 'az_card' field widget.
 */
#[FieldWidget(
  id: 'az_card',
  label: new TranslatableMarkup('Card'),
  field_types: ['az_card'],
)]
class AZCardWidget extends WidgetBase {

  // Default initial text format for cards.
  const AZ_CARD_DEFAULT_TEXT_FORMAT = 'az_standard';

  /**
   * The AZCardImageHelper service.
   *
   * @var \Drupal\az_card\AZCardImageHelper
   */
  protected $cardImageHelper;

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

    $instance->cardImageHelper = $container->get('az_card.image');
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
    $wrapper_id = Html::getUniqueId('az-card-wrapper');
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

    /** @var \Drupal\az_card\Plugin\Field\FieldType\AZCardItem $item */
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
            ['col-12', 'col-sm-6', 'col-md-6', 'col-lg-4', 'card-preview'],
        ],
      ];

      // Card item.
      $element['preview_container']['card_preview'] = [
        '#theme' => 'az_card',
        '#title' => $item->title ?? '',
        '#body' => check_markup(
          $item->body ?? '',
          $item->body_format ?? self::AZ_CARD_DEFAULT_TEXT_FORMAT),
        '#attributes' => ['class' => ['card']],
      ];

      // Add card class from options.
      if (!empty($item->options['class'])) {
        $element['preview_container']['card_preview']['#attributes']['class'][] = $item->options['class'];
      }

      // Check and see if we can construct a valid image to preview.
      $media_id = $item->media ?? NULL;
      if (!empty($media_id)) {
        if ($media = $this->entityTypeManager->getStorage('media')->load($media_id)) {
          $media_render_array = $this->cardImageHelper->generateImageRenderArray($media);
          if (!empty($media_render_array)) {
            $element['preview_container']['card_preview']['#media'] = $media_render_array;
          }
        }
      }

      // Check and see if there's a valid link to preview.
      if ($item->link_title || $item->link_uri) {
        if (str_starts_with($item->link_uri, '/' . PublicStream::basePath())) {
          // Link to public file: use fromUri() to get the URL.
          $link_url = Url::fromUri(urldecode('base:' . $item->link_uri));
        }
        else {
          $link_url = $this->pathValidator->getUrlIfValid($item->link_uri ?? '<none>');
        }
        $element['preview_container']['card_preview']['#link'] = [
          '#type' => 'link',
          '#title' => $item->link_title ?? '',
          '#url' => $link_url ?: Url::fromRoute('<none>'),
          '#attributes' => ['class' => ['btn', 'btn-default', 'w-100']],
        ];
      }
    }

    // Add link class from options.
    if (!empty($item->options['link_style'])) {
      $element['preview_container']['card_preview']['#link']['#attributes']['class'] = explode(' ', $item->options['link_style']);
    }

    if (!empty($element['preview_container']['card_preview']['#link'])) {
      $element['preview_container']['card_preview']['#link']['#attributes']['class'][] = 'az-card-no-follow';
    }

    $element['options'] = [
      '#type' => 'select',
      '#options' => [
        'bg-white' => $this->t('White'),
        'bg-transparent' => $this->t('Transparent'),
        'bg-red' => $this->t('Arizona Red'),
        'bg-blue' => $this->t('Arizona Blue'),
        'bg-sky' => $this->t('Sky'),
        'bg-oasis' => $this->t('Oasis'),
        'bg-azurite' => $this->t('Azurite'),
        'bg-midnight' => $this->t('Midnight'),
        'bg-bloom' => $this->t('Bloom'),
        'bg-chili' => $this->t('Chili'),
        'bg-cool-gray' => $this->t('Cool Gray'),
        'bg-warm-gray' => $this->t('Warm Gray'),
        'bg-gray-100' => $this->t('Gray 100'),
        'bg-gray-200' => $this->t('Gray 200'),
        'bg-gray-300' => $this->t('Gray 300'),
        'bg-leaf' => $this->t('Leaf'),
        'bg-river' => $this->t('River'),
        'bg-silver' => $this->t('Silver'),
        'bg-ash' => $this->t('Ash'),
        'bg-mesa' => $this->t('Mesa'),
      ],
      '#required' => TRUE,
      '#title' => $this->t('Card Background'),
      '#default_value' => (!empty($item->options['class'])) ? $item->options['class'] : 'bg-white',
    ];

    $element['media'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Card Media'),
      '#default_value' => $item->media ?? NULL,
      '#allowed_bundles' => ['az_image'],
      '#delta' => $delta,
      '#cardinality' => 1,
    ];

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Title'),
      '#default_value' => $item->title ?? NULL,
      '#maxlength' => 255,
    ];

    $element['title_alignment'] = [
      '#type' => 'select',
      '#options' => [
        'text-left' => $this->t('Title left'),
        'text-center' => $this->t('Title center'),
        'text-right' => $this->t('Title right'),
      ],
      '#title' => $this->t('Card Title Alignment'),
      '#default_value' => (!empty($item->options['title_alignment'])) ? $item->options['title_alignment'] : 'text-left',
    ];

    $element['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Card Body'),
      '#default_value' => $item->body ?? NULL,
      '#format' => $item->body_format ?? self::AZ_CARD_DEFAULT_TEXT_FORMAT,
    ];

    $element['link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Link Title'),
      '#element_validate' => [[$this, 'validateCardLinkTitle']],
      '#default_value' => $item->link_title ?? NULL,
      '#description' => $this->t('<p>Make each link title unique for <a href="https://www.w3.org/WAI/WCAG21/Understanding/link-purpose-in-context.html">best accessibility</a> of this content. Use the pattern <em>"verb" "noun"</em> to create helpful links. For example, "Explore Undergraduate Programs".</p><p>This field is required when a Card Link URL is provided. Card Link Title may be visually hidden with Card Link Style selection.</p>'),
    ];

    $element['link_uri'] = [
      '#type' => 'linkit',
      '#autocomplete_route_name' => 'linkit.autocomplete',
      '#autocomplete_route_parameters' => [
        'linkit_profile_id' => 'az_linkit',
      ],
      '#title' => $this->t('Card Link URL'),
      '#element_validate' => [[$this, 'validateCardLink']],
      '#default_value' => $item->link_uri ?? NULL,
      '#maxlength' => 2048,
    ];

    // Add client side validation for link title if not collapsed.
    if ($status) {
      $link_uri_unique_id = Html::getUniqueId('az_card_link_uri_input');
      $element['link_uri']['#attributes']['data-az-card-link-uri-input-id'] = $link_uri_unique_id;
      $element['link_title']['#states'] = [
        'required' => [
          ':input[data-az-card-link-uri-input-id="' . $link_uri_unique_id . '"]' => ['filled' => TRUE],
        ],
      ];
    }

    $element['link_style'] = [
      '#type' => 'select',
      '#options' => [
        'sr-only' => $this->t('Hidden link title'),
        'btn-block' => $this->t('Text link'),
        'btn btn-block btn-red' => $this->t('Red button'),
        'btn btn-block btn-blue' => $this->t('Blue button'),
        'btn btn-block btn-outline-red' => $this->t('Red outline button'),
        'btn btn-block btn-outline-blue' => $this->t('Blue outline button'),
        'btn btn-block btn-outline-white' => $this->t('White outline button'),
      ],
      '#title' => $this->t('Card Link Style'),
      '#default_value' => (!empty($item->options['link_style'])) ? $item->options['link_style'] : 'btn-block',
    ];

    if (!$item->isEmpty()) {
      $button_name = implode('-', array_merge(
        $field_parents,
        [$field_name, $delta, 'toggle']
      ));
      // Extra card_actions wrapper needed for core delete ajax submit nesting.
      $element['card_actions']['toggle'] = [
        '#type' => 'submit',
        '#limit_validation_errors' => [],
        '#attributes' => ['class' => ['button--extrasmall', 'ml-3']],
        '#submit' => [[$this, 'cardSubmit']],
        '#value' => ($status ? $this->t('Collapse Card') : $this->t('Edit Card')),
        '#name' => $button_name,
        '#ajax' => [
          'callback' => [$this, 'cardAjax'],
          'wrapper' => $wrapper,
        ],
      ];
    }

    $element['#theme_wrappers'] = ['container', 'form_element'];
    $element['#attributes']['class'][] = 'az-card-elements';
    $element['#attributes']['class'][] = $status ? 'az-card-elements-open' : 'az-card-elements-closed';
    $element['#attached']['library'][] = 'az_card/az_card';

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
        $elements[$delta]['card_actions']['delete'] = $remove;
        // Attempt to style it like collapse button.
        $elements[$delta]['card_actions']['delete']['#attributes']['class'][] = 'button--extrasmall';
        $elements[$delta]['card_actions']['delete']['#attributes']['class'][] = 'ml-3';
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
  public function cardSubmit(array $form, FormStateInterface $form_state) {

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
  public function cardAjax(array &$form, FormStateInterface $form_state) {

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
  public function validateCardLinkTitle(&$element, FormStateInterface $form_state, &$complete_form) {
    $parents = $element['#array_parents'];
    array_pop($parents);
    $parent_element = NestedArray::getValue($complete_form, $parents);
    if (empty($element['#value']) && !empty($parent_element['link_uri']['#value'])) {
      $form_state->setError($element, t('Card link title is required when a URL is provided.'));
    }
  }

  /**
   * Form element validation handler for the 'link_url' field.
   *
   * Disallows saving inaccessible or untrusted URLs.
   */
  public function validateCardLink(&$element, FormStateInterface $form_state, &$complete_form) {

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
      if ($value['title'] === '') {
        $values[$delta]['title'] = NULL;
      }
      if ($value['body'] === '') {
        $values[$delta]['body'] = NULL;
      }
      if (empty($value['media'])) {
        $values[$delta]['media'] = NULL;
      }
      if ($value['link_title'] === '') {
        $values[$delta]['link_title'] = NULL;
      }
      if ($value['link_uri'] === '') {
        $values[$delta]['link_uri'] = NULL;
      }
      if (!empty($value['options']) || !empty($value['link_style'])) {
        $values[$delta]['options'] = [
          'class' => $value['options'],
          'link_style' => $value['link_style'],
          'title_alignment' => $value['title_alignment'],
        ];
      }
      $values[$delta]['body'] = $value['body']['value'];
      $values[$delta]['body_format'] = $value['body']['format'];
    }
    return $values;
  }

}
