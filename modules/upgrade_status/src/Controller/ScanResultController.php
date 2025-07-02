<?php

namespace Drupal\upgrade_status\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\upgrade_status\ProjectCollector;
use Drupal\upgrade_status\ScanResultFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ScanResultController extends ControllerBase {

  /**
   * The scan result formatter service.
   *
   * @var \Drupal\upgrade_status\ScanResultFormatter
   */
  protected $resultFormatter;

  /**
   * The project collector service.
   *
   * @var \Drupal\upgrade_status\ProjectCollector
   */
  protected $projectCollector;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a \Drupal\upgrade_status\Controller\ScanResultController.
   *
   * @param \Drupal\upgrade_status\ScanResultFormatter $result_formatter
   *   The scan result formatter service.
   * @param \Drupal\upgrade_status\ProjectCollector $project_collector
   *   The project collector service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    ScanResultFormatter $result_formatter,
    ProjectCollector $project_collector,
    RendererInterface $renderer
  ) {
    $this->resultFormatter = $result_formatter;
    $this->projectCollector = $project_collector;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('upgrade_status.result_formatter'),
      $container->get('upgrade_status.project_collector'),
      $container->get('renderer')
    );
  }

  /**
   * Builds content for the error list page/popup.
   *
   * @param string $project_machine_name
   *   The machine name of the project.
   *
   * @return array
   *   Build array.
   */
  public function resultPage(string $project_machine_name) {
    $extension = $this->projectCollector->loadProject($project_machine_name);
    return $this->resultFormatter->formatResult($extension);
  }

  /**
   * Generates single project export.
   *
   * @param string $project_machine_name
   *   The machine name of the project.
   * @param string $format
   *   The format to use when exporting the data: html or ascii.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response object.
   */
  public function resultExport(string $project_machine_name, string $format) {
    $extension = $this->projectCollector->loadProject($project_machine_name);
    $result = $this->resultFormatter->getRawResult($extension);

    // Sanitize user input.
    if (!in_array($format, ['html', 'ascii'])) {
      $format = 'html';
    }

    $build = ['#theme' =>  'upgrade_status_' . $format . '_export' ];
    $build['#projects'][$extension->info['upgrade_status_type'] == ProjectCollector::TYPE_CUSTOM ? 'custom' : 'contrib'] = [
      $project_machine_name =>
        $format == 'html' ?
          $this->resultFormatter->formatResult($extension) :
          $this->resultFormatter->formatAsciiResult($extension) ,
    ];

    $fileDate = $this->resultFormatter->formatDateTime($result['date'], 'html_datetime');
    $extension = $format == 'html' ? '.html' : '.txt';
    $filename = 'single-export-' . $project_machine_name . '-' . $fileDate . $extension;
    $response = new Response($this->renderer->renderRoot($build));
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

    return $response;
  }

  /**
   * Analyze a specific project in its own HTTP request.
   *
   * @param string $project_machine_name
   *   The machine name of the project.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response object.
   */
  public function analyze(string $project_machine_name) {
    if ($project_machine_name == 'upgrade_status_request_test') {
      // Handle the special case of a request test which is testing the
      // HTTP sandboxing capability.
      return new JsonResponse(
        ['message' => 'Request test success']
      );
    }
    else {
      // Dealing with a real project.
      $extension = $this->projectCollector->loadProject($project_machine_name);
      \Drupal::service('upgrade_status.deprecation_analyzer')->analyze($extension);
      return new JsonResponse(
        ['message' => $this->t('Scanned @project', ['@project' => $extension->getName()])]
      );
    }
  }
}
