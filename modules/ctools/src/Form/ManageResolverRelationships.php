<?php

namespace Drupal\ctools\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ctools\TypedDataResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provider manage resolver relationships.
 */
abstract class ManageResolverRelationships extends FormBase {

  /**
   * The machine name.
   *
   * @var string
   */
  protected $machine_name;

  /**
   * An array of property types that are eligible as relationships.
   *
   * @var array
   */
  protected $property_types = [];

  /**
   * The typed data resolver.
   *
   * @var \Drupal\ctools\TypedDataResolver
   */
  protected $typedDataResolver;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Constructs a new ManageResolverRelationships object.
   *
   * @param \Drupal\ctools\TypedDataResolver $ctools_typed_data_resolver
   *   The typed data resolver.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(TypedDataResolver $ctools_typed_data_resolver, FormBuilderInterface $form_builder) {
    $this->typedDataResolver = $ctools_typed_data_resolver;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ctools.typed_data.resolver'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ctools_manage_resolver_relationships_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $this->machine_name = $cached_values['id'];
    $form['items'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="configured-relationships">',
      '#suffix' => '</div>',
      '#theme' => 'table',
      '#header' => [
        $this->t('Context ID'), $this->t('Label'), $this->t('Data Type'), $this->t('Options'),
      ],
      '#rows' => $this->renderRows($cached_values),
      '#empty' => $this->t('No relationships have been added.'),
    ];

    $form['relationships'] = [
      '#type' => 'select',
      '#title' => $this->t('Add a relationship'),
      '#options' => $this->getAvailableRelationships($cached_values),
    ];
    $form['add_relationship'] = [
      '#type' => 'submit',
      '#name' => 'add',
      '#value' => $this->t('Add Relationship'),
      '#ajax' => [
        'callback' => [$this, 'addRelationship'],
        'event' => 'click',
      ],
      '#submit' => [
        'callback' => [$this, 'submitForm'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] == 'add') {
      $cached_values = $form_state->getTemporaryValue('wizard');
      [, $route_parameters] = $this->getRelationshipOperationsRouteInfo($cached_values, $this->machine_name, $form_state->getValue('relationships'));
      $form_state->setRedirect($this->getAddRoute($cached_values), $route_parameters);
    }
  }

  /**
   * Add relationship.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Stores information about the state of a form.
   */
  public function addRelationship(array &$form, FormStateInterface $form_state) {
    $relationship = $form_state->getValue('relationships');
    $content = $this->formBuilder->getForm($this->getContextClass(), $relationship, $this->getTempstoreId(), $this->machine_name);
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $cached_values = $form_state->getTemporaryValue('wizard');
    [, $route_parameters] = $this->getRelationshipOperationsRouteInfo($cached_values, $this->machine_name, $relationship);
    $route_name = $this->getAddRoute($cached_values);
    $route_options = [
      'query' => [
        FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
      ],
    ];
    $url = Url::fromRoute($route_name, $route_parameters, $route_options);
    $content['submit']['#attached']['drupalSettings']['ajax'][$content['submit']['#id']]['url'] = $url->toString();
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($this->t('Configure Relationship'), $content, ['width' => '700']));
    return $response;
  }

  /**
   * Get the accesssible relationships.
   *
   * @param mixed $cached_values
   *   The arbitrary value from temporary storage.
   */
  protected function getAvailableRelationships($cached_values) {
    /** @var \Drupal\ctools\TypedDataResolver $resolver */
    $resolver = $this->typedDataResolver;
    return $resolver->getTokensForContexts($this->getContexts($cached_values));
  }

  /**
   * Render the rows.
   *
   * @param mixed $cached_values
   *   The arbitrary value from temporary storage.
   *
   * @return array
   *   The array context.
   */
  protected function renderRows($cached_values) {
    $contexts = [];
    foreach ($this->getContexts($cached_values) as $row => $context) {
      [$route_name, $route_parameters] = $this->getRelationshipOperationsRouteInfo($cached_values, $this->machine_name, $row);
      $build = [
        '#type' => 'operations',
        '#links' => $this->getOperations($cached_values, $row, $route_name, $route_parameters),
      ];
      $contexts[$row] = [
        $row,
        $context->getContextDefinition()->getLabel(),
        $context->getContextDefinition()->getDataType(),
        'operations' => [
          'data' => $build,
        ],
      ];
    }
    return $contexts;
  }

  /**
   * Get the operations.
   *
   * @param mixed $cached_values
   *   The arbitrary value from temporary storage.
   * @param string $row
   *   The row.
   * @param string $route_name_base
   *   The base of route.
   * @param array $route_parameters
   *   The parameters of route.
   *
   * @return mixed
   *   The operations.
   */
  protected function getOperations($cached_values, $row, $route_name_base, array $route_parameters = []) {
    // Base contexts will not be a :
    // separated and generated relationships should have 3 parts.
    if (count(explode(':', $row)) < 2) {
      return [];
    }
    $operations['edit'] = [
      'title' => $this->t('Edit'),
      'url' => new Url($route_name_base . '.edit', $route_parameters),
      'weight' => 10,
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];
    $route_parameters['id'] = $route_parameters['context'];
    $operations['delete'] = [
      'title' => $this->t('Delete'),
      'url' => new Url($route_name_base . '.delete', $route_parameters),
      'weight' => 100,
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ];
    return $operations;
  }

  /**
   * Return a subclass of '\Drupal\ctools\Form\ResolverRelationshipConfigure'.
   *
   * The ConditionConfigure class is designed to be subclassed with custom
   * route information to control the modal/redirect needs of your use case.
   *
   * @return string
   *   Return a subclass of '\Drupal\ctools\Form\ResolverRelationshipConfigure'.
   */
  abstract protected function getContextClass($cached_values);

  /**
   * The route to which relationship 'add' actions should submit.
   *
   * @param mixed $cached_values
   *   The arbitrary value from temporary storage.
   *
   * @return string
   *   The route of add action.
   */
  abstract protected function getAddRoute($cached_values);

  /**
   * Provide the tempstore id for your specified use case.
   *
   * @return string
   *   The id of tempstore.
   */
  abstract protected function getTempstoreId();

  /**
   * Gets the context value.
   *
   * @param mixed $cached_values
   *   The arbitrary value from temporary storage.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   Context data and definitions for plugins supporting
   *    caching and return docs.
   */
  abstract protected function getContexts($cached_values);

  /**
   * Get relationship operations of route info.
   *
   * @param mixed $cached_values
   *   The arbitrary value from temporary storage.
   * @param string $machine_name
   *   The machine name.
   * @param string $row
   *   The row.
   *
   * @return array
   *   The array of relationship operations.
   */
  abstract protected function getRelationshipOperationsRouteInfo($cached_values, $machine_name, $row);

}
