<?php

/**
 * \DrupalPractice\Sniffs\InfoFiles\DescriptionSniff.
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
 * Checks if the *.info.yml file contains a description.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DescriptionSniff implements Sniff
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
     * @return int
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $filename      = $phpcsFile->getFilename();
        $fileExtension = strtolower(substr($filename, -9));
        if ($fileExtension !== '.info.yml') {
            return ($phpcsFile->numTokens + 1);
        }

        // Exclude config files which might contain the info.yml extension.
        $filenameWithoutExtension = substr($filename, 0, -9);
        if (strpos($filenameWithoutExtension, '.') !== false) {
            return ($phpcsFile->numTokens + 1);
        }

        try {
            $info = Yaml::parseFile($phpcsFile->getFilename());
        } catch (ParseException $e) {
            // If the YAML is invalid we ignore this file.
            return ($phpcsFile->numTokens + 1);
        }

        // Check if the type key is set, to verify if we're inside a project info.yml file.
        if (isset($info['type']) === false) {
            return ($phpcsFile->numTokens + 1);
        }

        if (isset($info['description']) === false) {
            $warning = '"Description" property is missing in the info.yml file';
            $phpcsFile->addWarning($warning, $stackPtr, 'Missing');
        } else if ($info['description'] === '') {
            $warning = '"Description" should not be empty';
            $phpcsFile->addWarning($warning, $stackPtr, 'Empty');
        }

        return ($phpcsFile->numTokens + 1);

    }//end process()


}//end class
