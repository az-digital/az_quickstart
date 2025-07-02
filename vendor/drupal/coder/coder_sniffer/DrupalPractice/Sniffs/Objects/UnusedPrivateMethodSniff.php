<?php
/**
 * \DrupalPractice\Sniffs\Objects\UnusedPrivateMethodSniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\Objects;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractScopeSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Checks that private methods are actually used in a class.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class UnusedPrivateMethodSniff extends AbstractScopeSniff
{


    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct([T_CLASS], [T_FUNCTION], false);

    }//end __construct()


    /**
     * Processes the tokens within the scope.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being processed.
     * @param int                         $stackPtr  The position where this token was
     *                                               found.
     * @param int                         $currScope The position of the current scope.
     *
     * @return void
     */
    protected function processTokenWithinScope(File $phpcsFile, $stackPtr, $currScope)
    {
        // Only check private methods.
        $methodProperties = $phpcsFile->getMethodProperties($stackPtr);
        if ($methodProperties['scope'] !== 'private' || $methodProperties['is_static'] === true) {
            return;
        }

        $tokens     = $phpcsFile->getTokens();
        $methodName = $phpcsFile->getDeclarationName($stackPtr);

        if ($methodName === '__construct') {
            return;
        }

        $classPtr = key($tokens[$stackPtr]['conditions']);

        // Search for direct $this->methodCall() or indirect callbacks [$this,
        // 'methodCall'].
        $current = $tokens[$classPtr]['scope_opener'];
        $end     = $tokens[$classPtr]['scope_closer'];
        while (($current = $phpcsFile->findNext(T_VARIABLE, ($current + 1), $end)) !== false) {
            if ($tokens[$current]['content'] !== '$this') {
                continue;
            }

            $next = $phpcsFile->findNext(Tokens::$emptyTokens, ($current + 1), null, true);
            if ($next === false) {
                continue;
            }

            if ($tokens[$next]['code'] === T_OBJECT_OPERATOR) {
                $call = $phpcsFile->findNext(Tokens::$emptyTokens, ($next + 1), null, true);
                // PHP method calls are case insensitive.
                if ($call === false || strcasecmp($tokens[$call]['content'], $methodName) !== 0) {
                    continue;
                }

                $parenthesis = $phpcsFile->findNext(Tokens::$emptyTokens, ($call + 1), null, true);
                if ($parenthesis === false || $tokens[$parenthesis]['code'] !== T_OPEN_PARENTHESIS) {
                    continue;
                }

                // At this point this is a method call to the private method, so we
                // can stop.
                return;
            } else if ($tokens[$next]['code'] === T_COMMA) {
                $call = $phpcsFile->findNext(Tokens::$emptyTokens, ($next + 1), null, true);
                if ($call === false || substr($tokens[$call]['content'], 1, -1) !== $methodName) {
                    continue;
                }

                // At this point this is likely the private method as callback on a
                // function such as array_filter().
                return;
            }//end if
        }//end while

        $warning = 'Unused private method %s()';
        $data    = [$methodName];
        $phpcsFile->addWarning($warning, $stackPtr, 'UnusedMethod', $data);

    }//end processTokenWithinScope()


    /**
     * Process tokens outside of scope.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being processed.
     * @param int                         $stackPtr  The position where this token was
     *                                               found.
     *
     * @return void
     */
    protected function processTokenOutsideScope(File $phpcsFile, $stackPtr)
    {

    }//end processTokenOutsideScope()


}//end class
