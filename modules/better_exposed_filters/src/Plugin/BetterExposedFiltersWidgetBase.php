<?php

namespace Drupal\better_exposed_filters\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\ViewsHandlerInterface;
use Drupal\views\ViewExecutable;

/**
 * Base class for Better exposed filters widget plugins.
 */
abstract class BetterExposedFiltersWidgetBase extends PluginBase implements BetterExposedFiltersWidgetInterface {

  use StringTranslationTrait;

  /**
   * The views executable object.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected ViewExecutable $view;

  /**
   * The views plugin this configuration will affect when exposed.
   *
   * @var \Drupal\views\Plugin\views\ViewsHandlerInterface
   */
  protected ViewsHandlerInterface $handler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'plugin_id' => $this->pluginId,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setView(ViewExecutable $view): void {
    $this->view = $view;
  }

  /**
   * {@inheritdoc}
   */
  public function setViewsHandler(ViewsHandlerInterface $handler): void {
    $this->handler = $handler;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // Apply submitted form state to configuration.
    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      if (array_key_exists($key, $this->configuration)) {
        $this->configuration[$key] = $value;
      }
      else {
        // Remove from form state.
        unset($values[$key]);
      }
    }
  }

  /*
   * Helper functions.
   */

  /**
   * Sets metadata on the form elements for easier processing.
   *
   * @param array $element
   *   The form element to apply the metadata to.
   *
   * @see ://www.drupal.org/project/drupal/issues/2511548
   */
  protected function addContext(array &$element): void {
    $element['#context'] = [
      '#plugin_type' => 'bef',
      '#plugin_id' => $this->pluginId,
      '#view_id' => $this->view->id(),
      '#display_id' => $this->view->current_display,
    ];
  }

  /**
   * Moves an exposed form element into a field group.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Exposed views form state.
   * @param string $element
   *   The key of the form element.
   * @param string $group
   *   The name of the group element.
   */
  protected function addElementToGroup(array &$form, FormStateInterface $form_state, string $element, string $group): void {
    // Ensure group is enabled.
    $form[$group]['#access'] = TRUE;

    // Add element to group.
    $form[$element]['#group'] = $group;

    // Persist state of collapsible field-sets with active elements.
    if (empty($form[$group]['#open'])) {
      // Use raw user input to determine if field-set should be open or closed.
      $user_input = $form_state->getUserInput()[$element] ?? [0];
      // Take multiple values into account.
      if (!is_array($user_input)) {
        $user_input = [$user_input];
      }

      // Check if one or more values are set for our current element.
      $options = $form[$element]['#options'] ?? [];
      $default_value = $form[$element]['#default_value'] ?? key($options);
      $has_values = array_reduce($user_input, function ($carry, $value) use ($form, $element, $default_value) {
        return $carry || ($value === $default_value ? '' : ($value || $default_value === 0));
      }, FALSE);

      $collapsible_disable_automatic_open = FALSE;
      if (isset($form[$element]['#collapsible_disable_automatic_open'])) {
        $collapsible_disable_automatic_open = $form[$element]['#collapsible_disable_automatic_open'];
      }
      elseif (isset($form[$group]['#collapsible_disable_automatic_open'])) {
        $collapsible_disable_automatic_open = $form[$group]['#collapsible_disable_automatic_open'];
      }
      if ($has_values && !$collapsible_disable_automatic_open) {
        $form[$group]['#open'] = TRUE;
      }
    }
  }

  /**
   * Returns exposed form action URL object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Exposed views form state.
   *
   * @return \Drupal\Core\Url
   *   Url object.
   */
  protected function getExposedFormActionUrl(FormStateInterface $form_state): Url {
    $request = \Drupal::request();
    $url = Url::createFromRequest(clone $request);
    $url->setAbsolute();

    return $url;
  }

}
