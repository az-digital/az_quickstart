<?php
/**
 * \Drupal\Sniffs\InfoFiles\DependenciesArraySniff.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
namespace Drupal\Sniffs\InfoFiles;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Dependencies should be an array in .info.yml files.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class DependenciesArraySniff implements Sniff
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
     * Processes this test when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return int
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        // Only run this sniff on .info.yml files.
        if (strtolower(substr($phpcsFile->getFilename(), -9)) !== '.info.yml') {
            return ($phpcsFile->numTokens + 1);
        }

        try {
            $info = Yaml::parse(file_get_contents($phpcsFile->getFilename()));
        } catch (ParseException $e) {
            // If the YAML is invalid we ignore this file.
            return ($phpcsFile->numTokens + 1);
        }

        if (isset($info['dependencies']) === true && is_array($info['dependencies']) === false) {
            // The $stackPtr will always indicate line 1, but we can get the actual line by
            // searching $tokens to find the dependencies item.
            $tokens = $phpcsFile->getTokens();
            // $tokens cannot be empty at this point, but PHPStan 10.1.4 does not know this and gives the error
            // "Variable $key might not be defined". So initialize it here.
            $key = $stackPtr;
            foreach ($tokens as $key => $token) {
                if (preg_match('/dependencies\s*\:/', $token['content']) === 1) {
                    break;
                }
            }

            $error = '"dependencies" should be an array in the info yaml file';
            $phpcsFile->addError($error, $key, 'Dependencies');
        }

        return ($phpcsFile->numTokens + 1);

    }//end process()


}//end class
