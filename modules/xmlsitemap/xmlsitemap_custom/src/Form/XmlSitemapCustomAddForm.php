<?php

namespace Drupal\xmlsitemap_custom\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Url;
use Drupal\xmlsitemap\XmlSitemapLinkStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding a custom link.
 */
class XmlSitemapCustomAddForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The alias manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $httpClientFactory;

  /**
   * The xmlsitemap link storage handler.
   *
   * @var \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface
   */
  protected $linkStorage;

  /**
   * Constructs a new XmlSitemapCustomAddForm object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager service.
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   A Guzzle client object.
   * @param \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface $link_storage
   *   The xmlsitemap link storage service.
   */
  public function __construct(Connection $connection, LanguageManagerInterface $language_manager, AliasManagerInterface $alias_manager, ClientFactory $http_client_factory, XmlSitemapLinkStorageInterface $link_storage) {
    $this->connection = $connection;
    $this->languageManager = $language_manager;
    $this->aliasManager = $alias_manager;
    $this->httpClientFactory = $http_client_factory;
    $this->linkStorage = $link_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('language_manager'),
      $container->get('path_alias.manager'),
      $container->get('http_client_factory'),
      $container->get('xmlsitemap.link_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_custom_add';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Take into account that databases use wildly different names for their
    // data types.
    $db_type = $this->connection->databaseType();
    switch ($db_type) {
      case 'mysql':
        $type = 'UNSIGNED';
        break;

      case 'pgsql':
        $type = 'BIGINT';
        break;

      case 'sqlite':
        $type = 'INTEGER';
        break;

      default:
        $type = 'INT';
        break;
    }

    $query = $this->connection->select('xmlsitemap', 'x');
    $query->addExpression("MAX(CAST(id AS $type))");
    $query->condition('type', 'custom');
    $id = (int) $query->execute()->fetchField();
    $link = [
      'id' => $id + 1,
      'loc' => '',
      'priority' => XMLSITEMAP_PRIORITY_DEFAULT,
      'lastmod' => 0,
      'changefreq' => 0,
      'changecount' => 0,
      'language' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];

    $form['type'] = [
      '#type' => 'value',
      '#value' => 'custom',
    ];
    $form['subtype'] = [
      '#type' => 'value',
      '#value' => '',
    ];
    $form['id'] = [
      '#type' => 'value',
      '#value' => $link['id'],
    ];
    $form['loc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to link'),
      '#field_prefix' => rtrim(Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), '/'),
      '#default_value' => $link['loc'] ? $this->aliasManager->getPathByAlias($link['loc'], $link['language']) : '',
      '#description' => $this->t('Use a relative path with a slash in front. For example, "/about".'),
      '#required' => TRUE,
      '#size' => 30,
    ];
    $form['priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Priority'),
      '#options' => xmlsitemap_get_priority_options(),
      '#default_value' => number_format($link['priority'], 1),
      '#description' => $this->t('The priority of this URL relative to other URLs on your site.'),
    ];
    $form['changefreq'] = [
      '#type' => 'select',
      '#title' => $this->t('Change frequency'),
      '#options' => [0 => $this->t('None')] + xmlsitemap_get_changefreq_options(),
      '#default_value' => $link['changefreq'],
      '#description' => $this->t('How frequently the page is likely to change. This value provides general information to search engines and may not correlate exactly to how often they crawl the page.'),
    ];
    $form['language'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $link['language'],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#weight' => 5,
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('xmlsitemap_custom.list'),
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $link = $form_state->getValues();

    if (strpos($link['loc'], '/') !== 0) {
      $form_state->setErrorByName('loc', $this->t('The path should start with /.'));
      return;
    }

    // Make sure we trim and normalize the path first.
    $link['loc'] = trim($link['loc']);
    $link['loc'] = $this->aliasManager->getPathByAlias($link['loc'], $link['language']);
    $form_state->setValue('loc', $link['loc']);

    $query = $this->connection->select('xmlsitemap');
    $query->fields('xmlsitemap');
    $query->condition('type', 'custom');
    $query->condition('loc', $link['loc']);
    $query->condition('status', 1);
    $query->condition('access', 1);
    $query->condition('language', $link['language']);
    $result = $query->execute()->fetchAssoc();

    if ($result != FALSE) {
      $form_state->setErrorByName('loc', $this->t('There is already an existing link in the sitemap with the path %link.', ['%link' => $link['loc']]));
    }
    try {
      $client = $this->httpClientFactory->fromOptions(['config/curl', [CURLOPT_FOLLOWLOCATION => FALSE]]);
      $client->get(Url::fromUserInput($link['loc'], ['absolute' => TRUE])->toString());
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('loc', $this->t('The custom link @link is either invalid or it cannot be accessed by anonymous users.', ['@link' => $link['loc']]));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $link = $form_state->getValues();
    $this->linkStorage->save($link);
    $this->messenger()->addStatus($this->t('The custom link for %loc was saved.', ['%loc' => $link['loc']]));

    $form_state->setRedirect('xmlsitemap_custom.list');
  }

}
