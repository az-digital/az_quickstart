<?php

namespace Drupal\az_card\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;

/**
 * Defines the 'az_card' field widget.
 *
 * @FieldWidget(
 *   id = "az_card",
 *   label = @Translation("Card"),
 *   field_types = {
 *     "az_card"
 *   }
 * )
 */
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
    $container['widget']['#prefix'] = '<div id="' . $wrapper_id . '">';
    $container['widget']['#suffix'] = '</div>';

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
        $link_url = $this->pathValidator->getUrlIfValid($item->link_uri);
        $element['preview_container']['card_preview']['#link'] = [
          '#type' => 'link',
          '#title' => $item->link_title ?? '',
          '#url' => $link_url ? $link_url : '#',
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

    $element['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Card Body'),
      '#default_value' => $item->body ?? NULL,
      '#format' => $item->body_format ?? self::AZ_CARD_DEFAULT_TEXT_FORMAT,
    ];

    $element['link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Link Title'),
      '#default_value' => $item->link_title ?? NULL,
    ];

    $element['link_uri'] = [
      // Url FAPI element does not support internal paths.
      '#type' => 'textfield',
      '#title' => $this->t('Card Link URL'),
      '#element_validate' => [[$this, 'validateCardLink']],
      '#default_value' => $item->link_uri ?? NULL,
      '#maxlength' => 2048,
    ];

    $element['link_style'] = [
      '#type' => 'select',
      '#options' => [
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
      $button_name = implode('-', array_merge($field_parents,
        [$field_name, $delta, 'toggle']
      ));
      $remove_name = implode('-', array_merge($field_parents,
        [$field_name, $delta, 'remove']
      ));
      $element['toggle'] = [
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

      if (!empty($widget_state['items_count']) && ($widget_state['items_count'] > 1)) {
        $element['remove'] = [
          '#name' => $remove_name,
          '#delta' => $delta,
          '#type' => 'submit',
          '#value' => $this->t('Delete Card'),
          '#validate' => [],
          '#submit' => [[$this, 'cardRemove']],
          '#limit_validation_errors' => [],
          '#attributes' => ['class' => ['button--extrasmall', 'ml-3']],
          '#ajax' => [
            'callback' => [$this, 'cardAjax'],
            'wrapper' => $wrapper,
          ],
        ];
      }
    }

    $element['#theme_wrappers'] = ['container', 'form_element'];
    $element['#attributes']['class'][] = 'az-card-elements';
    $element['#attributes']['class'][] = $status ? 'az-card-elements-open' : 'az-card-elements-closed';
    $element['#attached']['library'][] = 'az_card/az_card';

    return $element;
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
    $array_parents = $triggering_element['#array_parents'];
    array_pop($array_parents);

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
   * Submit handler for remove button. See multiple_fields_remove_button module.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function cardRemove(array $form, FormStateInterface $form_state) {
    $formValues = $form_state->getValues();
    $formInputs = $form_state->getUserInput();
    $button = $form_state->getTriggeringElement();
    $delta = $button['#delta'];
    // Where in the form we'll find the parent element.
    $address = array_slice($button['#array_parents'], 0, -2);

    // Go one level up in the form, to the widgets container.
    $parent_element = NestedArray::getValue($form, $address);
    $field_name = $parent_element['#field_name'];
    $parents = $parent_element['#field_parents'];
    $field_state = WidgetBase::getWidgetState($parents, $field_name, $form_state);

    // Go ahead and renumber everything from our delta to the last
    // item down one. This will overwrite the item being removed.
    for ($i = $delta; $i <= $field_state['items_count']; $i++) {
      $old_element_address = array_merge($address, [$i + 1]);
      $new_element_address = array_merge($address, [$i]);

      $moving_element = NestedArray::getValue($form, $old_element_address);
      $keys = array_keys($old_element_address, 'widget', TRUE);
      foreach ($keys as $key) {
        unset($old_element_address[$key]);
      }
      $moving_element_value = NestedArray::getValue($formValues, $old_element_address);
      $moving_element_input = NestedArray::getValue($formInputs, $old_element_address);

      $keys = array_keys($new_element_address, 'widget', TRUE);
      foreach ($keys as $key) {
        unset($new_element_address[$key]);
      }
      // Tell the element where it's being moved to.
      $moving_element['#parents'] = $new_element_address;

      // Delete default value for the last deleted element.
      if ($field_state['items_count'] === 0) {
        $struct_key = NestedArray::getValue($formInputs, $new_element_address);
        if (is_null($moving_element_value)) {
          foreach ($struct_key as &$key) {
            $key = '';
          }
          $moving_element_value = $struct_key;
        }
        if (is_null($moving_element_input)) {
          $moving_element_input = $moving_element_value;
        }
      }

      // Move the element around.
      NestedArray::setValue($formValues, $moving_element['#parents'], $moving_element_value, TRUE);
      NestedArray::setValue($formInputs, $moving_element['#parents'], $moving_element_input);

      // Save new element values.
      foreach ($formValues as $key => $value) {
        $form_state->setValue($key, $value);
      }
      $form_state->setUserInput($formInputs);

      // Move the entity in our saved state.
      if (isset($field_state['original_deltas'][$i + 1])) {
        $field_state['original_deltas'][$i] = $field_state['original_deltas'][$i + 1];
      }
      else {
        unset($field_state['original_deltas'][$i]);
      }
    }

    // Replace the deleted entity with an empty one. This helps to ensure that
    // trying to add a new entity won't resurrect a deleted entity
    // from the trash bin.
    // $count = count($field_state['entity']);
    // Then remove the last item. But we must not go negative.
    if ($field_state['items_count'] > 0) {
      $field_state['items_count']--;
    }

    // Fix the weights. Field UI lets the weights be in a range of
    // (-1 * item_count) to (item_count). This means that when we remove one,
    // the range shrinks; weights outside of that range then get set to
    // the first item in the select by the browser, floating them to the top.
    // We use a brute force method because we lost weights on both ends
    // and if the user has moved things around, we have to cascade because
    // if I have items weight weights 3 and 4, and I change 4 to 3 but leave
    // the 3, the order of the two 3s now is undefined and may not match what
    // the user had selected.
    $address = array_slice($button['#array_parents'], 0, -2);
    $keys = array_keys($address, 'widget', TRUE);
    foreach ($keys as $key) {
      unset($address[$key]);
    }
    $input = NestedArray::getValue($formInputs, $address);

    if ($input && is_array($input)) {
      // Sort by weight.
      // phpcs:ignore
      uasort($input, '_field_multiple_value_form_sort_helper');

      // Reweight everything in the correct order.
      $weight = -1 * $field_state['items_count'];
      foreach ($input as $key => $item) {
        if ($item) {
          $input[$key]['_weight'] = $weight++;
        }
      }
      NestedArray::setValue($formInputs, $address, $input);
      $form_state->setUserInput($formInputs);
    }

    $element_id = $form[$field_name]['#id'] ?? '';
    if (!$element_id) {
      $element_id = $parent_element['#id'];
    }
    $field_state['wrapper_id'] = $element_id;
    WidgetBase::setWidgetState($parents, $field_name, $form_state, $field_state);

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
    $array_parents = array_slice($triggering_element['#array_parents'], 0, -2);
    $element = NestedArray::getValue($form, $array_parents);

    return $element;
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
        ];
      }
      $values[$delta]['body'] = $value['body']['value'];
      $values[$delta]['body_format'] = $value['body']['format'];
    }
    return $values;
  }

}
