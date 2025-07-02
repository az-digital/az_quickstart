<?php

namespace Drupal\schema_metatag;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SchemaMetatagClient.
 *
 * A class to parse Schema.org data.
 *
 * @package Drupal\schema_metatag
 */
class SchemaMetatagClient implements SchemaMetatagClientInterface {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The scheme used in the provided Schema.org data.
   *
   * @var string
   */
  protected static $scheme = 'http';

  /**
   * The object prefix used in the provided Schema.org data.
   *
   * @var string
   */
  protected static $prefix = 'http://schema.org/';

  /**
   * Construct a Schema client object.
   *
   * This can be used to query schema.org or a stored copy of the schema.org
   * definitions to construct object lists and determine hierarchy.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance.
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, CacheBackendInterface $cache_backend, LoggerInterface $logger) {
    $this->moduleHandler = $moduleHandler;
    $this->cacheBackend = $cache_backend;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('schema_metatag.cache'),
      $container->get('logger.channel.schema_metatag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalFile() {

    $path = DRUPAL_ROOT . '/' . $this->moduleHandler->getModule('schema_metatag')->getPath();
    $uri = $path . '/data/schemaorg-all-http.jsonld';
    try {
      if ($response = file_get_contents($uri)) {
        $data = json_decode($response, TRUE);
        if (is_array($data) && array_key_exists('@graph', $data)) {
          return $data['@graph'];
        }
      }
    }
    catch (RequestException $e) {
      $this->logger->error($e);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function objectInfo($clear = FALSE) {
    $cid = "schema_metatag.objects";
    if (!$clear && $cache = $this->cacheBackend->get($cid)) {
      $items = $cache->data;
    }
    else {
      $data = $this->getLocalFile();
      $items = [];
      foreach ($data as $item) {
        if ($this->isIncludedClass($item)) {
          $subobject_of = [];
          $object = is_array($item['rdfs:label'])
            ? $item['rdfs:label']['@value']
            : $item['rdfs:label'];
          $description = $item['rdfs:comment'];
          if (array_key_exists('rdfs:subClassOf', $item)) {
            foreach ($item['rdfs:subClassOf'] as $value) {
              if (!is_array($value)) {
                $value = [$value];
              }
              foreach ($value as $value_item) {
                $subobject_of[] = str_replace(static::$prefix, '', $value_item);
              }
            }
          }

          $description = strip_tags($description);

          $items[$object] = [
            'object' => $object,
            'description' => $description,
            'parents' => $subobject_of,
          ];
        }
      }
      // Cache permanently.
      $this->cacheBackend->set($cid, $items, CacheBackendInterface::CACHE_PERMANENT);
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function propertyInfo($clear = FALSE) {
    $cid = "schema_metatag.properties";
    if (!$clear && $cache = $this->cacheBackend->get($cid)) {
      $items = $cache->data;
    }
    else {
      $data = $this->getLocalFile();
      $items = [];
      foreach ($data as $item) {
        if ($this->isIncludedProperty($item)) {
          $expected_types = $belongs_to = [];
          $property = $item['rdfs:label'];
          $description = $item['rdfs:comment'];
          foreach ($item[static::$prefix . 'rangeIncludes'] as $value) {
            foreach ((array) $value as $value_item) {
              $expected_types[] = str_replace(static::$prefix, '', $value_item);
            }
          }
          if (!empty($expected_types)) {
            foreach ($item[static::$prefix . 'domainIncludes'] as $value) {
              foreach ((array) $value as $value_item) {
                $class = str_replace(static::$prefix, '', $value_item);
                $belongs_to[] = $class;
              }
            }
            foreach ($belongs_to as $parent) {
              $items[$parent][$property] = [
                'property' => $property,
                'description' => strip_tags($description),
                'expected_types' => array_unique($expected_types),
              ];
            }
          }
        }
        // Cache permanently.
        $this->cacheBackend->set($cid, $items, CacheBackendInterface::CACHE_PERMANENT);
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getObjectTree($clear = FALSE, $clear_objects = FALSE) {
    $cid = "schema_metatag.tree";
    if (!$clear && !$clear_objects && $cache = $this->cacheBackend->get($cid)) {
      $tree = $cache->data;
    }
    else {
      $objects = $this->objectInfo($clear_objects);
      $tree = [];
      $flat = [];
      foreach ($objects as $child => $item) {
        if (empty($item['parents'])) {
          $tree[$child] =& $flat[$child];
        }
        else {
          foreach ($item['parents'] as $parent) {
            if (!isset($flat[$child])) {
              $flat[$child] = [];
            }
            $flat[$parent][$child] =& $flat[$child];
          }
        }
      }
      // Sort the result, keeping the nested structure.
      $this->sortAssocArray($tree);

      // Cache permanently.
      $this->cacheBackend->set($cid, $tree, CacheBackendInterface::CACHE_PERMANENT);

    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function getTree($parent_name = NULL, $depth = -1, $clear = FALSE, $clear_tree = FALSE, $clear_objects = FALSE) {
    $cid = "schema_metatag.$parent_name.$depth";
    if (!$clear && !$clear_tree && !$clear_objects && $cache = $this->cacheBackend->get($cid)) {
      $tree = $cache->data;
    }
    else {
      // Get the whole tree.
      $base_tree = $this->getObjectTree($clear_tree, $clear_objects);
      $tree = $this->getUncachedTree($base_tree, $parent_name, $depth);
      $this->cacheBackend->set($cid, $tree, CacheBackendInterface::CACHE_PERMANENT);

    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function getUncachedTree($base_tree, $parent_name = NULL, $depth = -1) {
    $tree = [];

    // If we have a desired parent, pull that out of the tree.
    if (!empty($parent_name)) {

      // Use an iterator for ease in traversing this multidimensional array.
      $iterator = new \RecursiveArrayIterator($base_tree);
      $iterator = new \RecursiveIteratorIterator($iterator,
        \RecursiveIteratorIterator::SELF_FIRST);

      // Find the selected parent, which might be several levels down.
      foreach ($iterator as $key => $value) {
        if ($key == $parent_name) {
          // The array below the parent level is the tree we want.
          $tree = [$parent_name => $iterator->getInnerIterator()->offsetGet($key)];
          break;
        }
      }
    }
    // Otherwise use the whole tree.
    else {
      $tree = $base_tree;
    }

    // If we want to limit the depth of the selected section, iterate over the
    // tree and unset values below that depth. We aren't applying the depth
    // to the original tree, but to whatever part of the tree we retrieved
    // in the prior step.
    if ($depth !== -1) {

      // Use a new iterator to traverse the selected section of the tree.
      $iterator = new \RecursiveArrayIterator($tree);
      $iterator = new \RecursiveIteratorIterator($iterator,
        \RecursiveIteratorIterator::SELF_FIRST);

      // Tell the iterator to skip lower depths than $depth when iterating.
      // We don't want to iterate down into them and undo the work of emptying
      // their parent.
      $iterator->setMaxDepth($depth);

      // Iterate over top level items, the ones <= the chosen depth.
      foreach ($iterator as $key => $value) {

        // Whenever we hit the desired final depth, swap in an empty array for
        // the remainder of that branch, then go backwards up that branch of
        // the tree fixing all the parent arrays to match (each of which
        // contain all their children, including the child we changed).
        if ($iterator->getDepth() == $depth) {
          $child_depth = $iterator->getDepth();
          for ($current_depth = $child_depth; $current_depth >= 0; $current_depth--) {
            // Get the current level iterator and its parent.
            $child_iterator = $iterator->getSubIterator($current_depth);
            $parent_iterator = $iterator->getSubIterator(($current_depth + 1));

            // If we are on the level we want to change, use the new value,
            // otherwise set the parent iterators value.
            if ($child_depth === $current_depth) {
              // The child gets an empty array.
              $replacement = [];
            }
            else {
              // Get a fresh copy of the parent, which will now include the
              // new value for the child.
              $replacement = $parent_iterator->getArrayCopy();
            }
            $child_iterator->offsetSet($child_iterator->key(), $replacement);
          }
        }
      }
      // Replace the tree with our new values.
      $tree = $iterator->getArrayCopy();
    }
    return $tree;

  }

  /**
   * {@inheritDoc}
   */
  public function getParents($child_name) {
    $parents = [];
    $tree = $this->getTree();
    $iterator = new \RecursiveArrayIterator($tree);
    $iterator = new \RecursiveIteratorIterator($iterator,
      \RecursiveIteratorIterator::SELF_FIRST);
    $depth = 0;
    $prev_depth = 0;
    $prev_key = '';
    foreach ($iterator as $key => $value) {
      $depth = $iterator->getDepth();
      if ($depth > $prev_depth) {
        $parents[] = $prev_key;
      }
      elseif ($depth < $prev_depth) {
        for ($i = $depth; $i < $prev_depth; $i++) {
          array_pop($parents);
        }
      }
      if ($key == $child_name) {
        return $parents;
      }
      $prev_depth = $depth;
      $prev_key = $key;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getOptionList($parent_name = NULL, $depth = -1) {
    $tree = $this->getTree($parent_name, $depth);
    $separator = '-';
    $label_list = [];
    $previous_keys = [];
    $key_list = [];
    $iterator = new \RecursiveArrayIterator($tree);
    $iterator = new \RecursiveIteratorIterator($iterator,
      \RecursiveIteratorIterator::SELF_FIRST);

    // Some objects have multiple hierarchies, make sure we only provide
    // options for one of them to avoid problems when the keys are used in the
    // final option list. Basically we'll show the first one we encounter and
    // ignore it if it appears later.
    foreach ($iterator as $key => $item) {
      if (!in_array($key, $previous_keys)) {
        $depth = $iterator->getDepth() + 1;
        $label_list[] = str_repeat($separator, $depth) . ' ' . $key;
        $key_list[] = $key;
        $previous_keys[] = $key;
      }

    }
    // We have to create two separate arrays for types and labels rather than
    // using the object name as the key because the numerical keys keep the
    // whole array in the correct order, maintaining the nested hierarchy.
    $option_list = array_combine($key_list, $label_list);
    return $option_list;
  }

  /**
   * {@inheritdoc}
   */
  public function clearData() {
    $this->cacheBackend->invalidateAll();
  }

  /**
   * {@inheritdoc}
   */
  public function sortAssocArray(array &$array) {
    ksort($array);
    foreach ($array as &$a) {
      if (is_array($a)) {
        self::sortAssocArray($a);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isIncludedClass(array $item) {
    if (!isset($item['@type']) || !isset($item['rdfs:label'])) {
      return FALSE;
    }
    if ($item['@type'] != 'rdfs:Class') {
      return FALSE;
    }
    if (!empty($item[static::$prefix . 'supersededBy'])) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isIncludedProperty(array $item) {
    if (!isset($item['@type'])) {
      return FALSE;
    }
    if ($item['@type'] != 'rdf:Property') {
      return FALSE;
    }
    if (!empty($item[static::$prefix . 'supersededBy'])) {
      return FALSE;
    }
    return TRUE;
  }

}
