<?php

namespace Drupal\ctools\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Core\Url;
use Drupal\ctools\TypedDataResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manage Context Form.
 */
abstract class ManageContext extends FormBase {

  /**
   * The machine name of the wizard we're working with.
   *
   * @var string
   */
  protected $machine_name;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The typed data resolver.
   *
   * @var \Drupal\ctools\TypedDataResolver
   */
  protected $typedDataResolver;

  /**
   * An array of property types that are eligible as relationships.
   *
   * @var array
   */
  protected $property_types = [];

  /**
   * A property for controlling usage of relationships in an implementation.
   *
   * @var bool
   */
  protected $relationships = TRUE;

  /**
   * ManageContext constructor.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The typed data manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\ctools\TypedDataResolver $ctools_typed_data_resolver
   *   The typed data resolver.
   */
  public function __construct(TypedDataManagerInterface $typed_data_manager, FormBuilderInterface $form_builder, TypedDataResolver $ctools_typed_data_resolver) {
    $this->typedDataManager = $typed_data_manager;
    $this->formBuilder = $form_builder;
    $this->typedDataResolver = $ctools_typed_data_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('typed_data_manager'),
      $container->get('form_builder'),
      $container->get('ctools.typed_data.resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ctools_manage_context_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $this->machine_name = $cached_values['id'];
    $form['items'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="configured-contexts">',
      '#suffix' => '</div>',
      '#theme' => 'table',
      '#header' => [$this->t('Context ID'), $this->t('Label'), $this->t('Data Type'), $this->t('Options')],
      '#rows' => $this->renderRows($cached_values),
      '#empty' => $this->t('No contexts or relationships have been added.'),
    ];
    foreach ($this->typedDataManager->getDefinitions() as $type => $definition) {
      $types[$type] = $definition['label'] . " ($type)";
      if ($definition['id'] === 'entity_revision') {
        $types[$type] .= ' (' . $this->t('Revision') . ')';
      }
    }
    if (isset($types['entity'])) {
      unset($types['entity']);
    }
    asort($types);
    $form['context'] = [
      '#type' => 'select',
      '#options' => $types,
    ];
    $form['add'] = [
      '#type' => 'submit',
      '#name' => 'add',
      '#value' => $this->t('Add new context'),
      '#ajax' => [
        'callback' => [$this, 'addContext'],
        'event' => 'click',
      ],
      '#submit' => [
        'callback' => [$this, 'submitForm'],
      ],
    ];

    $form['relationships'] = [
      '#type' => 'select',
      '#title' => $this->t('Add a relationship'),
      '#options' => $this->getAvailableRelationships($cached_values),
      '#access' => $this->relationships,
    ];
    $form['add_relationship'] = [
      '#type' => 'submit',
      '#name' => 'add_relationship',
      '#value' => $this->t('Add Relationship'),
      '#ajax' => [
        'callback' => [$this, 'addRelationship'],
        'event' => 'click',
      ],
      '#submit' => [
        'callback' => [$this, 'submitForm'],
      ],
      '#access' => $this->relationships,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] == 'add') {
      $cached_values = $form_state->getTemporaryValue('wizard');
      [, $route_parameters] = $this->getContextOperationsRouteInfo($cached_values, $this->machine_name, $form_state->getValue('context'));
      $form_state->setRedirect($this->getContextAddRoute($cached_values), $route_parameters);
    }
    if ($form_state->getTriggeringElement()['#name'] == 'add_relationship') {
      $cached_values = $form_state->getTemporaryValue('wizard');
      [, $route_parameters] = $this->getRelationshipOperationsRouteInfo($cached_values, $this->machine_name, $form_state->getValue('relationships'));
      $form_state->setRedirect($this->getRelationshipAddRoute($cached_values), $route_parameters);
    }
  }

