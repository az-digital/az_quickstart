<?php

namespace Drupal\ib_dam_media\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\media_library\Form\AddFormBase;

class MediaLibraryIbDamRemoteAssetAddForm extends AddFormBase {

  protected function buildInputElement(array $form, FormStateInterface $form_state) {
    $form['container'] = [
      '#type' => 'container',
    ];

    $link_url = Url::fromRoute('id_dam_media.asset_browser_form');
    $link_url->setOptions([
      'attributes' => [
        'class' => ['use-ajax', 'button', 'button--primary'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'dialogClass' => 'media-library-widget-modal',
          'width' => '75%',
          'height' => 'window.innerHeight',
          'minHeight' => 500,
        ]),
      ],
      'query' => $this->getMediaLibraryState($form_state)->all(),
    ]);

    $form['container']['submit'] = [
      '#type' => 'markup',
      '#markup' => Link::fromTextAndUrl(t('Open IntelligenceBank DAM browser'), $link_url)->toString(),
      '#attached' => ['library' => [
        'core/drupal.dialog.ajax',
      ]]
    ];

    $dialogMode = $this->getDialogMode();
    if ($dialogMode === 'stacked') {
      $form['container']['submit']['#attached']['library'][] = 'ib_dam/dialog';
    }
    return $form;
  }

  public function getFormId() {
    return 'id_dam_media_library_remote_asset_add';
  }

  protected function getDialogMode() {
    return 'regular';
  }

}
