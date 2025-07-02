<?php

namespace Drupal\flag\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\flag\ActionLink\ActionLinkPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base flag add/edit form.
 *
 * Since both the add and edit flag forms are largely the same, the majority of
 * functionality is done in this class. It generates the form, validates the
 * input, and handles the submit.
 */
abstract class FlagFormBase extends EntityForm {

  /**
   * The action link plugin manager.
   *
   * @var \Drupal\flag\ActionLink\ActionLinkPluginManager
   */
  protected $actionLinkManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfoService;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new form.
   *
   * @param \Drupal\flag\ActionLink\ActionLinkPluginManager $action_link_manager
   *   The link type plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info_service
   *   The bundle info service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ActionLinkPluginManager $action_link_manager, EntityTypeBundleInfoInterface $bundle_info_service, LinkGeneratorInterface $link_generator, EntityTypeManagerInterface $entity_type_manager) {
    $this->actionLinkManager = $action_link_manager;
    $this->bundleInfoService = $bundle_info_service;
    $this->linkGenerator = $link_generator;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.flag.linktype'),
      $container->get('entity_type.bundle.info'),
      $container->get('link_generator'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = NULL) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\flag\FlagInterface $flag */
    $flag = $this->entity;

    $form['#flag'] = $flag;
    $form['#flag_name'] = $flag->id();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $flag->label(),
      '#description' => $this->t('Name or label of this flag. It will be used as hostname in various drupal entities. Some examples could be <em>Bookmarks</em>, <em>Favorites</em>, or <em>Offensive</em>.'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -3,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $flag->id(),
      '#description' => $this->t('The machine-name for this flag. It may be up to 32 characters long and may only contain lowercase letters, underscores, and numbers. It will be used in URLs and in all API calls.'),
      '#weight' => -2,
      '#machine_name' => [
        'exists' => '\Drupal\flag\Entity\Flag::load',
      ],
      '#disabled' => !$flag->isNew(),
      '#required' => TRUE,
    ];

    $form['global'] = [
      '#type' => 'radios',
      '#title' => $this->t('Scope'),
      '#default_value' => $flag->isGlobal() ? 1 : 0,
      '#options' => [
        0 => $this->t('Personal'),
        1 => $this->t('Global'),
      ],
      '#weight' => -1,
    ];

    // Add descriptions for each radio button.
    $form['global'][0]['#description'] = $this->t('Each user has individual flags on entities.');
    $form['global'][1]['#description'] = $this->t('The entity is either flagged or not for all users.');

    $form['messages'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Messages'),
    ];

    $form['messages']['flag_short'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flag link text'),
      '#default_value' => $flag->get('flag_short') ?: $this->t('Flag this item'),
      '#description' => $this->t('The text for the "flag this" link for this flag.'),
      '#required' => TRUE,
    ];

    $form['messages']['flag_long'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flag link description'),
      '#default_value' => $flag->get('flag_long'),
      '#description' => $this->t('The description of the "flag this" link. Usually displayed on mouseover.'),
    ];

    $form['messages']['flag_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Flagged message'),
      '#default_value' => $flag->get('flag_message'),
      '#description' => $this->t('Message displayed after flagging content. If JavaScript is enabled, it will be displayed below the link. If not, it will be displayed in the message area.'),
    ];

    $form['messages']['unflag_short'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unflag link text'),
      '#default_value' => $flag->get('unflag_short') ?: $this->t('Unflag this item'),
      '#description' => $this->t('The text for the "unflag this" link for this flag.'),
      '#required' => TRUE,
    ];

    $form['messages']['unflag_long'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unflag link description'),
      '#default_value' => $flag->get('unflag_long'),
      '#description' => $this->t('The description of the "unflag this" link. Usually displayed on mouseover.'),
    ];

    $form['messages']['unflag_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unflagged message'),
      '#default_value' => $flag->get('unflag_message'),
      '#description' => $this->t('Message displayed after content has been unflagged. If JavaScript is enabled, it will be displayed below the link. If not, it will be displayed in the message area.'),
    ];

    $form['access'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Flag access'),
      '#tree' => FALSE,
      '#weight' => 10,
    ];

    // Switch plugin type in case a different is chosen.
    /** @var \Drupal\flag\Plugin\Flag\EntityFlagType $flag_type_plugin */
    $flag_type_plugin = $flag->getFlagTypePlugin();
    $flag_type_def = $flag_type_plugin->getPluginDefinition();

    $bundles = $this->bundleInfoService->getBundleInfo($flag_type_def['entity_type']);
    $entity_bundles = [];
    foreach ($bundles as $bundle_id => $bundle_row) {
      $entity_bundles[$bundle_id] = $bundle_row['label'];
    }

    // Flag classes will want to override this form element.
    $form['access']['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Flaggable types'),
      '#options' => $entity_bundles,
      '#default_value' => $flag->getBundles(),
      '#description' => $this->t('Check any bundles that this flag may be used on. Leave empty to apply to all bundles.'),
      '#weight' => 10,
    ];

    $form['access']['unflag_denied_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unflag not allowed text'),
      '#default_value' => $flag->getUnflagDeniedText(),
      '#description' => $this->t('If a user is allowed to flag but not unflag, this text will be displayed after flagging. Often this is the past-tense of the link text, such as "flagged".'),
      '#weight' => -1,
    ];

    $form['display'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Display options'),
      '#description' => $this->t('Flags are usually controlled through links that allow users to toggle their behavior. You can choose how users interact with flags by changing options here. It is legitimate to have none of the following checkboxes ticked, if, for some reason, you wish <a href="@placement-url">to place the links on the page yourself</a>.', ['@placement-url' => 'http://drupal.org/node/295383']),
      '#tree' => FALSE,
      '#weight' => 20,
      '#prefix' => '<div id="link-type-settings-wrapper">',
      '#suffix' => '</div>',
      // @todo Move flag_link_type_options_states() into controller?
      // '#after_build' => array('flag_link_type_options_states'),
    ];

    $form['display']['settings'] = [
      '#type' => 'container',
      '#weight' => 21,
    ];

    $form = $flag_type_plugin->buildConfigurationForm($form, $form_state);
    /** @var \Drupal\flag\Plugin\Flag\EntityFlagType $flag_link_type_plugin */
    $flag_link_type_plugin = $flag->getLinkTypePlugin();

    $form['display']['link_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Link type'),
      '#options' => $this->actionLinkManager->getAllLinkTypes(),
      // '#after_build' => array('flag_check_link_types'),
      '#default_value' => $flag_link_type_plugin->getPluginId(),
      // Give this a high weight so additions by the flag classes for entity-
      // specific options go above.
      '#weight' => 18,
      '#attributes' => [
        'class' => ['flag-link-options'],
      ],
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateSelectedPluginType',
        'wrapper' => 'link-type-settings-wrapper',
        'event' => 'change',
        'method' => 'replaceWith',
      ],
    ];
    $form['display']['link_type_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#submit' => ['::submitSelectPlugin'],
      '#weight' => 20,
      '#attributes' => ['class' => ['js-hide']],
    ];
    // Add the descriptions to each ratio button element. These attach to the
    // elements when FormAPI expands them.
    $action_link_plugin_defs = $this->actionLinkManager->getDefinitions();
    foreach ($action_link_plugin_defs as $key => $info) {
      $form['display']['link_type'][$key] = [
        '#description' => $info['description'],
        '#executes_submit_callback' => TRUE,
        '#limit_validation_errors' => [['link_type']],
        '#submit' => ['::submitSelectPlugin'],
      ];
    }

    $action_link_plugin = $flag->getLinkTypePlugin();
    $form = $action_link_plugin->buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * Handles switching the configuration type selector.
   */
  public function updateSelectedPluginType($form, FormStateInterface $form_state) {
    return $form['display'];
  }

  /**
   * Handles submit call when sensor type is selected.
   */
  public function submitSelectPlugin(array $form, FormStateInterface $form_state) {
    // Rebuild the entity using the form's new state.
    $this->entity = $this->buildEntity($form, $form_state);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\flag\FlagInterface $entity */
    $entity = parent::buildEntity($form, $form_state);
    // Update the link type plugin.
    // @todo Do this somewhere else?
    $entity->setLinkTypePlugin($entity->get('link_type'));
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    /** @var \Drupal\flag\FlagInterface $flag */
    $flag = $this->entity;
    $flag->getFlagTypePlugin()->validateConfigurationForm($form, $form_state);
    $flag->getLinkTypePlugin()->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\flag\FlagInterface $flag */
    $flag = $this->entity;

    $flag->getFlagTypePlugin()->submitConfigurationForm($form, $form_state);
    $flag->getLinkTypePlugin()->submitConfigurationForm($form, $form_state);

    $status = $flag->save();

    $message_params = [
      '%label' => $flag->label(),
    ];
    $logger_params = [
      '%label' => $flag->label(),
      'link' => $flag->toLink($this->t('Edit'), 'edit-form')->toString(),
    ];

    if ($status == SAVED_UPDATED) {
      $this->messenger()->addMessage($this->t('Flag %label has been updated.', $message_params));
      $this->logger('flag')->notice('Flag %label has been updated.', $logger_params);
    }
    else {
      $this->messenger()->addMessage($this->t('Flag %label has been added.', $message_params));
      $this->logger('flag')->notice('Flag %label has been added.', $logger_params);
    }

    // We clear caches more vigorously if the flag was new.
    // _flag_clear_cache($flag->entity_type, !empty($flag->is_new));
    // Save permissions.
    // This needs to be done after the flag cache has been cleared, so that
    // the new permissions are picked up by hook_permission().
    // This may need to move to the flag class when we implement extra
    // permissions for different flag types: http://drupal.org/node/879988
    // If the flag ID has changed, clean up all the obsolete permissions.
    if ($flag->id() != $form['#flag_name']) {
      $old_name = $form['#flag_name'];
      $permissions = ["flag $old_name", "unflag $old_name"];
      $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
      foreach (array_keys($roles) as $rid) {
        user_role_revoke_permissions($rid, $permissions);
      }
    }
    // @todo when we add database caching for flags we'll have to clear the
    // cache again here.
    // @phpstan-ignore-next-line
    $form_state->setRedirect('entity.flag.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('flag_list');
  }

}
