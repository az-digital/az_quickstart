<?php
/**
 * \DrupalPractice\Sniffs\Commenting\ExpectedExceptionSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Checks that the PHPunit @expectedException tags are not used.
 *
 * See https://thephp.cc/news/2016/02/questioning-phpunit-best-practices .
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class ExpectedExceptionSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_DOC_COMMENT_TAG];

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

        $content = $tokens[$stackPtr]['content'];
        if ($content === '@expectedException' || $content === '@expectedExceptionCode'
            || $content === '@expectedExceptionMessage'
            || $content === '@expectedExceptionMessageRegExp'
        ) {
            $warning = '%s tags should not be used, use $this->setExpectedException() or $this->expectException() instead';
            $phpcsFile->addWarning($warning, $stackPtr, 'TagFound', [$content]);
        }

    }//end process()


}//end class
