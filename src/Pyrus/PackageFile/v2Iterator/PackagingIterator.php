<?php
/**
 * PEAR2_Pyrus_PackageFile_v2Iterator_PackagingIterator
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
 * iterator for packaging
 *
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */
class PEAR2_Pyrus_PackageFile_v2Iterator_PackagingIterator extends ArrayIterator
{
    static private $_parent;
    static function setParent(PEAR2_Pyrus_IPackageFile $parent)
    {
        self::$_parent = $parent;
    }

    function key()
    {
        $curfile = $this->current();
        $a = 'PEAR2_Pyrus_Installer_Role_' . ucfirst($curfile['attribs']['role']);
        $role = new $a(PEAR2_Pyrus_Config::current());
        // add the install-as attribute to retrieve packaging location
        return $role->getPackagingLocation(self::$_parent, $curfile['attribs']);
    }

    function current()
    {
        $curfile = parent::current();
        $curfile['attribs']['name'] = parent::key();
        if ($base = self::$_parent->getBaseInstallDir($curfile['attribs']['name'])) {
            $curfile['attribs']['baseinstalldir'] = $base;
        } elseif (!isset($curfile['attribs']['baseinstalldir'])) {
            $curfile['attribs']['baseinstalldir'] =
                (dirname($curfile['attribs']['name']) === '.' ?
                    '/' :
                    dirname($curfile['attribs']['name']));
        }
        if (isset($curfile['attribs']['md5sum'])) {
            unset($curfile['attribs']['md5sum']);
        }
        return $curfile;
    }
}