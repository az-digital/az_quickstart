<?php

namespace Drupal\xmlsitemap\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure what entities will be included in sitemap.
 */
class XmlSitemapEntitiesSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xmlsitemap_config_entities_settings_form';
  }

  /**
   * Constructs a XmlSitemapEntitiesSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\State\StateInterface $state
   *   The object State.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, StateInterface $state) {
    parent::__construct($config_factory);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('state')
    );
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Create the list of possible entity types.
    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types = array_filter($this->entityTypeManager->getDefinitions(), 'xmlsitemap_is_entity_type_supported');

    // Create the list of options as well as the default values based on which
    // entity types have enabled configuration already.
    $labels = array_map(function (EntityTypeInterface $entityType) {
      return $entityType->getLabel();
    }, $entity_types);
    asort($labels);
    $defaults = array_keys(array_filter(array_map(function (EntityTypeInterface $entityType) {
      return xmlsitemap_link_entity_check_enabled($entityType->id());
    }, $entity_types)));

    $form['entity_types'] = [
      '#title' => $this->t('Custom sitemap entities settings'),
      '#type' => 'checkboxes',
      '#options' => $labels,
      '#default_value' => $defaults,
    ];

    $form['settings'] = ['#tree' => TRUE];

    foreach ($labels as $entity_type_id => $label) {
      $entity_type = $entity_types[$entity_type_id];
      $bundle_label = $entity_type->getBundleLabel() ?: $label;
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);

      $form['settings'][$entity_type_id] = [
        '#type' => 'container',
        '#entity_type' => $entity_type_id,
        '#bundle_label' => $bundle_label,
        '#title' => $bundle_label,
        '#states' => [
          'visible' => [
            ':input[name="entity_types[' . $entity_type_id . ']"]' => ['checked' => TRUE],
          ],
        ],

        'types' => [
          '#type' => 'table',
          '#tableselect' => TRUE,
          '#default_value' => [],
          '#header' => [
            [
              'data' => $bundle_label,
              'class' => ['bundle'],
            ],
            [
              'data' => $this->t('Sitemap settings'),
              'class' => ['operations'],
            ],
          ],
        ],
        '#access' => !empty($bundles),
      ];

      foreach ($bundles as $bundle => $bundle_info) {
        $form['settings'][$entity_type_id][$bundle]['settings'] = [
          '#type' => 'item',
          '#label' => $bundle_info['label'],
        ];

        $form['settings'][$entity_type_id]['types'][$bundle] = [
          'bundle' => [
            '#markup' => $bundle_info['label'],
          ],
          'operations' => [
            '#type' => 'operations',
            '#links' => [
              'configure' => [
                'title' => $this->t('Configure'),
                'url' => Url::fromRoute('xmlsitemap.admin_settings_bundle', [
                  'entity' => $entity_type_id,
                  'bundle' => $bundle,
                ]),
                'query' => $this->getDestinationArray(),
              ],
            ],
          ],
        ];
        $form['settings'][$entity_type_id]['types']['#default_value'][$bundle] = xmlsitemap_link_bundle_check_enabled($entity_type_id, $bundle);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bundles = $this->entityTypeBundleInfo->getAllBundleInfo();
    $values = $form_state->getValues();
    $entity_values = $values['entity_types'];
    foreach ($entity_values as $key => $value) {
      if ($value) {
        foreach ($bundles[$key] as $bundle_key => $bundle_value) {
          if (!$values['settings'][$key]['types'][$bundle_key]) {
            xmlsitemap_link_bundle_delete($key, $bundle_key);
          }
          elseif (!xmlsitemap_link_bundle_check_enabled($key, $bundle_key)) {
            xmlsitemap_link_bundle_enable($key, $bundle_key);
          }
        }
      }
      else {
        foreach ($bundles[$key] as $bundle_key => $bundle_value) {
          xmlsitemap_link_bundle_delete($key, $bundle_key);
        }
      }
    }
    $this->state->set('xmlsitemap_regenerate_needed', TRUE);
    parent::submitForm($form, $form_state);
  }

}
