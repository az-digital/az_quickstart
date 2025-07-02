<?php

namespace Drupal\webform_templates\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route responses for webform templates.
 */
class WebformTemplatesController extends ControllerBase implements ContainerInjectionInterface {

  use WebformEntityStorageTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');
    $instance->formBuilder = $container->get('form_builder');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Returns the webform templates index page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param bool $manage
   *   Manage templates.
   *
   * @return array|RedirectResponse
   *   A render array representing the webform templates index page or redirect
   *   response to a selected webform via the filter's autocomplete.
   */
  public function index(Request $request, $manage = FALSE) {
    $keys = $request->get('search');
    $category = $request->get('category');

    // Handler autocomplete redirect.
    if ($keys && preg_match('#\(([^)]+)\)$#', $keys, $match)) {
      if ($webform = $this->getWebformStorage()->load($match[1])) {
        return new RedirectResponse($webform->toUrl()->setAbsolute(TRUE)->toString());
      }
    }

    $header = [];
    $header['title'] = $this->t('Title');
    $header['description'] = ['data' => $this->t('Description'), 'class' => [RESPONSIVE_PRIORITY_LOW]];
    $header['categories'] = ['data' => $this->t('Categories'), 'class' => [RESPONSIVE_PRIORITY_LOW]];
    if ($manage) {
      $header['owner'] = ['data' => $this->t('Author'), 'class' => [RESPONSIVE_PRIORITY_LOW]];
    }
    $header['operations'] = ['data' => $this->t('Operations')];

    $webforms = $this->getTemplates($keys, $category);
    $rows = [];
    foreach ($webforms as $webform) {
      $route_parameters = ['webform' => $webform->id()];

      $row['title'] = $webform->toLink();
      $row['description']['data'] = WebformHtmlEditor::checkMarkup($webform->get('description'));
      $row['categories']['data']['#markup'] = implode('; ', $webform->get('categories') ?: []);

      if ($manage) {
        $row['owner'] = ($owner = $webform->getOwner()) ? $owner->toLink() : '';

        $operations = [];
        if ($webform->access('update')) {
          $operations['edit'] = [
            'title' => $this->t('Build'),
            'url' => $webform->toUrl('edit-form'),
          ];
        }
        if ($webform->access('submission_page')) {
          $operations['view'] = [
            'title' => $this->t('View'),
            'url' => $webform->toUrl('canonical'),
          ];
        }
        if ($webform->access('update')) {
          $operations['settings'] = [
            'title' => $this->t('Settings'),
            'url' => $webform->toUrl('settings'),
          ];
        }
        if ($webform->access('duplicate')) {
          $operations['duplicate'] = [
            'title' => $this->t('Duplicate'),
            'url' => $webform->toUrl('duplicate-form', ['query' => ['template' => 1]]),
            'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
          ];
        }
        if ($webform->access('delete') && $webform->hasLinkTemplate('delete-form')) {
          $operations['delete'] = [
            'title' => $this->t('Delete'),
            'url' => $this->ensureDestination($webform->toUrl('delete-form')),
            'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
          ];
        }
        $row['operations']['data'] = [
          '#type' => 'operations',
          '#links' => $operations,
          '#prefix' => '<div class="webform-dropbutton">',
          '#suffix' => '</div>',
        ];
      }
      else {
        $row['operations']['data']['select'] = [
          '#type' => 'link',
          '#title' => $this->t('Select'),
          '#url' => Url::fromRoute('entity.webform.duplicate_form', $route_parameters),
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW, ['button', 'button--primary']),
        ];
        $row['operations']['data']['preview'] = [
          '#type' => 'link',
          '#title' => $this->t('Preview'),
          '#url' => Url::fromRoute('entity.webform.preview', $route_parameters),
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NORMAL, ['button']),
        ];
      }

      $rows[] = $row;
    }

    $build = [];
    $build['filter_form'] = $this->formBuilder->getForm('\Drupal\webform_templates\Form\WebformTemplatesFilterForm', $keys, $category);

    // Display info.
    if ($total = count($rows)) {
      $build['info'] = [
        '#markup' => $this->formatPlural($total, '@count template', '@count templates'),
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#sticky' => TRUE,
      '#empty' => $this->t('There are no templates available.'),
      '#cache' => [
        'contexts' => $this->getWebformStorage()->getEntityType()->getListCacheContexts(),
        'tags' => $this->getWebformStorage()->getEntityType()->getListCacheTags(),
      ],
    ];

    // Must preload libraries required by (modal) dialogs.
    WebformDialogHelper::attachLibraries($build);

    return $build;
  }

  /**
   * Returns a webform to add a new submission to a webform.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform this submission will be added to.
   *
   * @return array|NotFoundHttpException
   *   The webform submission webform.
   */
  public function previewForm(Request $request, WebformInterface $webform) {
    if (!$webform->isTemplate()) {
      return new NotFoundHttpException();
    }

    return $webform->getSubmissionForm([], 'preview');
  }

  /**
   * Get webform templates.
   *
   * @param string $keys
   *   (optional) Filter templates by keyword.
   * @param string $category
   *   (optional) Filter templates by category.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   An array webform entity that are used as templates.
   */
  protected function getTemplates($keys = '', $category = '') {
    $query = $this->getWebformStorage()->getQuery();
    $query->condition('template', TRUE);
    $query->condition('archive', FALSE);
    // Filter by key(word).
    if (in_array($keys, array_values($this->getWebformStorage()->getCategories()))) {
      $query->condition('categories.*', $keys);
    }
    elseif ($keys) {
      $or = $query->orConditionGroup()
        ->condition('title', $keys, 'CONTAINS')
        ->condition('description', $keys, 'CONTAINS')
        ->condition('elements', $keys, 'CONTAINS');
      $query->condition($or);
    }

    // Filter by category.
    if ($category) {
      $query->condition('categories.*', $category);
    }

    $query->sort('title');

    $entity_ids = $query->execute();
    if (empty($entity_ids)) {
      return [];
    }

    /** @var \Drupal\webform\WebformInterface[] $entities */
    $entities = $this->getWebformStorage()->loadMultiple($entity_ids);

    // If the user is not a webform admin, check view access to each webform.
    if (!$this->isAdmin()) {
      foreach ($entities as $entity_id => $entity) {
        if (!$entity->access('view')) {
          unset($entities[$entity_id]);
        }
      }
    }

    return $entities;

  }

  /**
   * Route preview title callback.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   *
   * @return string
   *   The webform label.
   */
  public function previewTitle(WebformInterface $webform = NULL) {
    return $this->t('Previewing @title template', ['@title' => $webform->label()]);
  }

  /**
   * Is the current user a webform administrator.
   *
   * @return bool
   *   TRUE if the current user has 'administer webform' or 'edit any webform'
   *   permission.
   */
  protected function isAdmin() {
    return ($this->currentUser->hasPermission('administer webform') || $this->currentUser->hasPermission('edit any webform'));
  }

  /**
   * Ensures that a destination is present on the given URL.
   *
   * @param \Drupal\Core\Url $url
   *   The URL object to which the destination should be added.
   *
   * @return \Drupal\Core\Url
   *   The updated URL object.
   */
  protected function ensureDestination(Url $url) {
    return $url->mergeOptions(['query' => $this->getRedirectDestination()->getAsArray()]);
  }

}
