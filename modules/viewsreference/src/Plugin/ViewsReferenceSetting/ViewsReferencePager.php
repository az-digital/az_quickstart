<?php

namespace Drupal\viewsreference\Plugin\ViewsReferenceSetting;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface;

/**
 * The views reference setting pager plugin.
 *
 * @ViewsReferenceSetting(
 *   id = "pager",
 *   label = @Translation("Pagination"),
 *   default_value = "",
 * )
 */
class ViewsReferencePager extends PluginBase implements ViewsReferenceSettingInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function alterFormField(array &$form_field) {
    $form_field['#type'] = 'select';
    $form_field['#options'] = [
      '' => $this->t('Default settings'),
      'full' => $this->t('Full pager'),
      'mini' => $this->t('Mini pager'),
      'some' => $this->t('Hide pager (display a fixed number of items)'),
      'none' => $this->t('Hide pager (display all items)'),
    ];
    $form_field['#weight'] = 35;
  }

  /**
   * {@inheritdoc}
   */
  public function alterView(ViewExecutable $view, $value) {
    if (!empty($value)) {
      $pager = $view->display_handler->getOption('pager');
      $pager['type'] = $value;
      $view->display_handler->setOption('pager', $pager);
    }
  }

}
