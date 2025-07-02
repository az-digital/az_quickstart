<?php

namespace Drupal\workbench_access\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\MultiItemsFieldHandlerInterface;
use Drupal\views\Plugin\views\field\PrerenderList;
use Drupal\views\ResultRow;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to present the section assigned to the node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("workbench_access_section")
 */
class Section extends PrerenderList implements MultiItemsFieldHandlerInterface {

  /**
   * Scheme.
   *
   * @var \Drupal\workbench_access\Entity\AccessSchemeInterface
   */
  protected $scheme;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var self $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    return $instance->setScheme($container->get('entity_type.manager')->getStorage('access_scheme')->load($configuration['scheme']));
  }

  /**
   * Sets access scheme.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   *
   * @return $this
   */
  public function setScheme(AccessSchemeInterface $scheme) {
    $this->scheme = $scheme;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['make_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Link to Section entity'),
      '#default_value' => $this->options['make_link'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['make_link'] = [
      'default' => FALSE,
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render_item($count, $item) { // phpcs:ignore
    return $item['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $this->items = [];
    if ($entity = $this->getEntity($values)) {
      $scheme = $this->scheme->getAccessScheme();
      $sections = $scheme->getEntityValues($entity);
      $tree = $scheme->getTree();
      foreach ($sections as $id) {
        foreach ($tree as $data) {
          if (isset($data[$id])) {
            // Check for link.
            if ($this->options['make_link'] && isset($data[$id]['path'])) {
              $this->items[$id]['path'] = $data[$id]['path'];
              $this->items[$id]['make_link'] = TRUE;
            }
            $this->items[$id]['value'] = $this->sanitizeValue($data[$id]['label']);
          }
        }
      }
    }
    return $this->items;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];
    $dependencies[$this->scheme->getConfigDependencyKey()][] = $this->scheme->getConfigDependencyName();
    return $dependencies;
  }

}
