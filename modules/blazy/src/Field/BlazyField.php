<?php

namespace Drupal\blazy\Field;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Element;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\internals\Internals;

/**
 * Provides common field API operation methods.
 */
class BlazyField {

  /**
   * Returns the string value of the fields: link, or text.
   */
  public static function getString($entity, $field_name, $langcode, $clean = TRUE): string {
    if ($entity->hasField($field_name)) {
      $values = self::getValue($entity, $field_name, $langcode);

      // Can be text, or link field.
      $string = $values[0]['uri'] ?? ($values[0]['value'] ?? '');

      if ($string && is_string($string)) {
        $string = $clean
          ? strip_tags($string, '<a><strong><em><span><small>')
          : Xss::filter($string, BlazyDefault::TAGS);
        return trim($string);
      }
    }
    return '';
  }

  /**
   * Returns the text or link value of the fields: link, or text.
   */
  public static function getTextOrLink($entity, $field_name, $view_mode, $langcode, $multiple = TRUE): array {
    if ($entity->hasField($field_name)) {
      if ($text = self::getValue($entity, $field_name, $langcode)) {
        if (!empty($text[0]['value']) && !isset($text[0]['uri'])) {
          // Prevents HTML-filter-enabled text from having bad markups (h2 > p),
          // except for a few reasonable tags acceptable within H2 tag.
          $text = self::getString($entity, $field_name, $langcode, FALSE);
        }
        elseif (isset($text[0]['uri'])) {
          $text = self::view($entity, $field_name, $view_mode, $multiple);
        }

        // Prevents HTML-filter-enabled text from having bad markups
        // (h2 > p), save for few reasonable tags acceptable within H2 tag.
        return is_string($text)
          ? ['#markup' => strip_tags($text, '<a><strong><em><span><small>')]
          : $text;
      }
    }
    return [];
  }

  /**
   * Returns the value of the fields: link, or text.
   */
  public static function getValue($entity, $field_name, $langcode) {
    if ($entity->hasField($field_name)) {
      $entity = Blazy::translated($entity, $langcode);

      return $entity->get($field_name)->getValue();
    }
    return NULL;
  }

  /**
   * Returns available bundles.
   */
  public static function getAvailableBundles($field): array {
    $type     = $field->getSetting('target_type');
    $views_ui = $field->getSetting('handler') == 'default';
    $handlers = $field->getSetting('handler_settings');
    $targets  = $handlers ? ($handlers['target_bundles'] ?? []) : [];
    $bundles  = $views_ui ? [] : $targets;

    // Fix for Views UI not recognizing Media bundles, unlike Formatters.
    if (empty($bundles)
      && $type
      && $service = Internals::service('entity_type.bundle.info')) {
      $bundles = $service->getBundleInfo($type);
    }

    return $bundles;
  }

  /**
   * Provides field-related settings, called by back-end and front-end.
   */
  public static function settings(array &$settings, $field, array $data = []): array {
    $settings['blazies'] = $settings['blazies'] ?? Internals::settings();
    $blazies = $settings['blazies'];
    $bundles = self::getAvailableBundles($field);

    $submodules = [
      'cardinality'    => $field->getFieldStorageDefinition()->getCardinality(),
      'field_type'     => $field->getType(),
      'target_bundles' => $bundles,
      'target_type'    => $field->getSetting('target_type'),
    ];

    $info = [
      'field_label'   => $field->getLabel(),
      'field_name'    => $field->getName(),
      'entity_type'   => $field->getTargetEntityTypeId(),
      'target_bundle' => $field->getTargetBundle(),
    ] + $submodules;

    if ($data) {
      $blazies->set('field', $data, TRUE);
    }

    $blazies->set('field.settings', $field->getSettings());
    if (!$blazies->get('namespace')
      && $namespace = $settings['namespace'] ?? NULL) {
      $blazies->set('namespace', $namespace);
    }

    foreach ($info as $key => $value) {
      $k = str_replace('field_', '', $key);
      $blazies->set('field.' . $k, $value);
    }

    // Cannot use blazies.field.settings.handler_settings.target_bundles, since
    // they are always empty at View UI.
    if ($bundles) {
      $blazies->set('field.target_bundles', $bundles);
    }

    // @todo remove at/ by 3.x after migration and sub-modules: EZ, Splidebox.
    foreach ($submodules as $key => $value) {
      $settings[$key] = $value;
    }

    return $settings;
  }

  /**
   * Returns the formatted renderable array of the field.
   */
  public static function view($entity, $field_name, $view_mode, $multiple = TRUE): array {
    if ($entity && $entity->hasField($field_name)) {
      $view = $entity->get($field_name)->view($view_mode);

      if (empty($view[0])) {
        return [];
      }

      // Prevents quickedit to operate here as otherwise JS error.
      // @see 2314185, 2284917, 2160321.
      // @see quickedit_preprocess_field().
      // @todo Remove when it respects plugin annotation.
      $view['#view_mode'] = '_custom';
      $weight = $view['#weight'] ?? 0;

      // Intentionally clean markups as this is not meant for vanilla.
      if ($multiple) {
        $items = [];
        foreach (Element::children($view) as $key) {
          $items[$key] = $view[$key];
        }

        $items['#weight'] = $weight;
        return $items;
      }
      return $view[0] ?? [];
    }

    return [];
  }

}
