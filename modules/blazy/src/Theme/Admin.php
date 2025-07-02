<?php

namespace Drupal\blazy\Theme;

use Drupal\Core\Render\Element;
use Drupal\blazy\internals\Internals;

/**
 * Provides non-reusable blazy admin static methods.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 */
class Admin {

  /**
   * Provides compact description due to small estates in modal.
   */
  public static function themeDescription(array &$form, array $parents = []): void {
    if (!empty($form['#description'])) {
      $desc = [
        '#type'  => 'details',
        '#title' => '?',
        '#open'  => FALSE,
        '#attributes' => [
          'class' => ['b-details', 'b-details--description'],
        ],
      ];

      if ($parents) {
        $desc['#parents'] = $parents;
      }

      $desc['description'] = [
        '#markup' => $form['#description'],
      ];

      if ($manager = Internals::service('blazy.manager')) {
        $form['#description'] = $manager->renderInIsolation($desc);
        $form['#wrapper_attributes']['class'][] = 'form-item--collapsidesc';
      }
    }
  }

  /**
   * Provides horizontal tabs menu for nested details elements.
   */
  public static function tabify(array &$form, $form_id, $region): void {
    $children = Element::children($form[$form_id]);

    // @todo add option $form[$form_id]['#type'] = 'container';
    $form[$form_id]['#attributes']['class'][] = 'b-tabs';
    $form[$form_id]['#attached']['library'][] = 'blazy/admin.tabs';
    $region = $region ?: 'bg';

    $list = [];
    foreach ($children as $delta => $name) {
      $title = $form[$form_id][$name]['#title'] ?? '';
      $group = $region . '-' . $name;
      $id = 'b-tabs-' . $group . '-' . $delta;
      $checked = $delta == 0 ? ' checked="checked"' : '';
      $menu_item = '<label class="b-tabs__label" for="' . $id . '">' . $title . '</label>';

      $list[] = [
        '#markup' => $menu_item,
        '#allowed_tags' => ['label'],
      ];
    }

    $form[$form_id]['tabs_menu'] = [
      '#type' => 'container',
      'items' => $list,
      '#attributes' => [
        'class' => [
          'b-tabs__menu',
        ],
      ],
      '#weight' => -9,
    ];

    foreach ($children as $delta => $name) {
      $title = $form[$form_id][$name]['#title'] ?? '';
      $group = $region . '-' . $name;
      $id = 'b-tabs-' . $group . '-' . $delta;
      $checked = $delta == 0 ? ' checked="checked"' : '';
      $menu_item = '<input class="b-tabs__btn" id="' . $id . '" name="b-tabs-' . $region . '" type="radio"' . $checked . '/>';

      $form[$form_id][$name]['#open'] = TRUE;
      $form[$form_id][$name]['#summary_attributes']['class'][] = 'visually-hidden';
      $content = $form[$form_id][$name];
      unset($form[$form_id][$name]);
      $form[$form_id][$name]['tabs_btn'] = [
        '#markup' => $menu_item,
        '#allowed_tags' => ['input'],
        '#weight' => -9,
      ];

      $form[$form_id][$name]['tabs_content'] = $content;
      $form[$form_id][$name]['tabs_content']['#attributes']['class'][] = 'b-tabs__pane';
      $form[$form_id][$name]['tabs_content']['#weight'] = -8;
    }
  }

}
