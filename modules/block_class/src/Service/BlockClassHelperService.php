<?php

namespace Drupal\block_class\Service;

use Drupal\block_class\Constants\BlockClassConstants;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Block Class Service Class.
 */
class BlockClassHelperService {

  use StringTranslationTrait;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Path Matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Drupal\Core\Entity\EntityRepositoryInterface service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The list of available modules.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * The block entity.
   *
   * @var useDrupal\block\Entity\Block
   */
  protected $blockEntity;

  /**
   * Construct of Block Class service.
   */
  public function __construct(LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, Connection $database, RequestStack $request_stack, PathMatcherInterface $path_matcher, UuidInterface $uuid_service, AliasManagerInterface $alias_manager, CurrentPathStack $current_path, EntityRepositoryInterface $entityRepository, AccountProxy $currentUser, EntityTypeManagerInterface $entity_manager, ModuleExtensionList $extension_list_module) {
    $this->languageManager = $language_manager;
    $this->pathMatcher = $path_matcher;
    $this->request = $request_stack->getCurrentRequest();
    $this->configFactory = $config_factory;
    $this->database = $database;
    $this->uuidService = $uuid_service;
    $this->aliasManager = $alias_manager;
    $this->currentPath = $current_path;
    $this->entityRepository = $entityRepository;
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entity_manager;
    $this->blockEntity = $this->entityTypeManager->getStorage('block');
    $this->extensionListModule = $extension_list_module;
  }

  /**
   * Method to do the presave block.
   */
  public function blockClassPreSave(&$entity) {

    // If there is no class, unset the Third Party Setting.
    if (empty($entity->getThirdPartySetting('block_class', 'classes'))) {
      $entity->unsetThirdPartySetting('block_class', 'classes');
    }

    // If there is no attributes, unset the Third Party Setting.
    if (empty($entity->getThirdPartySetting('block_class', 'attributes'))) {
      $entity->unsetThirdPartySetting('block_class', 'attributes');
    }

    if (empty($entity->getThirdPartySetting('block_class', 'replaced_id'))) {
      $entity->unsetThirdPartySetting('block_class', 'replaced_id');
    }

    // Get the config object.
    $config = $this->configFactory->getEditable('block_class.settings');

    // Get the default case on settings.
    $default_case = $config->get('default_case', 'standard');

    // Get the block class.
    $block_classes = $entity->getThirdPartySetting('block_class', 'classes');

    // Only process non-empty values.
    if (!empty($block_classes)) {

      switch ($default_case) {

        case 'uppercase':

          $block_classes = strtoupper($block_classes);

          break;

        case 'lowercase':

          $block_classes = strtolower($block_classes);

          break;

      }

      // Set the Third Party Settings.
      $entity->setThirdPartySetting('block_class', 'classes', $block_classes);

      // Get the config object.
      $config = $this->configFactory->getEditable('block_class.settings');

      // Get the current classes stored.
      $block_classes_stored = $config->get('block_classes_stored');

      // Get the current class and export to array.
      $current_block_classes = explode(' ', $block_classes ?? '');

      // Merge with the current one.
      $block_classes_to_store = array_merge($block_classes_stored, $current_block_classes);
      $block_classes_to_store = array_unique($block_classes_to_store);

      // Store in the config.
      $config->set('block_classes_stored', $block_classes_to_store);

      // Save.
      $config->save();

    }

    // Store the id replacement in the settings only if it is enabled in the
    // Global Settings page.
    if (!empty($config->get('enable_id_replacement'))) {

      // Get the id replacement stored.
      $id_replacement_stored = $config->get('id_replacement_stored');

      // Get the array from JSON.
      $id_replacement_stored = Json::decode($id_replacement_stored ?? '');

      // Verify if is empty.
      if (empty($id_replacement_stored)) {
        $id_replacement_stored = [];
      }

      // Get the block class.
      $replaced_id = $entity->getThirdPartySetting('block_class', 'replaced_id');

      // Avoid storing empty values.
      if (!empty(trim($replaced_id ?? ''))) {

        // Remove the extra spaces.
        $id_replacement_stored[$entity->id()] = trim($replaced_id);

        // Get as JSON.
        $id_replacement_to_store = Json::encode($id_replacement_stored);

        // Store in the config.
        $config->set('id_replacement_stored', $id_replacement_to_store);

        // Save.
        $config->save();
      }

    }

    // If the attribute isn't enabled, skip that.
    if (empty($config->get('enable_attributes'))) {
      return FALSE;
    }

    // Get the current attributes.
    $attributes = $entity->getThirdPartySetting('block_class', 'attributes');

    // Initial value.
    $attribute_keys_stored = '{}';

    $attribute_value_stored = '{}';

    $attributes_inline_stored = '{}';

    // Get the keys stored.
    if (!empty($config->get('attribute_keys_stored'))) {
      $attribute_keys_stored = $config->get('attribute_keys_stored');
    }

    if (!empty($config->get('attribute_value_stored'))) {
      $attribute_value_stored = $config->get('attribute_value_stored');
    }

    if (!empty($config->get('attributes_inline'))) {
      $attributes_inline_stored = $config->get('attributes_inline');
    }

    // Decode this to get an array with those values.
    $attribute_keys_stored = Json::decode($attribute_keys_stored ?? '');

    $attribute_value_stored = Json::decode($attribute_value_stored ?? '');

    $attributes_inline_stored = Json::decode($attributes_inline_stored ?? '');

    // Verify if it's empty and set a default array on this.
    if (empty($attribute_keys_stored)) {
      $attribute_keys_stored = [];
    }

    if (empty($attribute_value_stored)) {
      $attribute_value_stored = [];
    }

    if (empty($attributes_inline_stored)) {
      $attributes_inline_stored = [];
    }

    // Get the array with the values.
    $current_attributes = explode(PHP_EOL, $attributes ?? '');

    // Initial value.
    $attributes_inline = [];

    // Do a foreach to get all items.
    foreach ($current_attributes as $current_attribute) {

      if (empty($current_attribute)) {
        continue;
      }

      // Get the attribute inline to be stored.
      $attribute_inline = str_replace('|', '=', $current_attribute);

      // Put the attribute inline in the array.
      $attributes_inline[] = $attribute_inline;

      // Get by pipe to be able to get the key value.
      $attribute_array = explode('|', $current_attribute);

      // If there is no a key, skip that.
      if (empty($attribute_array[0]) || empty($attribute_array[1])) {
        return;
      }

      // Get the attribute key.
      $attribute_key = $attribute_array[0];
      $attribute_value = $attribute_array[1];

      if (!empty($attribute_key)) {
        $attribute_keys_stored[] = trim($attribute_key);
      }

      if (!empty($attribute_value)) {
        $attribute_value_stored[] = trim($attribute_value);
      }

    }

    // Combine to use the id and value.
    $attribute_keys_stored = array_combine($attribute_keys_stored, $attribute_keys_stored);

    $attribute_value_stored = array_combine($attribute_value_stored, $attribute_value_stored);

    $attributes_inline = array_combine($attributes_inline, $attributes_inline);

    // Merge the values.
    $attribute_keys_stored = array_merge($attribute_keys_stored, $attribute_keys_stored);

    $attribute_value_stored = array_merge($attribute_value_stored, $attribute_value_stored);

    $attributes_inline = array_merge($attributes_inline_stored, $attributes_inline);

    // Encode that to store in JSON.
    $attribute_keys_stored = Json::encode($attribute_keys_stored);

    $attribute_value_stored = Json::encode($attribute_value_stored);

    $attributes_inline = Json::encode($attributes_inline);

    // Set in the object.
    $config->set('attribute_keys_stored', $attribute_keys_stored);

    $config->set('attribute_value_stored', $attribute_value_stored);

    $config->set('attributes_inline', $attributes_inline);

    // Save  it.
    $config->save();

  }

