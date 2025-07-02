<?php

namespace Drupal\paragraphs\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\paragraphs\Controller\ParagraphsTypeListBuilder;
use Drupal\paragraphs\Form\ParagraphsTypeDeleteConfirm;
use Drupal\paragraphs\Form\ParagraphsTypeForm;
use Drupal\paragraphs\ParagraphsBehaviorCollection;
use Drupal\paragraphs\ParagraphsTypeAccessControlHandler;
use Drupal\paragraphs\ParagraphsTypeInterface;
use Drupal\Core\File\FileSystemInterface;

  /**
 * Defines the ParagraphsType entity.
 *
 * @ConfigEntityType(
 *   id = "paragraphs_type",
 *   label = @Translation("Paragraphs type"),
 *   label_collection = @Translation("Paragraphs types"),
 *   label_singular = @Translation("Paragraphs type"),
 *   label_plural = @Translation("Paragraphs types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Paragraphs type",
 *     plural = "@count Paragraphs types",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\paragraphs\ParagraphsTypeAccessControlHandler",
 *     "list_builder" = "Drupal\paragraphs\Controller\ParagraphsTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\paragraphs\Form\ParagraphsTypeForm",
 *       "edit" = "Drupal\paragraphs\Form\ParagraphsTypeForm",
 *       "delete" = "Drupal\paragraphs\Form\ParagraphsTypeDeleteConfirm"
 *     }
 *   },
 *   config_prefix = "paragraphs_type",
 *   admin_permission = "administer paragraphs types",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "icon_uuid",
 *     "icon_default",
 *     "description",
 *     "behavior_plugins",
 *   },
 *   bundle_of = "paragraph",
 *   links = {
 *     "edit-form" = "/admin/structure/paragraphs_type/{paragraphs_type}",
 *     "delete-form" = "/admin/structure/paragraphs_type/{paragraphs_type}/delete",
 *     "collection" = "/admin/structure/paragraphs_type",
 *   }
 * )
 */
#[ConfigEntityType(
  id: 'paragraphs_type',
  label: new TranslatableMarkup('Paragraphs type'),
  label_collection: new TranslatableMarkup('Paragraphs types'),
  label_singular: new TranslatableMarkup('Paragraphs type'),
  label_plural: new TranslatableMarkup('Paragraphs types'),
  config_prefix: 'paragraphs_type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  handlers: [
    'access' => ParagraphsTypeAccessControlHandler::class,
    'list_builder' => ParagraphsTypeListBuilder::class,
    'form' => [
      'add' => ParagraphsTypeForm::class,
      'edit' => ParagraphsTypeForm::class,
      'delete' => ParagraphsTypeDeleteConfirm::class,
    ],
  ],
  links: [
    'edit-form' => '/admin/structure/paragraphs_type/[paragraphs_type]',
    'delete-form' => '/admin/structure/paragraphs_type/[paragraphs_type]/delete',
    'collection' => '/admin/structure/paragraphs_type',
  ],
  admin_permission: 'administer paragraphs types',
  bundle_of: 'paragraph',
  label_count: [
    'singular' => '@count Paragraphs type',
    'plural' => '@count Paragraphs types',
  ],
  config_export: [
    'id',
    'label',
    'icon_uuid',
    'icon_default',
    'description',
    'behavior_plugins',
  ]
)]
class ParagraphsType extends ConfigEntityBundleBase implements ParagraphsTypeInterface, EntityWithPluginCollectionInterface {

  /**
   * The ParagraphsType ID.
   *
   * @var string
   */
  public $id;

  /**
   * The ParagraphsType label.
   *
   * @var string
   */
  public $label;

  /**
   * A brief description of this paragraph type.
   *
   * @var string
   */
  public $description;

  /**
   * UUID of the Paragraphs type icon file.
   *
   * @var string
   */
  protected $icon_uuid;

  /**
   * Default icon encoded as data URL scheme (RFC 2397).
   *
   * @var string
   */
  protected $icon_default;

  /**
   * The Paragraphs type behavior plugins configuration keyed by their id.
   *
   * @var array
   */
  public $behavior_plugins = [];

  /**
   * Holds the collection of behavior plugins that are attached to this
   * Paragraphs type.
   *
   * @var \Drupal\paragraphs\ParagraphsBehaviorCollection
   */
  protected $behaviorCollection;

