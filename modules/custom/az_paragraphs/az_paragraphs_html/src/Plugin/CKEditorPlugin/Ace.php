<?php

namespace Drupal\az_paragraphs_html\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "ace" plugin.
 *
 * NOTE: The plugin ID ('id' key) corresponds to the CKEditor plugin name.
 * It is the first argument of the CKEDITOR.plugins.add() function in the
 * plugin.js file.
 *
 * @CKEditorPlugin(
 *   id = "ace",
 *   label = @Translation("Replace CKEditor source with Ace Editor.")
 * )
 */
class Ace extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginContextualInterface {

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [
      'az_paragraphs_html/ace',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    // Make sure that the path to the plugin.js matches the file structure of
    // the CKEditor plugin you are implementing.
    return drupal_get_path('module', 'az_paragraphs_html') . '/libraries/ace/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings();

    return [
      'startupMode' => !empty($settings['plugins']['ace']['startup_mode']) ? $settings['plugins']['ace']['startup_mode'] : FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    $settings = $editor->getSettings();
    return isset($settings['plugins']['ace']) ? $settings['plugins']['ace']['enable'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable replacing source plugin with Ace editor.'),
      '#default_value' => !empty($settings['plugins']['ace']['enable']) ? $settings['plugins']['ace']['enable'] : FALSE,
    ];

    $form['startup_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Start editor in "Source" view.'),
      '#description' => $this->t('Starts editor off in "View Source" mode.'),
      '#default_value' => !empty($settings['plugins']['ace']['startup_mode']) ? $settings['plugins']['ace']['startup_mode'] : FALSE,
    ];

    return $form;
  }

}
