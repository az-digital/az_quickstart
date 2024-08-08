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

    $instructors = [];
    $links = [];

    // Depending on course, value may be a single element.
    if (!is_array($value)) {
      $value = !empty($value) ? [$value] : [];
    }

    /** @var array \SimpleXMLElement $xml */
    $elements = $value;
    // Get the child components of the instructor element.
    foreach ($elements as $xml) {
      if (!empty($xml->netid)) {
        $netid = (string) $xml->netid;
        // Prefer fullname if there is one.
        $fullname = (!empty($xml->fullname)) ? ((string) $xml->fullname) : $netid;
        // Use netid as key to prevent duplicates.
        $instructors[$netid] = $fullname;
      }
    }

    // Remove placeholder for sections with no instructor.
    unset($instructors['netid-not-found']);

    // Create links to each unique instructor.
    foreach ($instructors as $netid => $fullname) {
      $link = ['uri' => 'route:<nolink>', 'title' => $fullname];
      // See if there is a person with a matching netid.
      $persons = $this->entityTypeManager->getStorage('node')->loadByProperties([
        'field_az_netid' => $netid,
        'type' => 'az_person',
        'status' => [1, TRUE],
      ]);
      if (!empty($persons)) {
        $person = reset($persons);
        $link['uri'] = 'entity:node/' . $person->id();
      }
      $links[] = $link;
    }

    return $links;
  }

}
