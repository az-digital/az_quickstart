<?php

namespace Drupal\ckeditor_indentblock\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editor\EditorInterface;

/**
 * Indent block ckeditor5 plugin.
 */
class IndentBlock extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, CKEditor5PluginElementsSubsetInterface {

  use CKEditor5PluginConfigurableTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'enable' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable indentation on paragraphs.'),
      '#default_value' => $this->configuration['enable'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_value = $form_state->getValue('enable');
    $form_state->setValue('enable', (bool) $form_value);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['enable'] = $form_state->getValue('enable');
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    if (!$this->configuration['enable']) {
      // Remove all classes, so we don't have any indent levels.
      $static_plugin_config['indentBlock']['classes'] = [];
      // Also 'zero out' the offset so IndentBlock doesn't revert to default
      // behavior of using inline styles.
      $static_plugin_config['indentBlock']['offset'] = 0;
    }
    return $static_plugin_config;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSubset(): array {
    if ($this->configuration['enable']) {
      return ['<p class="Indent*">'];
    }

    return ['false'];
  }

}
