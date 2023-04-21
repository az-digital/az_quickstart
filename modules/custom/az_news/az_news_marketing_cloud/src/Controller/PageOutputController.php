<?php
namespace Drupal\az_news_marketing_cloud\Controller;

class PageOutputController {

  /**
   * Returns a render-able array for a test page.
   */
  public function template() {

    // Do something with your variables here.
    $myText = 'This is not just a default text!';
    $myNumber = 1;
    $myArray = [1, 2, 3];

    return [
      // Your theme hook name.
      '#theme' => 'az_news_marketing_cloud',
      // Your variables.
      '#variable1' => $myText,
      '#variable2' => $myNumber,
      '#variable3' => $myArray,
    ];
  }
}
