<?php

namespace Drupal\az_core\Drush\Commands;

use Drupal\az_core\Utility\AZBootstrapMarkupConverter;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands for Arizona Bootstrap 5 updates.
 */
final class AzBs5Commands extends DrushCommands {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new AzBs5Commands object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct();
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Updates block classes for Arizona Bootstrap 5 compatibility.
   *
   * This command converts legacy Arizona Bootstrap 2 classes in block_class module
   * settings to their Arizona Bootstrap 5 equivalents using the existing
   * AZBootstrapMarkupConverter utility.
   */
  #[CLI\Command(name: 'azbs5:update-block-classes', aliases: ['azbs5bc'])]
  #[CLI\Option(name: 'dry-run', description: 'Show what would be updated without making changes')]
  #[CLI\Option(name: 'interactive', description: 'Interactively choose which blocks to update')]
  #[CLI\Usage(name: 'azbs5:update-block-classes', description: 'Update all block classes for Arizona Bootstrap 5 compatibility')]
  #[CLI\Usage(name: 'azbs5:update-block-classes --dry-run', description: 'Preview what would be updated without making changes')]
  #[CLI\Usage(name: 'azbs5:update-block-classes --interactive', description: 'Interactively choose which blocks to update')]
  public function updateBlockClasses($options = ['dry-run' => FALSE, 'interactive' => FALSE]) {
    $dry_run = $options['dry-run'];
    $interactive = $options['interactive'];
    $updated_configs = [];
    $preview_configs = [];
    $skipped_configs = [];

    // Get all block configurations.
    $block_configs = $this->configFactory->listAll('block.block.');

    $this->output()->writeln(sprintf('Scanning %d block configurations...', count($block_configs)));

    foreach ($block_configs as $config_name) {
      $config = $this->configFactory->getEditable($config_name);
      $data = $config->getRawData();

      // Check if this block has block_class settings.
      if (isset($data['third_party_settings']['block_class']['classes'])) {
        $original_classes = $data['third_party_settings']['block_class']['classes'];

        // Convert classes using the existing utility.
        $converted_classes = $this->convertBlockClasses($original_classes);

        // Only process if classes actually changed.
        if ($original_classes !== $converted_classes) {
          $change_info = [
            'config' => $config_name,
            'original' => $original_classes,
            'converted' => $converted_classes,
          ];

          if ($interactive && !$dry_run) {
            // Interactive mode: ask user for each block
            $this->output()->writeln('');
            $this->output()->writeln(sprintf('<comment>Block:</comment> %s', $config_name));
            $this->output()->writeln(sprintf('<comment>Current classes:</comment> "%s"', $original_classes));
            $this->output()->writeln(sprintf('<comment>Updated classes:</comment> "%s"', $converted_classes));

            $answer = $this->io()->choice(
              'Update this block?',
              ['yes' => 'Yes, update it', 'no' => 'No, skip it', 'quit' => 'Quit without further updates'],
              'yes'
            );

            if ($answer === 'quit') {
              $this->output()->writeln('<info>Stopping at user request.</info>');
              break;
            } elseif ($answer === 'yes') {
              $data['third_party_settings']['block_class']['classes'] = $converted_classes;
              $config->setData($data);
              $config->save(TRUE);
              $updated_configs[] = $change_info;
              $this->output()->writeln('<info>✓ Updated</info>');
            } else {
              $skipped_configs[] = $change_info;
              $this->output()->writeln('<comment>⚬ Skipped</comment>');
            }
          } elseif ($dry_run || $interactive) {
            $preview_configs[] = $change_info;
          } else {
            // Non-interactive mode: update automatically
            $data['third_party_settings']['block_class']['classes'] = $converted_classes;
            $config->setData($data);
            $config->save(TRUE);
            $updated_configs[] = $change_info;
          }
        }
      }
    }

    // Display results based on mode
    if ($interactive && !$dry_run) {
      $this->displayInteractiveResults($updated_configs, $skipped_configs);
    } elseif ($dry_run || ($interactive && $dry_run)) {
      $this->displayDryRunResults($preview_configs);
    } else {
      $this->displayUpdateResults($updated_configs);
    }
  }

