<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Entity\Controller\EntityViewController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a controller to render a single webform submission.
 */
class WebformSubmissionViewController extends EntityViewController {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');
    $instance->entityRepository = $container->get('entity.repository');
    $instance->requestHandler = $container->get('webform.request');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $webform_submission, $view_mode = 'default', $langcode = NULL) {
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity('webform_submission');

    // Set webform submission template.
    $build = [
      '#theme' => 'webform_submission',
      '#view_mode' => $view_mode,
      '#webform_submission' => $webform_submission,
    ];

    // Navigation.
    $build['navigation'] = [
      '#type' => 'webform_submission_navigation',
      '#webform_submission' => $webform_submission,
    ];

    // Information.
    $build['information'] = [
      '#type' => 'webform_submission_information',
      '#webform_submission' => $webform_submission,
      '#source_entity' => $source_entity,
    ];

    // Submission.
    $build['submission'] = parent::view($webform_submission, $view_mode, $langcode);

    // Library.
    $build['#attached']['library'][] = 'webform/webform.admin';

    // Add entities cacheable dependency.
    $this->renderer->addCacheableDependency($build, $this->currentUser);
    $this->renderer->addCacheableDependency($build, $webform);
    $this->renderer->addCacheableDependency($build, $webform_submission);
    if ($source_entity) {
      $this->renderer->addCacheableDependency($build, $source_entity);
    }

    return $build;
  }

  /**
   * The _title_callback for the page that renders a single webform submission.
   *
   * @param \Drupal\Core\Entity\EntityInterface $webform_submission
   *   The current webform submission.
   * @param bool $duplicate
   *   Flag indicating if submission is being duplicated.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $webform_submission, $duplicate = FALSE) {
    $title = $this->entityRepository->getTranslationFromContext($webform_submission)->label();
    return ($duplicate) ? $this->t('Duplicate @title', ['@title' => $title]) : $title;
  }

}
