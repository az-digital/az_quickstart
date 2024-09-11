<?php

declare(strict_types=1);

namespace Drupal\az_person_profile_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Provides a Quickstart Person Profile Import form.
 */
final class AZPersonProfileImportForm extends FormBase {

  /**
   * The HTTP client.
   */
  protected ?Client $httpClient;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Drupal\migrate\Plugin\MigrationPluginManagerInterface definition.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $pluginManagerMigration;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->messenger = $container->get('messenger');
    $instance->pluginManagerMigration = $container->get('plugin.manager.migration');
    try {
      // Use the distribution cached http client if it is available.
      $instance->httpClient = $container->get('az_http.http_client');
    }
    catch (ServiceNotFoundException $e) {
      // Otherwise, fall back on the Drupal core guzzle client.
      $instance->httpClient = $container->get('http_client');
    }

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'az_person_profile_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('az_person_profile_import.settings');
    $has_key = !empty(trim($config->get('apikey')));
    if (!$has_key) {
      $url = Url::fromRoute('az_person_profile_import.settings_form')->toString();
      $this->messenger->addWarning($this->t('You must first configure a Profiles API token <a href=":link">here</a>.', [
        ':link' => $url,
      ]));
    }

    $form['netid'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List of NetID(s)'),
      '#description' => $this->t('Enter the NetIDs of the individuals you wish to import, one per line.'),
      '#disabled' => !$has_key,
      '#required' => TRUE,
    ];

    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose how profiles are imported'),
      '#options' => [
        'normal' => $this->t('New profiles'),
        'update' => $this->t('All listed profiles'),
        'track_changes' => $this->t('Profiles that have been updated since last import'),
      ],
      '#disabled' => !$has_key,
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#disabled' => !$has_key,
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Import'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('az_person_profile_import.settings');
    $endpoint = $config->get('endpoint');
    $apikey = $config->get('apikey');
    $urls = [];
    $netids = $form_state->getValue('netid');
    $mode = $form_state->getValue('mode');
    $netids = preg_split("(\r\n?|\n)", $netids);
    $update = $mode === 'update';
    $track = $mode === 'track_changes';

    foreach ($netids as $netid) {
      $netid = trim($netid);
      try {
        // Make a request to check if the NetID exists.
        $response = $this->httpClient->get($endpoint . '/get/' . urlencode($netid) . '?apikey=' . urlencode($apikey), [
          'http_errors' => FALSE,
        ]);

        // Check if the response is valid.
        if ($response->getStatusCode() === 200) {
          $urls[] = $endpoint . '/get/' . urlencode($netid) . '?apikey=' . urlencode($apikey);
        }
        else {
          // Log or inform the user that the NetID was not found.
          $this->messenger->addWarning($this->t('NetID %netid was not found in the Profiles API.', ['%netid' => $netid]));
        }
      }
      catch (RequestException $e) {
        // Handle request exceptions.
        $this->messenger->addError($this->t('Error fetching data for NetID %netid: %message', [
          '%netid' => $netid,
          '%message' => $e->getMessage(),
        ]));
      }
    }

    if (!empty($urls)) {
      // Continue with the migration only if there are valid URLs.
      $migration = $this->pluginManagerMigration->createInstance('az_person_profile_import');
      // Phpstan doesn't know this can be NULL.
      // @phpstan-ignore-next-line
      if (!empty($migration)) {
        // Reset status.
        $status = $migration->getStatus();
        if ($status !== MigrationInterface::STATUS_IDLE) {
          $migration->setStatus(MigrationInterface::STATUS_IDLE);
        }
        // Set migration options.
        $options = [
          'limit' => 0,
          'update' => (int) $update,
          'track_changes' => (int) $track,
          'configuration' => [
            'source' => [
              'urls' => $urls,
            ],
          ],
        ];

        // Run the migration.
        $executable = new MigrateBatchExecutable($migration, new MigrateMessage(), $options);
        $executable->batchImport();
      }
    }
    else {
      // Inform the user if no valid NetIDs were found.
      $this->messenger->addError($this->t('No valid NetIDs were found for import.'));
    }
  }

}