  /**
   * Helper function to convert block classes using AZBootstrapMarkupConverter.
   *
   * @param string $classes
   *   Space-separated string of CSS classes.
   *
   * @return string
   *   The converted class string with Arizona Bootstrap 5 compatible classes.
   */
  protected function convertBlockClasses($classes) {
    if (empty($classes) || !is_string($classes)) {
      return $classes;
    }

    // Split classes into array, trim whitespace, and filter empty values.
    $class_array = array_filter(array_map('trim', explode(' ', $classes)));
    $updated = FALSE;

    // Convert each class using the existing CLASS_MAP from AZBootstrapMarkupConverter.
    foreach ($class_array as $index => $class) {
      if (isset(AZBootstrapMarkupConverter::CLASS_MAP[$class])) {
        $class_array[$index] = AZBootstrapMarkupConverter::CLASS_MAP[$class];
        $updated = TRUE;
      }
    }

    // Return the converted classes as a space-separated string.
    return $updated ? implode(' ', $class_array) : $classes;
  }

  /**
   * Display results for interactive mode.
   */
  protected function displayInteractiveResults(array $updated_configs, array $skipped_configs) {
    $this->output()->writeln('');
    $this->output()->writeln('<info>Interactive update completed!</info>');

    if (!empty($updated_configs)) {
      $this->output()->writeln('');
      $this->output()->writeln('<info>Updated blocks:</info>');
      foreach ($updated_configs as $change) {
        $this->output()->writeln(sprintf(
          '  <info>✓</info> <comment>%s</comment>: "%s" → "%s"',
          $change['config'],
          $change['original'],
          $change['converted']
        ));
      }
    }

    if (!empty($skipped_configs)) {
      $this->output()->writeln('');
      $this->output()->writeln('<comment>Skipped blocks:</comment>');
      foreach ($skipped_configs as $change) {
        $this->output()->writeln(sprintf(
          '  <comment>⚬</comment> <comment>%s</comment>: "%s" → "%s"',
          $change['config'],
          $change['original'],
          $change['converted']
        ));
      }
    }

    $this->output()->writeln('');
    $this->output()->writeln(sprintf(
      '<info>Summary: %d updated, %d skipped</info>',
      count($updated_configs),
      count($skipped_configs)
    ));
  }

  /**
   * Display results for dry run mode.
   */
  protected function displayDryRunResults(array $preview_configs) {
    if (!empty($preview_configs)) {
      $this->output()->writeln('');
      $this->output()->writeln('<info>The following block classes would be updated:</info>');
      foreach ($preview_configs as $change) {
        $this->output()->writeln(sprintf(
          '  <comment>%s</comment>: "%s" → "%s"',
          $change['config'],
          $change['original'],
          $change['converted']
        ));
      }
      $this->output()->writeln('');
      $this->output()->writeln(sprintf('<info>Total: %d configurations would be updated.</info>', count($preview_configs)));
      $this->output()->writeln('<info>Run without --dry-run to apply these changes.</info>');
      $this->output()->writeln('<info>Use --interactive to choose updates one by one.</info>');
    } else {
      $this->output()->writeln('<info>No block class configurations need updating.</info>');
    }
  }

  /**
   * Display results for regular update mode.
   */
  protected function displayUpdateResults(array $updated_configs) {
    if (!empty($updated_configs)) {
      $this->output()->writeln('');
      $this->output()->writeln('<info>Updated block classes for Arizona Bootstrap 5 compatibility:</info>');
      foreach ($updated_configs as $change) {
        $this->output()->writeln(sprintf(
          '  <comment>%s</comment>: "%s" → "%s"',
          $change['config'],
          $change['original'],
          $change['converted']
        ));
      }
      $this->output()->writeln('');
      $this->output()->writeln(sprintf('<info>Successfully updated %d configurations.</info>', count($updated_configs)));
    } else {
      $this->output()->writeln('<info>No block class configurations required updating.</info>');
    }
  }

}
