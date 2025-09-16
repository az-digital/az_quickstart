<?php

namespace Drupal\az_core\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\az_core\Utility\AZBootstrapMarkupConverter;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Commands for updating AZ Bootstrap 2 attributes in field_group settings.
 */
final class AZBootstrapEntityDisplayGroupsCommands extends DrushCommands {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The config manager service.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected ConfigManagerInterface $configManager;

  public function __construct(ConfigFactoryInterface $configFactory, ConfigManagerInterface $configManager) {
    parent::__construct();
    $this->configFactory = $configFactory;
    $this->configManager = $configManager;
  }

  /**
   * Recursively collect all field IDs from a group (expanding nested groups).
   */
  protected function getFieldsFromGroup(array $groupConfig, array $allGroups): array {
    $fields = [];
    $children = $groupConfig['children'] ?? [];

    foreach ($children as $child) {
      if (isset($allGroups[$child])) {
        $fields = array_merge($fields, $this->getFieldsFromGroup($allGroups[$child], $allGroups));
      }
      else {
        $fields[] = $child;
      }
    }

    return $fields;
  }

  /**
   * Check command.
   */
  #[CLI\Command(name: 'bs5-entity-display-groups:check', aliases: ['bs5edg:check'])]
  #[CLI\Option(name: 'patterns', description: 'Comma-separated list of patterns to check (overrides defaults).')]
  #[CLI\Usage(name: 'drush bs5-entity-display-groups:check', description: 'Scan all field_group format_settings for deprecated Arizona Bootstrap 2 classes/attributes.')]
  #[CLI\DefaultTableFields(fields: ['entity', 'bundle', 'mode', 'group', 'match', 'config', 'fields'])]
  #[CLI\FieldLabels(labels: [
    'entity' => 'Entity',
    'bundle' => 'Bundle',
    'mode' => 'Mode',
    'group' => 'Field Group',
    'match' => 'Match',
    'config' => 'Config ID',
    'fields' => 'Fields in Group',
  ])]
  public function check(array $options = []): array {
    $matches = [];

    // Default patterns: Arizona Bootstrap 2 classes + data-* attributes.
    $defaultPatterns = array_merge(
      array_keys(AZBootstrapMarkupConverter::CLASS_MAP),
      array_map(fn($attr) => 'data-' . $attr, AZBootstrapMarkupConverter::LEGACY_DATA_ATTRIBUTES)
    );

    $patterns = $defaultPatterns;
    if (!empty($options['patterns'])) {
      $patterns = array_map('trim', explode(',', $options['patterns']));
    }

    $configNames = array_merge(
      $this->configFactory->listAll('core.entity_view_display.'),
      $this->configFactory->listAll('core.entity_form_display.')
    );

    foreach ($configNames as $configName) {
      $config = $this->configFactory->get($configName)->getRawData();

      $id = $config['id'] ?? $configName;
      $entityType = $config['targetEntityType'] ?? '?';
      $bundle = $config['bundle'] ?? '?';
      $mode = $config['mode'] ?? '?';

      $fieldGroups = $config['third_party_settings']['field_group'] ?? [];
      if (empty($fieldGroups)) {
        continue;
      }

      foreach ($fieldGroups as $groupName => $groupConfig) {
        $classes = $groupConfig['format_settings']['classes'] ?? '';
        $attributes = $groupConfig['format_settings']['attributes'] ?? '';

        $fieldsInGroup = $this->getFieldsFromGroup($groupConfig, $fieldGroups);

        foreach ($patterns as $pattern) {
          if (stripos($classes, $pattern) !== FALSE || stripos($attributes, $pattern) !== FALSE) {
            $matches[] = [
              'entity' => $entityType,
              'bundle' => $bundle,
              'mode' => $mode,
              'group' => $groupName,
              'match' => $pattern,
              'config' => $id,
              'fields' => implode(', ', $fieldsInGroup),
            ];
          }
        }
      }
    }

    if (empty($matches)) {
      $this->io()->success('No deprecated Arizona Bootstrap 2 classes or data attributes found in field groups.');
    }

    return $matches;
  }

  /**
   * Update command.
   */
  #[CLI\Command(name: 'bs5-entity-display-groups:update', aliases: ['bs5edg:update'])]
  #[CLI\Option(name: 'dry-run', description: 'Show proposed replacements without saving.')]
  #[CLI\Option(name: 'yes', description: 'Apply all replacements non-interactively.')]
  #[CLI\Usage(name: 'drush bs5-entity-display-groups:update', description: 'Interactively replace deprecated Arizona Bootstrap 2 classes/attributes in field_group settings for entity displays.')]
  #[CLI\Usage(name: 'drush bs5-entity-display-groups:update --dry-run', description: 'Preview changes without modifying configs.')]
  #[CLI\Usage(name: 'drush bs5-entity-display-groups:update --yes', description: 'Apply all replacements non-interactively.')]
  #[CLI\DefaultTableFields(fields: ['entity', 'bundle', 'mode', 'group', 'old', 'new', 'fields', 'config'])]
  #[CLI\FieldLabels(labels: [
    'entity' => 'Entity',
    'bundle' => 'Bundle',
    'mode' => 'Mode',
    'group' => 'Field Group',
    'old' => 'Old Value',
    'new' => 'New Value',
    'fields' => 'Fields in Group',
    'config' => 'Config ID',
  ])]
  public function update(array $options = ['dry-run' => FALSE, 'yes' => FALSE]): array {
    $dryRun = (bool) $options['dry-run'];
    $nonInteractive = (bool) $options['yes'];
    $changes = [];

    // Build replacement map: Arizona Bootstrap 2 → AZBootstrap.
    $replacementMap = AZBootstrapMarkupConverter::CLASS_MAP;
    foreach (AZBootstrapMarkupConverter::LEGACY_DATA_ATTRIBUTES as $attr) {
      $replacementMap['data-' . $attr] = 'data-bs-' . $attr;
    }

    $configNames = array_merge(
      $this->configFactory->listAll('core.entity_view_display.'),
      $this->configFactory->listAll('core.entity_form_display.')
    );

    foreach ($configNames as $configName) {
      $config = $this->configFactory->getEditable($configName);
      $raw = $config->getRawData();

      $id = $raw['id'] ?? $configName;
      $entityType = $raw['targetEntityType'] ?? '?';
      $bundle = $raw['bundle'] ?? '?';
      $mode = $raw['mode'] ?? '?';

      $fieldGroups = $raw['third_party_settings']['field_group'] ?? [];
      if (empty($fieldGroups)) {
        continue;
      }

      $updated = FALSE;

      foreach ($fieldGroups as $groupName => &$groupConfig) {
        $classes = $groupConfig['format_settings']['classes'] ?? '';
        $attributes = $groupConfig['format_settings']['attributes'] ?? '';

        $fieldsInGroup = $this->getFieldsFromGroup($groupConfig, $fieldGroups);
        $groupChanged = FALSE;

        foreach ($replacementMap as $old => $new) {
          if (stripos($classes, $old) !== FALSE) {
            $apply = $nonInteractive ?: $this->io()->askQuestion(
              new ConfirmationQuestion("Replace '{$old}' with '{$new}' in classes for group '{$groupName}' ({$id})? [y/N] ", FALSE)
            );

            if ($apply) {
              $classes = str_ireplace($old, $new, $classes);
              $groupChanged = TRUE;
              $changes[] = [
                'entity' => $entityType,
                'bundle' => $bundle,
                'mode' => $mode,
                'group' => $groupName,
                'old' => $old,
                'new' => $new,
                'fields' => implode(', ', $fieldsInGroup),
                'config' => $id,
              ];
            }
          }

          if (stripos($attributes, $old) !== FALSE) {
            $apply = $nonInteractive ?: $this->io()->askQuestion(
              new ConfirmationQuestion("Replace '{$old}' with '{$new}' in attributes for group '{$groupName}' ({$id})? [y/N] ", FALSE)
            );

            if ($apply) {
              $attributes = str_ireplace($old, $new, $attributes);
              $groupChanged = TRUE;
              $changes[] = [
                'entity' => $entityType,
                'bundle' => $bundle,
                'mode' => $mode,
                'group' => $groupName,
                'old' => $old,
                'new' => $new,
                'fields' => implode(', ', $fieldsInGroup),
                'config' => $id,
              ];
            }
          }
        }

        if ($groupChanged && !$dryRun) {
          $groupConfig['format_settings']['classes'] = $classes;
          $groupConfig['format_settings']['attributes'] = $attributes;
          $updated = TRUE;
        }
      }
      unset($groupConfig);

      if ($updated && !$dryRun) {
        $config->setData($raw)->save();
        $this->io()->success("Updated config: {$id}");
      }
    }

    if (empty($changes)) {
      $this->io()->success('No Arizona Bootstrap 2 classes or attributes to replace.');
    }

    return $changes;
  }

}
