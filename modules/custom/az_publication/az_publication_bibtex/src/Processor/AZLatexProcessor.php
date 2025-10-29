<?php

// phpcs:disable DrupalPractice.Commenting.AuthorTag.AuthorFound

namespace Drupal\az_publication_bibtex\Processor;

use RenanBr\BibTexParser\Processor\TagCoverageTrait;

/**
 * Processes various types of latex special characters.
 *
 * Provides translation patterns for LaTeX to Unicode entities. Adopted from
 * Refbase by Matthias Steffens <refbase@extracts.de>. Modified by
 * Christian Spitzlay to build patterns dynamically. Adapted to
 * BibTexParser Processor by Arizona Digital <az-digital@web.arizona.edu>
 *
 * This is a translation table for best-effort conversion from LaTeX to Unicode
 * entities. It contains a comprehensive list of substitution strings for LaTeX
 * characters, which are used with the 'T1' font encoding. Uses commands from
 * the 'textcomp' package. Unicode characters that can't be matched uniquely
 * are commented out. Adopted from 'transtab' by Markus Kuhn.
 *
 * Adapted to RenanBr\BibTexParser\Processor in 2023 for AZ Quickstart by
 * Arizona Digital <az-digital@web.arizona.edu>.
 *
 * @author Matthias Steffens <refbase@extracts.de>
 * @author Christian Spitzlay <www.drupal.org/u/cspitzlay>
 * @author Arizona Digital <az-digital@web.arizona.edu>
 * @copyright 2006 Matthias Steffens <refbase@extracts.de>
 * @copyright 2023 Arizona Digital <az-digital@web.arizona.edu>
 * @license https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html GNU General Public License, version 2
 */
class AZLatexProcessor {
  use TagCoverageTrait;

  // Default patterns provided as-is.
  const MISC_PATTERNS = [
    '\\$\\\\#\\$' => '#',
    '\\\\%' => '%',
    '\\\\&' => '&',
    '(?<!\\\\)~' => ' ',
    '\\{\\\\c\\\\ \\}' => '¸',
    '--' => '–',
    '---' => '—',
    '\\$\\^\\{0\\}\\$' => '⁰',
    '\\$\\^\\{4\\}\\$' => '⁴',
    '\\$\\^\\{5\\}\\$' => '⁵',
    '\\$\\^\\{6\\}\\$' => '⁶',
    '\\$\\^\\{7\\}\\$' => '⁷',
    '\\$\\^\\{8\\}\\$' => '⁸',
    '\\$\\^\\{9\\}\\$' => '⁹',
    '\\$\\^\\{+\\}\\$' => '⁺',
    '\\$\\^\\{-\\}\\$' => '⁻',
    '\\$\\^\\{=\\}\\$' => '⁼',
    '\\$\\^\\{n\\}\\$' => 'ⁿ',
    '\\$_\\{0\\}\\$' => '₀',
    '\\$_\\{1\\}\\$' => '₁',
    '\\$_\\{2\\}\\$' => '₂',
    '\\$_\\{3\\}\\$' => '₃',
    '\\$_\\{4\\}\\$' => '₄',
    '\\$_\\{5\\}\\$' => '₅',
    '\\$_\\{6\\}\\$' => '₆',
    '\\$_\\{7\\}\\$' => '₇',
    '\\$_\\{8\\}\\$' => '₈',
    '\\$_\\{9\\}\\$' => '₉',
    '\\$_\\{+\\}\\$' => '₊',
    '\\$_\\{-\\}\\$' => '₋',
    '\\$_\\{=\\}\\$' => '₌',
    '\\\\_' => '_',
    '\\\\ ' => ' ',
  ];

