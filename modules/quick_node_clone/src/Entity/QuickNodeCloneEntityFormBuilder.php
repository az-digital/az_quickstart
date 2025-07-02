<?php

namespace Drupal\quick_node_clone\Entity;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupRelationship;
use Drupal\node\Entity\Node;
use Drupal\Core\Render\Markup;

/**
 * Builds entity forms.
 */
class QuickNodeCloneEntityFormBuilder extends EntityFormBuilder {
  use StringTranslationTrait;

  /**
   * The Form Builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;
  /**
   * The Entity Bundle Type Info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;
  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  /**
   * The Module Handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;
  /**
   * The Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;
  /**
   * The Private Temp Store.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * QuickNodeCloneEntityFormBuilder constructor.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info provider.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $privateTempStoreFactory
   *   Private temp store factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   */
  public function __construct(FormBuilderInterface $formBuilder, EntityTypeBundleInfoInterface $entityTypeBundleInfo, ConfigFactoryInterface $configFactory, ModuleHandlerInterface $moduleHandler, EntityTypeManagerInterface $entityTypeManager, AccountInterface $currentUser, PrivateTempStoreFactory $privateTempStoreFactory, TranslationInterface $stringTranslation) {
    $this->formBuilder = $formBuilder;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->privateTempStoreFactory = $privateTempStoreFactory;
    $this->setStringTranslation($stringTranslation);
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(EntityInterface $original_entity, $operation = 'default', array $form_state_additions = []) {

    // Clone the node using the awesome createDuplicate() core function.
    /** @var \Drupal\node\Entity\Node $new_node */
    $new_node = $original_entity->createDuplicate();
    $new_node->set('uid', $this->currentUser->id());
    $new_node->set('created', time());
    $new_node->set('changed', time());
    $new_node->set('revision_timestamp', time());

    // Get and store groups of original entity, if any.
    $groups = [];
    if ($this->getConfigSettings('create_group_relationships') && $this->moduleHandler->moduleExists('gnode')) {
      $relation_class = class_exists(GroupContent::class) ? GroupContent::class : GroupRelationship::class;
      /** @var \Drupal\Core\Entity\ContentEntityInterface $original_entity */
      foreach ($relation_class::loadByEntity($original_entity) as $group_relationship) {
        $groups[] = $group_relationship->getGroup();
      }
    }
    $form_state_additions['quick_node_clone_groups_storage'] = $groups;

    // Get default status value of node bundle.
    $default_bundle_status = $this->entityTypeManager->getStorage('node')->create(['type' => $new_node->bundle()])->status->value;

    // Clone all translations of a node.
    foreach ($new_node->getTranslationLanguages() as $langcode => $language) {
      /** @var \Drupal\node\Entity\Node $translated_node */
      $translated_node = $new_node->getTranslation($langcode);
      $translated_node = $this->cloneParagraphs($translated_node);

      // Unset excluded fields.
      $config_name = 'exclude.node.' . $translated_node->getType();
      if ($exclude_fields = $this->getConfigSettings($config_name)) {
        foreach ($exclude_fields as $field) {
          unset($translated_node->{$field});
        }
      }

      $prepend_text = "";
      $title_prepend_config = $this->getConfigSettings('text_to_prepend_to_title');
      if (!empty($title_prepend_config)) {
        $prepend_text = $title_prepend_config . " ";
      }

      $clone_status_config = $this->getConfigSettings('clone_status');
      switch ($clone_status_config) {
        case 'original':
          break;

        case 'published':
          $translated_node->setPublished();
          break;

        case 'unpublished':
          $translated_node->setUnpublished();
          break;

        case 'default':
        default:
          $key = $translated_node->getEntityType()->getKey('published');
          $translated_node->set($key, $default_bundle_status);
          break;

      }

      $translated_node->setTitle($this->t('@prepend_text@title',
        [
          '@prepend_text' => Markup::create($prepend_text),
          '@title' => Markup::create($translated_node->getTitle()),
        ],
        [
          'langcode' => $langcode,
        ]
      )
      );

      $this->moduleHandler->alter('cloned_node', $translated_node, $original_entity);
    }

    // Get the form object for the entity defined in entity definition.
    $form_object = $this->entityTypeManager->getFormObject($translated_node->getEntityTypeId(), $operation);

    // Assign the form's entity to our duplicate!
    $form_object->setEntity($translated_node);

    $form_state = (new FormState())->setFormState($form_state_additions);
    $new_form = $this->formBuilder->buildForm($form_object, $form_state);

    // If we are cloning addresses, we need to reset our delta counter
    // once the form is built.
    $tempstore = $this->privateTempStoreFactory->get('quick_node_clone');
    if ($tempstore->get('address_initial_value_delta') !== NULL) {
      $tempstore->set('address_initial_value_delta', NULL);
    }

    return $new_form;
  }

  /**
   * Clone the paragraphs of a node.
   *
   * If we do not clone the paragraphs attached to the node, the linked
   * paragraphs would be linked to two nodes which is not ideal.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node to clone.
   *
   * @return \Drupal\node\Entity\Node
   *   The node with cloned paragraph fields.
   */
  public function cloneParagraphs(Node $node) {
    foreach ($node->getFieldDefinitions() as $field_definition) {
      $field_storage_definition = $field_definition->getFieldStorageDefinition();
      $field_settings = $field_storage_definition->getSettings();
      $field_name = $field_storage_definition->getName();
      if (isset($field_settings['target_type']) && $field_settings['target_type'] == "paragraph") {
        if (!$node->get($field_name)->isEmpty()) {
          foreach ($node->get($field_name) as $value) {
            if ($value->entity) {
              $value->entity = $value->entity->createDuplicate();
              foreach ($value->entity->getFieldDefinitions() as $field_definition) {
                $field_storage_definition = $field_definition->getFieldStorageDefinition();
                $paragraph_field_settings = $field_storage_definition->getSettings();
                $paragraph_field_name = $field_storage_definition->getName();

                // Check whether this field is excluded and if so unset.
                if ($this->excludeParagraphField($paragraph_field_name, $value->entity->bundle())) {
                  unset($value->entity->{$paragraph_field_name});
                }

                $this->moduleHandler->alter('cloned_node_paragraph_field', $value->entity, $paragraph_field_name, $paragraph_field_settings);
              }
            }
          }
        }
      }
    }

    return $node;
  }

  /**
   * Check whether to exclude the paragraph field.
   *
   * @param string $field_name
   *   The field name.
   * @param string $bundle_name
   *   The bundle name.
   *
   * @return bool|null
   *   TRUE or FALSE depending on config setting, or NULL if config not found.
   */
  public function excludeParagraphField($field_name, $bundle_name) {
    $config_name = 'exclude.paragraph.' . $bundle_name;
    if ($exclude_fields = $this->getConfigSettings($config_name)) {
      return in_array($field_name, $exclude_fields);
    }
  }

  /**
   * Get the settings.
   *
   * @param string $value
   *   The setting name.
   *
   * @return array|mixed|null
   *   Returns the setting value if it exists, or NULL.
   */
  public function getConfigSettings($value) {
    $settings = $this->configFactory->get('quick_node_clone.settings')
      ->get($value);

    return $settings;
  }

}
