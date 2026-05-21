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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The LDAP query controller service (if available).
   *
   * @var \Drupal\ldap_query\Controller\QueryController|null
   */
  protected $ldapQueryController;
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
    $instance->entityTypeManager = $container->get('entity_type.manager');
    try {
      // This service may not exist if ldap_query is not enabled.
      $instance->ldapQueryController = $container->get('ldap.query');
    }
    catch (\Exception $e) {
      $instance->ldapQueryController = NULL;
    }
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
      '#title' => $this->t('Manual List of NetID(s)'),
      '#description' => $this->t('Enter the NetIDs of the individuals you wish to import, one per line.'),
      '#disabled' => !$has_key,
      '#required' => FALSE,
    ];

    try {
      // Optionally, get enabled LDAP queries.
      // This may throw an exception if ldap_query isn't enabled.
      $queries = $this->entityTypeManager->getStorage('ldap_query_entity')->loadByProperties([
        // Only look at enabled queries.
        'status' => 1,
        // Only look at queries for EDS.
        'server_id' => 'az_eds',
      ]);
      if (!empty($queries)) {
        $options = [];
        foreach ($queries as $query) {
          $options[$query->id()] = $query->label();
        }
        // Phpstan doesn't realize this can be empty.
        // @phpstan-ignore-next-line
        if (!empty($options)) {
          // Add a form element to the form that allows selection of a query.
          $form['query'] = [
            '#type' => 'radios',
            '#title' => $this->t('EDS Query'),
            '#options' => $options,
            '#description' => $this->t('If selected, the NetIDs found in the query will be used to determine what profiles to import.'),
            '#disabled' => !$has_key,
            '#required' => FALSE,
          ];
        }
      }
    }
    catch (\Exception $e) {

    }

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
    $query = $form_state->getValue('query');
    $mode = $form_state->getValue('mode');
    $netids = preg_split("(\r\n?|\n)", $netids);
    // Remove case where no netid was specified.
    $netids = array_filter($netids);
    $update = $mode === 'update';
    $track = $mode === 'track_changes';

    foreach ($netids as $netid) {
      // For the profiles API fetcher, the url is the netid.
      $netid = trim($netid);
      $urls[] = $netid;
    }

    if (!empty($query) && !is_null($this->ldapQueryController)) {
      // Attempt to execute the specified query.
      $this->ldapQueryController->load($query);
      $this->ldapQueryController->execute();
      $results = $this->ldapQueryController->getRawResults();
      // For each person we find, add them as a net id to the profile import.
      foreach ($results as $result) {
        $row = $result->getAttributes();
        $uid = $row['uid'] ?? [];
        $uid = reset($uid);
        if (!empty($uid)) {
          $urls[] = trim($uid);
        }
      }
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
