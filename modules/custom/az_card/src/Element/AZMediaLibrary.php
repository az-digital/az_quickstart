<?php

namespace Drupal\az_card\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\media_library\MediaLibraryState;
use Drupal\media_library_form_element\Element\MediaLibrary;

/**
 * Provides an AZ Media library form element.
 *
 * The #default_value accepted by this element is an ID of a media object.
 *
 * @FormElement("az_media_library")
 *
 * Usage can include the following components:
 *
 *   $element['image'] = [
 *     '#type' => 'az_media_library',
 *     '#allowed_bundles' => ['image'],
 *     '#title' => t('Upload your image'),
 *     '#default_value' => NULL|'1'|'2,3,1',
 *     '#description' => t('Upload or select your profile image.'),
 *     '#cardinality' => -1|1,
 *   ];
 */
class AZMediaLibrary extends MediaLibrary {

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
              'class' => ['media-library-item__remove', 'icon-link'],
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

      $media = Media::load($referenced_entity->id());

      if ($media && $media->access('update') && $edit_template = $media->getEntityType()->getLinkTemplate('edit-form')) {
        $element['#attributes']['class'][] = 'js-media-library-edit-' . $wrapper_id . '-wrapper';
        $edit_url_query_params = [
          'media_library_edit' => 'ajax',
        ];
        $edit_url = Url::fromUserInput(str_replace('{media}', $referenced_entity->id(), $edit_template) . '?' . UrlHelper::buildQuery($edit_url_query_params));
        $element['selection'][$delta]['preview']['media_edit'] = [
          '#type' => 'link',
          '#title' => new FormattableMarkup('<span class="visually-hidden">@link_text</span>', [
            '@link_text' => t('Edit media item'),
          ]),
          '#url' => $edit_url,
          '#attributes' => [
            'class' => [
              'js-media-library-edit-link',
              'media-library-edit__link',
              'use-ajax',
            ],
            'target' => '_blank',
            'data-dialog-type' => 'modal',
          ],
          '#attached' => [
            'library' => [
              'media_library_edit/admin',
              'core/drupal.dialog.ajax',
            ],
          ],
        ];
      }
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

}
