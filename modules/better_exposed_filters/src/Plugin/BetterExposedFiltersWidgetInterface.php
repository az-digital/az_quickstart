<?php

namespace Drupal\better_exposed_filters\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\views\Plugin\views\ViewsHandlerInterface;
use Drupal\views\ViewExecutable;

/**
 * Defines an interface for Better exposed filters filter widget plugins.
 */
interface BetterExposedFiltersWidgetInterface extends PluginFormInterface, PluginInspectionInterface, ConfigurableInterface {

  /**
   * Sets the view object.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The views executable object.
   */
  public function setView(ViewExecutable $view);

  /**
   * Sets the exposed view handler plugin.
   *
   * @param \Drupal\views\Plugin\views\ViewsHandlerInterface $handler
   *   The views handler plugin this configuration will affect when exposed.
   */
  public function setViewsHandler(ViewsHandlerInterface $handler);

  /**
   * Verify this plugin can be used on the form element.
   *
   * @param mixed|null $handler
   *   The handler type we are altering (e.g. filter, pager, sort).
   * @param array $options
   *   The options for this handler.
   *
   * @return bool
   *   If this plugin can be used.
   */
  public static function isApplicable(mixed $handler = NULL, array $options = []): bool;

  /**
   * Manipulate views exposed from element.
   *
   * @param array $form
   *   The views configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state);

}
