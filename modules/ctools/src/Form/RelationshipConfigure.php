<?php

namespace Drupal\ctools\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\TypedDataResolver;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Relationship Form.
 */
abstract class RelationshipConfigure extends FormBase {

  /**
   * Tempstore Factory.
   *
   * @var \Drupal\Core\TempStore\SharedTempStoreFactory
   */
  protected $tempstore;

  /**
   * Typed Data Resolver Service.
   *
   * @var \Drupal\ctools\TypedDataResolver
   */
  protected $resolver;

  /**
   * Tempstore ID.
   *
   * @var string
   */
  protected $tempstore_id;

  /**
   * Relationship Machine Name.
   *
   * @var string
   */
  protected $machine_name;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('tempstore.shared'), $container->get('ctools.typed_data.resolver'));
  }

  /**
   * Configure Relationship Form constructor.
   *
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $tempstore
   *   Tempstore Service.
   * @param \Drupal\ctools\TypedDataResolver $resolver
   *   Typed Data Resolver Service.
   */
  public function __construct(SharedTempStoreFactory $tempstore, TypedDataResolver $resolver) {
    $this->tempstore = $tempstore;
    $this->resolver = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ctools_relationship_configure';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $context_id = NULL, $tempstore_id = NULL, $machine_name = NULL) {
    $this->tempstore_id = $tempstore_id;
    $this->machine_name = $machine_name;
    $cached_values = $this->tempstore->get($this->tempstore_id)->get($this->machine_name);

    /** @var \Drupal\Core\Plugin\Context\ContextInterface[] $contexts */
    $contexts = $this->getContexts($cached_values);
    $context_object = $this->resolver->convertTokenToContext($context_id, $contexts);
    $form['id'] = [
      '#type' => 'value',
      '#value' => $context_id,
    ];
    $form['context_object'] = [
      '#type' => 'value',
      '#value' => $context_object,
    ];
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Context label'),
      '#default_value' => !empty($contexts[$context_id]) ? $contexts[$context_id]->getContextDefinition()->getLabel() : $this->resolver->getLabelByToken($context_id, $contexts),
      '#required' => TRUE,
    ];
    $form['context_data'] = [
      '#type' => 'item',
      '#title' => $this->t('Data type'),
      '#markup' => $context_object->getContextDefinition()->getDataType(),
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
    [$route_name, $route_options] = $this->getParentRouteInfo($cached_values);
    $form_state->setRedirect($route_name, $route_options);
  }

  /**
   * Ajax Save Method.
   *
   * @param array $form
   *   Drupal Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax data in the response.
   */
  public function ajaxSave(array &$form, FormStateInterface $form_state) {
    $cached_values = $this->tempstore->get($this->tempstore_id)->get($this->machine_name);
    [$route_name, $route_parameters] = $this->getParentRouteInfo($cached_values);
    $response = new AjaxResponse();
    $url = Url::fromRoute($route_name, $route_parameters);
    $response->addCommand(new RedirectCommand($url->toString()));
    $response->addCommand(new CloseModalDialogCommand());
    return $response;
  }

  /**
   * Document the route name and parameters for redirect after submission.
   *
   * @param array $cached_values
   *   Cached Values get route info from.
   *
   * @return array In the format of
   *   In the format of
   *   return ['route.name',
   *      ['machine_name' => $this->machine_name, 'step' => 'step_name']];
   */
  abstract protected function getParentRouteInfo(array $cached_values);

  /**
   * Custom logic for setting the conditions array in cached_values.
   *
   * @param array $cached_values
   *
   * @param mixed $contexts
   *   The conditions to set within the cached values.
   *
   * @return mixed
   *   Return the $cached_values
   */
  abstract protected function setContexts(array $cached_values, mixed $contexts);

  /**
   * Custom logic for retrieving the contexts array from cached_values.
   *
   * @param array $cached_values
   *   Cached Values contexts are fetched from.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   */
  abstract protected function getContexts(array $cached_values);

}
