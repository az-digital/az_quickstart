<?php

namespace Drupal\devel_generate\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\devel_generate\DevelGenerateBaseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that allows privileged users to generate entities.
 */
class DevelGenerateForm extends FormBase {

  /**
   * The manager to be used for instantiating plugins.
   */
  protected PluginManagerInterface $develGenerateManager;

  /**
   * Logger service.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->develGenerateManager = $container->get('plugin.manager.develgenerate');
    $instance->messenger = $container->get('messenger');
    $instance->logger = $container->get('logger.channel.devel_generate');
    $instance->requestStack = $container->get('request_stack');
    $instance->stringTranslation = $container->get('string_translation');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'devel_generate_form_' . $this->getPluginIdFromRequest();
  }

  /**
   * Returns the value of the param _plugin_id for the current request.
   *
   * @see \Drupal\devel_generate\Routing\DevelGenerateRouteSubscriber
   */
  protected function getPluginIdFromRequest() {
    $request = $this->requestStack->getCurrentRequest();
    return $request->get('_plugin_id');
  }

  /**
   * Returns a DevelGenerate plugin instance for a given plugin id.
   *
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   *
   * @return \Drupal\devel_generate\DevelGenerateBaseInterface
   *   A DevelGenerate plugin instance.
   */
  public function getPluginInstance(string $plugin_id): DevelGenerateBaseInterface {
    return $this->develGenerateManager->createInstance($plugin_id, []);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $plugin_id = $this->getPluginIdFromRequest();
    $instance = $this->getPluginInstance($plugin_id);
    $form = $instance->settingsForm($form, $form_state);
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $plugin_id = $this->getPluginIdFromRequest();
    $instance = $this->getPluginInstance($plugin_id);
    $instance->settingsFormValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    try {
      $plugin_id = $this->getPluginIdFromRequest();
      $instance = $this->getPluginInstance($plugin_id);
      $instance->generate($form_state->getValues());
    }
    catch (\Exception $e) {
      $this->logger->error($this->t('Failed to generate elements due to "%error".', ['%error' => $e->getMessage()]));
      $this->messenger->addMessage($this->t('Failed to generate elements due to "%error".', ['%error' => $e->getMessage()]));
    }
  }

}
