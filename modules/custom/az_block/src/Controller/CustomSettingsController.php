<?php
namespace Drupal\az_block\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\block_content\BlockContentInterface;

class CustomSettingsController extends ControllerBase
{
  public function content(BlockContentInterface $block_content)
  {
    return [
      '#theme' => 'block_content',
    ];
  }
}
