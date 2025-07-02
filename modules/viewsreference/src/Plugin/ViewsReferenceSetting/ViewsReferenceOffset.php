<?php

namespace Drupal\viewsreference\Plugin\ViewsReferenceSetting;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface;

/**
 * The views reference setting offset results plugin.
 *
 * @ViewsReferenceSetting(
 *   id = "offset",
 *   label = @Translation("Offset results"),
 *   default_value = "",
 * )
 */
class ViewsReferenceOffset extends PluginBase implements ViewsReferenceSettingInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function alterFormField(array &$form_field) {
    $form_field['#title'] = $this->t('Offset results');
    $form_field['#type'] = 'number';
    $form_field['#weight'] = 30;
  }

  /**
   * {@inheritdoc}
   */
  public function alterView(ViewExecutable $view, $value) {
    if (is_numeric($value)) {
      $view->setOffset($value);
    }
  }

}