  /**
   * Method to redirect to Bulk Operations.
   */
  public function redirectToBulkOperations() {

    // Get path bulk operation.
    $bulk_operation_path = Url::fromRoute('block_class.bulk_operations')->toString();

    // Get response.
    $response = new RedirectResponse($bulk_operation_path);

    // Send to confirmation.
    $response->send();
    exit;

  }

  /**
   * Method to do the preprocess block.
   */
  public function blockClassPreprocessBlock(&$variables) {

    // Blocks coming from page manager widget does not have id. If there is no
    // Block ID, skip that.
    if (empty($variables['elements']['#id'])) {
      return;
    }

    // Load the block by ID.
    /** @var \Drupal\block\BlockInterface $block */
    $block = $this->blockEntity->load($variables['elements']['#id']);

    // If there is no block with this ID, skip.
    if (is_null($block)) {
      return;
    }

    // Add attributes on block.
    $this->addAttributesOnBlock($block, $variables);

    // Add classes on block.
    $this->addClassesOnBlock($block, $variables);

    // Add the new ID on block.
    $this->updateBlockId($block, $variables);

  }

  /**
   * Method to add attributes on block.
   */
  public function addAttributesOnBlock(&$block, &$variables) {

    // Get the config object.
    $config = $this->configFactory->getEditable('block_class.settings');

    // If attributes isn't enabled, skip.
    if (empty($config->get('enable_attributes'))) {
      return FALSE;
    }

    // If there is no attributes on block, skip.
    if (empty($block->getThirdPartySetting('block_class', 'attributes'))) {
      return FALSE;
    }

    $attributes = $block->getThirdPartySetting('block_class', 'attributes');

    $attributes = explode(PHP_EOL, $attributes);

    foreach ($attributes as $attribute) {

      $attribute = explode('|', $attribute);

      $attribute_key = trim($attribute[0]);

      $attribute_value = trim($attribute[1]);

      // Sanitize to ensure a valid and safe value.
      $attribute_value = Html::cleanCssIdentifier($attribute_value);

      // Insert the attributes.
      $variables['attributes'][$attribute_key][] = $attribute_value;
    }
  }

