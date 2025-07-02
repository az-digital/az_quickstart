<?php

namespace Drupal\block_class\Form;

use Drupal\block\Entity\Block;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form for Block Class Confirm Deletion on Attributes.
 */
class BlockClassConfirmDeletionAttributeForm extends ConfirmFormBase {

  /**
   * Block ID.
   *
   * @var string
   */
  protected $bid;

  /**
   * {@inheritdoc}
   */
  // @codingStandardsIgnoreLine
  public function buildForm(array $form, FormStateInterface $form_state, $bid = NULL) {

    $this->bid = $bid;

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

    // Load block. Todo: We'll implements DI here @codingStandardsIgnoreLine
    $block = Block::load($this->bid);

    // If there is ThirdPartySetting remove that.
    $block->unsetThirdPartySetting('block_class', 'attributes');

    // Block save.
    $block->save();

    // Set a message.
    $this->messenger()->addStatus($this->t('Attributes deleted'));

    // Get the block class list path.
    $block_class_list_path = Url::fromRoute('block_class.list')->toString();

    // Get response.
    $response = new RedirectResponse($block_class_list_path);

    // Send to confirmation.
    $response->send();
    exit;

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "block_class_confirm_deletion_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {

    return Url::fromRoute('block_class.list');

  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {

    $message_to_confirm = $this->t('Are you sure?');

    return $message_to_confirm;
  }

}
