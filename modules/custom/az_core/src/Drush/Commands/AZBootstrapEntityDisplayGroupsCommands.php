<?php

namespace Drupal\az_core\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\az_core\Utility\AZBootstrapMarkupConverter;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.manager')
    );
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
  public function check(array $options = []): RowsOfFields {
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

        // Check for exact matches in classes.
        $classMatches = $this->findExactMatches($classes, $patterns);
        foreach ($classMatches as $match) {
          $matches[] = [
            'entity' => $entityType,
            'bundle' => $bundle,
            'mode' => $mode,
            'group' => $groupName,
            'match' => $match,
            'config' => $configName,
            'fields' => implode(', ', $fieldsInGroup),
          ];
        }

        // Check for exact matches in attributes.
        $attributeMatches = $this->findExactMatches($attributes, $patterns);
        foreach ($attributeMatches as $match) {
          $matches[] = [
            'entity' => $entityType,
            'bundle' => $bundle,
            'mode' => $mode,
            'group' => $groupName,
            'match' => $match,
            'config' => $configName,
            'fields' => implode(', ', $fieldsInGroup),
          ];
        }
      }
    }

    return new RowsOfFields($matches);
  }

  /**
   * Update command.
   */
  #[CLI\Command(name: 'bs5-entity-display-groups:update', aliases: ['bs5edg:update'])]
  #[CLI\Option(name: 'dry-run', description: 'Show proposed replacements without saving.')]
  #[CLI\Usage(name: 'drush bs5-entity-display-groups:update', description: 'Interactively replace deprecated Arizona Bootstrap 2 classes/attributes in field_group settings for entity displays. Choose "yes-to-all" during prompts to apply remaining changes automatically.')]
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
  public function update(array $options = ['dry-run' => FALSE]): RowsOfFields {
    $dryRun = (bool) $options['dry-run'];
    $nonInteractive = $this->input()->getOption('yes');
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
          // Check for exact match in classes.
          if ($this->findExactMatches($classes, [$old])) {
            if ($nonInteractive) {
              $apply = TRUE;
            }
            else {
              $question = new ChoiceQuestion(
                "Replace '{$old}' with '{$new}' in classes for group '{$groupName}' ({$id})?",
                ['no', 'yes', 'yes-to-all'],
                0
                          );
              $question->setErrorMessage('Please select: %s');
              $answer = $this->io()->askQuestion($question);

              $apply = ($answer === 'yes' || $answer === 'yes-to-all');
              if ($answer === 'yes-to-all') {
                $nonInteractive = TRUE;
                $this->io()->note('Applying all remaining replacements automatically...');
              }
            }

            if ($apply) {
              $classes = $this->replaceExactMatch($classes, $old, $new);
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

          // Check for exact match in attributes.
          if ($this->findExactMatches($attributes, [$old])) {
            if ($nonInteractive) {
              $apply = TRUE;
            }
            else {
              $question = new ChoiceQuestion(
                "Replace '{$old}' with '{$new}' in attributes for group '{$groupName}' ({$id})?",
                ['no', 'yes', 'yes-to-all'],
                0
                          );
              $question->setErrorMessage('Please select: %s');
              $answer = $this->io()->askQuestion($question);

              $apply = ($answer === 'yes' || $answer === 'yes-to-all');
              if ($answer === 'yes-to-all') {
                $nonInteractive = TRUE;
                $this->io()->note('Applying all remaining replacements automatically...');
              }
            }

            if ($apply) {
              $attributes = $this->replaceExactMatch($attributes, $old, $new);
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
          $raw['third_party_settings']['field_group'][$groupName] = $groupConfig;
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

    return new RowsOfFields($changes);
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
   * Check if any patterns match exactly within a space-delimited string.
   *
   * @param string $input
   *   The input string (classes or attributes).
   * @param array $patterns
   *   Array of patterns to match against.
   *
   * @return array
   *   Array of matching patterns found.
   */
  protected function findExactMatches(string $input, array $patterns): array {
    if (empty($input)) {
      return [];
    }

    // Split input by spaces and filter out empty values.
    $inputTokens = array_filter(explode(' ', $input), fn($token) => !empty(trim($token)));
    $matches = [];

    foreach ($patterns as $pattern) {
      if (in_array($pattern, $inputTokens, TRUE)) {
        $matches[] = $pattern;
      }
    }

    return $matches;
  }

  /**
   * Replace exact matches in a space-delimited string.
   *
   * @param string $input
   *   The input string (classes or attributes).
   * @param string $old
   *   The old value to replace.
   * @param string $new
   *   The new value to replace with.
   *
   * @return string
   *   The updated string.
   */
  protected function replaceExactMatch(string $input, string $old, string $new): string {
    if (empty($input)) {
      return $input;
    }

    // Split by spaces, replace exact matches, then rejoin.
    $tokens = explode(' ', $input);
    $tokens = array_map(fn($token) => $token === $old ? $new : $token, $tokens);

    return implode(' ', $tokens);
  }

}
