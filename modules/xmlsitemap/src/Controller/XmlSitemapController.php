<?php

namespace Drupal\xmlsitemap\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\State\StateInterface;
use Drupal\xmlsitemap\Entity\XmlSitemap;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class for Xml Sitemap Controller.
 *
 * Returns responses for xmlsitemap.sitemap_xml and xmlsitemap.sitemap_xsl
 * routes.
 */
class XmlSitemapController extends ControllerBase {

  /**
   * The state store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructs a new XmlSitemapController object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory, ModuleExtensionList $module_extension_list) {
    $this->state = $state;
    $this->configFactory = $config_factory;
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('config.factory'),
      $container->get('extension.list.module')
    );
  }

  /**
   * Provides the sitemap in XML format.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The sitemap in XML format or plain text if xmlsitemap_developer_mode flag
   *   is set.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the sitemap is not found or the sitemap file is not readable.
   */
  public function renderSitemapXml(Request $request) {
    $headers = [];

    if ($this->state->get('xmlsitemap_developer_mode')) {
      $headers['X-XmlSitemap-Current-Context'] = Json::encode(xmlsitemap_get_current_context());
      $headers['X-XmlSitemap'] = 'NOT FOUND';
    }

    $sitemap = XmlSitemap::loadByContext();
    if (!$sitemap) {
      $exception = new NotFoundHttpException();
      $exception->setHeaders($headers);
      throw $exception;
    }

    $chunk = xmlsitemap_get_current_chunk($sitemap, $request);
    $file = xmlsitemap_sitemap_get_file($sitemap, $chunk);

    // Provide debugging information via headers.
    if ($this->state->get('xmlsitemap_developer_mode')) {
      $headers['X-XmlSitemap'] = Json::encode($sitemap->toArray());
      $headers['X-XmlSitemap-Cache-File'] = $file;
      $headers['X-XmlSitemap-Cache-Hit'] = file_exists($file) ? 'HIT' : 'MISS';
    }

    return $this->getSitemapResponse($file, $request, $headers);
  }

  /**
   * Creates a response object that will output the sitemap file.
   *
   * @param string $file
   *   File uri.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param array $headers
   *   An array of response headers
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The sitemap response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the sitemap is not found or the sitemap file is not readable.
   */
  public function getSitemapResponse($file, Request $request, array $headers = []) {
    if (!is_file($file) || !is_readable($file)) {
      $exception = new NotFoundHttpException();
      $exception->setHeaders($headers);
      throw $exception;
    }

    $headers += [
      'Content-Type' => 'text/xml; charset=utf-8',
      'X-Robots-Tag' => 'noindex, follow',
    ];

    $lifetime = $this->configFactory->get('xmlsitemap.settings')->get('minimum_lifetime');

    $response = new BinaryFileResponse($file, 200, $headers);
    $response->setPrivate();
    $response->headers->addCacheControlDirective('must-revalidate');

    //if ($lifetime) {
    //  $response->headers->addCacheControlDirective('max-age', $lifetime);
    //}

    // Manually set the etag value instead of hashing the contents of the file.
    $last_modified = $response->getFile()->getMTime();
    $response->setEtag(md5($last_modified));

    // Set expiration using the minimum lifetime.
    $response->setExpires(new \DateTime('@' . ($last_modified + $lifetime)));

    // Because we do not want this page to be cached, we manually check the
    // modified headers.
    $response->isNotModified($request);

    return $response;
  }

  /**
   * Provides the sitemap in XSL format.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response object in XSL format.
   */
  public function renderSitemapXsl() {
    // Read the XSL content from the file.
    $module_path = $this->moduleExtensionList->getPath('xmlsitemap');
    $xsl_content = file_get_contents($module_path . '/xsl/xmlsitemap.xsl');

    // Make sure the strings in the XSL content are translated properly.
    $replacements = [
      'Sitemap file' => $this->t('Sitemap file'),
      'Generated by the <a href="https://www.drupal.org/project/xmlsitemap">Drupal XML Sitemap module</a>.' => $this->t('Generated by the <a href="@link-xmlsitemap">Drupal XML Sitemap module</a>.', ['@link-xmlsitemap' => 'https://www.drupal.org/project/xmlsitemap']),
      'Number of sitemaps in this index' => $this->t('Number of sitemaps in this index'),
      'Click on the table headers to change sorting.' => $this->t('Click on the table headers to change sorting.'),
      'Sitemap URL' => $this->t('Sitemap URL'),
      'Last modification date' => $this->t('Last modification date'),
      'Number of URLs in this sitemap' => $this->t('Number of URLs in this sitemap'),
      'URL location' => $this->t('URL location'),
      'Change frequency' => $this->t('Change frequency'),
      'Priority' => $this->t('Priority'),
      '[jquery]' => base_path() . 'core/assets/vendor/jquery/jquery.js',
      '[jquery-tablesort]' => base_path() . $module_path . '/xsl/jquery.tablesorter.min.js',
      '[xsl-js]' => base_path() . $module_path . '/xsl/xmlsitemap.xsl.js',
      '[xsl-css]' => base_path() . $module_path . '/xsl/xmlsitemap.xsl.css',
    ];
    $xsl_content = strtr($xsl_content, $replacements);

    // Output the XSL content.
    return new Response($xsl_content, 200, [
      'Content-Type' => 'application/xml; charset=utf-8',
      'X-Robots-Tag' => 'noindex, nofollow',
    ]);
  }

}
