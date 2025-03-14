<?php

declare(strict_types=1);

namespace Drupal\az_person_profiles_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Quickstart Person Profiles Import form.
 */
final class AZPersonProfilesImportForm extends FormBase {

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * AZ Migration Remote Tools.
   *
   * @var \Drupal\az_migration_remote\MigrationRemoteTools
   */
  protected $migrationRemoteTools;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->messenger = $container->get('messenger');
    $instance->migrationRemoteTools = $container->get('az_migration_remote.tools');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'az_person_profiles_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('az_person_profiles_import.settings');
    $has_key = !empty(trim($config->get('apikey')));
    if (!$has_key) {
      $url = Url::fromRoute('az_person_profiles_import.settings_form')->toString();
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
        'normal' => $this->t('Import new profiles only'),
        'track_changes' => $this->t('Import new profiles and profiles updated since the last import'),
        'update' => $this->t('Import all listed profiles'),
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

    $migrations = [
      'az_person_profiles_import_files' => [
        'limit' => 0,
        'update' => (int) $update,
        'track_changes' => (int) $track,
        'configuration' => [
          'source' => [
            'urls' => $urls,
          ],
        ],
      ],
      'az_person_profiles_import_media' => [
        'limit' => 0,
        'update' => (int) $update,
        'track_changes' => (int) $track,
        'configuration' => [
          'source' => [
            'urls' => $urls,
          ],
        ],
      ],
      'az_person_profiles_import' => [
        'limit' => 0,
        'update' => (int) $update,
        'track_changes' => (int) $track,
        'configuration' => [
          'source' => [
            'urls' => $urls,
          ],
        ],
      ],
    ];

    // Run the migration.
    $this->migrationRemoteTools->batch($migrations);
  }

}
