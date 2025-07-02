<?php

namespace Drupal\blazy\Traits;

use Drupal\blazy\Blazy;
use Drupal\blazy\Utility\Arrays;
use Drupal\blazy\internals\Internals;

/**
 * A Trait for plugins, common for Blazy, Splide, Slick, etc.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 */
trait PluginScopesTrait {

  /**
   * Converts old plugin scopes array into BlazySettings object to interop.
   */
  protected function toPluginScopes(array $scopes = []) {
    $definitions = $current = [];

    if (empty($scopes)) {
      return Internals::settings($definitions);
    }

    // Allows to merge at admin level for consistent sane method uses.
    if (isset($scopes['scopes'])) {
      $current = $scopes['scopes']->storage();
      unset($scopes['scopes']);
    }

    $current = Arrays::merge($scopes, $current);

    // Excludes unique keys out of scopes at admin form level.
    foreach (['blazies', 'settings'] as $key) {
      if (isset($current[$key])) {
        unset($current[$key]);
      }
    }

    foreach ($current as $key => $value) {
      // All array values are grouped inside `data key`.
      if (is_array($value)) {
        // Do not put duplicate keys into $data, already processed below.
        if (in_array($key, ['data', 'entity', 'field', 'form', 'is'])) {
          continue;
        }

        $data[$key] = $value;

        if (isset($current['data'])) {
          $definitions['data'] = Arrays::merge($data, $current['data']);
        }
        else {
          $definitions['data'] = $data;
        }
      }
      else {
        if (is_bool($value)) {
          $group = Blazy::has($key, '_form') ? 'form' : 'is';
          $key = str_replace('_form', '', $key);
          $definitions[$group][$key] = $value;
        }
        else {
          // @todo recheck and remove for blazies: field, and entity.
          if (Blazy::has($key, 'field_')) {
            $key = str_replace('field_', '', $key);
            $definitions['field'][$key] = $value;
          }
          elseif (Blazy::has($key, 'entity_')) {
            $key = str_replace('entity_', '', $key);
            $definitions['entity'][$key] = $value;
          }
          else {
            $definitions[$key] = $value;
          }
        }
      }
    }
    return Internals::settings($definitions);
  }

  /**
   * Modifies the specific plugin settings.
   */
  protected function pluginSettings(&$blazies, array &$settings): void {
    if ($settings['namespace'] == 'blazy') {
      $id = 'blazy';

      $blazies->set('item.id', $id)
        ->set('lazy.id', $id)
        ->set('namespace', $id);
    }
  }

}
