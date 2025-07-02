<?php

namespace Drupal\media_library_form_element\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library\MediaLibraryUiBuilder;

/**
 * Provides a Media library form element.
 *
 * The #default_value accepted by this element is an ID of a media object.
 *
 * @FormElement("media_library")
 *
 * Usage can include the following components:
 *
 *   $element['image'] = [
 *     '#type' => 'media_library',
 *     '#allowed_bundles' => ['image'],
 *     '#title' => t('Upload your image'),
 *     '#default_value' => NULL|'1'|'2,3,1',
 *     '#description' => t('Upload or select your profile image.'),
 *     '#cardinality' => -1|1,
 *   ];
 */
class MediaLibrary extends FormElement {

  /**
   * Expand the media_library_element into it's required sub-elements.
   *
   * @param array $element
   *   The base form element render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $complete_form
   *   The complete form render array.
   *
   * @return array
   *   The form element render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function processMediaLibrary(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $default_value = NULL;
    $referenced_entities = [];

    if (!empty($element['#value'])) {
      $default_value = $element['#value'];
    }

    if (!empty($default_value['media_selection_id'])) {
      $entity_ids = [$default_value['media_selection_id']];
    }
    else {
      $entity_ids = array_filter(explode(',', $default_value ?? ''));
    }

    if (!empty($entity_ids)) {
      foreach ($entity_ids as $entity_id) {
        $entity = \Drupal::entityTypeManager()
          ->getStorage('media')
          ->load($entity_id);
        // EntityStorageInterface::load can return null.
        // @see https://www.drupal.org/project/media_library_form_element/issues/3243411
        if ($entity instanceof MediaInterface) {
          $referenced_entities[] = $entity;
        }
      }
    }

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('media');

    $allowed_media_type_ids = $element['#allowed_bundles'];
    $parents = $element['#parents'];
    $field_name = array_pop($parents);
    $attributes = $element['#attributes'] ?? [];
    // Create an ID suffix from the parents to make sure each widget is unique.
    $id_suffix = $parents ? '-' . implode('-', $parents) : '';
    $field_widget_id = implode('', array_filter([$field_name, $id_suffix]));
    $wrapper_id = $field_name . '-media-library-wrapper' . $id_suffix;
    $limit_validation_errors = [array_merge($parents, [$field_name])];

    $element = array_merge(
      $element,
      [
        '#target_bundles' => !empty($allowed_media_type_ids) ? $allowed_media_type_ids : FALSE,
        '#cardinality' => $element['#cardinality'] ?? 1,
        '#attributes' => [
          'id' => $wrapper_id,
          'class' => ['media-library-form-element'],
        ],
        '#modal_selector' => '#modal-media-library',
        '#attached' => [
          'library' => [
            'media_library_form_element/media_library_form_element',
            'media_library/view',
          ],
        ],
      ]
    );

    if (empty($referenced_entities)) {
      $element['empty_selection'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => t('No media item selected.'),
        '#attributes' => [
          'class' => [
            'media-library-form-element-empty-text',
          ],
        ],
      ];
    }

    $element['selection'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'js-media-library-selection',
          'media-library-selection',
        ],
      ],
    ];

    foreach ($referenced_entities as $delta => $referenced_entity) {
      if ($referenced_entity->access('view')) {
        // @todo Make the view mode configurable in https://www.drupal.org/project/drupal/issues/2971209.
        $preview = $view_builder->view($referenced_entity, 'media_library');
      }
      else {
        $item_label = $referenced_entity->access('view label')
          ? $referenced_entity->label()
          : new FormattableMarkup('@label @id', [
            '@label' => $referenced_entity->getEntityType()->getSingularLabel(),
            '@id' => $referenced_entity->id(),
          ]);
        $preview = [
          '#theme' => 'media_embed_error',
          '#message' => t('You do not have permission to view @item_label.', [
            '@item_label' => $item_label,
          ]),
        ];
      }

      $remove_label = $referenced_entity->access('view label')
        ? t('Remove @label', ['@label' => $referenced_entity->label()])
        : t('Remove media');

      $element['selection'][$delta] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'media-library-item',
            'media-library-item--grid',
            'js-media-library-item',
          ],
          // Add the tabindex '-1' to allow the focus to be shifted to the next
          // media item when an item is removed. We set focus to the container
          // because we do not want to set focus to the remove button
          // automatically.
          // @see ::updateFormElement()
          'tabindex' => '-1',
          // Add a data attribute containing the delta to allow us to easily
          // shift the focus to a specific media item.
          // @see ::updateFormElement()
          'data-media-library-item-delta' => $delta,
        ],
        'preview' => [
          '#type' => 'container',
          'remove_button' => [
            '#type' => 'submit',
            '#name' => $field_name . '-' . $delta . '-media-library-remove-button' . $id_suffix,
            '#value' => t('Remove'),
            '#media_id' => $referenced_entity->id(),
            '#attributes' => [
              'class' => ['media-library-item__remove'],
              'aria-label' => $remove_label,
            ],
            '#ajax' => [
              'callback' => [static::class, 'updateFormElement'],
              'wrapper' => $wrapper_id,
              'progress' => [
                'type' => 'throbber',
                'message' => $remove_label,
              ],
            ],
            '#submit' => [[static::class, 'removeItem']],
            // Prevent errors in other widgets from preventing removal.
            '#limit_validation_errors' => $limit_validation_errors,
          ],
          'rendered_entity' => $preview,
          'target_id' => [
            '#type' => 'hidden',
            '#value' => $referenced_entity->id(),
          ],
        ],
        'weight' => [
          '#type' => 'number',
          '#theme' => 'input__number__media_library_item_weight',
          '#title' => t('Weight'),
          '#default_value' => $delta,
          '#attributes' => [
            'class' => [
              'js-media-library-item-weight',
            ],
          ],
        ],
      ];
    }

    $cardinality_unlimited = ($element['#cardinality'] === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $remaining = $element['#cardinality'] - count($referenced_entities);

    // Inform the user of how many items are remaining.
    if (!$cardinality_unlimited) {
      if ($remaining) {
        $cardinality_message = \Drupal::translation()
          ->formatPlural($remaining, 'One media item remaining.', '@count media items remaining.');
      }
      else {
        $cardinality_message = \Drupal::translation()
          ->translate('The maximum number of media items have been selected.');
      }

      // Add a line break between the field message and the cardinality message.
      if (!empty($element['#description'])) {
        $element['#description'] .= '<br />' . $cardinality_message;
      }
      else {
        $element['#description'] = $cardinality_message;
      }
    }

    // Create a new media library URL with the correct state parameters.
    $selected_type_id = reset($allowed_media_type_ids);
    $remaining = $cardinality_unlimited ? FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED : $remaining;
    // This particular media library opener needs some extra metadata for its
    // \Drupal\media_library\MediaLibraryOpenerInterface::getSelectionResponse()
    // to be able to target the element
    // whose 'data-media-library-form-element-value'
    // attribute is the same as $field_widget_id. The entity ID, entity type ID,
    // bundle, field name are used for access checking.
    $opener_parameters = [
      'field_widget_id' => $field_widget_id,
      'field_name' => $field_name,
    ];
    $state = MediaLibraryState::create('media_library.opener.form_element', $allowed_media_type_ids, $selected_type_id, $remaining, $opener_parameters);

    // Add a button that will load the Media library in a modal using AJAX.
    $element['media_library_open_button'] = [
      '#type' => 'button',
      '#value' => t('Add media'),
      '#name' => $field_name . '-media-library-open-button' . $id_suffix,
      '#attributes' => [
        'class' => [
          'media-library-open-button',
          'js-media-library-open-button',
        ],
        // The jQuery UI dialog automatically moves focus to the first :tabbable
        // element of the modal, so we need to disable refocus on the button.
        'data-disable-refocus' => 'true',
      ],
      '#media_library_state' => $state,
      '#ajax' => [
        'callback' => [static::class, 'openMediaLibrary'],
        'progress' => [
          'type' => 'throbber',
          'message' => t('Opening media library.'),
        ],
      ],
      // Allow the media library to be opened even if there are form errors.
      '#limit_validation_errors' => [],
    ];

    // When the user returns from the modal to the widget, we want to shift the
    // focus back to the open button. If the user is not allowed to add more
    // items, the button needs to be disabled. Since we can't shift the focus to
    // disabled elements, the focus is set back to the open button via
    // JavaScript by adding the 'data-disabled-focus' attribute.
    // @see Drupal.behaviors.MediaLibraryWidgetDisableButton
    if (!$cardinality_unlimited && $remaining === 0) {
      $element['media_library_open_button']['#attributes']['data-disabled-focus'] = 'true';
      $element['media_library_open_button']['#attributes']['class'][] = 'visually-hidden';
    }

    // This hidden field and button are used to add new items to the widget.
    $element['media_library_selection'] = [
      '#type' => 'hidden',
      '#attributes' => array_merge(['data-media-library-form-element-value' => $field_widget_id], $attributes),
      '#default_value' => $element['#value'],
    ];

    // When a selection is made this hidden button is pressed to add new media
    // items based on the "media_library_selection" value.
    $element['media_library_update_widget'] = [
      '#type' => 'submit',
      '#value' => t('Update widget'),
      '#name' => $field_name . '-media-library-update' . $id_suffix,
      '#ajax' => [
        'callback' => [static::class, 'updateFormElement'],
        'wrapper' => $wrapper_id,
        'progress' => [
          'type' => 'throbber',
          'message' => t('Adding selection.'),
        ],
      ],
      '#attributes' => [
        'data-media-library-form-element-update' => $field_widget_id,
        'class' => ['js-hide'],
      ],
      '#validate' => [[static::class, 'validateItem']],
      '#submit' => [[static::class, 'updateItem']],
      // Prevent errors in other widgets from preventing updates.
      // Exclude other validations in case there is no data yet.
      '#limit_validation_errors' => !empty($referenced_entities) ? $limit_validation_errors : [],
    ];

    return $element;
  }

  /**
   * Extract the proper portion of our default_value.
   *
   * @param array $element
   *   The render element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $complete_form
   *   The complete form render array.
   */
  public static function elementValidateMediaLibrary(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $value = NULL;

    if (!empty($element['#value'])) {
      $value = $element['#value'];

      if (isset($value['media_library_selection'])) {
        $value = $value['media_library_selection'];
      }
    }

    $form_state->setValueForElement($element, $value);
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $value = NULL;
    // Process the submission of our form element.
    if ($input !== FALSE && $input !== NULL && isset($input['media_library_selection'])) {
      $value = $input['media_library_selection'];
    }
    elseif ($input === FALSE) {
      if (!empty($element['#default_value'])) {
        // Remove the default value propery in case of AJAX removal.
        if ($form_state->isSubmitted() && end($form_state->getTriggeringElement()['#parents']) === 'remove_button') {
          $element['#default_value'] = NULL;
        }
        $value = $element['#default_value'];
      }
    }

    if (!empty($value)) {
      if (isset($value['target_id'])) {
        $value = $value['target_id'];
      }

      // Normalize 0 value.
      $value = ($value === 0) ? '' : $value;
    }

    return $value;
  }

