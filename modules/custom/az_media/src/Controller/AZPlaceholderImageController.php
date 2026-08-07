<?php

namespace Drupal\az_media\Controller;

use Drupal\az_media\AZPlaceholderImageGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves a generated placeholder image.
 *
 * This runs when a browser asks for a placeholder URL, for example
 * /sites/default/files/az-placeholder/600x400/placeholder.svg. We build the
 * SVG markup on the spot and send it straight back. Nothing is saved as a
 * file on the server, so every request that reaches us is built fresh.
 *
 * Not many do. The Cache-Control header set below tells browsers and CDNs
 * how long they may reuse what we sent them; ours says a year, and marks it
 * immutable, meaning it will never change. So repeat requests for the same
 * size are usually answered by one of those caches rather than by us.
 *
 * @see \Drupal\az_media\AZPlaceholderImageGenerator
 * @see \Drupal\az_media\Routing\AZPlaceholderRoutes
 */
final class AZPlaceholderImageController implements ContainerInjectionInterface {

  /**
   * How long a placeholder may be cached, in seconds.
   *
   * The same size always produces the same image, so there is nothing to
   * go stale. One year is the longest value caches are expected to honor.
   */
  const MAX_AGE = 31536000;

  public function __construct(
    protected AZPlaceholderImageGenerator $generator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('az_media.placeholder_image_generator'));
  }

  /**
   * Generates and returns a placeholder image.
   *
   * @param string $dimensions
   *   The requested size as `{width}x{height}`. The route already constrains
   *   this to two integers of at most four digits.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The SVG response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   When either dimension falls outside the permitted range.
   */
  public function deliver(string $dimensions): Response {
    [$width, $height] = array_map('intval', explode('x', $dimensions));

    // If not in the allowed width and height, give a 404 error. Rationale:
    // If someone typed 8000x600 (most likely a typo of 800x600), we could
    // round down to 4000x600, but the user may not notice until later. So
    // we want to fail instead of rounding down.
    if (!$this->generator->isValidSize($width, $height)) {
      throw new NotFoundHttpException();
    }

    $response = new Response($this->generator->generate($width, $height), Response::HTTP_OK, [
      'Content-Type' => 'image/svg+xml',
    ]);
    // Together these set Cache-Control: public, max-age=<a year>, immutable.
    // public lets shared caches (a CDN, not just the user's browser) keep a
    // copy; max-age is how long they may reuse it; immutable means it will
    // never change, so they never need to check back with us. Expires says
    // the same deadline in an older format that some caches still read.
    //
    // Drupal has a subscriber that rewrites cache headers on the way out.
    // It leaves ours alone only because this is a plain Response and we set
    // Cache-Control ourselves. Setting Expires stops it stamping its own
    // 1978 date on top.
    //
    // So if you switch to a CacheableResponse, or drop either header
    // (Cache-Control or Expires), the subscriber takes over and the
    // year-long cache time disappears with no error to tell you.
    //
    // @see \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
    $response->setPublic();
    $response->setMaxAge(self::MAX_AGE);
    $response->headers->addCacheControlDirective('immutable');
    $response->setExpires(new \DateTime('@' . (time() + self::MAX_AGE)));

    return $response;
  }

}
