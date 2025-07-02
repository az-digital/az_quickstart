<?php

namespace Drupal\blazy_test\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Views\BlazyStylePluginBase;
use Drupal\blazy\internals\Internals;

/**
 * Blazy Views Test style plugin.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "blazy_test",
 *   title = @Translation("Blazy Views Test"),
 *   help = @Translation("Display the results in a Blazy Views Test."),
 *   theme = "blazy_test",
 *   register_theme = FALSE,
 *   display_types = {"normal"}
 * )
 */
class BlazyViewsTst extends BlazyStylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'blazy';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'box';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'box';

  /**
   * {@inheritdoc}
   */
  protected static $captionId = 'caption';

  /**
   * Returns the blazy admin.
   */
  public function admin() {
    return Internals::service('blazy_test.admin');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = [];
    $defaults = BlazyDefault::extendedSettings() + BlazyDefault::gridSettings();
    foreach ($defaults as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

  /**
   * Overrides parent::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $fields = [
      'captions',
      'layouts',
      'images',
      'links',
      'titles',
      'classes',
      'overlays',
      'thumbnails',
      'layouts',
    ];

    $definition = $this->getDefinedFieldOptions($fields);

    $definition += [
      'namespace'   => 'blazy',
      'plugin_id'   => $this->getPluginId(),
      'settings'    => $this->options,
      'style'       => TRUE,
      'grid_simple' => TRUE,
    ];

    // Build the form.
    $this->admin()->buildSettingsForm($form, $definition);
  }

  /**
   * Overrides StylePluginBase::render().
   */
  public function render() {
    $settings = $this->buildSettings() + BlazyDefault::entitySettings();
    $blazies = $settings['blazies'];

    $settings['caption']   = array_filter($settings['caption']);
    $settings['namespace'] = 'blazy';
    $settings['ratio']     = '';

    $elements = [];
    foreach ($this->renderGrouping($this->view->result, $settings['grouping']) as $rows) {
      $contents = [];
      foreach ($this->buildElements($settings, $rows) as $item) {
        $contents[] = $item;
      }

      // Supports Blazy multi-breakpoint images if using Blazy formatter.
      if ($data = $this->getFirstImage($rows[0] ?? NULL)) {
        $blazies->set('first.data', $data);
      }

      $build = ['items' => $contents, '#settings' => $settings];
      $elements = $this->blazyManager->build($build);
    }

    return $elements;
  }

  /**
   * Returns blazy_test contents.
   */
  protected function buildElements(array $settings, $rows): \Generator {
    $view = $this->view;

    foreach ($rows as $index => $row) {
      $view->row_index = $index;

      $box = [];
      $box[static::$itemId] = [];
      $box['#settings'] = $settings;

      // Use Vanilla if so configured.
      if (!empty($settings['vanilla'])) {
        $box[static::$itemId] = $view->rowPlugin->render($row);
      }
      else {
        // Build individual row/ element contents.
        $this->buildElement($box, $row, $index);
      }

      // Build blazy items.
      yield $box;
    }

    unset($view->row_index);
  }

}
