<?php

namespace Drupal\az_news_export\Plugin\views\row;

use Drupal\az_news_export\AZNewsDataEmpty;
use Drupal\image\Entity\ImageStyle;
use Drupal\rest\Plugin\views\row\DataFieldRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin which displays fields as raw data.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "az_news_data_field",
 *   title = @Translation("Quickstart News Fields"),
 *   help = @Translation("Use News fields as row data."),
 *   display_types = {"data"}
 * )
 */
class AZNewsDataFieldRow extends DataFieldRow {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Utility\Token definition.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

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

    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->token = $container->get('token');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $output = [];
    // Provider a helper for image serialization.
    $image_serializer = function ($value, $entity) {
      $item = [];
      if (!empty($value)) {
        $media = $this->entityTypeManager->getStorage('media')->load($value);
        if (!empty($media) && $media->access('view') && $media->hasField('field_media_az_image')) {
          if (!empty($media->field_media_az_image->entity)) {
            /** @var \Drupal\file\FileInterface $image */
            $image = $media->field_media_az_image->entity;
            $item['fid'] = $image->id();
            $item['uuid'] = $image->uuid();
            $item['original'] = $image->createFileUrl(FALSE);
            $uri = $image->getFileUri();
            $styles = [
              'thumbnail' => 'az_enterprise_thumbnail',
              'thumbnail_small' => 'az_enterprise_thumbnail_small',
            ];
            foreach ($styles as $key => $style_id) {
              $image_style = ImageStyle::load($style_id);
              if (!empty($image_style)) {
                $item[$key] = $image_style->buildUrl($uri);
              }
            }
            if (!empty($media->field_media_az_image->alt)) {
              $item['alt'] = $media->field_media_az_image->alt;
            }
          }
        }
      }
      // Avoid returning an empty array.
      if (empty($item)) {
        $item = new AZNewsDataEmpty();
      }
      return $item;
    };
    // Special serialization rules. Resolve references at serialization time.
    // View relationships creates duplicate rows and wrong array structure.
    $rules = [
      // Serialize file entities as URLs.
      'field_az_attachments' => function ($value, $entity) {
        $items = [];
        $files = $this->entityTypeManager->getStorage('file')->loadMultiple($value);
        foreach ($files as $file) {
          if (!$file->access('view')) {
            continue;
          }
          $items[] = $file->createFileUrl(FALSE);
        }
        return $items;
      },
      // Serialize contacts as associative arrays of keyed fields.
      'field_az_contacts' => function ($value, $entity) {
        $items = [];
        $contacts = $this->entityTypeManager->getStorage('paragraph')->loadMultiple($value);
        foreach ($contacts as $contact) {
          if (!$contact->access('view')) {
            continue;
          }
          $item = [];
          // Serialize specific fields from contact.
          $contact_fields = [
            'field_az_email',
            'field_az_title',
            'field_az_phone',
          ];
          foreach ($contact_fields as $contact_field) {
            if ($contact->hasField($contact_field) && !empty($contact->{$contact_field}->value)) {
              $item[$contact_field] = $contact->{$contact_field}->value;
            }
          }
          $items[] = $item;
        }
        return $items;
      },
      // Serialize media image as file URL.
      'field_az_media_image' => $image_serializer,
      'field_az_media_thumbnail_image' => $image_serializer,
      // Serialize the taxonomy terms as an array of labels.
      'field_az_news_tags' => function ($value, $entity) {
        $items = [];
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadMultiple($value);
        foreach ($terms as $term) {
          if (!$term->access('view')) {
            continue;
          }
          $items[] = $term->label();
        }
        return $items;
      },
      // Serialize the taxonomy terms as an array of enterprise keys.
      'field_az_enterprise_attributes' => function ($value, $entity) {
        $items = [];
        if (!empty($value)) {
          $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

          $terms = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
            ->accessCheck(TRUE)
            ->addTag('taxonomy_term_access')
            ->condition('vid', 'az_enterprise_attributes')
            ->condition('tid', $value, 'IN')
            ->condition('status', 1)
            ->sort('tid')->execute();
          $terms = $term_storage->loadMultiple($terms);
          foreach ($terms as $term) {
            if (!$term->access('view')) {
              continue;
            }
            if (!empty($term->parent->entity)) {
              if (!empty($term->field_az_attribute_key->value) && !empty($term->parent->entity->field_az_attribute_key->value)) {
                $items[$term->parent->entity->field_az_attribute_key->value][] = $term->field_az_attribute_key->value;
              }
            }
          }
        }

        // Avoid returning an empty array.
        if (empty($items)) {
          $items = new AZNewsDataEmpty();
        }
        return $items;
      },
      // Allow for token replacement in short title.
      'field_az_short_title' => function ($value, $entity) {
        $item = "";
        if (!empty($value)) {
          $token_data = ['node' => $entity];
          $token_options = ['clear' => TRUE];
          // Perform token replacement.
          $item = $this->token->replacePlain($value, $token_data, $token_options);
        }
        return $item;
      },
    ];
    foreach ($this->view->field as $id => $field) {
      // If the raw output option has been set, get the raw value.
      if (!empty($this->rawOutputOptions[$id])) {
        $value = $field->getValue($row);
        // Check for special serialization rules for this particular field.
        if (!empty($rules[$id]) && !empty($row->_entity)) {
          $value = $rules[$id]($value, $row->_entity);
        }
      }
      // Otherwise, get rendered field.
      else {
        // Advanced render for token replacement.
        $markup = $field->advancedRender($row);
        // Post render to support uncacheable fields.
        $field->postRender($row, $markup);
        $value = $field->last_render ?? "";
      }

      // Omit excluded fields from the rendered output.
      if (empty($field->options['exclude'])) {
        $output[$this->getFieldKeyAlias($id)] = $value;
      }
    }

    return $output;
  }

}
