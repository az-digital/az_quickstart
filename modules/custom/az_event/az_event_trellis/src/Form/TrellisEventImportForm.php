<?php

namespace Drupal\az_event_trellis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Drupal\migrate\MigrateMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CourseImportForm to compute course links.
 */
class TrellisEventImportForm extends FormBase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $pluginManagerMigration;

  /**
   * The Trellis helper.
   *
   * @var \Drupal\az_event_trellis\TrellisHelper
   */
  protected $trellisHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->httpClient = $container->get('http_client');
    $instance->pluginManagerMigration = $container->get('plugin.manager.migration');
    $instance->trellisHelper = $container->get('az_event_trellis.trellis_helper');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_event_trellis_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['event_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Trellis Event ID'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Import event'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $event_url = $this->trellisHelper->getEventUrl($form_state->getValue('event_id'));
    // Get category options remotely.
    $response = $this->httpClient->request('GET', $event_url, ['verify' => FALSE]);
    $event_data = json_decode($response->getBody(), TRUE);

    if (!isset($event_data[0]['data'])) {
      $form_state->setErrorByName('event_id', t('Invalid event ID.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Pass URL to migrate executable.
    $event_url = $this->trellisHelper->getEventUrl($form_state->getValue('event_id'));
    $migration = $this->pluginManagerMigration->createInstance('az_trellis_events');
    $options = [
      'limit' => 0,
      'update' => 1,
      'force' => 0,
      'configuration' => [
        'source' => [
          'urls' => $event_url,
        ],
      ],
    ];

    $executable = new MigrateBatchExecutable($migration, new MigrateMessage(), $options);
    $executable->batchImport();
  }

}
