<?php
/**
 * Verifies that case statements conform to their coding standards.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace Drupal\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Checks that a colon ":" is used instead of ";" on case statements.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class CaseSemicolonSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_CASE];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in the
     *                                               stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        if (isset($tokens[$stackPtr]['scope_opener']) === true
            && $tokens[$tokens[$stackPtr]['scope_opener']]['code'] === T_SEMICOLON
        ) {
            $error = 'A colon ":" must be used to open a case statement, found ";"';
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'SemicolonNotAllowed');
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken($tokens[$stackPtr]['scope_opener'], ':');
            }
        }

    }//end process()


}//end class
