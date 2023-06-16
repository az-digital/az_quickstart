<?php

namespace Drupal\az_publication_bibtex\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Drupal\migrate\MigrateMessage;

/**
 * BibTeX import form.
 */
class AZPublicationBibtexForm extends FormBase {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $pluginManagerMigration;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->fileSystem = $container->get('file_system');
    $instance->pluginManagerMigration = $container->get('plugin.manager.migration');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_publication_bibtex_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['bibtex'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload a BibTeX document'),
      '#upload_location' => 'temporary://bibtex/',
      '#upload_validators' => [
        'file_validate_extensions' => ['bib'],
      ],
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['bibtex'])) {
      $fid = reset($values['bibtex']);
      $file = File::load($fid);
      if (!empty($file)) {
        $uri = $file->getFileUri();
        $path = $this->fileSystem->realpath($uri);
        $migration_id = 'az_publication_bibtex_import';
        /** @var \Drupal\migrate\Plugin\Migration $migration */
        $migration = $this->pluginManagerMigration->createInstance($migration_id);
        // Reset status.
        $status = $migration->getStatus();
        if ($status !== MigrationInterface::STATUS_IDLE) {
          $migration->setStatus(MigrationInterface::STATUS_IDLE);
        }
        $options = [
          'limit' => 0,
          'update' => 1,
          'force' => 0,
          'configuration' => [
            'source' => [
              'urls' => $path,
            ],
          ],
        ];
        $executable = new MigrateBatchExecutable($migration, new MigrateMessage(), $options);
        $executable->batchImport();
      }
    }
  }

}
