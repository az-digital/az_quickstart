<?php

namespace Drupal\blazy\Media\Svg;

use Drupal\Component\Utility\Color;
use Drupal\Core\File\FileSystemInterface;
use Drupal\blazy\Media\BlazyFile;
use Drupal\blazy\internals\Internals;
use Drupal\file\Entity\File;
use enshrined\svgSanitize\Sanitizer;

/**
 * Provides Svg utility for blazy_file with SVG, and blur images.
 *
 * @todo make this class also functional for SVG blur.
 */
class Svg extends BlazyFile implements SvgInterface {

  /**
   * {@inheritdoc}
   */
  public function sanitize($file, array $options = []): ?string {
    $uri   = $file instanceof File ? $file->getFileUri() : $file;
    $ext   = pathinfo($uri, PATHINFO_EXTENSION);
    $ext   = strtolower($ext);
    $svg   = NULL;
    $valid = is_file($uri) && $ext == 'svg';
    $tmp   = $valid ? file_get_contents($uri) : $uri;

    if ($tmp && strpos($tmp, "<svg") !== FALSE) {
      $sanitize = $options['sanitize'] ?? TRUE;
      $sanitize_remote = $options['sanitize_remote'] ?? FALSE;

      if ($cleaned = $this->clean($tmp)) {
        $cleaned = $this->attributes($cleaned, $options);

        if ($sanitize && $sanitizer = $this->sanitizer()) {
          if ($sanitize_remote) {
            $sanitizer->removeRemoteReferences(TRUE);
          }
          $svg = $sanitizer->sanitize($cleaned);
        }
        else {
          $svg = $cleaned;
        }
      }
    }
    return $svg;
  }

