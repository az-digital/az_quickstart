<?php

namespace Drupal\webform\Form;

use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides an entities deletion confirmation form.
 *
 * @see \Drupal\Core\Entity\Form\DeleteMultipleForm
 * @see \Drupal\webform\Form\WebformDeleteFormInterface
 * @see \Drupal\webform\Form\WebformConfigEntityDeleteFormBase
 */
abstract class WebformDeleteMultipleFormBase extends DeleteMultipleForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    // Issue #2582295: Confirmation cancel links are incorrect if installed in
    // a subdirectory
    // Work-around: Remove subdirectory from destination before generating
    // actions.
    $request = $this->getRequest();
    $destination = $request->query->get('destination');
    if ($destination) {
      // Remove subdirectory from destination.
      $update_destination = preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', $destination);
      $request->query->set('destination', $update_destination);
      $form = parent::buildForm($form, $form_state, $entity_type_id);
      $request->query->set('destination', $destination);
    }
    else {
      $form = parent::buildForm($form, $form_state, $entity_type_id);
    }

    // Exit if redirect.
    if ($form instanceof RedirectResponse) {
      return $form;
    }

    $form['message'] = $this->getWarning();
    $form['entities'] += [
      '#prefix' => $this->formatPlural(count($this->selection), 'The below @item will be deleted.', 'The below @items will be deleted.', [
        '@item' => $this->entityType->getSingularLabel(),
        '@items' => $this->entityType->getPluralLabel(),
      ]),
      '#suffix' => '<hr class="webform-hr"/>',
    ];
    $form['description'] = $this->getDescription();
    $form['hr'] = ['#markup' => '<hr class="webform-hr"/>'];
    $form['confirm_input'] = $this->getConfirmInput();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->selection), 'Delete this @item?', 'Delete these @items?', [
      '@item' => $this->entityType->getSingularLabel(),
      '@items' => $this->entityType->getPluralLabel(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return [];
  }

  /**
   * Returns warning message to display.
   */
  public function getWarning() {
    $message = $this->formatPlural(count($this->selection), 'Are you sure you want to delete this @item?', 'Are you sure you want to delete these @items?', [
      '@item' => $this->entityType->getSingularLabel(),
      '@items' => $this->entityType->getPluralLabel(),
    ]);

    return [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $message,
      '#weight' => -10,
    ];
  }

  /**
   * Returns confirm input to display.
   *
   * @return array
   *   A renderable array containing confirm input.
   */
  public function getConfirmInput() {
    return [
      '#type' => 'checkbox',
      '#title' => $this->formatPlural(count($this->selection), 'Yes, I want to delete this @item.', 'Yes, I want to delete these @items.', [
        '@item' => $this->entityType->getSingularLabel(),
        '@items' => $this->entityType->getPluralLabel(),
      ]),
      '#required' => TRUE,
    ];
  }

}
