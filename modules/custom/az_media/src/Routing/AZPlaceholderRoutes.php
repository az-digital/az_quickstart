<?php

namespace Drupal\az_media\Routing;

use Drupal\az_media\AZPlaceholderImageGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Registers the on-demand placeholder route inside the public files directory.
 *
 * The route has to be built at runtime because the public files path is a
 * site setting: hard-coding `sites/default/files` breaks anywhere
 * `file_public_path` has been changed. Core has the same problem and solves
 * it the same way.
 *
 * The route lives inside the files directory because that is where the
 * component's `src` prop points: a `public://` URI maps to a URL under the
 * public files path, so serving it means owning a path there. Nothing is
 * ever written at that path, so every request falls through the web server's
 * missing-file rule to index.php and lands here - the same mechanism image
 * style derivatives rely on, minus the write.
 *
 * That fallback is web server configuration rather than something Drupal
 * controls: Apache gets it from core's .htaccess `!-f` rule, and an nginx
 * host supplies its own equivalent. A host that only falls back for
 * `/styles/` paths would 404 here.
 *
 * @see \Drupal\image\Routing\ImageStyleRoutes
 */
final class AZPlaceholderRoutes implements ContainerInjectionInterface {

  public function __construct(
    protected StreamWrapperManagerInterface $streamWrapperManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('stream_wrapper_manager'));
  }

  /**
   * Returns the placeholder route.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   Route objects keyed by route name.
   */
  public function routes(): array {
    $public = $this->streamWrapperManager->getViaScheme('public');
    // getDirectoryPath() is a LocalStream concern, not part of the generic
    // stream wrapper interface, and getViaScheme() can return FALSE.
    if (!$public instanceof LocalStream) {
      // No local public files directory means nowhere to serve from.
      return [];
    }
    $directory_path = $public->getDirectoryPath();

    // `{dimensions}` must be a whole path segment, hence the trailing
    // `/placeholder.svg` rather than `{dimensions}.svg`. Drupal's route
    // provider finds candidates by swapping entire segments for '%', so a
    // segment that mixes a placeholder with a literal suffix is unreachable
    // however well its compiled regex matches. Core works around the same
    // limitation with an inbound path processor; giving the filename its own
    // segment avoids needing one.
    // @see \Drupal\Core\Routing\RouteProvider::getCandidateOutlines()
    // @see \Drupal\image\PathProcessor\PathProcessorImageStyles
    return [
      'az_media.placeholder_image' => new Route(
        '/' . $directory_path . '/' . AZPlaceholderImageGenerator::DIRECTORY . '/{dimensions}/placeholder.svg',
        [
          '_controller' => '\Drupal\az_media\Controller\AZPlaceholderImageController::deliver',
        ],
        [
          '_access' => 'TRUE',
          // Reject anything that is not two small integers before the
          // controller is ever reached.
          'dimensions' => '\d{1,4}x\d{1,4}',
        ]
      ),
    ];
  }

}
