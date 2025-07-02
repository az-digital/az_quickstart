<?php

namespace Drupal\linkit\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\link\LinkItemInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;
use Drupal\linkit\ProfileInterface;
use Drupal\linkit\SubstitutionManagerInterface;
use Drupal\linkit\Utility\LinkitHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'linkit' formatter.
 *
 * @FieldFormatter(
 *   id = "linkit",
 *   label = @Translation("Linkit"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkitFormatter extends LinkFormatter {

  /**
   * The substitution manager.
   *
   * @var \Drupal\linkit\SubstitutionManagerInterface
   */
  protected $substitutionManager;

  /**
   * The linkit profile storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkitProfileStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->substitutionManager = $container->get('plugin.manager.linkit.substitution');
    $instance->linkitProfileStorage = $container->get('entity_type.manager')->getStorage('linkit_profile');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'linkit_profile' => 'default',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $options = array_map(function ($linkit_profile) {
      return $linkit_profile->label();
    }, $this->linkitProfileStorage->loadMultiple());

    $elements['linkit_profile'] = [
      '#type' => 'select',
      '#title' => $this->t('Linkit profile'),
      '#description' => $this->t('Must be the same as the profile selected on the form display for this field.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('linkit_profile'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $linkit_profile_id = $this->getSetting('linkit_profile');
    $linkit_profile = $this->linkitProfileStorage->load($linkit_profile_id);

    if ($linkit_profile) {
      $summary[] = $this->t('Linkit profile: @linkit_profile', ['@linkit_profile' => $linkit_profile->label()]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $settings = $this->getSettings();

    // Loop over the elements and substitute the URL.
    foreach ($elements as $delta => &$item) {
      /** @var \Drupal\link\LinkItemInterface $link_item */
      $link_item = $items->get($delta);
      $item_url = $this->buildUrl($link_item);
      $item_url_attributes = $item_url->getOption('attributes');
      if ($url = $this->getSubstitutedUrl($link_item)) {
        if ($url instanceof CacheableDependencyInterface) {
          $cacheable_url = $url;
        }
        // Keep query and fragment.
        $parsed_url = parse_url($link_item->uri);
        if (!empty($parsed_url['query'])) {
          $parsed_query = [];
          parse_str($parsed_url['query'], $parsed_query);
          if (!empty($parsed_query)) {
            $url->setOption('query', $parsed_query);
          }
        }
        if (!empty($parsed_url['fragment'])) {
          $url->setOption('fragment', $parsed_url['fragment']);
        }
        $attributes = (array) $url->getOption('attributes');
        $attributes = array_merge($item_url_attributes ?: [], $attributes ?: []);
        // Restore rel and target options.
        if (!empty($settings['rel'])) {
          $attributes['rel'] = $settings['rel'];
        }
        if (!empty($settings['target'])) {
          $attributes['target'] = $settings['target'];
        }
        $url->setOption('attributes', $attributes);
        $cacheable_metadata = BubbleableMetadata::createFromRenderArray($item);
        if (isset($cacheable_url)) {
          // Add cache dependency on the URL, if supported.
          // Currently, this is only the case if the substitution uses legacy
          // GeneratedUrl, but Url objects might as well be cacheable in the
          // future; see https://www.drupal.org/project/drupal/issues/3358113
          $cacheable_metadata->addCacheableDependency($cacheable_url);
        }
        if ($entity = LinkitHelper::getEntityFromUserInput($link_item->uri)) {
          $cacheable_metadata->addCacheableDependency($entity);
        }
        $cacheable_metadata->applyTo($item);
        $item['#url'] = $url;
      }
    }

    return $elements;
  }

  /**
   * Returns a substitution URL for the given linked item.
   *
   * In case the items links to an entity use a substituted/generated URL.
   *
   * @param \Drupal\link\LinkItemInterface $item
   *   The link item.
   *
   * @return \Drupal\Core\Url|\Drupal\Core\GeneratedUrl|null
   *   The substitution URL, or NULL if not able to retrieve it from the item.
   */
  protected function getSubstitutedUrl(LinkItemInterface $item) {
    // First try to derive entity information from Linkit-specific attributes.
    // This is more reliable and is required for File entities.
    if (!empty($item->options['data-entity-type']) && !empty($item->options['data-entity-uuid'])) {
      /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
      $entity_repository = \Drupal::service('entity.repository');
      $entity = $entity_repository->loadEntityByUuid($item->options['data-entity-type'], $item->options['data-entity-uuid']);
      if ($entity instanceof EntityInterface) {
        $entity = $entity_repository->getTranslationFromContext($entity);
      }
    }
    else {
      $entity = LinkitHelper::getEntityFromUserInput($item->uri);
    }
    if ($entity instanceof EntityInterface) {
      $linkit_profile = $this->linkitProfileStorage->load($this->getSettings()['linkit_profile']);

      if (!$linkit_profile instanceof ProfileInterface) {
        return NULL;
      }

      /** @var \Drupal\linkit\Plugin\Linkit\Matcher\EntityMatcher $matcher */
      $matcher = $linkit_profile->getMatcherByEntityType($entity->getEntityTypeId());
      $substitution_type = $matcher ? $matcher->getConfiguration()['settings']['substitution_type'] : SubstitutionManagerInterface::DEFAULT_SUBSTITUTION;
      return $this->substitutionManager->createInstance($substitution_type)->getUrl($entity);
    }
    return NULL;
  }

}
