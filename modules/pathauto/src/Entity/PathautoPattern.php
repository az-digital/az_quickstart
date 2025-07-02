<?php

namespace Drupal\pathauto\Entity;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\Utility\Error;
use Drupal\pathauto\PathautoPatternInterface;

/**
 * Defines the Pathauto pattern entity.
 *
 * @ConfigEntityType(
 *   id = "pathauto_pattern",
 *   label = @Translation("Pathauto pattern"),
 *   handlers = {
 *     "list_builder" = "Drupal\pathauto\PathautoPatternListBuilder",
 *     "form" = {
 *       "default" = "Drupal\pathauto\Form\PatternEditForm",
 *       "duplicate" = "Drupal\pathauto\Form\PatternDuplicateForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "enable" = "Drupal\pathauto\Form\PatternEnableForm",
 *       "disable" = "Drupal\pathauto\Form\PatternDisableForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "pattern",
 *   admin_permission = "administer pathauto",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "type",
 *     "pattern",
 *     "selection_criteria",
 *     "selection_logic",
 *     "weight",
 *     "relationships"
 *   },
 *   lookup_keys = {
 *     "type",
 *     "status",
 *   },
 *   links = {
 *     "collection" = "/admin/config/search/path/patterns",
 *     "edit-form" = "/admin/config/search/path/patterns/{pathauto_pattern}",
 *     "delete-form" = "/admin/config/search/path/patterns/{pathauto_pattern}/delete",
 *     "enable" = "/admin/config/search/path/patterns/{pathauto_pattern}/enable",
 *     "disable" = "/admin/config/search/path/patterns/{pathauto_pattern}/disable",
 *     "duplicate-form" = "/admin/config/search/path/patterns/{pathauto_pattern}/duplicate"
 *   }
 * )
 */
class PathautoPattern extends ConfigEntityBase implements PathautoPatternInterface {

  /**
   * The Pathauto pattern ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Pathauto pattern label.
   *
   * @var string
   */
  protected $label;

  /**
   * The pattern type.
   *
   * A string denoting the type of pathauto pattern this is. For a node path
   * this would be 'node', for users it would be 'user', and so on. This allows
   * for arbitrary non-entity patterns to be possible if applicable.
   *
   * @var string
   */
  protected $type;

  /**
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $aliasTypeCollection;

  /**
   * A tokenized string for alias generation.
   *
   * @var string
   */
  protected $pattern;

  /**
   * The plugin configuration for the selection criteria condition plugins.
   *
   * @var array
   */
  protected $selection_criteria = [];

  /**
   * The selection logic for this pattern entity (either 'and' or 'or').
   *
   * @var string
   */
  protected $selection_logic = 'and';

  /**
   * @var int
   */
  protected $weight = 0;

  /**
   * @var array[]
   *   Keys are context tokens, and values are arrays with the following keys:
   *   - label (string|null, optional): The human-readable label of this
   *     relationship.
   */
  protected $relationships = [];

  /**
   * The plugin collection that holds the selection criteria condition plugins.
   *
   * @var \Drupal\Component\Plugin\LazyPluginCollection
   */
  protected $selectionConditionCollection;

