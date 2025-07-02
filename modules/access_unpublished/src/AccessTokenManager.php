<?php

namespace Drupal\access_unpublished;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Service to work with access tokens.
 */
class AccessTokenManager {

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * AccessTokenManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface|null $moderationInformation
   *   The moderation information service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, ModerationInformationInterface $moderationInformation = NULL) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->moderationInfo = $moderationInformation;
  }

  /**
   * Obtains access tokens for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $status
   *   Status of the token. Possible values are 'active' and 'expired'. No
   *   parameter will return all tokens.
   *
   * @return \Drupal\access_unpublished\AccessTokenInterface[]
   *   The access tokens.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAccessTokensByEntity(EntityInterface $entity, $status = NULL) {
    if (!AccessUnpublished::applicableEntityType($entity->getEntityType())) {
      return [];
    }
    $query = $this->buildAccessTokenQuery($status);
    $tokens = $query->condition('entity_type', $entity->getEntityType()->id())
      ->condition('entity_id', $entity->id())
      ->execute();
    return $this->entityTypeManager->getStorage('access_token')->loadMultiple($tokens);
  }

  /**
   * Obtains all access tokens.
   *
   * @param string $status
   *   Status of the token. Possible values are 'active' and 'expired'. No
   *   parameter will return all tokens.
   *
   * @return AccessTokenInterface[]
   *   The access tokens.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAccessTokens($status = NULL) {
    $tokens = $this->buildAccessTokenQuery($status)->execute();
    return $this->entityTypeManager->getStorage('access_token')->loadMultiple($tokens);
  }

  /**
   * Builds a generic query to obtain some access tokens.
   *
   * @param string $status
   *   Status of the token. Possible values are 'active' and 'expired'. No
   *   parameter will return all tokens.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An access token query.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildAccessTokenQuery($status = NULL) {
    $query = $this->entityTypeManager->getStorage('access_token')->getQuery();
    if ($status == 'active') {
      $group = $query->orConditionGroup()
        ->condition('expire', time(), '>=')
        ->condition('expire', '-1');
      $query->condition($group);
    }
    elseif ($status == 'expired') {
      $group = $query->andConditionGroup()
        ->condition('expire', time(), '<')
        ->condition('expire', '-1', '!=');
      $query->condition($group);
    }

    $query->sort('expire', 'ASC');
    $query->accessCheck(FALSE);
    return $query;
  }

  /**
   * Obtains an active access token for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\access_unpublished\AccessTokenInterface|null
   *   An Access Token entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getActiveAccessToken(EntityInterface $entity) {
    if ($tokens = $this->getAccessTokensByEntity($entity, 'active')) {
      return current($tokens);
    }
    return NULL;
  }

  /**
   * Generates a URL containing the access token hash value.
   *
   * @param \Drupal\access_unpublished\AccessTokenInterface $token
   *   The access token.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language of the generated URL.
   *
   * @return \Drupal\Core\GeneratedUrl
   *   The generated URL that includes the hash parameter.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getAccessTokenUrl(AccessTokenInterface $token, LanguageInterface $language) {
    $tokenKey = $this->configFactory->get('access_unpublished.settings')->get('hash_key');

    $rel = 'canonical';

    // Link to a forward revision if available.
    if ($this->moderationInfo && $this->moderationInfo->hasPendingRevision($token->getHost()) && $token->getHost()->getEntityType()->hasLinkTemplate('latest-version')) {
      $rel = 'latest-version';
    }
    return $token->getHost()->toUrl($rel, [
      'query' => [$tokenKey => $token->get('value')->value],
      'absolute' => TRUE,
      'language' => $language,
    ])->toString();
  }

}
