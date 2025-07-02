<?php

namespace Drupal\ctools\Wizard;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\ctools\Ajax\OpenModalWizardCommand;
use Drupal\ctools\Event\WizardEvent;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The base class for all form wizard.
 */
abstract class FormWizardBase extends FormBase implements FormWizardInterface {

  /**
   * Tempstore Factory for keeping track of values in each step of the wizard.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * The Form Builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $builder;

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The shared temp store factory collection name.
   *
   * @var string
   */
  protected $tempstore_id;

  /**
   * The SharedTempStore key for our current wizard values.
   *
   * @var string|null
   */
  protected $machine_name;

  /**
   * The current active step of the wizard.
   *
   * @var string|null
   */
  protected $step;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $tempstore
   *   Tempstore Factory for keeping track of values in each step of the
   *   wizard.
   * @param \Drupal\Core\Form\FormBuilderInterface $builder
   *   The Form Builder.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param $tempstore_id
   *   The shared temp store factory collection name.
   * @param null $machine_name
   *   The SharedTempStore key for our current wizard values.
   * @param null $step
   *   The current active step of the wizard.
   */
  public function __construct(SharedTempStoreFactory $tempstore, FormBuilderInterface $builder, ClassResolverInterface $class_resolver, EventDispatcherInterface $event_dispatcher, RouteMatchInterface $route_match, RendererInterface $renderer, $tempstore_id, $machine_name = NULL, $step = NULL) {
    $this->tempstore = $tempstore;
    $this->builder = $builder;
    $this->classResolver = $class_resolver;
    $this->dispatcher = $event_dispatcher;
    $this->routeMatch = $route_match;
    $this->renderer = $renderer;
    $this->tempstore_id = $tempstore_id;
    $this->machine_name = $machine_name;
    $this->step = $step;
  }

