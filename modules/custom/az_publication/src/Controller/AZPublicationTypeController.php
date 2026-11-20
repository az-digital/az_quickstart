<?php

declare(strict_types=1);

namespace Drupal\az_publication\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\az_publication\Entity\AZPublicationType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for AJAX operations on AZPublicationType entities.
 */
class AZPublicationTypeController extends ControllerBase {

  /**
   * Performs an operation on an AZPublicationType and returns a response.
   *
   * This method handles AJAX operations for AZPublicationType entities.
   * Depending on the request type, it either returns an AJAX response
   * with a rendered entity list or redirects back to the list page.
   *
   * @param \Drupal\az_publication\Entity\AZPublicationType $az_publication_type
   *   The AZPublicationType entity on which the operation is performed.
   * @param string $op
   *   The operation to perform. Expected to be a method name in
   *   AZPublicationType.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object. Used to determine if the request is
   *   an AJAX request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns an AjaxResponse if the request is an AJAX request, otherwise
   *   a RedirectResponse.
   */
  public function ajaxOperation(AZPublicationType $az_publication_type, $op, Request $request) {
    // Perform the operation.
    if (method_exists($az_publication_type, $op)) {
      $az_publication_type->$op()->save();
    }

    // If the request is via AJAX, return the rendered list as an AJAX response.
    if ($request->request->get('js')) {
      $list = $this->entityTypeManager()->getListBuilder('az_publication_type')->render();
      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#az-publication-type-entity-list', $list));
      return $response;
    }

    // For non-AJAX requests, redirect back to the list page.
    return $this->redirect('entity.az_publication_type.collection');
  }

}
