<?php

namespace Drupal\devel_generate\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\devel_generate\Attributes\Generator;
use Drupal\devel_generate\DevelGenerateBaseInterface;
use Drupal\devel_generate\DevelGeneratePluginManager;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * Provide Drush commands for all the core Devel Generate plugins.
 *
 * For commands that are parts of modules, Drush expects to find commandfiles in
 * __MODULE__/src/Drush/Commands, and the namespace is Drupal/__MODULE__/Drush/Commands.
 */
final class DevelGenerateCommands extends DrushCommands {

  use AutowireTrait;

  const USERS = 'devel-generate:users';

  const TERMS = 'devel-generate:terms';

  const VOCABS = 'devel-generate:vocabs';

  const MENUS = 'devel-generate:menus';

  const CONTENT = 'devel-generate:content';

  const BLOCK_CONTENT = 'devel-generate:block-content';

  const MEDIA = 'devel-generate:media';

  /**
   * The plugin instance.
   */
  private DevelGenerateBaseInterface $pluginInstance;

  /**
   * The Generate plugin parameters.
   */
  private array $parameters;

  /**
   * DevelGenerateCommands constructor.
   *
   * @param \Drupal\devel_generate\DevelGeneratePluginManager $manager
   *   The DevelGenerate plugin manager.
   */
  public function __construct(protected DevelGeneratePluginManager $manager) {
    parent::__construct();
    $this->setManager($manager);
  }

  /**
   * Get the DevelGenerate plugin manager.
   *
   * @return \Drupal\devel_generate\DevelGeneratePluginManager
   *   The DevelGenerate plugin manager.
   */
  public function getManager(): DevelGeneratePluginManager {
    return $this->manager;
  }

  /**
   * Set the DevelGenerate plugin manager.
   *
   * @param \Drupal\devel_generate\DevelGeneratePluginManager $manager
   *   The DevelGenerate plugin manager.
   */
  public function setManager(DevelGeneratePluginManager $manager): void {
    $this->manager = $manager;
  }

  /**
   * Get the DevelGenerate plugin instance.
   *
   * @return \Drupal\devel_generate\DevelGenerateBaseInterface
   *   The DevelGenerate plugin instance.
   */
  public function getPluginInstance(): DevelGenerateBaseInterface {
    return $this->pluginInstance;
  }

  /**
   * Set the DevelGenerate plugin instance.
   *
   * @param mixed $pluginInstance
   *   The DevelGenerate plugin instance.
   */
  public function setPluginInstance(mixed $pluginInstance): void {
    $this->pluginInstance = $pluginInstance;
  }

  /**
   * Get the DevelGenerate plugin parameters.
   *
   * @return array
   *   The plugin parameters.
   */
  public function getParameters(): array {
    return $this->parameters;
  }

  /**
   * Set the DevelGenerate plugin parameters.
   *
   * @param array $parameters
   *   The plugin parameters.
   */
  public function setParameters(array $parameters): void {
    $this->parameters = $parameters;
  }

  /**
   * Create users.
   */
  #[CLI\Command(name: self::USERS, aliases: ['genu', 'devel-generate-users'])]
  #[CLI\Argument(name: 'num', description: 'Number of users to generate.')]
  #[CLI\Option(name: 'kill', description: 'Delete all users before generating new ones.')]
  #[CLI\Option(name: 'roles', description: 'A comma delimited list of role IDs for new users. Don\'t specify <info>authenticated</info>.')]
  #[CLI\Option(name: 'pass', description: 'Specify a password to be set for all generated users.')]
  #[Generator(id: 'user')]
  public function users(int $num = 50, array $options = ['kill' => FALSE, 'roles' => self::REQ]): void {
    // @todo pass $options to the plugins.
    $this->generate();
  }

