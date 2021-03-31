<?php

namespace Drupal\az_card\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\file\Entity\File;

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

  /**
   * Drupal\Core\Image\ImageFactory definition.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

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

    $instance->imageFactory = ($container->get('image.factory'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $wrapper_id = Html::getUniqueId('az-card-wrapper');
    $field_name = $this->fieldDefinition->getName();
    $field_parents = $form['#parents'];
    $field_state = static::getWidgetState($field_parents, $field_name, $form_state);
    $field_state['ajax_wrapper_id'] = $wrapper_id;
    $field_state['items_count'] = count($items);
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

    // Get current collapse status.
    $field_name = $this->fieldDefinition->getName();
    $field_parents = $element['#field_parents'];
    $widget_state = static::getWidgetState($field_parents, $field_name, $form_state);
    $wrapper = $widget_state['ajax_wrapper_id'];
    $status = (isset($widget_state['open_status'][$delta])) ? $widget_state['open_status'][$delta] : FALSE;
    if ($items[$delta]->isEmpty()) {
      $status = TRUE;
    }

    $title_hint = isset($items[$delta]->title) ? $items[$delta]->title : ('Card ' . ($delta + 1));

    if (!$status) {
      $element['title_hint'] = [
        '#markup' => ('<h5 class="mb-0">' . $title_hint . '</h5>'),
      ];
    }

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Title'),
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
      '#access' => $status,
    ];

    $element['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Card Body'),
      '#default_value' => isset($items[$delta]->body) ? $items[$delta]->body : NULL,
      '#format' => $items[$delta]->body_format ?? 'basic_html',
      '#access' => $status,
    ];

    $element['media'] = [
      '#type' => 'media_library',
      '#default_value' => isset($items[$delta]->media) ? $items[$delta]->media : NULL,
      '#allowed_bundles' => ['az_image'],
      '#delta' => $delta,
      '#cardinality' => 1,
    ];
    if ($status) {
      // Only show the title in open mode due to its size.
      $element['media']['#title'] = $this->t('Card Media');
    }

    $element['link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Link Title'),
      '#default_value' => isset($items[$delta]->link_title) ? $items[$delta]->link_title : NULL,
      '#access' => $status,
    ];

    $element['link_uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Card Link URI'),
      '#default_value' => isset($items[$delta]->link_uri) ? $items[$delta]->link_uri : NULL,
      '#access' => $status,
    ];

    // TODO: card style(s) selection form.
    $element['options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Card Options'),
      '#default_value' => isset($items[$delta]->options) ? $items[$delta]->options : NULL,
      // Hide element until implemented.
      '#access' => FALSE,
    ];

    if (!$items[$delta]->isEmpty()) {
      $button_name = implode('-', array_merge($field_parents,
        [$field_name, $delta, 'toggle']
      ));
      $element['toggle'] = [
        '#type' => 'submit',
        '#limit_validation_errors' => [],
        '#attributes' => ['class' => ['button--extrasmall']],
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
    $element['#attached']['library'][] = 'az_card/az_card';

    return $element;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function cardAjax(array $form, FormStateInterface $form_state) {

    // Find the widget and return it.
    $element = [];
    $triggering_element = $form_state->getTriggeringElement();
    $oops = $triggering_element['#array_parents'];
    $array_parents = array_slice($triggering_element['#array_parents'], 0, -2);
    $element = NestedArray::getValue($form, $array_parents);

    return $element;
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
      if ($value['options'] === '') {
        $values[$delta]['options'] = NULL;
      }
      $values[$delta]['body'] = $value['body']['value'];
      $values[$delta]['body_format'] = $value['body']['format'];
    }
    return $values;
  }

}
