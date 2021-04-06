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

  /**
   * Drupal\Core\Image\ImageFactory definition.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

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

    $instance->imageFactory = ($container->get('image.factory'));
    $instance->pathValidator = ($container->get('path.validator'));
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {

    // Create shared settings for widget elements.
    // This is necessary because wigets have to be AJAX replaced together,
    // And in general we need a place to store shared settings.
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
    // New field values shouldn't be considered collapsed.
    if ($items[$delta]->isEmpty()) {
      $status = TRUE;
    }

    // Generate a preview if we need one.
    if (!$status) {

      // Bootstrap wrapper.
      $element['preview_container'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['col-12', 'col-sm-6', 'col-md-6', 'col-lg-4', 'card-preview',
          ],
        ],
      ];

      // Card item.
      $element['preview_container']['card_preview'] = [
        '#theme' => 'az_card',
        '#title' => $items[$delta]->title ?? '',
        '#body' => check_markup(
          $items[$delta]->body ?? '',
          $items[$delta]->body_format ?? 'basic_html'),
        '#attributes' => ['class' => ['card']],
      ];
      // Add card class from options.
      if (!empty($items[$delta]->options['class'])) {
        $element['preview_container']['card_preview']['#attributes']['class'][] = $items[$delta]->options['class'];
      }

      // Check and see if we can construct a valid image to preview.
      $media_hint = $items[$delta]->media ?? NULL;
      if ($media_hint) {
        $file = $this->entityTypeManager->getStorage('file')->load($media_hint);
        if ($file) {
          $image = $this->imageFactory->get($file->getFileUri());
          if ($image && $image->isValid()) {
            $element['preview_container']['card_preview']['#media'] = [
              '#theme' => 'image_style',
              '#style_name' => 'az_card_image',
              '#uri' => $file->getFileUri(),
              '#attributes' => [
                'class' => ['card-img-top'],
              ],
            ];
          }
        }
      }

      // Check and see if there's a valid link to preview.
      if ($items[$delta]->link_title || $items[$delta]->link_uri) {
        $link_url = $this->pathValidator->getUrlIfValid($items[$delta]->link_uri);
        $element['preview_container']['card_preview']['#link'] = [
          '#type' => 'link',
          '#title' => $items[$delta]->link_title ?? '',
          '#url' => $link_url ? $link_url : '#',
          '#attributes' => ['class' => ['btn', 'btn-default', 'w-100']],
        ];
      }
    }

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Title'),
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
    ];

    $element['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Card Body'),
      '#default_value' => isset($items[$delta]->body) ? $items[$delta]->body : NULL,
      '#format' => $items[$delta]->body_format ?? 'basic_html',
    ];

    $element['media'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Card Media'),
      '#default_value' => isset($items[$delta]->media) ? $items[$delta]->media : NULL,
      '#allowed_bundles' => ['az_image'],
      '#delta' => $delta,
      '#cardinality' => 1,
    ];

    $element['link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Link Title'),
      '#default_value' => isset($items[$delta]->link_title) ? $items[$delta]->link_title : NULL,
    ];

    $element['link_uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Card Link URI'),
      '#default_value' => isset($items[$delta]->link_uri) ? $items[$delta]->link_uri : NULL,
    ];

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
        'bg-leaf' => $this->t('Leaf'),
        'bg-river' => $this->t('River'),
        'bg-silver' => $this->t('Silver'),
        'bg-ash' => $this->t('Ash'),
      ],
      '#required' => TRUE,
      '#title' => $this->t('Card Background'),
      '#default_value' => (!empty($items[$delta]->options['class'])) ? $items[$delta]->options['class'] : 'bg-white',
    ];

    if (!$items[$delta]->isEmpty()) {
      $button_name = implode('-', array_merge($field_parents,
        [$field_name, $delta, 'toggle']
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
      if (!empty($value['options'])) {
        $values[$delta]['options'] = ['class' => $value['options']];
      }
      $values[$delta]['body'] = $value['body']['value'];
      $values[$delta]['body_format'] = $value['body']['format'];
    }
    return $values;
  }

}
