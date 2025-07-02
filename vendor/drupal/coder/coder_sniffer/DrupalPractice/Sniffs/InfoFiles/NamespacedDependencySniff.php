<?php

/**
 * \DrupalPractice\Sniffs\InfoFiles\NamespacedDependencySniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice\Sniffs\InfoFiles;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Checks that all declared dependencies are namespaced.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class NamespacedDependencySniff implements Sniff
{


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_INLINE_HTML];

    }//end register()


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The current file being processed.
     * @param int                         $stackPtr  The position of the current token
     *                                               in the stack passed in $tokens.
     *
     * @return int|void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $fileExtension = strtolower(substr($phpcsFile->getFilename(), -9));
        if ($fileExtension !== '.info.yml') {
            return ($phpcsFile->numTokens + 1);
        }

        $contents = file_get_contents($phpcsFile->getFilename());
        try {
            $info = Yaml::parse($contents);
            // Themes are allowed to have not namespaced dependencies, see
            // https://www.drupal.org/project/drupal/issues/474684.
            if (isset($info['type']) === true && $info['type'] === 'theme') {
                return ($phpcsFile->numTokens + 1);
            }
        } catch (ParseException $e) {
            // If the YAML is invalid we ignore this file.
            return ($phpcsFile->numTokens + 1);
        }

        if (preg_match('/^dependencies:/', $tokens[$stackPtr]['content']) === 0) {
            return;
        }

        $nextLine = ($stackPtr + 1);

        while (isset($tokens[$nextLine]) === true) {
            // Dependency line without namespace.
            if (preg_match('/^[\s]+- [^:]+[\s]*$/', $tokens[$nextLine]['content']) === 1) {
                $error = 'All dependencies must be prefixed with the project name, for example "drupal:"';
                $phpcsFile->addWarning($error, $nextLine, 'NonNamespaced');
            } else if (preg_match('/^[\s]+- [^:]+:[^:]+[\s]*$/', $tokens[$nextLine]['content']) === 0
                && preg_match('/^[\s]*#.*$/', $tokens[$nextLine]['content']) === 0
            ) {
                // Not a dependency line with namespace or comment - stop.
                return $nextLine;
            }

            $nextLine++;
        }

    }//end process()


}//end class
