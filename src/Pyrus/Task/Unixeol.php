<?php
/**
 * <tasks:unixeol>
 *
 * PHP version 5
 *
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version   SVN: $Id$
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */

/**
 * Implements the unix line endings file task.
 *
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */
namespace pear2\Pyrus\Task;
class Unixeol extends \pear2\Pyrus\Task\Common
{
    const TYPE = 'simple';
    const PHASE = \pear2\Pyrus\Task\Common::PACKAGEANDINSTALL;
    var $_replacements;

    /**
     * Initialize a task instance with the parameters
     * @param array raw, parsed xml
     * @param array attributes from the <file> tag containing this task
     * @param string|null last installed version of this package
     */
    function __construct($pkg, $phase, $xml, $attribs, $lastversion)
    {
        parent::__construct($pkg, $phase, $xml, $attribs, $lastversion);
    }

    /**
     * Validate the basic contents of a <unixeol> tag
     * 
     * @param PEAR_Pyrus_IPackageFile
     * @param array
     * @param array the entire parsed <file> tag
     * @param string the filename of the package.xml
     * 
     * @throws \pear2\Pyrus\Task\Exception\InvalidTask
     */
    static function validateXml(\pear2\Pyrus\IPackage $pkg, $xml, $fileXml, $file)
    {
        if (is_array($xml) && count($xml) || $xml !== '') {
            throw new \pear2\Pyrus\Task\Exception\InvalidTask('unixeol', $file, 'no attributes allowed');
        }
        return true;
    }

    /**
     * Replace all line endings with line endings customized for the current OS
     *
     * See validateXml() source for the complete list of allowed fields
     * @param \pear2\Pyrus\IPackage
     * @param resource open file pointer, set to the beginning of the file
     * @param string the eventual final file location (informational only)
     * @return string
     */
    function startSession($fp, $dest)
    {
        $contents = stream_get_contents($fp);
        \pear2\Pyrus\Logger::log(3, "replacing all line endings with \\n in $dest");
        $contents = preg_replace("/\r\n|\n\r|\r|\n/", "\n", $contents);
        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, $contents);
        return true;
    }

    function isPreProcessed()
    {
        return true;
    }
}
?>