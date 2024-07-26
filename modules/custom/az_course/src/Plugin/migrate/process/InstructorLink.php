<?php

namespace Drupal\az_course\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns a link to a user, or a non-link.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess('instructor_link')]
class InstructorLink extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $link = ['uri' => 'route:<nolink>', 'title' => $value];

    // This is the API placeholder for an unassigned section.
    if ($value === 'netid-not-found') {
      $link['title'] = 'unassigned';
    }
    else {
      // See if there is a person with a matching netid.
      $persons = $this->entityTypeManager->getStorage('node')->loadByProperties([
        'field_az_netid' => $value,
        'status' => [1, TRUE],
      ]);
      if (!empty($persons)) {
        $person = reset($persons);
        $link['uri'] = 'entity:node/' . $person->id();
      }
    }

    return $link;
  }

}
