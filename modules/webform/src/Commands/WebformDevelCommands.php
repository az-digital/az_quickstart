<?php

namespace Drupal\webform\Commands;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformEntityElementsValidatorInterface;
use Drush\Exceptions\UserAbortException;

/**
 * Webform development related commands for Drush 9.x and 10.x.
 */
class WebformDevelCommands extends WebformCommandsBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Cache backend instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The webform element validator.
   *
   * @var \Drupal\webform\WebformEntityElementsValidatorInterface
   */
  protected $elementsValidator;

  /**
   * WebformDevelCommands constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\webform\WebformEntityElementsValidatorInterface $elements_validator
   *   The webform element validator.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend, RendererInterface $renderer, WebformEntityElementsValidatorInterface $elements_validator) {
    parent::__construct();
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->cacheBackend = $cache_backend;
    $this->elementsValidator = $elements_validator;
  }

  /* ************************************************************************ */
  // Repair.
  /* ************************************************************************ */

  /**
   * Makes sure all Webform admin configuration and webform settings are up-to-date.
   *
   * @command webform:repair
   *
   * @usage webform:repair
   *   Repairs admin configuration and webform settings are up-to-date.
   *
   * @aliases wfr,webform-repair
   */
  public function repair() {
    if (!$this->io()->confirm(dt("Are you sure you want repair the Webform module's admin settings and webforms?"))) {
      throw new UserAbortException();
    }

    $this->moduleHandler->loadInclude('webform', 'install');

    $this->output()->writeln(dt('Repairing webform submission storage schema…'));
    _webform_update_webform_submission_storage_schema();

    $this->output()->writeln(dt('Repairing admin configuration…'));
    _webform_update_admin_settings(TRUE);

    $this->output()->writeln(dt('Repairing webform HTML editor…'));
    _webform_update_html_editor();

    $this->output()->writeln(dt('Repairing webform settings…'));
    _webform_update_webform_settings();

    $this->output()->writeln(dt('Repairing webform handlers…'));
    _webform_update_webform_handler_settings();

    $this->output()->writeln(dt('Repairing webform actions…'));
    _webform_update_actions();

    $this->output()->writeln(dt('Repairing webform field storage definitions…'));
    _webform_update_field_storage_definitions();

    $this->output()->writeln(dt('Repairing webform submission storage schema…'));
    _webform_update_webform_submission_storage_schema();

    if ($this->moduleHandler->moduleExists('webform_entity_print')) {
      $this->output()->writeln(dt('Repairing webform entity print settings…'));
      $this->moduleHandler->loadInclude('webform_entity_print', 'install');
      webform_entity_print_install();
    }

    $this->output()->writeln(dt('Removing (unneeded) webform submission translation settings…'));
    _webform_update_webform_submission_translation();

    // Validate all webform elements.
    $this->output()->writeln(dt('Validating webform elements…'));

    $this->moduleHandler->loadAll();

    $render_context = new RenderContext();
    $this->renderer->executeInRenderContext($render_context, function () {
      /** @var \Drupal\webform\WebformInterface[] $webforms */
      $webforms = Webform::loadMultiple();
      foreach ($webforms as $webform) {
        // Ignored test files.
        // @todo Determine why these webforms are throwing error via CLI.
        if (in_array($webform->id(), ['test_element_managed_file_limit', 'test_composite_custom_file', 'test_element_comp_file_plugin'])) {
          continue;
        }

        $messages = $this->elementsValidator->validate($webform);
        if ($messages) {
          $this->output()->writeln('  ' . dt('@title (@id): Found element validation errors.', ['@title' => $webform->label(), '@id' => $webform->id()]));
          foreach ($messages as $message) {
            $this->output()->writeln('  - ' . strip_tags($message));
          }
        }
      }
    });

    Cache::invalidateTags(['rendered']);
    // @todo Remove when that is fixed in https://www.drupal.org/node/2773591.
    $this->cacheBackend->deleteAll();
  }

  /* ************************************************************************ */
  // Remove orphans.
  /* ************************************************************************ */

  /**
   * Removes orphaned submissions where the submission's webform was deleted.
   *
   * @command webform:remove:orphans
   *
   * @usage webform:remove:orphans
   *   Removes orphaned submissions where the submission's webform was deleted.
   *
   * @aliases wfro,webform-remove-orphans
   */
  public function removeOrphans() {
    $webform_ids = [];
    foreach ($this->configFactory->listAll('webform.webform.') as $webform_config_name) {
      $webform_id = str_replace('webform.webform.', '', $webform_config_name);
      $webform_ids[$webform_id] = $webform_id;
    }

    $sids = $this->database->select('webform_submission')
      ->fields('webform_submission', ['sid'])
      ->condition('webform_id', $webform_ids, 'NOT IN')
      ->orderBy('sid')
      ->execute()
      ->fetchCol();

    if (!$sids) {
      $this->output()->writeln(dt('No orphaned submission found.'));
      return;
    }

    $t_args = ['@total' => count($sids)];
    if (!$this->io()->confirm(dt("Are you sure you want remove @total orphaned webform submissions?", $t_args))) {
      throw new UserAbortException();
    }

    $this->output()->writeln(dt('Deleting @total orphaned webform submissions…', $t_args));
    $submissions = WebformSubmission::loadMultiple($sids);
    foreach ($submissions as $submission) {
      $submission->delete();
    }
  }

}
