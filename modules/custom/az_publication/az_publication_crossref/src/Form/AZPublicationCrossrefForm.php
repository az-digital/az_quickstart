<?php

namespace Drupal\az_publication_crossref\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use GuzzleHttp\Exception\GuzzleException;
use Seboettg\CiteProc\CiteProc;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * DOI import form.
 */
class AZPublicationCrossrefForm extends FormBase {

  /**
   * Crossref API endpoint.
   *
   * @var string
   */
  public static $apiBase = 'http://api.crossref.org/works';

  /**
   * DOI endpoint.
   *
   * @var string
   */
  public static $doiBase = 'http://doi.org/';

  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $pluginManagerMigration;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
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
  public function getFormId() {
    return 'az_publication_crossref_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['query'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search query'),
      '#description' => $this->t('A string to search via the Crossref API.'),
      '#required' => TRUE,
    ];
    $form['search'] = [
      '#type' => 'submit',
      '#name' => 'search_submit',
      '#value' => $this->t('Search'),
    ];

    $storage = $form_state->getStorage();
    if (!empty($storage['citation_data'])) {

      $options = [];
      foreach ($storage['citation_data'] as $k => $pub) {
        // Placeholder until CSL-JSON import available.
        if (empty($pub->DOI)) {
          continue;
        }
        // @phpstan-ignore-next-line
        $options[$pub->DOI] = ['publication' => check_markup($pub->citation, 'az_citation')];
      }
      // Show select options for publications to import.
      $form['publications'] = [
        '#type' => 'tableselect',
        '#header' => [
          'publication' => $this->t('Publication'),
        ],
        '#options' => $options,
        '#empty' => $this->t('No results found.'),
      ];

      // Show the import button if we have publications.
      $form['import'] = [
        '#type' => 'submit',
        '#name' => 'import_submit',
        '#value' => $this->t('Import'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $publications = $form_state->getValue('publications');
    $trigger = $form_state->getTriggeringElement()['#name'];

    if ($trigger === 'import_submit') {
      $publications = array_filter($publications);
      if (empty($publications)) {
        $form_state->setErrorByName('publications', $this->t('You must select a publication to import.'));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $trigger = $form_state->getTriggeringElement()['#name'];

    if ($trigger === 'search_submit') {
      $query = $values['query'];
      $url = self::$apiBase;
      $results = [];
      try {
        // Run crossref request.
        $response = $this->httpClient->request('GET', $url, ['query' => ['query.bibliographic' => $query]]);
        if ($response->getStatusCode() === 200) {
          $json = (string) $response->getBody();
          $json = json_decode($json);
          if ($json !== NULL) {
            $results = $json->message->items ?? [];
          }
        }
      }
      catch (GuzzleException $e) {
      }
      // @todo Use injected services.
      $default_config = \Drupal::config('az_publication.settings');
      $locale = \Drupal::service('az_publication.locale_metadata')->getLocaleId();
      $repository = \Drupal::service('entity.repository');
      $style_context = $default_config->get('default_citation_style');

      /** @var \Drupal\az_publication\Entity\AZQuickstartCitationStyle $style */
      $style = \Drupal::entityTypeManager()->getStorage('az_citation_style')->load($style_context);
      if (!is_null($style)) {
        $style_info = $style->getStyleSheet();
      }
      // @todo determine why these are sometimes arrays in crossref.
      $flattens = [
        'title',
        'container-title',
        'archive',
      ];

      $additionalMarkup = [
        "csl-entry" => function ($cslItem, $renderedText) {
          // Remove citation number tag.
          return preg_replace('#<div class="csl-left-margin">(.*?)</div>#', '', $renderedText);
        },
      ];
      // Set up CSL rendering.
      $citeProc = new CiteProc($style_info, $locale, $additionalMarkup);

      $storage = $form_state->getStorage();
      $storage['citation_data'] = [];
      foreach ($results as $result) {
        foreach ($flattens as $flatten) {
          if (!empty($result->{$flatten}) && is_array($result->{$flatten})) {
            $result->{$flatten} = reset($result->{$flatten});
          }
        }
        $citation = $citeProc->render([$result], "bibliography");
        $result->citation = $citation;
        $storage['citation_data'][] = $result;
      }
      // Store results and rebuild form.
      $form_state->setStorage($storage);
      $form_state->setRebuild(TRUE);
    }
    elseif ($trigger === 'import_submit') {
      $pubs = $values['publications'] ?? [];
      $pubs = array_filter($pubs);
      $urls = [];
      foreach ($pubs as $doi) {
        $urls[] = self::$doiBase . $doi;
      }
      \Drupal::logger('my_moduledoi')->notice(print_r($urls, TRUE));
      // Import publications.
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
            'urls' => $urls,
            'data_fetcher_plugin' => 'http',
            'headers' => [
              // Content Negotiation. https://citation.crosscite.org/docs.html
              'Accept' => 'application/x-bibtex; charset=utf-8',
            ],
          ],
        ],
      ];
      $executable = new MigrateBatchExecutable($migration, new MigrateMessage(), $options);
      $executable->batchImport();
    }
  }

}
