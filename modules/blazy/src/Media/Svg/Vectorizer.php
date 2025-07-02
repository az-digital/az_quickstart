<?php

namespace Drupal\blazy\Media\Svg;

/**
 * Provides image to svg converter.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 */
class Vectorizer implements VectorizerInterface {

  /**
   * Image source path.
   *
   * @var string
   */
  protected $path;

  /**
   * GDImageIdentifier.
   *
   * @var object|bool|null
   */
  protected $image;

  /**
   * Image pixel width.
   *
   * @var int
   */
  protected $width;

  /**
   * Image pixel $this->height.
   *
   * @var int
   */
  protected $height;

  /**
   * Image options.
   *
   * @var array
   */
  protected $options;

  /**
   * Similarity between colors.
   *
   * Threshold is compared against the distance between two colors in 3
   * dimensions. e.g.: RGB( 0, 0, 255 ) and RGB( 0, 0, 0 ) would be merged
   * with a threshold greater than 255.
   *
   * @var int
   */
  protected $threshold = 0;

  /**
   * Constructs a Vectorizer object.
   */
  public function __construct($path, array $options = []) {
    if (!is_readable($path) && !filter_var($path, FILTER_VALIDATE_URL)) {
      throw new \InvalidArgumentException(sprintf("Supplied URL / path is invalid : '%s'", $path));
    }

    $this->path = $path;
    $this->options = $options;
  }