  /**
   * {@inheritdoc}
   */
  public function sanitizer(): ?object {
    return class_exists(Sanitizer::class) ? new Sanitizer() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function transparentize(
    $uri,
    $destination,
    $color = '#ffffff',
    $fuzz = 20,
  ): ?string {
    $path = $this->realpath($uri);
    $dest = $this->realpath($destination);
    $name = pathinfo($path, PATHINFO_FILENAME);
    $dir1 = pathinfo($path, PATHINFO_DIRNAME);
    $dir2 = pathinfo($dest, PATHINFO_DIRNAME);
    $ext1 = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $ext2 = strtolower(pathinfo($dest, PATHINFO_EXTENSION));
    $ext3 = $ext2;
    $rgb  = Color::hexToRgb($color);
    $res  = NULL;

    // Prepare directory.
    if (!$this->fileSystem->prepareDirectory($dir2, FileSystemInterface::CREATE_DIRECTORY)) {
      return $res;
    }

    // @todo convert to supported transparent extensions: png, webp.
    // @todo make it an ImageEffect.
    if (!in_array($ext1, ['png', 'webp'])) {
      $ext3 = in_array($ext2, ['png', 'webp']) ? $ext2 : 'png';
      // @fixme, always succeed, but not working for JPGs.
      if ($this->toTransparentFormat($uri, $ext3)) {
        // $path = sprintf('%s/%s.%s', $dir1, $name, $ext3);
      }
    }

    // @see https://github.com/hackerb9/mktrans/
    // @todo mktrans expects non-transparent image for the source, else error.
    // @todo deal with the unexpected transparent source.
    if ($this->commandExists('mktrans')) {
      $tmp = sprintf('%s/%s-transparent.%s', $dir1, $name, $ext3);
      $arg = sprintf('-f %d %s', $fuzz, $path);

      $this->runOsShell('mktrans', $arg);

      // Destination is harcoded as NAME-transparent.png, move it.
      $replace = Internals::fileExistsReplace();
      $this->fileSystem->move($tmp, $dest, $replace);
      $res = $dest;
    }
    // Fallbacks to ImageMagick convert command, no real joy here.
    elseif ($this->commandExists('convert')) {
      // Convert -background none in.svg out.png.
      // $arg = sprintf('%s -fuzz %d%% -transparent %s %s', $path, $fuzz,
      // $color, $dest);.
      $arg = sprintf('%s -fuzz %d%% -transparent "rgb(%s,%s,%s)" %s', $path, $fuzz, $rgb['red'], $rgb['green'], $rgb['blue'], $dest);
      $this->runOsShell('convert', $arg);
      $res = $dest;
    }
    // Fallbacks to GD with minimum results.
    else {
      // @todo checks for .gif.
      $img = in_array($ext1, ['jpg', 'jpeg', 'jpe'])
        ? imagecreatefromjpeg($path) : imagecreatefrompng($path);
      $remove = imagecolorallocate($img, $rgb['red'], $rgb['green'], $rgb['blue']);
      imagecolortransparent($img, $remove);
      imagepng($img, $dest);

      $res = $dest;
      imagedestroy($img);
    }

    // Set standard file permissions for webserver-generated files.
    if ($res) {
      // @todo update database.
      // $replace = Internals::fileExistsReplace();
      // if ($file = Internals::loadByProperty('uri', $uri, 'file')) {
      // $this->fileRepository->move($file, $dest, $replace);
      // }
      if (isset($this->image) && $this->image->save($res)) {
        return $res;
      }
      elseif ($this->fileSystem->chmod($res)) {
        return $res;
      }
    }
    return $res;
  }

  /**
   * {@inheritdoc}
   *
   * Was planned to have more elaborate SVG works than ::sanitize() method:
   * transparentizing, vectorizing, rasterizing, blur, etc. via its options.
   * Dups for now, but no dups if we can make it. Perhaps at 4.x or so.
   */
  public function view($uri, array $options = []): ?string {
    return $this->sanitize($uri, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function vectorize($url, array $options = []): string {
    $converter = new Vectorizer($url, $options);
    return $converter->vectorize();
  }

  /**
   * Returns the modified SVG attributes based on the options.
   *
   * @param string $svg
   *   The SVG string.
   * @param array $options
   *   The attribute options.
   *
   * @return string
   *   The modified SVG string, or original.
   */
  protected function attributes($svg, array $options): string {
    $fill   = $options['fill'] ?? FALSE;
    $_title = $options['title'] ?? NULL;
    $width  = $height = NULL;
    $output = $svg;

    if ($attributes = $options['attributes'] ?? NULL) {
      $attributes = strip_tags($attributes);
      if (strpos($attributes, 'x') !== FALSE) {
        [$width, $height] = array_map('trim', explode('x', $attributes));
      }
    }

    if ($fill || $_title || ($width && $height)) {
      $dom = new \DOMDocument();
      libxml_use_internal_errors(TRUE);
      $dom->loadXML($svg);

      if (isset($dom->documentElement)) {
        // Credits: svg_image_field module.
        if ($fill) {
          $dom->documentElement->setAttribute('fill', 'currentColor');
        }

        if ($width && $height) {
          $dom->documentElement->setAttribute('height', (int) $height);
          $dom->documentElement->setAttribute('width', (int) $width);
        }

        // Credits: svg_formatter module.
        if ($_title) {
          $title = $dom->createElement('title', $_title);
          $title_id = Internals::getHtmlId('b-svg-' . substr(md5($_title), 0, 11));
          $title->setAttribute('id', $title_id);
          $dom->documentElement->insertBefore($title, $dom->documentElement->firstChild);
          $dom->documentElement->setAttribute('aria-labelledby', $title_id);
        }

        $output = $dom->saveXML($dom->documentElement);
      }
      else {
        $output = $dom->saveXML();
      }
    }

    return $output;
  }

  /**
   * Cleans out the SVG contents.
   *
   * @param string $svg
   *   The SVG content.
   *
   * @return string
   *   The cleaned SVG string.
   */
  protected function clean(string $svg): string {
    $svg = preg_replace(['/<\?xml.*\?>/i', '/<!DOCTYPE((.|\n|\r)*?)">/i'], '', $svg);
    $svg = str_replace(["\n", "  "], '', $svg);
    return trim($svg);
  }

  /**
   * Converts image to the supported transparent formats.
   */
  protected function toTransparentFormat($source, $ext = 'png', $toolkit_id = NULL): bool {
    $image = $this->image($source, $toolkit_id);
    $this->image = $image;

    if (!$image->convert($ext)) {
      $this->logger->error('Image convert failed using the %toolkit toolkit on %path (%mimetype)', [
        '%toolkit' => $image->getToolkitId(),
        '%path' => $image->getSource(),
        '%mimetype' => $image->getMimeType(),
      ]);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Checks if a shell command exists.
   *
   * @return bool
   *   TRUE if the shell command exists, else false.
   *
   * @todo use ImagemagickExecManagerInterface::execute|runOsShell for cross-os.
   */
  private function commandExists(string $command): bool {
    return !empty(shell_exec("which $command"));
  }

  /**
   * Returns the shell command result.
   *
   * @todo use ImagemagickExecManagerInterface::execute|runOsShell for cross-os.
   * @see http://php.net/manual/en/function.shell-exec.php
   */
  private function runOsShell($command, string $arguments): ?string {
    try {
      return shell_exec($command . ' ' . $arguments);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}
