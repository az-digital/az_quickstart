<?php

namespace Drupal\az_core\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for custom AZ Quickstart settings.
 */
class QuickstartCoreSettingsForm extends ConfigFormBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a QuickstartCoreSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface|null $typedConfigManager, RouteBuilderInterface $route_builder, RouteProviderInterface $route_provider, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context) {
    parent::__construct($config_factory, $typedConfigManager);

    $this->routeBuilder = $route_builder;
    $this->routeProvider = $route_provider;
    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('router.builder'),
      $container->get('router.route_provider'),
      $container->get('path_alias.manager'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_core_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['az_core.settings', 'system.site'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $az_core_config = $this->config('az_core.settings');
    $site_config = $this->config('system.site');

    $form['clear_cache'] = [
      '#type' => 'details',
      '#title' => t('Clear cache'),
      '#open' => TRUE,
    ];

    $form['clear_cache']['clear'] = [
      '#type' => 'submit',
      '#value' => t('Clear all caches'),
      '#submit' => ['::submitCacheClear'],
    ];

    $form['site_name'] = [
      '#type' => 'textfield',
      '#title' => t('Site name'),
      '#default_value' => $site_config->get('name'),
      '#required' => TRUE,
    ];

    $form['front_page'] = [
      '#type' => 'details',
      '#title' => $this->t('Front page'),
      '#open' => TRUE,
    ];

    $front_page = $site_config->get('page.front') !== '/user/login' ? $this->aliasManager->getAliasByPath($site_config->get('page.front')) : '';
    $form['front_page']['site_frontpage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default front page'),
      '#default_value' => $front_page,
      '#size' => 40,
      '#description' => $this->t('Optionally, specify a relative URL to display as the front page. Leave blank to display the default front page.'),
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
    ];

    $form['error_page'] = [
      '#type' => 'details',
      '#title' => $this->t('Error pages'),
      '#open' => TRUE,
    ];

    $form['error_page']['site_403'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default 403 (access denied) page'),
      '#default_value' => $site_config->get('page.403'),
      '#size' => 40,
      '#description' => $this->t('This page is displayed when the requested document is denied to the current user. Leave blank to display a generic "access denied" page.'),
    ];

    $form['error_page']['site_404'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default 404 (not found) page'),
      '#default_value' => $site_config->get('page.404'),
      '#size' => 40,
      '#description' => $this->t('This page is displayed when no other content matches the requested document. Leave blank to display a generic "page not found" page.'),
    ];

    $form['monitoring_page'] = [
      '#type' => 'details',
      '#title' => t('Monitoring page'),
      '#open' => TRUE,
      '#access' => $this->currentUser()->hasPermission('administer site configuration'),
    ];

    $form['monitoring_page']['monitoring_page_enabled'] = [
      '#title' => t('Enable monitoring page'),
      '#type' => 'checkbox',
      '#description' => t("Provides an uncacheable page intended for use with uptime monitoring tools to check the health of the site, bypassing any edge cache layer (e.g. varnish)."),
      '#default_value' => $az_core_config->get('monitoring_page.enabled'),
    ];

    $form['monitoring_page']['monitoring_page_path'] = [
      '#title' => t('Monitoring page path'),
      '#type' => 'textfield',
      '#description' => t('Path for monitoring page.'),
      '#default_value' => $az_core_config->get('monitoring_page.path'),
      '#element_validate' => ['::monitoringPagePathValidate'],
      '#states' => [
        'visible' => [':input[name="monitoring_page_enabled"]' => ['checked' => TRUE]],
        'enabled' => [':input[name="monitoring_page_enabled"]' => ['checked' => TRUE]],
        'required' => [':input[name="monitoring_page_enabled"]' => ['checked' => TRUE]],
      ],
    ];

    $form['enterprise_attributes'] = [
      '#type' => 'details',
      '#title' => t('Enterprise attributes'),
      '#open' => FALSE,
      '#access' => $this->currentUser()->hasPermission('administer site configuration'),
    ];

    $form['enterprise_attributes']['enterprise_attributes_locked'] = [
      '#title' => t('Enterprise attributes edits prohibited'),
      '#type' => 'checkbox',
      '#description' => t("With this setting enabled, edits to the enterprise attributes taxonomy will be prohibited (recommended)."),
      '#default_value' => $az_core_config->get('enterprise_attributes.locked'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for empty front page path.
    if ($form_state->isValueEmpty('site_frontpage')) {
      // Set to default "user/login".
      $form_state->setValueForElement($form['front_page']['site_frontpage'], '/user/login');
    }
    else {
      // Get the normal path of the front page.
      $form_state->setValueForElement($form['front_page']['site_frontpage'], $this->aliasManager->getPathByAlias($form_state->getValue('site_frontpage')));
    }
    // Validate front page path.
    if (($value = $form_state->getValue('site_frontpage')) && $value[0] !== '/') {
      $form_state->setErrorByName('site_frontpage', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('site_frontpage')]));

    }
    if (!$this->pathValidator->isValid($form_state->getValue('site_frontpage'))) {
      $form_state->setErrorByName('site_frontpage', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_frontpage')]));
    }
    // Get the normal paths of both error pages.
    if (!$form_state->isValueEmpty('site_403')) {
      $form_state->setValueForElement($form['error_page']['site_403'], $this->aliasManager->getPathByAlias($form_state->getValue('site_403')));
    }
    if (!$form_state->isValueEmpty('site_404')) {
      $form_state->setValueForElement($form['error_page']['site_404'], $this->aliasManager->getPathByAlias($form_state->getValue('site_404')));
    }
    if (($value = $form_state->getValue('site_403')) && $value[0] !== '/') {
      $form_state->setErrorByName('site_403', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('site_403')]));
    }
    if (($value = $form_state->getValue('site_404')) && $value[0] !== '/') {
      $form_state->setErrorByName('site_404', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('site_404')]));
    }
    // Validate 403 error path.
    if (!$form_state->isValueEmpty('site_403') && !$this->pathValidator->isValid($form_state->getValue('site_403'))) {
      $form_state->setErrorByName('site_403', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_403')]));
    }
    // Validate 404 error path.
    if (!$form_state->isValueEmpty('site_404') && !$this->pathValidator->isValid($form_state->getValue('site_404'))) {
      $form_state->setErrorByName('site_404', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_404')]));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Validates the monitoring page path.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function monitoringPagePathValidate(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if ($form_state->getValue('monitoring_page_enabled')) {
      $submitted_value = $form_state->getValue('monitoring_page_path');
      if (empty($submitted_value)) {
        $form_state->setError($element, t('A monitoring page path must be provided.'));
      }

      $path = strtolower(trim(trim($submitted_value), " \\/"));
      if (!empty($path) && $submitted_value !== $element['#default_value']) {
        if ($this->routeProvider->getRoutesByPattern($path)->count()) {
          $form_state->setError($element, t('The path is already in use.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('system.site')
      ->set('name', $form_state->getValue('site_name'))
      ->set('page.front', $form_state->getValue('site_frontpage'))
      ->set('page.403', $form_state->getValue('site_403'))
      ->set('page.404', $form_state->getValue('site_404'))
      ->save();

    $this->config('az_core.settings')
      ->set('monitoring_page.enabled', $form_state->getValue('monitoring_page_enabled'))
      ->set('monitoring_page.path', $form_state->getValue('monitoring_page_path'))
      ->set('enterprise_attributes.locked', $form_state->getValue('enterprise_attributes_locked'))
      ->save();

    $this->routeBuilder->setRebuildNeeded();

    parent::submitForm($form, $form_state);
  }

  /**
   * Clears the caches.
   */
  public function submitCacheClear(array &$form, FormStateInterface $form_state) {
    drupal_flush_all_caches();
    $this->messenger()->addStatus($this->t('Caches cleared.'));
  }

}
