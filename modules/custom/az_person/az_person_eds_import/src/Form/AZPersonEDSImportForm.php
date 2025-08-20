<?php

declare(strict_types=1);

namespace Drupal\az_person_eds_import\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Quickstart Person Profiles Import form.
 */
final class AZPersonEDSImportForm extends FormBase {

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $pluginManagerMigration;

  /**
   * The key/value factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected KeyValueFactoryInterface $keyValue;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected TranslationInterface $translation;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->messenger = $container->get('messenger');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->pluginManagerMigration = $container->get('plugin.manager.migration');
    $instance->keyValue = $container->get('keyvalue');
    $instance->time = $container->get('datetime.time');
    $instance->translation = $container->get('string_translation');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'az_person_eds_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Get enabled LDAP queries.
    $queries = $this->entityTypeManager->getStorage('ldap_query_entity')->loadByProperties([
      // Only look at enabled queries.
      'status' => 1,
      // Only look at queries for EDS.
      'server_id' => 'az_eds',
    ]);
    if (empty($queries)) {
      $url = Url::fromRoute('entity.ldap_query_entity.collection')->toString();
      $this->messenger->addWarning($this->t('Create and enable at least one LDAP Query <a href=":link">here</a>.', [
        ':link' => $url,
      ]));
    }
    else {
      $options = [];
      foreach ($queries as $query) {
        $options[$query->id()] = $query->label();
      }
      $form['query'] = [
        '#type' => 'radios',
        '#title' => $this->t('LDAP Query to Import'),
        '#options' => $options,
        '#description' => $this->t('The results of the selected Query will be imported as Persons.'),
        '#required' => TRUE,
      ];
    }

    $form['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose how profiles are imported'),
      '#options' => [
        'track' => $this->t('Import new/changed persons'),
        'normal' => $this->t('Import new persons only'),
        'update' => $this->t('Import all persons'),
      ],
      '#disabled' => empty($queries),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#disabled' => empty($queries),
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
    $query = $form_state->getValue('query');
    $mode = $form_state->getValue('mode');
    $update = $mode === 'update';
    $track = $mode === 'track';

    // Create an instance of the EDS import.
    $migration = $this->pluginManagerMigration->createInstance('az_person_eds_import');
    if ($migration->getStatus() !== MigrationInterface::STATUS_IDLE) {
      $migration->setStatus(MigrationInterface::STATUS_IDLE);
    }
    $options = [
      'limit' => 0,
      'update' => (int) $update,
      'force' => 0,
      'configuration' => [
        'source' => [
          'queries' => [$query],
          'track_changes' => $track,
        ],
      ],
    ];
    // Run the migration.
    $executable = new MigrateBatchExecutable(
      $migration,
      new MigrateMessage(),
      $this->keyValue,
      $this->time,
      $this->translation,
      $this->pluginManagerMigration,
      $options
    );
    $executable->batchImport();

  }

}
