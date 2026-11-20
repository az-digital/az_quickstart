<?php

namespace Drupal\az_digital_asset_library\Hook;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for az_digital_asset_library.
 *
 * The form alters run last so the IntelligenceBank modules' form alterations
 * are already applied.
 */
class AzDigitalAssetLibraryHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Change label text for open browser button.
   * Change dialog title option.
   *
   * See @link drupal.org/project/intelligencebank/issues/3353858
   */
  #[Hook('form_media_library_add_form_upload_alter', order: Order::Last)]
  public function formMediaLibraryAddFormUploadAlter(array &$form, FormStateInterface $form_state): void {
    $data_dialog_options = [];
    $form['ib_dam_container']['asset_browser']['#title'] = $this->t('Add Arizona Digital Asset Library Media');
    if (isset($form['ib_dam_container']['asset_browser']['#attributes']['data-dialog-options'])) {
      $data_dialog_options = Json::decode($form['ib_dam_container']['asset_browser']['#attributes']['data-dialog-options']);
    }
    $data_dialog_options['title'] = 'Arizona Digital Asset Library Browser';
    $data_dialog_options['width'] = '75%';
    $form['ib_dam_container']['asset_browser']['#attributes']['data-dialog-options'] = Json::encode($data_dialog_options);
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Remove image style option and always use the default image style.
   * Remove image link option.
   * Remove image loading options.
   *
   * See @link https://github.com/az-digital/az_quickstart/pull/2163
   */
  #[Hook('form_ib_dam_browser_form_alter', order: Order::Last)]
  public function formIbDamBrowserFormAlter(array &$form, FormStateInterface $form_state): void {
    $form['settings']['image_style']['#access'] = FALSE;
    $form['settings']['image_link']['#access'] = FALSE;
    $form['settings']['image_loading']['#access'] = FALSE;
  }

}
