<?php

namespace Drupal\az_cas\Form;

use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Url;
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
    return ['az_cas.settings', 'cas.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $az_cas_config = $this->config('az_cas.settings');
    $cas_config = $this->config('cas.settings');

    $form['login_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Login Settings'),
      '#description' => $this->t('Configure how Drupal login forms interact with CAS.'),
    ];

    $form['login_settings']['disable_login_form'] = [
      '#title' => $this->t("Disable login form"),
      '#type' => 'checkbox',
      '#description' => $this->t("Disables the default user login form provided by Drupal core."),
      '#default_value' => $az_cas_config->get('disable_login_form'),
    ];

    $form['login_settings']['disable_password_recovery_link'] = [
      '#title' => $this->t("Disable 'request new password' form"),
      '#type' => 'checkbox',
      '#description' => $this->t("Disables the default password recovery functionality provided by Drupal core."),
      '#default_value' => $az_cas_config->get('disable_password_recovery_link'),
    ];

    $form['admin_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Admin Settings'),
      '#description' => $this->t('Configure how CAS interacts with the admin interface.'),
    ];

    $form['admin_settings']['disable_admin_add_user_button'] = [
      '#title' => $this->t("Disable 'Add user' button"),
      '#type' => 'checkbox',
      '#description' => $this->t("Removes button for adding non-CAS users in admin interface."),
      '#default_value' => $az_cas_config->get('disable_admin_add_user_button'),
    ];

    $form['guest_authentication'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Guest Authentication'),
    ];

    $form['guest_authentication']['guest_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable guest authentication mode'),
      '#description' => $this->t('Allows restricting access to certain paths to anyone with a valid NetID without creating Drupal user accounts.'),
      '#default_value' => $az_cas_config->get('guest_mode'),
    ];

    $form['guest_authentication']['guest_auth_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Guest Authentication Settings'),
      '#open' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="guest_mode"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Add notice about auto_register requirement.
    $cas_settings_url = Url::fromRoute('cas.settings')->toString() . '#edit-user-accounts';
    $form['guest_authentication']['guest_auth_settings']['auto_register_notice'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--warning">' . $this->t('Guest authentication requires the CAS module\'s "Auto register users" setting to be enabled. This setting will be automatically enabled when guest mode is activated. <a href="@cas_settings_url">View CAS settings</a>', ['@cas_settings_url' => $cas_settings_url]) . '</div>',
      '#states' => [
        'visible' => [
          ':input[name="guest_mode"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['guest_authentication']['guest_auth_settings']['guest_auth_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Guest authentication paths'),
      '#default_value' => implode("\n", $az_cas_config->get('guest_auth_paths') ?: []),
      '#description' => $this->t('Specify pages that should be restricted to authenticated NetID users without requiring Drupal accounts. Enter one path per line. The * character is a wildcard. An example path is /content/* for all content pages. &lt;front&gt; is the front page.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get guest mode value.
    $guest_mode = $form_state->getValue('guest_mode');

    // Process guest authentication paths if guest mode is enabled.
    $guest_auth_paths = [];
    if ($guest_mode) {
      $guest_auth_paths = array_filter(preg_split('/[\n\r]+/', $form_state->getValue('guest_auth_paths')));

      // Ensure CAS auto_register is enabled when guest mode is enabled.
      $this->config('cas.settings')
        ->set('user_accounts.auto_register', TRUE)
        ->save();
    }

    // Save AZ CAS settings.
    $this->config('az_cas.settings')
      ->set('disable_login_form', $form_state->getValue('disable_login_form'))
      ->set('disable_admin_add_user_button', $form_state->getValue('disable_admin_add_user_button'))
      ->set('disable_password_recovery_link', $form_state->getValue('disable_password_recovery_link'))
      ->set('guest_mode', $guest_mode)
      ->set('guest_auth_paths', $guest_auth_paths)
      ->save();

    $this->routeBuilder->setRebuildNeeded();
    $this->cacheFactory->get('render')->deleteAll();
    $this->cacheFactory->get('discovery')->deleteAll();

    parent::submitForm($form, $form_state);
  }

}
