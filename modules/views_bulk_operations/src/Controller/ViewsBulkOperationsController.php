<?php

namespace Drupal\views_bulk_operations\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines VBO controller class.
 */
class ViewsBulkOperationsController extends ControllerBase implements ContainerInjectionInterface {

  use ViewsBulkOperationsFormTrait;

  /**
   * Constructs a new controller object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   Private temporary storage factory.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer service object.
   */
  public function __construct(
    protected readonly PrivateTempStoreFactory $tempStoreFactory,
    protected readonly ViewsBulkOperationsActionProcessorInterface $actionProcessor,
    protected readonly RendererInterface $renderer
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('views_bulk_operations.processor'),
      $container->get('renderer')
    );
  }

  /**
   * The actual page callback.
   *
   * @param string $view_id
   *   The current view ID.
   * @param string $display_id
   *   The display ID of the current view.
   */
  public function execute($view_id, $display_id): RedirectResponse {
    $view_data = $this->getTempstoreData($view_id, $display_id);
    if (empty($view_data)) {
      throw new NotFoundHttpException();
    }
    $this->deleteTempstoreData();

    return $this->actionProcessor->executeProcessing($view_data);
  }

  /**
   * AJAX callback to update selection (multipage).
   *
   * @param string $view_id
   *   The current view ID.
   * @param string $display_id
   *   The display ID of the current view.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function updateSelection($view_id, $display_id, Request $request): AjaxResponse {
    $response = [];
    $tempstore_data = $this->getTempstoreData($view_id, $display_id);
    if (empty($tempstore_data)) {
      throw new NotFoundHttpException();
    }

    $parameters = $request->request->all();

    if ($parameters['op'] === 'method_include') {
      unset($tempstore_data['exclude_mode']);
      $tempstore_data['list'] = [];
    }
    elseif ($parameters['op'] === 'method_exclude') {
      $tempstore_data['exclude_mode'] = TRUE;
      $tempstore_data['list'] = [];
    }
    elseif ($parameters['op'] === 'update') {
      $exclude_mode = \array_key_exists('exclude_mode', $tempstore_data) && $tempstore_data['exclude_mode'] === TRUE;
      foreach ($parameters['list'] as $bulkFormKey => $state) {
        if ($exclude_mode) {
          $state = $state === 'true' ? 'false' : 'true';
        }
        if ($state === 'true') {
          $list_item = $this->getListItem($bulkFormKey);
          if ($list_item !== NULL) {
            $tempstore_data['list'][$bulkFormKey] = $list_item;
          }
        }
        else {
          unset($tempstore_data['list'][$bulkFormKey]);
        }
      }
    }

    $this->setTempstoreData($tempstore_data);

    $count = empty($tempstore_data['exclude_mode']) ? \count($tempstore_data['list']) : $tempstore_data['total_results'] - \count($tempstore_data['list']);

    $selection_info_renderable = $this->getMultipageList($tempstore_data);
    $response_data = [
      'count' => $count,
      'selection_info' => $this->renderer->renderRoot($selection_info_renderable),
    ];

    $response = new AjaxResponse();
    $response->setData($response_data);
    return $response;
  }

}
