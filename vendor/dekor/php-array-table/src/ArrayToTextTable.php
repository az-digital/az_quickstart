<?php

namespace dekor;

use dekor\formatters\BaseColumnFormatter;
use function array_keys;

/**
 * @author Denis Koronets
 */
class ArrayToTextTable
{
    const H_LINE_CHAR = '-';
    const V_LINE_CHAR = '|';
    const INTERSECT_CHAR = '+';

    /**
     * @var array
     */
    private $data;
    
    /**
     * @var array
     */
    private $columnsList = [];

    /**
     * @var BaseColumnFormatter[]
     */
    private $columnFormatters = [];
    
    /**
     * @var array
     */
    private $columnsLength = [];
    
    /**
     * @var array
     */
    private $result = [];
    
    /**
     * @var string
     */
    private $charset = 'UTF-8';
    
    /**
     * @var bool
     */
    private $renderHeader = true;
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function applyFormatter(BaseColumnFormatter $formatter)
    {
        $this->columnFormatters[] = $formatter;
    }
    
    /**
     * Set custom charset for columns values
     *
     * @param $charset
     *
     * @return \dekor\ArrayToTextTable
     * @throws \Exception
     */
    public function charset($charset)
    {
        if (!in_array($charset, mb_list_encodings())) {
            throw new \Exception(
                'This charset `' . $charset . '` is not supported by mbstring.' .
                'Please check it: http://php.net/manual/ru/function.mb-list-encodings.php'
            );
        }
        
        $this->charset = $charset;
        
        return $this;
    }
    
    /**
     * Set mode to print no header in the table
     *
     * @return self
     */
    public function noHeader()
    {
        $this->renderHeader = false;
        
        return $this;
    }
    
    /**
     * Build your ascii table and return the result
     *
     * @return string
     */
    public function render()
    {
        if (empty($this->data)) {
            return 'Empty';
        }

        $this->validateData();

        $this->applyBeforeFormatters();
        $this->calcColumnsList();
        $this->calcColumnsLength();
        
        /** render section **/
        $this->renderHeader();
        $this->renderBody();
        $this->lineSeparator();
        /** end render section **/
        
        return str_replace(
            ['++', '||'],
            ['+', '|'],
            implode(PHP_EOL, $this->result)
        );
    }

    protected function validateData()
    {
        foreach ($this->data as $row) {
            foreach ($row as $column) {
                if (!is_scalar($column)) {
                    throw new ArrayToTextTableException(
                        'Tried to render invalid data: ' . print_r($column, 1) . '. Only scalars allowed'
                    );
                }
            }
        }
    }

    /**
     * Apply formatters to data before calculating length
     * @return void
     */
    protected function applyBeforeFormatters()
    {
        foreach ($this->data as $key => $row) {
            foreach ($row as $columnKey => $value) {
                foreach ($this->columnFormatters as $formatter) {
                    $this->data[$key][$columnKey] = $formatter->process($columnKey, $value, true);
                }
            }
        }
    }
    
    /**
     * Calculates list of columns in data
     */
    protected function calcColumnsList()
    {
        $this->columnsList = array_keys(reset($this->data));
    }
    
    /**
     * Calculates length for string
     *
     * @param $str
     *
     * @return int
     */
    protected function length($str)
    {
        return mb_strlen($str, $this->charset);
    }
    
    /**
     * Calculate maximum string length for each column
     */
    private function calcColumnsLength()
    {
        foreach ($this->data as $row) {
            if ($row === '---') {
                continue;
            }

            foreach ($this->columnsList as $column) {
                $this->columnsLength[$column] = max(
                    isset($this->columnsLength[$column])
                        ? $this->columnsLength[$column]
                        : 0,
                    $this->length($row[$column]),
                    $this->length($column)
                );
            }
        }
    }
    
    /**
     * Append a line separator to result
     */
    private function lineSeparator()
    {
        $tmp = [];
        
        foreach ($this->columnsList as $column) {
            $tmp[] = str_repeat(self::H_LINE_CHAR, $this->columnsLength[$column] + 2);
        }
        
        $this->result[] = self::INTERSECT_CHAR . implode(self::INTERSECT_CHAR, $tmp) . self::INTERSECT_CHAR;
    }
    
    /**
     * @param $columnKey
     * @param $value
     *
     * @return string
     */
    private function column($columnKey, $value)
    {
        return ' ' . $value . str_repeat(
            ' ',
            $this->columnsLength[$columnKey] - $this->length($value)
        ) . ' ';
    }
    
    /**
     * Render header part
     *
     * @return void
     */
    private function renderHeader()
    {
        $this->lineSeparator();
        
        if (!$this->renderHeader) {
            return;
        }
        
        $tmp = [];
        
        foreach ($this->columnsList as $column) {
            $tmp[] = $this->column($column, $column);
        }
        
        $this->result[] = self::V_LINE_CHAR . implode(self::V_LINE_CHAR, $tmp) . self::V_LINE_CHAR;
        
        $this->lineSeparator();
    }
    
    /**
     * Render body section of table
     *
     * @return void
     */
    private function renderBody()
    {
        foreach ($this->data as $row) {
            if ($row === '---') {
                $this->lineSeparator();
                continue;
            }
            
            $tmp = [];

            foreach ($this->columnsList as $column) {
                $value = $this->column($column, $row[$column]);

                foreach ($this->columnFormatters as $formatter) {
                    $value = $formatter->process($column, $value, false);
                }

                $tmp[] = $value;
            }
            
            $this->result[] = self::V_LINE_CHAR . implode(self::V_LINE_CHAR, $tmp) . self::V_LINE_CHAR;
        }
    }
}
