<?php

namespace Drupal\embed\Controller;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\editor\EditorInterface;
use Drupal\embed\Ajax\EmbedInsertCommand;
use Drupal\embed\EmbedButtonInterface;
use Drupal\filter\FilterFormatInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Embed module routes.
 */
class EmbedController extends ControllerBase {

  use AjaxHelperTrait;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * The CSRF token name.
   *
   * @var string
   */
  public const PREVIEW_CSRF_TOKEN_NAME = 'X-Drupal-EmbedPreview-CSRF-Token';

  /**
   * Constructs an EmbedController instance.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   */
  public function __construct(RendererInterface $renderer, CsrfTokenGenerator $csrf_token) {
    $this->renderer = $renderer;
    $this->csrfToken = $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('renderer'),
      $container->get('csrf_token'),
    );
  }

  /**
   * Returns an Ajax response to generate preview of embedded items.
   *
   * Expects the HTML element as GET parameter.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\filter\FilterFormatInterface $filter_format
   *   The filter format.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception if 'value' parameter is not found in the request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The preview of the embedded item specified by the data attributes.
   */
  public function preview(Request $request, FilterFormatInterface $filter_format) {
    $this->checkCsrf($request, $this->currentUser());

    $text = $request->get('text') ?: $request->get('value');
    if (empty($text)) {
      throw new NotFoundHttpException();
    }

    $build = [
      '#type' => 'processed_text',
      '#text' => $text,
      '#format' => $filter_format->id(),
      '#langcode' => $this->languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId(),
    ];

    if ($this->isAjax()) {
      $response = new AjaxResponse();
      $response->addCommand(new EmbedInsertCommand($build));
      return $response;
    }
    else {
      $html = DeprecationHelper::backwardsCompatibleCall(
        currentVersion: \Drupal::VERSION,
        deprecatedVersion: '10.3',
        currentCallable: fn() => $this->renderer->renderInIsolation($build),
        deprecatedCallable: fn() => $this->renderer->renderPlain($build),
      );

      // Note that we intentionally do not use:
      // - \Drupal\Core\Cache\CacheableResponse because caching it on the server
      //   side is wasteful, hence there is no need for cacheability metadata.
      // - \Drupal\Core\Render\HtmlResponse because there is no need for
      //   attachments nor cacheability metadata.
      return (new Response($html))
        // Do not allow any intermediary to cache the response, only end user.
        ->setPrivate()
        // Allow the end user to cache it for up to 5 minutes.
        ->setMaxAge(300);
    }
  }

  /**
   * Returns an Ajax response to generate preview of an entity.
   *
   * Expects the HTML element as GET parameter.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\editor\EditorInterface $editor
   *   The editor.
   * @param \Drupal\embed\EmbedButtonInterface $embed_button
   *   The embed button.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws an exception if 'value' parameter is not found in the request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The preview of the embedded item specified by the data attributes.
   */
  public function previewEditor(Request $request, EditorInterface $editor, EmbedButtonInterface $embed_button) {
    return $this->preview($request, $editor->getFilterFormat());
  }

  /**
   * Throws an AccessDeniedHttpException if the request fails CSRF validation.
   *
   * This is used instead of \Drupal\Core\Access\CsrfAccessCheck, in order to
   * allow access for anonymous users.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @todo Refactor this to an access checker.
   */
  private function checkCsrf(Request $request, AccountInterface $account): void {
    if (!$request->headers->has(static::PREVIEW_CSRF_TOKEN_NAME)) {
      throw new AccessDeniedHttpException();
    }

    if ($account->isAnonymous()) {
      // For anonymous users, just the presence of the custom header is
      // sufficient protection.
      return;
    }

    // For authenticated users, validate the token value.
    $token = $request->headers->get(static::PREVIEW_CSRF_TOKEN_NAME);
    if (!$this->csrfToken->validate($token, static::PREVIEW_CSRF_TOKEN_NAME)) {
      throw new AccessDeniedHttpException();
    }
  }

}
