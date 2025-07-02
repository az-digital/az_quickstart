<?php

namespace Drupal\seckit\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\seckit\SeckitInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a form to collect security check configuration.
 */
class SecKitSettingsForm extends ConfigFormBase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);

    $instance->renderer = $container->get('renderer');
    $instance->pathValidator = $container->get('path.validator');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'seckit_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['seckit.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'seckit/listener';
    $form['#attached']['library'][] = 'seckit/admin-styles';

    $config = $this->config('seckit.settings');

    // Main description.
    $args = [
      ':browserscope' => 'http://www.browserscope.org/?category=security',
      '@browserscope' => 'Browserscope',
    ];
    $form['seckit_description'] = [
      '#markup' => $this->t('This module provides your website with various options to mitigate risks of common web application vulnerabilities like Cross-site Scripting, Cross-site Request Forgery and Clickjacking. It also has some options to improve your SSL/TLS security and fixes Drupal 6 core Upload module issue leading to an easy exploitation of an old Internet Explorer MIME sniffer HTML injection vulnerability. Note that some security features are not supported by all browsers. You may find this out at <a href=":browserscope">@browserscope</a>.', $args),
    ];

    // Main fieldset for XSS.
    $form['seckit_xss'] = [
      '#type' => 'details',
      '#title' => $this->t('Cross-site Scripting'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => TRUE,
      '#description' => $this->t('Configure levels and various techniques of protection from cross-site scripting attacks'),
    ];

    // Fieldset for Content Security Policy (CSP).
    $args = [
      ':wiki' => 'https://wiki.mozilla.org/Security/CSP',
      '@wiki' => 'Mozilla Wiki',
      ':caniuse' => 'https://caniuse.com/#feat=contentsecuritypolicy',
      '@caniuse' => 'Can I use',
    ];

    $form['seckit_xss']['csp'] = [
      '#type' => 'details',
      '#title' => $this->t('Content Security Policy'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => !empty($config->get('seckit_xss.csp.checkbox')),
      '#description' => $this->t('Content Security Policy is a policy framework that allows to specify trustworthy sources of content and to restrict its capabilities. You may read more about it at <a href=":wiki">@wiki</a>.', $args),
    ];
    // CSP enable/disable.
    $form['seckit_xss']['csp']['checkbox'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('seckit_xss.csp.checkbox'),
      '#title' => $this->t('Send HTTP response header'),
      '#return_value' => 1,
      '#description' => $this->t('Send Content-Security-Policy HTTP response header with the list of Content Security Policy directives.'),
    ];
    $form['seckit_xss']['csp']['vendor-prefix'] = [
      '#type' => 'details',
      '#title' => $this->t('Vendor Prefixed CSP headers'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => !empty($config->get('seckit_xss.csp.vendor-prefix.x')) || !empty($config->get('seckit_xss.csp.vendor-prefix.webkit')),
      '#description' => $this->t('Support for legacy vendor-prefixed CSP headers. Details at <a href=":caniuse">@caniuse</a>.', $args),
    ];
    $form['seckit_xss']['csp']['vendor-prefix']['x'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('seckit_xss.csp.vendor-prefix.x'),
      '#title' => $this->t('Send X-Content-Security-Policy HTTP response header'),
      '#return_value' => 1,
      '#description' => $this->t('Send vendor-prefixed X-Content-Security-Policy HTTP response headers with the list of Content Security Policy directives.'),
    ];
    $form['seckit_xss']['csp']['vendor-prefix']['webkit'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('seckit_xss.csp.vendor-prefix.webkit'),
      '#title' => $this->t('Send X-WebKit-CSP HTTP response header'),
      '#return_value' => 1,
      '#description' => $this->t('Send vendor-prefixed X-WebKit-CSP HTTP response headers with the list of Content Security Policy directives.'),
    ];
    $form['seckit_xss']['csp']['upgrade-req'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('seckit_xss.csp.upgrade-req'),
      '#title' => $this->t('Enable Upgrade Insecure Requests'),
      '#return_value' => 1,
      '#description' => $this->t('Upgrade Insecure Requests (upgrade-insecure-requests) instructs user agents to rewrite URL schemes, changing HTTP to HTTPS. This directive is used to protect your visitors from insecure content or for websites with large numbers of old URLs that need to be rewritten.'),
    ];
    // CSP report-only mode.
    $form['seckit_xss']['csp']['report-only'] = [
      '#type' => 'checkbox',
      '#default_value' => $config->get('seckit_xss.csp.report-only'),
      '#title' => $this->t('Report Only'),
      '#return_value' => 1,
      '#description' => $this->t('Use Content Security Policy in report-only mode. In this case, violations of policies will only be reported, not blocked. Use this while configuring policies. Reports are logged.'),
    ];
    // CSP description.
    $keywords = [
      "'none' - block content from any source",
      "'self' - allow content only from your domain",
      "'unsafe-inline' - allow specific inline content (note, that it is supported by a subset of directives)",
      "'unsafe-eval' - allow a set of string-to-code API which is restricted by default (supported by script-src directive)",
    ];

    $wildcards = [
      '* - load content from any source',
      '*.example.com - load content from example.com and all its subdomains',
      'example.com:* - load content from example.com via any port. Otherwise, it will use your website default port',
    ];

    $args = [
      '@keywords' => $this->getItemsList($keywords),
      '@wildcards' => $this->getItemsList($wildcards),
      ':spec' => 'http://www.w3.org/TR/CSP/',
      '@spec' => 'specification page',
    ];

    $description = '<strong>' . $this->t('Directives') . '</strong><br />';
    $description .= $this->t('Set up security policy for different types of content. Don\'t use www prefix. Keywords (which must be surrounded by single quotes) are: @keywords Wildcards (*) are allowed: @wildcards More information is available at <a href=":spec">@spec</a>.', $args);
    $form['seckit_xss']['csp']['description'] = [
      '#markup' => $description,
    ];
    // CSP default-src directive.
    $form['seckit_xss']['csp']['default-src'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#default_value' => $config->get('seckit_xss.csp.default-src'),
      '#title' => 'default-src',
      '#description' => $this->t("Specify security policy for all types of content, which are not specified further (frame-ancestors excepted). Default is 'self'."),
    ];
    // CSP script-src directive.
    $form['seckit_xss']['csp']['script-src'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#default_value' => $config->get('seckit_xss.csp.script-src'),
      '#title' => 'script-src',
      '#description' => $this->t('Specify trustworthy sources for &lt;script&gt; elements.'),
    ];
    // CSP object-src directive.
    $form['seckit_xss']['csp']['object-src'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#default_value' => $config->get('seckit_xss.csp.object-src'),
      '#title' => 'object-src',
      '#description' => $this->t('Specify trustworthy sources for &lt;object&gt;, &lt;embed&gt; and &lt;applet&gt; elements.'),
    ];
    // CSP style-src directive.
    $form['seckit_xss']['csp']['style-src'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#default_value' => $config->get('seckit_xss.csp.style-src'),
      '#title' => 'style-src',
      '#description' => $this->t('Specify trustworthy sources for stylesheets. Note, that inline stylesheets and style attributes of HTML elements are allowed.'),
    ];
    // CSP img-src directive.
    $form['seckit_xss']['csp']['img-src'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#default_value' => $config->get('seckit_xss.csp.img-src'),
      '#title' => 'img-src',
      '#description' => $this->t('Specify trustworthy sources for &lt;img&gt; elements.'),
    ];
    // CSP media-src directive.
    $form['seckit_xss']['csp']['media-src'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#default_value' => $config->get('seckit_xss.csp.media-src'),
      '#title' => 'media-src',
      '#description' => $this->t('Specify trustworthy sources for &lt;audio&gt; and &lt;video&gt; elements.'),
    ];
    // CSP frame-src directive.
    $form['seckit_xss']['csp']['frame-src'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#default_value' => $config->get('seckit_xss.csp.frame-src'),
      '#title' => 'frame-src',
      '#description' => $this->t('Specify trustworthy sources for &lt;iframe&gt; and &lt;frame&gt; elements. This directive is deprecated and will be replaced by child-src. It is recommended to use the both the frame-src and child-src directives until all browsers you support recognize the child-src directive.'),
    ];
    // CSP frame-ancestors directive.
    $form['seckit_xss']['csp']['frame-ancestors'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#default_value' => $config->get('seckit_xss.csp.frame-ancestors'),
      '#title' => 'frame-ancestors',
      '#description' => $this->t("Specify trustworthy hosts which are allowed to embed this site's resources via &lt;iframe&gt;, &lt;frame&gt;, &lt;object&gt;, &lt;embed&gt; and &lt;applet&gt; elements."),
    ];
    // CSP child-src directive.
    $form['seckit_xss']['csp']['child-src'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#default_value' => $config->get('seckit_xss.csp.child-src'),
      '#title' => 'child-src',
      '#description' => $this->t('Specify trustworthy sources for &lt;iframe&gt; and &lt;frame&gt; elements as well as for loading Workers.'),
    ];
    // CSP font-src directive.
    $form['seckit_xss']['csp']['font-src'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#default_value' => $config->get('seckit_xss.csp.font-src'),
      '#title' => 'font-src',
      '#description' => $this->t('Specify trustworthy sources for @font-src CSS loads.'),
    ];
    // CSP connect-src directive.
    $form['seckit_xss']['csp']['connect-src'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#default_value' => $config->get('seckit_xss.csp.connect-src'),
      '#title' => 'connect-src',
      '#description' => $this->t('Specify trustworthy sources for XMLHttpRequest, WebSocket and EventSource connections.'),
    ];
    // CSP report-uri directive.
    $form['seckit_xss']['csp']['report-uri'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#default_value' => $config->get('seckit_xss.csp.report-uri'),
      '#title' => 'report-uri',
      '#description' => $this->t('Specify a URL (can be relative to the Drupal root, or absolute) to which user-agents will report CSP violations. Use the default value, unless you have set up an alternative handler for these reports. Note that if you specify a custom relative path, it should typically be accessible by all users (including anonymous). Defaults to <code>@report-url</code> which logs the report data.', ['@report-url' => SeckitInterface::CSP_REPORT_URL]),
    ];
    // CSP policy-uri directive.
    $form['seckit_xss']['csp']['policy-uri'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#default_value' => $config->get('seckit_xss.csp.policy-uri'),
      '#title' => 'policy-uri',
      '#description' => $this->t("Specify a URL (relative to the Drupal root) for a file containing the (entire) policy. <strong>All other directives will be omitted</strong> by Security Kit, as <code>policy-uri</code> may only be defined in the <em>absence</em> of other policy definitions in the <code>X-Content-Security-Policy</code> HTTP header. The MIME type for this URI <strong>must</strong> be <code>text/x-content-security-policy</code>, otherwise user-agents will enforce the policy <code>allow 'none'</code>  instead."),
    ];

    // Fieldset for X-XSS-Protection.
    $form['seckit_xss']['x_xss'] = [
      '#type' => 'details',
      '#title' => $this->t('X-XSS-Protection header'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => $config->get('seckit_xss.x_xss.select') != SeckitInterface::X_XSS_DISABLE,
      '#description' => $this->t('X-XSS-Protection HTTP response header controls Microsoft Internet Explorer, Google Chrome and Apple Safari internal XSS filters.'),
    ];
    // Options for X-XSS-Protection.
    $x_xss_protection_options = [
      SeckitInterface::X_XSS_DISABLE => $this->t('Disabled'),
      SeckitInterface::X_XSS_0 => '0',
      SeckitInterface::X_XSS_1 => '1',
      SeckitInterface::X_XSS_1_BLOCK => '1; mode=block',
    ];
    // Configure X-XSS-Protection.
    $args = [
      ':link' => 'http://hackademix.net/2009/11/21/ies-xss-filter-creates-xss-vulnerabilities',
      '@link' => 'IE\'s XSS filter security flaws in past',
    ];
    $items = [
      ['#markup' => $this->t('Disabled - XSS filter will work in default mode. Enabled by default')],
      ['#markup' => $this->t('0 - XSS filter will be disabled for a website. It may be useful because of <a href=":link">@link</a>', $args)],
      ['#markup' => $this->t('1 - XSS filter will be left enabled, and will modify dangerous content')],
      ['#markup' => $this->t('1; mode=block - XSS filter will be left enabled, but it will block entire page instead of modifying dangerous content')],
    ];

    $args = [
      '@values' => $this->getItemsList($items),
    ];

    $form['seckit_xss']['x_xss']['select'] = [
      '#type' => 'select',
      '#title' => $this->t('Configure'),
      '#options' => $x_xss_protection_options,
      '#default_value' => $config->get('seckit_xss.x_xss.select'),
      '#description' => $this->t('@values', $args),
    ];

    // Main fieldset for CSRF.
    $form['seckit_csrf'] = [
      '#type' => 'details',
      '#title' => $this->t('Cross-site Request Forgery'),
      '#tree' => TRUE,
      '#open' => !empty($config->get('seckit_csrf.origin')),
      '#collapsible' => TRUE,
      '#description' => $this->t('Configure levels and various techniques of protection from cross-site request forgery attacks'),
    ];

    // Enable/disable Origin.
    $form['seckit_csrf']['origin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('HTTP Origin'),
      '#default_value' => $config->get('seckit_csrf.origin'),
      '#description' => $this->t('Check Origin HTTP request header.'),
    ];
    // Origin whitelist.
    $form['seckit_csrf']['origin_whitelist'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#title' => $this->t('Allow requests from'),
      '#default_value' => $config->get('seckit_csrf.origin_whitelist'),
      '#size' => 90,
      '#description' => $this->t('Comma separated list of trustworthy sources. Do not enter your website URL - it is automatically added. Syntax of the source is: [protocol] :// [host] : [port] . E.g, http://example.com, https://example.com, https://www.example.com, http://www.example.com:8080'),
    ];

    // Main fieldset for Clickjacking.
    $form['seckit_clickjacking'] = [
      '#type' => 'details',
      '#title' => $this->t('Clickjacking'),
      '#collapsible' => TRUE,
      '#tree' => FALSE,
      '#open' => TRUE,
      '#description' => $this->t('Configure levels and various techniques of protection from Clickjacking/UI Redressing attacks'),
    ];

    $form['seckit_clickjacking']['x_frame_options'] = [
      '#type' => 'details',
      '#title' => $this->t('X-Frame-Options header'),
      '#collapsible' => TRUE,
      '#collapsed' => ($config->get('seckit_clickjacking.x_frame') != SeckitInterface::X_FRAME_DISABLE),
      '#tree' => FALSE,
      '#description' => $this->t('Configure the X-Frame-Options HTTP header'),
    ];

    // Options for X-Frame-Options.
    $x_frame_options = [
      SeckitInterface::X_FRAME_DISABLE => $this->t('Disabled'),
      SeckitInterface::X_FRAME_SAMEORIGIN => 'SAMEORIGIN',
      SeckitInterface::X_FRAME_DENY => 'DENY',
      SeckitInterface::X_FRAME_ALLOW_FROM => 'ALLOW-FROM',
    ];
    // Configure X-Frame-Options.
    $items = [
      'Disabled - turn off X-Frame-Options',
      'SAMEORIGIN - browser allows all the attempts of framing website within its domain. Enabled by default',
      'DENY - browser rejects any attempt of framing website',
      'ALLOW-FROM - browser allows framing website only from specified source',
    ];

    $args = [
      '@values' => $this->getItemsList($items),
      ':msdn' => 'http://blogs.msdn.com/b/ie/archive/2009/01/27/ie8-security-part-vii-clickjacking-defenses.aspx',
      '@msdn' => 'MSDN article',
      ':spec' => 'http://tools.ietf.org/html/draft-ietf-websec-x-frame-options-01',
      '@spec' => 'specification',
    ];
    $form['seckit_clickjacking']['x_frame_options']['x_frame'] = [
      '#type' => 'select',
      '#title' => $this->t('X-Frame-Options'),
      '#options' => $x_frame_options,
      '#default_value' => $config->get('seckit_clickjacking.x_frame'),
      '#description' => $this->t('X-Frame-Options HTTP response header controls browser\'s policy of frame rendering. Possible values: @values You may read more about it at <a href=":msdn">@msdn</a> or <a href=":spec">@spec</a>.', $args),
      // Non-tree (we skip a parent).
      '#parents' => [
        'seckit_clickjacking',
        'x_frame',
      ],
    ];

    // Origin value for "ALLOW-FROM" option.
    $form['seckit_clickjacking']['x_frame_options']['x_frame_allow_from'] = [
      '#type' => 'textarea',
      '#title' => $this->t('ALLOW-FROM'),
      '#default_value' => $config->get('seckit_clickjacking.x_frame_allow_from'),
      '#description' => $this->t('Origin URIs (as specified by RFC 6454) for the "X-Frame-Options: ALLOW-FROM" value. One per line. If the request does not contain a matching Origin header, the last value will be used. Example, http://domain.com'),
      '#states' => [
        'required' => [
          'select[name="seckit_clickjacking[x_frame]"]' => ['value' => SeckitInterface::X_FRAME_ALLOW_FROM],
        ],
      ],
      // Non-tree (we skip a parent).
      '#parents' => [
        'seckit_clickjacking',
        'x_frame_allow_from',
      ],
    ];

    $args = [
      ':noscript' => 'https://noscript.net/',
      '@noscript' => 'NoScript',
    ];
    // Fieldset for JavaScript settings. non-#tree.
    $form['seckit_clickjacking']['javascript'] = [
      '#type' => 'details',
      '#title' => $this->t('JavaScript-based protection'),
      '#collapsible' => TRUE,
      '#collapsed' => !empty($config->get('seckit_clickjacking.js_css_noscript')),
      '#tree' => FALSE,
      '#description' => $this->t('Warning: With this enabled, the site <em>will not work at all</em> for users who have JavaScript disabled (e.g. users running the popular <a href=":noscript">@noscript</a> browser extension, if they haven\'t whitelisted your site).', $args),
    ];

    // Enable/disable JS + CSS + Noscript protection.
    $args = [
      ':eduardovela' => 'http://sirdarckcat.blogspot.com/',
      '@eduardovela' => 'Eduardo Vela',
      '%js' => $this->t('seckit.document_write.js'),
      '%write' => $this->t('document.write()'),
      '%stop' => $this->t('stop SecKit protection'),
      '%css' => $this->t('seckit.no_body.css'),
      '%display' => $this->t('display: none'),
    ];
    $form['seckit_clickjacking']['javascript']['js_css_noscript'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable JavaScript + CSS + Noscript protection'),
      '#return_value' => 1,
      '#default_value' => $config->get('seckit_clickjacking.js_css_noscript'),
      '#description' => $this->t('Enable protection via JavaScript, CSS and &lt;noscript&gt; tag. This is the most efficient Clickjacking prevention technique. If website is not being framed, %js starts commenting with <em>document.write()</em> and stops when the comment %stop is reached. Thus %css, which hides the page body, is ignored. If particularly this JavaScript file is being blocked (with XSS filter of Internet Explorer 8 or Safari), %css applies <em>display: none</em> to <em>body</em>, hiding it. If JavaScript is disabled within browser, it shows a special message. Credits for this trick go to <a href=":eduardovela">@eduardovela</a>.', $args),
      '#parents' => [
        'seckit_clickjacking',
        'js_css_noscript',
      ],
    ];

    // Custom text for "disabled JavaScript" message.
    $form['seckit_clickjacking']['javascript']['noscript_message'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#title' => $this->t('Custom text for disabled JavaScript message'),
      '#default_value' => $config->get('seckit_clickjacking.noscript_message'),
      '#description' => $this->t('This message will be shown to user when JavaScript is disabled or unsupported in his browser. Default is "Sorry, you need to enable JavaScript to visit this website."'),
      '#states' => [
        'required' => [
          'input[name="seckit_clickjacking[js_css_noscript]"]' => ['checked' => TRUE],
        ],
      ],
      '#parents' => [
        'seckit_clickjacking',
        'noscript_message',
      ],
    ];

    // Main fieldset for SSL/TLS.
    $form['seckit_ssl'] = [
      '#type' => 'details',
      '#title' => $this->t('SSL/TLS'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => !empty($config->get('seckit_ssl.hsts')),
      '#description' => $this->t('Configure various techniques to improve security of SSL/TLS'),
    ];

    // Enable/disable HTTP Strict Transport Security (HSTS).
    $args = [
      ':wiki' => 'http://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security',
      '@wiki' => 'Wikipedia',
    ];

    $form['seckit_ssl']['hsts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('HTTP Strict Transport Security'),
      '#description' => $this->t('Enable Strict-Transport-Security HTTP response header. HTTP Strict Transport Security (HSTS) header is proposed to prevent eavesdropping and man-in-the-middle attacks like SSLStrip, when a single non-HTTPS request is enough for credential theft or hijacking. It forces browser to connect to the server in HTTPS-mode only and automatically convert HTTP links into secure before sending request. <a href=":wiki">@wiki</a> has more information about HSTS', $args),
      '#default_value' => $config->get('seckit_ssl.hsts'),
    ];
    // HSTS max-age directive.
    $form['seckit_ssl']['hsts_max_age'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max-Age'),
      '#description' => $this->t('Specify Max-Age value in seconds. It sets period when user-agent should remember receipt of this header field from this server. Default is 1000.'),
      '#default_value' => $config->get('seckit_ssl.hsts_max_age'),
      '#states' => [
        'required' => [
          'input[name="seckit_ssl[hsts]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    // HSTS includeSubDomains directive.
    $form['seckit_ssl']['hsts_subdomains'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include Subdomains'),
      '#description' => $this->t('Force HTTP Strict Transport Security for all subdomains. If enabled, HSTS policy will be applied for all subdomains, otherwise only for the main domain.'),
      '#default_value' => $config->get('seckit_ssl.hsts_subdomains'),
    ];

    // HSTS preload directive.
    $args = [
      ':hsts_preload_list' => 'https://hstspreload.appspot.com/',
      '@hsts_preload_list' => 'HSTS Preload list',
    ];
    $form['seckit_ssl']['hsts_preload'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Preload'),
      '#description' => $this->t('If you intend to submit your domain to the <a href=":hsts_preload_list">@hsts_preload_list</a>, you will need to enable the preload flag as confirmation. Don\'t submit your domain unless you\'re sure that you can support HTTPS for the long term, as this action cannot be undone.', $args),
      '#return_value' => 1,
      '#default_value' => $config->get('seckit_ssl.hsts_preload'),
    ];

    $args = [
      ':expect-ct_docs' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Expect-CT',
      '@expect-ct_docs' => "Mozilla's developer documentation",
    ];

    // Main fieldset for Expect-CT.
    $form['seckit_ct'] = [
      '#type' => 'details',
      '#title' => $this->t('Expect-CT'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => !empty($config->get('seckit_ct.expect_ct')),
      '#description' => $this->t('Configure the Expect-CT header which allows sites to opt in to reporting and/or enforcement of Certificate Transparency requirements. See <a href=":expect-ct_docs">@expect-ct_docs</a>.', $args),
    ];

    // Enable the Expect-CT settings.
    $form['seckit_ct']['expect_ct'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expect-CT'),
      '#default_value' => $config->get('seckit_ct.expect_ct'),
      '#description' => $this->t('Enable the Expect-CT header.'),
    ];

    $form['seckit_ct']['max_age'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max-Age'),
      '#description' => $this->t('Specify Max-Age value in seconds.'),
      '#default_value' => $config->get('seckit_ct.max_age'),
      '#states' => [
        'required' => [
          'input[name="seckit_ct[expect_ct]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['seckit_ct']['report_uri'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#title' => $this->t('Report-uri'),
      '#default_value' => $config->get('seckit_ct.report_uri'),
      '#description' => $this->t('Specify the (absolute) URI to which the user agent should report Expect-CT failures.'),
    ];

    $form['seckit_ct']['enforce'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enforce'),
      '#default_value' => $config->get('seckit_ct.enforce'),
      '#description' => $this->t('Enforce the Certificate Transparency policy.'),
    ];

    $args = [
      ':feature-policy_docs' => 'https://developers.google.com/web/updates/2018/06/feature-policy',
      '@feature-policy_docs' => "Google's developer documentation",
    ];

    // Main fieldset for Feature-Policy.
    $form['seckit_fp'] = [
      '#type' => 'details',
      '#title' => $this->t('Feature policy'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => !empty($config->get('seckit_fp.feature_policy')),
      '#description' => $this->t('Allows configuration of the Feature-Policy header to selectively enable, disable, and modify the behavior of certain APIs and web features in the browser. See <a href=":feature-policy_docs">@feature-policy_docs</a>.', $args),
    ];

    $form['seckit_fp']['feature_policy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Feature policy'),
      '#default_value' => $config->get('seckit_fp.feature_policy'),
      '#description' => $this->t('Enable the Feature-Policy header.'),
    ];

    $form['seckit_fp']['feature_policy_policy'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#title' => $this->t('Policy'),
      '#default_value' => $config->get('seckit_fp.feature_policy_policy'),
      '#size' => 90,
      '#description' => $this->t('Specify the policy to be sent out with Feature-Policy headers.'),
    ];

    // Main fieldset for various.
    $form['seckit_various'] = [
      '#type' => 'details',
      '#title' => $this->t('Miscellaneous'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => !empty($config->get('seckit_various.from_origin')),
      '#description' => $this->t('Configure miscellaneous unsorted security enhancements'),
    ];

    // Enable/disable From-Origin.
    $args = [
      ':spec' => 'http://www.w3.org/TR/from-origin/',
      '@spec' => 'specification',
    ];

    $form['seckit_various']['from_origin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('From-Origin'),
      '#default_value' => $config->get('seckit_various.from_origin'),
      '#description' => $this->t('Enable From-Origin HTTP response header. This forces user-agent to retrieve embedded content from your site only to listed destination. More information is available at <a href=":spec">@spec</a> page.', $args),
    ];
    // From-Origin destination.
    $items = [
      'same - allow loading of content only from your site. Default value.',
      'serialized origin - address of trustworthy destination. For example, http://example.com, https://example.com, https://www.example.com, http://www.example.com:8080',
    ];
    $args = [
      '@items' => $this->getItemsList($items),
    ];

    $form['seckit_various']['from_origin_destination'] = [
      '#type' => 'textarea',
      '#attributes' => [
        'rows' => 1,
      ],
      '#title' => $this->t('Allow loading content to'),
      '#default_value' => $config->get('seckit_various.from_origin_destination'),
      '#description' => $this->t('Trustworthy destination. Possible variants are: @items', $args),
      '#states' => [
        'required' => [
          'input[name="seckit_various[from_origin]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Enable/disable Referrer-Policy.
    $args = [
      ':spec' => 'http://www.w3.org/TR/referrer-policy/',
      '@spec' => 'specification',
    ];

    $form['seckit_various']['referrer_policy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Referrer-Policy'),
      '#default_value' => $config->get('seckit_various.referrer_policy'),
      '#description' => $this->t('Enable Referrer-Policy HTTP response header. This affects the Referer HTTP header for outgoing requests and navigations. More information is available at the <a href=":spec">@spec</a> page.', $args),
    ];

    // Referrer-Policy policy selection.
    $items = [
      $this->t('no-referrer - No referrer information will be sent.'),
      $this->t('no-referrer-when-downgrade - No referrer information will be sent when navigating from HTTPS to HTTP.'),
      $this->t('same-origin - Referrer information is sent only with requests to the same origin.'),
      $this->t('origin - This will strip any path information from the referrer information.'),
      $this->t('strict-origin - Like no-referrer-when-downgrade, but sends only the origin.'),
      $this->t('origin-when-cross-origin - Sends full referrer information with same-origin requests, and origin only with cross-origin requests.'),
      $this->t('strict-origin-when-cross-origin - Sends full referrer information with same-origin requests, origin only with cross-origin requests, and no referrer information if downgrading from https to http.'),
      $this->t('unsafe-url - Sends full referrer information with all requests; as the name implies, this is not safe.'),
      $this->t('"" (empty string) - Default to no-referrer-when-downgrade unless a higher-level policy has been defined elsewhere.'),
    ];
    $args = [
      '@items' => $this->getItemsList($items),
    ];

    $referrer_policy_options = [
      'no-referrer' => 'no-referrer',
      'no-referrer-when-downgrade' => 'no-referrer-when-downgrade',
      'same-origin' => 'same-origin',
      'origin' => 'origin',
      'strict-origin' => 'strict-origin',
      'origin-when-cross-origin' => 'origin-when-cross-origin',
      'strict-origin-when-cross-origin' => 'strict-origin-when-cross-origin',
      'unsafe-url' => 'unsafe-url',
      '""' => $this->t('"" (empty string)'),
    ];
    $form['seckit_various']['referrer_policy_policy'] = [
      '#type' => 'select',
      '#title' => $this->t('Select policy'),
      '#default_value' => $config->get('seckit_various.referrer_policy_policy'),
      '#description' => $this->t('Policy options are: @items', $args),
      '#options' => $referrer_policy_options,
      '#states' => [
        'required' => [
          'input[name="seckit_various[referrer_policy]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Disable autocomplete on login and registration forms.
    $form['seckit_various']['disable_autocomplete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable autocomplete on login and registration forms'),
      '#default_value' => $config->get('seckit_various.disable_autocomplete'),
      '#description' => $this->t('Prevent the browser from populating login/registration form fields using its autocomplete functionality. This as populated fields may contain sensitive information, facilitating unauthorized access.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // If From-Origin is enabled, it should be explicitly set.
    $from_origin_enable = $form_state->getValue([
      'seckit_various',
      'from_origin',
    ]);
    $from_origin_destination = $form_state->getValue([
      'seckit_various',
      'from_origin_destination',
    ]);
    if ($from_origin_enable && !$from_origin_destination) {
      $form_state->setErrorByName('seckit_various][from_origin_destination', $this->t('You have to set up trustworthy destination for From-Origin HTTP response header. Default is same.'));
    }
    // If X-Frame-Options is set to ALLOW-FROM, it should be explicitly set.
    $x_frame_value = $form_state->getValue(['seckit_clickjacking', 'x_frame']);
    if ($x_frame_value == SeckitInterface::X_FRAME_ALLOW_FROM) {
      $x_frame_allow_from = $form_state->getValue([
        'seckit_clickjacking',
        'x_frame_allow_from',
      ]);
      if (!$this->seckitExplodeValue($x_frame_allow_from)) {
        $form_state->setErrorByName('seckit_clickjacking][x_frame_allow_from', $this->t('You must specify a trusted Origin for the ALLOW-FROM value of the X-Frame-Options HTTP response header.'));
      }
    }
    // If HTTP Strict Transport Security is enabled, max-age must be specified.
    // HSTS max-age should only contain digits.
    $hsts = $form_state->getValue(['seckit_ssl', 'hsts']);
    $hsts_max_age = $form_state->getValue(['seckit_ssl', 'hsts_max_age']);
    if ($hsts && !$hsts_max_age) {
      $form_state->setErrorByName('seckit_ssl][hsts_max_age', $this->t('You have to set up Max-Age value for HTTP Strict Transport Security. Default is 1000.'));
    }
    if (preg_match('/[^0-9]/', $hsts_max_age)) {
      $form_state->setErrorByName('seckit_ssl][hsts_max_age', $this->t('Only digits are allowed in HTTP Strict Transport Security Max-Age field.'));
    }
    // If JS + CSS + Noscript Clickjacking protection is enabled,
    // custom text for disabled JS must be specified.
    $js_css_noscript_enable = $form_state->getValue([
      'seckit_clickjacking',
      'js_css_noscript',
    ]);
    $noscript_message = $form_state->getValue([
      'seckit_clickjacking',
      'noscript_message',
    ]);
    if ($js_css_noscript_enable && !$noscript_message) {
      $form_state->setErrorByName('seckit_clickjacking][noscript_message', $this->t('You have to set up Custom text for disabled JavaScript message when JS + CSS + Noscript protection is enabled.'));
    }
    // Check the value of CSP report-uri seems valid.
    $report_uri = $form_state->getValue(['seckit_xss', 'csp', 'report-uri']);
    if (UrlHelper::isExternal($report_uri)) {
      // UrlHelper::isValid will reject URIs beginning with '//' (i.e. without a
      // scheme). So add a fake scheme just for validation.
      if (strpos($report_uri, '//') === 0) {
        $report_uri = 'https:' . $report_uri;
      }
      if (!UrlHelper::isValid($report_uri)) {
        $form_state->setErrorByName('seckit_xss][csp][report-uri', $this->t('The CSP report-uri seems absolute but does not seem to be a valid URI.'));
      }
    }
    else {
      // Check that the internal path seems valid.
      if (!(bool) $this->pathValidator->getUrlIfValidWithoutAccessCheck($report_uri)) {
        $form_state->setErrorByName('seckit_xss][csp][report-uri', $this->t('The CSP report-uri seems relative but does not seem to be a valid path.'));
      }
    }
    // Check for newlines in some textarea inputs where there should be none.
    $csp_textareas = [
      'default-src',
      'script-src',
      'object-src',
      'style-src',
      'img-src',
      'media-src',
      'frame-src',
      'frame-ancestors',
      'child-src',
      'font-src',
      'connect-src',
    ];
    foreach ($csp_textareas as $csp_textarea) {
      $value = $form_state->getValue(['seckit_xss', 'csp', $csp_textarea]);
      if ($value !== str_replace(["\r", "\n"], '', (string) $value)) {
        $form_state->setErrorByName('seckit_xss][csp][' . $csp_textarea, t('CSP directives cannot contain newlines.'));
      }
    }
    $value = $form_state->getValue(['seckit_csrf', 'origin_whitelist']);
    if ($value !== str_replace(["\r", "\n"], '', (string) $value)) {
      $form_state->setErrorByName('seckit_csrf][origin_whitelist', t('CSRF Origin Whitelist cannot contain newlines.'));
    }
    $value = $form_state->getValue(['seckit_fp', 'feature_policy_policy']);
    if ($value !== str_replace(["\r", "\n"], '', (string) $value)) {
      $form_state->setErrorByName('seckit_fp][feature_policy_policy', t('Feature policy cannot contain newlines.'));
    }
    $value = $form_state->getValue(['seckit_various', 'from_origin_destination']);
    if ($value !== str_replace(["\r", "\n"], '', (string) $value)) {
      $form_state->setErrorByName('seckit_various][from_origin_destination', t('Allow loading content to cannot contain newlines.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $list = [];
    $this->buildAttributeList($list, $form_state->getValues());
    $config = $this->config('seckit.settings');
    foreach ($list as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Build a list from given items.
   */
  public function getItemsList($items) {
    $list = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    return $this->renderer->render($list);
  }

  /**
   * Build the configuration form value list.
   */
  protected function buildAttributeList(array &$list = [], array $rawAttributes = [], $currentName = '') {
    foreach ($rawAttributes as $key => $rawAttribute) {
      $name = $currentName ? $currentName . '.' . $key : $key;
      if (in_array(
        $name,
        ['op', 'form_id', 'form_token', 'form_build_id', 'submit']
        )) {
        continue;
      }
      if (is_array($rawAttribute)) {
        $this->buildAttributeList($list, $rawAttribute, $name);
      }
      else {
        $list[$name] = $rawAttribute;
      }
    }
  }

  /**
   * Converts a multi-line configuration option to an array.
   *
   * Sanitizes by trimming whitespace, and filtering empty options.
   */
  protected function seckitExplodeValue($string) {
    $values = explode("\n", $string);
    return array_values(array_filter(array_map('trim', $values)));
  }

}
