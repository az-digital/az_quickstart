<?php

namespace Drupal\upgrade_status;

use Composer\Semver\VersionParser;
use Composer\Semver\Constraint\Constraint;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ProfileExtensionList;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactory;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Collects projects and their associated metadata collated for Upgrade Status.
 */
class ProjectCollector {

  use StringTranslationTrait;

  /**
   * The list of available modules.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The list of available themes.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * The list of available profiles.
   *
   * @var \Drupal\Core\Extension\ProfileExtensionList
   */
  protected $profileExtensionList;

  /**
   * Available updates store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface|mixed
   */
  protected $availableUpdates;

  /**
   * Configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current installation profile.
   *
   * @var string
   */
  private $installProfile;

  /**
   * Update not checked for a project.
   */
  const UPDATE_NOT_CHECKED = 0;

  /**
   * Update not available for a project.
   */
  const UPDATE_NOT_AVAILABLE = 1;

  /**
   * Update available for a project.
   */
  const UPDATE_AVAILABLE = 2;

  /**
   * The latest version is already being used.
   */
  const UPDATE_ALREADY_INSTALLED = 3;

  /**
   * Custom project.
   */
  const TYPE_CUSTOM = 'custom';

  /**
   * Contributed project.
   */
  const TYPE_CONTRIB = 'contrib';

  /**
   * Suggest to relax.
   */
  const NEXT_RELAX = 'relax';

  /**
   * Suggest to remove.
   */
  const NEXT_REMOVE = 'remove';

  /**
   * Suggest to update.
   */
  const NEXT_UPDATE = 'update';

  /**
   * Suggest to collaborate with maintainer.
   */
  const NEXT_COLLABORATE = 'collaborate';

  /**
   * Suggest to scan for errors.
   */
  const NEXT_SCAN = 'scan';

  /**
   * Suggest to fix with rector.
   */
  const NEXT_RECTOR = 'rector';

  /**
   * Suggest to fix with manually.
   */
  const NEXT_MANUAL = 'manual';

  /**
   * Summary category for things to analyze.
   */
  const SUMMARY_ANALYZE = 'analyze';

  /**
   * Summary category for things to act on.
   */
  const SUMMARY_ACT = 'act';

  /**
   * Summary category for things to act on.
   */
  const SUMMARY_RELAX = 'relax';

  /**
   * Constructs a \Drupal\upgrade_status\ProjectCollector.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list service.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_extension_list
   *   The theme extension handler service.
   * @param \Drupal\Core\Extension\ProfileExtensionList $profile_extension_list
   *   The profile extension handler service.
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_expirable
   *   The expirable key/value storage.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param string $install_profile
   *   The current installation profile.
   */
  public function __construct(
    ModuleExtensionList $module_extension_list,
    ThemeExtensionList $theme_extension_list,
    ProfileExtensionList $profile_extension_list,
    KeyValueExpirableFactoryInterface $key_value_expirable,
    ConfigFactoryInterface $config_factory,
    $install_profile
  ) {
    $this->moduleExtensionList = $module_extension_list;
    $this->themeExtensionList = $theme_extension_list;
    $this->profileExtensionList = $profile_extension_list;
    $this->availableUpdates = $key_value_expirable->get('update_available_releases');
    $this->configFactory = $config_factory;
    $this->installProfile = $install_profile;
  }

  /**
   * Reset all extension lists so their data is regenerated.
   */
  public function resetLists() {
    $this->moduleExtensionList->reset();
    $this->themeExtensionList->reset();
    $this->profileExtensionList->reset();
  }

