<?php

/**
 * @file
 * The hooks used by Coffee includes an example of hook_coffee_commands().
 */

use Drupal\Core\Url;

/**
 * Implements hook_coffee_commands().
 */
function coffee_coffee_commands() {
  $commands = [];

  // Add in a link for the front page.
  $commands[] = [
    'value' => Url::fromRoute('<front>')->toString(),
    'label' => t('Go to front page'),
    'command' => ':front',
  ];

  // The command is ":add" and includes the link_title to empower the
  // autocompletion.
  $command = ':add';
  $entity_manager = \Drupal::entityTypeManager();
  // Only use node types the user has access to.
  foreach ($entity_manager->getStorage('node_type')->loadMultiple() as $type) {
    $node_type = $type->id();
    if ($entity_manager->getAccessControlHandler('node')->createAccess($node_type)) {
      $commands[] = [
        'value' => Url::fromRoute('node.add', ['node_type' => $node_type])->toString(),
        'label' => $type->label(),
        'command' => $command . ' ' . $type->label(),
      ];
    }
  }

  return $commands;
}
