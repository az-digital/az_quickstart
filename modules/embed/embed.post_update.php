<?php

/**
 * @file
 * Post update functions for Embed.
 */

/**
 * Convert embed button icons from managed files to encoded data.
 */
function embed_post_update_convert_encoded_icon_data() {
  $file_storage = \Drupal::entityTypeManager()->getStorage('file');
  $file_usage = \Drupal::service('file.usage');

  /** @var \Drupal\embed\EmbedButtonInterface[] $buttons */
  $buttons = \Drupal::entityTypeManager()->getStorage('embed_button')->loadMultiple();

  foreach ($buttons as $button) {
    if ($icon_uuid = $button->get('icon_uuid')) {
      if (!$button->get('icon')) {
        // Read in the button icon file and convert to base 64 encoded string.
        if ($files = $file_storage->loadByProperties(['uuid' => $icon_uuid])) {
          $file = reset($files);
          $button->set('icon', $button::convertImageToEncodedData($file->getFileUri()));

          // Decrement file usage for this embed button.
          $file_usage->delete($file, 'embed', 'embed_button', $button->id());
        }
        else {
          \Drupal::logger('embed')->warning('The embed button @label had an uploaded icon image file with UUID @uuid, but it no longer exists in the database. It has been reverted back to the default button icon and you may wish to re-upload a new version.', [
            '@label' => $button->label(),
            '@uuid' => $icon_uuid,
          ]);
        }
      }

      $button->set('icon_uuid', NULL);
      $button->save();
    }
  }
}
