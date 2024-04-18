<?php

declare(strict_types=1);

namespace Drupal\az_person_profile_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Quickstart Person Profile Import form.
 */
final class AZPersonProfileImportForm extends FormBase {

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

    $form['netid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NetID'),
      '#description' => $this->t('Enter the NetID of the person you wish to import.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
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
    $netid = $form_state->getValue('netid');
    $url = $endpoint . '/get/' . urlencode($netid) . '?apikey=' . urlencode($apikey);

    $this->messenger()->addStatus($this->t('Importing @netid from Profiles API.', [
      '@netid' => $netid,
    ]));

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
        'update' => 1,
        'configuration' => [
          'source' => [
            'urls' => $url,
          ],
        ],
      ];

      // Run the migration.
      $executable = new MigrateBatchExecutable($migration, new MigrateMessage(), $options);
      $executable->batchImport();
    }
  }

}
