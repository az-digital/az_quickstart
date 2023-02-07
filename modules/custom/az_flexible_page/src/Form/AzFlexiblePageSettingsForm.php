<?php

namespace Drupal\az_flexible_page\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for custom AZ Flexible Page module settings.
 */
class AzFlexiblePageSettingsForm extends ConfigFormBase {

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Constructs a AzFlexiblePageSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteBuilderInterface $route_builder) {
    parent::__construct($config_factory);

    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_flexible_page_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['az_flexible_page.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $az_flexible_page_config = $this->config('az_flexible_page.settings');

    $form['marketing_page']['marketing_page_styles_enabled'] = [
      '#title' => t('Enable Marketing Campaign Page styles'),
      '#type' => 'checkbox',
      '#description' => t('Allows Content Administrators to select a Marketing Campaign Page style for individual pages. These styles hide the navigation menu and other page regions to display the page as a landing page. See <a href="https://quickstart.arizona.edu/pages">Adding Pages</a> for details about each style.'),
      '#default_value' => $az_flexible_page_config->get('marketing_page_styles.enabled'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('az_flexible_page.settings')
      ->set('marketing_page_styles.enabled', $form_state->getValue('marketing_page_styles_enabled'))
      ->save();

    $this->routeBuilder->setRebuildNeeded();

    parent::submitForm($form, $form_state);
  }

}
