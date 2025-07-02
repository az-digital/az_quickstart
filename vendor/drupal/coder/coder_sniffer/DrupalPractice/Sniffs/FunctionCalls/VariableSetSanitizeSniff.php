<?php
/**
 * \DrupalPractice\Sniffs\FunctionCalls\VariableSetSanitizeSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\FunctionCalls;

use PHP_CodeSniffer\Files\File;
use Drupal\Sniffs\Semantics\FunctionCall;

/**
 * Check that variable_set() calls do not run check_plain() or other
 * sanitization functions on the value.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class VariableSetSanitizeSniff extends FunctionCall
{


    /**
     * Returns an array of function names this test wants to listen for.
     *
     * @return array<string>
     */
    public function registerFunctionNames()
    {
        return ['variable_set'];

    }//end registerFunctionNames()


    /**
     * Processes this function call.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile    The file being scanned.
     * @param int                         $stackPtr     The position of the function call in
     *                                                  the stack.
     * @param int                         $openBracket  The position of the opening
     *                                                  parenthesis in the stack.
     * @param int                         $closeBracket The position of the closing
     *                                                  parenthesis in the stack.
     *
     * @return void
     */
    public function processFunctionCall(
        File $phpcsFile,
        $stackPtr,
        $openBracket,
        $closeBracket
    ) {
        $tokens = $phpcsFile->getTokens();

        $argument = $this->getArgument(2);
        if ($argument !== false && in_array(
            $tokens[$argument['start']]['content'],
            [
                'check_markup',
                'check_plain',
                'check_url',
                'filter_xss',
                'filter_xss_admin',
            ]
        ) === true
        ) {
            $warning = 'Do not use the %s() sanitization function when writing values to the database, use it on output to HTML instead';
            $data    = [$tokens[$argument['start']]['content']];
            $phpcsFile->addWarning($warning, $argument['start'], 'VariableSet', $data);
        }

    }//end processFunctionCall()


}//end class
