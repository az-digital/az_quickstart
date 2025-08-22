<?php

namespace Drupal\az_card\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Attribute\FormElement;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\media_library_form_element\Element\MediaLibrary;

/**
 * Provides an AZ Media library form element.
 *
 * The #default_value accepted by this element is an ID of a media object.
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
#[FormElement('az_media_library')]
class AZMediaLibrary extends MediaLibrary {

  /**
   * {@inheritdoc}
   */
  public static function processMediaLibrary(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    $element = parent::processMediaLibrary($element, $form_state, $complete_form);
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

    $parents = $element['#parents'];
    $field_name = array_pop($parents);
    // Create an ID suffix from the parents to make sure each widget is unique.
    $id_suffix = $parents ? '-' . implode('-', $parents) : '';
    $wrapper_id = $field_name . '-media-library-wrapper' . $id_suffix;

    foreach ($referenced_entities as $delta => $referenced_entity) {

      $element['selection'][$delta]['preview']['remove_button']['#attributes']['class'][] = 'icon-link';

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
            'data-dialog-options' => json_encode([
              'classes' => ['ui-dialog-content' => 'media-library-edit__modal'],
              'drupalAutoButtons' => FALSE,
            ]),
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

    return $element;
  }

}
