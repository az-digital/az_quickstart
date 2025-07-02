<?php

namespace Drupal\access_unpublished;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of access token entities.
 */
class EntityFormAccessTokenListBuilder extends AccessTokenListBuilder {

  /**
   * The content entity the list belongs to.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $contentEntity;

  /**
   * {@inheritdoc}
   */
  protected $handlerName = 'entity_form_list_builder';

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    // Enable language column and filter if multiple languages are added.
    $header = parent::buildHeader();
    unset($header['host']);
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);
    unset($row['host']);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    return $this->accessTokenManager->getAccessTokensByEntity($this->contentEntity);
  }

  /**
   * {@inheritdoc}
   */
  public function render($contentEntity = NULL) {
    $this->contentEntity = $contentEntity;
    return parent::render();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $token) {
    /** @var \Drupal\access_unpublished\AccessTokenInterface $token */

    $operations = parent::getDefaultOperations($token);
    if (isset($operations['copy'])) {
      $url = $this->accessTokenManager->getAccessTokenUrl($token, $this->contentEntity->language());
      $operations['copy'] = [
        'title' => $this->t('Copy'),
        'url' => Url::fromUserInput('#'),
        'attributes' => [
          'data-unpublished-access-url' => $url,
          'class' => ['clipboard-button'],
        ],
        'weight' => 50,
      ];
    }
    return $operations;
  }

}