  /**
   * Create terms in specified vocabulary.
   */
  #[CLI\Command(name: self::TERMS, aliases: ['gent', 'devel-generate-terms'])]
  #[CLI\Argument(name: 'num', description: 'Number of terms to generate.')]
  #[CLI\Option(name: 'kill', description: 'Delete all terms in these vocabularies before generating new ones.')]
  #[CLI\Option(name: 'bundles', description: 'A comma-delimited list of machine names for the vocabularies where terms will be created.')]
  #[CLI\Option(name: 'feedback', description: 'An integer representing interval for insertion rate logging.')]
  #[CLI\Option(name: 'languages', description: 'A comma-separated list of language codes')]
  #[CLI\Option(name: 'translations', description: 'A comma-separated list of language codes for translations.')]
  #[CLI\Option(name: 'min-depth', description: 'The minimum depth of hierarchy for the new terms.')]
  #[CLI\Option(name: 'max-depth', description: 'The maximum depth of hierarchy for the new terms.')]
  #[Generator(id: 'term')]
  public function terms(int $num = 50, array $options = ['kill' => FALSE, 'bundles' => self::REQ, 'feedback' => '1000', 'languages' => self::REQ, 'translations' => self::REQ, 'min-depth' => '1', 'max-depth' => '4']): void {
    $this->generate();
  }

  /**
   * Create vocabularies.
   */
  #[CLI\Command(name: self::VOCABS, aliases: ['genv', 'devel-generate-vocabs'])]
  #[CLI\Argument(name: 'num', description: 'Number of vocabularies to generate.')]
  #[Generator(id: 'vocabulary')]
  #[CLI\ValidateModulesEnabled(modules: ['taxonomy'])]
  #[CLI\Option(name: 'kill', description: 'Delete all vocabs before generating new ones.')]
  public function vocabs(int $num = 1, array $options = ['kill' => FALSE]): void {
    $this->generate();
  }

  /**
   * Create menus.
   */
  #[CLI\Command(name: self::MENUS, aliases: ['genm', 'devel-generate-menus'])]
  #[CLI\Argument(name: 'number_menus', description: 'Number of menus to generate.')]
  #[CLI\Argument(name: 'number_links', description: 'Number of links to generate.')]
  #[CLI\Argument(name: 'max_depth', description: 'Max link depth.')]
  #[CLI\Argument(name: 'max_width', description: 'Max width of first level of links.')]
  #[CLI\Option(name: 'kill', description: 'Delete any menus and menu links previously created by devel_generate before generating new ones.')]
  #[Generator(id: 'menu')]
  public function menus(int $number_menus = 2, int $number_links = 50, int $max_depth = 3, int $max_width = 8, array $options = ['kill' => FALSE]): void {
    $this->generate();
  }

  /**
   * Create content.
   */
  #[CLI\Command(name: self::CONTENT, aliases: ['genc', 'devel-generate-content'])]
  #[CLI\ValidateModulesEnabled(modules: ['node'])]
  #[CLI\Argument(name: 'num', description: 'Number of nodes to generate.')]
  #[CLI\Argument(name: 'max_comments', description: 'Maximum number of comments to generate.')]
  #[CLI\Option(name: 'kill', description: 'Delete all content before generating new content.')]
  #[CLI\Option(name: 'bundles', description: 'A comma-delimited list of content types to create.')]
  #[CLI\Option(name: 'authors', description: 'A comma delimited list of authors ids. Defaults to all users.')]
  #[CLI\Option(name: 'roles', description: 'A comma delimited list of role machine names to filter the random selection of users. Defaults to all roles.')]
  #[CLI\Option(name: 'feedback', description: 'An integer representing interval for insertion rate logging.')]
  #[CLI\Option(name: 'skip-fields', description: 'A comma delimited list of fields to omit when generating random values')]
  #[CLI\Option(name: 'base-fields', description: 'A comma delimited list of base field names to populate')]
  #[CLI\Option(name: 'languages', description: 'A comma-separated list of language codes')]
  #[CLI\Option(name: 'translations', description: 'A comma-separated list of language codes for translations.')]
  #[CLI\Option(name: 'add-type-label', description: 'Add the content type label to the front of the node title')]
  #[Generator(id: 'content')]
  public function content(int $num = 50, int $max_comments = 0, array $options = ['kill' => FALSE, 'bundles' => 'page,article', 'authors' => self::REQ, 'roles' => self::REQ, 'feedback' => 1000, 'skip-fields' => self::REQ, 'base-fields' => self::REQ, 'languages' => self::REQ, 'translations' => self::REQ, 'add-type-label' => FALSE]): void {
    $this->generate();
  }