  /**
   * AJAX callback to update the widget when the selection changes.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   An AJAX response to update the selection.
   */
  public static function updateFormElement(array $form, FormStateInterface $form_state): array {
    $triggering_element = $form_state->getTriggeringElement();

    // This callback is either invoked from the remove button or the update
    // button, which have different nesting levels.
    $is_remove_button = end($triggering_element['#parents']) === 'remove_button';
    $length = $is_remove_button ? -4 : -1;
    if (count($triggering_element['#array_parents']) < abs($length)) {
      throw new \LogicException(
        'The element that triggered the form element update was at an unexpected depth. Triggering element parents were: ' . implode(',', $triggering_element['#array_parents'])
      );
    }

    $parents = array_slice($triggering_element['#array_parents'], 0, $length);

    return NestedArray::getValue($form, $parents);
  }

  /**
   * Validates that newly selected items can be added to the widget.
   *
   * Making an invalid selection from the view should not be possible, but we
   * still validate in case other selection methods (ex: upload) are valid.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateItem(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));

    $media = static::getNewMediaItem($element, $form_state);

    if (empty($media)) {
      return;
    }

    // Validate that each selected media is of an allowed bundle.
    $all_bundles = \Drupal::service('entity_type.bundle.info')
      ->getBundleInfo('media');
    $bundle_labels = array_map(function ($bundle) use ($all_bundles) {
      return $all_bundles[$bundle]['label'];
    }, $element['#target_bundles']);

    if ($element['#target_bundles']) {
      // Validate the bundle of each selected media entity.
      foreach ($media as $media_entity) {
        if (!in_array($media_entity->bundle(), $element['#target_bundles'], TRUE)) {
          $form_state->setError(
            $element,
            t('The media item "@label" is not of an accepted type. Allowed types: @types', [
              '@label' => $media_entity->label(),
              '@types' => implode(', ', $bundle_labels),
            ])
          );
        }
      }
    }
  }

  /**
   * Gets newly selected media item(s).
   *
   * @param array $element
   *   The wrapping element for this widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An array with selected media item(s).
   */
  protected static function getNewMediaItem(array $element, FormStateInterface $form_state) {
    // Get the new media IDs passed to our hidden button.
    $values = $form_state->getValues();
    $path = $element['#parents'];
    $value = NestedArray::getValue($values, $path);

    if (!empty($value['media_library_selection'])) {
      $ids = explode(',', $value['media_library_selection']);
      $ids = array_filter($ids, 'is_numeric');
      if (!empty($ids)) {
        /** @var \Drupal\media\MediaInterface[] $media */
        return Media::loadMultiple($ids);
      }
    }

    return [];
  }

