<?php

namespace Drupal\Component\Serialization;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

/**
 * Provides a YAML serialization implementation using symfony/yaml.
 */
class Yaml implements SerializationInterface {

  /**
   * {@inheritdoc}
   */
  public static function encode($data) {
    try {
      // Set the indentation to 2 to match Drupal's coding standards.
      $yaml = new Dumper(2);
      return $yaml->dump($data, PHP_INT_MAX, 0, SymfonyYaml::DUMP_EXCEPTION_ON_INVALID_TYPE | SymfonyYaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
    }
    catch (\Exception $e) {
      throw new InvalidDataTypeException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function decode($raw) {
    try {
      $yaml = new Parser();
      // Make sure we have a single trailing newline. A very simple config like
      // 'foo: bar' with no newline will fail to parse otherwise.
      return $yaml->parse(
        $raw,
        SymfonyYaml::PARSE_EXCEPTION_ON_INVALID_TYPE | SymfonyYaml::PARSE_CUSTOM_TAGS
      );
    }
    catch (\Exception $e) {
      throw new InvalidDataTypeException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileExtension() {
    return 'yml';
  }

  /**
   * Determines which implementation to use for parsing YAML.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no
   *  replacement.
   *
   * @see https://www.drupal.org/node/3415489
   */
  protected static function getSerializer() {
    @trigger_error('Calling ' . __METHOD__ . '() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3415489', E_USER_DEPRECATED);
    return YamlSymfony::class;
  }

}
