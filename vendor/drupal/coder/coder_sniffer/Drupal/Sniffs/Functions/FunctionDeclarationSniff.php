<?php
/**
 * \Drupal\Sniffs\Functions\FunctionDeclarationSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace Drupal\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensure that there is only one space after the function keyword and no space
 * before the opening parenthesis.
 *
 * @deprecated in Coder 8.x, will be removed in Coder 9.x.
 * MultiLineFunctionDeclarationSniff is used instead.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class FunctionDeclarationSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_FUNCTION];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[($stackPtr + 1)]['content'] !== ' ') {
            $error = 'Expected exactly one space after the function keyword';
            $phpcsFile->addError($error, ($stackPtr + 1), 'SpaceAfter');
        }

        if (isset($tokens[($stackPtr + 3)]) === true
            && $tokens[($stackPtr + 3)]['code'] === T_WHITESPACE
        ) {
            $error = 'Space before opening parenthesis of function definition prohibited';
            $phpcsFile->addError($error, ($stackPtr + 3), 'SpaceBeforeParenthesis');
        }

    }//end process()


}//end class