  // Diacritics that do not require whitespace in the absence of braces.
  const DIACRITIC_PATTERNS = [
    '`|A' => 'À',
    '\'|A' => 'Á',
    '^|A' => 'Â',
    '~|A' => 'Ã',
    '"|A' => 'Ä',
    '`|E' => 'È',
    '\'|E' => 'É',
    '^|E' => 'Ê',
    '"|E' => 'Ë',
    '`|I' => 'Ì',
    '\'|I' => 'Í',
    '^|I' => 'Î',
    '"|I' => 'Ï',
    '~|N' => 'Ñ',
    '\'|N' => 'Ń',
    '\'|n' => 'ń',
    '`|O' => 'Ò',
    '\'|O' => 'Ó',
    '^|O' => 'Ô',
    '~|O' => 'Õ',
    '"|O' => 'Ö',
    '`|U' => 'Ù',
    '\'|U' => 'Ú',
    '^|U' => 'Û',
    '"|U' => 'Ü',
    '\'|Y' => 'Ý',
    '`|a' => 'à',
    '\'|a' => 'á',
    '^|a' => 'â',
    '~|a' => 'ã',
    '"|a' => 'ä',
    '`|e' => 'è',
    '\'|e' => 'é',
    '^|e' => 'ê',
    '"|e' => 'ë',
    '`|i' => 'ì',
    '\'|i' => 'í',
    '^|i' => 'î',
    '"|i' => 'ï',
    '"|\\i' => 'ï',
    '~|n' => 'ñ',
    '`|o' => 'ò',
    '\'|o' => 'ó',
    '^|o' => 'ô',
    '~|o' => 'õ',
    '"|o' => 'ö',
    '=|o' => 'ō',
    '`|u' => 'ù',
    '\'|u' => 'ú',
    '^|u' => 'û',
    '"|u' => 'ü',
    '\'|y' => 'ý',
    '"|y' => 'ÿ',
    '\'|C' => 'Ć',
    '\'|c' => 'ć',
    '.|g' => 'ġ',
    '.|I' => 'İ',
    '\'|\\i' => 'í',
    '\'|L' => 'Ĺ',
    '\'|l' => 'ĺ',
    '\'|R' => 'Ŕ',
    '\'|r' => 'ŕ',
    '\'|S' => 'Ś',
    '\'|s' => 'ś',
    '"|Y' => 'Ÿ',
    '\'|Z' => 'Ź',
    '\'|z' => 'ź',
    '.|Z' => 'Ż',
    '.|z' => 'ż',
  ];

  // Diacritics that require whitespace in the absence of braces.
  const DIACRITIC_WHITESPACE_PATTERNS = [
    'v|L' => 'Ľ',
    'v|l' => 'ľ',
    'r|A' => 'Å',
    'c|C' => 'Ç',
    'r|a' => 'å',
    'c|c' => 'ç',
    'u|A' => 'Ă',
    'u|a' => 'ă',
    'k|A' => 'Ą',
    'k|a' => 'ą',
    'v|C' => 'Č',
    'v|c' => 'č',
    'v|D' => 'Ď',
    'v|d' => 'ď',
    'k|E' => 'Ę',
    'k|e' => 'ę',
    'v|E' => 'Ě',
    'v|e' => 'ě',
    'u|e' => 'ĕ',
    'u|G' => 'Ğ',
    'u|g' => 'ğ',
    'v|N' => 'Ň',
    'v|n' => 'ň',
    'H|O' => 'Ő',
    'H|o' => 'ő',
    'v|R' => 'Ř',
    'v|r' => 'ř',
    'c|S' => 'Ş',
    'c|s' => 'ş',
    'v|S' => 'Š',
    'v|s' => 'š',
    'c|T' => 'Ţ',
    'c|t' => 'ţ',
    'v|T' => 'Ť',
    'v|t' => 'ť',
    'r|U' => 'Ů',
    'r|u' => 'ů',
    'H|U' => 'Ű',
    'H|u' => 'ű',
    'v|Z' => 'Ž',
    'v|z' => 'ž',
  ];

