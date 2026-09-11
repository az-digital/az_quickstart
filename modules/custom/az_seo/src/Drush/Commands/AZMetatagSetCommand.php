<?php

declare(strict_types=1);

namespace Drupal\az_seo\Drush\Commands;

use Consolidation\AnnotatedCommand\Input\StdinAwareInterface;
use Consolidation\AnnotatedCommand\Input\StdinAwareTrait;
use Consolidation\SiteAlias\SiteAliasManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImportStorageTransformer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageManagerInterface;
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

  const SET = 'az-seo:tag';

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
    protected SiteAliasManagerInterface $siteAliasManager,
    protected StorageManagerInterface $configStorageExport,
    protected ImportStorageTransformer $importStorageTransformer,
  ) {
    parent::__construct();
  }

  /**
   * Save a global metatag default directly.
   */
  #[CLI\Command(name: self::SET, aliases: ['azm', 'azm-set'])]
  #[CLI\Argument(name: 'tag', description: 'The tag to set.')]
  #[CLI\Argument(name: 'value', description: 'The value to assign to the tag. Use <info>-</info> to read from stdin.')]
  #[CLI\Option(name: 'input-format',
    description: 'Format to parse the object. Recognized values: <info>string</info>, <info>yaml</info>. Since JSON is a subset of YAML, $value may be in JSON format.',
    suggestedValues: ['string', 'json',
    ])]
  #[CLI\Usage(name: 'drush az-seo:tag schema_organization_name sitename', description: 'Sets a global metatag default of <info>sitename</info> for the <info>schema_organization_name</info> tag.')]
  #[CLI\Usage(name: 'drush az-seo:tag schema_organization_parent_organization.@id https://quickstart.arizona.edu', description: 'Sets a global metatag default of <info>https://quickstart.arizona.edu</info> for the <info>@id</info> element of the <info>schema_organization_parent_organization</info> tag.')]
  public function set($tag, $value, $options = ['input-format' => 'string']) {
    $data = $value;

    if (!isset($data)) {
      throw new \Exception(dt('No tag value specified.'));
    }

    // Special flag indicating that the value has been passed via STDIN.
    if ($data === '-') {
      $data = $this->stdin()->contents();
    }

    // Special handling for null.
    if (strtolower($data) === 'null') {
      $data = NULL;
    }

    // Special handling for empty array.
    if ($data == '[]') {
      $data = [];
    }

    if ($options['input-format'] === 'yaml') {
      $parser = new Parser();
      $data = $parser->parse($data);
    }

    // @todo make class constant.
    $config_name = 'metatag.metatag_defaults.global';
    $config = $this->getConfigFactory()->getEditable($config_name);
    // @todo determine special handling of parent organization
    // Keep track if we need a specific child element of this tag.
    $tag_key = NULL;
    // Special handling for parent organization.
    if (preg_match('/^schema_organization_parent_organization\.(.+)/', $tag, $matches)) {
      $tag = 'schema_organization_parent_organization';
      $tag_key = $matches[1];
    }
    // Check to see if tag already exists.
    $tag = 'tags.' . $tag;
    $existing_tag = $config->get($tag);
    // Special case handling, unserialize organization.
    if ($tag_key) {
      // @todo more sanity checking.
      $nested = unserialize($existing_tag, ['allowed_classes' => FALSE]);
      if (is_array($nested)) {
        // Set the requested nested array element.
        $nested[$tag_key] = $data;
      }
      $data = $nested;
    }
    // Parent organization needs to be serialized for storage.
    if ($tag === 'tags.schema_organization_parent_organization') {
      $data = serialize($data);
    }
    $simulate = $this->getConfig()->simulate();

    $confirmed = FALSE;
    if ($config->isNew() && $this->io()->confirm(dt('!name config does not exist. Do you want to create a new config object?', ['!name' => $config_name]))) {
      $confirmed = TRUE;
    }
    elseif (($existing_tag === NULL) && $this->io()->confirm(dt('Tag !tag does not exist. Do you want to create the tag?', ['!tag' => $tag]))) {
      $confirmed = TRUE;
    }
    elseif ($this->io()->confirm(dt('Do you want to update the !tag tag in !name config?', [
      '!tag' => $tag,
      '!name' => $config_name,
    ]))) {
      $confirmed = TRUE;
    }
    if ($confirmed && !$simulate) {
      return $config->set($tag, $data)->save();
    }
  }

}
