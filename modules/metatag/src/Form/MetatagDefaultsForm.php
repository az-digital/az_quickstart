<?php

namespace Drupal\metatag\Form;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\metatag\MetatagDefaultsInterface;
use Drupal\metatag\MetatagManager;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\metatag\MetatagTagPluginManager;
use Drupal\metatag\MetatagToken;
use Drupal\page_manager\Entity\PageVariant;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the Metatag Defaults entity type.
 *
 * @package Drupal\metatag\Form
 */
class MetatagDefaultsForm extends EntityForm {

  /**
   * The Metatag defaults object being reverted.
   *
   * @var \Drupal\metatag\Entity\MetatagDefaults
   */
  protected $entity;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The Metatag manager service.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * The Metatag token service.
   *
   * @var \Drupal\metatag\MetatagToken
   */
  protected $metatagToken;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Metatag tag plugin manager service.
   *
   * @var \Drupal\metatag\MetatagTagPluginManager
   */
  protected $metatagPluginManager;

  /**
   * Constructs a new MetatagDefaultsForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\metatag\MetatagManagerInterface $metatag_manager
   *   The Metatag manager service.
   * @param \Drupal\metatag\MetatagToken $metatag_token
   *   The Metatag token service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\metatag\MetatagTagPluginManager $metatag_plugin_manager
   *   The Metatag tag plugin manager service.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, MetatagManagerInterface $metatag_manager, MetatagToken $metatag_token, ModuleHandlerInterface $module_handler, MetatagTagPluginManager $metatag_plugin_manager) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->metatagManager = $metatag_manager;
    $this->metatagToken = $metatag_token;
    $this->moduleHandler = $module_handler;
    $this->metatagPluginManager = $metatag_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.bundle.info'),
      $container->get('metatag.manager'),
      $container->get('metatag.token'),
      $container->get('module_handler'),
      $container->get('plugin.manager.metatag.tag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $metatag_defaults */
    $metatag_defaults = $this->entity;

    $form['#ajax_wrapper_id'] = 'metatag-defaults-form-ajax-wrapper';
    $ajax = [
      'wrapper' => $form['#ajax_wrapper_id'],
      'callback' => '::rebuildForm',
    ];
    $form['#prefix'] = '<div id="' . $form['#ajax_wrapper_id'] . '">';
    $form['#suffix'] = '</div>';

    $default_type = NULL;
    if ($metatag_defaults) {
      $default_type = $metatag_defaults->getOriginalId();
    }
    else {
      $form_state->set('default_type', $default_type);
    }

    $token_types = empty($default_type) ? [] : [explode('__', $default_type)[0]];

    // Add the token browser at the top.
    $form += $this->metatagToken->tokenBrowser($token_types);

    // If this is a new Metatag defaults, then list available bundles.
    if ($metatag_defaults->isNew()) {
      $options = $this->getAvailableBundles();
      $form['id'] = [
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#description' => $this->t('Select the type of default meta tags you would like to add.'),
        '#options' => $options,
        '#required' => TRUE,
        '#default_value' => $default_type,
        '#ajax' => $ajax + [
          'trigger_as' => [
            'name' => 'select_id_submit',
          ],
        ],
      ];
      $form['select_id_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
        '#name' => 'select_id_submit',
        '#ajax' => $ajax,
        '#attributes' => [
          'class' => ['js-hide'],
        ],
      ];
      $values = [];
    }
    else {
      $values = $metatag_defaults->get('tags');
    }

    // Retrieve configuration settings.
    $settings = $this->config('metatag.settings');
    $entity_type_groups = $settings->get('entity_type_groups');

    // Find the current entity type and bundle.
    $entity_bundle = NULL;
    $metatag_defaults_id = $metatag_defaults->id();
    if (!empty($metatag_defaults_id)) {
      $type_parts = explode('__', $metatag_defaults_id);
      $entity_type = $type_parts[0];
      $entity_bundle = $type_parts[1] ?? NULL;
    }

