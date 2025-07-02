<?php

namespace Drupal\blazy\Form;

use Drupal\Component\Utility\Unicode;

/**
 * A base for field formatter admin to have re-usable methods in one place.
 */
abstract class BlazyAdminFormatterBase extends BlazyAdminBase {

  /**
   * {@inheritdoc}
   */
  public function basicImageForm(array &$form, array $definition): void {
    $scopes = $this->toScopes($definition);
    $data = $scopes->get('data');

    $this->imageStyleForm($form, $definition);

    if ($scopes->form('media_switch') && !isset($form['media_switch'])) {
      $this->mediaSwitchForm($form, $definition);
    }

    if (isset($data['images'])) {
      $form['image'] = $this->baseForm($definition)['image'];
      $form['image']['#prefix'] = '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function imageStyleForm(array &$form, array $definition): void {
    $scopes     = $this->toScopes($definition);
    $blazies    = $definition['blazies'];
    $field_type = $blazies->get('field.type');
    $plugin_id  = $blazies->get('field.plugin_id', '');
    $no_image   = $scopes->is('no_image_style');

    // Not all has defined plugin_id such as filters for now.
    if ($no_image || strpos($plugin_id, '_text') !== FALSE) {
      return;
    }

    $base = $this->baseForm($definition);

    // Excludes VEF which has no File API to work with.
    $disabled = ($field_type && $field_type == 'video_embed_field')
      || $plugin_id == 'blazy_vef_default';

    if (!$disabled && isset($base['preload'])) {
      $form['preload'] = $base['preload'];
    }

    foreach (['loading', 'image_style', 'responsive_image_style'] as $key) {
      if (isset($base[$key])) {
        $form[$key] = $base[$key];
      }
    }

    if ($scopes->is('thumbnail_style')) {
      if (isset($base['thumbnail_style'])) {
        $form['thumbnail_style'] = $base['thumbnail_style'];
      }
    }

    if ($scopes->form('svg')) {
      $this->svgForm($form, $definition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsSummary(array $definition): array {
    if (empty($definition['settings'])) {
      return [];
    }

    $this->getExcludedSettingsSummary($definition);

    $enforced = [
      'optionset',
      'cache',
      'skin',
      'view_mode',
      'override',
      'overridables',
      'style',
      'vanilla',
    ];

    $summary  = [];
    $enforced = $definition['enforced'] ?? $enforced;
    $settings = array_filter($definition['settings']);

    foreach ($definition['settings'] as $key => $setting) {
      $title   = Unicode::ucfirst(str_replace('_', ' ', $key));
      $vanilla = !empty($settings['vanilla']);

      // @todo remove deprecated breakpoints anytime before 3.x.
      if ($key == 'breakpoints') {
        continue;
      }

      if ($vanilla && !in_array($key, $enforced)) {
        continue;
      }

      if ($key == 'override' && empty($setting)) {
        unset($settings['overridables']);
      }

      if (is_bool($setting) && $setting) {
        $setting = 'yes';
      }
      elseif (is_array($setting)) {
        $setting = array_filter($setting);
        if (!empty($setting)) {
          $setting = implode(', ', $setting);
        }
      }

      if ($key == 'cache') {
        $setting = $this->getCacheOptions()[$setting];
      }

      if (empty($setting)) {
        continue;
      }

      if (isset($settings[$key]) && is_string($setting)) {
        $summary[] = $this->t('@title: <strong>@setting</strong>', [
          '@title'   => $title,
          '@setting' => $setting,
        ]);
      }
    }
    return $summary;
  }

  /**
   * Exclude the field formatter settings summary as required.
   */
  protected function getExcludedSettingsSummary(array &$definition): void {
    $scopes       = $this->toScopes($definition);
    $settings     = &$definition['settings'];
    $excludes     = $scopes->data('excludes');
    $plugin_id    = $scopes->get('plugin_id');
    $blazy        = $plugin_id && strpos($plugin_id, 'blazy') !== FALSE;
    $image_styles = $this->getEntityAsOptions('image_style');
    $lightboxes   = $scopes->data('lightboxes');

    if ($blazy) {
      $excludes['optionset'] = TRUE;
    }

    $excludes['admin_uri'] = TRUE;
    $excludes['use_lb'] = TRUE;

    if (empty($settings['grid'])) {
      foreach (['grid', 'grid_medium', 'grid_small', 'visible_items'] as $key) {
        $excludes[$key] = TRUE;
      }
    }

    if ($lightboxes
      && !empty($settings['media_switch'])
      && !in_array($settings['media_switch'], $lightboxes)) {
      foreach (['box_style', 'box_media_style', 'box_caption'] as $key) {
        $excludes[$key] = TRUE;
      }
    }

    if (empty($settings['media_switch'])) {
      foreach (['box_style', 'box_media_style', 'box_caption'] as $key) {
        $excludes[$key] = TRUE;
      }
    }

    // Remove exluded settings.
    $scopes->set('data.excludes', $excludes);
    foreach ($excludes as $key => $value) {
      if (isset($settings[$key])) {
        unset($settings[$key]);
      }
    }

    foreach ($settings as $key => $setting) {
      if ($key == 'style' || $key == 'responsive_image_style' || empty($settings[$key])) {
        continue;
      }
      if (strpos($key, 'style') !== FALSE && isset($image_styles[$settings[$key]])) {
        $settings[$key] = $image_styles[$settings[$key]];
      }
    }
  }

}
