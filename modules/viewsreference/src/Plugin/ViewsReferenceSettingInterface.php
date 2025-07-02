<?php

namespace Drupal\viewsreference\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\views\ViewExecutable;

/**
 * Defines an interface for views reference setting plugins.
 */
interface ViewsReferenceSettingInterface extends PluginInspectionInterface {

  /**
   * Get the form field array.
   *
   * @param array $form_field
   *   The form field array.
   */
  public function alterFormField(array &$form_field);

  /**
   * Alter the view object.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object to alter.
   * @param mixed $value
   *   The field value.
   */
  public function alterView(ViewExecutable $view, $value);

}
