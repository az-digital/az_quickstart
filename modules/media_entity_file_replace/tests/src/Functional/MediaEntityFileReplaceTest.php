<?php

declare(strict_types=1);

namespace Drupal\Tests\media_entity_file_replace\Functional;

/**
 * Tests the file replacement feature.
 *
 * @group media_entity_file_replace
 */
class MediaEntityFileReplaceTest extends MediaEntityFileReplaceTestBase {

  /**
   * Tests the basic functionality of the module.
   */
  public function testFileReplacement(): void {
    $user = $this->drupalCreateUser([
      'access media overview',
      'administer media form display',
      'view media',
      'administer media',
      'access content',
    ]);
    $this->drupalLogin($user);

    // Begin by confirming that our custom file replacement widget is available
    // on form display configurations for media bundles that use a file source.
    $this->drupalGet('/admin/structure/media/manage/document/form-display');
    $this->assertSession()->fieldExists("fields[replace_file][region]");
    $this->assertSession()->fieldValueEquals('fields[replace_file][region]', 'hidden');

    // But not on media bundles that don't use a file source, like remote video.
    $this->drupalGet('/admin/structure/media/manage/remote_video/form-display');
    $this->assertSession()->fieldNotExists("fields[replace_file][weight]");

    // While we're here, enable the name field so we can manually provide a name
    // for remote videos. This just makes tests easier.
    $page = $this->getSession()->getPage();
    $page->fillField('fields[name][region]', 'content');
    $page->pressButton('Save');

    // Create a video media entity and confirm we don't see the replacement
    // widget on the edit screen.
    $this->drupalGet('/media/add/remote_video');
    $this->assertSession()->pageTextNotContains('Replace file');
    $this->assertSession()->fieldNotExists('files[replacement_file]');
    $page->fillField('Name', 'DrupalCon Amsterdam Keynote');
    $page->fillField('Remote video URL', 'https://www.youtube.com/watch?v=Apqd4ff0NRI');
    $page->pressButton('Save');
    $this->assertSession()->pageTextContains('Remote video DrupalCon Amsterdam Keynote has been created.');
    $page->clickLink('DrupalCon Amsterdam Keynote');
    $this->assertSession()->fieldExists('Remote video URL');
    $this->assertSession()->fieldNotExists('files[replacement_file]');

    // Create a document entity and confirm it works as usual.
    // The file replacement widget should not appear on this form since we did
    // not enable the new replacement widget on the form display yet.
    $this->drupalGet('/media/add/document');
    $file_1 = [
      'uri' => 'temporary://file_1.txt',
      'data' => 'file 1 original',
    ];
    file_put_contents($file_1['uri'], $file_1['data']);
    $this->assertSession()->pageTextNotContains('Replace file');
    $page->fillField('Name', 'Foobar');
    $page->attachFileToField('File', \Drupal::service('file_system')->realpath($file_1['uri']));
    $this->assertSession()->fieldNotExists('files[replacement_file]');
    $page->pressButton('Save');
    $this->assertSession()->addressEquals('admin/content/media');
    unlink($file_1['uri']);

    // Reload the document from storage.
    $originalDocument = $this->loadMediaEntityByName('Foobar');
    $file_entity_1 = $this->loadFileEntity($originalDocument->getSource()->getSourceFieldValue($originalDocument));

    // Edit the document and confirm the remove button for the default file
    // widget is there, since our pseudo widget which normally removes it is not
    // yet active.
    $this->drupalGet("/media/{$originalDocument->id()}/edit");
    $this->assertSession()->buttonExists('Remove');

    // Now enable the file replacement widget for document media bundle.
    $this->drupalGet('/admin/structure/media/manage/document/form-display');
    $page->fillField('fields[replace_file][region]', 'content');
    $page->pressButton('Save');

    // When creating new media, we should not see the replacement field.
    $this->drupalGet("/media/add/document");
    $this->assertSession()->fieldNotExists('files[replacement_file]');
    $this->assertSession()->fieldNotExists('keep_original_filename');

    // Edit the document again. The "remove" button on the default file
    // widget should be removed now.
    $this->drupalGet("/media/{$originalDocument->id()}/edit");
    $this->assertSession()->buttonNotExists('Remove');

    // And there should be additional fields for uploading replacement file and
    // controlling behavior for overwriting it.
    $this->assertSession()->fieldExists('files[replacement_file]');
    $this->assertSession()->fieldExists('keep_original_filename');

    // Upload a replacement file with new contents and different file name,
    // overwriting the original file.
    $this->uploadReplacementFile('temporary://file_2.txt', 'file 2', TRUE);

    // Reload document and confirm the filename and URI have not changed, but
    // the contents of the file have.
    $updatedDocument = $this->loadMediaEntityByName('Foobar');
    $file_entity_2 = $this->loadFileEntity($updatedDocument->getSource()->getSourceFieldValue($updatedDocument));
    $this->assertEquals($file_entity_1->id(), $file_entity_2->id());
    $this->assertEquals($file_entity_1->getFileUri(), $file_entity_2->getFileUri());
    $this->assertEquals('file_1.txt', $file_entity_2->getFilename());
    $this->assertEquals('file 2', file_get_contents($file_entity_2->getFileUri()));
    $this->assertFalse($file_entity_2->isTemporary());

    // Assert the size of the file was updated.
    $this->assertNotEquals($file_entity_1->getSize(), $file_entity_2->getSize());

    // Now upload another replacement document, but this time don't overwrite
    // the original file.
    $this->drupalGet("/media/{$originalDocument->id()}/edit");
    $this->uploadReplacementFile('temporary://file_3.txt', 'file 3', FALSE);

    // Verify that the file associated with the document has a different name
    // and content since we didn't override the original.
    $updatedDocument = $this->loadMediaEntityByName('Foobar');
    $file_entity_3 = $this->loadFileEntity($updatedDocument->getSource()->getSourceFieldValue($updatedDocument));
    $this->assertEquals('file_3.txt', $file_entity_3->getFilename());
    $this->assertEquals('file 3', file_get_contents($file_entity_3->getFileUri()));
    $this->assertFalse($file_entity_3->isTemporary());

    // The old file entity should still exist, and should not be marked as
    // temporary since editing the document entity created a revision and the
    // old revision still references the old document.
    $originalFile = $this->loadFileEntity($file_entity_1->id());
    $this->assertFalse($originalFile->isTemporary());

    // Verify that when uploading a replacement and overwriting the original,
    // the file extension is forced to be the same.
    $originalDocument = $this->loadMediaEntityByName('Foobar');
    $this->drupalGet("/media/{$originalDocument->id()}/edit");
    $this->uploadReplacementFile('temporary://file_4.pdf', 'file 4', TRUE);
    $this->assertSession()->pageTextContains('Only files with the following extensions are allowed: txt');
    $this->assertSession()->addressEquals("/media/{$originalDocument->id()}/edit");
    // It should be allowed if we opt NOT to overwrite the original though.
    $this->uploadReplacementFile('temporary://file_4.pdf', 'file 4', FALSE);
    $this->assertSession()->pageTextNotContains('Only files with the following extensions are allowed: txt');
    $this->assertSession()->addressEquals("/admin/content/media");

    // Simulate deleting the file and then revisit the media entity. Since
    // there is no longer a file associated to the media entity, there is
    // nothing to replace and therefore the "replace_file" widget should
    // not show.
    $originalDocument = $this->loadMediaEntityByName('Foobar');
    $fileToDelete = $this->loadFileEntity($originalDocument->getSource()->getSourceFieldValue($originalDocument));
    $fileToDelete->delete();
    $this->drupalGet("/media/{$originalDocument->id()}/edit");
    $this->assertSession()->fieldNotExists('files[replacement_file]');
  }

}
