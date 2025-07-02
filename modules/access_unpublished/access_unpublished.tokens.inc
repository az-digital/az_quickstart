<?php

/**
 * @file
 * Contains access_unpublished token includes.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\access_unpublished\AccessUnpublished;

/**
 * Implements hook_token_info().
 */
function access_unpublished_token_info() {

  $tokens = [];
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if (AccessUnpublished::applicableEntityType($entity_type)) {
      // @todo Remove after https://drupal.org/i/2163027 has landed, also @see comment_token_info().
      $token_type = ($entity_type_id == 'taxonomy_term') ? 'term' : $entity_type_id;

      $tokens[$token_type] = [
        'access-unpublished-url' => [
          'name' => t('Access Unpublished URL'),
          'description' => t('The URL containing the hash. The most recently changed unexpired access token is used.'),
        ],
      ];
    }
  }

  return [
    'tokens' => $tokens,
  ];
}

/**
 * Implements hook_tokens().
 */
function access_unpublished_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  if (
    empty($data['entity']) ||
    !($data['entity'] instanceof EntityInterface) ||
    !AccessUnpublished::applicableEntityType($data['entity']->getEntityType())
  ) {
    return [];
  }

  $replacements = [];
  $entity = $data['entity'];
  /** @var \Drupal\access_unpublished\AccessTokenManager $accessTokenManager */
  $accessTokenManager = \Drupal::service('access_unpublished.access_token_manager');
  foreach ($tokens as $name => $original) {
    switch ($name) {
      case 'access-unpublished-url':
        $token = $accessTokenManager->getActiveAccessToken($entity);
        $url = '';
        if ($token) {
          $url = $accessTokenManager->getAccessTokenUrl($token, $entity->language());
        }

        $replacements[$original] = $url;
        break;
    }
  }

  return $replacements;
}
