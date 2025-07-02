<?php

namespace Drupal\embed\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\editor\EditorInterface;
use Drupal\embed\EmbedButtonInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Routing requirement access check for embed buttons and text editors.
 */
class EmbedButtonEditorAccessCheck implements AccessInterface {

  /**
   * Checks whether the embed button is enabled for the given text editor.
   *
   * Returns allowed if the editor toolbar contains the embed button or neutral
   * otherwise.
   *
   * @code
   * pattern: '/foo/{editor}/{embed_button}'
   * requirements:
   *   _embed_button_editor_access: 'TRUE'
   * @endcode
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $parameters = $route_match->getParameters();

    $access_result = AccessResult::allowedIf($parameters->has('editor') && $parameters->has('embed_button'))
      // Vary by 'route' because the access result depends on the 'editor' and
      // 'embed_button' route parameters.
      ->addCacheContexts(['route']);

    if ($access_result->isAllowed()) {
      $editor = $parameters->get('editor');
      $embed_button = $parameters->get('embed_button');
      if ($editor instanceof EditorInterface && $embed_button instanceof EmbedButtonInterface) {
        return $access_result
          // Besides having the 'editor' route parameter, it's also necessary to
          // be allowed to use the text format associated with the text editor.
          ->andIf($editor->getFilterFormat()->access('use', $account, TRUE))
          // And on top of that, the 'embed_button' needs to be present in the
          // text editor's configured toolbar.
          ->andIf($this->checkButtonEditorAccess($embed_button, $editor));
      }
    }

    // No opinion, so other access checks should decide if access should be
    // allowed or not.
    return $access_result;
  }

  /**
   * Checks if the embed button is enabled in an editor configuration.
   *
   * @param \Drupal\embed\EmbedButtonInterface $embed_button
   *   The embed button entity to check.
   * @param \Drupal\editor\EditorInterface $editor
   *   The editor entity to check.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   When the received Text Editor entity does not use CKEditor. This is
   *   currently only capable of detecting buttons used by CKEditor.
   */
  protected function checkButtonEditorAccess(EmbedButtonInterface $embed_button, EditorInterface $editor) {
    if (!in_array($editor->getEditor(), ['ckeditor', 'ckeditor5'], TRUE)) {
      throw new HttpException(500, 'Currently, only CKEditor is supported.');
    }

    $has_button = FALSE;
    $settings = $editor->getSettings();
    if ($editor->getEditor() === 'ckeditor') {
      foreach ($settings['toolbar']['rows'] as $row) {
        foreach ($row as $group) {
          if (in_array($embed_button->id(), $group['items'])) {
            $has_button = TRUE;
            break 2;
          }
        }
      }
    }
    elseif ($editor->getEditor() === 'ckeditor5') {
      // The schema for CKEditor5 has changed, therefore we need to check for
      // the toolbar items differently.
      if ($settings['toolbar']['items'] && in_array($embed_button->id(), $settings['toolbar']['items'])) {
        $has_button = TRUE;
      }
    }

    return AccessResult::allowedIf($has_button)
      ->addCacheableDependency($embed_button)
      ->addCacheableDependency($editor);
  }

}
