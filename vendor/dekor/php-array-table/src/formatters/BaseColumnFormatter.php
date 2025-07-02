<?php

namespace dekor\formatters;

/**
 * Base class for column formatters.
 * Feel free to extend it in your projects and override with the value
 */
abstract class BaseColumnFormatter
{
    /**
     * @var string[]|\Closure[] $config - key/value pair where key is column name to process and value is Closure or scalar value
     */
    protected $config;

    /**
     * @param array $config - key/value pair where key is column name to process and value is Closure or scalar value
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $columnName
     * @param string $value
     * @param bool $isBefore
     * @return string
     */
    public function process($columnName, $value, $isBefore)
    {
        if (!isset($this->config[$columnName])) {
            return $value;
        }

        $formatterValue = $this->config[$columnName];

        // compute formatter value in case we accepted closure
        if (is_callable($this->config[$columnName])) {
            $formatterValue = call_user_func($formatterValue, $value);
        }

        if ($isBefore) {
            return $this->applyBefore($value, $formatterValue);
        }

        return $this->applyAfter($value, $formatterValue);
    }

    /**
     * Allows to apply some formatting to column value before calculating columns length.
     * Just return $value in case you don't want to do anything with the column at this stage
     * @param $value
     * @param string $formatterValue
     * @return string
     */
    abstract protected function applyBefore($value, $formatterValue);

    /**
     * Allows to apply some formatting to column value after adding spaces to column value
     * Just return $value in case you don't want to do anything with the column at this stage
     * @param $value
     * @param string $formatterValue
     * @return string
     */
    abstract protected function applyAfter($value, $formatterValue);
}