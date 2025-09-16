<?php

namespace Drupal\az_core\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\az_core\Utility\AZBootstrapMarkupConverter;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Commands for updating AZ Bootstrap 2 attributes in field group settings.
 */
final class AZBootstrapEntityDisplayGroupsCommands extends DrushCommands {

  /**
   * The config factory service.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The config manager service.
   */
  protected ConfigManagerInterface $configManager;

  /**
   * Constructs the command.
   */
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
   * Update deprecated AZ Bootstrap 2 attributes in field group settings.
   *
   * This command scans entity display configurations for field groups
   * containing deprecated Arizona Bootstrap 2 classes and data-* attributes,
   * then optionally replaces them with Bootstrap 5 equivalents. Use --dry-run
   * to preview changes.
   */
  #[CLI\Command(name: 'bs5-entity-display-groups:update', aliases: ['bs5edg:update'])]
  #[CLI\Option(name: 'dry-run', description: 'Show proposed replacements without saving.')]
  #[CLI\Usage(name: 'drush bs5edg:update', description: 'Interactively replace deprecated Arizona Bootstrap 2 classes/attributes in field_group settings.')]
  #[CLI\Usage(name: 'drush bs5edg:update --dry-run', description: 'Preview proposed replacements without modifying configs.')]
  #[CLI\Usage(name: 'drush bs5edg:update --yes', description: 'Apply all replacements non-interactively.')]
  #[CLI\DefaultTableFields(fields: ['config', 'group', 'setting', 'match', 'new'])]
  #[CLI\FieldLabels(labels: [
    'config' => 'Config ID',
    'group' => 'Field Group',
    'setting' => 'Format Setting',
    'match' => 'Matched Pattern',
    'new' => 'Replacement',
  ])]
  public function update(array $options = ['dry-run' => FALSE]): RowsOfFields {
    $dryRun = (bool) $options['dry-run'];
    $nonInteractive = $this->input()->getOption('yes');
    $results = [];

    // Build replacement map for all AZ Bootstrap 2 to 5 conversions.
    $replacementMap = [];

    // Add class mappings.
    foreach (AZBootstrapMarkupConverter::CLASS_MAP as $oldClass => $newClass) {
      $replacementMap[$oldClass] = $newClass;
    }

    // Add data attribute mappings (data-* to data-bs-*)
    foreach (AZBootstrapMarkupConverter::LEGACY_DATA_ATTRIBUTES as $attribute) {
      $oldAttribute = 'data-' . $attribute;
      $newAttribute = 'data-bs-' . $attribute;
      $replacementMap[$oldAttribute] = $newAttribute;
    }

    if (count($replacementMap) === 0) {
      $this->io()->warning('No valid patterns found to check.');
      return new RowsOfFields([]);
    }

    // Get all entity display configs.
    $configNames = array_merge(
      $this->configFactory->listAll('core.entity_view_display.'),
      $this->configFactory->listAll('core.entity_form_display.')
    );

    foreach ($configNames as $configName) {
      $config = $this->configFactory->getEditable($configName);
      $raw = $config->getRawData();

      $fieldGroups = $raw['third_party_settings']['field_group'] ?? [];
      if (empty($fieldGroups)) {
        continue;
      }

      $configUpdated = FALSE;

      foreach ($fieldGroups as $groupName => &$groupConfig) {
        // Check for replacements and optionally apply them.
        $groupChanged = FALSE;

        // Define the format_settings that can contain classes or attributes.
        $classFields = ['classes', 'label_element_classes'];
        $attributeFields = ['attributes'];

        // Process class fields.
        foreach ($classFields as $fieldName) {
          if (isset($groupConfig['format_settings'][$fieldName])) {
            $value = $groupConfig['format_settings'][$fieldName];
            if (!empty(trim($value))) {
              // For classes, parse tokens by space.
              $tokens = array_filter(array_map('trim', explode(' ', $value)));

              // Check each token against replacement map.
              foreach ($tokens as $token) {
                if (isset($replacementMap[$token])) {
                  $newToken = $replacementMap[$token];

                  // Ask for confirmation if interactive and not dry-run.
                  if (!$nonInteractive && !$dryRun) {
                    $apply = $this->io()->confirm("Replace '{$token}' with '{$newToken}' in classes for group '{$groupName}' ({$configName})?");
                    if (!$apply) {
                      continue;
                    }
                  }

                  // Apply the replacement.
                  if (!$dryRun) {
                    $groupConfig['format_settings'][$fieldName] = $this->replaceExactMatch($groupConfig['format_settings'][$fieldName], $token, $newToken);
                  }

                  $results[] = [
                    'config' => $configName,
                    'group' => $groupName,
                    'setting' => $fieldName,
                    'match' => $token,
                    'new' => $newToken,
                  ];

                  $groupChanged = TRUE;
                }
              }
            }
          }
        }

        // Process attribute fields.
        foreach ($attributeFields as $fieldName) {
          if (isset($groupConfig['format_settings'][$fieldName])) {
            $value = $groupConfig['format_settings'][$fieldName];
            if (!empty(trim($value))) {
              // For attributes, check for data-* attribute patterns.
              foreach ($replacementMap as $old => $new) {
                // Check for exact match patterns in the attributes string.
                if (stripos($value, $old) !== FALSE) {
                  // Ask for confirmation if interactive and not dry-run.
                  if (!$nonInteractive && !$dryRun) {
                    $apply = $this->io()->confirm("Replace '{$old}' with '{$new}' in attributes for group '{$groupName}' ({$configName})?");
                    if (!$apply) {
                      continue;
                    }
                  }

                  // Apply the replacement.
                  if (!$dryRun) {
                    $groupConfig['format_settings'][$fieldName] = str_ireplace($old, $new, $groupConfig['format_settings'][$fieldName]);
                  }

                  $results[] = [
                    'config' => $configName,
                    'group' => $groupName,
                    'setting' => $fieldName,
                    'match' => $old,
                    'new' => $new,
                  ];

                  $groupChanged = TRUE;
                }
              }
            }
          }
        }

        if ($groupChanged && !$dryRun) {
          $raw['third_party_settings']['field_group'][$groupName] = $groupConfig;
          $configUpdated = TRUE;
        }
      }
      unset($groupConfig);

      if ($configUpdated && !$dryRun) {
        $config->setData($raw)->save();
        $this->io()->success("Updated config: {$configName}");
      }
    }

    if (empty($results)) {
      $this->io()->success('No Arizona Bootstrap 2 classes or attributes found to replace.');
    }

    return new RowsOfFields($results);
  }

  /**
   * Replace exact matches in a string.
   */
  protected function replaceExactMatch(string $input, string $old, string $new): string {
    if (empty($input)) {
      return $input;
    }

    $tokens = preg_split('/\s+/', trim($input), -1, PREG_SPLIT_NO_EMPTY);
    $updated = [];

    foreach ($tokens as $token) {
      $updated[] = ($token === $old) ? $new : $token;
    }

    return implode(' ', array_filter($updated));
  }

}
