<?php
/**
 * PEAR2_Pyrus_Dependency_Validator, advanced dependency validation
 *
 * PHP versions 4 and 5
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
 * Dependency check for PEAR2 packages
 *
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */
class PEAR2_Pyrus_Dependency_Validator
{
    /**
     * @var PEAR2_MultiErrors
     */
    protected $errs;
    /**
     * One of the PEAR2_Pyrus_Validate::* states
     * @see PEAR2_Pyrus_Validate::NORMAL
     * @var integer
     */
    var $_state;
    /**
     * @var PEAR2_Pyrus_OSGuess
     */
    var $_os;
    /**
     * Package to validate
     * @var PEAR2_Pyrus_Package
     */
    var $_currentPackage;
    /**
     * @param PEAR2_Pyrus_Package
     * @param int installation state (one of PEAR2_Pyrus_Validate::*)
     * @param PEAR2_MultiErrors
     */
    function __construct($package, $state = PEAR2_Pyrus_Validate::INSTALLING,
                         PEAR2_MultiErrors $errs)
    {
        $this->_state = $state;
        $this->_currentPackage = $package;
        $this->errs = $errs;
    }

    function _getExtraString($dep)
    {
        $extra = ' (';
        if (isset($dep->uri)) {
            return '';
        }
        if (isset($dep->recommended)) {
            $extra .= 'recommended version ' . $dep->recommended;
        } else {
            if (isset($dep->min)) {
                $extra .= 'version >= ' . $dep->min;
            }
            if (isset($dep->max)) {
                if ($extra != ' (') {
                    $extra .= ', ';
                }
                $extra .= 'version <= ' . $dep->max;
            }
            if (isset($dep->exclude)) {
                if (!is_array($dep->exclude)) {
                    $dep->exclude = array($dep->exclude);
                }
                if ($extra != ' (') {
                    $extra .= ', ';
                }
                $extra .= 'excluded versions: ';
                foreach ($dep->exclude as $i => $exclude) {
                    if ($i) {
                        $extra .= ', ';
                    }
                    $extra .= $exclude;
                }
            }
        }
        $extra .= ')';
        if ($extra == ' ()') {
            $extra = '';
        }
        return $extra;
    }

    /**
     * This makes unit-testing a heck of a lot easier
     */
    function getPHP_OS()
    {
        return PHP_OS;
    }

    /**
     * This makes unit-testing a heck of a lot easier
     */
    function getsysname()
    {
        $this->_os = new PEAR2_Pyrus_OSGuess;
        return $this->_os->getSysname();
    }

