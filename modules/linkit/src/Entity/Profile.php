<?php

namespace Drupal\linkit\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\linkit\MatcherCollection;
use Drupal\linkit\MatcherInterface;
use Drupal\linkit\Plugin\Linkit\Matcher\EntityMatcher;
use Drupal\linkit\ProfileInterface;

/**
 * Defines the linkit profile entity.
 *
 * @ConfigEntityType(
 *   id = "linkit_profile",
 *   label = @Translation("Linkit profile"),
 *   handlers = {
 *     "list_builder" = "Drupal\linkit\ProfileListBuilder",
 *     "form" = {
 *       "add" = "Drupal\linkit\Form\Profile\AddForm",
 *       "edit" = "Drupal\linkit\Form\Profile\EditForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer linkit profiles",
 *   config_prefix = "linkit_profile",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "collection" = "/admin/config/content/linkit",
 *     "edit-form" = "/admin/config/content/linkit/manage/{linkit_profile}",
 *     "delete-form" = "/admin/config/content/linkit/manage/{linkit_profile}/delete"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "description",
 *     "matchers"
 *   }
 * )
 */
class Profile extends ConfigEntityBase implements ProfileInterface, EntityWithPluginCollectionInterface {

  /**
   * The ID of this profile.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label of this profile.
   *
   * @var string
   */
  protected $label;

  /**
   * Description of this profile.
   *
   * @var string
   */
  protected $description;

  /**
   * Configured matchers for this profile.
   *
   * An associative array of matchers assigned to the profile, keyed by the
   * matcher ID of each matcher and using the properties:
   * - id: The plugin ID of the matchers instance.
   * - status: (optional) A Boolean indicating whether the matchers is enabled
   *   in the profile. Defaults to FALSE.
   * - weight: (optional) The weight of the matchers in the profile.
   *   Defaults to 0.
   *
   * @var array
   */
  protected $matchers = [];

  /**
   * Holds the collection of matchers that are attached to this profile.
   *
   * @var \Drupal\linkit\MatcherCollection
   */
  protected $matcherCollection;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description');
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', trim($description));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMatcher($instance_id) {
    return $this->getMatchers()->get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getMatcherByEntityType($entity_type_id) {
    foreach ($this->getMatchers() as $matcher) {
      if ($matcher instanceof EntityMatcher && $matcher->getPluginDefinition()['target_entity'] === $entity_type_id) {
        return $matcher;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMatchers() {
    if (!$this->matcherCollection) {
      $this->matcherCollection = new MatcherCollection(\Drupal::service('plugin.manager.linkit.matcher'), $this->matchers);
      $this->matcherCollection->sort();
    }
    return $this->matcherCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function addMatcher(array $configuration) {
    $configuration['uuid'] = $this->uuidGenerator()->generate();
    $this->getMatchers()->addInstanceId($configuration['uuid'], $configuration);
    return $configuration['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function removeMatcher(MatcherInterface $matcher) {
    $this->getMatchers()->removeInstanceId($matcher->getUuid());
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMatcherConfig($instance_id, array $configuration) {
    $this->matchers[$instance_id] = $configuration;
    $this->getMatchers()->setInstanceConfiguration($instance_id, $configuration);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'matchers' => $this->getMatchers(),
    ];
  }

}