  // TeX names and their unicode equivalents.
  const BACKSLASH_PATTERNS = [
    'alpha'               => 'α',
    'beta'                => 'β',
    'gamma'               => 'γ',
    'delta'               => 'δ',
    'epsilon'             => 'ε',
    'zeta'                => 'ζ',
    'eta'                 => 'η',
    'theta'               => 'θ',
    'iota'                => 'ι',
    'kappa'               => 'κ',
    'lambda'              => 'λ',
    'mu'                  => 'μ',
    'nu'                  => 'ν',
    'xi'                  => 'ξ',
    'pi'                  => 'π',
    'rho'                 => 'ρ',
    'varsigma'            => 'ς',
    'sigma'               => 'σ',
    'tau'                 => 'τ',
    'upsilon'             => 'υ',
    'phi'                 => 'φ',
    'chi'                 => 'χ',
    'psi'                 => 'ψ',
    'omega'               => 'ω',
    'Gamma'               => 'Γ',
    'Delta'               => 'Δ',
    'Theta'               => 'Θ',
    'Lambda'              => 'Λ',
    'Xi'                  => 'Ξ',
    'Pi'                  => 'Π',
    'Sigma'               => 'Σ',
    'Upsilon'             => 'Υ',
    'Phi'                 => 'Φ',
    'Psi'                 => 'Ψ',
    'Omega'               => 'Ω',
    'AA'                  => 'Å',
    'aa'                  => 'å',
    'AE'                  => 'Æ',
    'ae'                  => 'æ',
    'DH'                  => 'Ð',
    'dh'                  => 'ð',
    'DJ'                  => 'Đ',
    'dj'                  => 'đ',
    'i'                   => 'ı',
    'L'                   => 'Ł',
    'l'                   => 'ł',
    'NG'                  => 'Ŋ',
    'ng'                  => 'ŋ',
    'O'                   => 'Ø',
    'o'                   => 'ø',
    'OE'                  => 'Œ',
    'oe'                  => 'œ',
    'TH'                  => 'Þ',
    'th'                  => 'þ',
    'ss'                  => 'ß',
    'texteuro'            => '€',
    'textcelsius'         => '℃',
    'textnumero'          => '№',
    'textcircledP'        => '℗',
    'textservicemark'     => '℠',
    'texttrademark'       => '™',
    'textohm'             => 'Ω',
    'textestimated'       => '℮',
    'textleftarrow'       => '←',
    'textuparrow'         => '↑',
    'textrightarrow'      => '→',
    'textdownarrow'       => '↓',
    'infty'               => '∞',
    'textlangle'          => '〈',
    'textrangle'          => '〉',
    'textvisiblespace'    => '␣',
    'textopenbullet'      => '◦',
    'textflorin'          => 'ƒ',
    'textasciicircum'     => 'ˆ',
    'textacutedbl'        => '˝',
    'textendash'          => '–',
    'textemdash'          => '—',
    'textbardbl'          => '‖',
    'textunderscore'      => '‗',
    'textquoteleft'       => '‘',
    'textquoteright'      => '’',
    'quotesinglbase'      => '‚',
    'textquotedblleft'    => '“',
    'textquotedblright'   => '”',
    'quotedblbase'        => '„',
    'textdagger'          => '†',
    'textdaggerdbl'       => '‡',
    'textbullet'          => '•',
    'textellipsis'        => '…',
    'textperthousand'     => '‰',
    'guilsinglleft'       => '‹',
    'guilsinglright'      => '›',
    'textfractionsolidus' => '⁄',
    'textdiv'             => '÷',
    'textexclamdown'      => '¡',
    'textcent'            => '¢',
    'textsterling'        => '£',
    'textyen'             => '¥',
    'textbrokenbar'       => '¦',
    'textsection'         => '§',
    'textasciidieresis'   => '¨',
    'textcopyright'       => '©',
    'textordfeminine'     => 'ª',
    'guillemotleft'       => '«',
    'textlnot'            => '¬',
    'textregistered'      => '®',
    'textasciimacron'     => '¯',
    'textdegree'          => '°',
    'textpm'              => '±',
    'texttwosuperior'     => '²',
    'textthreesuperior'   => '³',
    'textasciiacute'      => '´',
    'textmu'              => 'µ',
    'textparagraph'       => '¶',
    'textperiodcentered'  => '·',
    'textonesuperior'     => '¹',
    'textordmasculine'    => 'º',
    'guillemotright'      => '»',
    'textonequarter'      => '¼',
    'textonehalf'         => '½',
    'textthreequarters'   => '¾',
    'textquestiondown'    => '¿',
    'texttimes'           => '×',
    'textgreater'         => '>',
    'textless'            => '<',
  ];

