<?php

namespace Drupal\viewsreference\Plugin\ViewsReferenceSetting;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface;

/**
 * The views reference setting title plugin.
 *
 * @ViewsReferenceSetting(
 *   id = "title",
 *   label = @Translation("Include View Title"),
 *   default_value = false,
 * )
 */
class ViewsReferenceTitle extends PluginBase implements ViewsReferenceSettingInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function alterFormField(array &$form_field) {
    $form_field['#type'] = 'checkbox';
    $form_field['#weight'] = 20;
  }

  /**
   * {@inheritdoc}
   */
  public function alterView(ViewExecutable $view, $value) {
    if (empty($value)) {
      $view->display_handler->setOption('title', '');
    }
  }

}
