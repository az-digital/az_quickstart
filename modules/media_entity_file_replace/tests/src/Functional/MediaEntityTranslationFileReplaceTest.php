<?php

declare(strict_types=1);

namespace Drupal\Tests\media_entity_file_replace\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the file replacement feature with content translation.
 *
 * @group media_entity_file_replace
 */
class MediaEntityTranslationFileReplaceTest extends MediaEntityFileReplaceTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'image',
  ];

  /**
   * Tests the basic functionality of the module with translation in the scope.
   */
  public function testFileReplacement(): void {
    // Create a language and enable the translation for document media.
    ConfigurableLanguage::createFromLangcode('hu')->save();
    \Drupal::service('content_translation.manager')->setEnabled('media', 'document', TRUE);
    $field_config = FieldConfig::load('media.document.field_media_file');
    $field_config->setTranslatable(TRUE);
    $field_config->save();

    $user = $this->drupalCreateUser([
      'access media overview',
      'administer media form display',
      'view media',
      'administer media',
      'access content',
      'translate any entity',
    ]);
    $this->drupalLogin($user);

    $page = $this->getSession()->getPage();

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

    // Save the original file for later assertions.
    $originalDocument = $this->loadMediaEntityByName('Foobar');
    $file_entity_1 = $this->loadFileEntity($originalDocument->getSource()->getSourceFieldValue($originalDocument));

    // Now enable the file replacement widget for document media bundle.
    $this->drupalGet('/admin/structure/media/manage/document/form-display');
    $page->fillField('fields[replace_file][region]', 'content');
    $page->pressButton('Save');

    // When creating new media, we should not see the replacement field.
    $this->drupalGet("/media/add/document");
    $this->assertSession()->fieldNotExists('files[replacement_file]');
    $this->assertSession()->fieldNotExists('keep_original_filename');

    // Assert the file replacement field is not visible when adding a new
    // translation.
    $this->drupalGet("/hu/media/{$originalDocument->id()}/edit/translations/add/en/hu");
    $this->assertSession()->fieldNotExists('files[replacement_file]');
    $this->assertSession()->fieldNotExists('keep_original_filename');

    // Create a translation for the document media.
    $values = $originalDocument->toArray();
    $values['name'] = 'Foobar HU';
    $originalDocument->addTranslation('hu', $values);
    $originalDocument->save();

    // And there should be additional fields for uploading replacement file and
    // controlling behavior for overwriting it.
    $this->drupalGet("/media/{$originalDocument->id()}/edit");
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

    // Assert the translation file was overridden since it was referencing the
    // same file as the default language one.
    $translation = $updatedDocument->getTranslation('hu');
    $translationFile = $this->loadFileEntity($translation->getSource()->getSourceFieldValue($translation));
    $this->assertEquals($file_entity_1->id(), $translationFile->id());
    $this->assertEquals($file_entity_1->getFileUri(), $translationFile->getFileUri());
    $this->assertEquals('file_1.txt', $translationFile->getFilename());
    $this->assertEquals('file 2', file_get_contents($translationFile->getFileUri()));
    $this->assertFalse($translationFile->isTemporary());

    // When we edit the translation, we should not see our replacement widget,
    // otherwise we will be able to override the default language file from a
    // translation.
    $this->drupalGet("/hu/media/{$originalDocument->id()}/edit");
    $this->assertSession()->buttonExists('Remove');
    $this->assertSession()->fieldNotExists('files[replacement_file]');
    $this->assertSession()->fieldNotExists('keep_original_filename');

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

    // Now assert that the translation file didn't change since we didn't
    // override the referenced one but replaced with file_3.
    $translation = $updatedDocument->getTranslation('hu');
    $translationFile = $this->loadFileEntity($translation->getSource()->getSourceFieldValue($translation));
    $this->assertEquals($file_entity_1->id(), $translationFile->id());
    $this->assertEquals($file_entity_1->getFileUri(), $translationFile->getFileUri());
    $this->assertEquals('file_1.txt', $translationFile->getFilename());
    $this->assertEquals('file 2', file_get_contents($translationFile->getFileUri()));
    $this->assertFalse($translationFile->isTemporary());

    // Do a replacement in the translation with override. We are able to do that
    // because the file_entity_1 is not referenced by the english one anymore.
    $this->drupalGet("/hu/media/{$originalDocument->id()}/edit");
    $this->uploadReplacementFile('temporary://file_translation.txt', 'file translation', TRUE);

    // Assert the new document has the same name, id, uri, but different content
    // as the original file. This means the file name will be kept.
    $updatedDocument = $this->loadMediaEntityByName('Foobar');
    $translation = $updatedDocument->getTranslation('hu');
    $translationFile = $this->loadFileEntity($translation->getSource()->getSourceFieldValue($translation));
    $this->assertEquals($file_entity_1->id(), $translationFile->id());
    $this->assertEquals($file_entity_1->getFileUri(), $translationFile->getFileUri());
    $this->assertEquals('file_1.txt', $translationFile->getFilename());
    $this->assertEquals('file translation', file_get_contents($translationFile->getFileUri()));
    $this->assertFalse($translationFile->isTemporary());

    // Assert the default language file didn't change.
    $updatedFile = $this->loadFileEntity($updatedDocument->getSource()->getSourceFieldValue($updatedDocument));
    $this->assertEquals('file 3', file_get_contents($updatedFile->getFileUri()));
    $this->assertEquals('file_3.txt', $updatedFile->getFilename());

    // Do a replacement in the translation without override.
    $this->drupalGet("/hu/media/{$originalDocument->id()}/edit");
    $this->uploadReplacementFile('temporary://file_4.txt', 'file 4', FALSE);

    // Assert the english translation didn't change.
    $updatedDocument = $this->loadMediaEntityByName('Foobar');
    $file_entity_3 = $this->loadFileEntity($updatedDocument->getSource()->getSourceFieldValue($updatedDocument));
    $this->assertEquals('file_3.txt', $file_entity_3->getFilename());
    $this->assertEquals('file 3', file_get_contents($file_entity_3->getFileUri()));
    $this->assertFalse($file_entity_3->isTemporary());

    // Assert the translation file changed to new one.
    $translation = $updatedDocument->getTranslation('hu');
    $translationFile = $this->loadFileEntity($translation->getSource()->getSourceFieldValue($translation));
    $this->assertEquals('file_4.txt', $translationFile->getFilename());
    $this->assertEquals('file 4', file_get_contents($translationFile->getFileUri()));
    $this->assertFalse($translationFile->isTemporary());

    // The old file entity should still exist, and should not be marked as
    // temporary since editing the document entity created a revision and the
    // old revision still references the old document.
    $originalFile = $this->loadFileEntity($file_entity_1->id());
    $this->assertFalse($originalFile->isTemporary());

    // Verify that when uploading a replacement and overwriting the original,
    // the file extension is forced to be the same.
    $originalDocument = $this->loadMediaEntityByName('Foobar');
    $this->drupalGet("/media/{$originalDocument->id()}/edit");
    $this->uploadReplacementFile('temporary://file_5.pdf', 'file 5', TRUE);
    $this->assertSession()->pageTextContains('Only files with the following extensions are allowed: txt');
    $this->assertSession()->addressEquals("/media/{$originalDocument->id()}/edit");
    // It should be allowed if we opt NOT to overwrite the original though.
    $this->uploadReplacementFile('temporary://file_5.pdf', 'file 5', FALSE);
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

    // Do the same for the translation.
    $translation = $originalDocument->getTranslation('hu');
    $fileToDelete = $this->loadFileEntity($translation->getSource()->getSourceFieldValue($translation));
    $fileToDelete->delete();
    $this->drupalGet("/hu/media/{$originalDocument->id()}/edit");
    $this->assertSession()->fieldNotExists('files[replacement_file]');
  }

  /**
   * Tests the image media type file replacement in the context of translations.
   */
  public function testImageFileReplacement(): void {
    $this->createMediaType('image', [
      'id' => 'image',
      'label' => 'Image',
    ]);

    // Create a language and enable the translation for image media.
    ConfigurableLanguage::createFromLangcode('hu')->save();
    \Drupal::service('content_translation.manager')->setEnabled('media', 'image', TRUE);
    $field_config = FieldConfig::load('media.image.field_media_image');
    $field_config->setTranslatable(TRUE);
    $field_config->save();

    $user = $this->drupalCreateUser([
      'access media overview',
      'administer media form display',
      'view media',
      'administer media',
      'access content',
      'translate any entity',
    ]);
    $this->drupalLogin($user);

    $page = $this->getSession()->getPage();

    // Now enable the file replacement widget for image media bundle.
    $this->drupalGet('/admin/structure/media/manage/image/form-display');
    $page->fillField('fields[replace_file][region]', 'content');
    $page->pressButton('Save');

    $this->drupalGet('/media/add/image');

    // Assert the replacement field is not visible.
    $this->assertSession()->pageTextNotContains('Replace file');
    $this->assertSession()->fieldNotExists('files[replacement_file]');

    // Upload an image.
    $page->fillField('Name', 'Foobar');
    $page->attachFileToField('Image', \Drupal::root() . '/core/misc/druplicon.png');
    $page->pressButton('Save');
    $page->fillField('Alternative text', 'Foobar image');
    $page->pressButton('Save');
    $this->assertSession()->addressEquals('admin/content/media');

    // Save the original file for later assertions.
    $originalImage = $this->loadMediaEntityByName('Foobar');
    $file_entity_1 = $this->loadFileEntity($originalImage->getSource()->getSourceFieldValue($originalImage));

    // Get a new test image.
    $file_system = \Drupal::service('file_system');
    $test_files = $this->getTestFiles('image');
    foreach ($test_files as $test_file) {
      if ($test_file->filename === 'image-test.png') {
        $png_path = $file_system->realpath($test_file->uri);
      }
      if ($test_file->filename === 'image-test.jpg') {
        $jpg_path = $file_system->realpath($test_file->uri);
      }
    }

    // Now make a replacement with content override.
    $this->drupalGet("/media/{$originalImage->id()}/edit");
    $page->attachFileToField('File', $jpg_path);
    $page->checkField('keep_original_filename');
    $page->pressButton('Save');

    // We should get an error that the file extensions don't match.
    $this->assertSession()->pageTextContains('Only files with the following extensions are allowed: png.');
    $this->assertSession()->pageTextContains('Unable to upload replacement file.');

    // Now upload the png instead.
    $page->attachFileToField('File', $png_path);
    $page->pressButton('Save');
    $this->assertSession()->pageTextNotContains('Only files with the following extensions are allowed: png.');
    $this->assertSession()->pageTextNotContains('Unable to upload replacement file.');
    $this->assertSession()->addressEquals('admin/content/media');

    // Reload the media and assert the image content was replaced but the name
    // is maintained.
    $updatedImage = $this->loadMediaEntityByName('Foobar');
    $file_entity_2 = $this->loadFileEntity($updatedImage->getSource()->getSourceFieldValue($updatedImage));
    $this->assertEquals($file_entity_1->id(), $file_entity_2->id());
    $this->assertEquals($file_entity_1->getFileUri(), $file_entity_2->getFileUri());
    $this->assertEquals('druplicon.png', $file_entity_2->getFilename());
    $this->assertFalse($file_entity_2->isTemporary());

    // Assert the size of the file was updated. We can't compare the contents of
    // the files because they are images, so we rely on the file size
    // difference.
    $this->assertNotEquals($file_entity_1->getSize(), $file_entity_2->getSize());

    // Make a replacement without the override, so we replace the file not just
    // the content.
    $this->drupalGet("/media/{$originalImage->id()}/edit");
    $page->attachFileToField('File', $jpg_path);
    $page->uncheckField('keep_original_filename');
    $page->pressButton('Save');
    $this->assertSession()->addressEquals('admin/content/media');

    // Reload the media and assert the image is replaced.
    $updatedImage = $this->loadMediaEntityByName('Foobar');
    $file_entity_3 = $this->loadFileEntity($updatedImage->getSource()->getSourceFieldValue($updatedImage));
    $this->assertNotEquals($file_entity_2->id(), $file_entity_3->id());
    $this->assertNotEquals($file_entity_2->getFileUri(), $file_entity_3->getFileUri());
    $this->assertEquals('image-test.jpg', $file_entity_3->getFilename());
    $this->assertFalse($file_entity_3->isTemporary());

    $this->assertNotEquals($file_entity_2->getSize(), $file_entity_3->getSize());

    // Add a new translation.
    $this->drupalGet("/hu/media/{$updatedImage->id()}/edit/translations/add/en/hu");
    $this->assertSession()->fieldNotExists('files[replacement_file]');
    $this->assertSession()->fieldNotExists('keep_original_filename');

    // The remove button is in place, because we see the image from the default
    // language. If we allowed override here, we would risk of changing the
    // image file on the default language.
    $this->assertSession()->buttonExists('Remove');

    // Upload a translation file.
    $page->pressButton('Remove');
    $page->fillField('Name', 'HU Foobar');
    $page->attachFileToField('Image', \Drupal::root() . '/core/misc/druplicon.png');
    $page->pressButton('Save');
    // The "Alternative text" field is required after uploading the image.
    $page->fillField('Alternative text', 'HU Foobar image');
    $page->pressButton('Save');
    $this->assertSession()->addressEquals('hu/admin/content/media');

    // Get the original translation file for assertions.
    $updatedImage = $this->loadMediaEntityByName('Foobar');
    $translation = $updatedImage->getTranslation('hu');
    $translation_file_1 = $this->loadFileEntity($translation->getSource()->getSourceFieldValue($translation));
    // The name will increment to 0 because we used the same name before.
    $this->assertEquals('druplicon_0.png', $translation_file_1->getFilename());

    $this->drupalGet("/hu/media/{$updatedImage->id()}/edit");

    // Now do an image override on the translation.
    $page->attachFileToField('File', $jpg_path);
    $page->checkField('keep_original_filename');
    $page->pressButton('Save');

    // We should get an error that the file extensions don't match.
    $this->assertSession()->pageTextContains('Only files with the following extensions are allowed: png.');
    $this->assertSession()->pageTextContains('Unable to upload replacement file.');

    // Now upload the png instead.
    $page->attachFileToField('File', $png_path);
    $page->pressButton('Save');
    $this->assertSession()->pageTextNotContains('Only files with the following extensions are allowed: png.');
    $this->assertSession()->pageTextNotContains('Unable to upload replacement file.');
    $this->assertSession()->addressEquals('hu/admin/content/media');

    // Assert the file content was overridden.
    $updatedImage = $this->loadMediaEntityByName('Foobar');
    $translation = $updatedImage->getTranslation('hu');
    $translation_file_2 = $this->loadFileEntity($translation->getSource()->getSourceFieldValue($translation));
    $this->assertEquals($translation_file_1->id(), $translation_file_2->id());
    $this->assertEquals($translation_file_1->getFileUri(), $translation_file_2->getFileUri());
    // File name incremented previously because we already used once this png.
    $this->assertEquals('druplicon_0.png', $translation_file_2->getFilename());
    $this->assertFalse($translation_file_2->isTemporary());

    // Assert the file size is different from the original.
    $this->assertNotEquals($translation_file_1->getSize(), $translation_file_2->getSize());

    // Now replace the file completely.
    $this->drupalGet("/hu/media/{$originalImage->id()}/edit");
    $page->attachFileToField('File', $png_path);
    $page->uncheckField('keep_original_filename');
    $page->pressButton('Save');
    $this->assertSession()->addressEquals('hu/admin/content/media');

    // Reload the media and assert the image is replaced.
    $updatedImage = $this->loadMediaEntityByName('Foobar');
    $translation = $updatedImage->getTranslation('hu');
    $translation_file_3 = $this->loadFileEntity($translation->getSource()->getSourceFieldValue($translation));
    $this->assertNotEquals($translation_file_2->id(), $translation_file_3->id());
    $this->assertNotEquals($translation_file_2->getFileUri(), $translation_file_3->getFileUri());
    $this->assertEquals('image-test.png', $translation_file_3->getFilename());
    $this->assertFalse($translation_file_3->isTemporary());

    // Delete the translation.
    $updatedImage->removeTranslation('hu');
    $updatedImage->save();

    // Make the file column in the image field non-translatable.
    $property_settings = [
      'alt' => 'alt',
      'title' => 'title',
      'file' => 0,
    ];
    $field_config->setThirdPartySetting('content_translation', 'translation_sync', $property_settings);
    $field_config->save();

    // Proceed with adding a new translation to the image media.
    $this->drupalGet("/hu/media/{$updatedImage->id()}/edit/translations/add/en/hu");
    // Since the file in the image field is not translatable and visible on the
    // form, we don't allow replacement because we would change the original.
    $this->assertSession()->fieldNotExists('files[replacement_file]');
    $this->assertSession()->fieldNotExists('keep_original_filename');

    // Now make the image field itself non-translatable.
    $field_config->setTranslatable(FALSE);
    $field_config->save();

    // Proceed with adding a new translation to the image media.
    $this->drupalGet("/hu/media/{$updatedImage->id()}/edit/translations/add/en/hu");
    // Since the image field is not translatable and visible on the form, it
    // acts just as the default language version where we allow replacement.
    $this->assertSession()->fieldExists('files[replacement_file]');
    $this->assertSession()->fieldExists('keep_original_filename');

    // Now make the non-translatable fields hidden on the translation form.
    $contentLanguageSettings = ContentLanguageSettings::load('media.image');
    $setting = ['untranslatable_fields_hide' => 1];
    $contentLanguageSettings->setThirdPartySetting('content_translation', 'bundle_settings', $setting);
    $contentLanguageSettings->save();

    // Navigate to the translation form, and make sure our widget is not there.
    $this->drupalGet("/hu/media/{$updatedImage->id()}/edit/translations/add/en/hu");
    $this->assertSession()->fieldNotExists('files[replacement_file]');
    $this->assertSession()->fieldNotExists('keep_original_filename');
  }

}
