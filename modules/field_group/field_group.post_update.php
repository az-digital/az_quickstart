<?php

/**
 * @file
 * Post update functions for Field Group.
 */

/**
 * Assign a region to Field Groups.
 */
function field_group_post_update_0001() {
  foreach (['entity_form_display', 'entity_view_display'] as $entity_type) {
    foreach (\Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple() as $display) {
      /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $display */
      if (in_array('field_group', $display->getThirdPartyProviders())) {
        $updated = FALSE;

        // Take Display Suite regions into account.
        $has_ds = FALSE;
        $ds_regions = [];
        if ($entity_type == 'entity_view_display' && in_array('ds', $display->getThirdPartyProviders())) {
          $ds = $display->getThirdPartySettings('ds');
          if (!empty($ds['regions'])) {
            foreach ($ds['regions'] as $region_name => $region_fields) {
              foreach ($region_fields as $field_name) {
                $has_ds = TRUE;
                $ds_regions[$field_name] = $region_name;
              }
            }
          }
        }

        $field_groups = $display->getThirdPartySettings('field_group');
        foreach ($field_groups as $group_name => $data) {
          if (!isset($data['region'])) {
            $region = 'content';
            if ($has_ds) {
              $region = 'hidden';
              if (isset($ds_regions[$group_name])) {
                $region = $ds_regions[$group_name];
              }
            }
            $data['region'] = $region;
            $display->setThirdPartySetting('field_group', $group_name, $data);
            $updated = TRUE;
          }
        }
        if ($updated) {
          $display->save();
        }
      }
    }
  }
}