  /**
   * Method to update the ID on block.
   */
  public function updateBlockId(&$block, &$variables) {

    // If there is no replaced id on block, skip.
    if (empty($block->getThirdPartySetting('block_class', 'replaced_id'))) {
      return FALSE;
    }

    // Verify if the block has Third Party Settings with replaced id.
    $replaced_id = $block->getThirdPartySetting('block_class', 'replaced_id');

    // Remove extra spaces.
    $replaced_id = trim($replaced_id);

    // If the user selected <none> in the block settings disable the block id.
    if ($replaced_id == '<none>') {
      unset($variables['attributes']['id']);
      return;
    }

    // Sanitize to ensure a valid and safe identifier.
    $replaced_id = Html::cleanCssIdentifier($replaced_id);

    // Update the ID.
    $variables['attributes']['id'] = $replaced_id;

  }

  /**
   * Method to add classes on block.
   */
  public function addClassesOnBlock(&$block, &$variables) {

    // Verify if the current block has Third Party Settings with classes.
    $classes = $block->getThirdPartySetting('block_class', 'classes');

    if (empty($classes)) {
      return FALSE;
    }

    // Get all classes if exists.
    $classes_array = explode(' ', $classes);

    // Get the config object.
    $config = $this->configFactory->getEditable('block_class.settings');

    $filter_css_identifier = [];

    if (!empty($config->get('filter_html_clean_css_identifier'))) {

      $filter_clean_css_identifier = $config->get('filter_html_clean_css_identifier');

      $filter_clean_css_identifier = explode(PHP_EOL, $filter_clean_css_identifier);

      foreach ($filter_clean_css_identifier as $filter) {

        $filter = explode('|', $filter);

        $origin_char = trim($filter[0]);
        $target_char = trim($filter[1]);

        $filter_css_identifier[$origin_char] = $target_char;

      }

    }

    // Add all classes.
    foreach ($classes_array as $class) {
      // Skip cleanCssIdentifier if the option "Allow Special Char" is enabled.
      if ($config->get('enable_special_chars') == FALSE) {
        $class = Html::cleanCssIdentifier($class, $filter_css_identifier);
      }
      $variables['attributes']['class'][] = $class;
    }
  }

