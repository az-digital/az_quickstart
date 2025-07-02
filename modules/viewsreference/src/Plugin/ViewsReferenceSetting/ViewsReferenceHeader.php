<?php

namespace Drupal\viewsreference\Plugin\ViewsReferenceSetting;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface;

/**
 * The views reference setting header plugin.
 *
 * @ViewsReferenceSetting(
 *   id = "header",
 *   label = @Translation("Hide header"),
 *   default_value = false,
 * )
 */
class ViewsReferenceHeader extends PluginBase implements ViewsReferenceSettingInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function alterFormField(array &$form_field) {
    $form_field['#type'] = 'checkbox';
    $form_field['#weight'] = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function alterView(ViewExecutable $view, $value) {
    if (!empty($value) && !empty($view->display_handler->getOption('header'))) {
      $view->display_handler->setOption('header', []);
    }
  }

}
