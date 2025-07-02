<?php

namespace Drupal\auto_entitylabel\Form;

use Drupal\auto_entitylabel\AutoEntityLabelManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Administrative form to enable/configure auto_entitylabel on an entity type.
 */
class AutoEntityLabelForm extends ConfigFormBase {

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route matcher.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * User.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $user;

  /**
   * The entity type machine name.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The bundle machine name.
   *
   * @var string
   */
  protected $entityBundle;

  /**
   * The entity type that our config entity describes bundles of.
   *
   * @var string
   */
  protected $entityTypeBundleOf;

  /**
   * AutoEntityLabelController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route Match.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module Handler.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Account Interface.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    ModuleHandlerInterface $moduleHandler,
    AccountInterface $user,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $route_options = $this->routeMatch->getRouteObject()->getOptions();
    $array_keys = array_keys($route_options['parameters']);
    $this->entityType = array_shift($array_keys);
    $entity_type = $this->routeMatch->getParameter($this->entityType);
    if (!empty($entity_type)) {
      $this->entityBundle = $entity_type->id();
      $this->entityTypeBundleOf = $entity_type->getEntityType()->getBundleOf();
    }
    else {
      $this->entityBundle = $this->entityType;
      $this->entityTypeBundleOf = $this->entityType;
    }
    $this->moduleHandler = $moduleHandler;
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('module_handler'),
      $container->get('current_user')
    );
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'auto_entitylabel.settings',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'auto_entitylabel_settings_form';
  }

  /**
   * Get the config name for this entity & bundle.
   *
   * @return string
   *   The compiled config name.
   */
  protected function getConfigName() {
    return 'auto_entitylabel.settings.' . $this->entityTypeBundleOf . '.' . $this->entityBundle;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config($this->getConfigName());
    /*
     * @todo
     *  Find a generic way of determining if the label is rendered on the
     *  entity form. If not, don't show 'auto_entitylabel_optional' option.
     */
    $options = [
      AutoEntityLabelManager::DISABLED => $this->t('Disabled'),
      AutoEntityLabelManager::ENABLED => $this->t('Automatically generate the label and hide the label field'),
      AutoEntityLabelManager::OPTIONAL => $this->t('Automatically generate the label if the label field is left empty'),
      AutoEntityLabelManager::PREFILLED => $this->t('Automatically prefill the label'),
    ];

    // Create an array for description of the options.
    $options_description = [
      AutoEntityLabelManager::DISABLED => [
        '#description' => $this->t('Selecting this option will disable the auto labels for the entity.'),
      ],
      AutoEntityLabelManager::ENABLED => [
        '#description' => $this->t('Selecting this option will hide the title field and will generate a new option based on the pattern provided below.'),
      ],
      AutoEntityLabelManager::OPTIONAL => [
        '#description' => $this->t('Selecting this option will make the label field optional and will generate a label if the label field is left empty.'),
      ],
      AutoEntityLabelManager::PREFILLED => [
        '#description' => $this->t('Selecting this option will prefills the label field with the generated pattern provided below. This option provides limited token support because it only prefills the label and it will not be able to replace all the tokens like current node based tokens for ex: [node:nid] because that token has not been generated yet.'),
      ],
    ];
    // Shared across most of the settings on this page.
    $invisible_state = [
      'invisible' => [
        ':input[name="status"]' => ['value' => AutoEntityLabelManager::DISABLED],
      ],
    ];

    $form['auto_entitylabel'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Automatic label generation for @type', ['@type' => $this->entityBundle]),
      '#weight' => 0,
    ];

    $form['auto_entitylabel']['status'] = [
      '#type' => 'radios',
      '#default_value' => $config->get('status') ?: 0,
      '#options' => $options,
    ];
    $form['auto_entitylabel']['status'] += $options_description;

    $form['auto_entitylabel']['pattern'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Pattern for the label'),
      '#description' => $this->t('Leave blank for using the per default generated label. Otherwise this string will be used as label. Use the syntax [token] if you want to insert a replacement pattern.
      <br>Pattern string can be translated via User interface translation by searching for the string <b>@pattern</b>.'),
      '#default_value' => $config->get('pattern') ?: '',
      '#attributes' => ['class' => ['pattern-label']],
      '#states' => $invisible_state,
    ];

    // Display the list of available placeholders if token module is installed.
    if ($this->moduleHandler->moduleExists('token')) {
      // Special treatment for Core's taxonomy_vocabulary and taxonomy_term.
      $token_type = strtr($this->entityTypeBundleOf, ['taxonomy_' => '']);
      $form['auto_entitylabel']['token_help'] = [
        // #states needs a container to work, put token replacement link inside
        '#type' => 'container',
        '#states' => $invisible_state,
        'token_link' => [
          '#theme' => 'token_tree_link',
          '#token_types' => [$token_type],
          '#dialog' => TRUE,
        ],
      ];
    }
    else {
      $form['auto_entitylabel']['pattern']['#description'] .= ' ' . $this->t('To get a list of available tokens install <a href=":drupal-token" target="blank">Token</a> module.', [':drupal-token' => 'https://www.drupal.org/project/token']);
    }

    $form['auto_entitylabel']['escape'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Remove special characters.'),
      '#description' => $this->t('Check this to remove all special characters.'),
      '#default_value' => $config->get('escape'),
      '#states' => $invisible_state,
    ];

    $form['auto_entitylabel']['preserve_titles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Preserve already created node titles.'),
      '#description' => $this->t('Check this to preserve the titles of the nodes that were already created.'),
      '#default_value' => $config->get('preserve_titles'),
      '#states' => [
        'visible' => [
          ':input[name="status"]' => ['value' => AutoEntityLabelManager::ENABLED],
        ],
      ],
    ];

    $newActionOptions = [
      AutoEntityLabelManager::BEFORE_SAVE => $this->t('Create label before first save'),
      AutoEntityLabelManager::AFTER_SAVE => $this->t('Create label after first save'),
    ];
    $newActionDescriptions = [
      AutoEntityLabelManager::BEFORE_SAVE => [
        '#description' => $this->t('Create the label before saving the content. This option is faster but does not support all tokens (ie the id token).'),
      ],
      AutoEntityLabelManager::AFTER_SAVE => [
        '#description' => $this->t('Create the label after saving the content. All tokens are supported however the content will be saved twice. This may interfere with other modules.'),
      ],
    ];

    $form['auto_entitylabel']['new_content_behavior'] = [
      '#type' => 'radios',
      '#title' => $this->t('New content behavior'),
      '#description' => $this->t('Select when to create the automatic label for new content of type %type', ['%type' => $this->entityBundle]),
      '#default_value' => $config->get('new_content_behavior') ?: AutoEntityLabelManager::BEFORE_SAVE,
      '#options' => $newActionOptions,
    ];
    $form['auto_entitylabel']['new_content_behavior'] += $newActionDescriptions;

    $form['#attached']['library'][] = 'auto_entitylabel/auto_entitylabel.admin';

    $form['auto_entitylabel']['save'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Re-save'),
      '#description' => $this->t('Re-save all labels.'),
      '#default_value' => $config->get('save'),
    ];

    $form['auto_entitylabel']['chunk'] = [
      '#type' => 'number',
      '#title' => $this->t('Chunk size'),
      '#description' => $this->t('Number of entities to be processed per batch operation.'),
      '#default_value' => 50,
      '#min' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable($this->getConfigName());
    $form_state->cleanValues();
    foreach (
      [
        'status',
        'pattern',
        'escape',
        'preserve_titles',
        'new_content_behavior',
        'save',
        'chunk',
      ] as $key) {
      $config->set($key, $form_state->getValue($key));
    }

    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorage $storage */
    $storage = $this->entityTypeManager->getStorage($this->entityType);
    /** @var \Drupal\Core\Config\Entity\ConfigEntityType $entity_type */
    $entity_type = $storage->getEntityType();
    if ($entity_type instanceof ConfigEntityType) {
      $prefix = $entity_type->getConfigPrefix();
      $bundle = $entity_type->getBundleOf();

      $config->set('dependencies', ['config' => [$prefix . '.' . $this->entityBundle]]);
    }
    $config->save();

    // If user checked the re-save option, set batch for re-saving labels.
    if ($config->get('save')) {
      $this->setBatch($this->entityBundle, $bundle, $config->get('chunk'));
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * Prepares a batch for resaving all labels.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string|null $bundle
   *   The bundle.
   * @param int $chunk
   *   The chunk to handle in the batch.
   */
  protected function setBatch($entity_type, $bundle, $chunk) {
    $ids = $this->getIds($entity_type, $bundle);
    $chunks = array_chunk($ids, $chunk);
    $num_chunks = count($chunks);

    // Re-save all labels chunk by chunk.
    $operations = [];
    for ($chunk_iterator = 0; $chunk_iterator < $num_chunks; $chunk_iterator++) {
      $operations[] = [
        '\Drupal\auto_entitylabel\Batch\ResaveBatch::batchOperation',
        [$chunks[$chunk_iterator], [$bundle]],
      ];
    }

    $batch = [
      'title' => $this->t('Re-saving labels'),
      'progress_message' => $this->t('Completed @current out of @total chunks.'),
      'finished' => '\Drupal\auto_entitylabel\Batch\ResaveBatch::batchFinished',
      'operations' => $operations,
    ];

    batch_set($batch);
  }

  /**
   * Get the IDs for for an entity type.
   *
   * @param string $entity_type
   *   The entity type to get the IDs for.
   * @param string|null $bundle
   *   The bundle to get the IDs for.
   *
   * @return array
   *   An array with IDs.
   */
  public function getIds($entity_type, $bundle) {
    $type_definition = $this->entityTypeManager->getDefinition($bundle);
    $bundle_field = $type_definition->getKey('bundle');
    $query = $this->entityTypeManager->getStorage($bundle)->getQuery()->accessCheck(TRUE);
    return $query->condition($bundle_field, $entity_type, 'IN')->execute();
  }

}
