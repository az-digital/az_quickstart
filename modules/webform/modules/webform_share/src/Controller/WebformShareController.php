<?php

namespace Drupal\webform_share\Controller;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\WebformMessageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for webform share page, script, and embed.
 */
class WebformShareController extends ControllerBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The webform message manager.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface
   */
  protected $messageManager;

  /**
   * The webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->renderer = $container->get('renderer');
    $instance->messageManager = $container->get('webform.message_manager');
    $instance->requestHandler = $container->get('webform.request');
    return $instance;
  }

  /**
   * Returns a webform to be shared as the page of an iframe.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param string|null $library
   *   The iframe JavaScript library.
   * @param string|null $version
   *   The iframe JavaScript library version.
   *
   * @return array
   *   The webform rendered in a page template with only the content.
   *
   * @see \Drupal\webform_share\Theme\WebformShareThemeNegotiator
   * @see page--webform-share.html.twig
   * @see webform_share.libraries.yml
   */
  public function page(Request $request, $library = NULL, $version = NULL) {
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity(['webform']);

    $build = [];
    // Webform.
    $build['webform'] = [
      '#type' => 'webform',
      '#webform' => $webform,
      '#source_entity' => $source_entity,
      '#prefix' => '<div class="webform-share-submission-form">',
      '#suffix' => '</div>',
    ];
    // Attachments.
    $build['#attached']['library'][] = 'webform_share/webform_share.page';
    if ($library && $version) {
      $build['#attached']['library'][] = "webform_share/libraries.$library.$version";
    }
    // Add setting notifying AjaxCommand that this page is shared via an
    // embedded iframe.
    // @see Drupal.AjaxCommands.prototype.webformRefresh
    $build['#attached']['drupalSettings']['webform_share']['page'] = TRUE;
    return $build;
  }

  /**
   * Returns a webform to be shared using (java)script.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   * @param string|null $library
   *   The iframe JavaScript library.
   * @param string|null $version
   *   The iframe JavaScript library version.
   *
   * @return array
   *   The webform rendered in a page template with only the content.
   *
   * @see \Drupal\webform_share\Theme\WebformShareThemeNegotiator
   * @see page--webform-share.html.twig
   * @see webform_share.libraries.yml
   */
  public function script(Request $request, $library = NULL, $version = NULL) {
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity(['webform']);

    $build = [
      '#type' => 'webform_share_iframe',
      '#webform' => $webform,
      '#source_entity' => $source_entity,
      '#javascript' => TRUE,
      '#query' => $request->query->all(),
    ];
    $iframe = $this->renderer->renderPlain($build);

    $iframe_script = json_encode($iframe);
    $iframe_script = str_replace('src=\\"\/\/', 'src=\\"' . $request->getScheme() . ':\/\/', $iframe_script);
    $content = 'document.write(' . $iframe_script . ');';
    $response = new CacheableResponse($content, 200, ['Content-Type' => 'text/javascript']);

    $additional_cache_contexts = [];
    foreach ($webform->getElementsPrepopulate() as $element_key) {
      $additional_cache_contexts[] = 'url.query_args:' . $element_key;
    }
    $webform->addCacheContexts($additional_cache_contexts);

    $response->addCacheableDependency($webform);
    if ($source_entity) {
      $response->addCacheableDependency($source_entity);
    }

    return $response;
  }

  /**
   * Returns a preview of a webform to be shared.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array containing a review of the webform to be shared.
   */
  public function preview(Request $request) {
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity(['webform']);

    $build = [];
    if ($this->currentUser()->isAuthenticated()) {
      $build['message'] = [
        '#type' => 'webform_message',
        '#message_message' => [
          'message' => [
            '#markup' => $this->t('To test anonymous user access to the below embedded webform, please log out or open the below link in a new private or incognito window.'),
            '#suffix' => '<br/>',
          ],
          'link' => [
            '#type' => 'link',
            '#url' => Url::fromRoute('<current>'),
            '#title' => Url::fromRoute('<current>')->setAbsolute()->toString(),
          ],
        ],
        '#message_type' => 'info',
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_SESSION,
      ];
    }
    $build['preview'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['webform-share-iframe-container']],
    ];
    $build['preview']['iframe'] = [
      '#type' => 'webform_share_iframe',
      '#webform' => $webform,
      '#source_entity' => $source_entity,
      '#javascript' => TRUE,
      '#options' => ['log' => TRUE],
      '#query' => $request->query->all(),
    ];
    $build['#attached']['library'][] = 'webform_share/webform_share.admin';
    return $build;
  }

  /**
   * Returns a test of a webform to be shared.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array containing a review of the webform to be shared.
   */
  public function test(Request $request) {
    $webform = $this->requestHandler->getCurrentWebform();
    $source_entity = $this->requestHandler->getCurrentSourceEntity(['webform']);

    $build = [];
    $build['message'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->messageManager->get(WebformMessageManagerInterface::SUBMISSION_TEST),
      '#message_type' => 'warning',
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
    ];
    $build['test'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['webform-share-iframe-container']],
    ];
    $build['test']['iframe'] = [
      '#type' => 'webform_share_iframe',
      '#webform' => $webform,
      '#source_entity' => $source_entity,
      '#test' => TRUE,
      '#javascript' => TRUE,
      '#options' => ['log' => TRUE],
      '#query' => $request->query->all(),
    ];
    $build['#attached']['library'][] = 'webform_share/webform_share.admin';
    return $build;
  }

}