  /**
   * Flags the form for rebuild.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function updateItem(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Submit callback for remove buttons.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function removeItem(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    // Get the parents required to find the top-level widget element.
    if (count($triggering_element['#array_parents']) < 4) {
      throw new \LogicException(
        'Expected the remove button to be more than four levels deep in the form. Triggering element parents were: ' . implode(',', $triggering_element['#array_parents'])
      );
    }
    $parents = array_slice($triggering_element['#array_parents'], 0, -4);
    $element = NestedArray::getValue($form, $parents);
    $user_input_parents = array_slice($triggering_element['#parents'], 0, -4);

    $delta = array_slice($triggering_element['#array_parents'], -3, 1)[0];

    if (isset($element['selection'][$delta])) {
      unset($element['selection'][$delta]);
      $items = array_filter($element['selection'], function ($k) {
        return is_numeric($k);
      }, ARRAY_FILTER_USE_KEY);
      $remaining_items = [];

      foreach ($items as $item) {
        /** @var \Drupal\media\Entity\Media $media_item */
        $media_item = $item['preview']['rendered_entity']['#media'];
        if ($media_item instanceof MediaInterface) {
          $remaining_items[] = $media_item->id();
        }
      }

      $selection = implode(',', $remaining_items);

      // Remove our value.
      $element['media_library_selection']['#value'] = $selection;
      $element['media_library_selection']['#default_value'] = $selection;
      $element['#value'] = $selection;
      $element['#default_value'] = $selection;

      // Clear the formstate values.
      $form_state->setValueForElement($element, $selection);

      // Clear formstate user input.
      $user_input = $form_state->getUserInput();
      NestedArray::setValue($user_input, $user_input_parents, $selection);
      $form_state->setUserInput($user_input);
      $form_state->setValue($user_input_parents, $selection);
    }

    // Refresh the form element.
    $form_state->setRebuild();
  }

  /**
   * AJAX callback to open the library modal.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response to open the media library.
   */
  public static function openMediaLibrary(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $library_ui = \Drupal::service('media_library.ui_builder')
      ->buildUi($triggering_element['#media_library_state']);
    $dialog_options = MediaLibraryUiBuilder::dialogOptions();

    return (new AjaxResponse())->addCommand(new OpenModalDialogCommand($dialog_options['title'], $library_ui, $dialog_options, NULL, '#modal-media-library'));
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => TRUE,
      '#tree' => TRUE,
      '#cardinality' => 1,
      '#allowed_bundles' => [],
      '#process' => [
        [$class, 'processAjaxForm'],
        [$class, 'processMediaLibrary'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#element_validate' => [
        [$class, 'elementValidateMediaLibrary'],
      ],
      '#theme' => 'media_library_element',
    ];
  }

}
