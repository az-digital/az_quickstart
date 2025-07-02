<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate_plus\authentication;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\AuthenticationPluginBase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Sainsburys\Guzzle\Oauth2\GrantType\AuthorizationCode;
use Sainsburys\Guzzle\Oauth2\GrantType\ClientCredentials;
use Sainsburys\Guzzle\Oauth2\GrantType\JwtBearer;
use Sainsburys\Guzzle\Oauth2\GrantType\PasswordCredentials;
use Sainsburys\Guzzle\Oauth2\GrantType\RefreshToken;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;

/**
 * Provides OAuth2 authentication for the HTTP resource.
 *
 * @link https://packagist.org/packages/sainsburys/guzzle-oauth2-plugin
 *
 * @Authentication(
 *   id = "oauth2",
 *   title = @Translation("OAuth2")
 * )
 */
class OAuth2 extends AuthenticationPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationOptions(): array {
    $handlerStack = HandlerStack::create();
    $client = new Client([
      'handler' => $handlerStack,
      'base_uri' => $this->configuration['base_uri'],
      'auth' => 'oauth2',
    ]);

    switch ($this->configuration['grant_type']) {
      case 'authorization_code':
        $grant_type = new AuthorizationCode($client, $this->configuration);
        break;

      case 'client_credentials':
        $grant_type = new ClientCredentials($client, $this->configuration);
        break;

      case 'urn:ietf:params:oauth:grant-type:jwt-bearer':
        $grant_type = new JwtBearer($client, $this->configuration);
        break;

      case 'password':
        $grant_type = new PasswordCredentials($client, $this->configuration);
        break;

      case 'refresh_token':
        $grant_type = new RefreshToken($client, $this->configuration);
        break;

      default:
        throw new MigrateException("Unrecognized grant_type {$this->configuration['grant_type']}.");

    }
    $middleware = new OAuthMiddleware($client, $grant_type);

    return [
      'headers' => [
        'Authorization' => 'Bearer ' . $middleware->getAccessToken()->getToken(),
      ],
    ];
  }

}
