<?php

namespace Drupal\az_publication_bibtex\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * BibTeX import form.
 */
class AZPublicationBibtexForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
    $instance->fileSystem = $container->get('file_system');
    $instance->pluginManagerMigration = $container->get('plugin.manager.migration');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->keyValue = $container->get('keyvalue');
    $instance->time = $container->get('datetime.time');
    $instance->translation = $container->get('string_translation');
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
        'FileExtension' => ['extensions' => 'bib'],
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['bibtex'])) {
      $fid = reset($values['bibtex']);
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
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
        $executable = new MigrateBatchExecutable(
          $migration,
          new MigrateMessage(),
          $this->keyValue,
          $this->time,
          $this->translation,
          $this->pluginManagerMigration,
          $options,
        );
        $executable->batchImport();
      }
    }
  }

}
