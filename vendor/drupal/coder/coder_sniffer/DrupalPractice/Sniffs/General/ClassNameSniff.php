<?php
/**
 * \DrupalPractice\Sniffs\General\ClassNameSniff
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\General;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use DrupalPractice\Project;

/**
 * Checks that classes without namespaces are properly prefixed with the module
 * name.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class ClassNameSniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [
            T_CLASS,
            T_INTERFACE,
        ];

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
        // If there is a PHP 5.3 namespace declaration in the file we return
        // immediately as classes can be named arbitrary within a namespace.
        $namespace = $phpcsFile->findPrevious(T_NAMESPACE, ($stackPtr - 1));
        if ($namespace !== false) {
            return;
        }

        $moduleName = Project::getName($phpcsFile);
        if ($moduleName === false) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $className = $phpcsFile->findNext(T_STRING, $stackPtr);
        $name      = trim($tokens[$className]['content']);

        // Underscores are omitted in class names. Also convert all characters
        // to lower case to compare them later.
        $classPrefix = strtolower(str_replace('_', '', $moduleName));
        // Views classes might have underscores in the name, which is also fine.
        $viewsPrefix = strtolower($moduleName);
        $name        = strtolower($name);

        if (strpos($name, $classPrefix) !== 0 && strpos($name, $viewsPrefix) !== 0) {
            $warning   = '%s name must be prefixed with the project name "%s"';
            $nameParts = explode('_', $moduleName);
            $camelName = '';
            foreach ($nameParts as &$part) {
                $camelName .= ucfirst($part);
            }

            $errorData = [
                ucfirst($tokens[$stackPtr]['content']),
                $camelName,
            ];
            $phpcsFile->addWarning($warning, $className, 'ClassPrefix', $errorData);
        }

    }//end process()


}//end class
