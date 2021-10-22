<?php

namespace Drupal\az_core\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for custom AZ Quickstart settings.
 */
class QuickstartCoreSettingsForm extends ConfigFormBase {

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
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteBuilderInterface $route_builder, RouteProviderInterface $route_provider) {
    parent::__construct($config_factory);

    $this->routeBuilder = $route_builder;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.builder'),
      $container->get('router.route_provider')
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

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validates the monitoring page path.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param Drupal\Core\Form\FormStateInterface $form_state
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
      ->save();

    $this->config('az_core.settings')
      ->set('monitoring_page.enabled', $form_state->getValue('monitoring_page_enabled'))
      ->set('monitoring_page.path', $form_state->getValue('monitoring_page_path'))
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
