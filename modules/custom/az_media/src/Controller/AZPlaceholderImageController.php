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
 * The response is built per request and never written to disk; caching is
 * left entirely to the immutable Cache-Control header below.
 *
 * @see \Drupal\az_media\AZPlaceholderImageGenerator
 * @see \Drupal\az_media\Routing\AZPlaceholderRoutes
 */
final class AZPlaceholderImageController implements ContainerInjectionInterface {

  /**
   * How long a placeholder may be cached, in seconds.
   *
   * A placeholder for a given size is a pure function of that size and can
   * never change, so this is the conventional one-year maximum.
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

    // Out-of-range sizes 404 rather than being clamped, so a mistaken
    // `examples` value announces itself instead of quietly rendering at some
    // other size. There is no bound beyond that range: the response is
    // computed and never stored, so an arbitrary number of distinct sizes
    // costs nothing more than the requests themselves.
    if (!$this->generator->isValidSize($width, $height)) {
      throw new NotFoundHttpException();
    }

    $response = new Response($this->generator->generate($width, $height), Response::HTTP_OK, [
      'Content-Type' => 'image/svg+xml',
    ]);
    $response->setPublic();
    $response->setMaxAge(self::MAX_AGE);
    $response->headers->addCacheControlDirective('immutable');
    // Set Expires to agree with max-age. HTTP/1.1 caches prefer Cache-Control
    // and would ignore Expires entirely, but Drupal otherwise stamps a 1978
    // date here for any response it treats as uncacheable, and a long max-age
    // sitting beside an expiry in the past invites misreading. Core only
    // applies its default when the header is absent.
    // @see \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
    $response->setExpires(new \DateTime('@' . (time() + self::MAX_AGE)));

    return $response;
  }

}
