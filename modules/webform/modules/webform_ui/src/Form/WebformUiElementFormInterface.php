<?php

namespace Drupal\webform_ui\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;

/**
 * Provides an interface for webform element webform.
 */
interface WebformUiElementFormInterface extends FormInterface, ContainerInjectionInterface {

  /**
   * Is new element.
   *
   * @return bool
   *   TRUE if this webform generating a new element.
   */
  public function isNew();

  /**
   * Return the webform associated with this form.
   *
   * @return \Drupal\webform\WebformInterface
   *   A form
   */
  public function getWebform();

  /**
   * Return the webform element associated with this form.
   *
   * @return \Drupal\webform\Plugin\WebformElementInterface
   *   A webform element.
   */
  public function getWebformElementPlugin();

  /**
   * Return the render element associated with this form.
   *
   * @return array
   *   An element.
   */
  public function getElement();

  /**
   * Return the render element's key associated with this form.
   *
   * This method allows form alter hooks to know the element's key, which
   * is not included in the element's properties.
   *
   * @return string
   *   The render element's key.
   */
  public function getKey();

  /**
   * Return the render element's parent key associated with this form.
   *
   * This method allows form alter hooks to know the element's parent key, which
   * is not included in the element's properties.
   *
   * @return string
   *   The render element's parent key.
   */
  public function getParentKey();

}
