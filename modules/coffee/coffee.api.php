<?php

/**
 * @file
 * Hooks provided by Coffee module.
 */

use Drupal\Core\Url;
use Drupal\views\Views;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Extend the Coffee functionality with your own commands and items.
 *
 * Here's an example of how to add content to Coffee.
 */
function hook_coffee_commands() {
  $commands = [];

  // Basic example, for 1 result.
  $commands[] = [
    'value' => Url::fromRoute('my.simple.route')->toString(),
    'label' => 'Simple',
    // Every result should include a command.
    'command' => ':simple',
  ];

  // More advanced example to include view results.
  if ($view = Views::getView('frontpage')) {
    $view->setDisplay();
    $view->preExecute();
    $view->execute();

    foreach ($view->result as $row) {
      $entity = $row->_entity;
      $commands[] = [
        'value' => $entity->toUrl()->toString(),
        'label' => 'Pub: ' . $entity->label(),
        // You can also specify commands that if the user enters, this command
        // should show.
        'command' => ':x ' . $entity->label(),
      ];
    }
  }

  return $commands;
}

/**
 * @} End of "addtogroup hooks"
 */
