<?php

namespace Drupal\embed_test\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\HtmlResponse;
use Drupal\editor\EditorInterface;
use Drupal\embed\EmbedButtonInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Embed Test module routes.
 */
final class EmbedTestController extends ControllerBase {

  /**
   * Constructs an EmbedController instance.
   */
  public function __construct(
    private readonly CsrfTokenGenerator $csrfToken,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('csrf_token')
    );
  }

  /**
   * Verify HTML response.
   */
  public function testAccess(Request $request, EditorInterface $editor, EmbedButtonInterface $embed_button) {
    $text = $request->get('value');

    $response = new HtmlResponse([
      '#markup' => $text,
      '#cache' => [
        'contexts' => ['url.query_args:value'],
      ],
    ]);

    if ($text == '') {
      $response->setStatusCode(404);
    }

    return $response;
  }

  /**
   * Return CSRF token.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   CSRF token.
   */
  public function getCsrfToken() {
    return new JsonResponse($this->csrfToken->get('X-Drupal-EmbedPreview-CSRF-Token'));
  }

}