  /**
   * Method to do a form alter.
   */
  public function blockClassFormAlter(&$form, &$form_state) {

    // If the user don't have permission, skip that.
    if (!$this->currentUser->hasPermission(BlockClassConstants::BLOCK_CLASS_PERMISSION)) {
      return;
    }

    $form_object = $form_state->getFormObject();

    // Implement the alter only if is a instance of EntityFormInterface.
    if (!($form_object instanceof EntityFormInterface)) {
      return;
    }

    $qty_classes_per_block = 10;

    // Get config object.
    $config = $this->configFactory->getEditable('block_class.settings');

    if (!empty($config->get('qty_classes_per_block'))) {
      $qty_classes_per_block = $config->get('qty_classes_per_block');
    }

    // Get the URL settings global page.
    $url_settings_page = Url::fromRoute('block_class.settings')->toString();

    $url_used_items_list = Url::fromRoute('block_class.class_list')->toString();

    // Put the default help text.
    $help_text = $this->t('Customize the styling of this block by adding CSS classes.');

    // Put the Modal with all items used.
    $help_text .= ' <div class="show-items-used">' . $this->t('<a href="@url_used_items_list@" class="use-ajax" data-dialog-options="{&quot;width&quot;:800}" data-dialog-type="modal">See all the classes used</a>.</div>', [
      '@url_used_items_list@' => $url_used_items_list,
    ]);

    $form['class'] = [
      '#type' => 'details',
      '#title' => $this->t('Class'),
      '#open' => TRUE,
      '#description' => $help_text,
    ];

    // Put the weight only if exists.
    if (!empty($config->get('weight_class')) || $config->get('weight_class') === '0') {
      $form['class']['#weight'] = $config->get('weight_class');
    }

    /** @var \Drupal\block\BlockInterface $block */
    $block = $form_object->getEntity();

    // This will automatically be saved in the third party settings.
    $form['class']['third_party_settings']['#tree'] = TRUE;

    // Default field type.
    $field_type = 'textfield';

    // Default value for maxlength.
    $maxlength_block_class_field = 255;

    // Get the field type if exists.
    if (!empty($config->get('field_type')) && $config->get('field_type') != 'multiple_textfields') {
      $field_type = $config->get('field_type');
    }

    // Get maxlength if exists.
    if (!empty($config->get('maxlength_block_class_field'))) {
      $maxlength_block_class_field = $config->get('maxlength_block_class_field');
    }

    $image_path = '/' . $this->extensionListModule->getPath('block_class') . '/images/';

    if ($config->get('field_type') == 'textfield') {

      // Remove the help text in the field group because the field will have
      // their help text.
      unset($form['class']['#description']);

      $form['class']['third_party_settings']['block_class']['classes'] = [
        '#type' => $field_type,
        '#title' => $this->t('CSS class(es)'),
        '#description' => $this->t('Customize the styling of this block by adding CSS classes. Separate multiple classes by spaces. The maxlength configured is @maxlength_block_class_field@. If necessary you can <a href="/admin/config/content/block-class/settings">update it here</a>. This class will appear in the first level of block. <a href="@image_path@/example-1.png">See an example</a>', [
          '@maxlength_block_class_field@' => $maxlength_block_class_field,
          '@image_path@' => $image_path,
        ]),
        '#default_value' => $block->getThirdPartySetting('block_class', 'classes'),
        '#maxlength' => $maxlength_block_class_field,
      ];

    }

    if ($config->get('field_type') == 'multiple_textfields') {

      // Get the classes on getThirdPartySettings.
      $classes = $block->getThirdPartySetting('block_class', 'classes');

      // Explode by spaces to get all classes in an array.
      $classes = explode(' ', $classes ?? '');

      // If the quantity of items in the block is lower than the quantity of
      // items configured in the settings we need to have the higher limit
      // just to avoid some items being cut due the limit.
      if ((int) $qty_classes_per_block < (int) count($classes)) {
        $qty_classes_per_block = (int) count($classes);
      }

      // Run a for to add 10 multiple fields.
      for ($index = 0; $index <= ($qty_classes_per_block - 1); $index++) {

        // Initial value.
        $multi_class_default_value = '';

        // Verify if there is a value on this class to add in the field.
        if (!empty($classes[$index])) {
          $multi_class_default_value = $classes[$index];
        }

        // Insert the new field.
        $form['class']['third_party_settings']['block_class']['classes_' . $index] = [
          '#type' => 'textfield',
          '#title' => $this->t('CSS class'),
          '#default_value' => $multi_class_default_value,
          '#maxlength' => $maxlength_block_class_field,
        ];

        // Enable the auto-complete only is selected in the settings page.
        if (!empty($config->get('enable_auto_complete'))) {
          $form['class']['third_party_settings']['block_class']['classes_' . $index]['#autocomplete_route_name'] = 'block_class.autocomplete';
        }

        // Insert a default class for all classes visible or not.
        $form['class']['third_party_settings']['block_class']['classes_' . $index]['#attributes']['class'][] = 'multiple-textfield';

        // Put the visible class by default.
        $form['class']['third_party_settings']['block_class']['classes_' . $index]['#attributes']['class'][] = 'displayed-class-field';

        // If is the second o higher and there is no class for that, hide.
        if ($index >= 1 && empty($classes[$index])) {

          $form['class']['third_party_settings']['block_class']['classes_' . $index]['#attributes']['class'][] = 'hidden-class-field';

          // If this class should be hidden, get the key to put the right
          // class on that.
          $hidden_class_key = array_search('displayed-class-field', $form['class']['third_party_settings']['block_class']['classes_' . $index]['#attributes']['class']);

          // Unset in the array to remove the hidden class.
          unset($form['class']['third_party_settings']['block_class']['classes_' . $index]['#attributes']['class'][$hidden_class_key]);

        }

      }

      // Add another item button in the last field.
      $form['class']['third_party_settings']['block_class']['add_another_item'] = [
        '#type' => 'button',
        '#value' => $this->t('Add another class'),
      ];

      // Add the class to identity the "add another item" button.
      $form['class']['third_party_settings']['block_class']['add_another_item']['#attributes']['class'][] = 'block-class-add-another-item';

      // Add remove item button in the last field.
      $form['class']['third_party_settings']['block_class']['remove_item'] = [
        '#type' => 'button',
        '#value' => $this->t('Remove last added class'),
      ];

      // Add the class to identity the "Remove item" button.
      $form['class']['third_party_settings']['block_class']['remove_item']['#attributes']['class'][] = 'block-class-remove-item';

      // Verify if there is a help text for qty item and if not set a default
      // value for this.
      if (empty($help_text_qty_items)) {
        $help_text_qty_items = '';
      }

      $help_text_qty_items .= ' ' . $this->t('The maximum of classes per block is @qty_classes_per_block@ but if you need you can update this value in the <a href="@url_settings_page@">settings page</a>', [
        '@qty_classes_per_block@' => $qty_classes_per_block,
        '@url_settings_page@' => $url_settings_page,
      ]);

      $form['class']['third_party_settings']['block_class']['help_text_qty_items'] = [
        '#type' => 'markup',
        '#markup' => '<p class="help-text-qty-items help-text-qty-items-hidden">' . $help_text_qty_items . '</p>',
      ];
    }

    $form['class']['third_party_settings']['block_class']['classes']['#attributes']['class'][] = 'block-class-class';

    if (!empty($config->get('enable_id_replacement'))) {

      // Default value for maxlength to be used in the replaced_id.
      // If no value is present in the settings item we can use the default of
      // 255 in the maxlength.
      $maxlength_id = 255;

      // Get maxlength for replaced_id if exists in the Global Settings page.
      if (!empty($config->get('maxlength_id'))) {
        $maxlength_id = $config->get('maxlength_id');
      }

      $form['replaced_id'] = [
        '#type' => 'details',
        '#title' => $this->t('ID'),
        '#open' => TRUE,
        '#description' => $this->t("Customize the block id"),
        '#attributes' => [
          'class' => [
            'replaced-id-details',
          ],
        ],
      ];

      // Put the weight only if exists.
      if (!empty($config->get('weight_id')) || $config->get('weight_id') === '0') {
        $form['replaced_id']['#weight'] = $config->get('weight_id');
      }

      $form['replaced_id']['third_party_settings']['#tree'] = TRUE;

      $form['replaced_id']['third_party_settings']['block_class']['replaced_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('ID'),
        '#description' => $this->t("If you put a value here it'll replace the block's id. Use @none_key@ to remove the default block id", [
          '@none_key@' => '<none>',
        ]),
        '#default_value' => $block->getThirdPartySetting('block_class', 'replaced_id'),
        '#maxlength' => $maxlength_id,
      ];

      // Insert the specific class for this item.
      $form['replaced_id']['third_party_settings']['block_class']['replaced_id']['#attributes']['class'][] = 'replaced-id-item';

    }

    if (!empty($config->get('enable_attributes'))) {

      $attributes = $block->getThirdPartySetting('block_class', 'attributes');

      $attributes = explode(PHP_EOL, $attributes ?? '');

      $qty_attributes_per_block = 10;

      if (!empty($config->get('qty_attributes_per_block'))) {
        $qty_attributes_per_block = $config->get('qty_attributes_per_block');
      }

      // If the quantity of items in the block is lower than the quantity of
      // items configured in the settings we need to have the higher limit
      // just to avoid some items being cut due the limit.
      if ((int) $qty_attributes_per_block < (int) count($attributes)) {
        $qty_attributes_per_block = (int) count($attributes);
      }

      // Add the attributes dynamically based on the settings field of global
      // settings page.
      for ($index = 0; $index <= ($qty_attributes_per_block - 1); $index++) {

        // Default value for attribute key and attribute value.
        $attribute_key = '';
        $attribute_value = '';

        // Verify if there an attribute to be filled.
        if (!empty($attributes[$index])) {

          $attribute = explode('|', $attributes[$index]);

          // Get the attribute key and attribute value.
          $attribute_key = $attribute[0];
          $attribute_value = $attribute[1];

        }

        // Get the URL of route with the attribute list used.
        $url_used_attribute_list = Url::fromRoute('block_class.attribute_list')->toString();

        // Create the help text for the multiple attribute fields.
        $help_text = $this->t('Customize the this block by adding attributes. E.g. "data-block-type"="admin"');

        // Update the help text with the Modal to show this for the user.
        $help_text .= ' - <div class="show-items-used">' . $this->t('<a href="@url_used_attribute_list@" class="use-ajax" data-dialog-options="{&quot;width&quot;:800}" data-dialog-type="modal">See all the attributes used</a>.', [
          '@url_used_attribute_list@' => $url_used_attribute_list,
        ]);

        // Create the field group for multiple attributes.
        $form['multiple_attributes']['third_party_settings']['block_class']['attribute_' . $index] = [
          '#type' => 'details',
          '#title' => $this->t('Attribute'),
          '#open' => TRUE,
          '#description' => $help_text,
          '#attributes' => [
            'class' => [
              'attribute-details',
            ],
          ],
        ];

        // Put the weight only if exists.
        if (!empty($config->get('weight_attributes')) || $config->get('weight_attributes') === '0') {
          $form['multiple_attributes']['third_party_settings']['block_class']['attribute_' . $index]['#weight'] = $config->get('weight_attributes');
        }

        $maxlength_multiple_attributes = $config->get('maxlength_attributes');

        // Add the attribute key item.
        $form['multiple_attributes']['third_party_settings']['block_class']['attribute_' . $index]['attribute_key_' . $index] = [
          '#type' => 'textfield',
          '#title' => $this->t('Attribute Key'),
          '#default_value' => $attribute_key,
          '#description' => $this->t('Set the attribute key. E.g. data-block-type'),
          '#maxlength' => $maxlength_multiple_attributes,
        ];

        // Enable the auto-complete only is selected in the settings page.
        if (!empty($config->get('enable_auto_complete'))) {
          $form['multiple_attributes']['third_party_settings']['block_class']['attribute_' . $index]['attribute_key_' . $index]['#autocomplete_route_name'] = 'block_class.autocomplete_attributes';
        }

        // Add the attribute value item.
        $form['multiple_attributes']['third_party_settings']['block_class']['attribute_' . $index]['attribute_value_' . $index] = [
          '#type' => 'textfield',
          '#title' => $this->t('Attribute Value'),
          '#default_value' => $attribute_value,
          '#description' => $this->t('Set the attribute value. E.g. admin'),
          '#maxlength' => $maxlength_multiple_attributes,
        ];

        // Enable the auto-complete only is selected in the settings page.
        if (!empty($config->get('enable_auto_complete'))) {
          $form['multiple_attributes']['third_party_settings']['block_class']['attribute_' . $index]['attribute_value_' . $index]['#autocomplete_route_name'] = 'block_class.autocomplete_attribute_values';
        }

        $form['multiple_attributes']['third_party_settings']['block_class']['attribute_' . $index]['#attributes']['class'][] = 'multiple-textfield-attribute';

        // Show this attribute only if it's the first one of if already exists
        // a value. (Editing the block).
        if ($index == 0 || (!empty($attribute_key) && !empty($attribute_value))) {
          $form['multiple_attributes']['third_party_settings']['block_class']['attribute_' . $index]['#attributes']['class'][] = 'displayed-attribute-field';
        }
        else {
          $form['multiple_attributes']['third_party_settings']['block_class']['attribute_' . $index]['#attributes']['class'][] = 'hidden-attribute-field';
        }
      }

      // Add the button to add another attribute item.
      $form['multiple_attributes']['add_another_attribute'] = [
        '#type' => 'button',
        '#value' => $this->t('Add another attribute'),
      ];

      $form['multiple_attributes']['add_another_attribute']['#attributes']['class'][] = 'block-class-add-another-attribute';

      // Add the button to remove an attribute item.
      $form['multiple_attributes']['remove_attribute'] = [
        '#type' => 'button',
        '#value' => $this->t('Remove last added attribute'),
      ];

      $form['multiple_attributes']['remove_attribute']['#attributes']['class'][] = 'block-class-remove-attribute';

    }

    if (empty($block->getThirdPartySetting('block_class', 'classes')) && !empty($config->get('enable_auto_complete'))) {
      $form['third_party_settings']['block_class']['classes']['#autocomplete_route_name'] = 'block_class.autocomplete';
    }

    $form['#validate'][] = 'block_class_form_block_form_validate';

    $form['#attached']['library'][] = 'block_class/block-class';

    // Get the default case on settings.
    $default_case = $config->get('default_case', 'standard');

    // Put the "default case" in the Drupal settings to be used in the JS.
    $form['#attached']['drupalSettings']['block_class']['default_case'] = $default_case;

  }

