<?php

namespace Drupal\entity_embed\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginCssInterface;
use Drupal\editor\Entity\Editor;
use Drupal\embed\EmbedButtonInterface;
use Drupal\embed\EmbedCKEditorPluginBase;

/**
 * Defines the "drupalentity" plugin.
 *
 * @CKEditorPlugin(
 *   id = "drupalentity",
 *   label = @Translation("Entity"),
 *   embed_type_id = "entity"
 * )
 *
 * @deprecated in entity_embed:8.x-1.7 and is removed from entity_embed:2.0.0.
 * Use \Drupal\entity_embed\Plugin\CKEditor5Plugin\DrupalEntity instead.
 */
class DrupalEntity extends EmbedCKEditorPluginBase implements CKEditorPluginCssInterface {

  /**
   * {@inheritdoc}
   */
  protected function getButton(EmbedButtonInterface $embed_button) {
    $button = parent::getButton($embed_button);
    $button['entity_type'] = $embed_button->getTypeSetting('entity_type');
    return $button;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->getModulePath('entity_embed') . '/js/plugins/drupalentity/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [
      'DrupalEntity_dialogTitleAdd' => t('Insert entity'),
      'DrupalEntity_dialogTitleEdit' => t('Edit entity'),
      'DrupalEntity_buttons' => $this->getButtons(),
      'DrupalEntity_previewCsrfToken' => $this->getEmbedPreviewCsrfToken(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor) {
    return [
      $this->getModulePath('system') . '/css/components/hidden.module.css',
      $this->getModulePath('entity_embed') . '/css/entity_embed.editor.css',
    ];
  }

}
