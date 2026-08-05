<?php

namespace Drupal\az_media\Routing;

use Drupal\az_media\AZPlaceholderImageGenerator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Registers the URL that serves placeholder images.
 *
 * Drupal only answers URLs it knows about. This class gives it one:
 * /sites/default/files/az-placeholder/{width}x{height}/placeholder.svg,
 * pointed at AZPlaceholderImageController.
 *
 * We build that path in code instead of writing it in a routing.yml file
 * because the `sites/default/files` part is a site setting, and a site
 * that moved its files directory would break a hard-coded path. Core's
 * image module has the same problem and solves it the same way.
 *
 * The URL sits under the files directory because that is where the
 * component's `src` prop points - a `public://` URI turns into a URL
 * there. Nothing is ever saved at that path, so the web server finds no
 * file and hands the request to Drupal, which is how it reaches us. That
 * hand-off is web server config, not Drupal's: Apache gets it from core's
 * .htaccess, nginx hosts supply their own. Pantheon's nginx hands off for
 * our path. If placeholders ever 404 on a new platform, check this first.
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
    // Get the local public files directory (whatever public:// is set to).
    // Ask for the object behind that alias, since it knows the real folder
    // name - sites/default/files unless a site changed it.
    $public = $this->streamWrapperManager->getViaScheme('public');
    // If public:// isn't a normal local directory, register nothing.
    // For example, a site keeping its public files on something like S3 has
    // no local path to build a URL from.
    if (!$public instanceof LocalStream) {
      return [];
    }
    $directory_path = $public->getDirectoryPath();

    // The filename gets its own path segment: {dimensions}/placeholder.svg,
    // not {dimensions}.svg. Rationale: when a request comes in, Drupal
    // looks for matching routes by swapping whole segments for '%' - for
    // our URL it tries .../az-placeholder/%/placeholder.svg. A segment that
    // mixes a placeholder with a literal suffix ({dimensions}.svg) never
    // shows up in that list, so the route can't be found no matter how well
    // its regex matches. Core hits the same wall and works around it with a
    // path processor; giving the filename its own segment avoids needing
    // one.
    // @see \Drupal\Core\Routing\RouteProvider::getCandidateOutlines()
    // @see \Drupal\image\PathProcessor\PathProcessorImageStyles
    return [
      'az_media.placeholder_image' => new Route(
        '/' . $directory_path . '/' . AZPlaceholderImageGenerator::DIRECTORY . '/{dimensions}/placeholder.svg',
        [
          '_controller' => '\Drupal\az_media\Controller\AZPlaceholderImageController::deliver',
        ],
        [
          // Open to everyone: an <img> tag on a public page loads this URL,
          // so there is no user to check permissions against.
          '_access' => 'TRUE',
          // Only match 1-4 digits, an x, then 1-4 digits. Anything else
          // 404s before the controller runs.
          'dimensions' => '\d{1,4}x\d{1,4}',
        ]
      ),
    ];
  }

}
