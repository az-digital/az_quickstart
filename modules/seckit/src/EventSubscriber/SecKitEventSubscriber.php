<?php

namespace Drupal\seckit\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\seckit\SeckitInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribing an event.
 */
class SecKitEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Request object.
   *
   * @var Request
   */
  protected $request;

  /**
   * Response object.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  protected $response;

  /**
   * Logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs an SecKitEventSubscriber object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The Seckit logger channel.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   */
  public function __construct(LoggerInterface $logger, ConfigFactoryInterface $config_factory, ModuleExtensionList $extension_list_module) {
    $this->logger = $logger;
    $this->config = $config_factory->get('seckit.settings');
    $this->moduleExtensionList = $extension_list_module;
  }

  /**
   * Executes actions on the request event.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Event Response Object.
   */
  public function onKernelRequest(RequestEvent $event) {
    $this->request = $event->getRequest();

    // Execute necessary functions.
    if ($this->config->get('seckit_csrf.origin')) {
      $this->seckitOrigin($event);
    }
  }

  /**
   * Executes actions on the response event.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   Filter Response Event object.
   */
  public function onKernelResponse(ResponseEvent $event) {
    $this->response = $event->getResponse();

    // Execute necessary functions.
    if ($this->config->get('seckit_xss.csp.checkbox')) {
      $this->seckitCsp();
    }
    if ($this->config->get('seckit_xss.x_xss.select')) {
      $this->seckitXxss($this->config->get('seckit_xss.x_xss.select'));
    }
    if ($this->config->get('seckit_clickjacking.js_css_noscript')) {
      $this->seckitJsCssNoscript();
    }
    if ($this->config->get('seckit_ssl.hsts')) {
      $this->seckitHsts();
    }
    if ($this->config->get('seckit_various.from_origin')) {
      $this->seckitFromOrigin();
    }
    if ($this->config->get('seckit_various.referrer_policy')) {
      $this->seckitReferrerPolicy();
    }
    if ($this->config->get('seckit_ct.expect_ct')) {
      $this->seckitExpectCt();
    }
    if ($this->config->get('seckit_fp.feature_policy')) {
      $this->seckitFeaturePolicy();
    }

    // Always call this (regardless of the setting) since if it's disabled it
    // may be necessary to actively disable the core's clickjacking defense.
    $this->seckitXframe($this->config->get('seckit_clickjacking.x_frame'), $event);
  }

  /**
   * Aborts HTTP request upon invalid 'Origin' HTTP request header.
   *
   * When included in an HTTP request, the Origin header indicates the origin(s)
   * that caused the user agent to issue the request. This helps to protect
   * against CSRF attacks, as we can abort requests with an unapproved origin.
   *
   * Applies to all HTTP request methods except GET and HEAD.
   *
   * Requests which do not include an 'Origin' header must always be allowed,
   * as (a) not all user-agents support the header, and (b) those that do may
   * include it or omit it at their discretion.
   *
   * Note that (a) will become progressively less of a factor over time --
   * CSRF attacks depend upon convincing a user agent to send a request, and
   * there is no particular motivation for users to prevent their web browsers
   * from sending this header; so as people upgrade to browsers which support
   * 'Origin', its effectiveness increases.
   *
   * Implementation of Origin is based on specification draft available at
   * http://tools.ietf.org/html/draft-abarth-origin-09
   */
  public function seckitOrigin($event) {
    // Allow requests without an 'Origin' header, or with a 'null' origin.
    $origin = $this->request->headers->get('Origin');
    if (!$origin || $origin === 'null') {
      return;
    }
    // Allow command-line requests.
    if (PHP_SAPI === 'cli') {
      return;
    }
    // Allow GET and HEAD requests.
    $method = $this->request->getMethod();
    if (in_array($method, ['GET', 'HEAD'], TRUE)) {
      return;
    }

    // Allow requests from whitelisted Origins.
    global $base_root;

    $whitelist = explode(',', $this->config->get('seckit_csrf.origin_whitelist'));
    // Default origin is always allowed.
    $whitelist[] = $base_root;
    $whitelist = array_values(array_filter(array_map('trim', $whitelist)));
    if (in_array($origin, $whitelist, TRUE)) {
      return;
      // n.b. RFC 6454 allows Origins to have more than one value (each
      // separated by a single space).  All values must be on the whitelist
      // (order is not important).  We intentionally do not handle this
      // because the feature has been confirmed as a design mistake which
      // user agents do not utilize in practice.  For details, see
      // http://lists.w3.org/Archives/Public/www-archive/2012Jun/0001.html
      // and https://www.drupal.org/node/2406075
    }
    // The Origin is invalid, so we deny the request.
    // Clean the POST data first, as drupal_access_denied() may render a page
    // with forms which check for their submissions.
    $args = [
      '@ip' => $this->request->getClientIp(),
      '@origin' => $origin,
    ];

    $message = 'Possible CSRF attack was blocked. IP address: @ip, Origin: @origin.';
    $this->logger->warning($message, $args);

    $event->setResponse(new Response($this->t('Access denied'), Response::HTTP_FORBIDDEN));
  }

  /**
   * Sends Content Security Policy HTTP headers.
   *
   * Header specifies Content Security Policy (CSP) for a website,
   * which is used to allow/block content from selected sources.
   *
   * Based on specification available at http://www.w3.org/TR/CSP/
   */
  public function seckitCsp() {
    // Get default/set options.
    $csp_vendor_prefix_x = $this->config->get('seckit_xss.csp.vendor-prefix.x');
    $csp_vendor_prefix_webkit = $this->config->get('seckit_xss.csp.vendor-prefix.webkit');
    $csp_report_only = $this->config->get('seckit_xss.csp.report-only');
    $csp_default_src = $this->config->get('seckit_xss.csp.default-src');
    $csp_script_src = $this->config->get('seckit_xss.csp.script-src');
    $csp_object_src = $this->config->get('seckit_xss.csp.object-src');
    $csp_img_src = $this->config->get('seckit_xss.csp.img-src');
    $csp_media_src = $this->config->get('seckit_xss.csp.media-src');
    $csp_style_src = $this->config->get('seckit_xss.csp.style-src');
    $csp_frame_src = $this->config->get('seckit_xss.csp.frame-src');
    $csp_frame_ancestors = $this->config->get('seckit_xss.csp.frame-ancestors');
    $csp_child_src = $this->config->get('seckit_xss.csp.child-src');
    $csp_font_src = $this->config->get('seckit_xss.csp.font-src');
    $csp_connect_src = $this->config->get('seckit_xss.csp.connect-src');
    $csp_report_uri = $this->config->get('seckit_xss.csp.report-uri');
    $csp_upgrade_req = $this->config->get('seckit_xss.csp.upgrade-req');
    // $csp_policy_uri = $this->config->get('seckit_xss.csp.policy-uri');
    // Prepare directives.
    $directives = [];

    // If policy-uri is declared, no other directives are permitted.
    /* if ($csp_report_only) {
    $directives = "policy-uri " . base_path() . $csp_report_only;
    } */
    // Otherwise prepare directives.
    // else {.
    if ($csp_default_src) {
      $directives[] = "default-src $csp_default_src";
    }
    if ($csp_script_src) {
      $directives[] = "script-src $csp_script_src";
    }
    if ($csp_object_src) {
      $directives[] = "object-src $csp_object_src";
    }
    if ($csp_style_src) {
      $directives[] = "style-src $csp_style_src";
    }
    if ($csp_img_src) {
      $directives[] = "img-src $csp_img_src";
    }
    if ($csp_media_src) {
      $directives[] = "media-src $csp_media_src";
    }
    if ($csp_frame_src) {
      $directives[] = "frame-src $csp_frame_src";
    }
    if ($csp_frame_ancestors) {
      $directives[] = "frame-ancestors $csp_frame_ancestors";
    }
    if ($csp_child_src) {
      $directives[] = "child-src $csp_child_src";
    }
    if ($csp_font_src) {
      $directives[] = "font-src $csp_font_src";
    }
    if ($csp_connect_src) {
      $directives[] = "connect-src $csp_connect_src";
    }
    if ($csp_report_uri) {
      $base_path = '';
      if (!UrlHelper::isExternal($csp_report_uri)) {
        // Strip leading slashes from internal paths to prevent them becoming
        // external URLs without protocol. /report-csp-violation should not be
        // turned into //report-csp-violation.
        $csp_report_uri = ltrim($csp_report_uri, '/');
        $base_path = base_path();
      }
      $directives[] = "report-uri " . $base_path . $csp_report_uri;
    }
    if ($csp_upgrade_req) {
      $directives[] = 'upgrade-insecure-requests';
    }
    // Merge directives.
    $directives = implode('; ', $directives);
    // }
    // send HTTP response header if directives were prepared.
    if ($directives) {
      if ($csp_report_only) {
        // Use report-only mode.
        $this->response->headers->set('Content-Security-Policy-Report-Only', $directives);
        if ($csp_vendor_prefix_x) {
          $this->response->headers->set('X-Content-Security-Policy-Report-Only', $directives);
        }
        if ($csp_vendor_prefix_webkit) {
          $this->response->headers->set('X-WebKit-CSP-Report-Only', $directives);
        }
      }
      else {
        $this->response->headers->set('Content-Security-Policy', $directives);
        if ($csp_vendor_prefix_x) {
          $this->response->headers->set('X-Content-Security-Policy', $directives);
        }
        if ($csp_vendor_prefix_webkit) {
          $this->response->headers->set('X-WebKit-CSP', $directives);
        }
      }
    }
  }

  /**
   * Sends X-XSS-Protection HTTP header.
   *
   * X-XSS-Protection controls IE8/Safari/Chrome internal XSS filter.
   */
  public function seckitXxss($setting) {
    switch ($setting) {
      case SeckitInterface::X_XSS_0:
        // Set X-XSS-Protection header to 0.
        $this->response->headers->set('X-XSS-Protection', '0');
        break;

      case SeckitInterface::X_XSS_1:
        // Set X-XSS-Protection header to 1.
        $this->response->headers->set('X-XSS-Protection', '1');
        break;

      case SeckitInterface::X_XSS_1_BLOCK:
        // Set X-XSS-Protection header to 1; mode=block.
        $this->response->headers->set('X-XSS-Protection', '1; mode=block');
        break;

      case SeckitInterface::X_XSS_DISABLE:
        // Do nothing.
      default:
        break;
    }
  }

  /**
   * Sends X-Frame-Options HTTP header.
   *
   * X-Frame-Options controls should browser show frames or not.
   * More information can be found at initial article about it at
   * http://blogs.msdn.com/ie/archive/2009/01/27/ie8-security-part-vii-clickjacking-defenses.aspx.
   *
   * Implementation of X-Frame-Options is based on specification draft available
   * at http://tools.ietf.org/html/draft-ietf-websec-x-frame-options-01.
   */
  public function seckitXframe($setting, $event) {
    switch ($setting) {
      case SeckitInterface::X_FRAME_SAMEORIGIN:
        // Set X-Frame-Options to SAMEORIGIN.
        $this->response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        break;

      case SeckitInterface::X_FRAME_DENY:
        // Set X-Frame-Options to DENY.
        $this->response->headers->set('X-Frame-Options', 'DENY');
        break;

      case SeckitInterface::X_FRAME_ALLOW_FROM:
        // If this request's Origin is allowed, we specify that value.
        // If the origin is not allowed, we can use any other value to prevent
        // the client from framing the page.
        $allowed_from = $this->config->get('seckit_clickjacking.x_frame_allow_from');
        $values = explode("\n", $allowed_from);
        $allowed = array_values(array_filter(array_map('trim', $values)));
        $origin = $event->getRequest()->headers->get('Origin');
        if (!in_array($origin, $allowed, TRUE)) {
          $origin = array_pop($allowed);
        }
        $this->response->headers->set('X-Frame-Options', "ALLOW-FROM $origin");
        break;

      case SeckitInterface::X_FRAME_DISABLE:
        // Make sure Drupal core does not set the header either.
        // See Drupal\Core\EventSubscriber\FinishResponseSubscriber.
        $this->response->headers->remove('X-Frame-Options');
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest', 100];
    $events[KernelEvents::RESPONSE][] = ['onKernelResponse'];
    return $events;
  }

  /**
   * Enables JavaScript + CSS + Noscript Clickjacking defense.
   *
   * Closes inline JavaScript and allows loading of any inline HTML elements.
   * After, it starts new inline JavaScript to avoid breaking syntax.
   * We need it, because Drupal API doesn't allow to init HTML elements in
   * desired sequence.
   */
  public function seckitJsCssNoscript() {
    // @todo Consider better solution?
    $content = $this->response->getContent();
    $head_close_position = strpos($content, '</head>');
    $seckit_code_position = strpos($content, 'seckit-clickjacking-no-body');
    if ($head_close_position && $seckit_code_position === FALSE) {
      $content = substr_replace($content, $this->seckitGetJsCssNoscriptCode(), $head_close_position, 0);
      $this->response->setContent($content);
    }
  }

  /**
   * Gets JavaScript and CSS code.
   *
   * @return string
   *   Return the js and css code.
   */
  public function seckitGetJsCssNoscriptCode($noscript_message = NULL) {
    // Allows noscript automated testing.
    $noscript_message = $noscript_message ?
        $noscript_message :
        $this->config->get('seckit_clickjacking.noscript_message');

    $message = Xss::filter($noscript_message);
    $path = base_path() . $this->moduleExtensionList->getPath('seckit');
    return <<< EOT
        <script type="text/javascript" src="$path/js/seckit.document_write.js"></script>
        <link type="text/css" rel="stylesheet" id="seckit-clickjacking-no-body" media="all" href="$path/css/seckit.no_body.css" />
        <!-- stop SecKit protection -->
        <noscript>
        <link type="text/css" rel="stylesheet" id="seckit-clickjacking-noscript-tag" media="all" href="$path/css/seckit.noscript_tag.css" />
        <div id="seckit-noscript-tag">
          $message
        </div>
        </noscript>
EOT;
  }

  /**
   * Sends HTTP Strict-Transport-Security header (HSTS).
   *
   * The HSTS header prevents certain eavesdropping and MITM attacks like
   * SSLStrip. It forces the user-agent to send requests in HTTPS-only mode.
   * e.g.: http:// links are treated as https://
   *
   * Implementation of HSTS is based on the specification draft available at
   * http://tools.ietf.org/html/draft-hodges-strict-transport-sec-02
   */
  public function seckitHsts() {
    // Prepare HSTS header value.
    $header[] = sprintf("max-age=%d", $this->config->get('seckit_ssl.hsts_max_age'));
    if ($this->config->get('seckit_ssl.hsts_subdomains')) {
      $header[] = 'includeSubDomains';
    }

    if ($this->config->get('seckit_ssl.hsts_preload')) {
      $header[] = 'preload';
    }

    $header = implode('; ', $header);

    $this->response->headers->set('Strict-Transport-Security', $header);
  }

  /**
   * Sends From-Origin HTTP response header.
   *
   * Implementation is based on specification draft
   * available at http://www.w3.org/TR/from-origin.
   */
  public function seckitFromOrigin() {
    $value = $this->config->get('seckit_various.from_origin_destination');
    $this->response->headers->set('From-Origin', $value);
  }

  /**
   * Sends Referrer-Policy HTTP response header.
   *
   * Implementation is based on specification draft
   * available at https://www.w3.org/TR/referrer-policy.
   */
  public function seckitReferrerPolicy() {
    $value = $this->config->get('seckit_various.referrer_policy_policy');
    $this->response->headers->set('Referrer-Policy', $value);
  }

  /**
   * Sends Expect-CT HTTP response header.
   *
   * Implementation is based on specification draft available at
   * https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Expect-CT.
   */
  public function seckitExpectCt() {
    $header[] = sprintf("max-age=%d", $this->config->get('seckit_ct.max_age'));
    if ($this->config->get('seckit_ct.enforce')) {
      $header[] = 'enforce';
    }

    if ($this->config->get('seckit_ct.report_uri')) {
      $header[] = 'report-uri="' . $this->config->get('seckit_ct.report_uri') . '"';
    }

    $header = implode(', ', $header);
    $this->response->headers->set('Expect-CT', $header);
  }

  /**
   * Sends Feature-Policy HTTP response header.
   *
   * Implementation is based on specification draft available
   * at https://developers.google.com/web/updates/2018/06/feature-policy.
   */
  public function seckitFeaturePolicy() {
    $header[] = $this->config->get('seckit_fp.feature_policy_policy');

    $this->response->headers->set('Feature-Policy', $header);
  }

}
