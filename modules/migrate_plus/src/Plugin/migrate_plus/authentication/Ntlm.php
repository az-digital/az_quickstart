<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate_plus\authentication;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate_plus\AuthenticationPluginBase;

/**
 * Provides NTLM (Microsoft NTLM) authentication for the HTTP resource.
 *
 * @Authentication(
 *   id = "ntlm",
 *   title = @Translation("Ntlm")
 * )
 */
class Ntlm extends AuthenticationPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationOptions(): array {
    return [
      'auth' => [
        $this->configuration['username'],
        $this->configuration['password'],
        'ntlm',
      ],
    ];
  }

}
