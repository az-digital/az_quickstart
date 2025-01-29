<?php

namespace Drupal\az_enterprise_attributes_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Imports enterprise tags from URL endpoint.
 */
class AZEnterpriseAttributesImportForm extends ConfigFormBase {

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
  protected function getEditableConfigNames() {
    return [
      'az_enterprise_attributes_import.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_enterprise_attributes_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('az_enterprise_attributes_import.settings');
    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Enterprise Attributes Endpoint'),
      '#description' => $this->t('Enter a fully qualified URL for the endpoint of your enterprise attributes service.'),
      '#default_value' => $config->get('endpoint'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('az_enterprise_attributes_import.settings')
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->save();

    // Fetch the attribute migration.
    $migration = $this->pluginManagerMigration->createInstance('az_enterprise_attributes_import');
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
      ];

      // Run the migration.
      $executable = new MigrateBatchExecutable($migration, new MigrateMessage(), $options);
      $executable->batchImport();
    }
  }

}