  /**
   * Collect projects of installed modules grouped by custom and contrib.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   An array keyed by project names. Extensions selected as projects
   *   without a defined project name get one based on their topmost parent
   *   extension and only that topmost extension gets included in the list.
   */
  public function collectProjects() {
    $projects = [];
    $modules = $this->moduleExtensionList->getList();
    $themes = $this->themeExtensionList->getList();
    $profiles = $this->profileExtensionList->getList();
    $extensions = array_merge($modules, $themes, $profiles);
    unset($modules, $themes, $profiles);
    $update_check_for_uninstalled = $this->configFactory->get('update.settings')->get('check.disabled_extensions');

    /** @var \Drupal\Core\Extension\Extension $extension */
    foreach ($extensions as $key => $extension) {

      if ($extension->origin === 'core') {
        // Ignore core extensions for the sake of upgrade status.
        continue;
      }

      // If the project is already specified in this extension, use that.
      $project = isset($extension->info['project']) ? $extension->info['project'] : '';
      if (isset($projects[$project])) {
        // If we already have a representative of this project in the list,
        // don't add this extension.
        // @todo Make sure to use the extension with the shortest file path.

        // If the existing project was already Drupal 9 compatible, consider
        // this subcomponent as well. If this component was enabled, it would
        // affect how we consider the Drupal 9 compatibility.
        if (!empty($projects[$project]->info['upgrade_status_next_major_compatible']) && !empty($extension->status)) {
          // Overwrite compatibility. If this is still compatible, it will
          // keep being TRUE, otherwise FALSE.
          $projects[$project]->info['upgrade_status_next_major_compatible'] =
            isset($extension->info['core_version_requirement']) &&
            self::isCompatibleWithNextMajorDrupal($extension->info['core_version_requirement']);
        }
        continue;
      }

      if ((strpos($key, 'upgrade_status') === 0) && !drupal_valid_test_ua()) {
        // Don't add the Upgrade Status modules to the list if not in tests.
        // Upgrade status is a temporary site component and does have
        // intentional deprecated API use for the sake of testing. Avoid
        // distracting site owners with this.
        continue;
      }

      // Attempt to identify if the project was contrib based on the directory
      // structure it is in. Extension placement is not a mandatory requirement
      // and theoretically this could lead to false positives, but if
      // composer_deploy or git_deploy is not available (and/or did not
      // identify the project for us), this is all we can do. Ignore our test
      // modules for this scenario.
      if (empty($project)) {
        $type = self::TYPE_CUSTOM;
        if (strpos($extension->getPath(), '/contrib/') && (strpos($key, 'upgrade_status_test_') !== 0)) {
          $type = self::TYPE_CONTRIB;
        }
      }
      // Extensions that have the 'drupal' project but did not have the 'core'
      // origin assigned are custom extensions that are running in a Drupal
      // core git checkout, so also categorize them as custom.
      elseif ($project === 'drupal') {
        $type = self::TYPE_CUSTOM;
      }
      else {
        $type = self::TYPE_CONTRIB;
      }

      // Add additional information to the extension info for our tracking.
      // Keep this on a cloned extension object so we are not polluting runtime
      // extension information elsewhere.
      $extdata = clone $extension;
      $extdata->info['upgrade_status_type'] = $type;
      $extdata->info['upgrade_status_next_major_compatible'] =
        isset($extdata->info['core_version_requirement']) &&
        self::isCompatibleWithNextMajorDrupal($extdata->info['core_version_requirement']);

      // Save this as a possible project to consider.
      $projects[$key] = $extdata;
    }

    // Collate extensions to projects, removing sub-extensions.
    $projects = $this->collateExtensionsIntoProjects($projects);

    // After the collation is done, assign project names based on the topmost
    // extension. While this is not always right for drupal.org projects, this
    // is the best guess we have.
    foreach ($projects as $name => $extension) {
      if (!isset($extension->info['project'])) {
        $projects[$name]->info['project'] = $name;
      }

      // Add available update information to contrib projects found.
      if ($extension->info['upgrade_status_type'] == self::TYPE_CONTRIB) {
        // Look up by drupal.org project info not $name because the two may be different.
        $project_update = $this->availableUpdates->get($extension->info['project']);
        if (!isset($project_update['releases']) || is_null($project_update['releases'])) {
          // Releases were either not checked or not available.
          $projects[$name]->info['upgrade_status_update'] = $update_check_for_uninstalled ? self::UPDATE_NOT_AVAILABLE : self::UPDATE_NOT_CHECKED;
        }
        else {
          // Add Drupal 9 compatibility info from the update's data.
          $latest_release = reset($project_update['releases']);
          $projects[$name]->info['upgrade_status_update_compatible'] = FALSE;
          if (!empty($latest_release['core_compatibility']) && self::isCompatibleWithNextMajorDrupal($latest_release['core_compatibility'])) {
            $projects[$name]->info['upgrade_status_update_compatible'] = TRUE;
          }
          // Denormalize update info into the extension info for our own use.
          if ($extension->info['version'] !== $latest_release['version']) {
            $projects[$name]->info['upgrade_status_update'] = self::UPDATE_AVAILABLE;
            $link = $project_update['link'] . '/releases/' . $latest_release['version'];
            $projects[$name]->info['upgrade_status_update_link'] = $link;
            $projects[$name]->info['upgrade_status_update_version'] = $latest_release['version'];
          }
          else {
            // If the current version is already the latest, store that.
            $projects[$name]->info['upgrade_status_update'] = self::UPDATE_ALREADY_INSTALLED;
          }
        }
      }

      // Get scan results if there was any.
      $scan_result = $this->getResults($name);

      // Pick a suggested next step for this project.
      if ($extension->info['upgrade_status_next_major_compatible'] && $extension->info['upgrade_status_type'] == self::TYPE_CONTRIB) {
        // If the project was contrib and already Drupal 9 compatible, relax.
        $extension->info['upgrade_status_next'] = self::NEXT_RELAX;
      }
      elseif (empty($extension->status) && ($name != $this->installProfile)) {
        // Uninstalled extensions should be removed. Except if this is the
        // profile. See https://www.drupal.org/project/drupal/issues/1170362
        $extension->info['upgrade_status_next'] = self::NEXT_REMOVE;
      }
      elseif (isset($extension->info['upgrade_status_update']) && $extension->info['upgrade_status_update'] == self::UPDATE_AVAILABLE) {
        // If there was a Drupal 9 compatible update or even a yet incompatible
        // update to this project, the best course of action is to update to
        // that, since that should move closer to Drupal 9 compatibility.
        $extension->info['upgrade_status_next'] = self::NEXT_UPDATE;
      }
      elseif ($extension->info['upgrade_status_type'] == self::TYPE_CONTRIB) {
        // For installed contributed modules that do not have compatible updates, collaborate.
        $extension->info['upgrade_status_next'] = self::NEXT_COLLABORATE;
      }
      else {
        // If there was no scanning result yet, next step is to scan this project.
        if (empty($scan_result) || empty($scan_result['data']['totals']['upgrade_status_next'])) {
          $extension->info['upgrade_status_next'] = self::NEXT_SCAN;
        }
        // If there were scanning results, carry over the next step suggestion from there.
        else {
          $extension->info['upgrade_status_next'] = $scan_result['data']['totals']['upgrade_status_next'];
        }
      }
    }
    return $projects;
  }

