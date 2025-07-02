<?php

namespace Drupal\xmlsitemap_engines_test\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for xmlsitemap_engines_test.ping route.
 */
class XmlSitemapEnginesTestController extends ControllerBase {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a XmlSitemapEnginesTestController object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.channel.xmlsitemap')
    );
  }

  /**
   * Callback for the xmlsitemap_engines_test.ping route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response with 200 code if the url query is valid.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throw a NotFoundHttpException if query url is not valid.
   */
  public function render(Request $request) {
    $query = $request->query->get('sitemap');
    if (empty($query) || !UrlHelper::isValid($query)) {
      $this->logger->debug('No valid sitemap parameter provided.');
      // @todo Remove this? Causes an extra watchdog error to be handled.
      throw new NotFoundHttpException();
    }
    else {
      $this->logger->debug('Received ping for @sitemap.', ['@sitemap' => $query]);
    }
    return new Response('', 200);
  }

}
