<?php

namespace Drupal\Tests\metatag\Functional\Update;

use Drupal\Component\Serialization\Json;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests all of the v2 updates.
 *
 * This is a complicated task as the update script needs to accommodate both
 * field changes from serialized arrays to JSON encoded arrays, and deletion of
 * various meta tag plugins and submodule(s).
 *
 * How this works:
 * - The appropriate core fixture file is loaded.
 * - The Metatag v1 fixture file is loaded.
 * - testPostUpdates() runs.
 * - The list of meta tag values is defined; the global list is the same as the
 *   entity list only with the prefix "Global".
 * - The node field value is loaded and compared against the expected values.
 * - Global values are added to the default config and then verified.
 * - The update scripts are executed.
 * - The node meta tags are tested to confirm they were removed or updated as
 *   expected.
 * - The default configuration is tested to confirm they were removed or updated
 *   as expected.
 *
 * @todo Finish documenting this file.
 * @todo Expand to handle multiple languages.
 * @todo Expand to handle revisions.
 * @todo Expand to have Metatag fields on multiple entity types.
 * @todo Expand to have multiple Metatag fields, with different field names.
 *
 * @group metatag
 */
class V2UpdatesTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    // Drupal 10 uses the D9 core fixture, D11 uses the D10 fixture.
    $core9 = static::getDrupalRoot() . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz';
    $core10 = static::getDrupalRoot() . '/core/modules/system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz';
    if (file_exists($core9)) {
      $this->databaseDumpFiles = [
        $core9,
      ];
    }
    else {
      $this->databaseDumpFiles = [
        $core10,
      ];
    }

    // Load the Metatag v1 data dump on top of the core data dump.
    $this->databaseDumpFiles[] = __DIR__ . '/../../../fixtures/d8_metatag_v1.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function doSelectionTest() {
    parent::doSelectionTest();

    // Verify that the v2 post post-update script is present.
    $this->assertSession()->responseContains('Convert all fields to use JSON storage.');

    // Verify that the GooglePlus-removal post-update scripts are present.
    $this->assertSession()->responseContains('Remove meta tags entity values that were removed in v2.');
    $this->assertSession()->responseContains('Remove meta tags from default configurations that were removed in v2.');
    $this->assertSession()->responseContains('Uninstall submodule(s) deprecated in v2: GooglePlus.');
  }

  /**
   * Tests whether the post-update scripts works correctly.
   */
  public function testPostUpdates() {
    $global_description = 'This is an example description.';

    // Meta tags that will not be removed.
    $tags_retained = [
      'description' => 'This is a Metatag v1 meta tag.',
      'title' => 'Testing | [site:name]',
      'robots' => 'index, nofollow, noarchive',
    ];

    // The meta tags that will be removed.
    $tags_removed = [
      // For #3065441.
      'google_plus_author' => 'GooglePlus Author tag test value for #3065441.',
      'google_plus_description' => 'GooglePlus Description tag test value for #3065441.',
      'google_plus_name' => 'GooglePlus Name tag test value for #3065441.',
      'google_plus_publisher' => 'GooglePlus Publisher tag test value for #3065441.',

      // For #2973351.
      'news_keywords' => 'News Keywords tag test value for #2973351.',
      'standout' => 'Standout tag test value for #2973351.',

      // For #3132065.
      'twitter_cards_data1' => 'Data1 tag test for #3132065.',
      'twitter_cards_data2' => 'Data2 tag test for #3132065.',
      'twitter_cards_dnt' => 'Do Not Track tag test for #3132065.',
      'twitter_cards_gallery_image0' => 'Gallery Image0 tag test for #3132065.',
      'twitter_cards_gallery_image1' => 'Gallery Image1 tag test for #3132065.',
      'twitter_cards_gallery_image2' => 'Gallery Image2 tag test for #3132065.',
      'twitter_cards_gallery_image3' => 'Gallery Image3 tag test for #3132065.',
      'twitter_cards_image_height' => 'Image Height tag test for #3132065.',
      'twitter_cards_image_width' => 'Image Width tag test for #3132065.',
      'twitter_cards_label1' => 'Label1 tag test for #3132065.',
      'twitter_cards_label2' => 'Label2 tag test for #3132065.',
      'twitter_cards_page_url' => 'Page URL tag test for #3132065.',

      // For #3217263.
      'content_language' => 'Content Language tag test for #3217263.',

      // For #3361816.
      'google_rating' => 'Google Rating tag test for #3361816',
    ];

    // Global tags test values are the same as the entity tags only they have
    // the word "Global" at the start.
    $global_tags = [];
    foreach ($tags_removed as $tag => $value) {
      $global_tags[$tag] = 'Global ' . $value;
    }

    // Perform these checks for both the main field and the revision field.
    foreach (['node__field_meta_tags', 'node_revision__field_meta_tags'] as $table_name) {
      // Confirm the data started as a serialized array.
      $query = \Drupal::database()->select($table_name);
      $query->addField($table_name, 'field_meta_tags_value');
      $result = $query->execute();
      $records = $result->fetchAll();

      // Verify the data loads correctly and is the old format.
      // @see metatag_post_update_v2_01_change_fields_to_json()
      $this->assertTrue(count($records) === 1);
      $this->assertTrue(strpos($records[0]->field_meta_tags_value, 'a:') === 0);
      $data = unserialize($records[0]->field_meta_tags_value, ['allowed_classes' => FALSE]);

      // Confirm each of the retained tags exists.
      foreach ($tags_retained as $tag_name => $tag_value) {
        $this->assertTrue(isset($data[$tag_name]));
        if (isset($data[$tag_name])) {
          $this->assertTrue($data[$tag_name] === $tag_value);
        }
      }

      // Verify each of the expected meta tags is present and has the expected
      // value.
      // @see metatag_post_update_v2_02_remove_entity_values()
      foreach ($tags_removed as $tag_name => $tag_value) {
        $this->assertTrue(isset($data[$tag_name]));
        $this->assertEquals($data[$tag_name], $tag_value);
      }

      // Verify the Twitter Card "type" value is present and has the expected
      // value.
      $this->assertTrue(isset($data['twitter_cards_type']));
      $this->assertEquals($data['twitter_cards_type'], 'gallery');
    }

    // Set up examples of each meta tag that is being removed in a default
    // configuration so that it can be confirmed later on to have been removed.
    // @see metatag_post_update_v2_03_remove_config_values()
    $config = $this->config('metatag.metatag_defaults.global');
    $tags = $config->get('tags');
    foreach ($global_tags as $tag_name => $tag_value) {
      $tags[$tag_name] = $tag_value;
    }

    // Add a deprecated Twitter Card Type for #3132062.
    $tags['twitter_cards_type'] = 'photo';

    // Also add some example tags that aren't being removed, to make sure that
    // the configuration works correctly.
    $tags['description'] = $global_description;
    $config->set('tags', $tags);
    $config->save();
    $config = $this->config('metatag.metatag_defaults.global');
    $tags = $config->get('tags');

    // Make sure the example description tag is still present.
    $this->assertTrue(isset($tags['description']));
    $this->assertEquals($tags['description'], $global_description);

    // Verify each of the global tags is present.
    foreach ($global_tags as $tag_name => $tag_value) {
      $this->assertTrue(isset($tags[$tag_name]));
      $this->assertEquals($tags[$tag_name], $tag_value);
    }

    // Verify the Twitter Card "type" tag is present and has the correct value.
    $this->assertTrue(isset($tags['twitter_cards_type']));
    $this->assertEquals($tags['twitter_cards_type'], 'photo');

    $this->runUpdates();

    // Make sure that the data still loads correctly, i.e. that the data was
    // successfully converted to the new structure.
    // @see metatag_post_update_v2_01_change_fields_to_json()
    foreach (['node__field_meta_tags', 'node_revision__field_meta_tags'] as $table_name) {
      $query = \Drupal::database()->select($table_name);
      $query->addField($table_name, 'field_meta_tags_value');
      $result = $query->execute();
      $records = $result->fetchAll();
      $this->assertTrue(count($records) === 1);
      $this->assertTrue(strpos($records[0]->field_meta_tags_value, '{"') === 0);
      $data = Json::decode($records[0]->field_meta_tags_value);

      // Confirm each of the retained tags still exists.
      foreach ($tags_retained as $tag_name => $tag_value) {
        $this->assertTrue(isset($data[$tag_name]));
        if (isset($data[$tag_name])) {
          $this->assertTrue($data[$tag_name] === $tag_value);
        }
      }

      // Make sure the meta tag was removed.
      // @see metatag_post_update_v2_02_remove_entity_values()
      foreach ($tags_removed as $tag_name => $tag_value) {
        $this->assertTrue(!isset($data[$tag_name]));
      }

      // Verify the Twitter Card "type" value has been changed.
      $this->assertTrue(isset($data['twitter_cards_type']));
      $this->assertEquals($data['twitter_cards_type'], 'summary_large_image');
    }

    // @see metatag_post_update_v2_03_remove_config_values()
    $config = $this->config('metatag.metatag_defaults.global');
    $tags = $config->get('tags');

    // Make sure the example description tag is still present.
    $this->assertTrue(isset($tags['description']));
    $this->assertEquals($tags['description'], $global_description);

    // Verify each of the global tags is no longer present.
    foreach ($global_tags as $tag_name => $tag_value) {
      $this->assertTrue(!isset($tags[$tag_name]));
    }

    // Make sure the Twitter Card Type value has been changed.
    // @see metatag_post_update_v2_05_twitter_type_changes()
    $this->assertTrue(isset($tags['twitter_cards_type']));
    $this->assertEquals($tags['twitter_cards_type'], 'summary_large_image');
  }

}
