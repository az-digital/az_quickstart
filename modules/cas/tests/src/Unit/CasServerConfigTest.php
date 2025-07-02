<?php

namespace Drupal\Tests\cas\Unit;

use Drupal\cas\CasServerConfig;
use Drupal\cas\Service\CasHelper;
use Drupal\Tests\UnitTestCase;

/**
 * CasServerConfig unit tests.
 *
 * @ingroup cas
 *
 * @group cas
 */
class CasServerConfigTest extends UnitTestCase {

  /**
   * Test getters.
   */
  public function testGetters() {
    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example.com',
        'server.protocol' => 'https',
        'server.port' => 443,
        'server.path' => '/cas',
        'server.version' => '1.0',
        'server.verify' => CasHelper::CA_DEFAULT,
        'server.cert' => 'foo',
        'advanced.connection_timeout' => 30,
      ],
    ]);

    $serverConfig = CasServerConfig::createFromModuleConfig($configFactory->get('cas.settings'));

    $this->assertEquals('example.com', $serverConfig->getHostname());
    $this->assertEquals('https', $serverConfig->getHttpScheme());
    $this->assertEquals(443, $serverConfig->getPort());
    $this->assertEquals('/cas', $serverConfig->getPath());
    $this->assertEquals('1.0', $serverConfig->getProtocolVerison());
    $this->assertEquals('foo', $serverConfig->getCustomRootCertBundlePath());
    $this->assertEquals(CasHelper::CA_DEFAULT, $serverConfig->getVerify());
    $this->assertEquals(30, $serverConfig->getDirectConnectionTimeout());
  }

  /**
   * Test getCasServerGuzzleConnectionOptions.
   *
   * @dataProvider casServerConnectionOptionsDataProvider
   */
  public function testCasServerGuzzleConnectionOptions($sslVerifyMethod) {
    $configFactory = $this->getConfigFactoryStub([
      'cas.settings' => [
        'server.hostname' => 'example.com',
        'server.protocol' => 'https',
        'server.port' => 443,
        'server.path' => '/cas',
        'server.version' => '1.0',
        'server.verify' => $sslVerifyMethod,
        'server.cert' => 'foo',
        'advanced.connection_timeout' => 30,
      ],
    ]);

    $serverConfig = CasServerConfig::createFromModuleConfig($configFactory->get('cas.settings'));

    switch ($sslVerifyMethod) {
      case CasHelper::CA_CUSTOM:
        $this->assertEquals(['verify' => 'foo', 'timeout' => 30], $serverConfig->getCasServerGuzzleConnectionOptions());
        break;

      case CasHelper::CA_NONE:
        $this->assertEquals(['verify' => FALSE, 'timeout' => 30], $serverConfig->getCasServerGuzzleConnectionOptions());
        break;

      default:
        $this->assertEquals(['verify' => TRUE, 'timeout' => 30], $serverConfig->getCasServerGuzzleConnectionOptions());
        break;
    }
  }

  /**
   * Data provider for testCasServerGuzzleConnectionOptions.
   *
   * @return array
   *   The data to provide.
   */
  public function casServerConnectionOptionsDataProvider() {
    return [
      [CasHelper::CA_NONE],
      [CasHelper::CA_CUSTOM],
      [CasHelper::CA_DEFAULT],
    ];
  }

  /**
   * Test getServerBaseUrl.
   *
   * @dataProvider getServerBaseUrlDataProvider
   */
  public function testGetServerBaseUrl($serverConfig, $expectedBaseUrl) {
    $config_factory = $this->getConfigFactoryStub([
      'cas.settings' => $serverConfig,
    ]);

    $casServerConfig = CasServerConfig::createFromModuleConfig($config_factory->get('cas.settings'));

    $this->assertEquals($expectedBaseUrl, $casServerConfig->getServerBaseUrl());
  }

  /**
   * Data provider for testGetServerBaseUrl.
   */
  public function getServerBaseUrlDataProvider() {
    return [
      [
        [
          'server.protocol' => 'https',
          'server.hostname' => 'example.com',
          'server.port' => 443,
          'server.path' => '/cas',
        ],
        'https://example.com/cas/',
      ],
      [
        [
          'server.protocol' => 'http',
          'server.hostname' => 'foobar.net',
          'server.port' => 4433,
          'server.path' => '/cas-alt',
        ],
        'http://foobar.net:4433/cas-alt/',
      ],
    ];
  }

}
