<?php

namespace Drupal\cas\Controller;

use Drupal\cas\CasRedirectData;
use Drupal\cas\Service\CasRedirector;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ForceLoginController.
 *
 * Used to force CAS authentication for anonymous users.
 */
class ForceLoginController implements ContainerInjectionInterface {

  /**
   * The cas helper to get config settings from.
   *
   * @var \Drupal\cas\Service\CasRedirector
   */
  protected $casRedirector;

  /**
   * Used to get query string parameters from the request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   *
   * @param \Drupal\cas\Service\CasRedirector $cas_redirector
   *   The CAS Redirector service.
   * @param \Symfony\Component\HttpFoundation\RequestStack|null $request_stack
   *   (deprecated) The request stack. The $request_stack parameter is
   *   deprecated in cas:2.0.0 and is removed from cas:3.0.0.
   */
  public function __construct(CasRedirector $cas_redirector, $request_stack = NULL) {
    $this->casRedirector = $cas_redirector;
    // Support PHP 7.0.8. We should have been strict typed $request_stack as
    // nullable request stack (?RequestStack), as we deprecate it, but nullables
    // were introduced, later, in PHP 7.1.
    assert($request_stack === NULL || $request_stack instanceof RequestStack);
    if ($request_stack) {
      @trigger_error('The request stack parameter is deprecated in cas:2.0.0 and is removed from cas:3.0.0. See https://www.drupal.org/node/3231208', E_USER_DEPRECATED);
      $this->requestStack = $request_stack;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('cas.redirector'));
  }

  /**
   * Handles a page request for our forced login route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   */
  public function forceLogin(Request $request) {
    // @todo What if CAS is not configured? Need to handle that case.
    $service_url_query_params = $request->query->all();
    $cas_redirect_data = new CasRedirectData($service_url_query_params);
    $cas_redirect_data->setIsCacheable(TRUE);
    return $this->casRedirector->buildRedirectResponse($cas_redirect_data, TRUE);
  }

}
