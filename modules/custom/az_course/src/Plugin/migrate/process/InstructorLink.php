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

    $instructor = [];
    /** @var \SimpleXMLElement $xml */
    $xml = $value;
    // Get the child components of the instructor element.
    foreach ($xml->children() as $node) {
      $instructor[$node->getName()] = (string) $node;
    }

    $netid = $instructor['netid'] ?? 'netid-not-found';
    $fullname = $instructor['fullname'] ?? '';
    // We prefer to display their fullname if possible.
    $displayname = (!empty($fullname)) ? $fullname : $netid;
    $link = ['uri' => 'route:<nolink>', 'title' => $displayname];

    // This is the API placeholder for an unassigned section.
    if ($netid === 'netid-not-found') {
      $link['title'] = 'unassigned';
    }
    else {
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
    }

    return $link;
  }

}
