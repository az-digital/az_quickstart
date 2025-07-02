<?php

/**
 * @file
 * Metatag token integration.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Implements hook_token_info().
 */
function metatag_token_info() {
  $info = [];

  $group_manager = \Drupal::service('plugin.manager.metatag.group');
  $tag_manager = \Drupal::service('plugin.manager.metatag.tag');
  $tag_definitions = $tag_manager->getDefinitions();

  $info['types']['metatag'] = [
    'name' => new TranslatableMarkup('Metatags'),
    'description' => new TranslatableMarkup('Tokens related to Metatags.'),
    'needs-data' => 'metatag',
  ];

  foreach ($tag_definitions as $tag_id => $tag_definition) {
    $label = $tag_definition['label'];
    $description = $tag_definition['description'] ?? '';
    $multiple = $tag_definition['multiple'];
    $metatag_token_name = 'metatag-' . $tag_id;

    $group = $group_manager->getDefinition($tag_definition['group']);
    if ($group) {
      $label = $group['label'] . ': ' . $label;
    }

    $info['tokens']['current-page']['metatag'] = [
      'name' => new TranslatableMarkup('Metatags'),
      'description' => new TranslatableMarkup('Metatag values for the current page.'),
      'type' => 'metatag',
    ];
    $info['tokens']['metatag'][$tag_id] = [
      'name' => Html::escape($label),
      'description' => $description,
      'type' => $multiple ? "list<$metatag_token_name>" : $metatag_token_name,
    ];

    $info['types'][$metatag_token_name] = [
      'name' => Html::escape($label),
      'description' => new TranslatableMarkup('@label tokens.', ['@label' => Html::escape($label)]),
      'needs-data' => $metatag_token_name,
      'nested' => TRUE,
    ];

    $info['tokens'][$metatag_token_name]['value'] = [
      'name' => Html::escape($label),
      'module' => 'metatag',
      'description' => $description,
    ];

    // Tag list token type.
    if ($multiple) {
      $info['types']["list<$metatag_token_name>"] = [
        'name' => new TranslatableMarkup('List of @type values', ['@type' => Html::escape($label)]),
        'description' => new TranslatableMarkup('Tokens for lists of @type values.', ['@type' => Html::escape($label)]),
        'needs-data' => "list<$metatag_token_name>",
        'nested' => TRUE,
      ];

      // Show a different token for each tag delta.
      // Since we don't know how many there will be, we will just show 3.
      for ($delta = 0; $delta < 3; $delta++) {
        $info['tokens']["list<$metatag_token_name>"][$delta] = [
          'name' => new TranslatableMarkup('@type type with delta @delta', [
            '@type' => Html::escape($label),
            '@delta' => $delta,
          ]),
          'module' => 'token',
          'type' => $metatag_token_name,
        ];
      }
    }
  }

  return $info;
}

/**
 * Implements hook_token_info_alter().
 */
function metatag_token_info_alter(&$info) {
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if (!$entity_type->entityClassImplements(ContentEntityInterface::class)) {
      continue;
    }

    // Make sure a token type exists for this entity.
    $token_type = \Drupal::service('token.entity_mapper')->getTokenTypeForEntityType($entity_type_id);
    if (empty($token_type) || !isset($info['types'][$token_type])) {
      continue;
    }

    $fields = \Drupal::entityTypeManager()->getStorage('field_storage_config')->loadByProperties([
      'entity_type' => $entity_type_id,
      'type' => 'metatag',
    ]);
    foreach ($fields as $field) {
      $field_token_name = $token_type . '-' . $field->getName();
      $info['types'][$field_token_name] = [
        'name' => Html::escape($field->getName()),
        'description' => new TranslatableMarkup('@label tokens.', ['@label' => Html::escape($field->getName())]),
        'needs-data' => $field_token_name,
        'nested' => TRUE,
        'type' => 'metatag',
        'module' => 'metatag',
      ];
    }
  }
}

/**
 * Implements hook_tokens().
 */
function metatag_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  switch ($type) {
    case 'current-page':
      /** @var \Drupal\Core\Utility\Token $token_service */
      $token_service = \Drupal::token();
      $metatag_tokens = $token_service->findWithPrefix($tokens, 'metatag');
      if (!empty($metatag_tokens) && metatag_is_current_route_supported()) {
        // Add cache contexts to ensure this token functions on a per-path
        // basis.
        $bubbleable_metadata->addCacheContexts(['url.site']);
        $replacements += $token_service->generate('metatag', $metatag_tokens, [], $options, $bubbleable_metadata);
      }
      break;

    case 'entity':
      if (!empty($data['entity_type']) && !empty($data['entity']) && !empty($data['token_type'])) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $data['entity'];
        if (!($entity instanceof ContentEntityInterface)) {
          return $replacements;
        }

        $metatag_fields = [];
        foreach ($tokens as $name => $original) {
          $field_name = explode(':', $name)[0];

          if ($entity->hasField($field_name) && $entity->get($field_name)->getFieldDefinition()->getType() === 'metatag') {
            $metatag_fields[] = $field_name;
          }
        }

        if (!empty($metatag_fields)) {
          /** @var \Drupal\Core\Utility\Token $token_service */
          $token_service = \Drupal::token();
          $metatag_tokens = [];
          foreach ($metatag_fields as $metatag_field) {
            $metatag_tokens += $token_service->findWithPrefix($tokens, $metatag_field);
          }
          $replacements += $token_service->generate('metatag', $metatag_tokens, ['entity' => $entity], $options, $bubbleable_metadata);
        }
      }
      break;

    case 'metatag':
      $metatag_manager = \Drupal::service('metatag.manager');

      $entity = $options['entity'] ?? metatag_get_route_entity();
      $tags = [];
      if ($entity instanceof ContentEntityInterface) {
        // If content entity does not have an ID the page is likely an "Add"
        // page, so skip processing for entity which has not been created yet.
        if (!$entity->id()) {
          return NULL;
        }

        $tags = $metatag_manager->tagsFromEntity($entity);
      }
      $tags += metatag_get_default_tags($entity);

      // Trigger hook_metatags_alter().
      // Allow modules to override tags or the entity used for token
      // replacements.
      $context = [
        'entity' => &$entity,
      ];
      \Drupal::service('module_handler')->alter('metatags', $tags, $context);

      // If the entity was changed above, use that for generating the meta tags.
      if (isset($context['entity'])) {
        $entity = $context['entity'];
      }

      $processed_tags = $metatag_manager->generateTokenValues($tags, $entity);

      foreach ($tokens as $name => $original) {
        // For the [metatag:tag_name] token.
        if (strpos($name, ':') === FALSE) {
          $tag_name = $name;
        }
        // For [metatag:tag_name:0], [metatag:tag_name:0:value] and
        // [metatag:tag_name:value] tokens.
        else {
          [$tag_name, $delta] = explode(':', $name, 2);
          if (!is_numeric($delta)) {
            unset($delta);
          }
        }

        // Replace dashes (-) with underscores (_) for e.g. canonical-url.
        $tag_name = str_replace('-', '_', $tag_name);

        if (empty($processed_tags[$tag_name])) {
          continue;
        }

        // Render only one delta.
        if (isset($delta)) {
          $replacements[$original] = $processed_tags[$tag_name][$delta] ?? NULL;
        }
        else {
          if (is_array($processed_tags[$tag_name])) {
            $replacements[$original] = implode(',', array_filter($processed_tags[$tag_name]));
          }
          else {
            $replacements[$original] = $processed_tags[$tag_name];
          }
        }
      }
      break;
  }

  return $replacements;
}