  /**
   * Collect core modules that are installed and obsolete or deprecated.
   *
   * @return array
   *   An associated array of extension names keyed by extension machine names.
   */
  public function collectCoreDeprecatedAndObsoleteExtensions() {
    $deprecated_or_obsolete = [];
    $modules = $this->moduleExtensionList->getList();
    $themes = $this->themeExtensionList->getList();
    $profiles = $this->profileExtensionList->getList();
    $extensions = array_merge($modules, $themes, $profiles);
    unset($modules, $themes, $profiles);

    /** @var \Drupal\Core\Extension\Extension $extension */
    foreach ($extensions as $key => $extension) {
      if (!empty($extension->status) && $extension->origin === 'core' && !empty($extension->info['lifecycle']) && in_array($extension->info['lifecycle'], ['deprecated', 'obsolete'])) {
        $prefix = '';
        $suffix = '';
        if (isset($extension->info['lifecycle_link'])) {
          $prefix = '<a href="' . $extension->info['lifecycle_link'] . '">';
          $suffix = ' (' . $this->t('read more') . ')</a>';
        }
        $deprecated_or_obsolete[$key] = $prefix . $extension->info['name'] . $suffix;
      }
    }
    return $deprecated_or_obsolete;
  }

  /**
   * Finds topmost extension for each extension and keeps only that.
   *
   * @param \Drupal\Core\Extension\Extension[] $extensions
   *   List of all enabled extensions.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   List of extensions, with only the topmost extension left for each
   *   extension that has a parent extension.
   */
  protected function collateExtensionsIntoProjects(array $extensions) {
    foreach ($extensions as $name_a => $extension_a) {
      $path_a = $extension_a->getPath() . '/';
      $path_a_length = strlen($path_a);

      foreach ($extensions as $name_b => $extension_b) {
        // Skip collation for test modules except where we test that.
        if ((strpos($name_b, 'upgrade_status_test_') === 0) && ($name_b != 'upgrade_status_test_submodules_a') && ($name_b != 'upgrade_status_test_submodules_with_errors_a')) {
          continue;
        }

        $path_b = $extension_b->getPath();
        // If the extension is not the same but the beginning of paths match,
        // remove this extension from the list as it is part of another one.
        if ($name_b != $name_a && substr($path_b, 0, $path_a_length) === $path_a) {

          // If the existing project was already Drupal 9 compatible, consider
          // this subcomponent as well. If this component was enabled, it would
          // affect how we consider the Drupal 9 compatibility.
          if (!empty($extensions[$name_a]->info['upgrade_status_next_major_compatible']) && !empty($extension_b->status)) {
            // Overwrite compatibility. If this is still compatible, it will
            // keep being TRUE, otherwise FALSE.
            $extensions[$name_a]->info['upgrade_status_next_major_compatible'] =
              isset($extension_b->info['core_version_requirement']) &&
              self::isCompatibleWithNextMajorDrupal($extension_b->info['core_version_requirement']);
          }

          // Remove the subextension.
          unset($extensions[$name_b]);
        }
      }
    }
    return $extensions;
  }