  /**
   * Method to do a form validate.
   */
  public function blockClassFormValidate(&$form, &$form_state) {

    // Get the config object.
    $config = $this->configFactory->getEditable('block_class.settings');

    // Validate dynamic items.
    $this->validateDynamicClasses($form, $form_state, $config);

    // Validate dynamic attributes.
    $this->validateAttributes($form, $form_state, $config);

    // Validate class.
    $this->validateClass($form, $form_state, $config);

    // Validate ID.
    $this->validateId($form, $form_state, $config);

  }

  /**
   * Method to validate Dynamic items.
   */
  public function validateDynamicClasses(&$form, &$form_state, $config) {

    // Get the ThirdPartySettings.
    $third_party_settings = $form_state->getValue('class') ? $form_state->getValue('class')['third_party_settings'] : [];

    // Verify if there is attributes enabled.
    if (!empty($form_state->getValue('attributes')['third_party_settings'])) {
      $third_party_settings['block_class']['attributes'] = $form_state->getValue('attributes')['third_party_settings']['block_class']['attributes'];
    }

    // Remove unused items.
    unset($third_party_settings['block_class']['add_another_item']);
    unset($third_party_settings['block_class']['remove_item']);

    // Clear empty values.
    if (!empty($third_party_settings['block_class'])) {
      $third_party_settings['block_class'] = array_filter($third_party_settings['block_class']);
    }

    // Merge with all third party settings.
    $all_third_party_settings = $form_state->getValue('third_party_settings');
    if (!empty($all_third_party_settings)) {
      $third_party_settings = array_merge($third_party_settings, $all_third_party_settings);
    }

    // Set the ThirdPartySettings with the default array.
    $form_state->setValue('third_party_settings', $third_party_settings);

    // Unset the old values.
    $form_state->unsetValue('class');

    // Unset the old values for attributes.
    $form_state->unsetValue('attributes');

    if ($config->get('field_type') == 'multiple_textfields') {

      // Initial value for class.
      $classes = '';

      $third_party_settings = $form_state->getValue('third_party_settings');

      $classes_field = $third_party_settings['block_class'];

      // Unset values that aren't classes.
      if (isset($classes_field['attributes'])) {
        unset($classes_field['attributes']);
      }

      if (isset($classes_field['remove_item'])) {
        unset($classes_field['remove_item']);
      }

      if (isset($classes_field['add_another_item'])) {
        unset($classes_field['add_another_item']);
      }

      // Removed blank values.
      $classes_field = array_filter($classes_field);

      // If there are duplicated classes send a message.
      if (!empty(count($classes_field) !== count(array_unique($classes_field)))) {
        $form_state->setErrorByName('class][third_party_settings][block_class', $this->t("There are duplicated classes"));
        return FALSE;
      }

      foreach ($classes_field as $field_id => $class_field) {

        if ($field_id == 'classes' || $field_id == 'attributes' || $field_id == 'remove_item' || $field_id == 'add_another_item') {
          continue;
        }

        // Verify in the backend if there is space in the block class field and
        // Send a message that isn't necessary they're using multiple class.
        if (strpos($class_field, ' ')) {
          $form_state->setErrorByName('class][third_party_settings][block_class][' . $field_id, $this->t("Spaces isn't necessary since you're using multiple class field. Use one class per field"));
          return FALSE;
        }

        // Validate if the first char is a point and say that isn't necessary.
        if (strpos($class_field, '.') === 0) {
          $form_state->setErrorByName('class][third_party_settings][block_class][' . $field_id, $this->t("Isn't necessary add point in the beginning of class"));
          return FALSE;
        }

        // Concatenate the classes.
        $classes .= ' ' . $class_field;

      }

      $classes = trim($classes);

      $third_party_settings['block_class']['classes'] = $classes;

      // Remove unused values from Third Party Settings.
      foreach ($third_party_settings['block_class'] as $key => $third_party_setting) {

        // If there is a classes_ we can remove.
        if (strpos($key, 'classes_') !== FALSE) {
          unset($third_party_settings['block_class'][$key]);
        }

      }

      $form_state->setValue('third_party_settings', $third_party_settings);

    }
  }

