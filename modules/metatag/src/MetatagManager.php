<?php

namespace Drupal\metatag;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\metatag\Entity\MetatagDefaults;
use Drupal\views\ViewEntityInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Primary logic for the Metatag module.
 *
 * @package Drupal\metatag
 */
class MetatagManager implements MetatagManagerInterface {

  use StringTranslationTrait;
  use MetatagSeparator;

  /**
   * The group plugin manager.
   *
   * @var \Drupal\metatag\MetatagGroupPluginManager
   */
  protected $groupPluginManager;

  /**
   * The tag plugin manager.
   *
   * @var \Drupal\metatag\MetatagTagPluginManager
   */
  protected $tagPluginManager;

  /**
   * The Metatag defaults.
   *
   * @var \Drupal\metatag\Entity\MetatagDefaults
   */
  protected $metatagDefaults;

  /**
   * The Metatag token.
   *
   * @var \Drupal\metatag\MetatagToken
   */
  protected $tokenService;

  /**
   * Config factory.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Metatag logging channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Caches processed strings, keyed by tag name.
   *
   * @var array
   */
  protected $processedTokenCache = [];

  /**
   * Constructor for MetatagManager.
   *
   * @param \Drupal\metatag\MetatagGroupPluginManager $groupPluginManager
   *   The MetatagGroupPluginManager object.
   * @param \Drupal\metatag\MetatagTagPluginManager $tagPluginManager
   *   The MetatagTagPluginManager object.
   * @param \Drupal\metatag\MetatagToken $token
   *   The MetatagToken object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $channelFactory
   *   The LoggerChannelFactoryInterface object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManagerInterface object.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   The path matcher.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Config Factory.
   */
  public function __construct(
    MetatagGroupPluginManager $groupPluginManager,
    MetatagTagPluginManager $tagPluginManager,
    MetatagToken $token,
    LoggerChannelFactoryInterface $channelFactory,
    EntityTypeManagerInterface $entityTypeManager,
    PathMatcherInterface $pathMatcher,
    RouteMatchInterface $routeMatch,
    RequestStack $requestStack,
    LanguageManagerInterface $languageManager,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->groupPluginManager = $groupPluginManager;
    $this->tagPluginManager = $tagPluginManager;
    $this->tokenService = $token;
    $this->logger = $channelFactory->get('metatag');
    $this->metatagDefaults = $entityTypeManager->getStorage('metatag_defaults');
    $this->pathMatcher = $pathMatcher;
    $this->routeMatch = $routeMatch;
    $this->requestStack = $requestStack;
    $this->languageManager = $languageManager;
    $this->configFactory = $config_factory;
  }

