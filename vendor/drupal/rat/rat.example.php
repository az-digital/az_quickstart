<?php
/**
 * @file RenderArrayTool examples.
 */

use Drupal\rat\v1\RenderArray;

// Creating a RenderArray.
$build = RenderArray::create();
$build->get('foo')->setValue(['#markup' => 'Yay!']);
$build->getDotted('bar.baz')->setValue(['#markup' => 'Yo!']);
// Get the result.
$array = $build->toRenderable();
// Get the result as reference.
$build->getValue()['boo'] = [['#markup' => 'Yow!']];

// Attach all kinds of stuff.
$build->attachLibrary('module/library');
$build->attachDrupalSettings('setting', 'value');
$build->attachHead('mymodule_script', ['#type' => 'html_tag', '#tag' => 'script', '#value' => 'alert("hello");']);
$build->addCacheability(\Drupal::config('mymodule')->get('settings'));

// Safely restrict access.
$build->restrictAccess(FALSE, $cacheability);
