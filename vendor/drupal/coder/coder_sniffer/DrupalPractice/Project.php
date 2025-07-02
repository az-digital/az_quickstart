<?php
/**
 * \DrupalPractice\Project
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

namespace DrupalPractice;

use PHP_CodeSniffer\Files\File;
use \Drupal\Sniffs\InfoFiles\ClassFilesSniff;
use Symfony\Component\Yaml\Yaml;
use PHP_CodeSniffer\Config;

/**
 * Helper class to retrieve project information like module/theme name for a file.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Project
{


    /**
     * Determines the project short name a file might be associated with.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     *
     * @return string|false Returns the project machine name or false if it could not
     *   be derived.
     */
    public static function getName(File $phpcsFile)
    {
        // Cache the project name per file as this might get called often.
        static $cache;

        if (isset($cache[$phpcsFile->getFilename()]) === true) {
            return $cache[$phpcsFile->getFilename()];
        }

        $pathParts = pathinfo($phpcsFile->getFilename());
        // Module and install files are easy: they contain the project name in the
        // file name.
        if (isset($pathParts['extension']) === true && in_array($pathParts['extension'], ['install', 'module', 'profile', 'theme']) === true) {
            $cache[$phpcsFile->getFilename()] = $pathParts['filename'];
            return $pathParts['filename'];
        }

        $infoFile = static::getInfoFile($phpcsFile);
        if ($infoFile === false) {
            return false;
        }

        $pathParts = pathinfo($infoFile);

        // Info files end in *.info.yml on Drupal 8 and *.info on Drupal 7.
        $filename = $pathParts['filename'];
        $filename = preg_replace('/\.info$/', '', $filename);

        $cache[$phpcsFile->getFilename()] = $filename;
        return $filename;

    }//end getName()


    /**
     * Determines the info file a file might be associated with.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     *
     * @return string|false The project info file name or false if it could not
     *   be derived.
     */
    public static function getInfoFile(File $phpcsFile)
    {
        // Cache the project name per file as this might get called often.
        static $cache;

        if (isset($cache[$phpcsFile->getFilename()]) === true) {
            return $cache[$phpcsFile->getFilename()];
        }

        $pathParts = pathinfo($phpcsFile->getFilename());

        // Search for an info file.
        $dir = $pathParts['dirname'];
        do {
            $infoFiles = glob("$dir/*.info.yml");
            if (empty($infoFiles) === true) {
                $infoFiles = glob("$dir/*.info");
            }

            // Filter out directories.
            $infoFiles = array_filter($infoFiles, 'is_file');

            // Go one directory up if we do not find an info file here.
            $dir = dirname($dir);
        } while (empty($infoFiles) === true && $dir !== dirname($dir));

        // No info file found, so we give up.
        if (empty($infoFiles) === true) {
            $cache[$phpcsFile->getFilename()] = false;
            return false;
        }

        // Sort the info file names and take the shortest info file.
        usort($infoFiles, [__NAMESPACE__.'\Project', 'compareLength']);
        $infoFile = $infoFiles[0];
        $cache[$phpcsFile->getFilename()] = $infoFile;
        return $infoFile;

    }//end getInfoFile()


    /**
     * Determines the *.services.yml file in a module.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     *
     * @return string|false The Services YML file name or false if it could not
     *   be derived.
     */
    public static function getServicesYmlFile(File $phpcsFile)
    {
        // Cache the services file per file as this might get called often.
        static $cache;

        if (isset($cache[$phpcsFile->getFilename()]) === true) {
            return $cache[$phpcsFile->getFilename()];
        }

        $pathParts = pathinfo($phpcsFile->getFilename());

        // Search for an info file.
        $dir = $pathParts['dirname'];
        do {
            $ymlFiles = glob("$dir/*.services.yml");

            // Go one directory up if we do not find an info file here.
            $dir = dirname($dir);
        } while (empty($ymlFiles) === true && $dir !== dirname($dir));

        // No YML file found, so we give up.
        if (empty($ymlFiles) === true) {
            $cache[$phpcsFile->getFilename()] = false;
            return false;
        }

        // Sort the YML file names and take the shortest info file.
        usort($ymlFiles, [__NAMESPACE__.'\Project', 'compareLength']);
        $ymlFile = $ymlFiles[0];
        $cache[$phpcsFile->getFilename()] = $ymlFile;
        return $ymlFile;

    }//end getServicesYmlFile()


    /**
     * Return true if the given class is a Drupal service registered in *.services.yml.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int                         $classPtr  The position of the class declaration
     *                                               in the token stack.
     *
     * @return bool
     */
    public static function isServiceClass(File $phpcsFile, $classPtr)
    {
        // Cache the information per file as this might get called often.
        static $cache;

        if (isset($cache[$phpcsFile->getFilename()]) === true) {
            return $cache[$phpcsFile->getFilename()];
        }

        // Get the namespace of the class if there is one.
        $namespacePtr = $phpcsFile->findPrevious(T_NAMESPACE, ($classPtr - 1));
        if ($namespacePtr === false) {
            $cache[$phpcsFile->getFilename()] = false;
            return false;
        }

        $ymlFile = static::getServicesYmlFile($phpcsFile);
        if ($ymlFile === false) {
            $cache[$phpcsFile->getFilename()] = false;
            return false;
        }

        $services = Yaml::parse(file_get_contents($ymlFile), Yaml::PARSE_CUSTOM_TAGS);
        if (isset($services['services']) === false) {
            $cache[$phpcsFile->getFilename()] = false;
            return false;
        }

        $nsEnd           = $phpcsFile->findNext(
            [
                T_NS_SEPARATOR,
                T_STRING,
                T_WHITESPACE,
            ],
            ($namespacePtr + 1),
            null,
            true
        );
        $namespace       = trim($phpcsFile->getTokensAsString(($namespacePtr + 1), ($nsEnd - $namespacePtr - 1)));
        $classNameSpaced = ltrim($namespace.'\\'.$phpcsFile->getDeclarationName($classPtr), '\\');

        foreach ($services['services'] as $service) {
            if (isset($service['class']) === true
                && $classNameSpaced === ltrim($service['class'], '\\')
            ) {
                $cache[$phpcsFile->getFilename()] = true;
                return true;
            }
        }

        return false;

    }//end isServiceClass()


    /**
     * Helper method to sort array values by string length with usort().
     *
     * @param string $a First string.
     * @param string $b Second string.
     *
     * @return int
     */
    public static function compareLength($a, $b)
    {
        return (strlen($a) - strlen($b));

    }//end compareLength()


    /**
     * Determines the Drupal core version a file might be associated with.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     *
     * @return int The core version number. Returns 8 by default.
     */
    public static function getCoreVersion(File $phpcsFile)
    {
        // First check if a config option was passed.
        $coreVersion = Config::getConfigData('drupal_core_version');
        if (empty($coreVersion) === false) {
            return (int) $coreVersion;
        }

        // Try to guess the core version from info files in the file path.
        $infoFile = static::getInfoFile($phpcsFile);
        if ($infoFile === false) {
            // Default to Drupal 8.
            return 8;
        }

        $pathParts = pathinfo($infoFile);

        // Drupal 6 and 7 use the .info file extension.
        if ($pathParts['extension'] === 'info') {
            $infoSettings = ClassFilesSniff::drupalParseInfoFormat(file_get_contents($infoFile));
            if (isset($infoSettings['core']) === true
                && is_string($infoSettings['core']) === true
            ) {
                return (int) $infoSettings['core'][0];
            }

            // Default to Drupal 7 if there is an info file.
            return 7;
        }

        // Drupal 8 uses the .yml file extension.
        // @todo Revisit for Drupal 9, but I don't want to do YAML parsing
        // for now.
        return 8;

    }//end getCoreVersion()


}//end class
