<?php

namespace Drupal\cas;

use Drupal\cas\Service\CasHelper;
use Drupal\Core\Config\Config;

/**
 * Class CasServerConfig.
 *
 * A value object that represents server configuration for the CAS server.
 *
 * This object can is passed around in various CAS events, allowing modules
 * to modify details about the default CAS server config if needed.
 */
class CasServerConfig {

  /**
   * The CAS protocol version to use when interacting with the CAS server.
   *
   * @var string
   */
  protected $protocolVersion;

  /**
   * The HTTP scheme to use for the CAS server.
   *
   * @var string
   */
  protected $httpScheme;

  /**
   * The HTTP hostname to use for the CAS server.
   *
   * @var string
   */
  protected $hostname;

  /**
   * The port number to use for the CAS server.
   *
   * @var int
   */
  protected $port;

  /**
   * The path where CAS can be interacted with on the CAS server.
   *
   * @var string
   */
  protected $path;

  /**
   * The cert verfication method to use when interacting with the CAS server.
   *
   * @var int
   */
  protected $verify;

  /**
   * The path to the CA root cert bundle to use when validating server SSL cert.
   *
   * @var string
   */
  protected $customRootCertBundlePath;

  /**
   * Number of seconds to wait on CAS server response during requests.
   *
   * @var int
   */
  protected $connectionTimeout;

  /**
   * Initialize an object from the CAS module config.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The config object for the CAS module.
   *
   * @return \Drupal\cas\CasServerConfig
   *   The initialized value object.
   */
  public static function createFromModuleConfig(Config $config) {
    $obj = new self();
    $obj->setProtocolVersion($config->get('server.version'));
    $obj->setHttpScheme($config->get('server.protocol'));
    $obj->setHostname($config->get('server.hostname'));
    $obj->setPort($config->get('server.port'));
    $obj->setPath($config->get('server.path'));
    $obj->setVerify($config->get('server.verify'));
    $obj->setCustomRootCertBundlePath($config->get('server.cert'));
    $obj->setConnectionTimeout($config->get('advanced.connection_timeout'));
    return $obj;
  }

  /**
   * Set the protocol version.
   *
   * @param string $version
   *   The version.
   */
  public function setProtocolVersion($version) {
    $this->protocolVersion = $version;
  }

  /**
   * Get the protocol version.
   *
   * @return string
   *   The protocol version.
   */
  public function getProtocolVerison() {
    return $this->protocolVersion;
  }

  /**
   * Set the HTTP scheme.
   *
   * @param string $scheme
   *   The scheme.
   */
  public function setHttpScheme($scheme) {
    $this->httpScheme = $scheme;
  }

  /**
   * Get HTTP scheme.
   *
   * @return string
   *   The HTTP scheme.
   */
  public function getHttpScheme() {
    return $this->httpScheme;
  }

  /**
   * Set hostname.
   *
   * @param string $hostname
   *   The hostname.
   */
  public function setHostname($hostname) {
    $this->hostname = $hostname;
  }

  /**
   * Get hostname.
   *
   * @return string
   *   The hostname.
   */
  public function getHostname() {
    return $this->hostname;
  }

  /**
   * Set port.
   *
   * @param int $port
   *   The port.
   */
  public function setPort($port) {
    $this->port = $port;
  }

  /**
   * Get port.
   *
   * @return int
   *   The port.
   */
  public function getPort() {
    return $this->port;
  }

  /**
   * Set path.
   *
   * @param string $path
   *   The path.
   */
  public function setPath($path) {
    $this->path = $path;
  }

  /**
   * Get path.
   *
   * @return string
   *   The path.
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * Set SSL cert verification method.
   *
   * @param int $verify
   *   The verification method.
   */
  public function setVerify($verify) {
    $this->verify = $verify;
  }

  /**
   * Get SSL cert verification method.
   *
   * @return int
   *   The SSL cert verification method.
   */
  public function getVerify() {
    return $this->verify;
  }

  /**
   * Set custom CA root cert bundle path.
   *
   * @param string $path
   *   The path.
   */
  public function setCustomRootCertBundlePath($path) {
    $this->customRootCertBundlePath = $path;
  }

  /**
   * Get custom CA root cert bundle path.
   *
   * @return string
   *   The path.
   */
  public function getCustomRootCertBundlePath() {
    return $this->customRootCertBundlePath;
  }

  /**
   * Set connection timeout.
   *
   * @param int $timeout
   *   The timeout.
   */
  public function setConnectionTimeout($timeout) {
    $this->connectionTimeout = $timeout;
  }

  /**
   * Get connection timeout.
   *
   * @return int
   *   The timeout.
   */
  public function getDirectConnectionTimeout() {
    return $this->connectionTimeout;
  }

  /**
   * Construct the base URL to the CAS server.
   *
   * @return string
   *   The base URL.
   */
  public function getServerBaseUrl() {
    $httpScheme = $this->getHttpScheme();
    $url = $httpScheme . '://' . $this->getHostname();

    // Only append port if it's non standard.
    $port = $this->getPort();
    if (($httpScheme === 'http' && $port !== 80) || ($httpScheme === 'https' && $port !== 443)) {
      $url .= ':' . $port;
    }

    $url .= $this->getPath();
    $url = rtrim($url, '/') . '/';

    return $url;
  }

  /**
   * Gets config data for guzzle communications with the CAS server.
   *
   * @return array
   *   The guzzle connection options.
   */
  public function getCasServerGuzzleConnectionOptions() {
    $options = [];
    $verify = $this->getVerify();
    switch ($verify) {
      case CasHelper::CA_CUSTOM:
        $cert = $this->getCustomRootCertBundlePath();
        $options['verify'] = $cert;
        break;

      case CasHelper::CA_NONE:
        $options['verify'] = FALSE;
        break;

      case CasHelper::CA_DEFAULT:
      default:
        $options['verify'] = TRUE;
    }

    $options['timeout'] = $this->getDirectConnectionTimeout();

    return $options;
  }

}
