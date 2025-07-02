<?php

namespace Drupal\devel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\user\RoleInterface;
use Drupal\user\RoleStorageInterface;
use Drupal\user\UserStorageInterface;

/**
 * Switch user helper service.
 */
class SwitchUserListHelper {

  use StringTranslationTrait;

  /**
   * The Current User object.
   */
  protected AccountInterface $currentUser;

  /**
   * The user storage.
   */
  protected UserStorageInterface $userStorage;

  /**
   * The redirect destination service.
   */
  protected RedirectDestinationInterface $redirectDestination;

  /**
   * The role storage.
   */
  protected RoleStorageInterface $roleStorage;

  /**
   * Constructs a new SwitchUserListHelper service.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(
    AccountInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    RedirectDestinationInterface $redirect_destination,
    TranslationInterface $string_translation,
  ) {
    $this->currentUser = $current_user;
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->redirectDestination = $redirect_destination;
    $this->stringTranslation = $string_translation;
    $this->roleStorage = $entity_type_manager->getStorage('user_role');
  }

  /**
   * Provides the list of accounts that can be used for the user switch.
   *
   * Inactive users are omitted from all of the following db selects. Users
   * with 'switch users' permission and anonymous user if include_anon property
   * is set to TRUE, are prioritized.
   *
   * @param int $limit
   *   The number of accounts to use for the list.
   * @param bool $include_anonymous
   *   Whether or not to include the anonymous user.
   *
   * @return \Drupal\Core\Session\AccountInterface[]
   *   List of accounts to be used for the switch.
   */
  public function getUsers(int $limit = 50, bool $include_anonymous = FALSE) {
    $limit = $include_anonymous ? $limit - 1 : $limit;

    // Users with 'switch users' permission are prioritized so get these first.
    $query = $this->userStorage->getQuery()
      ->condition('uid', 0, '>')
      ->condition('status', 0, '>')
      ->sort('access', 'DESC')
      ->accessCheck(FALSE)
      ->range(0, $limit);

    /** @var array<string, RoleInterface> $roles */
    $roles = $this->roleStorage->loadMultiple();
    unset($roles[AccountInterface::ANONYMOUS_ROLE]);
    $roles = array_filter($roles, static fn($role): bool => $role->hasPermission('switch users'));
    if ($roles !== [] && !isset($roles[RoleInterface::AUTHENTICATED_ID])) {
      $query->condition('roles', array_keys($roles), 'IN');
    }

    $user_ids = $query->execute();

    // If we don't have enough users with 'switch users' permission, add more
    // users until we hit $limit.
    if (count($user_ids) < $limit) {
      $query = $this->userStorage->getQuery()
        ->condition('uid', 0, '>')
        ->condition('status', 0, '>')
        ->sort('access', 'DESC')
        ->accessCheck(FALSE)
        ->range(0, $limit);

      // Exclude the prioritized user ids if the previous query returned some.
      if (!empty($user_ids)) {
        $query->condition('uid', array_keys($user_ids), 'NOT IN');
        $query->range(0, $limit - count($user_ids));
      }

      $user_ids += $query->execute();
    }

    /** @var \Drupal\Core\Session\AccountInterface[] $accounts */
    $accounts = $this->userStorage->loadMultiple($user_ids);

    if ($include_anonymous) {
      $anonymous = new AnonymousUserSession();
      $accounts[$anonymous->id()] = $anonymous;
    }

    // Syntax comes from https://php.watch/versions/8.2/partially-supported-callable-deprecation.
    uasort($accounts, self::class . '::sortUserList');

    return $accounts;
  }

  /**
   * Builds the user listing as renderable array.
   *
   * @param \Drupal\Core\Session\AccountInterface[] $accounts
   *   The accounts to be rendered in the list.
   *
   * @return array
   *   A renderable array.
   */
  public function buildUserList(array $accounts): array {
    $links = [];

    foreach ($accounts as $account) {
      $links[$account->id()] = [
        'title' => $account->getDisplayName(),
        'url' => Url::fromRoute('devel.switch', ['name' => $account->getAccountName()]),
        'query' => $this->redirectDestination->getAsArray(),
        'attributes' => [
          'title' => $account->hasPermission('switch users') ? $this->t('This user can switch back.') : $this->t('Caution: this user will be unable to switch back.'),
        ],
      ];

      if ($account->isAnonymous()) {
        $links[$account->id()]['url'] = Url::fromRoute('user.logout');
      }

      if ($this->currentUser->id() === $account->id()) {
        $links[$account->id()]['title'] = new FormattableMarkup('<strong>%user</strong>', ['%user' => $account->getDisplayName()]);
      }
    }

    return [
      '#theme' => 'links',
      '#links' => $links,
      '#attached' => ['library' => ['devel/devel']],
    ];
  }

  /**
   * Helper callback for uasort() to sort accounts by last access.
   *
   * @param \Drupal\Core\Session\AccountInterface $a
   *   First account.
   * @param \Drupal\Core\Session\AccountInterface $b
   *   Second account.
   *
   * @return int
   *   Result of comparing the last access times:
   *   - -1 if $a was more recently accessed
   *   -  0 if last access times compare equal
   *   -  1 if $b was more recently accessed
   */
  public static function sortUserList(AccountInterface $a, AccountInterface $b): int {
    $a_access = (int) $a->getLastAccessedTime();
    $b_access = (int) $b->getLastAccessedTime();

    if ($a_access === $b_access) {
      return 0;
    }

    // User never access to site.
    if ($a_access === 0) {
      return 1;
    }

    return ($a_access > $b_access) ? -1 : 1;
  }

}
