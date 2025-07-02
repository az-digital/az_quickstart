<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate_plus\data_parser;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\DataParserPluginBase;

/**
 * Obtain SOAP data for migration.
 *
 * @DataParser(
 *   id = "soap",
 *   title = @Translation("SOAP")
 * )
 */
class Soap extends DataParserPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Iterator over the SOAP data.
   */
  protected ?\ArrayIterator $iterator = NULL;

  /**
   * Method to call on the SOAP service.
   */
  protected string $function;

  /**
   * Parameters to pass to the SOAP service function.
   */
  protected array $parameters;

  /**
   * Form of the function response - 'xml', 'object', or 'array'.
   */
  protected string $responseType;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\Exception\RequirementsException
   *   If PHP SOAP extension is not installed.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    if (!class_exists('\SoapClient')) {
      throw new RequirementsException('The PHP SOAP extension is not installed');
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->function = $configuration['function'];
    $this->parameters = $configuration['parameters'];
    $this->responseType = $configuration['response_type'];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \SoapFault
   *   If there's an error in a SOAP call.
   * @throws \Drupal\migrate\MigrateException
   *   If we can't resolve the SOAP function or its response property.
   */
  protected function openSourceUrl($url): bool {
    // Will throw SoapFault if there's an error in a SOAP call.
    $client = new \SoapClient($url);
    // Determine the response property name.
    $function_found = FALSE;
    foreach ($client->__getFunctions() as $function_signature) {
      // E.g., "GetWeatherResponse GetWeather(GetWeather $parameters)".
      $response_type = strtok($function_signature, ' ');
      $function_name = strtok('(');
      if (strcasecmp($function_name, $this->function) === 0) {
        $function_found = TRUE;
        foreach ($client->__getTypes() as $type_info) {
          // E.g., "struct GetWeatherResponse {\n string GetWeatherResult;\n}".
          if (preg_match('|struct (.*?) {\s*[a-z]+ (.*?);|is', $type_info, $matches)) {
            if ($matches[1] == $response_type) {
              $response_property = $matches[2];
            }
          }
        }
        break;
      }
    }
    if (!$function_found) {
      throw new MigrateException("SOAP function {$this->function} not found.");
    }
    elseif (!isset($response_property)) {
      throw new MigrateException("Response property not found for SOAP function {$this->function}.");
    }
    $response = $client->{$this->function}($this->parameters);
    $response_value = $response->$response_property;
    switch ($this->responseType) {
      case 'xml':
        $xml = simplexml_load_string($response_value);
        $this->iterator = new \ArrayIterator($xml->xpath($this->itemSelector));
        break;

      case 'object':
        $this->iterator = new \ArrayIterator($response_value->{$this->itemSelector});
        break;

      case 'array':
        $this->iterator = new \ArrayIterator($response_value[$this->itemSelector]);
        break;

    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow(): void {
    $current = $this->iterator->current();
    if ($current) {
      foreach ($this->fieldSelectors() as $field_name => $selector) {
        $this->currentItem[$field_name] = $current->$selector;
      }
      $this->iterator->next();
    }
  }

}
