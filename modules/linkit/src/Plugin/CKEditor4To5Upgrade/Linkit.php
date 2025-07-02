<?php

declare(strict_types=1);

namespace Drupal\linkit\Plugin\CKEditor4To5Upgrade;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\filter\FilterFormatInterface;

/**
 * Provides the CKEditor 4 to 5 upgrade for Linkit's CKEditor plugin.
 *
 * @CKEditor4To5Upgrade(
 *   id = "linkit",
 *   cke4_plugin_settings = {
 *     "drupallink",
 *   }
 * )
 */
class Linkit extends PluginBase implements CKEditor4To5UpgradePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(string $cke4_button, HTMLRestrictions $text_format_html_restrictions): ?array {
    throw new \OutOfBoundsException();
  }

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4SettingsToCKEditor5Configuration(string $cke4_plugin_id, array $cke4_plugin_settings): ?array {
    switch ($cke4_plugin_id) {
      // @see \Drupal\linkit\Plugin\CKEditorPlugin\LinkitDrupalLink
      // @see \Drupal\linkit\Plugin\CKEditor5Plugin\Linkit
      case 'drupallink':
        $sanitized = [];
        if (!isset($cke4_plugin_settings['linkit_enabled']) || !isset($cke4_plugin_settings['linkit_profile'])) {
          $sanitized['linkit_enabled'] = FALSE;
        }
        else {
          $sanitized['linkit_enabled'] = (bool) $cke4_plugin_settings['linkit_enabled'];
          if ($sanitized['linkit_enabled']) {
            $sanitized['linkit_profile'] = $cke4_plugin_settings['linkit_profile'];
          }
        }
        return ['linkit_extension' => $sanitized];

      default:
        throw new \OutOfBoundsException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function computeCKEditor5PluginSubsetConfiguration(string $cke5_plugin_id, FilterFormatInterface $text_format): ?array {
    throw new \OutOfBoundsException();
  }

}
