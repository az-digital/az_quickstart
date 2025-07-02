<?php
/**
 * \DrupalPractice\Sniffs\General\FormStateInputSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\General;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Throws a message whenever $form_state['input'] is used. $form_state['values']
 * is preferred.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class FormStateInputSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_VARIABLE];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the function
     *                                               name in the stack.
     *
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        if ($phpcsFile->getTokensAsString($stackPtr, 4) === '$form_state[\'input\']'
            || $phpcsFile->getTokensAsString($stackPtr, 4) === '$form_state["input"]'
        ) {
            $warning = 'Do not use the raw $form_state[\'input\'], use $form_state[\'values\'] instead where possible';
            $phpcsFile->addWarning($warning, $stackPtr, 'Input');
        }

    }//end process()


}//end class
