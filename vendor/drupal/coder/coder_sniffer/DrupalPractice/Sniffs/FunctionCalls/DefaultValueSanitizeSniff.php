<?php
/**
 * \DrupalPractice\Sniffs\FunctionCalls\DefaultValueSanitizeSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\FunctionCalls;

use PHP_CodeSniffer\Files\File;
use Drupal\Sniffs\Semantics\FunctionCall;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Check that sanitization functions such as check_plain() are not used on Form
 * API #default_value elements.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DefaultValueSanitizeSniff extends FunctionCall
{


    /**
     * Returns an array of function names this test wants to listen for.
     *
     * @return array<string>
     */
    public function registerFunctionNames()
    {
        return [
            'check_markup',
            'check_plain',
            'check_url',
            'filter_xss',
            'filter_xss_admin',
        ];

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

        // We assume that the sequence '#default_value' => check_plain(...) is
        // wrong because the Form API already sanitizes #default_value.
        $arrow = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPtr - 1), null, true);
        if ($arrow === false || $tokens[$arrow]['code'] !== T_DOUBLE_ARROW) {
            return;
        }

        $arrayKey = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($arrow - 1), null, true);
        if ($arrayKey === false
            || $tokens[$arrayKey]['code'] !== T_CONSTANT_ENCAPSED_STRING
            || substr($tokens[$arrayKey]['content'], 1, -1) !== '#default_value'
        ) {
            return;
        }

        $warning = 'Do not use the %s() sanitization function on Form API #default_value elements, they get escaped automatically';
        $data    = [$tokens[$stackPtr]['content']];
        $phpcsFile->addWarning($warning, $stackPtr, 'DefaultValue', $data);

    }//end processFunctionCall()


}//end class
