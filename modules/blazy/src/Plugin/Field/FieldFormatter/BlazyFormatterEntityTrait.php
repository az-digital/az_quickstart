<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

/**
 * A Trait common for all blazy entity formatters.
 */
trait BlazyFormatterEntityTrait {

  /**
   * Returns available field options by bundles.
   */
  protected function getFieldOptionsWithBundles(
    array $target_bundles,
    array $allowed_field_types = [],
    $entity_type = 'media',
    $target_type = '',
    $exclude = TRUE,
  ): array {
    $options = [];

    // Fix for Views UI not recognizing Media bundles, unlike Formatters.
    if (empty($target_bundles)) {
      if ($service = $this->manager->service('entity_type.bundle.info')) {
        $target_bundles = $service->getBundleInfo($entity_type);
      }
    }

    // Declutters options from less relevant options.
    $excludes = $exclude ? $this->getExcludedFieldOptions() : [];

    foreach ($target_bundles as $bundle => $label) {
      if ($fields = $this->manager->loadByProperties([
        'entity_type' => $entity_type,
        'bundle' => $bundle,
      ], 'field_config', FALSE)) {
        foreach ((array) $fields as $field) {
          $name = $field->getName();
          $label = $field->getLabel();

          if ($excludes && in_array($name, $excludes)) {
            continue;
          }

          if (empty($allowed_field_types)) {
            $options[$name] = $label;
          }
          elseif (in_array($field->getType(), $allowed_field_types)) {
            $options[$name] = $label;
          }

          if (!empty($target_type)
            && ($field->getSetting('target_type') == $target_type)) {
            $options[$name] = $label;
          }
        }
      }
    }

    return $options;
  }

  /**
   * Declutters options from less relevant options, specific to captions.
   */
  protected function getExcludedFieldOptions(): array {
    // @todo figure out a more efficient way than blacklisting.
    // Do not exclude field_media_image as needed for Main stage.
    // Do not exclude field_media as needed for Overlays.
    $fields = 'media_document media_pinterest media_svg document_size media_file id media_in_library mime_type source media_twitter tweet_author tweet_id tweet_url media_video_embed_field instagram_shortcode instagram_url media_oembed_instagram media_soundcloud media_oembed_video media_audio_file media_video_file media_facebook media_flickr file_url external_thumbnail local_thumbnail local_thumbnail_uri media_unsplash';
    $fields = array_map('trim', explode(' ', $fields));

    $excludes = [];
    foreach ($fields as $exclude) {
      $excludes['field_' . $exclude] = 'field_' . $exclude;
    }

    $this->manager->moduleHandler()->alter('blazy_excluded_field_options', $excludes);
    return $excludes;
  }

}
