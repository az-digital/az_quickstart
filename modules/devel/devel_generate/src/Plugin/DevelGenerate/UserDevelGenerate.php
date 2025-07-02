<?php

namespace Drupal\devel_generate\Plugin\DevelGenerate;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\user\RoleStorageInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a UserDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "user",
 *   label = @Translation("users"),
 *   description = @Translation("Generate a given number of users. Optionally delete current users."),
 *   url = "user",
 *   permission = "administer devel_generate",
 *   settings = {
 *     "num" = 50,
 *     "kill" = FALSE,
 *     "pass" = ""
 *   }
 * )
 */
class UserDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The user storage.
   */
  protected UserStorageInterface $userStorage;

  /**
   * The date formatter service.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Provides system time.
   */
  protected TimeInterface $time;

  /**
   * The role storage.
   */
  protected RoleStorageInterface $roleStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $entity_type_manager = $container->get('entity_type.manager');
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->userStorage = $entity_type_manager->getStorage('user');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->time = $container->get('datetime.time');
    $instance->roleStorage = $entity_type_manager->getStorage('user_role');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['num'] = [
      '#type' => 'number',
      '#title' => $this->t('How many users would you like to generate?'),
      '#default_value' => $this->getSetting('num'),
      '#required' => TRUE,
      '#min' => 0,
    ];

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete all users (except user id 1) before generating new users.'),
      '#default_value' => $this->getSetting('kill'),
    ];

    $roles = array_map(static fn($role): string => $role->label(), $this->roleStorage->loadMultiple());
    unset($roles[AccountInterface::AUTHENTICATED_ROLE], $roles[AccountInterface::ANONYMOUS_ROLE]);
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Which roles should the users receive?'),
      '#description' => $this->t('Users always receive the <em>authenticated user</em> role.'),
      '#options' => $roles,
    ];

    $form['pass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password to be set'),
      '#default_value' => $this->getSetting('pass'),
      '#size' => 32,
      '#description' => $this->t('Leave this field empty if you do not need to set a password'),
    ];

    $options = [1 => $this->t('Now')];
    foreach ([3600, 86400, 604800, 2592000, 31536000] as $interval) {
      $options[$interval] = $this->dateFormatter->formatInterval($interval, 1) . ' ' . $this->t('ago');
    }

    $form['time_range'] = [
      '#type' => 'select',
      '#title' => $this->t('How old should user accounts be?'),
      '#description' => $this->t('User ages will be distributed randomly from the current time, back to the selected time.'),
      '#options' => $options,
      '#default_value' => 604800,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values): void {
    $num = $values['num'];
    $kill = $values['kill'];
    $pass = $values['pass'];
    $age = $values['time_range'];
    $roles = array_filter($values['roles']);

    if ($kill) {
      $uids = $this->userStorage->getQuery()
        ->condition('uid', 1, '>')
        ->accessCheck(FALSE)
        ->execute();
      $users = $this->userStorage->loadMultiple($uids);
      $this->userStorage->delete($users);

      $this->setMessage($this->formatPlural(count($uids), '1 user deleted', '@count users deleted.'));
    }

    if ($num > 0) {
      $names = [];
      while (count($names) < $num) {
        $name = $this->getRandom()->word(mt_rand(6, 12));
        $names[$name] = '';
      }

      if ($roles === []) {
        $roles = [AccountInterface::AUTHENTICATED_ROLE];
      }

      foreach (array_keys($names) as $name) {
        /** @var \Drupal\user\UserInterface $account */
        $account = $this->userStorage->create([
          'uid' => NULL,
          'name' => $name,
          'pass' => $pass,
          'mail' => $name . '@example.com',
          'status' => 1,
          'created' => $this->time->getRequestTime() - mt_rand(0, $age),
          'roles' => array_values($roles),
          // A flag to let hook_user_* know that this is a generated user.
          'devel_generate' => TRUE,
        ]);

        // Populate all fields with sample values.
        $this->populateFields($account);
        $account->save();
      }
    }

    $this->setMessage($this->t('@num_users created.',
      ['@num_users' => $this->formatPlural($num, '1 user', '@count users')]));
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []): array {
    return [
      'num' => array_shift($args),
      'time_range' => 0,
      'roles' => self::csvToArray($options['roles']),
      'kill' => $options['kill'],
      'pass' => $options['pass'],
    ];
  }

}