  /**
   * Add a context.
   *
   * @param array $form
   *   The Drupal Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Form ajax repsonse.
   */
  public function addContext(array &$form, FormStateInterface $form_state) {
    $context = $form_state->getValue('context');
    $cached_values = $form_state->getTemporaryValue('wizard');
    $content = $this->formBuilder->getForm($this->getContextClass($cached_values), $context, $this->getTempstoreId(), $this->machine_name);
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    [, $route_parameters] = $this->getContextOperationsRouteInfo($cached_values, $this->machine_name, $context);
    $route_name = $this->getContextAddRoute($cached_values);
    $route_options = [
      'query' => [
        FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
      ],
    ];
    $url = Url::fromRoute($route_name, $route_parameters, $route_options);
    $content['submit']['#attached']['drupalSettings']['ajax'][$content['submit']['#id']]['url'] = $url->toString();
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($this->t('Add new context'), $content, ['width' => '700']));
    return $response;
  }

  /**
   * Add relationship form.
   *
   * @param array $form
   *   The Drupal Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Form ajax repsonse.
   */
  public function addRelationship(array &$form, FormStateInterface $form_state) {
    $relationship = $form_state->getValue('relationships');
    $cached_values = $form_state->getTemporaryValue('wizard');
    $content = $this->formBuilder->getForm($this->getRelationshipClass($cached_values), $relationship, $this->getTempstoreId(), $this->machine_name);
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    [, $route_parameters] = $this->getRelationshipOperationsRouteInfo($cached_values, $this->machine_name, $relationship);
    $route_name = $this->getRelationshipAddRoute($cached_values);
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
   * Retrieve the available relationships.
   *
   * @param array $cached_values
   *   The cached context values.
   *
   * @return mixed
   *   The available relationships.
   */
  protected function getAvailableRelationships(array $cached_values) {
    /** @var \Drupal\ctools\TypedDataResolver $resolver */
    $resolver = $this->typedDataResolver;
    return $resolver->getTokensForContexts($this->getContexts($cached_values));
  }

  /**
   * Render the Rows.
   *
   * @param array $cached_values
   *   The cached context values.
   *
   * @return array
   *   The rendered rows.
   */
  protected function renderRows(array $cached_values) {
    $contexts = [];
    foreach ($this->getContexts($cached_values) as $row => $context) {
      [$route_name, $route_parameters] = $this->getContextOperationsRouteInfo($cached_values, $this->machine_name, $row);
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
   * Get available Operations.
   *
   * @param array $cached_values
   *   The cached context values.
   * @param string $row
   *   The row operations are being fetched from.
   * @param string $route_name_base
   *   The route name.
   * @param array $route_parameters
   *   Parameters for the route.
   *
   * @return mixed
   *   The operations.
   */
  protected function getOperations(array $cached_values, string $row, string $route_name_base, array $route_parameters = []) {
    $operations = [];
    if ($this->isEditableContext($cached_values, $row)) {
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
    }
    return $operations;
  }

  /**
   * Return a subclass of '\Drupal\ctools\Form\ContextConfigure'.
   *
   * The ContextConfigure class is designed to be subclassed with custom
   * route information to control the modal/redirect needs of your use case.
   *
   * @param mixed $cached_values
   *   Cached Relationship Class values.
   *
   * @return string
   *   The context class.
   */
  abstract protected function getContextClass(mixed $cached_values);

  /**
   * Return a subclass of '\Drupal\ctools\Form\RelationshipConfigure'.
   *
   * The RelationshipConfigure class is designed to be subclassed with custom
   * route information to control the modal/redirect needs of your use case.
   *
   * @param mixed $cached_values
   *   Cached Relationship Class values.
   *
   * @return string
   *   The relationship Class.
   */
  abstract protected function getRelationshipClass(mixed $cached_values);

  /**
   * The route to which context 'add' actions should submit.
   *
   * @param mixed $cached_values
   *   Cached Route info values.
   *
   * @return string
   *   The context add route.
   */
  abstract protected function getContextAddRoute(mixed $cached_values);

  /**
   * The route to which relationship 'add' actions should submit.
   *
   * @param mixed $cached_values
   *   Cached Route info values.
   *
   * @return string
   *   Relationship Add Route.
   */
  abstract protected function getRelationshipAddRoute(mixed $cached_values);

  /**
   * Provide the tempstore id for your specified use case.
   *
   * @return string
   *   The tempstore ID.
   */
  abstract protected function getTempstoreId();

  /**
   * Returns the contexts already available in the wizard.
   *
   * @param mixed $cached_values
   *   Cached Contexts.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   The contexts.
   */
  abstract protected function getContexts($cached_values);

  /**
   * Gets the Context Operations Route info.
   *
   * @param mixed $cached_values
   *   Cached Route info values.
   * @param string $machine_name
   *   Relationship Machine Name.
   * @param string $row
   *   Context Row.
   *
   * @return array
   *   The context operations.
   */
  abstract protected function getContextOperationsRouteInfo($cached_values, $machine_name, $row);

  /**
   * Gets the Route info for Relationship Operations.
   *
   * @param mixed $cached_values
   *   Cached Route info values.
   * @param string $machine_name
   *   Relationship Machine Name.
   * @param string $row
   *   Context Row.
   *
   * @return array
   *   The operations allowed.
   */
  abstract protected function getRelationshipOperationsRouteInfo($cached_values, $machine_name, $row);

  /**
   * @param mixed $cached_values
   *  Cached context values.
   * @param string $row
   *   Context Row.
   *
   * @return bool
   *   If context is editable.
   */
  abstract protected function isEditableContext($cached_values, $row);

}
