<?php

namespace Drupal\views_bulk_operations\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStore;

/**
 * Defines common methods for Views Bulk Operations forms.
 */
trait ViewsBulkOperationsFormTrait {
  use MessengerTrait;

  /**
   * The tempstore object associated with the current view.
   */
  protected PrivateTempStore $viewTempstore;

  /**
   * The tempstore name.
   */
  protected string $tempStoreName;

  /**
   * Helper function to prepare data needed for proper form display.
   *
   * @param string $view_id
   *   The current view ID.
   * @param string $display_id
   *   The current view display ID.
   *
   * @return array
   *   Array containing data for the form builder.
   */
  protected function getFormData($view_id, $display_id): array {

    // Get tempstore data.
    $form_data = $this->getTempstoreData($view_id, $display_id);

    // Get data needed for selected entities list.
    $this->addListData($form_data);

    return $form_data;
  }

  /**
   * Add data needed for entity list rendering.
   */
  protected function addListData(&$form_data): void {
    $form_data['entity_labels'] = [];
    if (!empty($form_data['list'])) {
      $form_data['selected_count'] = \count($form_data['list']);
      if (!empty($form_data['exclude_mode'])) {
        $form_data['selected_count'] = $form_data['total_results'] - $form_data['selected_count'];
      }

      // In case of exclude mode we still get excluded labels
      // so we temporarily switch off exclude mode.
      $modified_form_data = $form_data;
      $modified_form_data['exclude_mode'] = FALSE;
      $form_data['entity_labels'] = $this->actionProcessor->getLabels($modified_form_data);
    }
    else {
      $form_data['selected_count'] = $form_data['total_results'] ?? 0;
    }
  }

  /**
   * Get the selection info title.
   *
   * @param array $tempstore_data
   *   VBO tempstore data array.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The selection info title.
   */
  protected function getSelectionInfoTitle(array $tempstore_data): TranslatableMarkup {
    if (!empty($tempstore_data['list'])) {
      return empty($tempstore_data['exclude_mode']) ? $this->t('Items selected:') : $this->t('Selected all items except:');
    }
  }

  /**
   * Build the selection info element.
   *
   * @param array $tempstore_data
   *   VBO tempstore data array.
   *
   * @return array
   *   Renderable array of the item list.
   */
  protected function getMultipageList(array $tempstore_data): array {
    $this->addListData($tempstore_data);
    $list = $this->getListRenderable($tempstore_data);
    return $list;
  }

  /**
   * Build selected entities list renderable.
   *
   * @param array $form_data
   *   Data needed for this form.
   *
   * @return array
   *   Renderable list array.
   */
  protected function getListRenderable(array $form_data): array {
    $renderable = [
      '#theme' => 'item_list',
      '#items' => $form_data['entity_labels'],
      '#empty' => $this->t('No items'),
    ];
    if (!empty($form_data['entity_labels'])) {
      $more = \count($form_data['list']) - \count($form_data['entity_labels']);
      if ($more > 0) {
        $renderable['#items'][] = [
          '#children' => $this->t('..plus @count more..', [
            '@count' => $more,
          ]),
          '#wrapper_attributes' => ['class' => ['more']],
        ];
      }
      $renderable['#title'] = $this->getSelectionInfoTitle($form_data);
    }
    elseif (!empty($form_data['exclude_mode'])) {
      $renderable['#empty'] = $this->t('Action will be executed on all items in the view.');
    }

    $renderable['#wrapper_attributes'] = ['class' => ['vbo-info-list-wrapper']];

    return $renderable;
  }

  /**
   * Calculates the bulk form key for an entity.
   *
   * This generates a key that is used as the checkbox return value when
   * submitting the bulk form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to calculate a bulk form key for.
   * @param mixed $base_field_value
   *   The value of the base field for this view result.
   *
   * @return string
   *   The bulk form key representing the entity id, language and revision (if
   *   applicable) as one string.
   *
   * @see self::loadEntityFromBulkFormKey()
   */
  public static function calculateEntityBulkFormKey(EntityInterface $entity, $base_field_value): string {
    // We don't really need the entity ID or type ID, since only the
    // base field value and language are used to select rows, but
    // other modules may need those values.
    $key_parts = [
      $base_field_value,
      $entity->language()->getId(),
      $entity->getEntityTypeId(),
      $entity->id(),
    ];

    // An entity ID could be an arbitrary string (although they are typically
    // numeric). JSON then Base64 encoding ensures the bulk_form_key is
    // safe to use in HTML, and that the key parts can be retrieved.
    $key = \json_encode($key_parts);
    return \base64_encode($key);
  }

  /**
   * Get an entity list item from a bulk form key.
   *
   * @param string $bulkFormKey
   *   A bulk form key.
   *
   * @return array
   *   Entity list item.
   */
  protected function getListItem($bulkFormKey): ?array {
    $decoded = \base64_decode($bulkFormKey);
    if ($decoded === FALSE) {
      return NULL;
    }
    $item = \json_decode($decoded);
    if (!\is_array($item)) {
      return NULL;
    }
    return $item;
  }

  /**
   * Initialize the current view tempstore object.
   */
  protected function getTempstore($view_id = NULL, $display_id = NULL): PrivateTempStore {
    if (!isset($this->viewTempstore)) {
      $this->tempStoreName = 'views_bulk_operations_' . $view_id . '_' . $display_id;
      $this->viewTempstore = $this->tempStoreFactory->get($this->tempStoreName);
    }
    return $this->viewTempstore;
  }

  /**
   * Gets the current view user tempstore data.
   *
   * @param string $view_id
   *   The current view ID.
   * @param string $display_id
   *   The display ID of the current view.
   */
  protected function getTempstoreData($view_id = NULL, $display_id = NULL): ?array {
    $data = $this->getTempstore($view_id, $display_id)->get($this->currentUser()->id());

    return $data;
  }

  /**
   * Sets the current view user tempstore data.
   *
   * @param array $data
   *   The data to set.
   * @param string $view_id
   *   The current view ID.
   * @param string $display_id
   *   The display ID of the current view.
   */
  protected function setTempstoreData(array $data, $view_id = NULL, $display_id = NULL): void {
    $this->getTempstore($view_id, $display_id)->set($this->currentUser()->id(), $data);
  }

  /**
   * Deletes the current view user tempstore data.
   *
   * @param string $view_id
   *   The current view ID.
   * @param string $display_id
   *   The display ID of the current view.
   */
  protected function deleteTempstoreData($view_id = NULL, $display_id = NULL): void {
    $this->getTempstore($view_id, $display_id)->delete($this->currentUser()->id());
  }

  /**
   * Add a cancel button into a VBO form.
   *
   * @param array $form
   *   The form definition.
   */
  protected function addCancelButton(array &$form): void {
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => [
        [$this, 'cancelForm'],
      ],
      '#limit_validation_errors' => [],
    ];
  }

  /**
   * Submit callback to cancel an action and return to the view.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state): void {
    $form_data = $form_state->get('views_bulk_operations');
    $this->messenger()->addMessage($this->t('Canceled "%action".', ['%action' => $form_data['action_label']]));
    $form_state->setRedirectUrl($form_data['redirect_url']);
  }

}
