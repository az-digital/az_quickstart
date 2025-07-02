<?php

declare(strict_types=1);

namespace Drupal\file_mdm_exif\Plugin\FileMetadata;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file_mdm\Plugin\Attribute\FileMetadata;
use Drupal\file_mdm\Plugin\FileMetadata\FileMetadataPluginBase;
use Drupal\file_mdm_exif\ExifTagMapperInterface;
use lsolesen\pel\PelEntry;
use lsolesen\pel\PelEntryNumber;
use lsolesen\pel\PelExif;
use lsolesen\pel\PelIfd;
use lsolesen\pel\PelJpeg;
use lsolesen\pel\PelTiff;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

/**
 * FileMetadata plugin for EXIF.
 */
#[FileMetadata(
  id: 'exif',
  title: new TranslatableMarkup('EXIF'),
  help: new TranslatableMarkup('File metadata plugin for EXIF image information, using the PHP Exif Library (PEL).')
)]
class Exif extends FileMetadataPluginBase {

  /**
   * The MIME type guessing service.
   */
  protected readonly MimeTypeGuesserInterface $mimeTypeGuesser;

  /**
   * The EXIF tag mapping service.
   */
  protected readonly ExifTagMapperInterface $tagMapper;

  /**
   * The PEL file object being processed.
   */
  protected PelJpeg|PelTiff|false $pelFile;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->mimeTypeGuesser = $container->get('file.mime_type.guesser');
    $instance->tagMapper = $container->get(ExifTagMapperInterface::class);
    return $instance;
  }

  public function getSupportedKeys(array $options = NULL): array {
    return $this->tagMapper->getSupportedKeys($options);
  }

  /**
   * Returns the PEL file object for the image file.
   *
   * @return \lsolesen\pel\PelJpeg|\lsolesen\pel\PelTiff|false
   *   A PEL file object, or FALSE if it cannot be opened.
   */
  protected function getFile(): PelJpeg|PelTiff|false {
    if (!isset($this->pelFile)) {
      switch ($this->mimeTypeGuesser->guessMimeType($this->getUri())) {
        case 'image/jpeg':
          $this->pelFile = new PelJpeg($this->getLocalTempPath());
          break;

        case 'image/tiff':
          $this->pelFile = new PelTiff($this->getLocalTempPath());
          break;

        default:
          $this->pelFile = FALSE;

      }
    }
    return $this->pelFile;
  }

  protected function doGetMetadataFromFile(): mixed {
    // Get the file as a PelJpeg or PelTiff object.
    $file = $this->getFile();
    if (!$file) {
      return [];
    }

    // Get the TIFF section if existing, or return if not.
    if ($file instanceof PelJpeg) {
      $exif = $file->getExif();
      if ($exif === NULL) {
        return [];
      }
      $tiff = $exif->getTiff();
      if ($tiff === NULL) {
        return [];
      }
    }
    else {
      $tiff = $file;
    }

    // Scans metadata for entries of supported tags.
    $metadata = [];
    $keys = $this->tagMapper->getSupportedKeys();
    foreach ($keys as $key) {
      $ifd_tag = $this->tagMapper->resolveKeyToIfdAndTag($key);
      if ($entry = $this->getEntry($tiff, $ifd_tag['ifd'], $ifd_tag['tag'])) {
        $metadata[$ifd_tag['ifd']][$ifd_tag['tag']] = $entry;
      }
    }
    return $metadata;
  }

  /**
   * Returns a PelEntry.
   *
   * @param \lsolesen\pel\PelTiff $tiff
   *   A PelTiff object.
   * @param int $ifd_tag
   *   The IFD EXIF integer identifier.
   * @param int $key_tag
   *   The TAG EXIF integer identifier.
   *
   * @return \lsolesen\pel\PelEntry|null
   *   The PelEntry for the specified IFD and TAG.
   */
  protected function getEntry(PelTiff $tiff, int $ifd_tag, int $key_tag): ?PelEntry {
    $ifd = $tiff->getIfd();
    switch ($ifd_tag) {
      case PelIfd::IFD0:
        return $ifd->getEntry($key_tag);

      case PelIfd::IFD1:
        $ifd1 = $ifd->getNextIfd();
        if ($ifd1 === NULL) {
          return NULL;
        }
        return $ifd1->getEntry($key_tag);

      case PelIfd::EXIF:
        $exif = $ifd->getSubIfd(PelIfd::EXIF);
        if ($exif === NULL) {
          return NULL;
        }
        return $exif->getEntry($key_tag);

      case PelIfd::INTEROPERABILITY:
        $exif = $ifd->getSubIfd(PelIfd::EXIF);
        if ($exif === NULL) {
          return NULL;
        }
        $interop = $exif->getSubIfd(PelIfd::INTEROPERABILITY);
        if ($interop === NULL) {
          return NULL;
        }
        return $interop->getEntry($key_tag);

      case PelIfd::GPS:
        $gps = $ifd->getSubIfd(PelIfd::GPS);
        if ($gps === NULL) {
          return NULL;
        }
        return $gps->getEntry($key_tag);

    }

    return NULL;
  }

  public function isSaveToFileSupported(): bool {
    return TRUE;
  }

  protected function doSaveMetadataToFile(): bool {
    // Get the file as a PelJpeg or PelTiff object.
    $file = $this->getFile();
    if (!$file) {
      return FALSE;
    }

    // Get the TIFF section if existing, or create one if not.
    if ($file instanceof PelJpeg) {
      $exif = $file->getExif();
      if ($exif === NULL) {
        // If EXIF section is missing we simply create a new APP1 section
        // (a PelExif object) and add it to the PelJpeg object.
        $exif = new PelExif();
        $file->setExif($exif);
      }
      $tiff = $exif->getTiff();
      if ($tiff === NULL) {
        // Same for TIFF section.
        $tiff = new PelTiff();
        $exif->setTiff($tiff);
      }
    }
    else {
      $tiff = $file;
    }

    // Get IFD0 if existing, or create it if not.
    $ifd0 = $tiff->getIfd();
    if ($ifd0 === NULL) {
      // No IFD in the TIFF data, we just create and insert an empty PelIfd
      // object.
      $ifd0 = new PelIfd(PelIfd::IFD0);
      $tiff->setIfd($ifd0);
    }

    // Loops through in-memory metadata and update tag entries accordingly.
    foreach ($this->metadata as $ifd_id => $entries) {
      switch ($ifd_id) {
        case PelIfd::IFD0:
          $this->setIfdEntries($ifd0, $entries);
          break;

        case PelIfd::IFD1:
          $ifd1 = $ifd0->getNextIfd();
          if ($ifd1 === NULL) {
            $ifd1 = new PelIfd(PelIfd::IFD1);
            $ifd0->setNextIfd($ifd1);
          }
          $this->setIfdEntries($ifd1, $entries);
          break;

        case PelIfd::EXIF:
          $exif = $ifd0->getSubIfd(PelIfd::EXIF);
          if ($exif === NULL) {
            $exif = new PelIfd(PelIfd::EXIF);
            $ifd0->addSubIfd($exif);
          }
          $this->setIfdEntries($exif, $entries);
          break;

        case PelIfd::INTEROPERABILITY:
          $exif = $ifd0->getSubIfd(PelIfd::EXIF);
          if ($exif === NULL) {
            $exif = new PelIfd(PelIfd::EXIF);
            $ifd0->addSubIfd($exif);
          }
          $interop = $exif->getSubIfd(PelIfd::INTEROPERABILITY);
          if ($interop === NULL) {
            $interop = new PelIfd(PelIfd::INTEROPERABILITY);
            $exif->addSubIfd($interop);
          }
          $this->setIfdEntries($interop, $entries);
          break;

        case PelIfd::GPS:
          $gps = $ifd0->getSubIfd(PelIfd::GPS);
          if ($gps === NULL) {
            $gps = new PelIfd(PelIfd::GPS);
            $ifd0->addSubIfd($gps);
          }
          $this->setIfdEntries($gps, $entries);
          break;

      }
    }

    return $file->saveFile($this->getLocalTempPath()) === FALSE ? FALSE : TRUE;
  }

  /**
   * Adds or changes entries for an IFD.
   *
   * @param \lsolesen\pel\PelIfd $ifd
   *   A PelIfd object.
   * @param array<string|PelEntry> $entries
   *   An array of PelEntry objects.
   *
   * @return bool
   *   TRUE if entries were added/changed successfully, FALSE otherwise.
   */
  protected function setIfdEntries(PelIfd $ifd, array $entries): bool {
    foreach ($entries as $tag => $input_entry) {
      if ($c = $ifd->getEntry($tag)) {
        if ($input_entry === 'deleted') {
          unset($ifd[$tag]);
        }
        else {
          if ($this->getFile() instanceof PelJpeg) {
            $c->setValue($input_entry->getValue());
          }
          else {
            $v = $input_entry->getValue();
            if (is_array($v) && $c instanceof PelEntryNumber) {
              $c->setValueArray($v);
            }
            else {
              $c->setValue($v);
            }
          }
        }
      }
      else {
        if ($input_entry !== 'deleted') {
          $ifd->addEntry($input_entry);
        }
      }
    }
    return TRUE;
  }

  protected function doGetMetadata(mixed $key = NULL): mixed {
    if (!$this->metadata) {
      return NULL;
    }
    if (!$key) {
      return $this->metadata;
    }
    else {
      $ifd_tag = $this->tagMapper->resolveKeyToIfdAndTag($key);
      if (!isset($this->metadata[$ifd_tag['ifd']][$ifd_tag['tag']]) || $this->metadata[$ifd_tag['ifd']][$ifd_tag['tag']] === 'deleted') {
        return NULL;
      }
      $entry = $this->metadata[$ifd_tag['ifd']][$ifd_tag['tag']];
      return [
        'value' => $entry->getValue(),
        'text' => $entry->getText(),
      ];
    }
  }

  protected function doSetMetadata(mixed $key, mixed $value): bool {
    $ifd_tag = $this->tagMapper->resolveKeyToIfdAndTag($key);
    if ($value instanceof PelEntry) {
      $this->metadata[$ifd_tag['ifd']][$ifd_tag['tag']] = $value;
      return TRUE;
    }
    elseif (isset($this->metadata[$ifd_tag['ifd']][$ifd_tag['tag']])) {
      if (is_array($value)) {
        $this->metadata[$ifd_tag['ifd']][$ifd_tag['tag']]->setValueArray($value);
      }
      else {
        $this->metadata[$ifd_tag['ifd']][$ifd_tag['tag']]->setValue($value);
      }
      return TRUE;
    }
    return FALSE;
  }

  protected function doRemoveMetadata(mixed $key): bool {
    if (!$this->metadata || !$key) {
      return FALSE;
    }
    else {
      $ifd_tag = $this->tagMapper->resolveKeyToIfdAndTag($key);
      if (isset($this->metadata[$ifd_tag['ifd']][$ifd_tag['tag']])) {
        $this->metadata[$ifd_tag['ifd']][$ifd_tag['tag']] = 'deleted';
        return TRUE;
      }
      return FALSE;
    }
  }

}