  /**
   * Returns the list of protected defaults.
   *
   * @return array
   *   The protected defaults.
   */
  public static function protectedDefaults(): array {
    return [
      'global',
      '403',
      '404',
      'node',
      'front',
      'taxonomy_term',
      'user',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tagsFromEntity(ContentEntityInterface $entity): array {
    $tags = [];

    $fields = $this->getFields($entity);

    /** @var \Drupal\field\Entity\FieldConfig $field_info */
    foreach ($fields as $field_name => $field_info) {
      // Get the tags from this field.
      $tags = $this->getFieldTags($entity, $field_name);
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function tagsFromEntityWithDefaults(ContentEntityInterface $entity): array {
    return $this->tagsFromEntity($entity) + $this->defaultTagsFromEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultTagsFromEntity(ContentEntityInterface $entity): array {
    /** @var \Drupal\metatag\Entity\MetatagDefaults $metatags */
    $metatags = $this->metatagDefaults->load('global');
    if (!$metatags || !$metatags->status()) {
      return [];
    }
    // Add/overwrite with tags set on the entity type.
    /** @var \Drupal\metatag\Entity\MetatagDefaults $entity_type_tags */
    $entity_type_tags = $this->metatagDefaults->load($entity->getEntityTypeId());
    if (!is_null($entity_type_tags) && $entity_type_tags->status()) {
      $metatags->overwriteTags($entity_type_tags->get('tags'));
    }
    // Add/overwrite with tags set on the entity bundle.
    /** @var \Drupal\metatag\Entity\MetatagDefaults $bundle_metatags */
    $bundle_metatags = $this->metatagDefaults->load($entity->getEntityTypeId() . '__' . $entity->bundle());
    if (!is_null($bundle_metatags) && $bundle_metatags->status()) {
      $metatags->overwriteTags($bundle_metatags->get('tags'));
    }
    return $metatags->get('tags');
  }

  /**
   * Gets the group plugin definitions.
   *
   * @return array
   *   Group definitions.
   */
  protected function groupDefinitions(): array {
    return $this->groupPluginManager->getDefinitions();
  }

  /**
   * Gets the tag plugin definitions.
   *
   * @return array
   *   Tag definitions
   */
  protected function tagDefinitions(): array {
    return $this->tagPluginManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function sortedGroups(): array {
    $metatag_groups = $this->groupDefinitions();

    // Pull the data from the definitions into a new array.
    $groups = [];
    foreach ($metatag_groups as $group_name => $group_info) {
      $groups[$group_name] = $group_info;
      $groups[$group_name]['label'] = $group_info['label']->render();
      $groups[$group_name]['description'] = $group_info['description'] ?? '';
    }

    // Sort the tag groups.
    uasort($groups, [
      'Drupal\Component\Utility\SortArray',
      'sortByWeightElement',
    ]);

    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function sortedTags(): array {
    $metatag_tags = $this->tagDefinitions();

    // Pull the data from the definitions into a new array.
    $tags = [];
    foreach ($metatag_tags as $tag_name => $tag_info) {
      $tags[$tag_info['group']][$tag_name] = $tag_info;
      $tags[$tag_info['group']][$tag_name]['label'] = $tag_info['label']->render();
    }

    // Sort the tags based on the group.
    $sorted_tags = [];
    foreach ($this->sortedGroups() as $group_name => $group) {
      $tag_weight = $group['weight'] * 100;
    
      // Check if $tags[$group_name] is set and is an array.
      if (isset($tags[$group_name]) && is_array($tags[$group_name])) {
        // First, sort the tags within the group according to the original sort
        // order provided by the tag's definition.
        uasort($tags[$group_name], [
          'Drupal\Component\Utility\SortArray',
          'sortByWeightElement',
        ]);
        foreach ($tags[$group_name] as $tag_name => $tag_info) {
          $tag_info['weight'] = $tag_weight++;
          $sorted_tags[$tag_name] = $tag_info;
        }
      } else {
        // Log an error message if $tags[$group_name] is not set or not an array.
        \Drupal::logger('metatag')->error('Expected an array but got null or other type for group: @group_name', ['@group_name' => $group_name]);
      }
    }    

    return $sorted_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function sortedGroupsWithTags(): array {
    $groups = $this->sortedGroups();
    $tags = $this->sortedTags();

    foreach ($tags as $tag_name => $tag) {
      $tag_group = $tag['group'];

      if (!isset($groups[$tag_group])) {
        // If the tag is claiming a group that has no matching plugin, log an
        // error and force it to the basic group.
        $this->logger->error("Undefined group '%group' on tag '%tag'", [
          '%group' => $tag_group,
          '%tag' => $tag_name,
        ]);
        $tag['group'] = 'basic';
        $tag_group = 'basic';
      }

      $groups[$tag_group]['tags'][$tag_name] = $tag;
    }

    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $values, array $element, array $token_types = [], array $included_groups = NULL, array $included_tags = NULL, $verbose_help = FALSE): array {
    // Add the outer fieldset.
    $element += [
      '#type' => 'details',
    ];

    // Add a title to the form.
    $element['preamble'] = [
      '#markup' => '<p><strong>' . $this->t('Configure the meta tags below.') . '</strong></p>',
      '#weight' => -11,
    ];

    $element += $this->tokenService->tokenBrowser($token_types, $verbose_help);

    $groups_and_tags = $this->sortedGroupsWithTags();

    foreach ($groups_and_tags as $group_name => $group) {
      // Only act on groups that have tags and are in the list of included
      // groups (unless that list is null).
      if (isset($group['tags']) && (is_null($included_groups) || in_array($group_name, $included_groups) || in_array($group['id'], $included_groups))) {
        // Create the fieldset.
        $element[$group_name]['#type'] = 'details';
        $element[$group_name]['#title'] = $group['label'];
        $element[$group_name]['#description'] = $group['description'] ?? '';
        $element[$group_name]['#open'] = FALSE;

        foreach ($group['tags'] as $tag_name => $tag) {
          // Only act on tags in the included tags list, unless that is null.
          if (is_null($included_tags) || in_array($tag_name, $included_tags) || in_array($tag['id'], $included_tags)) {
            // Make an instance of the tag.
            $tag = $this->tagPluginManager->createInstance($tag_name);

            // Set the value to the stored value, if any.
            $tag_value = $values[$tag_name] ?? NULL;
            $tag->setValue($tag_value);

            // Open any groups that have non-empty values.
            if (!empty($tag_value)) {
              $element[$group_name]['#open'] = TRUE;
            }

            // Create the bit of form for this tag.
            $element[$group_name][$tag_name] = $tag->form($element);
          }
        }
      }
    }

    return $element;
  }

  /**
   * Returns a list of the Metatag fields on an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to examine.
   *
   * @return array
   *   The fields from the entity which are Metatag fields.
   */
  protected function getFields(ContentEntityInterface $entity): array {
    $field_list = [];

    if ($entity instanceof ContentEntityInterface) {
      // Get a list of the metatag field types.
      $field_types = $this->fieldTypes();

      // Get a list of the field definitions on this entity.
      $definitions = $entity->getFieldDefinitions();

      // Iterate through all the fields looking for ones in our list.
      foreach ($definitions as $field_name => $definition) {
        // Get the field type, ie: metatag.
        $field_type = $definition->getType();

        // Check the field type against our list of fields.
        if (!empty($field_type) && in_array($field_type, $field_types)) {
          $field_list[$field_name] = $definition;
        }
      }
    }

    return $field_list;
  }

  /**
   * Returns a list of the meta tags with values from a field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The ContentEntityInterface object.
   * @param string $field_name
   *   The name of the field to work on.
   *
   * @return array
   *   Array of field tags.
   */
  protected function getFieldTags(ContentEntityInterface $entity, $field_name): array {
    $tags = [];
    foreach ($entity->{$field_name} as $item) {
      // Get serialized value and break it into an array of tags with values.
      $serialized_value = $item->get('value')->getValue();
      if (!empty($serialized_value)) {
        $new_tags = metatag_data_decode($serialized_value);
        if ($new_tags !== FALSE) {
          if (!empty($new_tags)) {
            if (is_array($new_tags)) {
              $tags += $new_tags;
            }
            else {
              $this->logger->error("This was expected to be an array but it is not: \n%value", ['%value' => print_r($new_tags, TRUE)]);
            }
          }
        }
        else {
          $this->logger->error("This could not be unserialized: \n%value", ['%value' => print_r($serialized_value, TRUE)]);
        }
      }
    }

    return $tags;
  }

  /**
   * Returns default meta tags for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to work on.
   *
   * @return array
   *   The default meta tags appropriate for this entity.
   */
  public function getDefaultMetatags(ContentEntityInterface $entity = NULL): array {
    // Get general global metatags.
    $metatags = $this->getGlobalMetatags();
    // If that is empty something went wrong.
    if (!$metatags) {
      return [];
    }

    // Check if this is a special page.
    $special_metatags = $this->getSpecialMetatags();

    // Merge with all globals defaults.
    if ($special_metatags) {
      $metatags->set('tags', array_merge($metatags->get('tags'), $special_metatags->get('tags')));
    }

    // Next check if there is this page is an entity that has meta tags.
    // @todo Think about using other defaults, e.g. views. Maybe use plugins?
    else {
      if (is_null($entity)) {
        $entity = metatag_get_route_entity();
      }

      if (!empty($entity)) {
        // Get default meta tags for a given entity.
        $entity_defaults = $this->getEntityDefaultMetatags($entity);
        if ($entity_defaults != NULL) {
          $metatags->set('tags', array_merge($metatags->get('tags'), $entity_defaults));
        }
      }
    }

    return $metatags->get('tags');
  }

  /**
   * Returns global meta tags.
   *
   * @return \Drupal\metatag\Entity\MetatagDefaults|null
   *   The global meta tags or NULL.
   */
  public function getGlobalMetatags(): MetatagDefaults|NULL {
    $metatags = $this->metatagDefaults->load('global');
    return (!empty($metatags) && $metatags->status()) ? $metatags : NULL;
  }

  /**
   * Returns special meta tags.
   *
   * @return \Drupal\metatag\Entity\MetatagDefaults|null
   *   The defaults for this page, if it's a special page.
   */
  public function getSpecialMetatags(): MetatagDefaults|NULL {
    $metatags = NULL;

    if ($this->pathMatcher->isFrontPage()) {
      $metatags = $this->metatagDefaults->load('front');
    }
    elseif ($this->routeMatch->getRouteName() == 'system.403') {
      $metatags = $this->metatagDefaults->load('403');
    }
    elseif ($this->routeMatch->getRouteName() == 'system.404') {
      $metatags = $this->metatagDefaults->load('404');
    }

    if ($metatags && !$metatags->status()) {
      // Do not return disabled special metatags.
      return NULL;
    }

    return $metatags;
  }

  /**
   * Returns default meta tags for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to work with.
   *
   * @return array
   *   The appropriate default meta tags.
   */
  public function getEntityDefaultMetatags(ContentEntityInterface $entity): array {
    /** @var \Drupal\metatag\Entity\MetatagDefaults $entity_metatags */
    $entity_metatags = $this->metatagDefaults->load($entity->getEntityTypeId());
    $metatags = [];
    if ($entity_metatags != NULL && $entity_metatags->status()) {
      // Merge with global defaults.
      $metatags = array_merge($metatags, $entity_metatags->get('tags'));
    }

    // Finally, check if we should apply bundle overrides.
    /** @var \Drupal\metatag\Entity\MetatagDefaults $bundle_metatags */
    $bundle_metatags = $this->metatagDefaults->load($entity->getEntityTypeId() . '__' . $entity->bundle());
    if ($bundle_metatags != NULL && $bundle_metatags->status()) {
      // Merge with existing defaults.
      $metatags = array_merge($metatags, $bundle_metatags->get('tags'));
    }

    return $metatags;
  }

  /**
   * {@inheritdoc}
   */
  public function generateElements(array $tags, $entity = NULL): array {
    $elements = [];
    $tags = $this->generateRawElements($tags, $entity);

    foreach ($tags as $name => $tag) {
      if (!empty($tag)) {
        $elements['#attached']['html_head'][] = [
          $tag,
          $name,
        ];
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function generateRawElements(array $tags, $entity = NULL, BubbleableMetadata $cache = NULL): array {
    // Ignore the update.php path.
    $request = $this->requestStack->getCurrentRequest();
    if ($request->getBaseUrl() == '/update.php') {
      return [];
    }

    // Use the entity's language code, if one is defined.
    $langcode = NULL;
    // Prepare any tokens that might exist.
    $token_replacements = [];
    if ($entity) {
      $langcode = $entity->language()->getId();
      // @todo This needs a better way of discovering the context.
      if ($entity instanceof ViewEntityInterface) {
        // Views tokens require the ViewExecutable, not the config entity.
        // @todo Can we move this into metatag_views somehow?
        $token_replacements = ['view' => $entity->getExecutable()];
      }
      elseif ($entity instanceof ContentEntityInterface) {
        $token_replacements = [$entity->getEntityTypeId() => $entity];
      }
    }

    $definitions = $this->sortedTags();

    // Sort the meta tags so they are rendered in the correct order.
    $ordered_tags = [];
    foreach ($definitions as $id => $metatag) {
      if (isset($tags[$id])) {
        $ordered_tags[$id] = $tags[$id];
      }
    }

    // Each element of the $values array is a tag with the tag plugin name as
    // the key.
    $rawTags = [];
    foreach ($ordered_tags as $tag_name => $value) {
      // Check to ensure there is a matching plugin.
      if (isset($definitions[$tag_name])) {
        // Get an instance of the plugin.
        $tag = $this->tagPluginManager->createInstance($tag_name);

        // Prepare value.
        $processed_value = $this->processTagValue($tag, $value, $token_replacements, FALSE, $langcode);

        // Now store the value with processed tokens back into the plugin.
        $tag->setValue($processed_value);

        // Have the tag generate the output based on the value we gave it.
        $output = $tag->output();

        if (!empty($output)) {
          $output = $tag->multiple() ? $output : [$output];

          // Backwards compatibility for modules which don't support this logic.
          if (isset($output['#tag'])) {
            $output = [$output];
          }

          foreach ($output as $index => $element) {
            // Add index to tag name as suffix to avoid having same key.
            $index_tag_name = $tag->multiple() ? $tag_name . '_' . $index : $tag_name;
            $rawTags[$index_tag_name] = $element;
          }
        }
      }
    }

    return $rawTags;
  }

  /**
   * Generate the actual meta tag values for use as tokens.
   *
   * @param array $tags
   *   The array of tags as plugin_id => value.
   * @param object $entity
   *   Optional entity object to use for token replacements.
   *
   * @return array
   *   Array of MetatagTag plugin instances.
   */
  public function generateTokenValues(array $tags, $entity = NULL): array {
    // Ignore the update.php path.
    $request = $this->requestStack->getCurrentRequest();
    if ($request->getBaseUrl() == '/update.php') {
      return [];
    }

    $entity_identifier = '_none';
    // Use the entity's language code, if one is defined.
    $langcode = NULL;
    if ($entity) {
      $langcode = $entity->language()->getId();
      $entity_identifier = $entity->getEntityTypeId() . ':' . ($entity->uuid() ?? $entity->id()) . ':' . $langcode;
    }

    if (!isset($this->processedTokenCache[$entity_identifier])) {
      $metatag_tags = $this->sortedTags();

      // Each element of the $values array is a tag with the tag plugin name as
      // the key.
      foreach ($tags as $tag_name => $value) {
        // Check to ensure there is a matching plugin.
        if (isset($metatag_tags[$tag_name])) {
          // Get an instance of the plugin.
          $tag = $this->tagPluginManager->createInstance($tag_name);

          // Render any tokens in the value.
          $token_replacements = [];
          if ($entity) {
            // @todo This needs a better way of discovering the context.
            if ($entity instanceof ViewEntityInterface) {
              // Views tokens require the ViewExecutable, not the config entity.
              // @todo Can we move this into metatag_views somehow?
              $token_replacements = ['view' => $entity->getExecutable()];
            }
            elseif ($entity instanceof ContentEntityInterface) {
              $token_replacements = [$entity->getEntityTypeId() => $entity];
            }
          }
          $processed_value = $this->processTagValue($tag, $value, $token_replacements, TRUE, $langcode);
          $this->processedTokenCache[$entity_identifier][$tag_name] = $tag->multiple() ? explode($tag->getSeparator(), $processed_value) : $processed_value;
        }
      }
    }

    return $this->processedTokenCache[$entity_identifier];
  }

  /**
   * Returns a list of fields handled by Metatag.
   *
   * @return array
   *   A list of supported field types.
   */
  protected function fieldTypes(): array {
    // @todo Either get this dynamically from field plugins or forget it and
    // just hardcode metatag where this is called.
    return ['metatag'];
  }

  /**
   * Sets tag value and returns sanitized value with token replaced.
   *
   * @param \Drupal\metatag\Plugin\metatag\Tag\MetaNameBase|object $tag
   *   Metatag object.
   * @param array|string $value
   *   Value to process.
   * @param array $token_replacements
   *   Arguments for token->replace().
   * @param bool $plain_text
   *   (optional) If TRUE, value will be formatted as a plain text. Defaults to
   *   FALSE.
   * @param string $langcode
   *   (optional) The language code to use for replacements; if not provided the
   *   current interface language code will be used.
   *
   * @return array|string
   *   Processed value.
   */
  protected function processTagValue($tag, $value, array $token_replacements, bool $plain_text = FALSE, $langcode = ''): array|string {
    // Set the value as sometimes the data needs massaging, such as when
    // field defaults are used for the Robots field, which come as an array
    // that needs to be filtered and converted to a string.
    // @see Robots::setValue()
    $tag->setValue($value);

    // Obtain the processed value. Some meta tags will store this as a
    // string, so support that option.
    // @todo Is there a better way of doing this? It seems unclean.
    $value = $tag->value();

    // Make sure the value is always handled as an array, but track whether it
    // was actually passed in as an array.
    $is_array = is_array($value);
    if (!$is_array) {
      $value = [$value];
    }

    // If a langcode was not specified, use the current interface language.
    if (empty($langcode)) {
      $langcode = $this->languageManager
        ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
        ->getId();
    }

    // Create options for handling token replacements, setting the current
    // language and a custom delimiter for multiple value fields in tokens.
    $options = [
      'langcode' => $langcode,
      'join' => ',',
    ];

    // Loop over each item in the array.
    foreach ($value as $key => $value_item) {
      // Process the tokens in this value and decode any HTML characters that
      // might be found.
      if (!empty($value_item) && is_string($value_item)) {
        if (strpos($value_item, '[') !== FALSE) {
          $value[$key] = htmlspecialchars_decode($this->tokenService->replace($value_item, $token_replacements, $options));
        }
        $value[$key] = htmlspecialchars_decode($value[$key]);
      }

      // If requested, run the value through the render system.
      if ($plain_text && !empty($value[$key])) {
        $value[$key] = PlainTextOutput::renderFromHtml($value[$key]);
      }
    }

    // If the original value was passed as an array return the whole value,
    // otherwise return the first item from the array.
    return $is_array ? $value : reset($value);
  }

}
