<?php

namespace Drupal\webform_image_select;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Url;
use Drupal\webform\EntityListBuilder\WebformEntityListBuilderSortLabelTrait;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform_image_select\Entity\WebformImageSelectImages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines a class to build a listing of webform image select images entities.
 *
 * @see \Drupal\webform_image_select\Entity\WebformImageSelectImages
 */
class WebformImageSelectImagesListBuilder extends ConfigEntityListBuilder {

  use WebformEntityListBuilderSortLabelTrait;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Search keys.
   *
   * @var string
   */
  protected $keys;

  /**
   * Search category.
   *
   * @var string
   */
  protected $category;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var \Drupal\webform_image_select\WebformImageSelectImagesListBuilder $instance */
    $instance = parent::createInstance($container, $entity_type);

    $instance->request = $container->get('request_stack')->getCurrentRequest();

    $instance->initialize();
    return $instance;
  }

  /**
   * Initialize WebformImageSelectImagesListBuilder object.
   */
  protected function initialize() {
    $this->keys = $this->request->query->get('search');
    $this->category = $this->request->query->get('category');
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Handler autocomplete redirect.
    if ($this->keys && preg_match('#\(([^)]+)\)$#', $this->keys, $match)) {
      if ($webform_images = $this->getStorage()->load($match[1])) {
        return new RedirectResponse($webform_images->toUrl()->setAbsolute(TRUE)->toString());
      }
    }

    $build = [];

    // Filter form.
    $build['filter_form'] = $this->buildFilterForm();

    // Display info.
    $build['info'] = $this->buildInfo();

    // Table.
    $build += parent::render();
    $build['table']['#sticky'] = TRUE;

    // Attachments.
    $build['#attached']['library'][] = 'webform/webform.tooltip';
    $build['#attached']['library'][] = 'webform/webform.admin.dialog';

    return $build;
  }

  /**
   * Build the filter form.
   *
   * @return array
   *   A render array representing the filter form.
   */
  protected function buildFilterForm() {
    $categories = $this->getStorage()->getCategories();
    return \Drupal::formBuilder()->getForm('\Drupal\webform_image_select\Form\WebformImageSelectImagesFilterForm', $this->keys, $this->category, $categories);
  }

  /**
   * Build information summary.
   *
   * @return array
   *   A render array representing the information summary.
   */
  protected function buildInfo() {
    $total = $this->getQuery($this->keys, $this->category)->accessCheck(FALSE)->count()->execute();
    if (!$total) {
      return [];
    }

    return [
      '#markup' => $this->formatPlural($total, '@count images', '@count images'),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['category'] = $this->t('Category');
    $header['images'] = [
      'data' => $this->t('Images'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['used_by'] = [
      'data' => $this->t('Used by'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    /** @var \Drupal\webform_image_select\WebformImageSelectImagesInterface $entity */
    $row['label'] = $entity->toLink((string) $entity->label(), 'edit-form');
    $row['category'] = $entity->get('category');
    $row['images'] = $this->buildImages($entity);
    $row['used_by'] = $this->buildUsedBy($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, $type = 'edit') {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('duplicate')) {
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'weight' => 20,
        'url' => Url::fromRoute('entity.webform_image_select_images.duplicate_form', ['webform_image_select_images' => $entity->id()]),
      ];
    }
    if (isset($operations['delete'])) {
      $operations['delete']['attributes'] = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW);
    }
    return $operations;
  }

  /**
   * Build images for a webform image select images entity.
   *
   * @param \Drupal\webform_image_select\WebformImageSelectImagesInterface $entity
   *   A webform image select images entity.
   *
   * @return array
   *   Images for a webform image select images entity.
   */
  protected function buildImages(WebformImageSelectImagesInterface $entity) {
    $element = ['#images' => $entity->id()];
    $images = WebformImageSelectImages::getElementImages($element);
    if (!$images) {
      return [];
    }

    $build = [];
    foreach ($images as $key => $image) {
      $title = $image['text'] . ($key !== $image ? ' (' . $key . ')' : '');
      $build[] = [
        '#type' => 'html_tag',
        '#tag' => 'img',
        '#attributes' => [
          'src' => $image['src'],
          'alt' => $title,
          'title' => $title,
          'class' => ['js-webform-tooltip-link'],
          'style' => 'max-height:60px',
        ],
      ];
    }

    return ['data' => $build];
  }

  /**
   * Build list of webforms that the webform images is used by.
   *
   * @param \Drupal\webform_image_select\WebformImageSelectImagesInterface $webform_images
   *   A webform image select images entity.
   *
   * @return array
   *   Table data containing list of webforms that the webform images is used by.
   */
  protected function buildUsedBy(WebformImageSelectImagesInterface $webform_images) {
    $links = [];
    $webforms = $this->getStorage()->getUsedByWebforms($webform_images);
    foreach ($webforms as $id => $title) {
      $links[] = [
        '#type' => 'link',
        '#title' => $title,
        '#url' => Url::fromRoute('entity.webform.canonical', ['webform' => $id]),
        '#suffix' => '</br>',
      ];
    }
    return [
      'nowrap' => TRUE,
      'data' => $links,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    return parent::buildOperations($entity) + [
      '#prefix' => '<div class="webform-dropbutton">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Get the base entity query filtered by search and category.
   *
   * @param string $keys
   *   (optional) Search key.
   * @param string $category
   *   (optional) Category.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   */
  protected function getQuery($keys = '', $category = ''): QueryInterface {
    $query = $this->getStorage()->getQuery();

    // Filter by key(word).
    if ($keys) {
      $or = $query->orConditionGroup()
        ->condition('id', $keys, 'CONTAINS')
        ->condition('title', $keys, 'CONTAINS')
        ->condition('images', $keys, 'CONTAINS');
      $query->condition($or);
    }

    // Filter by category.
    if ($category) {
      $query->condition('category', $category);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $header = $this->buildHeader();
    $query = $this->getQuery($this->keys, $this->category);
    $query->tableSort($header);
    $query->pager($this->limit);
    return $query->execute();
  }

}