  /**
   * Method to validate Dynamic attributes.
   */
  public function validateAttributes(&$form, &$form_state, $config) {

    if (empty($form_state->getValue('multiple_attributes')['third_party_settings'])) {
      return FALSE;
    }

    $third_party_settings = $form_state->getValue('third_party_settings');

    $third_party_settings['block_class']['multiple_attributes'] = $form_state->getValue('multiple_attributes')['third_party_settings']['block_class'];

    // Set the ThirdPartySettings with the default array.
    $form_state->setValue('third_party_settings', $third_party_settings);

    // Unset the old values.
    $form_state->unsetValue('multiple_attributes');

    // Initial value for attributes.
    $attributes = '';

    $multiple_attributes = $third_party_settings['block_class']['multiple_attributes'];

    $index = 0;

    $attributes = '';

    $attribute_keys = [];

    foreach ($multiple_attributes as $key => $attribute) {

      // If both key and attribute are empty, skip that.
      if (empty($attribute['attribute_key_' . $index]) && empty($attribute['attribute_value_' . $index])) {
        unset($multiple_attributes[$key]);
        $index++;
        continue;
      }

      $attribute_key = $attribute['attribute_key_' . $index];

      // Verify if there is a equal sign on attribute key.
      $equal_sign_found = strpos($attribute_key, '=');

      if ($equal_sign_found !== FALSE) {

        $form_state->setErrorByName('multiple_attributes][third_party_settings][block_class][attribute_' . $index . '][attribute_key_' . $index, $this->t("Ins't necessary add = sign. You can use the key and value fields"));

        return FALSE;

      }

      $attribute_value = $attribute['attribute_value_' . $index];

      // Verify if there is a equal sign on attribute value.
      $equal_sign_found = strpos($attribute_value, '=');

      if ($equal_sign_found !== FALSE) {

        $form_state->setErrorByName('multiple_attributes][third_party_settings][block_class][attribute_' . $index . '][attribute_value_' . $index, $this->t("Ins't necessary add = sign. You can use the key and value fields"));

        return FALSE;

      }

      // Validate if key and value are populated.
      if (empty($attribute_key) || empty($attribute_value)) {

        $form_state->setErrorByName('multiple_attributes][third_party_settings][block_class][attribute_' . $index . '][attribute_key_' . $index, $this->t('To apply attribute is necessary put "key" and "value"'));

        return FALSE;
      }

      // Search by duplicated attributes.
      $attribute_already_exists = array_search($attribute_key, $attribute_keys);

      // Validate if this attribute already exists.
      if ($attribute_already_exists !== FALSE) {

        $form_state->setErrorByName('multiple_attributes][third_party_settings][block_class][attribute_' . $index . '][attribute_key_' . $index, $this->t('There are duplicated classes'));

        return FALSE;

      }

      // Attribute keys to validated with duplicated.
      $attribute_keys[] = $attribute_key;

      if (empty($attribute_key)) {

        $form_state->setErrorByName('multiple_attributes][third_party_settings][block_class][attribute_' . $index . '][attribute_key_' . $index, $this->t('You need to fill the attribute key'));

        return FALSE;
      }

      if (empty($attribute_value)) {

        $form_state->setErrorByName('multiple_attributes][third_party_settings][block_class][attribute_' . $index . '][attribute_value_' . $index, $this->t('You need to fill the attribute value'));

        return FALSE;
      }

      // Validate ID attribute.
      if ($attribute_key == 'id') {

        // Set a message informing that we can't update the ID attribute.
        $form_state->setErrorByName('multiple_attributes][third_party_settings][block_class][attribute_' . $index . '][attribute_key_' . $index, $this->t("You can't the attribute id"));

        // Return False on validation.
        return FALSE;

      }

      // Validate class attribute.
      if ($attribute_key == 'class') {

        // Set a message informing that we can't use the class attribute.
        $form_state->setErrorByName('multiple_attributes][third_party_settings][block_class][attribute_' . $index . '][attribute_key_' . $index, $this->t("You can't use class. Use the field class instead"));

        // Return False on validation.
        return FALSE;

      }

      $attributes .= PHP_EOL . $attribute['attribute_key_' . $index] . '|' . $attribute['attribute_value_' . $index];

      // If there is a settings to allow only letters and numbers, validate it.
      if (!empty($config->get('enable_special_chars'))) {
        $index++;
        continue;
      }

      // Verify if there is a special char on this class.
      if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬]/', $attribute['attribute_key_' . $index])) {

        $url_settings_page = Url::fromRoute('block_class.settings')->toString();

        // If there is a special chat return the error for the user.
        $form_state->setErrorByName('multiple_attributes][third_party_settings][block_class][attribute_' . $index . '][attribute_key_' . $index, $this->t('Special chars is not enabled. To enable this, go to the <a href="@url_settings_page@">settings page</a>', [
          '@url_settings_page@' => $url_settings_page,
        ]));

        $form_state->setErrorByName('multiple_attributes][third_party_settings][block_class][attribute_' . $index . '][attribute_value_' . $index, $this->t('Special chars is not enabled. To enable this, go to the <a href="@url_settings_page@">settings page</a>', [
          '@url_settings_page@' => $url_settings_page,
        ]));
      }

