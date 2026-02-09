<?php

namespace Drupal\az_curated_views\Plugin\views\field;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\BulkForm;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines a views form element for curated views.
 */
#[ViewsField("az_curated_views_field")]
class AZCuratedViewsField extends BulkForm {

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    // Remove bulk form fields that are not relevant.
    $form['action_title']['#access'] = FALSE;
    $form['include_exclude']['#access'] = FALSE;
    $form['selected_actions']['#access'] = FALSE;
    $form['exclude']['#access'] = FALSE;
    $form['alter']['#access'] = FALSE;
    $form['empty_field_behavior']['#access'] = FALSE;
    $form['empty']['#access'] = FALSE;
    $form['empty_zero']['#access'] = FALSE;
    $form['hide_empty']['#access'] = FALSE;
    $form['hide_alter_empty']['#access'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    // Deliberately overridden. Parent class inhibits column label.
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    $form['#cache']['max-age'] = 0;

    $form[$this->options['id']] = [
      '#tree' => TRUE,
    ];

    foreach ($this->view->result as $row_index => $row) {
      $form[$this->options['id']][$row_index] = [
        '#tree' => TRUE,
      ];

      // Add draggable views weight, but as a selection rather than drag.
      $val = $row->draggableviews_structure_weight ?? NULL;
      $form[$this->options['id']][$row_index]['weight'] = [
        '#type' => 'select',
        '#options' => [
          '1' => $this->t('Sticky at Top'),
          '0' => $this->t('Hidden'),
        ],
        '#empty_option' => $this->t('Shown'),
        '#empty_value' => 'none',
        '#default_value' => $val,
      ];

      // Item to keep id of the entity.
      $form[$this->options['id']][$row_index]['id'] = [
        '#type' => 'hidden',
        '#value' => $this->getEntity($row)->id(),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    $ids = $form_state->getValue($this->options['id']);
    if (empty($ids) || empty(array_filter($ids))) {
      $form_state->setErrorByName('', $this->emptySelectedMessage());
    }
    // Unlike parent class, do not throw form error when action is empty.
  }

  /**
   * Submit handler for the curation form.
   *
   * @param mixed $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user tried to access an action without access to it.
   */
  public function viewsFormSubmit(&$form, FormStateInterface $form_state) {
    if (!\Drupal::currentUser()->hasPermission('access az_curated_views')) {
      throw new AccessDeniedHttpException();
    }
    $input = $form_state->getUserInput();

    /** @var \Drupal\views\ViewExecutable $view */
    $view = $form_state->getBuildInfo()['args'][0];
    $view_name = $view->id();
    $view_display = $view->current_display;
    $view_args = !empty($view->args) ? json_encode($view->args) : '[]';

    $connection = Database::getConnection();
    $transaction = $connection->startTransaction();
    try {
      foreach ($input[$this->options['id']] as $item) {
        // Remove old data.
        $connection->delete('draggableviews_structure')
          ->condition('view_name', $view_name)
          ->condition('view_display', $view_display)
          ->condition('args', $view_args)
          ->condition('entity_id', $item['id'])
          ->execute();

        // Default option makes no draggableviews weight entry.
        if ($item['weight'] !== 'none') {
          // Add new data.
          $record = [
            'view_name' => $view_name,
            'view_display' => $view_display,
            'args' => $view_args,
            'entity_id' => $item['id'],
            'weight' => $item['weight'],
          ];
          $connection->insert('draggableviews_structure')->fields($record)->execute();
        }

      }
      // We invalidate the entity list cache for other views.
      $views_entity_table_info = $view->query->getEntityTableInfo();
      // Find the entity type used by the view.
      $result = array_keys(array_filter($views_entity_table_info, function ($info) {
        return $info['relationship_id'] === 'none';
      }));
      $entity_type_id = reset($result);
      $list_cache_tags = $this->entityTypeManager->getDefinition($entity_type_id)->getListCacheTags();

      // Add the view configuration cache tag to let third-party integrations to
      // rely on it.
      $list_cache_tags[] = 'config:views.view.' . $view_name;
      $list_cache_tags[] = 'config:views.view.' . $view_name . '.' . $view_display;

      Cache::invalidateTags($list_cache_tags);
    }
    catch (\Exception $e) {
      $transaction->rollback();
      $this->messenger->addMessage($this->t('There was an error while saving the curation information.'), 'warning');
    }
  }

}
