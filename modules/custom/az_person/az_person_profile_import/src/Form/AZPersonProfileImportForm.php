<?php

declare(strict_types=1);

namespace Drupal\az_person_profile_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Quickstart Person Profile Import form.
 */
final class AZPersonProfileImportForm extends FormBase {

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
    $urls = [];
    $netids = $form_state->getValue('netid');
    $mode = $form_state->getValue('mode');
    $netids = preg_split("(\r\n?|\n)", $netids);
    $update = $mode === 'update';
    $track = $mode === 'track_changes';

    foreach ($netids as $netid) {
      // For the profiles API fetcher, the url is the netid.
      $netid = trim($netid);
      $urls[] = $netid;
    }

    // Fetch the profiles integration migration.
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

}
