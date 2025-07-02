<?php

namespace Drupal\masquerade;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Masquerade callbacks.
 */
class MasqueradeCallbacks implements TrustedCallbackInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The masquerade service.
   *
   * @var \Drupal\masquerade\Masquerade
   */
  protected $masquerade;

  /**
   * MasqueradeCallbacks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\masquerade\Masquerade $masquerade
   *   The masuerade.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Masquerade $masquerade) {
    $this->entityTypeManager = $entity_type_manager;
    $this->masquerade = $masquerade;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['renderCacheLink', 'renderSwitchBackLink'];
  }

  /**
   * The #post_render_cache callback; replaces placeholder with masquerade link.
   *
   * @param int $account_id
   *   The account ID.
   *
   * @return array
   *   A renderable array containing the masquerade link if allowed.
   */
  public function renderCacheLink($account_id) {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($account_id);
    if (masquerade_target_user_access($account)) {
      // @todo Attaching a CSS class to this would be nice.
      return [
        'masquerade' => [
          '#type' => 'link',
          '#title' => new TranslatableMarkup('Masquerade as @name', ['@name' => $account->getDisplayName()]),
          '#url' => $account->toUrl('masquerade'),
        ],
      ];
    }
    return ['#markup' => ''];
  }

  /**
   * Lazy builder callback for switch-back link.
   *
   * @return array|string
   *   Render array or an emty string.
   */
  public function renderSwitchBackLink() {
    if ($this->masquerade->isMasquerading()) {
      return [
        [
          '#type' => 'link',
          '#title' => new TranslatableMarkup('Switch back'),
          '#url' => Url::fromRoute('masquerade.unmasquerade', [], [
            'query' => \Drupal::destination()->getAsArray(),
          ]),
        ],
      ];
    }
    return '';
  }

}
