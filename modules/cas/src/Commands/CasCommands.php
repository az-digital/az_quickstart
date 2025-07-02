<?php

namespace Drupal\cas\Commands;

use Drush\Commands\DrushCommands;

/**
 * Drush command file for CAS commands.
 */
class CasCommands extends DrushCommands {

  /**
   * Sets CAS username for an existing Drupal user.
   *
   * @param string $drupalUsername
   *   The drupal user name of the user to modify.
   * @param string $casUsername
   *   The CAS username to assign to the user.
   *
   * @usage cas:set-cas-username foo bar
   *   Assigns the CAS username of "bar" to the Drupal user with name "foo"
   *
   * @command cas:set-cas-username
   */
  public function setCasUsername($drupalUsername, $casUsername) {
    $account = user_load_by_name($drupalUsername);
    if ($account) {
      $casUserManager = \Drupal::service('cas.user_manager');
      $casUserManager->setCasUsernameForAccount($account, $casUsername);
      $this->logger->success(dt('Assigned CAS username "!casUsername" to user "!drupalUsername"', [
        '!casUsername' => $casUsername,
        '!drupalUsername' => $drupalUsername,
      ]));
    }
    else {
      $this->logger->error(dt('Unable to load user: !user', ['!user' => $drupalUsername]));
    }
  }

}