  /**
   * Restores the icon file from the default icon value.
   *
   * @return \Drupal\file\FileInterface|bool
   *   The icon's file entity or FALSE if no default icon set.
   */
  protected function restoreDefaultIcon() {
    // Default icon data in RFC 2397 format ("data" URL scheme).
    if ($this->icon_default && $icon_data = fopen($this->icon_default, 'r')) {
      // Compose the default icon file destination.
      $icon_meta = stream_get_meta_data($icon_data);
      // File extension from MIME, only JPG/JPEG, PNG and SVG expected.
      [, $icon_file_ext] = explode('image/', $icon_meta['mediatype']);
      // SVG special case.
      if ($icon_file_ext == 'svg+xml') {
        $icon_file_ext = 'svg';
      }

      $filesystem = \Drupal::service('file_system');
      $icon_upload_path = ParagraphsTypeInterface::ICON_UPLOAD_LOCATION;
      $icon_file_destination = $icon_upload_path . $this->id() . '-default-icon.' . $icon_file_ext;
      // Check the directory exists before writing data to it.
      $filesystem->prepareDirectory($icon_upload_path, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      // Save the default icon file.
      $icon_file_uri = $filesystem->saveData($icon_data, $icon_file_destination);
      if ($icon_file_uri) {
        // Create the icon file entity.
        $icon_entity_values = [
          'uri' => $icon_file_uri,
          'uid' => \Drupal::currentUser()->id(),
          'uuid' => $this->icon_uuid,
          'status' => FileInterface::STATUS_PERMANENT,
        ];

        // Delete existent icon file if it exists.
        if ($old_icon = $this->getFileByUuid($this->icon_uuid)) {
          $old_icon->delete();
        }

        $new_icon = File::create($icon_entity_values);
        $new_icon->save();
        $this->updateFileIconUsage($new_icon, $old_icon);
        return $new_icon;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getIconFile() {
    if ($this->icon_uuid !== NULL) {
      $icon = $this->getFileByUuid($this->icon_uuid) ?: $this->restoreDefaultIcon();
      if ($icon) {
        return $icon;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getBehaviorPlugins() {
    if (!isset($this->behaviorCollection)) {
      $this->behaviorCollection = new ParagraphsBehaviorCollection(\Drupal::service('plugin.manager.paragraphs.behavior'), $this->behavior_plugins);
    }
    return $this->behaviorCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getIconUrl() {
    if ($image = $this->getIconFile()) {
      return \Drupal::service('file_url_generator')->generateString($image->getFileUri());
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getBehaviorPlugin($instance_id) {
    return $this->getBehaviorPlugins()->get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Add the file icon entity as dependency if a UUID was specified.
    if ($this->icon_uuid && $file_icon = $this->getIconFile()) {
      $this->addDependency($file_icon->getConfigDependencyKey(), $file_icon->getConfigDependencyName());
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    $behavior_plugins = $this->getBehaviorPlugins();
    foreach ($dependencies['module'] as $module) {
      /** @var \Drupal\Component\Plugin\PluginInspectionInterface $plugin */
      foreach ($behavior_plugins as $instance_id => $plugin) {
        $definition = (array) $plugin->getPluginDefinition();
        // If a module providing a behavior plugin is being uninstalled,
        // remove the plugin and dependency so this paragraph bundle is not
        // deleted too.
        if (isset($definition['provider']) && $definition['provider'] === $module) {
          unset($this->behavior_plugins[$instance_id]);
          $this->getBehaviorPlugins()->removeInstanceId($instance_id);
          $changed = TRUE;
        }
      }
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledBehaviorPlugins() {
    return $this->getBehaviorPlugins()->getEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['behavior_plugins' => $this->getBehaviorPlugins()];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function hasEnabledBehaviorPlugin($plugin_id) {
    $plugins = $this->getBehaviorPlugins();
    if ($plugins->has($plugin_id)) {
      /** @var \Drupal\paragraphs\ParagraphsBehaviorInterface $plugin */
      $plugin = $plugins->get($plugin_id);
      $config = $plugin->getConfiguration();
      return (array_key_exists('enabled', $config) && $config['enabled'] === TRUE);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    if (!$update || $this->icon_uuid != $this->original->icon_uuid) {
      // Update the file usage for the icon file.
      $new_icon_file = $this->icon_uuid ? $this->getFileByUuid($this->icon_uuid) : FALSE;
      // Update the file usage of the old icon as well if the icon was changed.
      $old_icon_file = $update && $this->original->icon_uuid ? $this->getFileByUuid($this->original->icon_uuid) : FALSE;
      $this->updateFileIconUsage($new_icon_file, $old_icon_file);
    }

    parent::postSave($storage, $update);
  }

  /**
   * Gets the file entity defined by the UUID.
   *
   * @param string $uuid
   *   The file entity's UUID.
   *
   * @return \Drupal\file\FileInterface|null
   *   The file entity. NULL if the UUID is invalid.
   */
  protected function getFileByUuid($uuid) {
    if ($id = \Drupal::service('paragraphs_type.uuid_lookup')->get($uuid)) {
      return $this->entityTypeManager()->getStorage('file')->load($id);
    }

    return NULL;
  }

  /**
   * Updates the icon file usage information.
   *
   * @param \Drupal\file\FileInterface|mixed $new_icon
   *   The new icon file, FALSE on deletion.
   * @param \Drupal\file\FileInterface|mixed $old_icon
   *   (optional) Old icon, on update or deletion.
   */
  protected function updateFileIconUsage($new_icon, $old_icon = FALSE) {
    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = \Drupal::service('file.usage');

    // Clear the UUID lookup cache in case this changes an existing file.
    \Drupal::service('paragraphs_type.uuid_lookup')->clear();

    if ($new_icon) {
      // Add usage of the new icon file.
      $file_usage->add($new_icon, 'paragraphs', 'paragraphs_type', $this->id());
    }
    if ($old_icon) {
      // Delete usage of the old icon file.
      $file_usage->delete($old_icon, 'paragraphs', 'paragraphs_type', $this->id());
    }
  }

}
