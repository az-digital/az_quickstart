<?php
/**
 * \Drupal\Sniffs\Arrays\DisallowLongArraySyntaxSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace Drupal\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use DrupalPractice\Project;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Arrays\DisallowLongArraySyntaxSniff as GenericDisallowLongArraySyntaxSniff;

/**
 * Bans the use of the PHP long array syntax in Drupal 8.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DisallowLongArraySyntaxSniff extends GenericDisallowLongArraySyntaxSniff
{


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return void|int
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $drupalVersion = Project::getCoreVersion($phpcsFile);
        if ($drupalVersion < 8) {
            // No need to check this file again, mark it as done.
            return ($phpcsFile->numTokens + 1);
        }

        parent::process($phpcsFile, $stackPtr);

    }//end process()


}//end class
