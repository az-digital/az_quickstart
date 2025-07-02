<?php

namespace Drupal\google_tag\Plugin\GoogleTag\Event;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Utility\Token;

/**
 * Base class for event plugins that have tokenized configuration forms.
 */
abstract class ConfigurableEventBase extends EventBase implements PluginFormInterface {

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected Token $token;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['#process'][] = [$this, 'processToken'];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Process callback for adding token validators and token tree to form.
   *
   * @param array $form
   *   Form render array.
   *
   * @return array
   *   Updated form render array.
   */
  public function processToken(array $form): array {
    if ($this->getTokenElements() !== [] && $this->moduleHandler->moduleExists('token')) {
      foreach ($this->getTokenElements() as $element) {
        $form[$element]['#element_validate'] = [
          'token_element_validate',
        ];
      }
      $form['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['node'],
        '#weight' => 100,
      ];
    }
    return $form;
  }

  /**
   * Returns elements on which token is to be applied.
   *
   * @return string[]
   *   Token elements array.
   */
  abstract protected function getTokenElements(): array;

  /**
   * {@inheritDoc}
   */
  public function getData(): array {
    $data = [];
    foreach (array_filter($this->configuration) as $key => $value) {
      $data[$key] = $this->token->replace($value, [], ['clear' => TRUE]);
    }
    return $data;
  }

  /**
   * Sets token service dynamically to the event object.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   Token service.
   */
  public function setToken(Token $token): void {
    $this->token = $token;
  }

  /**
   * Sets module handler.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler): void {
    $this->moduleHandler = $module_handler;
  }

}
