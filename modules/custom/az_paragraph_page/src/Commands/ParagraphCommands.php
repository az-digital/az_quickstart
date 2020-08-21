<?php

namespace Drupal\az_paragraph_page\Commands;

use Drush\Commands\DrushCommands;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use joshtronic\LoremIpsum;

/**
 * A drush command file.
 *
 * @package Drupal\az_paragraph_page\Commands
 */
class ParagraphCommands extends DrushCommands {

  /**
   * Drush command that generates paragraphs.
   *
   * @param integer $numNodes
   *   Argument with number of nodes to create.
   * @param integer $numParagraphs
   *   Argument with number of paragraphs to generate.
   * @param integer $numRevisions
   *   Argument with number of revisions to generate.
   * @command az_paragraph_page:generate
   * @aliases paragraph-experiment
   * @usage az_paragraph_page:generate --uppercase --reverse drupal8
   */
  public function generate($numNodes = 10, $numParagraphs = 10, $numRevisions = 10) {

    $nodes = [];
    $lipsum = new LoremIpsum();

    // Create nodes.
    for($i=0; $i < $numNodes; $i++) {
      $node = Node::create([
        'type'        => 'az_paragraph_page',
        'title'       => $lipsum->words(5),
      ]);
      $paragraphs = [];
      // Create paragraph entities and assemble the field value.
      for($j=0; $j < $numParagraphs; $j++) {
        $paragraph = Paragraph::create([
          'type' => 'az_text_area',
          'field_body' => ['value' => $lipsum->paragraphs(1, 'p'), 'format' => 'full_html'],
        ]);
        $paragraph->save();
        $paragraphs[] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
      // Assign paragraphs.
      $node->set('field_az_flexible_content', $paragraphs);
      $node->save();
      $nodes[] = $node;
    }

    // Create some revisions.
    for($k=0; $k < $numRevisions; $k++) {
      foreach($nodes as $node) {
        foreach($node->field_az_flexible_content->referencedEntities() as $paragraph) {
          // Update SOME paragraphs.
          if (rand(0,10) < 4) {
            $paragraph->field_body->value = $lipsum->paragraphs(1, 'p');
            $paragraph->save();
          }
        }
        // Prepare to save a revision.
        $node->setNewRevision(TRUE);
        $node->setRevisionLogMessage($lipsum->words(7));
        $node->save();
      }
    }
  }

}