  /**
   * Destructs the current instance.
   */
  public function __destruct() {
    $this->flushImageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getThreshold(): int {
    return $this->threshold;
  }

  /**
   * {@inheritdoc}
   */
  public function setThreshold(int $threshold): self {
    $threshold = filter_var($threshold, FILTER_VALIDATE_INT, [
      'options' => [
        'min_range' => 0,
        'max_range' => 255,
      ],
    ]);
    if ($threshold === FALSE) {
      throw new \InvalidArgumentException(
        'The submitted threshold is invalid, value must be a integer between > 0 and < 255'
      );
    }

    $this->threshold = $threshold;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function loadImage(string $path): self {
    if (!is_readable($path) && !filter_var($path, FILTER_VALIDATE_URL)) {
      throw new \InvalidArgumentException(sprintf("Supplied URL / path is invalid : '%s'", $path));
    }

    $this->path = $path;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLoadedImagePath(): string {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function vectorize(): string {
    $svg = $this->toXml();
    return $svg->saveXml($svg->documentElement);
  }

  /**
   * {@inheritdoc}
   */
  public function saveSvg(string $path): int {
    return $this->toXml()->save($path);
  }

  /**
   * {@inheritdoc}
   */
  public function toXml(): \DOMDocument {
    $this->setImageSettings();

    $svgh = $this->vectorizeFromRaster(self::DIRECTION_HORIZONTAL);
    $svg  = $this->vectorizeFromRaster(self::DIRECTION_VERTICAL);

    if ($svgh->getElementsByTagName('rect')->length < $svg->getElementsByTagName('rect')->length) {
      $svg = $svgh;
    }

    $this->flushImageSettings();
    return $svg;
  }

  /**
   * Remove image settings.
   */
  protected function flushImageSettings() {
    if (!is_null($this->image)) {
      imagedestroy($this->image);
      $this->image  = NULL;
      $this->width  = 0;
      $this->height = 0;
    }
  }

  /**
   * Initializes Image settings.
   *
   * @throws \InvalidArgumentException
   *   If the image is not yet loaded.
   */
  protected function setImageSettings() {
    $this->flushImageSettings();

    if (empty($this->path)) {
      throw new \InvalidArgumentException('You must input the path.');
    }

    $this->image  = imagecreatefromstring(file_get_contents($this->path));
    $this->width  = imagesx($this->image);
    $this->height = imagesy($this->image);
  }

  /**
   * Create a SVG document from raster depending on its direction.
   *
   * @param int $direction
   *   Whether horizontal or vertical.
   *
   * @return \DOMDocument
   *   The DOM document object.
   */
  protected function vectorizeFromRaster($direction): \DOMDocument {
    $svg = $this->createSvgDocument();
    if ($direction == self::DIRECTION_HORIZONTAL) {
      for ($y = 0; $y < $this->height; ++$y) {
        $number_of_consecutive_pixels = 1;
        for ($x = 0; $x < $this->width; $x = $x + $number_of_consecutive_pixels) {
          $number_of_consecutive_pixels = $this->createLine($svg, $x, $y, $direction);
        }
      }
    }
    else {
      for ($x = 0; $x < $this->width; ++$x) {
        $number_of_consecutive_pixels = 1;
        for ($y = 0; $y < $this->height; $y = $y + $number_of_consecutive_pixels) {
          $number_of_consecutive_pixels = $this->createLine($svg, $x, $y, $direction);
        }
      }
    }

    return $svg;
  }

  /**
   * Creates a template SVG file.
   *
   * @return \DOMDocument
   *   The DOM document object.
   */
  protected function createSvgDocument(): \DOMDocument {
    $imp = new \DOMImplementation();
    $dom = $imp->createDocument(
      NULL,
        'svg',
        $imp->createDocumentType(
          'svg',
          '-//W3C//DTD SVG 1.1//EN',
            'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd'
        )
      );
    $dom->encoding = 'UTF-8';
    $dom->formatOutput = TRUE;
    $dom->documentElement->setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    $dom->documentElement->setAttribute('class', 'svg');
    // $dom->documentElement->setAttribute('shape-rendering', 'crispEdges');
    // $dom->documentElement->setAttribute('width', $this->width);
    // $dom->documentElement->setAttribute('height', $this->height);
    $dom->documentElement->setAttribute('viewBox', '0 0 ' . $this->width . ' ' . $this->height);

    return $dom;
  }

  /**
   * Create a line SVG.
   *
   * @param \DOMDocument $svg
   *   The DOM document SVG.
   * @param int $x
   *   The X coordinate.
   * @param int $y
   *   The Y coordinate.
   * @param int $direction
   *   Whether horizontal or vertical.
   *
   * @return int
   *   The number of consecutive pixels.
   */
  protected function createLine(\DOMDocument $svg, $x, $y, $direction): int {
    $rgba  = $this->getPixelColors($x, $y);
    $delta = 1;
    while ($this->isSimilarPixel($rgba, $x, $y, $delta, $direction)) {
      ++$delta;
    }
    $this->createRectElement($svg, $rgba, $x, $y, $delta, $direction);

    return $delta;
  }

  /**
   * Creates a Rect Element for SVG.
   *
   * @param int $x
   *   The X coordinate.
   * @param int $y
   *   The Y coordinate.
   *
   * @return array
   *   Color array, [red: int, green: int, blue: int, alpha: int].
   */
  protected function getPixelColors($x, $y): array {
    // @todo recheck return imagecolorat($this->image, $x, $y);.
    return imagecolorsforindex($this->image, imagecolorat($this->image, $x, $y));
  }

  /**
   * Returns whether the pixel are similar in color depending on the direction.
   *
   * @param array $rgba
   *   Color array, [red: int, green: int, blue: int, alpha: int].
   * @param int $x
   *   The X coordinate.
   * @param int $y
   *   The Y coordinate.
   * @param int $delta
   *   The difference dimension.
   * @param int $direction
   *   Whether horizontal OR vertical.
   *
   * @return bool
   *   Whether the pixel are similar in color depending on the direction.
   */
  protected function isSimilarPixel(array $rgba, $x, $y, $delta, $direction): bool {
    if ($direction == self::DIRECTION_HORIZONTAL) {
      $res = $x + $delta;

      return $res < $this->width && ($rgba == $this->getPixelColors($res, $y));
    }

    $res = $y + $delta;

    return $res < $this->height && ($rgba == $this->getPixelColors($x, $res));
  }

  /**
   * Creates a SVG rect Element.
   *
   * @param \DOMDocument $svg
   *   The SVG DOMDocument.
   * @param array $rgba
   *   Color array, [red: int, green: int, blue: int, alpha: int].
   * @param int $x
   *   The X coordinate.
   * @param int $y
   *   The Y coordinate.
   * @param int $width
   *   The element width.
   * @param int $direction
   *   Whether horizontal or vertical.
   */
  protected function createRectElement(
    \DOMDocument $svg,
    array $rgba,
    $x,
    $y,
    $width,
    $direction,
  ) {
    $rectWidth  = $width;
    $rectHeight = 1;

    if ($direction == self::DIRECTION_VERTICAL) {
      $rectWidth  = 1;
      $rectHeight = $width;
    }

    $rect = $svg->createElement('rect');
    $rect->setAttribute("x", $x);
    $rect->setAttribute("y", $y);
    $rect->setAttribute("width", $rectWidth);
    $rect->setAttribute("height", $rectHeight);

    if ($this->isColor($rgba) == 'white') {
      // fill="rgba(124,240,10,0.5)".
      $rect->setAttribute("fill", "none");
      // $rect->setAttribute("fill-opacity", 0.0);
    }
    elseif ($this->isColor($rgba) == 'black') {
      // fill="rgba(124,240,10,0.5)".
      $rect->setAttribute("fill", "rgb(0,0,0)");
      $rect->setAttribute("class", "svgc");
    }
    else {
      $rect->setAttribute("fill", "rgb({$rgba['red']},{$rgba['green']},{$rgba['blue']})");
    }

    $alpha = filter_var($rgba["alpha"], FILTER_VALIDATE_INT, [
      'options' => ['min_range' => 0, 'max_range' => 128],
    ]);

    // @todo recheck.
    if ($alpha > 0) {
      $rect->setAttribute("fill-opacity", (128 - $alpha) / 128);
    }
    $svg->documentElement->appendChild($rect);
  }

  /**
   * Checks if a color nears to black or white.
   *
   * @param array $rgba
   *   Color array, [ red: int, green: int, blue: int ].
   *
   * @return string
   *   Either black or white closest to the given color.
   */
  protected function isColor(array $rgba): string {
    $color = (0.2126 * $rgba['red']) + (0.7152 * $rgba['green']) + (0.0722 * $rgba['red']);
    return $color < 128 ? 'black' : 'white';
  }

  /**
   * Check if two colors are within the tolerance as determined by threshold.
   *
   * @param array $color_a
   *   Color array, [ red: int, green: int, blue: int ].
   * @param array $color_b
   *   Color array, [ red: int, green: int, blue: int ].
   *
   * @return bool
   *   TRUE if the colors are within the tolerance,
   *   FALSE if they are outside the tolerance.
   */
  protected function checkThreshold(array $color_a, array $color_b): bool {
    $distance = sqrt(
      pow($color_b['red'] - $color_a['red'], 2) +
      pow($color_b['green'] - $color_a['green'], 2) +
      pow($color_b['blue'] - $color_a['blue'], 2)
    );
    return $this->threshold > $distance;
  }

}
