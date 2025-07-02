<?php

namespace Drupal\upgrade_status\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\Extension;
use Drupal\upgrade_status\DeprecationAnalyzer;
use Drupal\upgrade_status\ProjectCollector;
use Drupal\upgrade_status\ScanResultFormatter;
use Drush\Commands\DrushCommands;
use Drush\Drupal\DrupalUtil;
use Drush\Exceptions\CommandFailedException;

/**
 * Upgrade Status Drush command
 */
class UpgradeStatusCommands extends DrushCommands {

  /**
   * The scan result formatter service.
   *
   * @var \Drupal\upgrade_status\ScanResultFormatter
   */
  protected $resultFormatter;

  /**
   * The project collector service.
   *
   * @var \Drupal\upgrade_status\ProjectCollector
   */
  protected $projectCollector;

  /**
   * The codebase analyzer service.
   *
   * @var \Drupal\upgrade_status\DeprecationAnalyzer
   */
  protected $deprecationAnalyzer;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new UpgradeStatusCommands object.
   *
   * @param \Drupal\upgrade_status\ScanResultFormatter $result_formatter
   *   The scan result formatter service.
   * @param \Drupal\upgrade_status\ProjectCollector $project_collector
   *   The project collector service.
   * @param \Drupal\upgrade_status\DeprecationAnalyzer $deprecation_analyzer
   *   The codebase analyzer service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
    ScanResultFormatter $result_formatter,
    ProjectCollector $project_collector,
    DeprecationAnalyzer $deprecation_analyzer,
    DateFormatterInterface $date_formatter) {
    $this->projectCollector = $project_collector;
    $this->resultFormatter = $result_formatter;
    $this->deprecationAnalyzer = $deprecation_analyzer;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('upgrade_status.result_formatter'),
      $container->get('upgrade_status.project_collector'),
      $container->get('upgrade_status.deprecation_analyzer'),
      $container->get('date.formatter')
    );
  }

  /**
   * Analyze projects and output results in selected format.
   *
   * @param array $projects
   *   List of projects to analyze.
   * @param array $options
   *   Additional options for the command.
   * @return \Consolidation\AnnotatedCommand\CommandResult;
   *   Exit code of self::EXIT_SUCCESS if no errors found,
   *   self::EXIT_FAILURE_WITH_CLARITY if at least one error found.
   *
   * @command upgrade_status:analyze
   * @option all Analyze all projects.
   * @option skip-existing Return results from a previous scan of a project if available, otherwise start a new one.
   * @option ignore-uninstalled Ignore uninstalled projects.
   * @option ignore-contrib Ignore contributed projects.
   * @option ignore-custom Ignore custom projects.
   * @option ignore-list Ignore a list of comma-separated projects.
   * @option phpstan-memory-limit Set memory limit for PHPStan.
   * @option format Set the format: plain, checkstyle or codeclimate.
   * @aliases us-a
   *
   * @throws \InvalidArgumentException
   *   Thrown when one of the passed arguments is invalid or no arguments were provided.
   */
  public function analyze(array $projects, array $options = ['all' => FALSE, 'skip-existing' => FALSE, 'ignore-uninstalled' => FALSE, 'ignore-contrib' => FALSE, 'ignore-custom' => FALSE, 'ignore-list' => '', 'phpstan-memory-limit' => '1500M', 'format' => 'plain']) {
    $extensions = $this->doAnalyze($projects, $options);

    $found_issue = FALSE;
    switch ($options['format']) {
      case 'checkstyle':
        $found_issue = $this->formatAllAsCheckStyle($extensions);
        break;
      case 'codeclimate':
        $found_issue = $this->formatAllAsCodeClimate($extensions);
        break;
      default:
        $found_issue = $this->formatAllAsPlain($extensions);
    }

    return CommandResult::exitCode($found_issue ? self::EXIT_FAILURE_WITH_CLARITY : self::EXIT_SUCCESS);
  }

  /**
   * Analyze projects and output results in checkstyle XML.
   *
   * @param array $projects
   *   List of projects to analyze.
   * @param array $options
   *   Additional options for the command.
   *
   * @command upgrade_status:checkstyle
   * @option all Analyze all projects.
   * @option skip-existing Return results from a previous scan of a project if available, otherwise start a new one.
   * @option ignore-uninstalled Ignore uninstalled projects.
   * @option ignore-contrib Ignore contributed projects.
   * @option ignore-custom Ignore custom projects.
   * @option ignore-list Ignore a list of comma-separated projects.
   * @option phpstan-memory-limit Set memory limit for PHPStan.
   * @aliases us-cs
   *
   * @throws \InvalidArgumentException
   *   Thrown when one of the passed arguments is invalid or no arguments were provided.
   */
  public function checkstyle(array $projects, array $options = ['all' => FALSE, 'skip-existing' => FALSE, 'ignore-uninstalled' => FALSE, 'ignore-contrib' => FALSE, 'ignore-custom' => FALSE, 'ignore-list' => '', 'phpstan-memory-limit' => '1500M']) {

    $this->logger()->notice('The checkstyle (us-cs) drush command is deprecated and will be removed. Use the analyze command with --format=checkstyle instead.');
    $options['format'] = 'checkstyle';
    return $this->analyze($projects, $options);
  }

  /**
   * Formats command output as checkstyle XML.
   *
   * @param array $extensions
   *   Result data by extension.
   * @return bool
   *   Whether issues were found.
   */
  protected function formatAllAsCheckStyle(array $extensions) {
    $xml = new \SimpleXMLElement("<?xml version='1.0'?><checkstyle/>");

    $found_issue = FALSE;
    foreach ($extensions as $list) {
      foreach ($list as $name => $extension) {
        $result = $this->resultFormatter->getRawResult($extension);

        if (is_null($result)) {
          $found_issue = TRUE;
          $this->logger()->error('Project scan @name failed.', ['@name' => $name]);
          continue;
        }

        foreach ($result['data']['files'] as $filepath => $errors) {
          $found_issue = TRUE;
          $short_path = str_replace(DRUPAL_ROOT . '/', '', $filepath);
          $file_xml = $xml->addChild('file');
          $file_xml->addAttribute('name', $short_path);
          foreach ($errors['messages'] as $error) {
            $severity = 'error';
            if ($error['upgrade_status_category'] == 'ignore') {
              $severity = 'info';
            }
            elseif ($error['upgrade_status_category'] == 'later') {
              $severity = 'warning';
            }
            $error_xml = $file_xml->addChild('error');
            $error_xml->addAttribute('line', $error['line']);
            $error_xml->addAttribute('message', $error['message']);
            $error_xml->addAttribute('severity', $severity);
          }
        }
      }
    }

    $this->output()->writeln($xml->asXML());
    return $found_issue;
  }

  /**
   * Formats command output as plain text tables.
   *
   * @param array $extensions
   *   Result data by extension.
   * @return bool
   *   Whether issues were found.
   */
  protected function formatAllAsPlain(array $extensions) {
    $found_issue = FALSE;
    foreach ($extensions as $list) {
      $this->output()->writeln('');
      $this->output()->writeln(str_pad('', 80, '='));

      foreach ($list as $name => $extension) {
        $result = $this->resultFormatter->getRawResult($extension);

        if (is_null($result)) {
          $found_issue = TRUE;
          $this->logger()->error('Project scan @name failed.', ['@name' => $name]);
          continue;
        }

        $output = $this->formatExtensionAsPlain($extension, $result);
        foreach ($output['table'] as $line) {
          $this->output()->writeln($line);
        }
        // If we did not find an extension with an issue earlier, use the result
        // from this extension.
        if (!$found_issue) {
          $found_issue = $output['found_issue'];
        }
      }
    }
    return $found_issue;
  }

  /**
   * Analyzes projects and returns results for processed extensions.
   *
   * @param array $projects
   *   List of projects to analyze.
   * @param array $options
   *   Additional options for the command.
   *
   * @throws \InvalidArgumentException
   *   Thrown when one of the passed arguments is invalid or no arguments were provided.
   * @throws Drush\Exceptions\CommandFailedException
   *   Thrown when the environment is not ready to run the analysis.
   */
  protected function doAnalyze(array $projects, array $options = ['all' => FALSE, 'skip-existing' => FALSE, 'ignore-uninstalled' => FALSE, 'ignore-contrib' => FALSE, 'ignore-custom' => FALSE, 'ignore-list' => '', 'phpstan-memory-limit' => '1500M']) {
    try {
      $this->deprecationAnalyzer->initEnvironment();
    }
    catch (\Exception $e) {
      throw new CommandFailedException($e->getMessage() . ' ' . dt('Analysis is not possible until this is resolved.'));
    }

    $extensions = [];
    $invalid_names = [];

    if (empty($projects) && !$options['all']) {
      $message = dt('You need to provide at least one installed project\'s machine_name.');
      throw new \InvalidArgumentException($message);
    }

    // Gather project list grouped by custom and contrib projects.
    $available_projects = $this->projectCollector->collectProjects();

    if ($options['all']) {
      $projects_to_ignore = explode(',', $options['ignore-list']);
      foreach ($available_projects as $name => $project) {
        if ($options['ignore-uninstalled'] && $project->status === 0) {
          continue;
        }
        if ($options['ignore-contrib'] && $project->info['upgrade_status_type'] == ProjectCollector::TYPE_CONTRIB) {
          continue;
        }
        if ($options['ignore-custom'] && $project->info['upgrade_status_type'] == ProjectCollector::TYPE_CUSTOM) {
          continue;
        }
        if (in_array($name, $projects_to_ignore)) {
          continue;
        }
        $extensions[$project->getType()][$name] = $project;
      }
    }
    else {
      foreach ($projects as $name) {
        if (!isset($available_projects[$name])) {
          $invalid_names[] = $name;
          continue;
        }
        if ($options['ignore-uninstalled'] && $available_projects[$name]->status === 0) {
          $invalid_names[] = $name;
          continue;
        }
        if ($options['ignore-contrib'] && $available_projects[$name]->info['upgrade_status_type'] == ProjectCollector::TYPE_CONTRIB) {
          $invalid_names[] = $name;
          continue;
        }
        if ($options['ignore-custom'] && $available_projects[$name]->info['upgrade_status_type'] == ProjectCollector::TYPE_CUSTOM) {
          $invalid_names[] = $name;
          continue;
        }
        $extensions[$available_projects[$name]->getType()][$name] = $available_projects[$name];
      }
    }

    if (!empty($invalid_names)) {
      if (count($invalid_names) == 1) {
        $message = dt('The project machine name @invalid_name is invalid. Is this a project on this site? (For community projects, use the machine name of the drupal.org project itself).', [
          '@invalid_name' => $invalid_names[0],
        ]);
      }
      else {
        $message = dt('The project machine names @invalid_names are invalid. Are these projects on this site? (For community projects, use the machine name of the drupal.org project itself).', [
          '@invalid_names' => implode(', ', $invalid_names),
        ]);
      }
      throw new \InvalidArgumentException($message);
    }
    else {
      $this->logger()->info(dt('Starting the analysis. This may take a while.'));
    }

    foreach ($extensions as $list) {
      foreach ($list as $name => $extension) {
        if ($options['skip-existing']) {
          $scan_result = \Drupal::service('keyvalue')->get('upgrade_status_scan_results')->get($name);
          if (!empty($scan_result)) {
            $this->logger()->info(dt('Using previous results for @name.', ['@name' => $name]));
            continue;
          }
        }
        $this->logger()->info(dt('Processing @name.', ['@name' => $name]));
        $this->deprecationAnalyzer->analyze($extension, $options);
      }
    }

    return $extensions;
  }

  /**
   * Format results output for an extension for Drush STDOUT usage.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   Drupal extension objet.
   * @param array $result
   *   Deprecation checking results.
   *
   * @return array
   *   Associative array with the 'table' key containing the ASCII
   *   output, and 'found_issue' key indicating whether issues were
   *   identified.
   */
  protected function formatExtensionAsPlain(Extension $extension, array $result) {
    $table = [];
    $info = $extension->info;

    $table[] = $info['name'] . ', ' . (!empty($info['version']) ? ' ' . $info['version'] : '--');
    $table[] = dt('Scanned on @date', [
      '@date' => $this->dateFormatter->format($result['date']),
    ]);

    if (isset($result['data']['totals'])) {
      $project_error_count = $result['data']['totals']['file_errors'];
    }
    else {
      $project_error_count = 0;
    }

    if (!$project_error_count || !is_array($result['data']['files'])) {
      $table[] = '';
      $table[] = dt('No known issues found.');
      $table[] = '';
      return ['table' => $table, 'found_issue' => FALSE];
    }

    foreach ($result['data']['files'] as $filepath => $errors) {
      // Remove the Drupal root directory name. If this is a composer setup,
      // then the webroot is in a web/ directory, add that back in for easy
      // path copy-pasting.
      $short_path = str_replace(DRUPAL_ROOT . '/', '', $filepath);
      if (preg_match('!/web$!', DRUPAL_ROOT)) {
        $short_path = 'web/' . $short_path;
      }
      $short_path = wordwrap(dt('FILE: ') . $short_path, 80, "\n", TRUE);

      $table[] = '';
      $table[] = $short_path;
      $table[] = '';
      $title_level = str_pad(dt('STATUS'), 15, ' ');
      $title_line = str_pad(dt('LINE'), 5, ' ');
      $title_msg = str_pad(dt('MESSAGE'), 60, ' ', STR_PAD_BOTH);
      $table[] = $title_level . $title_line . $title_msg;

      foreach ($errors['messages'] as $error) {
        $table[] = str_pad('', 80, '-');
        $error['message'] = str_replace("\n", ' ', $error['message']);
        $error['message'] = str_replace('  ', ' ', $error['message']);
        $error['message'] = trim($error['message']);

        $level_label = dt('Check manually');
        if ($error['upgrade_status_category'] == 'ignore') {
          $level_label = dt('Ignore');
        }
        elseif ($error['upgrade_status_category'] == 'later') {
          $level_label = dt('Fix later');
        }
        elseif (in_array($error['upgrade_status_category'], ['safe', 'old'])) {
          $level_label = dt('Fix now');
        }
        $linecount = 0;

        $msg_parts = explode("\n", wordwrap($error['message'], 60, "\n", TRUE));
        foreach ($msg_parts as $msg_part) {
          $msg_part = str_pad($msg_part, 60, ' ');
          if (!$linecount++) {
            $level_label = str_pad(substr($level_label, 0, 15), '15', ' ');
            $line = str_pad($error['line'], 5, ' ');
          }
          else {
            $level_label = str_pad(substr('', 0, 15), '15', ' ');
            $line = str_pad('', 5, ' ');
          }
          $table[] = $level_label . $line . $msg_part;
        }
      }

      $table[] = str_pad('', 80, '-');
    }
    $table[] = '';

    return ['table' => $table, 'found_issue' => TRUE];
  }

  /**
   * Formats command output as Code Climate issues JSON.
   *
   * @param array $extensions
   *   Result data by extension.
   * @return bool
   *   Whether issues were found.
   */
  protected function formatAllAsCodeClimate(array $extensions): bool {
    $found_issue = FALSE;
    $report = [];

    foreach ($extensions as $list) {
      foreach ($list as $name => $extension) {
        $result = $this->resultFormatter->getRawResult($extension);

        if (is_null($result)) {
          $found_issue = TRUE;
          $this->logger()->error('Project scan @name failed.', ['@name' => $name]);
          continue;
        }

        foreach ($result['data']['files'] as $filepath => $errors) {
          $found_issue = TRUE;
          $short_path = str_replace(DRUPAL_ROOT . '/', '', $filepath);
          foreach ($errors['messages'] as $error) {
            $severity = 'major';

            // We downgrade to 'info' severity, if:
            // - It has the ignore/later category, as these issues shouldn't be
            //   fixed now.
            // - It is not an error, but something unable to detect.
            if (
              in_array($error['upgrade_status_category'], ['ignore', 'later'], TRUE) ||
              str_contains($error['message'], 'Cannot decide if it is deprecated or not.') ||
              str_contains($error['message'], 'Cannot check deprecated library use.')
            ) {
              $severity = 'info';
            }

            // We downgrade to 'minor' severity, if:
            // - The category is 'uncategorized', because we might not need to
            //   fix it now.
            elseif ($error['upgrade_status_category'] == 'uncategorized') {
              $severity = 'minor';
            }

            $description = $name . ' - ' . $error['message'];

            $fingerprint = hash(
              'sha256',
              implode(
                [
                  $filepath,
                  $error['line'],
                  $error['message'],
                ]
              ));

            $report[] = [
              'type' => 'issue',
              'check_name' => $error['analyzer'],
              'categories' => ['Compatibility'],
              'description' => $description,
              'fingerprint' => $fingerprint,
              'severity' => $severity,
              'location' => [
                'path' => $short_path,
                'lines' => [
                  'begin' => $error['line'] ?: 0,
                ],
              ],
            ];

          }
        }
      }
    }

    $this->output()->writeln(json_encode($report, JSON_PRETTY_PRINT));
    return $found_issue;
  }

}
