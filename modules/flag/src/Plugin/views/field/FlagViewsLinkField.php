<?php

namespace Drupal\flag\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\flag\FlagLinkBuilderInterface;
use Drupal\flag\FlaggingInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a views field to flag or unflag the selected content.
 *
 * Unlike FlagViewsFlaggedField, this views field handler provides an
 * actionable link to flag or unflag the selected content.
 *
 * @ViewsField("flag_link")
 */
class FlagViewsLinkField extends FieldPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The flag for this row.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * The builder for flag links.
   *
   * @var \Drupal\flag\FlagLinkBuilderInterface
   */
  protected $flagLinkBuilder;

  /**
   * Constructs a FlagViewsLinkField object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\flag\FlagLinkBuilderInterface $flag_link_builder
   *   Tha flag link builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FlagLinkBuilderInterface $flag_link_builder,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->flagLinkBuilder = $flag_link_builder;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('flag.link_builder'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * A helper method to retrieve the flag entity from the views relationship.
   *
   * @return \Drupal\flag\FlagInterface|null
   *   The flag selected by the views relationship.
   */
  public function getFlag() {
    if ($this->flag) {
      return $this->flag;
    }
    // When editing a view it's possible to delete the relationship (either by
    // error or to later recreate it), so we have to guard against a missing
    // one.
    elseif (isset($this->view->relationship[$this->options['relationship']])) {
      // @phpstan-ignore-next-line
      return $this->view->relationship[$this->options['relationship']]->getFlag();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Set the default relationship handler. The first instance of the
    // FlagViewsRelationship should always have the id "flag_relationship", so
    // we set that as the default.
    $options['relationship'] = ['default' => 'flag_relationship'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['relationship']['#default_value'] = $this->options['relationship'];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Intentionally do nothing here since we're only providing a link and not
    // querying against a real table column.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    // If the flagging is the base for the view, there wouldn't be a
    // relationship involved.
    if ($values->_entity instanceof FlaggingInterface) {
      $entity_type = $values->_entity->getFlaggableType();
      $entity_id = $values->_entity->getFlaggableId();
      $entity = $this->entityTypeManager
        ->getStorage($entity_type)
        ->load($entity_id);
      $this->flag = $values->_entity->getFlag();
    }
    else {
      $entity = $this->getParentRelationshipEntity($values);
    }
    if (empty($entity)) {
      return '';
    }
    return $this->renderLink($entity, $values);
  }

  /**
   * Returns the entity for this field's relationship's parent relationship.
   *
   * For example, if this field's flag relationship is itself on a node, then
   * this will return the node entity for the current row.
   *
   * @param \Drupal\views\ResultRow $values
   *   The current result row.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The parent entity.
   */
  protected function getParentRelationshipEntity(ResultRow $values) {
    $relationship_id = $this->options['relationship'];
    $relationship_handler = $this->view->display_handler->handlers['relationship'][$relationship_id];
    $parent_relationship_id = $relationship_handler->options['relationship'];

    if ($parent_relationship_id == 'none') {
      return $values->_entity;
    }
    elseif (isset($values->_relationship_entities[$parent_relationship_id])) {
      return $values->_relationship_entities[$parent_relationship_id];
    }
    return NULL;
  }

  /**
   * Creates a render array for flag links.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Drupal\views\ResultRow $values
   *   The current result row.
   *
   * @return array|string
   *   The render array for the flag link.
   */
  protected function renderLink(EntityInterface $entity, ResultRow $values) {
    // Output nothing as there is no flag.
    // For an 'empty text' option use the default 'No results behavior'
    // option provided by Views.
    if ($entity === NULL) {
      return '';
    }

    return $this->flagLinkBuilder->build(
      $entity->getEntityTypeId(), $entity->id(), $this->getFlag()->id(), 'default'
    );
  }

}
