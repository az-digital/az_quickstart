<?php

namespace Drupal\az_cas\Form;

use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for custom AZ CAS module settings.
 */
class AzCasSettingsForm extends ConfigFormBase {

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * The cache factory.
   *
   * @var \Drupal\Core\Cache\CacheFactoryInterface
   */
  protected $cacheFactory;

  /**
   * Constructs a AzCasSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   * @param \Drupal\Core\Cache\CacheFactoryInterface $cache_factory
   *   The cache factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface|null $typedConfigManager, RouteBuilderInterface $route_builder, CacheFactoryInterface $cache_factory) {
    parent::__construct($config_factory, $typedConfigManager);

    $this->routeBuilder = $route_builder;
    $this->cacheFactory = $cache_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('router.builder'),
      $container->get('cache_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_cas_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['az_cas.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['disable_login_form'] = [
      '#title' => t("Disable login form"),
      '#type' => 'checkbox',
      '#description' => t("Disables the default user login form provided by Drupal core."),
      '#config_target' => 'az_cas.settings:disable_login_form',
    ];

    $form['disable_admin_add_user_button'] = [
      '#title' => t("Disable 'Add user' button"),
      '#type' => 'checkbox',
      '#description' => t("Removes button for adding non-CAS users in admin interface."),
      '#default_value' => $az_cas_config->get('disable_admin_add_user_button'),
    ];

    $form['disable_password_recovery_link'] = [
      '#title' => t("Disable 'request new password' form"),
      '#type' => 'checkbox',
      '#description' => t("Disables the default password recovery functionality provided by Drupal core."),
      '#config_target' => 'az_cas.settings:disable_password_recovery_link',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->routeBuilder->setRebuildNeeded();
    $this->cacheFactory->get('render')->deleteAll();
    $this->cacheFactory->get('discovery')->deleteAll();

    parent::submitForm($form, $form_state);
  }

}
