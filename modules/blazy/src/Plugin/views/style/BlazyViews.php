<?php

namespace Drupal\blazy\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Views\BlazyStyleVanilla;
use Drupal\blazy\internals\Internals;

/**
 * Provides Blazy Grid style plugin.
 */
class BlazyViews extends BlazyStyleVanilla implements BlazyViewsInterface {

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'blazy';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'content';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'blazy';

  /**
   * {@inheritdoc}
   */
  protected static $captionId = 'captions';

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  public function admin() {
    return Internals::service('blazy.admin');
  }

  /**
   * Overrides StylePluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $definition = [
      'plugin_id'      => $this->getPluginId(),
      'namespace'      => 'blazy',
      'grid_form'      => TRUE,
      'grid_required'  => TRUE,
      'grid_simple'    => TRUE,
      'no_image_style' => TRUE,
      'opening_class'  => 'form--views',
      'settings'       => $this->options,
      'style'          => TRUE,
      '_views'         => TRUE,
    ];

    // Build the form.
    $this->admin()->gridOnlyForm($form, $definition);
  }

  /**
   * Overrides StylePluginBase::render().
   */
  public function render() {
    $settings = $this->buildSettings();
    $blazies  = $settings['blazies'];
    $view     = $this->view;

    $blazies->set('is.grid', TRUE);

    $elements = [];
    foreach ($this->renderGrouping($view->result, $settings['grouping']) as $rows) {
      $items = [];
      foreach ($rows as $index => $row) {
        $view->row_index = $index;

        $items[$index] = $view->rowPlugin->render($row);
      }

      // Supports lightbox gallery if using Blazy formatter.
      $build = ['items' => $items];
      $this->checkBlazy($settings, $build, $rows);

      $build['#settings'] = $settings;
      $elements = $this->manager->build($build);

      unset($view->row_index, $items);
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = [];
    foreach (BlazyDefault::gridSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

}
