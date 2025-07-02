<?php
/**
 * \Drupal\Sniffs\Strings\UnnecessaryStringConcatSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace Drupal\Sniffs\Strings;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Checks that two strings are not concatenated together; suggests using one string instead.
 *
 * We cannot implement DeprecatedSniff here because that would show deprecation
 * messages to Coder users although they cannot fix them.
 *
 * @deprecated in Coder 8.3.30 and will be removed in Coder 9.0.0. Use
 *   Generic.Strings.UnnecessaryStringConcat instead.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class UnnecessaryStringConcatSniff implements Sniff
{


     /**
      * Returns the token types that this sniff is interested in.
      *
      * @return array<int, int|string>
      */
    public function register()
    {
        return [T_OPEN_TAG];

    }//end register()


    /**
     * Processes the tokens that this sniff is interested in.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file where the token was found.
     * @param int                         $stackPtr  The position in the stack where
     *                                               the token was found.
     *
     * @return int
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        // This sniff is deprecated and disabled - do nothing.
        return ($phpcsFile->numTokens + 1);

    }//end process()


        /**
         * {@inheritdoc}
         *
         * @return string
         */
    public function getDeprecationVersion(): string
    {
        return 'Coder 8.3.30';

    }//end getDeprecationVersion()


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRemovalVersion(): string
    {
        return 'Coder 9.0.0';

    }//end getRemovalVersion()


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getDeprecationMessage(): string
    {
        return 'The custom UnnecessaryStringConcatSniff is deprecated and will be removed in Coder 9.0.0. Use Generic.Strings.UnnecessaryStringConcat instead.';

    }//end getDeprecationMessage()


}//end class
