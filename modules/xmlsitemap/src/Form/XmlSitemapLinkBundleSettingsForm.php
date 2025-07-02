<?php

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure what entities will be included in sitemap.
 */
class XmlSitemapLinkBundleSettingsForm extends ConfigFormBase {

  // @codingStandardsIgnoreStart
  private $entity_type;
  private $bundle_type;
  // @codingStandardsIgnoreEnd

  /**
   * The state system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a XmlSitemapLinkBundleSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state system.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state) {
    parent::__construct($config_factory);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_link_bundle_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['xmlsitemap.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity = NULL, $bundle = NULL) {
    $this->entity_type = $entity;
    $this->bundle_type = $bundle;
    $request = $this->getRequest();

    $form['#title'] = $this->t('@bundle XML Sitemap settings', ['@bundle' => $bundle]);

    xmlsitemap_add_link_bundle_settings($form, $form_state, $entity, $bundle);
    $form['xmlsitemap']['#type'] = 'markup';
    $form['xmlsitemap']['#value'] = '';
    $form['xmlsitemap']['#access'] = TRUE;
    $form['xmlsitemap']['#show_message'] = TRUE;

    $destination = $request->get('destination');

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#href' => isset($destination) ? $destination : 'admin/config/search/xmlsitemap/settings',
      '#weight' => 10,
    ];
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundle = $form['xmlsitemap']['#bundle'];

    // Handle new bundles by fetching the proper bundle key value from the form
    // state values.
    if (empty($bundle)) {
      $entity_info = $form['xmlsitemap']['#entity_info'];
      if (isset($entity_info['bundle keys']['bundle'])) {
        $bundle_key = $entity_info['bundle keys']['bundle'];
        if ($form_state->hasValue($bundle_key)) {
          $bundle = $form_state->getValue($bundle_key);
          $form['xmlsitemap']['#bundle'] = $bundle;
        }
      }
    }

    $xmlsitemap = $form_state->getValue('xmlsitemap');
    xmlsitemap_link_bundle_settings_save($this->entity_type, $this->bundle_type, $xmlsitemap);

    $entity_info = $form['xmlsitemap']['#entity_info'];
    if (!empty($form['xmlsitemap']['#show_message'])) {
      $this->messenger()->addStatus($this->t('XML Sitemap settings for the %bundle have been saved.', ['%bundle' => $entity_info['bundles'][$bundle]['label']]));
    }

    // Unset the form values since we have already saved the bundle settings and
    // we don't want these values to get saved as configuration, depending on
    // how the form saves the form values.
    $form_state->unsetValue('xmlsitemap');
    $form_state->setRedirect('xmlsitemap.admin_settings');
    parent::submitForm($form, $form_state);
  }

}