  /**
   * {@inheritdoc}
   */
  public static function getParameters() {
    return [
      'tempstore' => \Drupal::service('tempstore.shared'),
      'builder' => \Drupal::service('form_builder'),
      'class_resolver' => \Drupal::service('class_resolver'),
      'event_dispatcher' => \Drupal::service('event_dispatcher'),
      'renderer' => \Drupal::service('renderer'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initValues() {
    $values = [];
    $event = new WizardEvent($this, $values);
    $this->dispatcher->dispatch($event, FormWizardInterface::LOAD_VALUES);
    return $event->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function getTempstoreId() {
    return $this->tempstore_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getTempstore() {
    $tempstore = $this->tempstore->get($this->getTempstoreId());
    return $tempstore;
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getStep($cached_values) {
    if (!$this->step) {
      $operations = $this->getOperations($cached_values);
      $steps = array_keys($operations);
      $this->step = reset($steps);
    }
    return $this->step;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperation($cached_values) {
    $operations = $this->getOperations($cached_values);
    $step = $this->getStep($cached_values);
    if (!empty($operations[$step])) {
      return $operations[$step];
    }

    throw new NotFoundHttpException();
  }

  /**
   * The translated text of the "Next" button's text.
   *
   * @return string
   */
  public function getNextOp() {
    return $this->t('Next');
  }

  /**
   * {@inheritdoc}
   */
  public function getNextParameters($cached_values) {
    // Get the steps by key.
    $operations = $this->getOperations($cached_values);
    $steps = array_keys($operations);
    // Get the steps after the current step.
    $after = array_slice($operations, array_search($this->getStep($cached_values), $steps) + 1);
    // Get the steps after the current step by key.
    $after_keys = array_keys($after);
    $step = reset($after_keys);
    if (!$step) {
      $keys = array_keys($operations);
      $step = end($keys);
    }
    return [
      'machine_name' => $this->getMachineName(),
      'step' => $step,
      'js' => 'nojs',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousParameters($cached_values) {
    $operations = $this->getOperations($cached_values);
    $step = $this->getStep($cached_values);

    // Get the steps by key.
    $steps = array_keys($operations);
    // Get the steps before the current step.
    $before = array_slice($operations, 0, array_search($step, $steps));
    // Get the steps before the current step by key.
    $before = array_keys($before);
    // Reverse the steps for easy access to the next step.
    $before_steps = array_reverse($before);
    $step = reset($before_steps);
    return [
      'machine_name' => $this->getMachineName(),
      'step' => $step,
      'js' => 'nojs',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    if (!$this->getMachineName() || !$this->getTempstore()->get($this->getMachineName())) {
      $cached_values = $this->initValues();
    }
    else {
      $cached_values = $this->getTempstore()->get($this->getMachineName());
    }
    $operation = $this->getOperation($cached_values);
    /** @var \Drupal\Core\Form\FormInterface $operation */
    $operation = $this->classResolver->getInstanceFromDefinition($operation['form']);
    return $operation->getFormId();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    // Get the current form operation.
    $operation = $this->getOperation($cached_values);
    $form = $this->customizeForm($form, $form_state);
    /** @var \Drupal\Core\Form\FormInterface $formClass */
    $formClass = $this->classResolver->getInstanceFromDefinition($operation['form']);
    // Pass include any custom values for this operation.
    if (!empty($operation['values'])) {
      $cached_values = array_merge($cached_values, $operation['values']);
      $form_state->setTemporaryValue('wizard', $cached_values);
    }
    // Build the form.
    $form = $formClass->buildForm($form, $form_state);
    if (isset($operation['title'])) {
      $form['#title'] = $operation['title'];
    }
    $form['actions'] = $this->actions($formClass, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Only perform this logic if we're moving to the next page. This prevents
    // the loss of cached values on ajax submissions.
    if ((string) $form_state->getValue('op') == (string) $this->getNextOp()) {
      $cached_values = $form_state->getTemporaryValue('wizard');
      if ($form_state->hasValue('label')) {
        $cached_values['label'] = $form_state->getValue('label');
      }
      if ($form_state->hasValue('id')) {
        $cached_values['id'] = $form_state->getValue('id');
      }
      if (is_null($this->machine_name) && !empty($cached_values['id'])) {
        $this->machine_name = $cached_values['id'];
      }
      $this->getTempstore()->set($this->getMachineName(), $cached_values);
      $next_parameters = $this->getNextParameters($cached_values);
      if (!$form_state->get('ajax')) {
        $form_state->setRedirect($this->getRouteName(), $next_parameters);
      }
      else {
        // Switch steps for ajax forms.
        if (!empty($next_parameters['step'])) {
          $this->step = $next_parameters['step'];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function populateCachedValues(array &$form, FormStateInterface $form_state) {
    $cached_values = $this->getTempstore()->get($this->getMachineName());
    if (!$cached_values) {
      $cached_values = $form_state->getTemporaryValue('wizard');
      if (!$cached_values) {
        $cached_values = $this->initValues();
        $form_state->setTemporaryValue('wizard', $cached_values);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function previous(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $prev_parameters = $this->getPreviousParameters($cached_values);
    // Redirect for non ajax forms.
    if (!$form_state->get('ajax')) {
      $form_state->setRedirect($this->getRouteName(), $prev_parameters);
    }
    else {
      // Switch step for ajax forms.
      if (!empty($prev_parameters['step'])) {
        $this->step = $prev_parameters['step'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function finish(array &$form, FormStateInterface $form_state) {
    $this->getTempstore()->delete($this->getMachineName());
  }

  /**
   * Helper function for generating default form elements.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  protected function customizeForm(array $form, FormStateInterface $form_state) {
    // Setup the step rendering theme element.
    $prefix = [
      '#theme' => ['ctools_wizard_trail'],
      '#wizard' => $this,
      '#cached_values' => $form_state->getTemporaryValue('wizard'),
    ];
    $form['#prefix'] = $this->renderer->render($prefix);
    return $form;
  }

  /**
   * Generates action elements for navigating between the operation steps.
   *
   * @param \Drupal\Core\Form\FormInterface $form_object
   *   The current operation form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   */
  protected function actions(FormInterface $form_object, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $operations = $this->getOperations($cached_values);
    $step = $this->getStep($cached_values);
    $operation = $operations[$step];

    $steps = array_keys($operations);
    // Slice to find the operations that occur before the current operation.
    $before = array_slice($operations, 0, array_search($step, $steps));
    // Slice to find the operations that occur after the current operation.
    $after = array_slice($operations, array_search($step, $steps) + 1);

    $actions = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#button_type' => 'primary',
        '#validate' => [
          '::populateCachedValues',
          [$form_object, 'validateForm'],
        ],
        '#submit' => [
          [$form_object, 'submitForm'],
        ],
      ],
    ];

    // Add any submit or validate functions for the step and the global ones.
    if (isset($operation['validate'])) {
      $actions['submit']['#validate'] = array_merge($actions['submit']['#validate'], $operation['validate']);
    }
    $actions['submit']['#validate'][] = '::validateForm';
    if (isset($operation['submit'])) {
      $actions['submit']['#submit'] = array_merge($actions['submit']['#submit'], $operation['submit']);
    }
    $actions['submit']['#submit'][] = '::submitForm';

    if ($form_state->get('ajax')) {
      // Ajax submissions need to submit to the current step, not "next".
      $parameters = $this->getNextParameters($cached_values);
      $parameters['step'] = $this->getStep($cached_values);
      $actions['submit']['#ajax'] = [
        'callback' => '::ajaxSubmit',
        'url' => Url::fromRoute($this->getRouteName(), $parameters),
        'options' => ['query' => $this->getRequest()->query->all() + [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]],
      ];
    }

    // If there are steps before this one, label the button "previous"
    // otherwise do not display a button.
    if ($before) {
      $actions['previous'] = [
        '#type' => 'submit',
        '#value' => $this->t('Previous'),
        '#validate' => [
          [$this, 'populateCachedValues'],
        ],
        '#submit' => [
          [$this, 'previous'],
        ],
        '#limit_validation_errors' => [],
        '#weight' => -10,
      ];
      if ($form_state->get('ajax')) {
        // Ajax submissions need to submit to the current step, not "previous".
        $parameters = $this->getPreviousParameters($cached_values);
        $parameters['step'] = $this->getStep($cached_values);
        $actions['previous']['#ajax'] = [
          'callback' => '::ajaxPrevious',
          'url' => Url::fromRoute($this->getRouteName(), $parameters),
          'options' => ['query' => $this->getRequest()->query->all() + [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE]],
        ];
      }
    }

    // If there are not steps after this one, label the button "Finish".
    if (!$after) {
      $actions['submit']['#value'] = $this->t('Finish');
      $actions['submit']['#submit'][] = [$this, 'finish'];
      if ($form_state->get('ajax')) {
        $actions['submit']['#ajax']['callback'] = [$this, 'ajaxFinish'];
      }
    }

    return $actions;
  }

  public function ajaxSubmit(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $response = new AjaxResponse();
    $parameters = $this->getNextParameters($cached_values);
    $response->addCommand(new OpenModalWizardCommand($this, $this->getTempstoreId(), $parameters));
    return $response;
  }

  public function ajaxPrevious(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $response = new AjaxResponse();
    $parameters = $this->getPreviousParameters($cached_values);
    $response->addCommand(new OpenModalWizardCommand($this, $this->getTempstoreId(), $parameters));
    return $response;
  }

  public function ajaxFinish(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  public function getRouteName() {
    return $this->routeMatch->getRouteName();
  }

}
