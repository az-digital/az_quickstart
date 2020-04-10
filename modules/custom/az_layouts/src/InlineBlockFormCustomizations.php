<?php

namespace Drupal\az_layouts;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class InlineBlockFormCustomizations.
 *
 * Customizes the inline block forms for use with layout builder.
 */
class InlineBlockFormCustomizations {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new InlineBlockFormCustomizations object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Invoked during the az_layouts hook_form_alter().
   */
  public function alterForm(&$form, FormStateInterface $form_state, $form_id) {

    // Check if this is a BlockContent form before altering anything.
    if (isset($form['settings']['block_form']['#block']) && $form['settings']['block_form']['#block'] instanceof BlockContent) {

      // Get bundle label of the block, for generating the placeholder value.
      $entity_type = $form['settings']['block_form']['#block']->getEntityType()->getBundleEntityType();
      $bundle = $form['settings']['block_form']['#block']->bundle();
      $label = $this->entityTypeManager->getStorage($entity_type)->load($bundle)->label();

      // Autogenerate a label based on the custom block type bundle.
      $form['settings']['label']['#type'] = 'value';
      if (empty($form['settings']['label']['#default_value'])) {
        $form['settings']['label']['#default_value'] = t('Inline @label', ['@label' => $label]);
      }

      // Hide the label display checkbox.
      $form['settings']['label_display']['#type'] = 'value';
      $form['settings']['label_display']['#default_value'] = FALSE;

    }
  }

}
