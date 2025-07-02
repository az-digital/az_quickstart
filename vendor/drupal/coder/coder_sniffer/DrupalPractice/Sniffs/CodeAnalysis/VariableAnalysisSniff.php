<?php
/**
 * \DrupalPractice\Sniffs\CodeAnalysis\VariableAnalysisSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\CodeAnalysis;

use VariableAnalysis\Sniffs\CodeAnalysis\VariableAnalysisSniff as VendorVariableAnalysisSniff;


/**
 * Checks for variable usage using the sirbrillig/phpcs-variable-analysis sniff.
 *
 * This class exists to make the VariableAnalysis sniff available to DrupalPractice.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class VariableAnalysisSniff extends VendorVariableAnalysisSniff
{
}//end class
