<?php

namespace Drupal\flag\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagInterface;

/**
 * Provides the Flag configuration entity.
 *
 * @ConfigEntityType(
 *   id = "flag",
 *   label = @Translation("Flag"),
 *   label_singular = @Translation("flag"),
 *   label_plural = @Translation("flags"),
 *   label_count = @PluralTranslation(
 *     singular = "@count flag",
 *     plural = "@count flags",
 *   ),
 *   admin_permission = "administer flags",
 *   handlers = {
 *     "list_builder" = "Drupal\flag\Controller\FlagListBuilder",
 *     "form" = {
 *       "add" = "Drupal\flag\Form\FlagAddForm",
 *       "edit" = "Drupal\flag\Form\FlagEditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   bundle_of = "flagging",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "uuid",
 *     "label",
 *     "bundles",
 *     "entity_type",
 *     "global",
 *     "weight",
 *     "flag_short",
 *     "flag_long",
 *     "flag_message",
 *     "unflag_short",
 *     "unflag_long",
 *     "unflag_message",
 *     "unflag_denied_text",
 *     "flag_type",
 *     "link_type",
 *     "flagTypeConfig",
 *     "linkTypeConfig",
 *   },
 *   lookup_keys = {
 *     "global",
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/flags/manage/{flag}",
 *     "delete-form" = "/admin/structure/flags/manage/{flag}/delete",
 *     "collection" = "/admin/structure/flags",
 *     "enable" = "/admin/structure/flags/manage/{flag}/enable",
 *     "disable" = "/admin/structure/flags/manage/{flag}/disable",
 *     "reset" = "/admin/structure/flags/manage/{flag}/reset"
 *   }
 * )
 */
class Flag extends ConfigEntityBundleBase implements FlagInterface {

  /**
   * The entity type this flag works with.
   *
   * @var string
   */
  protected $entity_type = NULL;

  /**
   * Whether this flag state should act as a single toggle to all users.
   *
   * @var bool
   */
  protected $global = FALSE;

  /**
   * The bundles this flag applies to.
   *
   * This may be an empty array to indicate all bundles apply.
   *
   * @var array
   */
  protected $bundles = [];

  /**
   * The text for the "flag this" link for this flag.
   *
   * @var string
   */
  protected $flag_short = '';

  /**
   * The description of the "flag this" link.
   *
   * @var string
   */
  protected $flag_long = '';

  /**
   * Message displayed after flagging an entity.
   *
   * @var string
   */
  protected $flag_message = '';

  /**
   * The text for the "unflag this" link for this flag.
   *
   * @var string
   */
  protected $unflag_short = '';

  /**
   * The description of the "unflag this" link.
   *
   * @var string
   */
  protected $unflag_long = '';

  /**
   * Message displayed after flagging an entity.
   *
   * @var string
   */
  protected $unflag_message = '';

  /**
   * Message displayed if users aren't allowed to unflag.
   *
   * @var string
   */
  protected $unflag_denied_text = '';

  /**
   * The ID of the FlagType plugin.
   *
   * @var string
   */
  protected $flag_type;

  /**
   * A collection to store the FlagType plugin.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $flagTypeCollection;

  /**
   * An array to store and load the FlagType plugin configuration.
   *
   * @var array
   */
  protected $flagTypeConfig = [];

  /**
   * The ID of the ActionLink plugin.
   *
   * @var string
   * @see \Drupal\flag\ActionLink\ActionLinkTypeBase
   */
  protected $link_type = 'reload';

  /**
   * A collection to store the ActionLink plugin.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $linkTypeCollection;

  /**
   * An array to store and load the ActionLink plugin configuration.
   *
   * @var array
   */
  protected $linkTypeConfig = [];

  /**
   * The weight of the flag.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function isFlagged(EntityInterface $entity, ?AccountInterface $account = NULL, ?string $session_id = NULL) {
    \Drupal::service('flag')->populateFlaggerDefaults($account, $session_id);

    // Load the is flagged list from the flagging storage, check if this flag
    // is in the list.
    /** @var \Drupal\flag\Entity\Storage\FlaggingStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('flagging');
    $flag_ids = $storage->loadIsFlagged($entity, $account, $session_id);
    return isset($flag_ids[$this->id()]);

  }

  /**
   * {@inheritdoc}
   */
  public function getFlaggableEntityTypeId() {
    return $this->entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    return $this->bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getApplicableBundles() {
    $bundles = $this->getBundles();

    if (empty($bundles)) {
      // If the setting is empty, return all bundle names for the flag's entity
      // type.
      /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info_service */
      $bundle_info_service = \Drupal::service('entity_type.bundle.info');
      $bundle_info = $bundle_info_service->getBundleInfo($this->entity_type);
      $bundles = array_keys($bundle_info);
    }

    return $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'flagTypeConfig' => $this->getFlagTypeCollection(),
      'linkTypeConfig' => $this->getLinkTypeCollection(),
    ];
  }

