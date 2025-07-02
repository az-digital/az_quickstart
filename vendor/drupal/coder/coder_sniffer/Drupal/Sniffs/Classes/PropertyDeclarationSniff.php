<?php
/**
 * Verifies that the "var" keyword is not used for class properties.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace Drupal\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Originally this was a fork of the PSR2 PropertyDeclarationSniff to have a fixer
 * for the var keyword. Since we don't want to maintain all the forked code, this
 * class was changed to only target the var keyword and provide a fixer for it.
 *
 * As a replacement PSR2.Classes.PropertyDeclaration is now included in ruleset.xml.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class PropertyDeclarationSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_VAR];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in
     *                                               the stack passed in $tokens.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $error = 'The var keyword must not be used to declare a property';
        $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'VarUsed');
        if ($fix === true) {
            $phpcsFile->fixer->replaceToken($stackPtr, 'public');
        }

    }//end process()


}//end class
