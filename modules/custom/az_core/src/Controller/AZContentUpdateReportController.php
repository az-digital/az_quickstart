<?php

namespace Drupal\az_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\az_core\AZContentUpdateTracker;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Arizona content updates report.
 */
final class AZContentUpdateReportController extends ControllerBase {

  /**
   * Constructs a new AZContentUpdateReportController.
   *
   * @param \Drupal\az_core\AZContentUpdateTracker $contentUpdateTracker
   *   The content update tracker service.
   */
  public function __construct(
    protected readonly AZContentUpdateTracker $contentUpdateTracker,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get(AZContentUpdateTracker::class)
    );
  }

  /**
   * Builds the report page.
   *
   * @return array
   *   A render array for the report.
   */
  public function content() {
    return $this->contentUpdateTracker->buildReport();
  }

}