      $index++;

    }

    $attributes = trim($attributes);

    $third_party_settings['block_class']['attributes'] = $attributes;

    // Remove the multiple_attributes attributes from ThirdPartySettings.
    unset($third_party_settings['block_class']['multiple_attributes']);

    $form_state->setValue('third_party_settings', $third_party_settings);

  }

  /**
   * Method to validate ID.
   */
  public function validateId(&$form, &$form_state, $config) {

    // If the ID replacement isn't active, skip that.
    if (empty($config->get('enable_id_replacement'))) {
      return FALSE;
    }

    // Get the id to be used.
    $id_replacement = $form_state->getValue('replaced_id')['third_party_settings']['block_class']['replaced_id'];

    // Get the default case on settings.
    $default_case = $config->get('default_case', 'standard');

    switch ($default_case) {

      case 'lowercase':

        $id_replacement = strtolower($id_replacement);

        break;

      case 'lowercase':

        $id_replacement = strtoupper($id_replacement);

        break;

    }

    // If there is a settings to allow only letters and numbers, validate it.
    if (!empty($config->get('enable_special_chars')) && preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬]/', $id_replacement) && ($id_replacement != '<none>')) {

      $url_settings_page = Url::fromRoute('block_class.settings')->toString();

      // If there is a special chat return the error for the user.
      $form_state->setErrorByName('replaced_id][third_party_settings][block_class][replaced_id', $this->t('Special chars is not enabled. To enable this, go to the <a href="@url_settings_page@">settings page</a>', [
        '@url_settings_page@' => $url_settings_page,
      ]));

    }

    // Get the id replacement stored to verify is this $id_replacement is
    // already in use by other block settings.
    $id_replacement_stored = $config->get('id_replacement_stored');

    // Get the array from JSON.
    $id_replacement_stored = Json::decode($id_replacement_stored ?? '');

    // Verify if is empty.
    if (empty($id_replacement_stored)) {
      $id_replacement_stored = [];
    }

    // Filtering the array with valid values.
    $id_replacement_stored = array_filter($id_replacement_stored);

    $block_id = $form_state->getValue('id');

    $id_found = array_search($id_replacement, $id_replacement_stored);

    // If this $id_replacement is present in the array means that the
    // $id_replacement is already in use in another block. Send a message for
    // the user.
    if (!empty($id_found) && $id_found != $block_id) {

      // Trigger the form set error with this message.
      $form_state->setErrorByName('replaced_id][third_party_settings][block_class][replaced_id', $this->t('This ID: @id_replacement@ is already in use by another block: @block_id_found@', [
        '@id_replacement@' => $id_replacement,
        '@block_id_found@' => $id_found,
      ]));

      // Skip the validation.
      return FALSE;
    }

    // Get the third party settings.
    $third_party_settings = $form_state->getValue('third_party_settings');

    // Put the Replaced ID.
    $third_party_settings['block_class']['replaced_id'] = $id_replacement;

    // Set the third party settings.
    $form_state->setValue('third_party_settings', $third_party_settings);

  }

  /**
   * Method to validate class.
   */
  public function validateClass(&$form, &$form_state, $config) {

    // If there is a settings to allow only letters and numbers, validate this.
    if (!empty($config->get('enable_special_chars'))) {
      return FALSE;
    }

    // Get the Third Party Settings from formState.
    $third_party_settings = $form_state->getValue('third_party_settings');

    if ($config->get('field_type') == 'multiple_textfields') {

      // Get the block class array from Third Party Settings.
      $multiple_items = $third_party_settings['block_class'];

      // Remove unnecessary values for this verification.
      unset($multiple_items['add_another_item']);
      unset($multiple_items['remove_item']);
      unset($multiple_items['attributes']);
      unset($multiple_items['multiple_attributes']);
      unset($multiple_items['classes']);

      // Run a foreach in all items.
      foreach ($multiple_items as $key => $item) {

        // Verify if there is a special char on this class.
        if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬]/', $item)) {

          $url_settings_page = Url::fromRoute('block_class.settings')->toString();

          // If there is a special chat return the error for the user.
          $form_state->setErrorByName('class][third_party_settings][block_class][' . $key, $this->t('Special chars is not enabled. To enable this, go to the <a href="@url_settings_page@">settings page</a>', [
            '@url_settings_page@' => $url_settings_page,
          ]));

        }

      }

      return FALSE;

    }

    if (empty($third_party_settings['block_class']['classes'])) {
      return FALSE;
    }

    $classes = $third_party_settings['block_class']['classes'];

    if (preg_match('/[\'^£$%&*()}{@#~?><>,|=+¬]/', $classes)) {

      $form_state->setErrorByName('class][third_party_settings][block_class][classes', $this->t("In class is allowed only letters, numbers, hyphen and underline"));

    }

    $classes = explode(' ', $classes);

    foreach ($classes as $class) {

      // Validate if the first char is a point and say that isn't necessary.
      if (strpos($class, '.') === 0) {
        $form_state->setErrorByName('class][third_party_settings][block_class][classes', $this->t("Isn't necessary add point in the beginning of class"));
        return FALSE;
      }
    }

  }

}
