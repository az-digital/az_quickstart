<?php

declare(strict_types=1);

namespace Drupal\az_event_trellis\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views_remote_data\Plugin\views\PropertyPluginTrait;
use Drupal\views_remote_data\Plugin\views\query\RemoteDataQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter Trellis event API values according to Enterprise Attributes.
 */
#[ViewsFilter("az_event_trellis_views_attribute_filter")]
class AZEventTrellisViewsAttributeFilter extends FilterPluginBase {

  use PropertyPluginTrait;

  /**
   * {@inheritdoc}
   *
   * The string, equality, numeric, and boolean filters set this to TRUE. It
   * prevents the value from being wrapped as an array.
   */
  protected $alwaysMultiple = TRUE;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The term storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $termStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->entityRepository = $container->get('entity.repository');
    $instance->termStorage = $container->get('entity_type.manager')->getStorage('taxonomy_term');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state): void {
    $options = [];
    $key = $this->options['az_attribute_key'] ?? '';

    // Get the applicable attribute terms.
    $query = $this->termStorage->getQuery()
      ->accessCheck(TRUE)
      ->addTag('taxonomy_term_access')
      ->condition('vid', 'az_enterprise_attributes')
      ->condition('field_az_attribute_key', $key);
    $parents = $query->execute();
    $children = [];
    if (!empty($parents)) {
      $query = $this->termStorage->getQuery()
        ->accessCheck(TRUE)
        ->sort('name')
        ->addTag('taxonomy_term_access')
        ->condition('parent', $parents, 'IN');
      $children = $query->execute();
    }
    $terms = Term::loadMultiple($children);
    // Build option list.
    foreach ($terms as $term) {
      if ($term->hasField('field_az_attribute_key') && !empty($term->field_az_attribute_key->value)) {
        $options[$term->field_az_attribute_key->value] = $this->entityRepository->getTranslationFromContext($term)->label();
      }
    }
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Value'),
      '#options' => $options,
      '#required' => FALSE,
      '#access' => !empty($options),
      '#default_value' => $this->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function operatorOptions(): array {
    return [
      '=' => $this->t('Is equal to'),
      '!=' => $this->t('Is not equal to'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $this->definePropertyPathOption($options);
    $options['az_attribute_key'] = ['default' => ''];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state): void {
    $this->propertyPathElement($form, $this->options);
    parent::buildOptionsForm($form, $form_state);
    $form['az_attribute_key'] = [
      '#title' => $this->t('Unique key of enterprise attribute'),
      '#type' => 'textfield',
      '#default_value' => $this->options['az_attribute_key'] ?? '',
      '#required' => TRUE,
    ];
  }

  /**
   * Return the attribute id and api parameter name.
   *
   * @return array
   *   An array with the key as the attribute id and the value as the api path.
   */
  public function getApiMapping(): array {
    $key = $this->options['az_attribute_key'] ?? '';
    $path = $this->options['property_path'] ?? '';
    return [$key => $path];
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $prop = $this->options['property_path'] ?? '';
    $key = $this->options['az_attribute_key'] ?? '';
    return $key . ' (API value ' . $prop . ') ' . $this->operator . ' ' . $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE): void {
    if (!($this->query instanceof RemoteDataQuery)) {
      return;
    }
    $this->query->addWhere(
      $this->options['group'],
      $this->options['property_path'],
      $this->value,
      $this->operator
    );
  }

}