  /**
   * Returns a single extension based on type and machine name.
   *
   * @param string $project_machine_name
   *   Machine name for the extension.
   *
   * @return \Drupal\Core\Extension\Extension
   *   A project if exists.
   *
   * @throws \Drupal\Core\Extension\Exception\UnknownExtensionException
   *   If there was no identified project with the given name.
   */
  public function loadProject(string $project_machine_name) {
    $projects = $this->collectProjects();
    if (!empty($projects[$project_machine_name])) {
      return $projects[$project_machine_name];
    }
    throw new UnknownExtensionException("The {$project_machine_name} project does not exist.");
  }

  /**
   * Get local scanning results for a project.
   *
   * @param string $project_machine_name
   *   Machine name for project.
   *
   * @return mixed
   *   - NULL if there was no result
   *   - Associative array of results otherwise
   */
  public function getResults(string $project_machine_name) {
    // Always use a fresh service. An injected service could get stale results
    // because scan result saving happens in different HTTP requests for most
    // cases (when analysis was successful).
    return \Drupal::service('keyvalue')->get('upgrade_status_scan_results')->get($project_machine_name) ?: NULL;
  }

  /**
   * Return list of possible next steps and their labels and descriptions.
   *
   * @return array
   *   Associative array keys by next step identifier. Values are arrays
   *   where the first item is a label an the second is a description.
   */
  public function getNextStepInfo() {
    return [
      ProjectCollector::NEXT_REMOVE => [
        $this->t('Remove'),
        $this->t('The likely best action is to remove projects that are uninstalled. Why invest in updating them to be compatible if you are not using them?'),
        ProjectCollector::SUMMARY_ACT,
        'color-warning ' . ProjectCollector::NEXT_REMOVE
      ],
      ProjectCollector::NEXT_UPDATE => [
        $this->t('Update'),
        $this->t('There is an update available. Even if that is not fully compatible with the next major Drupal core, it may be more compatible than what you have, so best to start with updating first.'),
        ProjectCollector::SUMMARY_ACT,
        'color-warning ' . ProjectCollector::NEXT_REMOVE
      ],
      ProjectCollector::NEXT_SCAN => [
        $this->t('Scan'),
        $this->t('Status of this project cannot be determined without scanning the source code here. Use this form to run a scan on these.'),
        ProjectCollector::SUMMARY_ANALYZE,
        'color-warning ' . ProjectCollector::NEXT_REMOVE
      ],
      ProjectCollector::NEXT_COLLABORATE => [
        $this->t('Collaborate with maintainers'),
        $this->t('There may be Drupal.org issues by contributors or even <a href=":drupal-bot">the Project Update Bot</a>. Work with the maintainer to get them committed, provide feedback if they worked.', [':drupal-bot' => 'https://www.drupal.org/u/project-update-bot']),
        ProjectCollector::SUMMARY_ACT,
        'color-warning ' . ProjectCollector::NEXT_REMOVE
      ],
      ProjectCollector::NEXT_RECTOR => [
        $this->t('Fix with rector'),
        $this->t('Some or all problems found can be fixed automatically with <a href=":drupal-rector">drupal-rector</a>. Make the machine do the work.', [':drupal-rector' => 'https://www.drupal.org/project/rector']),
        ProjectCollector::SUMMARY_ACT,
        'color-error ' . ProjectCollector::NEXT_REMOVE
      ],
      ProjectCollector::NEXT_MANUAL => [
        $this->t('Fix manually'),
        $this->t('It looks like there is no automated fixes for either problems found. Check the report for pointers on how to fix.'),
        ProjectCollector::SUMMARY_ACT,
        'color-error ' . ProjectCollector::NEXT_REMOVE
      ],
      ProjectCollector::NEXT_RELAX => [
        $this->t('Compatible with next major Drupal core version'),
        $this->t('Well done. Congrats! Let\'s get everything else here!'),
        ProjectCollector::SUMMARY_RELAX,
        'color-success ' . ProjectCollector::NEXT_REMOVE
      ],
    ];
  }

