<?php

namespace Drupal\az_publication_doi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * DOI import form.
 */
class AZPublicationDOIForm extends FormBase {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
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
    $instance->pluginManagerMigration = $container->get('plugin.manager.migration');
    $instance->keyValue = $container->get('keyvalue');
    $instance->dateTime = $container->get('datetime.time');
    $instance->translation = $container->get('string_translation');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_publication_doi_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['doi'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter a digital object identifier (DOI)'),
      '#description' => $this->t('A valid digital object identifier (DOI), e.g. 10.1001/jama.2013.284427'),
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
    $doi = $form_state->getValue('doi');
    $url = 'https://doi.org/' . $doi;
    if (substr($doi, 0, 4) === "http") {
      $form_state->setErrorByName('doi', $this->t('Please enter only the digital object identifier.'));
    }
    elseif (empty($doi) || (filter_var($url, FILTER_VALIDATE_URL) === FALSE)) {
      $form_state->setErrorByName('doi', $this->t('Please enter a DOI.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['doi'])) {
      $doi = $values['doi'];
      $url = 'https://doi.org/' . $doi;
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
            'urls' => $url,
            'data_fetcher_plugin' => 'http',
            'headers' => [
              // Content Negotiation. https://citation.crosscite.org/docs.html
              'Accept' => 'application/x-bibtex; charset=utf-8',
            ],
          ],
        ],
      ];
      $executable = new MigrateBatchExecutable(
        $migration,
        new MigrateMessage(),
        $this->keyValue,
        $this->time,
        $this->translation,
        $options,
      );
      $executable->batchImport();
    }
  }

}
