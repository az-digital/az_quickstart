<?php

namespace Drupal\antibot\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Implement Class AntibotPage.
 *
 * @package Drupal\antibot\Controller
 */
class AntibotPage extends ControllerBase {

  /**
   * The Antibot page where robotic form submissions end up.
   *
   * @return string
   *   Return message.
   */
  public function page() {
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['antibot-message', 'antibot-message-error'],
      ],
      '#value' => $this->t('You have reached this page because you submitted a form that required JavaScript to be enabled on your browser. This protection is in place to attempt to prevent automated submissions made on forms. Please return to the page that you came from and enable JavaScript on your browser before attempting to submit the form again.'),
      '#attached' => [
        'library' => ['antibot/antibot.form'],
      ],
    ];
  }

}
