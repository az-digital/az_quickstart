<?php

declare(strict_types=1);

namespace Drupal\az_seo\Drush\Commands;

use Consolidation\AnnotatedCommand\Input\StdinAwareInterface;
use Consolidation\AnnotatedCommand\Input\StdinAwareTrait;
use Consolidation\OutputFormatters\FormatterManager;
use Consolidation\OutputFormatters\StructuredData\UnstructuredListData;
use Consolidation\SiteAlias\SiteAliasManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImportStorageTransformer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\schema_metatag\SchemaMetatagManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Drush\Exec\ExecTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Parser;

/**
 * Convenience command to alter global metatag defaults.
 */
final class AZMetatagSetCommand extends DrushCommands implements StdinAwareInterface {
  use AutowireTrait;
  use StdinAwareTrait;
  use ExecTrait;

  const SET = 'az-seo:metatag:set';
  const GET = 'az-seo:metatag:get';

  /**
   * Return the ConfigFactory service.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  public function getConfigFactory(): ConfigFactoryInterface {
    return $this->configFactory;
  }

  public function __construct(
    // @todo remove unnecessary services.
    protected ConfigFactoryInterface $configFactory,
    #[Autowire(service: 'config.storage')]
    protected StorageInterface $configStorage,
    #[Autowire(service: 'entity_type.manager')]
    protected EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'plugin.manager.metatag.tag')]
    protected PluginManagerInterface $pluginMetatagManager,
    #[Autowire(service: 'schema_metatag.schema_metatag_manager')]
    protected SchemaMetatagManagerInterface $schemaMetatagManager,
    protected SiteAliasManagerInterface $siteAliasManager,
    protected StorageManagerInterface $configStorageExport,
    protected ImportStorageTransformer $importStorageTransformer,
    protected FormatterManager $formatterManager,
  ) {
    parent::__construct();
  }

  /**
   * Save a metatag default directly.
   */
  #[CLI\Command(name: self::SET, aliases: ['azm-set'])]
  #[CLI\Argument(name: 'default', description: 'The defaults to alter, e.g. global, node.')]
  #[CLI\Argument(name: 'key', description: 'The key of the tag to set, can be nested (title, schema_organization_parent_organization.@id).')]
  #[CLI\Argument(name: 'value', description: 'The value to assign to the tag. Use <info>-</info> to read from stdin.')]
  #[CLI\Option(name: 'input-format',
    description: 'Format to parse the object. Recognized values: <info>string</info>, <info>yaml</info>. Since JSON is a subset of YAML, $value may be in JSON format.',
    suggestedValues: ['string', 'json',
    ])]
  #[CLI\Usage(name: 'drush az-seo:metatag:set global schema_organization_name sitename', description: 'Sets a global metatag default of <info>sitename</info> for the <info>schema_organization_name</info> tag.')]
  #[CLI\Usage(name: 'drush az-seo:metatag:set global schema_organization_parent_organization.@id https://quickstart.arizona.edu', description: 'Sets a global metatag default of <info>https://quickstart.arizona.edu</info> for the <info>@id</info> element of the <info>schema_organization_parent_organization</info> tag.')]
  public function set($default, $key, $value, $options = ['input-format' => 'string']) {

    // Special flag indicating that the value has been passed via STDIN.
    if ($value === '-') {
      $value = $this->stdin()->contents();
    }

    // Special handling for null.
    if (strtolower($value) === 'null') {
      $value = NULL;
    }

    // Special handling for empty array.
    if ($value == '[]') {
      $value = [];
    }

    if ($options['input-format'] === 'yaml') {
      $parser = new Parser();
      $value = $parser->parse($value);
    }

    // Get the metatag_defaults storage so we can load a particular default.
    $metatag_default_storage = $this->entityTypeManager->getStorage('metatag_defaults');
    $metatag_default = $metatag_default_storage->load($default);
    if (!$metatag_default) {
      throw new \Exception(dt('Could not find !default metatag_default.', ['!default' => $default]));
    }

    // Get the current tags for the metatag defaults.
    $tags = $metatag_default->get('tags');

    // The tag is the first element in the list, the rest is nested properties.
    $path = explode('.', $key);
    $tag = array_shift($path);

    // Load the plugin for the tag, since it understands serialization for it.
    // This can throw an exception if the plugin does not exist.
    $plugin = $this->pluginMetatagManager->createInstance($tag);

    // Ask the schemaMetatagManager to unserialize the value.
    $current_value = $tags[$tag] ?? [];
    $current_value = $this->schemaMetatagManager->unserialize($current_value);

    // If the user specified a nested path we're dealing with an array.
    // Set a value at arbitrary depth.
    if (!empty($path)) {
      NestedArray::setValue($current_value, $path, $value);
    }
    else {
      $current_value = $value;
    }

    $plugin->setValue($current_value);
    // Get actual raw value with plugin serialization.
    $new_value = $plugin->value();
    $simulate = $this->getConfig()->simulate();
    $confirmed = FALSE;
    if ($this->io()->confirm(dt('Update the !tag tag of the !default metatag_default to !new_value ?', [
      '!default' => $default,
      '!tag' => $tag,
      '!new_value' => $new_value,
    ]))) {
      $confirmed = TRUE;
    }
    if ($confirmed && !$simulate) {
      $tags[$tag] = $new_value;
      $metatag_default->set('tags', $tags);
      $metatag_default->save();
    }
  }

  /**
   * Get a metatag default directly.
   */
  #[CLI\Command(name: self::GET, aliases: ['azm-get'])]
  #[CLI\Argument(name: 'default', description: 'The default to put from, e.g. global, node.')]
  #[CLI\Argument(name: 'key', description: 'The key of the tag to get, can be nested (title, schema_organization_parent_organization.@id).')]
  #[CLI\Option(name: 'format',
    description: 'Format to output',
    suggestedValues: ['yaml', 'json',
    ])]
  #[CLI\Usage(name: 'drush az-seo:metatag:get global schema_organization_name', description: 'Gets the global metatag default for the <info>schema_organization_name</info> tag.')]
  #[CLI\Usage(name: 'drush az-seo:metatag:get global schema_organization_parent_organization.@id', description: 'Gets the global metatag default for the <info>@id</info> element of the <info>schema_organization_parent_organization</info> tag.')]
  public function get($default, $key, $options = ['format' => 'yaml']) {

    // Get the metatag_defaults storage so we can load a particular default.
    $metatag_default_storage = $this->entityTypeManager->getStorage('metatag_defaults');
    $metatag_default = $metatag_default_storage->load($default);
    if (!$metatag_default) {
      throw new \Exception(dt('Could not find !default metatag_default.', ['!default' => $default]));
    }

    // Get the current tags for the metatag defaults.
    $tags = $metatag_default->get('tags');

    // The tag is the first element in the list, the rest is nested properties.
    $path = explode('.', $key);
    $tag = array_shift($path);

    // Ask the schemaMetatagManager to unserialize the value.
    $current_value = $tags[$tag] ?? [];
    $current_value = $this->schemaMetatagManager->unserialize($current_value);

    // If the user specified a nested path we're dealing with an array.
    // Get a value at arbitrary depth.
    if (!empty($path)) {
      $current_value = NestedArray::getValue($current_value, $path);
    }

    // See if we have a nested value or array.
    if (!is_scalar($current_value)) {
      $current_value = new UnstructuredListData($current_value);
    }
    return $current_value;
  }

}