  /**
   * @var array
   */
  private $patterns;

  /**
   * @var array
   */
  private $search;

  /**
   * @var array
   */
  private $replace;

  /**
   * Create a new AZLatexProcessor.
   */
  public function __construct() {
    $patterns = [];

    // Non-dynamic patterns provided as-is.
    $patterns += self::MISC_PATTERNS;

    // Limited TeX parsing. Consume matches pairs of braces and backslashes.
    // Dynamically create regexes for the entity pattersn defined above.
    // Diacritics that do not require whitespace in the absence of braces.
    foreach (self::DIACRITIC_PATTERNS as $pattern => $character) {
      // Split pattern at the pipe symbol.
      $key = explode('|', $pattern);
      $pattern = '(\\{)?\\\\' . preg_quote($key[0], '/') . '(\s*\\{)?' . preg_quote($key[1], '/') . '(?(2)\\}|)(?(1)\\}|)';
      $patterns[$pattern] = $character;
    }

    // Diacritics that require whitespace in the absence of braces.
    foreach (self::DIACRITIC_WHITESPACE_PATTERNS as $pattern => $character) {
      // Split pattern at the pipe symbol.
      $key = explode('|', $pattern);
      $pattern = '(\\{)?\\\\' . preg_quote($key[0], '/') . '((\s*\\{)?|\s+)' . preg_quote($key[1], '/') . '(?(3)\\}|)(?(1)\\}|)';
      $patterns[$pattern] = $character;
    }

    // TeX names and their unicode equivalents.
    foreach (self::BACKSLASH_PATTERNS as $pattern => $character) {
      // Consume pairs of $ and braces.
      // if neither brace nor $ present then whitespace or a backslash required.
      $pattern = '(\\$)?(\\{)?\\\\' . $pattern . '(?(2)\\}|(\\s+|(?=\\$)|(?=\\\\)))(?(1)\\s*\\$|)';
      $patterns[$pattern] = $character;
    }

    // Finalize patterns.
    foreach ($patterns as $pattern => $character) {
      $pattern = '/' . $pattern . '/';
      $this->patterns[$pattern] = $character;
    }
    $this->search = array_keys($this->patterns);
    $this->replace = array_values($this->patterns);
  }

  /**
   * @return array
   *   The associative citation fields.
   */
  public function __invoke(array $entry) {
    $covered = $this->getCoveredTags(array_keys($entry));
    foreach ($covered as $tag) {
      // Translate string.
      if (is_string($entry[$tag])) {
        $entry[$tag] = $this->detex($entry[$tag]);
        continue;
      }

      // Translate array.
      if (is_array($entry[$tag])) {
        array_walk_recursive($entry[$tag], function (&$text) {
          if (is_string($text)) {
            $text = $this->detex($text);
          }
        });

      }
    }

    return $entry;
  }

  /**
   * Removes some limited TeX representations.
   *
   * @param string $text
   *   Value to remove TeX markup from.
   *
   * @return string
   *   The deTeX-ified string.
   */
  private function detex($text) {
    return preg_replace($this->search, $this->replace, $text);
  }

}
