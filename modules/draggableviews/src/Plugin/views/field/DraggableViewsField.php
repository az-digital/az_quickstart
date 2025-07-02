<?php

namespace Drupal\draggableviews\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\draggableviews\DraggableViews;
use Drupal\views\Plugin\views\field\BulkForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a draggableviews form element.
 *
 * @ViewsField("draggable_views_field")
 */
class DraggableViewsField extends BulkForm {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The action storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $actionStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;
  /**
   * The Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Sets the current_user service.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   *
   * @return $this
   */
  public function setCurrentUser(AccountInterface $current_user) {
    $this->currentUser = $current_user;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $datasource */
    $bulk_form = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $bulk_form->setCurrentUser($container->get('current_user'));
    return $bulk_form;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['draggable_views_hierarchy'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['draggable_views_help'] = [
      '#markup' => $this->t("A draggable element will be added to the first table column. You do not have to set this field as the first column in your View."),
    ];

    $form['draggable_views_hierarchy'] = [
      '#title' => $this->t('Enable hierarchy'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['draggable_views_hierarchy'],
      '#weight' => -1,
    ];

    parent::buildOptionsForm($form, $form_state);
    // Remove all the fields that would break this or are completely ignored
    // when rendering the drag interface.
    $form['custom_label']['#access'] = FALSE;
    $form['label']['#access'] = FALSE;
    $form['element_label_colon']['#access'] = FALSE;
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
  // @codingStandardsIgnoreStart
  public function render_item($count, $item) {
    // @codingStandardsIgnoreEnd
    // Using internal method. @todo Recheck after drupal stable release.
    return Markup::create('<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->');
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    $form[$this->options['id']] = [
      '#tree' => TRUE,
    ];

    $draggableviews = new DraggableViews($this->view);

    foreach ($this->view->result as $row_index => $row) {
      if (empty($this->getEntity($row))) {
        continue;
      }

      $form[$this->options['id']][$row_index] = [
        '#tree' => TRUE,
      ];

      // Add weight.
      $form[$this->options['id']][$row_index]['weight'] = [
        '#type' => 'textfield',
        '#size' => '5',
        '#maxlength' => '5',
        '#value' => $row->draggableviews_structure_weight,
        '#attributes' => ['class' => ['draggableviews-weight']],
      ];

      // Item to keep id of the entity.
      $form[$this->options['id']][$row_index]['id'] = [
        '#type' => 'hidden',
        '#value' => $this->getEntity($row)->id(),
        '#attributes' => ['class' => ['draggableviews-id']],
      ];

      // Add parent.
      $form[$this->options['id']][$row_index]['parent'] = [
        '#type' => 'hidden',
        '#default_value' => $draggableviews->getParent($row_index),
        '#attributes' => ['class' => ['draggableviews-parent']],
      ];
    }

    if ($this->currentUser->hasPermission('access draggableviews')) {
      // Get an array of field group titles.
      $fieldGrouping = $draggableviews->fieldGrouping();
      foreach ($fieldGrouping as $key => $row) {
        $options = [
          'table_id' => $draggableviews->getHtmlId($key),
          'action' => 'match',
          'relationship' => $this->options['draggable_views_hierarchy'] === 1 ? 'parent' : 'sibling',
          'group' => 'draggableviews-parent',
          'subgroup' => 'draggableviews-parent',
          'source' => 'draggableviews-id',
        ];
        drupal_attach_tabledrag($form, $options);
      }
    }
  }

}