  /**
   * {@inheritdoc}
   *
   * Not using core's default logic around ConditionPluginCollection since it
   * incorrectly assumes no condition will ever be applied twice.
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    $criteria = [];
    foreach ($this->getSelectionConditions() as $id => $condition) {
      $criteria[$id] = $condition->getConfiguration();
    }
    $this->selection_criteria = $criteria;

    // Invalidate the static caches.
    \Drupal::service('pathauto.generator')->resetCaches();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    // Invalidate the static caches.
    \Drupal::service('pathauto.generator')->resetCaches();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    $this->calculatePluginDependencies($this->getAliasType());

    foreach ($this->getSelectionConditions() as $instance) {
      $this->calculatePluginDependencies($instance);
    }

    return $this->getDependencies();
  }

  /**
   * {@inheritdoc}
   */
  public function getPattern() {
    return $this->pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setPattern($pattern) {
    $this->pattern = $pattern;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasType() {
    if (!$this->aliasTypeCollection) {
      $this->aliasTypeCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.alias_type'), $this->getType(), ['default' => $this->getPattern()]);
    }
    return $this->aliasTypeCollection->get($this->getType());
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContexts() {
    $contexts = $this->getAliasType()->getContexts();
    foreach ($this->getRelationships() as $token => $definition) {
      $context = $this->convertTokenToContext($token, $contexts);
      $context_definition = $context->getContextDefinition();
      if (!empty($definition['label'])) {
        $context_definition->setLabel($definition['label']);
      }
      $contexts[$token] = $context;
    }
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRelationship($token) {
    return isset($this->relationships[$token]);
  }

  /**
   * {@inheritdoc}
   */
  public function addRelationship($token, $label = NULL) {
    if (!$this->hasRelationship($token)) {
      $this->relationships[$token] = [
        'label' => $label,
      ];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceRelationship($token, $label) {
    if ($this->hasRelationship($token)) {
      $this->relationships[$token] = [
        'label' => $label,
      ];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeRelationship($token) {
    unset($this->relationships[$token]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationships() {
    return $this->relationships;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionConditions() {
    if (!$this->selectionConditionCollection) {
      $this->selectionConditionCollection = new ConditionPluginCollection(\Drupal::service('plugin.manager.condition'), $this->get('selection_criteria'));
    }
    return $this->selectionConditionCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function addSelectionCondition(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getSelectionConditions()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionCondition($condition_id) {
    return $this->getSelectionConditions()->get($condition_id);
  }

  /**
   * {@inheritdoc}
   */
  public function removeSelectionCondition($condition_id) {
    $this->getSelectionConditions()->removeInstanceId($condition_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectionLogic() {
    return $this->selection_logic;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($object) {
    if ($this->getAliasType()->applies($object)) {
      $definitions = $this->getAliasType()->getContextDefinitions();
      if (count($definitions) > 1) {
        throw new \Exception("Alias types do not support more than one context.");
      }
      $keys = array_keys($definitions);
      // Set the context object on our Alias plugin before retrieving contexts.
      $this->getAliasType()->setContextValue($keys[0], $object);
      /** @var \Drupal\Core\Plugin\Context\ContextInterface[] $base_contexts */
      $contexts = $this->getContexts();
      /** @var \Drupal\Core\Plugin\Context\ContextHandler $context_handler */
      $context_handler = \Drupal::service('context.handler');
      $conditions = $this->getSelectionConditions();
      foreach ($conditions as $condition) {

        // As the context object is kept and only the value is switched out,
        // it can over time grow to a huge number of cache contexts. Reset it
        // if there are 100 cache tags to prevent cache tag merging getting too
        // slow.
        foreach ($condition->getContextDefinitions() as $name => $context_definition) {
          if (count($condition->getContext($name)->getCacheTags()) > 100) {
            $condition->setContext($name, new Context($context_definition));
          }
        }

        if ($condition instanceof ContextAwarePluginInterface) {
          try {
            $context_handler->applyContextMapping($condition, $contexts);
          }
          catch (ContextException $e) {
            if (method_exists(Error::class, 'logException')) {
              Error::logException(\Drupal::logger('pathauto'), $e);
            }
            else {
              /* @phpstan-ignore-next-line */
              watchdog_exception('pathauto', $e);
            }
            return FALSE;
          }
        }
        $result = $condition->execute();
        if ($this->getSelectionLogic() == 'and' && !$result) {
          return FALSE;
        }
        elseif ($this->getSelectionLogic() == 'or' && $result) {
          return TRUE;
        }
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Extracts a context from an array of contexts by a tokenized pattern.
   *
   * This is more than simple isset/empty checks on the contexts array. The
   * pattern could be node:uid:name which will iterate over all provided
   * contexts in the array for one named 'node', it will then load the data
   * definition of 'node' and check for a property named 'uid'. This will then
   * set a new (temporary) context on the array and recursively call itself to
   * navigate through related properties all the way down until the request
   * property is located. At that point the property is passed to a
   * TypedDataResolver which will convert it to an appropriate ContextInterface
   * object.
   *
   * @param string $token
   *   A ":" delimited set of tokens representing
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   The array of available contexts.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface
   *   The requested token as a full Context object.
   *
   * @throws \Exception
   */
  public function convertTokenToContext(string $token, array $contexts) {
    // If the requested token is already a context, just return it.
    if (isset($contexts[$token])) {
      return $contexts[$token];
    }
    else {
      [$base, $property_path] = explode(':', $token, 2);
      // A base must always be set. This method recursively calls itself
      // setting bases for this reason.
      if (!empty($contexts[$base])) {
        return $this->getContextFromProperty($property_path, $contexts[$base]);
      }
      // @todo improve this exception message.
      throw new ContextNotFoundException("The requested context was not found in the supplied array of contexts.");
    }
  }

  /**
   * Convert a property to a context.
   *
   * This method will respect the value of contexts as well, so if a context
   * object is pass that contains a value, the appropriate value will be
   * extracted and injected into the resulting context object if available.
   *
   * @param string $property_path
   *   The name of the property.
   * @param \Drupal\Core\Plugin\Context\ContextInterface $context
   *   The context from which we will extract values if available.
   *
   * @return \Drupal\Core\Plugin\Context\Context
   *   A context object that represents the definition & value of the property.
   *
   * @throws \Exception
   */
  public function getContextFromProperty($property_path, ContextInterface $context) {
    $value = NULL;
    $data_definition = NULL;
    if ($context->hasContextValue()) {
      /** @var \Drupal\Core\TypedData\ComplexDataInterface $data */
      $data = $context->getContextData();
      foreach (explode(':', $property_path) as $name) {

        if ($data instanceof ListInterface) {
          if (!is_numeric($name)) {
            // Implicitly default to delta 0 for lists when not specified.
            $data = $data->first();
          }
          else {
            // If we have a delta, fetch it and continue with the next part.
            $data = $data->get($name);
            continue;
          }
        }

        // Forward to the target value if this is a data reference.
        if ($data instanceof DataReferenceInterface) {
          $data = $data->getTarget();
        }

        if (!$data->getDataDefinition()->getPropertyDefinition($name)) {
          throw new \Exception("Unknown property $name in property path $property_path");
        }
        $data = $data->get($name);
      }

      $value = $data->getValue();
      $data_definition = $data instanceof DataReferenceInterface ? $data->getDataDefinition()->getTargetDefinition() : $data->getDataDefinition();
    }
    else {
      /** @var \Drupal\Core\TypedData\ComplexDataDefinitionInterface $data_definition */
      $data_definition = $context->getContextDefinition()->getDataDefinition();
      foreach (explode(':', $property_path) as $name) {

        if ($data_definition instanceof ListDataDefinitionInterface) {
          $data_definition = $data_definition->getItemDefinition();

          // If the delta was specified explicitly, continue with the next part.
          if (is_numeric($name)) {
            continue;
          }
        }

        // Forward to the target definition if this is a data reference
        // definition.
        if ($data_definition instanceof DataReferenceDefinitionInterface) {
          $data_definition = $data_definition->getTargetDefinition();
        }

        if (!$data_definition->getPropertyDefinition($name)) {
          throw new \Exception("Unknown property $name in property path $property_path");
        }
        $data_definition = $data_definition->getPropertyDefinition($name);
      }

      // Forward to the target definition if this is a data reference
      // definition.
      if ($data_definition instanceof DataReferenceDefinitionInterface) {
        $data_definition = $data_definition->getTargetDefinition();
      }
    }
    if (strpos($data_definition->getDataType(), 'entity:') === 0) {
      $context_definition = new EntityContextDefinition($data_definition->getDataType(), $data_definition->getLabel(), $data_definition->isRequired(), FALSE, $data_definition->getDescription());
    }
    else {
      $context_definition = new ContextDefinition($data_definition->getDataType(), $data_definition->getLabel(), $data_definition->isRequired(), FALSE, $data_definition->getDescription());
    }
    return new Context($context_definition, $value);
  }

}