    // See if there are requested groups for this entity type and bundle.
    if (isset($entity_type) && !empty($entity_type_groups[$entity_type]) && !empty($entity_type_groups[$entity_type][$entity_bundle])) {
      $form = $this->metatagManager->form($values, $form, [$entity_type], $entity_type_groups[$entity_type][$entity_bundle], NULL, TRUE);
    }
    // Otherwise, display all groups.
    else {
      $form = $this->metatagManager->form($values, $form, [], NULL, NULL, TRUE);
    }

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#default_value' => $metatag_defaults->status(),
    ];
    if ($metatag_defaults_id === 'global') {
      // Disabling global prevents any metatags from working.
      // Warn users about this.
      $form['status']['#description'] = $this->t('Warning: disabling the Global default metatag will prevent any metatags from being used.');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function rebuildForm(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    if (isset($actions['delete'])) {
      $actions['delete']['#access'] = $actions['delete']['#access'] && !in_array($this->entity->id(), MetatagManager::protectedDefaults());
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement()['#name'] == 'select_id_submit') {
      $form_state->set('default_type', $form_state->getValue('id'));
      $form_state->setRebuild();
    }
    else {
      parent::submitForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $metatag_defaults = $this->entity;

    $metatag_defaults->setStatus($form_state->getValue('status'));

    // Set the label on new defaults.
    if ($metatag_defaults->isNew()) {
      $metatag_defaults_id = $form_state->getValue('id');

      $type_parts = explode('__', $metatag_defaults_id);
      $entity_type = $type_parts[0];
      $entity_bundle = $type_parts[1] ?? NULL;

      // Get the entity label.
      $entity_info = $this->entityTypeManager->getDefinitions();
      $entity_label = (string) $entity_info[$entity_type]->get('label');

      if (!is_null($entity_bundle)) {
        // Get the bundle label.
        $bundle_info = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
        if ($entity_type === 'page_variant') {
          // Check if page manager is enabled and try to load the page variant
          // so the label of the variant can be used.
          if ($this->moduleHandler->moduleExists('metatag_page_manager')) {
            $page_variant = PageVariant::load($entity_bundle);
            $page = $page_variant->getPage();
            if ($page_variant) {
              $entity_label .= ': ' . $page->label() . ': ' . $page_variant->label();
            }
          }
        }
        else {
          $entity_label .= ': ' . $bundle_info[$entity_bundle]['label'];
        }
      }

      // Set the label to the config entity.
      $this->entity->set('label', $entity_label);
    }

    // Set tags within the Metatag entity.
    $tags = $this->metatagManager->sortedTags();
    $tag_values = [];
    foreach ($tags as $tag_id => $tag_definition) {
      if ($form_state->hasValue($tag_id)) {
        // Some plugins need to process form input before storing it. Hence, we
        // set it and then get it.
        $tag = $this->metatagPluginManager->createInstance($tag_id);
        $tag->setValue($form_state->getValue($tag_id));
        if (!empty($tag->value())) {
          $tag_values[$tag_id] = $tag->value();
        }
      }
    }

    // Sort the values prior to saving. so that they are easier to manage.
    ksort($tag_values);

    $metatag_defaults->set('tags', $tag_values);
    /** @var int $status */
    $status = $metatag_defaults->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Metatag defaults.', [
          '%label' => $metatag_defaults->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Metatag defaults.', [
          '%label' => $metatag_defaults->label(),
        ]));
    }

    $form_state->setRedirectUrl($metatag_defaults->toUrl('collection'));

    return $status;
  }

  /**
   * Returns an array of available bundles to override.
   *
   * @return array
   *   A list of available bundles as $id => $label.
   */
  protected function getAvailableBundles(): array {
    $options = [];
    $entity_types = static::getSupportedEntityTypes();
    $metatags_defaults_manager = $this->entityTypeManager->getStorage('metatag_defaults');
    foreach ($entity_types as $entity_type => $entity_label) {
      if (empty($metatags_defaults_manager->load($entity_type))) {
        $options[$entity_label][$entity_type] = "$entity_label (Default)";
      }

      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      foreach ($bundles as $bundle_id => $bundle_metadata) {
        $metatag_defaults_id = $entity_type . '__' . $bundle_id;

        if (empty($metatags_defaults_manager->load($metatag_defaults_id))) {
          $options[$entity_label][$metatag_defaults_id] = $bundle_metadata['label'];
        }
      }
    }
    return $options;
  }

  /**
   * Returns a list of supported entity types.
   *
   * @return array
   *   A list of available entity types as $machine_name => $label.
   */
  public static function getSupportedEntityTypes(): array {
    $entity_types = [];

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');

    // A list of entity types that are not supported.
    $unsupported_types = [
      // Custom blocks.
      'block_content',
      // Contact messages are the messages submitted on individual contact forms
      // so obviously shouldn't get meta tags.
      'contact_message',
      // Menu items.
      'menu_link_content',
      // Path aliases.
      'path_alias',
      // Shortcut items.
      'shortcut',
      // From contributed modules:
      // Various Commerce entities.
      'commerce_order',
      'commerce_payment',
      'commerce_payment_method',
      'commerce_promotion',
      'commerce_promotion_coupon',
      'commerce_shipment',
      'commerce_shipping_method',
      'commerce_stock_location',
      // LinkChecker.
      'linkcheckerlink',
      // Redirect.
      'redirect',
      // Salesforce.
      'salesforce_mapped_object',
      // Webform.
      'webform_submission',
    ];

    // Make a list of supported content types.
    foreach ($entity_type_manager->getDefinitions() as $entity_name => $definition) {
      // Skip some entity types that we don't want to support.
      if (in_array($entity_name, $unsupported_types)) {
        continue;
      }

      // Identify supported entities.
      if ($definition instanceof ContentEntityType) {
        // Only work with entity types that have a list of links, i.e. publicly
        // viewable.
        $links = $definition->get('links');
        if (!empty($links)) {
          $entity_types[$entity_name] = static::getEntityTypeLabel($definition);
        }
      }
    }

    return $entity_types;
  }

  /**
   * Returns the text label for the entity type specified.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type to process.
   *
   * @return string
   *   A label.
   */
  public static function getEntityTypeLabel(EntityTypeInterface $entityType): string {
    $label = $entityType->getLabel();

    if (is_a($label, 'Drupal\Core\StringTranslation\TranslatableMarkup')) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
      $label = $label->render();
    }

    return $label;
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\metatag\MetatagDefaultsInterface $metatag_defaults
   *   Metatags default entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translated route title.
   */
  public function getTitle(MetatagDefaultsInterface $metatag_defaults): TranslatableMarkup {
    return $this->t('Edit default meta tags for @path', [
      '@path' => $metatag_defaults->label(),
    ]);
  }

}