  /**
   * Encapsulates the creation of the flag type's plugin collection.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   *   The flag type's plugin collection.
   */
  protected function getFlagTypeCollection() {
    if (!$this->flagTypeCollection) {
      $this->flagTypeCollection = new DefaultSingleLazyPluginCollection(
        \Drupal::service('plugin.manager.flag.flagtype'),
        $this->flag_type, $this->flagTypeConfig
      );
    }
    return $this->flagTypeCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getFlagTypePlugin() {
    return $this->getFlagTypeCollection()->get($this->flag_type);
  }

  /**
   * {@inheritdoc}
   */
  public function setFlagTypePlugin($plugin_id) {
    $this->flag_type = $plugin_id;
    // $this->flagTypeBag->addInstanceId($pluginID);
    // Workaround for https://www.drupal.org/node/2288805
    $this->flagTypeCollection = new DefaultSingleLazyPluginCollection(
      \Drupal::service('plugin.manager.flag.flagtype'),
      $this->flag_type, $this->flagTypeConfig
    );

    // Get the entity type from the plugin definition.
    /** @var \Drupal\flag\Plugin\Flag\EntityFlagType $plugin */
    $plugin = $this->getFlagTypePlugin();
    $plugin_def = $plugin->getPluginDefinition();
    $this->entity_type = $plugin_def['entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkTypePlugin() {
    return $this->getLinkTypeCollection()->get($this->link_type);
  }

  /**
   * Encapsulates the creation of the link type's plugin collection.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   *   The link type's plugin collection.
   */
  protected function getLinkTypeCollection() {
    if (!$this->linkTypeCollection) {
      $this->linkTypeCollection = new DefaultSingleLazyPluginCollection(
        \Drupal::service('plugin.manager.flag.linktype'),
        $this->link_type, $this->linkTypeConfig
      );
    }
    return $this->linkTypeCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function setlinkTypePlugin($plugin_id) {
    $this->link_type = $plugin_id;

    // $this->linkTypeBag->addInstanceId($pluginID);
    // Workaround for https://www.drupal.org/node/2288805
    $this->linkTypeCollection = new DefaultSingleLazyPluginCollection(
      \Drupal::service('plugin.manager.flag.linktype'),
      $this->link_type, $this->linkTypeConfig
    );
  }

  /**
   * {@inheritdoc}
   */
  public function actionPermissions() {
    return $this->getFlagTypePlugin()->actionPermissions($this);
  }

  /**
   * {@inheritdoc}
   */
  public function actionAccess($action, ?AccountInterface $account = NULL, ?EntityInterface $flaggable = NULL) {
    $account = $account ?: \Drupal::currentUser();
    return $this->getFlagTypePlugin()->actionAccess($action, $this, $account, $flaggable);
  }

  /**
   * {@inheritdoc}
   */
  public function isGlobal() {
    return $this->global;
  }

  /**
   * {@inheritdoc}
   */
  public function setGlobal($global = TRUE) {
    if ($global) {
      $this->global = TRUE;
    }
    else {
      $this->global = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setFlagShortText($text) {
    $this->flag_short = $text;
  }

  /**
   * {@inheritdoc}
   */
  public function getShortText($action) {
    return $action === 'unflag' ? $this->unflag_short : $this->flag_short;
  }

  /**
   * {@inheritdoc}
   */
  public function getLongText($action) {
    return $action === 'unflag' ? $this->unflag_long : $this->flag_long;
  }

  /**
   * {@inheritdoc}
   */
  public function setFlagLongText($flag_long) {
    $this->flag_long = $flag_long;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($action) {
    return $action === 'unflag' ? $this->unflag_message : $this->flag_message;
  }

  /**
   * {@inheritdoc}
   */
  public function setFlagMessage($flag_message) {
    $this->flag_message = $flag_message;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnflagLongText($unflag_long) {
    $this->unflag_long = $unflag_long;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnflagMessage($unflag_message) {
    $this->unflag_message = $unflag_message;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnflagShortText($unflag_short) {
    $this->unflag_short = $unflag_short;
  }

  /**
   * {@inheritdoc}
   */
  public function getUnflagDeniedText() {
    return $this->unflag_denied_text;
  }

  /**
   * {@inheritdoc}
   */
  public function setUnflagDeniedText($unflag_denied_text) {
    $this->unflag_denied_text = $unflag_denied_text;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $bundles = array_filter($this->get('bundles'));
    sort($bundles);

    $this->set('bundles', $bundles);

    /*
    // Save the Flag Type configuration.
    $flagTypePlugin = $this->getFlagTypePlugin();
    $this->set('flagTypeConfig', $flagTypePlugin->getConfiguration());

    // Save the Link Type configuration.
    $linkTypePlugin = $this->getLinkTypePlugin();
    $this->set('linkTypeConfig', $linkTypePlugin->getConfiguration());
     */

    // Reset the render cache for the entity.
    \Drupal::entityTypeManager()
      ->getViewBuilder($this->getFlaggableEntityTypeId())
      ->resetCache();

    // Clear entity extra field caches.
    // @todo Inject the entity field manager into the object?
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    foreach ($entities as $flag) {
      \Drupal::service('flag')->unflagAllByFlag($flag);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    if (\Drupal::moduleHandler()->moduleExists('views')) {
      // Rebuild views data to invalidate flag relationships.
      \Drupal::service('views.views_data')->clear();
    }
    \Drupal::service('plugin.manager.action')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    if (\Drupal::moduleHandler()->moduleExists('views')) {
      // Rebuild views data to invalidate flag relationships.
      \Drupal::service('views.views_data')->clear();
    }
    \Drupal::service('plugin.manager.action')->clearCachedDefinitions();
  }

  /**
   * Sorts the flag entities, putting disabled flags at the bottom.
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityBase::sort()
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {

    // Check if the entities are flags, if not go with the default.
    if ($a instanceof FlagInterface && $b instanceof FlagInterface) {

      if ($a->status() && $b->status()) {
        return parent::sort($a, $b);
      }
      elseif (!$a->status()) {
        return -1;
      }
      elseif (!$b->status()) {
        return 1;
      }
    }

    return parent::sort($a, $b);
  }

}