    /**
     * Specify a dependency on an OS.  Use arch for detailed os/processor information
     *
     * There are two generic OS dependencies that will be the most common, unix and windows.
     * Other options are linux, freebsd, darwin (OS X), sunos, irix, hpux, aix
     */
    function validateOsDependency(PEAR2_Pyrus_PackageFile_v2_Dependencies_Dep $dep)
    {
        if ($this->_state != PEAR2_Pyrus_Validate::INSTALLING &&
              $this->_state != PEAR2_Pyrus_Validate::DOWNLOADING) {
            return true;
        }
        if ($dep->name == '*') {
            return true; // no one will do conflicts with *, so assume no conflicts
        }
        switch (strtolower($dep->name)) {
            case 'windows' :
                if ($dep->conflicts) {
                    if (strtolower(substr($this->getPHP_OS(), 0, 3)) == 'win') {
                        if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) &&
                              !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                            return $this->raiseError("Cannot install %s on Windows");
                        } else {
                            return $this->warning("warning: Cannot install %s on Windows");
                        }
                    }
                } else {
                    if (strtolower(substr($this->getPHP_OS(), 0, 3)) != 'win') {
                        if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) &&
                              !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                            return $this->raiseError("Can only install %s on Windows");
                        } else {
                            return $this->warning("warning: Can only install %s on Windows");
                        }
                    }
                }
            break;
            case 'unix' :
                $unices = array('linux', 'freebsd', 'darwin', 'sunos', 'irix', 'hpux', 'aix');
                if ($dep->conflicts) {
                    if (in_array(strtolower($this->getSysname()), $unices)) {
                        if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) &&
                              !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                            return $this->raiseError("Cannot install %s on any Unix system");
                        } else {
                            return $this->warning(
                                "warning: Cannot install %s on any Unix system");
                        }
                    }
                } else {
                    if (!in_array(strtolower($this->getSysname()), $unices)) {
                        if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) &&
                              !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                            return $this->raiseError("Can only install %s on a Unix system");
                        } else {
                            return $this->warning(
                                "warning: Can only install %s on a Unix system");
                        }
                    }
                }
            break;
            default :
                if ($dep->conflicts) {
                    if (strtolower($dep->name) == strtolower($this->getSysname())) {
                        if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) &&
                              !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                            return $this->raiseError('Cannot install %s on ' . $dep->name .
                                ' operating system');
                        } else {
                            return $this->warning('warning: Cannot install %s on ' .
                                $dep->name . ' operating system');
                        }
                    }
                } else {
                    if (strtolower($dep->name) != strtolower($this->getSysname())) {
                        if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) &&
                              !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                            return $this->raiseError('Cannot install %s on ' .
                                $this->getSysname() .
                                ' operating system, can only install on ' . $dep->name);
                        } else {
                            return $this->warning('warning: Cannot install %s on ' .
                                $this->getSysname() .
                                ' operating system, can only install on ' . $dep->name);
                        }
                    }
                }
        }
        return true;
    }

    /**
     * This makes unit-testing a heck of a lot easier
     */
    function matchSignature($pattern)
    {
        $this->_os = new PEAR2_Pyrus_OSGuess;
        return $this->_os->matchSignature($pattern);
    }

    /**
     * Specify a complex dependency on an OS/processor/kernel version,
     * Use OS for simple operating system dependency.
     *
     * This is the only dependency that accepts an eregable pattern.  The pattern
     * will be matched against the php_uname() output parsed by OS_Guess
     */
    function validateArchDependency($dep)
    {
        if ($this->_state != PEAR2_Pyrus_Validate::INSTALLING) {
            return true;
        }
        if ($this->matchSignature($dep->pattern)) {
            if ($dep->conflicts) {
                if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                    return $this->raiseError('%s Architecture dependency failed, cannot match "' .
                        $dep->pattern . '"');
                }
                return $this->warning('warning: %s Architecture dependency failed, ' .
                    'cannot match "' . $dep->pattern . '"');
            }
            return true;
        } else {
            if ($dep->conflicts) {
                return true;
            }
            if (!isset(PEAR2_Pyrus_Installer::$options['nodeps'])
                && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                return $this->raiseError('%s Architecture dependency failed, does not ' .
                    'match "' . $dep->pattern . '"');
            }
            return $this->warning('warning: %s Architecture dependency failed, does ' .
                'not match "' . $dep->pattern . '"');
        }
    }

    /**
     * This makes unit-testing a heck of a lot easier
     */
    function extension_loaded($name)
    {
        return extension_loaded($name);
    }

    /**
     * This makes unit-testing a heck of a lot easier
     */
    function phpversion($name = null)
    {
        if ($name !== null) {
            return phpversion($name);
        } else {
            return phpversion();
        }
    }

    function validateExtensionDependency(PEAR2_Pyrus_PackageFile_v2_Dependencies_Package $dep, $required = true)
    {
        if ($this->_state != PEAR2_Pyrus_Validate::INSTALLING &&
              $this->_state != PEAR2_Pyrus_Validate::DOWNLOADING) {
            return true;
        }
        $loaded = $this->extension_loaded($dep->name);
        $extra = $this->_getExtraString($dep);
        if (!isset($dep->min) && !isset($dep->max) &&
              !isset($dep->recommended) && !isset($dep->exclude)) {
            if ($loaded) {
                if ($dep->conflicts) {
                    if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                        return $this->raiseError('%s conflicts with PHP extension "' .
                            $dep->name . '"' . $extra);
                    } else {
                        return $this->warning('warning: %s conflicts with PHP extension "' .
                            $dep->name . '"' . $extra);
                    }
                }
                return true;
            } else {
                if ($dep->conflicts) {
                    return true;
                }
                if ($required) {
                    if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                        return $this->raiseError('%s requires PHP extension "' .
                            $dep->name . '"' . $extra);
                    } else {
                        return $this->warning('warning: %s requires PHP extension "' .
                            $dep->name . '"' . $extra);
                    }
                } else {
                    return $this->warning('%s can optionally use PHP extension "' .
                        $dep->name . '"' . $extra);
                }
            }
        }
        if (!$loaded) {
            if ($dep->conflicts) {
                return true;
            }
            if (!$required) {
                return $this->warning('%s can optionally use PHP extension "' .
                    $dep->name . '"' . $extra);
            } else {
                if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                    return $this->raiseError('%s requires PHP extension "' . $dep->name .
                        '"' . $extra);
                }
                    return $this->warning('warning: %s requires PHP extension "' . $dep->name .
                        '"' . $extra);
            }
        }
        $version = (string) $this->phpversion($dep->name);
        if (empty($version)) {
            $version = '0';
        }
        $fail = false;
        if (isset($dep->min)) {
            if (!version_compare($version, $dep->min, '>=')) {
                $fail = true;
            }
        }
        if (isset($dep->max)) {
            if (!version_compare($version, $dep->max, '<=')) {
                $fail = true;
            }
        }
        if ($fail && !$dep->conflicts) {
            if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                return $this->raiseError('%s requires PHP extension "' . $dep->name .
                    '"' . $extra . ', installed version is ' . $version);
            } else {
                return $this->warning('warning: %s requires PHP extension "' . $dep->name .
                    '"' . $extra . ', installed version is ' . $version);
            }
        } elseif ((isset($dep->min) || isset($dep->max)) && !$fail && $dep->conflicts) {
            if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                return $this->raiseError('%s conflicts with PHP extension "' .
                    $dep->name . '"' . $extra . ', installed version is ' . $version);
            } else {
                return $this->warning('warning: %s conflicts with PHP extension "' .
                    $dep->name . '"' . $extra . ', installed version is ' . $version);
            }
        }
        if (isset($dep->exclude)) {
            foreach ($dep->exclude as $exclude) {
                if (version_compare($version, $exclude, '==')) {
                    if ($dep->conflicts) {
                        continue;
                    }
                    if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) &&
                          !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                        return $this->raiseError('%s is not compatible with PHP extension "' .
                            $dep->name . '" version ' .
                            $exclude);
                    } else {
                        return $this->warning('warning: %s is not compatible with PHP extension "' .
                            $dep->name . '" version ' .
                            $exclude);
                    }
                } elseif (version_compare($version, $exclude, '!=') && $dep->conflicts) {
                    if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                        return $this->raiseError('%s conflicts with PHP extension "' .
                            $dep->name . '"' . $extra . ', installed version is ' . $version);
                    } else {
                        return $this->warning('warning: %s conflicts with PHP extension "' .
                            $dep->name . '"' . $extra . ', installed version is ' . $version);
                    }
                }
            }
        }
        if (isset($dep->recommended)) {
            if (version_compare($version, $dep->recommended, '==')) {
                return true;
            } else {
                if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                    return $this->raiseError('%s dependency: PHP extension ' . $dep->name .
                        ' version "' . $version . '"' .
                        ' is not the recommended version "' . $dep->recommended .
                        '", but may be compatible, use --force to install');
                } else {
                    return $this->warning('warning: %s dependency: PHP extension ' .
                        $dep->name . ' version "' . $version . '"' .
                        ' is not the recommended version "' . $dep->recommended . '"');
                }
            }
        }
        return true;
    }

    function validatePhpDependency(PEAR2_Pyrus_PackageFile_v2_Dependencies_Dep $dep)
    {
        if ($this->_state != PEAR2_Pyrus_Validate::INSTALLING &&
              $this->_state != PEAR2_Pyrus_Validate::DOWNLOADING) {
            return true;
        }
        $version = $this->phpversion();
        $extra = $this->_getExtraString($dep);
        if (isset($dep->min)) {
            if (!version_compare($version, $dep->min, '>=')) {
                if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                    return $this->raiseError('%s requires PHP' .
                        $extra . ', installed version is ' . $version);
                } else {
                    return $this->warning('warning: %s requires PHP' .
                        $extra . ', installed version is ' . $version);
                }
            }
        }
        if (isset($dep->max)) {
            if (!version_compare($version, $dep->max, '<=')) {
                if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                    return $this->raiseError('%s requires PHP' .
                        $extra . ', installed version is ' . $version);
                } else {
                    return $this->warning('warning: %s requires PHP' .
                        $extra . ', installed version is ' . $version);
                }
            }
        }
        if (isset($dep->exclude)) {
            foreach ($dep->exclude as $exclude) {
                if (version_compare($version, $exclude, '==')) {
                    if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) &&
                          !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                        return $this->raiseError('%s is not compatible with PHP version ' .
                            $exclude);
                    } else {
                        return $this->warning(
                            'warning: %s is not compatible with PHP version ' .
                            $exclude);
                    }
                }
            }
        }
        return true;
    }

    /**
     * This makes unit-testing a heck of a lot easier
     */
    function getPEARVersion()
    {
        return '@PACKAGE_VERSION@' === '@'.'PACKAGE_VERSION@' ? '2.0.0' : '@PACKAGE_VERSION@';
    }

    function validatePearinstallerDependency(PEAR2_Pyrus_PackageFile_v2_Dependencies_Dep $dep)
    {
        $pearversion = $this->getPEARVersion();
        $extra = $this->_getExtraString($dep);
        if (version_compare($pearversion, $dep->min, '<')) {
            if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                return $this->raiseError('%s requires PEAR Installer' . $extra .
                    ', installed version is ' . $pearversion);
            } else {
                return $this->warning('warning: %s requires PEAR Installer' . $extra .
                    ', installed version is ' . $pearversion);
            }
        }
        if (isset($dep->max)) {
            if (version_compare($pearversion, $dep->max, '>')) {
                if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                    return $this->raiseError('%s requires PEAR Installer' . $extra .
                        ', installed version is ' . $pearversion);
                } else {
                    return $this->warning('warning: %s requires PEAR Installer' . $extra .
                        ', installed version is ' . $pearversion);
                }
            }
        }
        if (isset($dep->exclude)) {
            foreach ($dep->exclude as $exclude) {
                if (version_compare($exclude, $pearversion, '==')) {
                    if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                        return $this->raiseError('%s is not compatible with PEAR Installer ' .
                            'version ' . $exclude);
                    } else {
                        return $this->warning('warning: %s is not compatible with PEAR ' .
                            'Installer version ' . $exclude);
                    }
                }
            }
        }
        return true;
    }

    function validateSubpackageDependency(PEAR2_Pyrus_PackageFile_v2_Dependencies_Package $dep, $required, $params)
    {
        return $this->validatePackageDependency($dep, $required, $params);
    }

    /**
     * @param array dependency information (2.0 format)
     * @param boolean whether this is a required dependency
     * @param array a list of downloaded packages to be installed, if any
     */
    function validatePackageDependency(PEAR2_Pyrus_PackageFile_v2_Dependencies_Package $dep, $required, $params)
    {
        if ($this->_state != PEAR2_Pyrus_Validate::INSTALLING &&
              $this->_state != PEAR2_Pyrus_Validate::DOWNLOADING) {
            return true;
        }
        if (isset($dep->providesextension)) {
            if ($this->extension_loaded($dep->providesextension)) {
                $req = $required ? 'required' : 'optional';
                $info = $dep->getInfo();
                $info['name'] = $info['providesextension'];
                $subdep = new PEAR2_Pyrus_PackageFile_v2_Dependencies_Package(
                    $req, 'extension', null, $info, 0);
                $ret = $this->validateExtensionDependency($subdep, $required);
                if ($ret === true) {
                    return true;
                }
            }
        }
        if ($this->_state == PEAR2_Pyrus_Validate::INSTALLING) {
            return $this->_validatePackageInstall($dep, $required);
        }
        if ($this->_state == PEAR2_Pyrus_Validate::DOWNLOADING) {
            return $this->_validatePackageDownload($dep, $required, $params);
        }
    }

    function _validatePackageDownload(PEAR2_Pyrus_PackageFile_v2_Dependencies_Package$dep, $required, $params)
    {
        $depname = PEAR2_Pyrus_Config::parsedPackageNameToString(array('package' => $dep->name,
                                                                       'channel' => $dep->channel), true);
        $found = false;
        foreach ($params as $param) {
            if ($param->name == $dep->name && $param->channel == $dep->channel) {
                $found = true;
                break;
            }
        }
        if (!$found && isset($dep->providesextension)) {
            foreach ($params as $param) {
                if ($param->isExtension($dep->providesextension)) {
                    $found = true;
                    break;
                }
            }
        }
        if ($found) {
            $version = $param->version['release'];
            $installed = false;
            $downloaded = true;
        } else {
            if (PEAR2_Pyrus_Config::current()->registry->exists($dep->name, $dep->channel)) {
                $installed = true;
                $downloaded = false;
                $version = PEAR2_Pyrus_Config::current()->registry->info($dep->name,
                    $dep->channel, 'version');
            } else {
                $version = 'not installed or downloaded';
                $installed = false;
                $downloaded = false;
            }
        }
        $extra = $this->_getExtraString($dep);
        if (!isset($dep->min) && !isset($dep->max) &&
              !isset($dep->recommended) && !isset($dep->exclude)) {
            if ($installed || $downloaded) {
                $installed = $installed ? 'installed' : 'downloaded';
                if ($dep->conflicts) {
                    if ($version) {
                        $rest = ", $installed version is " . $version;
                    } else {
                        $rest = '';
                    }
                    if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                        return $this->raiseError('%s conflicts with package "' . $depname . '"' .
                            $extra . $rest);
                    } else {
                        return $this->warning('warning: %s conflicts with package "' . $depname . '"' .
                            $extra . $rest);
                    }
                }
                return true;
            } else {
                if ($dep->conflicts) {
                    return true;
                }
                if ($required) {
                    if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                        return $this->raiseError('%s requires package "' . $depname . '"' .
                            $extra);
                    } else {
                        return $this->warning('warning: %s requires package "' . $depname . '"' .
                            $extra);
                    }
                } else {
                    return $this->warning('%s can optionally use package "' . $depname . '"' .
                        $extra);
                }
            }
        }
        if (!$installed && !$downloaded) {
            if ($dep->conflicts) {
                return true;
            }
            if ($required) {
                if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                    return $this->raiseError('%s requires package "' . $depname . '"' .
                        $extra);
                } else {
                    return $this->warning('warning: %s requires package "' . $depname . '"' .
                        $extra);
                }
            } else {
                return $this->warning('%s can optionally use package "' . $depname . '"' .
                    $extra);
            }
        }
        $fail = false;
        if (isset($dep->min)) {
            if (version_compare($version, $dep->min, '<')) {
                $fail = true;
            }
        }
        if (isset($dep->max)) {
            if (version_compare($version, $dep->max, '>')) {
                $fail = true;
            }
        }
        if ($fail && !$dep->conflicts) {
            $installed = $installed ? 'installed' : 'downloaded';
            $dep = PEAR2_Pyrus_Config::parsedPackageNameToString(array('package' => $dep->name,
                                                                       'channel' => $dep->channel), true);
            if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                return $this->raiseError('%s requires package "' . $depname . '"' .
                    $extra . ", $installed version is " . $version);
            } else {
                return $this->warning('warning: %s requires package "' . $depname . '"' .
                    $extra . ", $installed version is " . $version);
            }
        } elseif ((isset($dep->min) || isset($dep->max)) && !$fail &&
              $dep->conflicts && !isset($dep->exclude)) {
            $installed = $installed ? 'installed' : 'downloaded';
            if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                return $this->raiseError('%s conflicts with package "' . $depname . '"' . $extra .
                    ", $installed version is " . $version);
            } else {
                return $this->warning('warning: %s conflicts with package "' . $depname . '"' .
                    $extra . ", $installed version is " . $version);
            }
        }
        if (isset($dep->exclude)) {
            $installed = $installed ? 'installed' : 'downloaded';
            foreach ($dep->exclude as $exclude) {
                if (version_compare($version, $exclude, '==') && !$dep->conflicts) {
                    if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) &&
                          !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                        return $this->raiseError('%s is not compatible with ' .
                            $installed . ' package "' .
                            $depname . '" version ' .
                            $exclude);
                    } else {
                        return $this->warning('warning: %s is not compatible with ' .
                            $installed . ' package "' .
                            $depname . '" version ' .
                            $exclude);
                    }
                } elseif (version_compare($version, $exclude, '!=') && $dep->conflicts) {
                    $installed = $installed ? 'installed' : 'downloaded';
                    if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                        return $this->raiseError('%s conflicts with package "' . $depname . '"' .
                            $extra . ", $installed version is " . $version);
                    } else {
                        return $this->warning('warning: %s conflicts with package "' . $depname . '"' .
                            $extra . ", $installed version is " . $version);
                    }
                }
            }
        }
        if (isset($dep->recommended)) {
            $installed = $installed ? 'installed' : 'downloaded';
            if (version_compare($version, $dep->recommended, '==')) {
                return true;
            } else {
                if (!$found && $installed) {
                    $param = PEAR2_Pyrus_Config::current()->registry->package[$dep->channel . '/' . $dep->name];
                }
                if ($param) {
                    $found = false;
                    foreach ($params as $parent) {
                        if ($parent->name == $this->_currentPackage['package'] &&
                              $parent->channel == $this->_currentPackage['channel']) {
                            $found = true;
                            break;
                        }
                    }
                    if ($found) {
                        if ($param->isCompatible($parent)) {
                            return true;
                        }
                    } else { // this is for validPackage() calls
                        $parent = PEAR2_Pyrus_Config::current()->registry->package[
                            $this->_currentPackage['channel'] . '/' .
                            $this->_currentPackage['package']];
                        if ($parent !== null) {
                            if ($param->isCompatible($parent)) {
                                return true;
                            }
                        }
                    }
                }
                if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force']) &&
                      !isset(PEAR2_Pyrus_Installer::$options['loose'])) {
                    return $this->raiseError('%s dependency package "' . $depname .
                        '" ' . $installed . ' version ' . $version .
                        ' is not the recommended version ' . $dep->recommended .
                        ', but may be compatible, use --force to install');
                } else {
                    return $this->warning('warning: %s dependency package "' . $depname .
                        '" ' . $installed . ' version ' . $version .
                        ' is not the recommended version ' . $dep->recommended);
                }
            }
        }
        return true;
    }

    function _validatePackageInstall($dep, $required)
    {
        return $this->_validatePackageDownload($dep, $required, array());
    }

    function validatePackageUninstall($dep, $required, $param, $params)
    {
        $depname = PEAR2_Pyrus_Config::parsedPackageNameToString(array('package' => $dep->name,
                                                                       'channel' => $dep->channel), true);
        $version = $package->version;
        $extra = $this->_getExtraString($dep);
        if (isset($dep->exclude)) {
            if (!is_array($dep->exclude)) {
                $dep->exclude = array($dep->exclude);
            }
        }
        if ($dep->conflicts) {
            return true; // uninstall OK - these packages conflict (probably installed with --force)
        }
        if (!isset($dep->min) && !isset($dep->max)) {
            if ($required) {
                if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['force'])) {
                    return $this->raiseError('"' . $depname . '" is required by ' .
                        'installed package %s' . $extra);
                } else {
                    return $this->warning('warning: "' . $depname . '" is required by ' .
                        'installed package %s' . $extra);
                }
            } else {
                return $this->warning('"' . $depname . '" can be optionally used by ' .
                        'installed package %s' . $extra);
            }
        }
        $fail = false;
        if (isset($dep->min)) {
            if (version_compare($version, $dep->min, '>=')) {
                $fail = true;
            }
        }
        if (isset($dep->max)) {
            if (version_compare($version, $dep->max, '<=')) {
                $fail = true;
            }
        }
        if ($fail) {
            if ($required) {
                if (!isset(PEAR2_Pyrus_Installer::$options['nodeps']) && !isset(PEAR2_Pyrus_Installer::$options['nodeps']['force'])) {
                    return $this->raiseError($depname . $extra . ' is required by installed package' .
                        ' "%s"');
                } else {
                    return $this->warning('warning: ' . $depname . $extra .
                        ' is required by installed package "%s"');
                }
            } else {
                return $this->warning($depname . $extra . ' can be optionally used by installed package' .
                        ' "%s"');
            }
        }
        return true;
    }

    /**
     * validate a downloaded package against installed packages
     *
     * As of PEAR 1.4.3, this will only validate
     *
     * @param array|PEAR_Downloader_Package|PEAR_PackageFile_v1|PEAR_PackageFile_v2
     *              $pkg package identifier (either
     *                   array('package' => blah, 'channel' => blah) or an array with
     *                   index 'info' referencing an object)
     * @param PEAR_Downloader $dl
     * @param array $params full list of packages to install
     * @return true|PEAR_Error
     */
    function validatePackage($pkg, $dl, $params = array())
    {
        $deps = PEAR2_Pyrus_Config::current()->registry->getDependentPackageDependencies($pkg);
        $fail = false;
        if ($deps) {
            $dp = new PEAR_Downloader_Package($dl);
            if (is_object($pkg)) {
                $dp->setPackageFile($pkg);
            } else {
                $dp->setDownloadURL($pkg);
            }
            foreach ($deps as $channel => $info) {
                foreach ($info as $package => $ds) {
                    foreach ($params as $packd) {
                        if (strtolower($packd->getPackage()) == strtolower($package) &&
                              $packd->getChannel() == $channel) {
                            $dl->log(3, 'skipping installed package check of "' .
                                        PEAR2_Pyrus_Config::parsedPackageNameToString(
                                            array('channel' => $channel, 'package' => $package),
                                            true) .
                                        '", version "' . $packd->getVersion() . '" will be ' .
                                        'downloaded and installed');
                            continue 2; // jump to next package
                        }
                    }
                    foreach ($ds as $d) {
                        $checker = new PEAR2_Pyrus_Dependency_Validator(
                            array('channel' => $channel, 'package' => $package), $this->_state,
                            $this->errs);
                        $dep = $d['dep'];
                        $required = $d['type'] == 'required';
                        try {
                            $ret = $checker->_validatePackageDownload($dep, $required,
                                 array(&$dp));
                            if (is_array($ret)) {
                                $dl->log(0, $ret[0]);
                            }
                        } catch (Exception $e) {
                            $dl->log(0, $e->getMessage());
                            $fail = true;
                        }
                    }
                }
            }
        }
        if ($fail) {
            return $this->raiseError(
                '%s cannot be installed, conflicts with installed packages');
        }
        return true;
    }

    function raiseError($msg)
    {
        if (isset(PEAR2_Pyrus_Installer::$options['ignore-errors'])) {
            return $this->warning($msg);
        }
        $this->errs->E_ERROR[] = new PEAR2_Pyrus_Dependency_Exception(sprintf($msg, PEAR2_Pyrus_Config::parsedPackageNameToString(
            $this->_currentPackage, true)));
        return false;
    }

    function warning($msg)
    {
        $this->errs->E_WARNING[] = new PEAR2_Pyrus_Dependency_Exception(sprintf($msg, PEAR2_Pyrus_Config::parsedPackageNameToString(
            $this->_currentPackage, true)));
        return true;
    }
}
?>
