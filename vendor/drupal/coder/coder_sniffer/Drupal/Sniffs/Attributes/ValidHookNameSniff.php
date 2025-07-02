<?php
/**
 * \Drupal\Sniffs\Attributes\ValidHookNameSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace Drupal\Sniffs\Attributes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Checks that Hook attribute argument name not starts with "hook_" prefix.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class ValidHookNameSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_ATTRIBUTE];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The PHP_CodeSniffer file where the
     *                                               token was found.
     * @param int                         $stackPtr  The position in the PHP_CodeSniffer
     *                                               file's token stack where the token
     *                                               was found.
     *
     * @return void|int Optionally returns a stack pointer. The sniff will not be
     *                  called again on the current file until the returned stack
     *                  pointer is reached. Return $phpcsFile->numTokens + 1 to skip
     *                  the rest of the file.
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens        = $phpcsFile->getTokens();
        $attributeName = $phpcsFile->findNext(T_STRING, ($stackPtr + 1));
        if ($attributeName !== false
            && $tokens[$attributeName]['content'] === 'Hook'
        ) {
            $hookName = $phpcsFile->findNext(T_CONSTANT_ENCAPSED_STRING, ($attributeName + 2));
            if ($hookName !== false) {
                // Remove outer quotes.
                $hookNameValue = trim($tokens[$hookName]['content'], '"\'');

                if (strpos($hookNameValue, 'hook_') === 0 && $hookNameValue !== 'hook_') {
                    // Remove "hook_" prefix.
                    $hookNameValueFixed = substr($hookNameValue, 5);
                    $message            = sprintf("The hook name should not start with 'hook_', expected '%s' but found '%s'", $hookNameValueFixed, $hookNameValue);

                    $fix = $phpcsFile->addFixableWarning($message, $hookName, 'HookPrefix');
                    if ($fix === true) {
                        // Return outer quotes.
                        $hookNameValueFixed = str_replace($hookNameValue, $hookNameValueFixed, $tokens[$hookName]['content']);
                        $phpcsFile->fixer->replaceToken($hookName, $hookNameValueFixed);
                    }
                }
            }
        }//end if

    }//end process()


}//end class
