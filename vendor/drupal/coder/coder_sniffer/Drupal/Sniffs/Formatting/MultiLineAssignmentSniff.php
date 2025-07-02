<?php
/**
 * \Drupal\Sniffs\Formatting\MultiLineAssignmentSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace Drupal\Sniffs\Formatting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * \Drupal\Sniffs\Formatting\MultiLineAssignmentSniff.
 *
 * If an assignment goes over two lines, ensure the equal sign is indented.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class MultiLineAssignmentSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_EQUAL];

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

        // Equal sign can't be the last thing on the line.
        $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if ($next === false) {
            // Bad assignment.
            return;
        }

        // Make sure it is the first thing on the line, otherwise we ignore it.
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($prev === false) {
            // Bad assignment.
            return;
        }

        if ($tokens[$prev]['line'] === $tokens[$stackPtr]['line']) {
            return;
        }

        // Find the required indent based on the ident of the previous line.
        $assignmentIndent = 0;
        $prevLine         = $tokens[$prev]['line'];
        for ($i = ($prev - 1); $i >= 0; $i--) {
            if ($tokens[$i]['line'] !== $prevLine) {
                $i++;
                break;
            }
        }

        if ($tokens[$i]['code'] === T_WHITESPACE) {
            $assignmentIndent = strlen($tokens[$i]['content']);
        }

        // Find the actual indent.
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1));

        $expectedIndent = ($assignmentIndent + 2);
        $foundIndent    = strlen($tokens[$prev]['content']);
        if ($foundIndent !== $expectedIndent) {
            $error = "Multi-line assignment not indented correctly; expected $expectedIndent spaces but found $foundIndent";
            $phpcsFile->addError($error, $stackPtr, 'MultiLineAssignmentIndent');
        }

    }//end process()


}//end class
