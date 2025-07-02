<?php

namespace Drupal\ctools\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\ctools\ConstraintConditionInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for condition configur operations.
 */
abstract class ConditionConfigure extends FormBase {

  /**
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $manager;

  /**
   * @var string
   */
  protected $tempstore_id;

  /**
   * @var string
   */
  protected $machine_name;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('tempstore.shared'), $container->get('plugin.manager.condition'));
  }

  /**
   * Constructor for Condition Configuration.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $tempstore
   *   The Tempstore Factory.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The Plugin Manager.
   */
  public function __construct(SharedTempStoreFactory $tempstore, PluginManagerInterface $manager) {
    $this->tempstore = $tempstore;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ctools_condition_configure';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $condition = NULL, $tempstore_id = NULL, $machine_name = NULL) {
    $this->tempstore_id = $tempstore_id;
    $this->machine_name = $machine_name;
    $cached_values = $this->tempstore->get($this->tempstore_id)->get($this->machine_name);
    if (is_numeric($condition) || Uuid::isValid($condition)) {
      $id = $condition;
      $condition = $this->getConditions($cached_values)[$id];
      $instance = $this->manager->createInstance($condition['id'], $condition);
    }
    else {
      $instance = $this->manager->createInstance($condition, []);
    }
    $form_state->setTemporaryValue('gathered_contexts', $this->getContexts($cached_values));
    /** @var \Drupal\Core\Condition\ConditionInterface $instance */
    $form = $instance->buildConfigurationForm($form, $form_state);
    if (isset($id)) {
      // Conditionally set this form element so that we can update or add.
      $form['id'] = [
        '#type' => 'value',
        '#value' => $id,
      ];
    }
    $form['instance'] = [
      '#type' => 'value',
      '#value' => $instance,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#ajax' => [
        'callback' => [$this, 'ajaxSave'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $this->tempstore->get($this->tempstore_id)->get($this->machine_name);
    /** @var \Drupal\Core\Condition\ConditionInterface $instance */
    $instance = $form_state->getValue('instance');
    $instance->submitConfigurationForm($form, $form_state);
    $conditions = $this->getConditions($cached_values);
    if ($instance instanceof ContextAwarePluginInterface) {
      /** @var  \Drupal\Core\Plugin\ContextAwarePluginInterface $instance */
      $context_mapping = $form_state->hasValue('context_mapping') ? $form_state->getValue('context_mapping') : [];
      $instance->setContextMapping($context_mapping);
    }
    if ($instance instanceof ConstraintConditionInterface) {
      /** @var  \Drupal\ctools\ConstraintConditionInterface $instance */
      $instance->applyConstraints($this->getContexts($cached_values));
    }
    if ($form_state->hasValue('id')) {
      $conditions[$form_state->getValue('id')] = $instance->getConfiguration();
    }
    else {
      $conditions[] = $instance->getConfiguration();
    }
    $cached_values = $this->setConditions($cached_values, $conditions);
    $this->tempstore->get($this->tempstore_id)->set($this->machine_name, $cached_values);
    [$route_name, $route_parameters] = $this->getParentRouteInfo($cached_values);
    $form_state->setRedirect($route_name, $route_parameters);
  }

  /**
   * Ajax callback to save tempstore values.
   *
   * @param array $form
   *   The Drupal Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax values from tempstore.
   */
  public function ajaxSave(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $cached_values = $this->tempstore->get($this->tempstore_id)->get($this->machine_name);
    [$route_name, $route_parameters] = $this->getParentRouteInfo($cached_values);
    $url = Url::fromRoute($route_name, $route_parameters);
    $response->addCommand(new RedirectCommand($url->toString()));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * Document the route name and parameters for redirect after submission.
   *
   * @param array $cached_values
   *   Cached values to get the route info.
   *
   * @return array
   *   In the format of [
   *    'route.name',
   *    ['machine_name' => $this->machine_name, 'step' => 'step_name']];
   */
  abstract protected function getParentRouteInfo($cached_values);

  /**
   * Custom logic for retrieving the conditions array from cached_values.
   *
   * @param array $cached_values
   *   Cached values to get contexts from.
   *
   * @return array
   *   The conditions attached to cached values.
   */
  abstract protected function getConditions(array $cached_values);

  /**
   * Custom logic for setting the conditions array in cached_values.
   *
   * @param array $cached_values
   *   Cached values that will get set.
   *
   * @param mixed $conditions
   *   The conditions to set within the cached values.
   *
   * @return mixed
   *   Return the $cached_values
   */
  abstract protected function setConditions($cached_values, $conditions);

  /**
   * Custom logic for retrieving the contexts array from cached_values.
   *
   * @param array $cached_values
   *   Cached values to get contexts from.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   The contexts from cache.
   */
  abstract protected function getContexts(array $cached_values);

}
