<?php

namespace Drupal\xmlsitemap_custom\Form;

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
 * Provides a form for editing a custom link.
 */
class XmlSitemapCustomEditForm extends FormBase {

  /**
   * The path of the custom link.
   *
   * @var string
   *
   * @codingStandardsIgnoreStart
   */
  protected $custom_link;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   *
   * @codingStandardsIgnoreEnd
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
   * Constructs a new XmlSitemapCustomEditForm object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager service.
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   A Guzzle client object.
   * @param \Drupal\xmlsitemap\XmlSitemapLinkStorageInterface $link_storage
   *   The xmlsitemap link storage service.
   */
  public function __construct(LanguageManagerInterface $language_manager, AliasManagerInterface $alias_manager, ClientFactory $http_client_factory, XmlSitemapLinkStorageInterface $link_storage) {
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
    return 'xmlsitemap_custom_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $link = '') {
    if (!$custom_link = $this->linkStorage->load('custom', $link)) {
      $this->messenger()->addError($this->t('No valid custom link specified.'));
      $this->redirect('xmlsitemap_custom.list');
    }
    else {
      $this->custom_link = $custom_link;
    }

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
      '#value' => $this->custom_link['id'],
    ];
    $form['loc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to link'),
      '#field_prefix' => rtrim(Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), '/'),
      '#default_value' => $this->custom_link['loc'],
      '#description' => $this->t('Use a relative path with a slash in front. For example, "/about".'),
      '#required' => TRUE,
      '#size' => 30,
    ];
    $form['priority'] = [
      '#type' => 'select',
      '#title' => $this->t('Priority'),
      '#options' => xmlsitemap_get_priority_options(),
      '#default_value' => number_format($this->custom_link['priority'], 1),
      '#description' => $this->t('The priority of this URL relative to other URLs on your site.'),
    ];
    $form['changefreq'] = [
      '#type' => 'select',
      '#title' => $this->t('Change frequency'),
      '#options' => [0 => $this->t('None')] + xmlsitemap_get_changefreq_options(),
      '#default_value' => $this->custom_link['changefreq'],
      '#description' => $this->t('How frequently the page is likely to change. This value provides general information to search engines and may not correlate exactly to how often they crawl the page.'),
    ];
    $form['language'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $this->custom_link['language'],
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