  /**
   * Checks constraint compatibility with the next major Drupal core version.
   *
   * A customized version of Semver::satisfies(), since that only works for
   * a == condition.
   *
   * @param string $constraints
   *   Composer compatible constraints from core_version_requirement or
   *   drupal/core requirement.
   *
   * @return bool
   */
  public static function isCompatibleWithNextMajorDrupal(string $constraints) {
    $version_parser = new VersionParser();
    $provider = new Constraint('>=', $version_parser->normalize((self::getDrupalCoreMajorVersion() + 1) . '.0.0'));
    $parsed_constraints = $version_parser->parseConstraints($constraints);
    return $parsed_constraints->matches($provider);
  }

  /**
   * Checks constraint compatibility with a PHP version.
   *
   * A customized version of Semver::satisfies(), since that only works for
   * a == condition.
   *
   * @param string $constraints
   *   Composer compatible constraints from a PHP version requirement.
   * @param string $php
   *   Optional PHP version number. Defaults to 8.0.0.
   *
   * @return bool
   */
  public static function isCompatibleWithPHP(string $constraints, string $php = '8.0.0') {
    $version_parser = new VersionParser();
    $provider = new Constraint('>=', $version_parser->normalize($php));
    $parsed_constraints = $version_parser->parseConstraints($constraints);
    return $parsed_constraints->matches($provider);
  }

  /**
   * Return the oldest supported minor version for the current core major.
   *
   * @return string
   *   Oldest supported core version number.
   */
  public static function getOldestSupportedMinor(): string {
    $major = (int) \Drupal::VERSION;
    switch ($major) {
      case 9:
        return '9.5';
      case 10:
        return '10.2';
      case 11:
        return '11.0';
    }
    return '';
  }

  /**
   * Returns current core's major version.
   *
   * @return int
   *   Version converted to int.
   */
  public static function getDrupalCoreMajorVersion(): int {
    return (int) \Drupal::VERSION;
  }

}
