<?php

namespace Drupal\webform\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Drupal\webform\Utility\WebformElementHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Webform' condition.
 *
 * @Condition(
 *   id = "webform",
 *   label = @Translation("Webforms"),
 *   context_definitions = {
 *     "webform" = @ContextDefinition("entity:webform", label = @Translation("Webform"), required = FALSE),
 *     "webform_submission" = @ContextDefinition("entity:webform_submission", label = @Translation("Webform submission"), required = FALSE),
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"), required = FALSE),
 *   }
 * )
 */
class Webform extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  use WebformEntityStorageTrait;

  /**
   * The webform entity reference manager.
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $webformEntityReferenceManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->webformEntityReferenceManager = $container->get('webform.entity_reference_manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $webforms = $this->getWebformStorage()->loadMultiple();
    foreach ($webforms as $webform) {
      $options[$webform->id()] = $webform->label();
    }
    $form['webforms'] = [
      '#title' => $this->t('Webform', [], ['context' => 'form']),
      '#description' => $this->t('Select which webforms this block should be displayed on.'),
      '#type' => 'select',
      '#options' => $options,
      '#multiple' => $options,
      '#select2' => TRUE,
      '#default_value' => $this->configuration['webforms'],
    ];
    WebformElementHelper::process($form['webforms']);

    if (empty($this->configuration['context_mapping'])) {
      $form['message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('Please make sure to select which entities should be used to determine the current webform.'),
        '#message_type' => 'warning',
      ];
    }

    $form = parent::buildConfigurationForm($form, $form_state);

    // Add helpful descriptions to context mapping.
    $form['context_mapping']['webform']['#description'] = $this->t("Select 'Webform from URL' to display this block, when the current request's path contains the selected webform.");
    $form['context_mapping']['webform_submission']['#title'] = $this->t('Select a @context value:', ['@context' => $this->t('webform submission')]);
    $form['context_mapping']['webform_submission']['#description'] = $this->t("Select 'Webform submission from URL' to display this block, when the current request's path contains a webform submission that was created from the selected webform.");
    $form['context_mapping']['node']['#description'] = $this->t("Select 'Node from URL' to display this block, when the current request's path contains a node that references the selected webform using a dedicated webform field or node.");

    // Attached library to summarize configuration settings.
    $form['#attached']['library'][] = 'webform/webform.block';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['webforms']) && empty(array_filter($values['context_mapping']))) {
      $form_state->setErrorByName('webforms', $this->t('Please select which entity should be used to determine the current webform.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('webforms') ?: [];
    $values = array_filter($values);
    $this->configuration['webforms'] = array_combine($values, $values);
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['webforms']) > 1) {
      $webforms = $this->configuration['webforms'];
      $last = array_pop($webforms);
      $webforms = implode(', ', $webforms);
      return $this->t('The webform is @webforms or @last', ['@webforms' => $webforms, '@last' => $last]);
    }
    $webform = reset($this->configuration['webforms']);
    return $this->t('The webform is @webform', ['@webform' => $webform]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['webforms']) && !$this->isNegated()) {
      return TRUE;
    }
    elseif ($webform = $this->getContextWebform()) {
      return !empty($this->configuration['webforms'][$webform->id()]);
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['webforms' => []] + parent::defaultConfiguration();
  }

  /**
   * Gets the webform for a defined context.
   *
   * @return null|\Drupal\webform\WebformInterface
   *   The current context's webform.
   */
  protected function getContextWebform() {
    if ($webform_submission = $this->getContextValue('webform_submission')) {
      return $webform_submission->getWebform();
    }
    if ($webform = $this->getContextValue('webform')) {
      return $webform;
    }
    if ($node = $this->getContextValue('node')) {
      if ($webform_target = $this->webformEntityReferenceManager->getWebform($node)) {
        return $webform_target;
      }
    }
    return NULL;
  }

}
