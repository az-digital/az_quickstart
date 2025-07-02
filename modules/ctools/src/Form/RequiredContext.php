<?php

namespace Drupal\ctools\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Required Context Form.
 */
abstract class RequiredContext extends FormBase {

  /**
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * The builder of form.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * @var string
   */
  protected $machine_name;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('typed_data_manager'),
      $container->get('form_builder')
    );
  }

  /**
   * Required Context Form constructor.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $typed_data_manager
   *   The Typed Data Manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The Form Builder.
   */
  public function __construct(PluginManagerInterface $typed_data_manager, FormBuilderInterface $form_builder) {
    $this->typedDataManager = $typed_data_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ctools_required_context_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $this->machine_name = $cached_values['id'];
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $options = [];
    foreach ($this->typedDataManager->getDefinitions() as $plugin_id => $definition) {
      $options[$plugin_id] = (string) $definition['label'];
    }
    $form['items'] = [
      '#type' => 'markup',
      '#prefix' => '<div id="configured-contexts">',
      '#suffix' => '</div>',
      '#theme' => 'table',
      '#header' => [$this->t('Information'), $this->t('Description'), $this->t('Operations')],
      '#rows' => $this->renderContexts($cached_values),
      '#empty' => $this->t('No required contexts have been configured.'),
    ];
    $form['contexts'] = [
      '#type' => 'select',
      '#options' => $options,
    ];
    $form['add'] = [
      '#type' => 'submit',
      '#name' => 'add',
      '#value' => $this->t('Add required context'),
      '#ajax' => [
        'callback' => [$this, 'add'],
        'event' => 'click',
      ],
      '#submit' => [
        'callback' => [$this, 'submitform'],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    [$route_name, $route_parameters] = $this->getOperationsRouteInfo($cached_values, $this->machine_name, $form_state->getValue('contexts'));
    $form_state->setRedirect($route_name . '.edit', $route_parameters);
  }

  /**
   * Custom ajax form submission handler.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function add(array &$form, FormStateInterface $form_state) {
    $context = $form_state->getValue('contexts');
    $content = $this->formBuilder->getForm($this->getContextClass(), $context, $this->getTempstoreId(), $this->machine_name);
    $content['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand($this->t('Configure Required Context'), $content, ['width' => '700']));
    return $response;
  }

  /**
   * Render The contexts in the form.
   *
   * @param $cached_values
   *   Cached context values.
   *
   * @return array
   *   The rendered contexts.
   */
  public function renderContexts($cached_values) {
    $configured_contexts = [];
    foreach ($this->getContexts($cached_values) as $row => $context) {
      [$plugin_id, $label, $machine_name, $description] = array_values($context);
      [$route_name, $route_parameters] = $this->getOperationsRouteInfo($cached_values, $cached_values['id'], $row);
      $build = [
        '#type' => 'operations',
        '#links' => $this->getOperations($route_name, $route_parameters),
      ];
      $configured_contexts[] = [
        $this->t('<strong>Label:</strong> @label<br /> <strong>Type:</strong> @type', ['@label' => $label, '@type' => $plugin_id]),
        $this->t('@description', ['@description' => $description]),
        'operations' => [
          'data' => $build,
        ],
      ];
    }
    return $configured_contexts;
  }

  /**
   * Retrieve Form Operations
   *
   * @param $route_name_base
   *   The base route name.
   * @param array $route_parameters
   *   Route Parameters.
   *
   * @return array
   *   The available operations.
   */
  protected function getOperations($route_name_base, array $route_parameters = []) {
    $operations['edit'] = [
      'title' => $this->t('Edit'),
      'url' => new Url($route_name_base . '.edit', $route_parameters),
      'weight' => 10,
      'attributes' => [
        'class' => ['use-ajax'],
        'data-accepts' => 'application/vnd.drupal-modal',
        'data-dialog-options' => json_encode([
          'width' => 700,
        ]),
      ],
      'ajax' => [
        '',
      ],
    ];
    $route_parameters['id'] = $route_parameters['context'];
    $operations['delete'] = [
      'title' => $this->t('Delete'),
      'url' => new Url($route_name_base . '.delete', $route_parameters),
      'weight' => 100,
      'attributes' => [
        'class' => ['use-ajax'],
        'data-accepts' => 'application/vnd.drupal-modal',
        'data-dialog-options' => json_encode([
          'width' => 700,
        ]),
      ],
    ];
    return $operations;
  }

  /**
   * Return a subclass of '\Drupal\ctools\Form\ContextConfigure'.
   *
   * The ContextConfigure class is designed to be subclassed with custom route
   * information to control the modal/redirect needs of your use case.
   *
   * @return string
   *   The Context Class.
   */
  abstract protected function getContextClass();

  /**
   * Provide the tempstore id for your specified use case.
   *
   * @return string
   *   The Tempstore ID.
   */
  abstract protected function getTempstoreId();

  /**
   * Document the route name and parameters for edit/delete context operations.
   *
   * The route name returned from this method is used as a "base" to which
   * ".edit" and ".delete" are appended in the getOperations() method.
   * Subclassing '\Drupal\ctools\Form\ContextConfigure' and
   * '\Drupal\ctools\Form\RequiredContextDelete' should set you up for using
   * this approach quite seamlessly.
   *
   * @param mixed $cached_values
   *  The Cached Values.
   * @param string $machine_name
   *  The form machine name.
   * @param string $row
   *  The form row to operate on.
   *
   * @return array
   *   In the format of
   *   return ['route.base.name',
   *     ['machine_name' => $machine_name, 'context' => $row]];
   */
  abstract protected function getOperationsRouteInfo(mixed $cached_values, string $machine_name, string $row);

  /**
   * Custom logic for retrieving the contexts array from cached_values.
   *
   * @param array $cached_values
   *   The Cached Values.
   *
   * @return array
   *   The Contexts.
   */
  abstract protected function getContexts(array $cached_values);

}
