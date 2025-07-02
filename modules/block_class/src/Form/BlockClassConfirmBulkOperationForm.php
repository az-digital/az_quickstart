<?php

namespace Drupal\block_class\Form;

use Drupal\block\Entity\Block;
use Drupal\block_class\Service\BlockClassHelperService;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form for Block Class Confirm Bulk Operation.
 */
class BlockClassConfirmBulkOperationForm extends ConfirmFormBase {

  /**
   * Bulk Operation type.
   *
   * @var string
   */
  protected $operation;

  /**
   * Classes to be added.
   *
   * @var string
   */
  protected $classesToBeAdded;

  /**
   * Current class.
   *
   * @var string
   */
  protected $currentClass;

  /**
   * New class.
   *
   * @var string
   */
  protected $newClass;

  /**
   * Attributes to be Added.
   *
   * @var string
   */
  protected $attributesToBeAdded;

  /**
   * Current attribute.
   *
   * @var string
   */
  protected $currentAttribute;

  /**
   * New attribute.
   *
   * @var string
   */
  protected $newAttribute;

  /**
   * The config factory.
   *
   * @var \Drupal\block_class\Service\BlockClassHelperService
   */
  protected $blockClassHelper;

  /**
   * Construct of Block Class service.
   */
  public function __construct(BlockClassHelperService $block_class_helper) {
    $this->blockClassHelper = $block_class_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('block_class.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreLine
  public function buildForm(array $form, FormStateInterface $form_state, $operation = NULL, $classes_to_be_added = NULL, $current_class = NULL, $new_class = NULL, $attributes_to_be_added = NULL, $current_attribute = NULL, $new_attribute = NULL) {

    // Get the default parameters.
    $this->operation = $operation;
    $this->classesToBeAdded = $classes_to_be_added;
    $this->currentClass = $current_class;
    $this->newClass = $new_class;
    $this->attributesToBeAdded = base64_decode($attributes_to_be_added);
    $this->currentAttribute = base64_decode($current_attribute);
    $this->newAttribute = base64_decode($new_attribute);

    $message_to_confirm = (string) $this->getQuestion();

    $form['message_to_confirm'] = [
      '#type' => 'item',
      '#markup' => $message_to_confirm,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Load blocks. Todo: We'll implements DI here @codingStandardsIgnoreLine
    $blocks = Block::loadMultiple();

    foreach ($blocks as $block) {

      switch ($this->operation) {

        case 'insert':

          $this->insertOperation($block);

          continue 2;

        case 'insert_attributes':

          $this->insertAttributes($block);

          continue 2;

        case 'convert_attributes_to_uppercase':

          $this->convertAttributesToUpperCase($block);

          continue 2;

        case 'convert_attributes_to_lowercase':

          $this->convertAttributesToLowerCase($block);

          continue 2;

        case 'convert_to_uppercase':

          $this->convertToUpperCase($block);

          continue 2;

        case 'convert_to_lowercase':

          $this->convertToLowerCase($block);

          continue 2;

        case 'update':

          $this->updateOperation($block);

          continue 2;

        case 'update_attributes':

          $this->updateAttributes($block);

          continue 2;

        case 'delete':

          $this->deleteOperation($block);

          continue 2;

        case 'delete_attributes':

          $this->deleteAttributes($block);

          continue 2;

        case 'remove_all_custom_ids':

          $this->removeAllCustomIds($block);

          continue 2;

      }
    }

    $this->messenger()->addStatus($this->t('Bulk operation concluded'));

    // Get path bulk operation.
    $bulk_operation_path = Url::fromRoute('block_class.bulk_operations')->toString();

    // Get response.
    $response = new RedirectResponse($bulk_operation_path);

    // Send to confirmation.
    $response->send();
    exit;
  }

  /**
   * Method to do the Insert Operation.
   */
  public function insertOperation(&$block) {

    // Get the current block classes configured.
    $current_classes = $block->getThirdPartySetting('block_class', 'classes');

    // Add the new classes in the current ones.
    $current_classes .= ' ' . $this->classesToBeAdded;

    // Using trim to remove spaces.
    $current_classes = trim($current_classes);

    // Store that in the Third Party Setting.
    $block->setThirdPartySetting('block_class', 'classes', $current_classes);

    // Save the block.
    $block->save();
  }

  /**
   * Method to do the Insert Attributes.
   */
  public function insertAttributes(&$block) {

    // Get the current block attributes configured.
    $current_attributes = $block->getThirdPartySetting('block_class', 'attributes');

    // Add the new attributes in the current ones.
    $current_attributes = $current_attributes . PHP_EOL . $this->attributesToBeAdded;

    // Using trim to remove spaces.
    $current_attributes = trim($current_attributes);

    // Store that in the Third Party Setting.
    $block->setThirdPartySetting('block_class', 'attributes', $current_attributes);

    // Save the block.
    $block->save();
  }

  /**
   * Method to convert class to uppercase.
   */
  public function convertToUpperCase(&$block) {

    // Get the current block classes configured.
    $current_attributes = $block->getThirdPartySetting('block_class', 'classes');

    // Add the new classes in the current ones.
    $current_attributes = strtoupper($current_attributes);

    // Store that in the Third Party Setting.
    $block->setThirdPartySetting('block_class', 'classes', $current_attributes);

    // Save the block.
    $block->save();
  }

  /**
   * Method to update attributes to uppercase.
   */
  public function convertAttributesToUpperCase(&$block) {

    // Get the current block classes configured.
    $attributes = $block->getThirdPartySetting('block_class', 'attributes');

    if (empty($attributes)) {
      return FALSE;
    }

    // Add the new attributes in the current ones.
    $attributes = strtoupper($attributes);

    // Store that in the Third Party Setting.
    $block->setThirdPartySetting('block_class', 'attributes', $attributes);

    // Save the block.
    $block->save();
  }

  /**
   * Method to update attributes to lowercase.
   */
  public function convertAttributesToLowerCase(&$block) {

    // Get the current block classes configured.
    $attributes = $block->getThirdPartySetting('block_class', 'attributes');

    if (empty($attributes)) {
      return FALSE;
    }

    // Add the new attributes in the current ones.
    $attributes = strtolower($attributes);

    // Store that in the Third Party Setting.
    $block->setThirdPartySetting('block_class', 'attributes', $attributes);

    // Save the block.
    $block->save();
  }

  /**
   * Method to convert class to lowercase.
   */
  public function convertToLowerCase(&$block) {

    // Get the current block classes configured.
    $current_attributes = $block->getThirdPartySetting('block_class', 'classes');

    // Add the new classes in the current ones.
    $current_attributes = strtolower($current_attributes);

    // Store that in the Third Party Setting.
    $block->setThirdPartySetting('block_class', 'classes', $current_attributes);

    // Save the block.
    $block->save();
  }

  /**
   * Method to do the Update Operation.
   */
  public function updateOperation(&$block) {

    // Get the current block classes configured.
    $current_block_classes = $block->getThirdPartySetting('block_class', 'classes');

    // If the current block class doesn't have this current class, skip.
    if (!preg_match("/\b" . $this->currentClass . "\b/i", $current_block_classes)) {
      return FALSE;
    }

    // Update the new block classes value with this replace.
    $new_block_classes = preg_replace("/\b" . $this->currentClass . "\b/i", $this->newClass, $current_block_classes);

    // Store that in the Third Party Setting.
    $block->setThirdPartySetting('block_class', 'classes', $new_block_classes);

    // Save the block.
    $block->save();

  }

  /**
   * Method to do the Update Attributes.
   */
  public function updateAttributes(&$block) {

    // Get the attributes configured.
    $attributes_stored = $block->getThirdPartySetting('block_class', 'attributes');

    // If the attributes doesn't have this attribute, skip.
    if (!preg_match("/\b" . $this->currentAttribute . "\b/i", $attributes_stored)) {

      return FALSE;
    }

    $attributes = explode(PHP_EOL, $attributes_stored);

    foreach ($attributes as $key => $attribute) {

      $attribute = explode('|', $attribute);

      $attribute_key = $attribute[0];

      if (strpos($this->currentAttribute, $attribute_key) === FALSE) {
        continue;
      }

      unset($attributes[$key]);

      $attributes[] = $this->newAttribute;

      $attributes_to_be_stored = implode(PHP_EOL, $attributes);

      $block->setThirdPartySetting('block_class', 'attributes', $attributes_to_be_stored);

      // Save the block.
      $block->save();
    }
  }

  /**
   * Method to do the delete Operation.
   */
  public function deleteOperation(&$block) {

    // If there is ThirdPartySetting remove that.
    $block->unsetThirdPartySetting('block_class', 'classes');

    // Block save.
    $block->save();

    // Get the config object.
    $config = $this->configFactory()->getEditable('block_class.settings');

    // Store in the config.
    $config->set('block_classes_stored', []);

    // Save.
    $config->save();

  }

  /**
   * Method to do remove all custom ids.
   */
  public function removeAllCustomIds(&$block) {

    // If there is no replaced id, skip.
    if (!empty($block->getThirdPartySetting('block_class', 'replaced_id'))) {
      return FALSE;
    }

    $bid = $block->id();

    $block = Block::load($bid);

    $block->unsetThirdPartySetting('block_class', 'replaced_id');

    // Block save.
    $block->save();

    // Get the config object.
    $config = $this->configFactory()->getEditable('block_class.settings');

    // Remove from settings.
    $config->set('id_replacement_stored', FALSE);

    // Save.
    $config->save();

  }

  /**
   * Method to do the delete attributes.
   */
  public function deleteAttributes(&$block) {

    // Get the config object.
    $config = $this->configFactory()->getEditable('block_class.settings');

    // Get the items stored.
    $block_classes_stored = $config->get('block_classes_stored');

    // If there is ThirdPartySetting remove that.
    $block->unsetThirdPartySetting('block_class', 'attributes');

    // Block save.
    $block->save();

    // Set in the object.
    $config->set('attribute_keys_stored', '{}');

    // Set in the object value.
    $config->set('attribute_value_stored', '{}');

    // Store the block items.
    $config->set('block_classes_stored', $block_classes_stored);

    // Save  it.
    $config->save();

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'block_class_confirm_bulk_operation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {

    return Url::fromRoute('block_class.bulk_operations');

  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {

    switch ($this->operation) {

      case 'insert':

        $message_to_confirm = $this->t('Do you want to insert class(es): <strong>@classes_to_be_added@</strong> to all blocks?', [
          '@classes_to_be_added@' => $this->classesToBeAdded,
        ]);

        break;

      case 'insert_attributes':

        $message_to_confirm = $this->t('Do you want to insert attribute(s): <strong>@attributes_to_be_added@</strong> to all blocks?', [
          '@attributes_to_be_added@' => $this->attributesToBeAdded,
        ]);

        break;

      case 'convert_attributes_to_uppercase':

        $message_to_confirm = $this->t('Do you want to convert all attributes to uppercase?');

        break;

      case 'convert_attributes_to_lowercase':

        $message_to_confirm = $this->t('Do you want to convert all attributes to lowercase?');

        break;

      case 'convert_to_uppercase':

        $message_to_confirm = $this->t('Do you want to convert all to uppercase?');

        break;

      case 'convert_to_lowercase':

        $message_to_confirm = $this->t('Do you want to convert all to lowercase?');

        break;

      case 'update':

        $message_to_confirm = $this->t('Do you want to update all block classes that have <strong>@current_class@</strong> to <strong>@new_class@</strong>?', [
          '@current_class@' => $this->currentClass,
          '@new_class@' => $this->newClass,
        ]);

        break;

      case 'update_attributes':

        $message_to_confirm = $this->t('Do you want to update all attributes that have <strong>@current_attribute@</strong> to <strong>@new_attribute@</strong>?', [
          '@current_attribute@' => $this->currentAttribute,
          '@new_attribute@' => $this->newAttribute,
        ]);

        break;

      case 'delete':

        $message_to_confirm = $this->t('Do you want to run this bulk operation for all block classes?');

        break;

      case 'delete_attributes':

        $message_to_confirm = $this->t('Do you want to delete all attributes?');

        break;

      case 'remove_all_custom_ids':

        $message_to_confirm = $this->t('Do you want to remove all custom Ids?');

        break;
    }

    return $message_to_confirm;
  }

}
