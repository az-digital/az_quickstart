<?php
/**
 * \DrupalPractice\Sniffs\General\DescriptionTSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\General;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Checks that string values for #description in render arrays are translated.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DescriptionTSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_CONSTANT_ENCAPSED_STRING];

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
        // Look for the string "#description".
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['content'] !== '"#description"' && $tokens[$stackPtr]['content'] !== "'#description'") {
            return;
        }

        // Look for an array pattern that starts to define #description values.
        $statementEnd = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1));
        $arrayString  = $phpcsFile->getTokensAsString(($stackPtr + 1), ($statementEnd - $stackPtr));
        // Cut out all the white space.
        $arrayString = preg_replace('/\s+/', '', $arrayString);

        if (strpos($arrayString, '=>"') !== 0 && strpos($arrayString, ']="') !== 0
            && strpos($arrayString, "=>'") !== 0 && strpos($arrayString, "]='") !== 0
        ) {
            return;
        }

        $stringToken = $phpcsFile->findNext(T_CONSTANT_ENCAPSED_STRING, ($stackPtr + 1));
        $content     = strip_tags($tokens[$stringToken]['content']);

        if (strlen($content) > 5) {
            $warning = '#description values usually have to run through t() for translation';
            $phpcsFile->addWarning($warning, $stringToken, 'DescriptionT');
        }

    }//end process()


}//end class