  /**
   * Create Block content blocks.
   */
  #[CLI\Command(name: self::BLOCK_CONTENT, aliases: ['genbc', 'devel-generate-block-content'])]
  #[CLI\ValidateModulesEnabled(modules: ['block_content'])]
  #[CLI\Argument(name: 'num', description: 'Number of blocks to generate.')]
  #[CLI\Option(name: 'kill', description: 'Delete all block content before generating new.')]
  #[CLI\Option(name: 'block_types', description: 'A comma-delimited list of block content types to create.')]
  #[CLI\Option(name: 'authors', description: 'A comma delimited list of authors ids. Defaults to all users.')]
  #[CLI\Option(name: 'feedback', description: 'An integer representing interval for insertion rate logging.')]
  #[CLI\Option(name: 'skip-fields', description: 'A comma delimited list of fields to omit when generating random values')]
  #[CLI\Option(name: 'base-fields', description: 'A comma delimited list of base field names to populate')]
  #[CLI\Option(name: 'languages', description: 'A comma-separated list of language codes')]
  #[CLI\Option(name: 'translations', description: 'A comma-separated list of language codes for translations.')]
  #[CLI\Option(name: 'add-type-label', description: 'Add the block type label to the front of the node title')]
  #[CLI\Option(name: 'reusable', description: 'Create re-usable blocks. Disable for inline Layout Builder blocks, for example.')]
  #[Generator(id: 'block_content')]
  public function blockContent(int $num = 50, array $options = ['kill' => FALSE, 'block_types' => 'basic', 'feedback' => 1000, 'skip-fields' => self::REQ, 'base-fields' => self::REQ, 'languages' => self::REQ, 'translations' => self::REQ, 'add-type-label' => FALSE, 'reusable' => TRUE]): void {
    $this->generate();
  }

  /**
   * Create media items.
   */
  #[CLI\Command(name: self::MEDIA, aliases: ['genmd', 'devel-generate-media'])]
  #[CLI\Argument(name: 'num', description: 'Number of media to generate.')]
  #[CLI\Option(name: 'kill', description: 'Delete all media items before generating new.')]
  #[CLI\Option(name: 'media_types', description: 'A comma-delimited list of media types to create.')]
  #[CLI\Option(name: 'feedback', description: 'An integer representing interval for insertion rate logging.')]
  #[CLI\Option(name: 'skip-fields', description: 'A comma delimited list of fields to omit when generating random values')]
  #[CLI\Option(name: 'base-fields', description: 'A comma delimited list of base field names to populate')]
  #[CLI\Option(name: 'languages', description: 'A comma-separated list of language codes')]
  #[CLI\ValidateModulesEnabled(modules: ['media'])]
  #[Generator(id: 'media')]
  public function media(int $num = 50, array $options = ['kill' => FALSE, 'media-types' => self::REQ, 'feedback' => 1000, 'skip-fields' => self::REQ, 'languages' => self::REQ, 'base-fields' => self::REQ]): void {
    $this->generate();
  }

  /**
   * The standard drush validate hook.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The data sent from the drush command.
   */
  #[CLI\Hook(HookManager::ARGUMENT_VALIDATOR)]
  public function validate(CommandData $commandData): void {
    $manager = $this->manager;
    $args = $commandData->input()->getArguments();
    // The command name is the first argument but we do not need this.
    array_shift($args);
    /** @var \Drupal\devel_generate\DevelGenerateBaseInterface $instance */
    $instance = $manager->createInstance($commandData->annotationData()->get('pluginId'), []);
    $this->setPluginInstance($instance);
    $parameters = $instance->validateDrushParams($args, $commandData->input()->getOptions());
    $this->setParameters($parameters);
  }

  /**
   * Wrapper for calling the plugin instance generate function.
   */
  public function generate(): void {
    $instance = $this->pluginInstance;
    $instance->generate($this->parameters);
  }

}
