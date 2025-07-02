<?php

namespace Drupal\webform\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformYaml;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission debug handler.
 *
 * @WebformHandler(
 *   id = "debug",
 *   label = @Translation("Debug"),
 *   category = @Translation("Development"),
 *   description = @Translation("Debug webform submission."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class DebugWebformHandler extends WebformHandlerBase {

  /**
   * Format YAML.
   */
  const FORMAT_YAML = 'yaml';

  /**
   * Format JSON.
   */
  const FORMAT_JSON = 'json';

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'format' => 'yaml',
      'submission' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $settings = $this->getSettings();
    switch ($settings['format']) {
      case static::FORMAT_JSON:
        $settings['format'] = $this->t('JSON');
        break;

      case static::FORMAT_YAML:
      default:
        $settings['format'] = $this->t('YAML');
        break;
    }
    return [
      '#settings' => $settings,
    ] + parent::getSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['debug_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Debug settings'),
    ];
    $form['debug_settings']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Data format'),
      '#options' => [
        static::FORMAT_YAML => $this->t('YAML'),
        static::FORMAT_JSON => $this->t('JSON'),
      ],
      '#default_value' => $this->configuration['format'],
    ];
    $form['debug_settings']['submission'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include submission properties'),
      '#description' => $this->t('If checked, all submission properties and values  will be included in the displayed debug information. This includes sid, created, updated, completed, and more.'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['submission'],
    ];
    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->applyFormStateToConfiguration($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $settings = $this->getSettings();

    $data = ($settings['submission'])
      ? $webform_submission->toArray(TRUE)
      : $webform_submission->getData();
    WebformElementHelper::convertRenderMarkupToStrings($data);

    $label = ($settings['submission'])
      ? $this->t('Submitted properties and values are:')
      : $this->t('Submitted values are:');

    $build = [
      'label' => ['#markup' => $label],
      'data' => [
        '#markup' => ($settings['format'] === static::FORMAT_JSON)
          ? json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_PRETTY_PRINT)
          : WebformYaml::encode($data),
        '#prefix' => '<pre>',
        '#suffix' => '</pre>',
      ],
    ];
    $message = $this->renderer->renderPlain($build);

    $this->messenger()->addWarning($message);
  }

}
