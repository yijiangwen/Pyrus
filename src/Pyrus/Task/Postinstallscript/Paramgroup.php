<?php
/**
 * <tasks:postinstallscript> paramgroup object
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
 * Implements the postinstallscript file task <paramgroup> tag
 *
 * Sample usage:
 *
 * <code>
 * $task->paramgroup['nameid']->instructions('hi there')
 *  ->condition($task->paramgroup['previous']->param['paramname'], '>=', '25')
 *  ->param['paramname']->prompt('blah')->type('string')->defaultValue('hi');
 * </code>
 *
 * @category  PEAR2
 * @package   PEAR2_Pyrus
 * @author    Greg Beaver <cellog@php.net>
 * @copyright 2008 The PEAR Group
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link      http://svn.pear.php.net/wsvn/PEARSVN/Pyrus/
 */
class PEAR2_Pyrus_Task_Postinstallscript_Paramgroup implements ArrayAccess, Iterator
{
    protected $info;
    protected $index = null;
    protected $parent;
    protected $tasksNs;

    function __construct($tasksNs, $parent, array $info, $index = null)
    {
        if ($tasksNs && $tasksNs[strlen($tasksNs)-1] != ':') {
            $tasksNs .= ':';
        } else {
            $tasksNs = '';
        }
        $this->tasksNs = $tasksNs;
        $this->parent = $parent;
        $this->info = $info;
        $this->index = $index;
    }

    function current()
    {
        $i = key($this->info);
        foreach (array('id', 'instructions', 'name', 'conditiontype', 'value', 'param') as $key) {
            if (!array_key_exists($this->tasksNs . $key, $this->info[$i])) {
                $this->info[$i][$this->tasksNs . $key] = null;
            }
        }
        return new self($this->tasksNs, $this, $this->info[$i], $i);
    }

    function rewind()
    {
        reset($this->info);
    }

    function key()
    {
        $i = key($this->info);
        return $this->info[$i][$this->tasksNs . 'id'];
    }

    function next()
    {
        return next($this->info);
    }

    function valid()
    {
        return current($this->info);
    }

    function locateParamgroup($id)
    {
        foreach ($this->info as $i => $paramgroup)
        {
            if (isset($paramgroup[$this->tasksNs . 'id']) && $paramgroup[$this->tasksNs . 'id'] == $id) {
                return $i;
            }
        }
        return false;
    }

    function offsetGet($var)
    {
        if (isset($this->index)) {
            throw new PEAR2_Pyrus_Task_Exception('Use -> operator to access paramgroup properties');
        }
        $i = $this->locateParamgroup($var);
        if (false === $i) {
            $i = count($this->info);
            $this->info[$i] = array($this->tasksNs . 'id' => $var,
                                    $this->tasksNs . 'instructions' => null,
                                    $this->tasksNs . 'name' => null,
                                    $this->tasksNs . 'conditiontype' => null,
                                    $this->tasksNs . 'value' => null,
                                    $this->tasksNs . 'param' => null);
        } else {
            foreach (array('id', 'instructions', 'name', 'conditiontype', 'value', 'param') as $key) {
                if (!array_key_exists($this->tasksNs . $key, $this->info[$i])) {
                    $this->info[$i][$key] = null;
                }
            }
        }
        return new self($this->tasksNs, $this, $this->info[$i], $i);
    }

    function offsetSet($var, $value)
    {
        if (isset($this->index)) {
            throw new PEAR2_Pyrus_Task_Exception('Use -> operator to access paramgroup properties');
        }
        if (!($value instanceof self)) {
            throw new PEAR2_Pyrus_Task_Exception('Can only set $paramgroup[\'' .
                $var .
                '\'] to PEAR2_Pyrus_Task_Postinstallscript_Paramgroup object');
        }
        if ($var === null) {
            $var = $value->id;
        }
        if ($value->id != $var) {
            throw new PEAR2_Pyrus_Task_Exception('Cannot set ' .
                $var . ' to ' .
                $value->id .
                ', use $paramgroup[] to set a new value');
        }
        if (false === ($i = $this->locateParamgroup($var))) {
            $i = count($this->info);
        }
        $this->info[$i] = $value->getInfo();
        $this->save();
    }

    function offsetExists($var)
    {
        if (isset($this->index)) {
            throw new PEAR2_Pyrus_Task_Exception('Use -> operator to access paramgroup properties');
        }
        $i = $this->locateParamgroup($var);
        return $i !== false;
    }

    function offsetUnset($var)
    {
        if (isset($this->index)) {
            throw new PEAR2_Pyrus_Task_Exception('Use -> operator to access paramgroup properties');
        }
        $i = $this->locateParamgroup($var);
        if ($i === false) {
            return;
        }
        unset($this->info[$i]);
        $this->info = array_values($this->info);
        $this->save();
    }

    function __get($var)
    {
        if (!isset($this->index)) {
            throw new PEAR2_Pyrus_Task_Exception('Use [] operator to access paramgroups');
        }
        if (!array_key_exists($var, $this->info)) {
            $info = array_keys($this->info);
            $a = $this->tasksNs;
            array_walk($info, function(&$key) use ($a) {$key = str_replace($a, '', $key);});
            throw new PEAR2_Pyrus_Task_Exception(
                'Unknown variable ' . $var . ', should be one of ' . implode(', ', $keys)
            );
        }
        if ($var === 'param') {
            $ret = $this->info[$this->tasksNs . 'param'];
            if (!is_array($ret)) {
                $ret = array($ret);
            }
            return new PEAR2_Pyrus_Task_Postinstallscript_Paramgroup_Params($this->tasksNs, $this, $ret);
        }
        return $this->info[$var];
    }

    function __isset($var)
    {
        if (!isset($this->index)) {
            throw new PEAR2_Pyrus_Task_Exception('Use [] operator to access paramgroups');
        }
        if (!array_key_exists($var, $this->info)) {
            $info = array_keys($this->info);
            $a = $this->tasksNs;
            array_walk($info, function(&$key) use ($a) {$key = str_replace($a, '', $key);});
            throw new PEAR2_Pyrus_Task_Exception(
                'Unknown variable ' . $var . ', should be one of ' . implode(', ', $keys)
            );
        }
        return isset($this->info[$var]);
    }

    function __unset($var)
    {
        if (!isset($this->index)) {
            throw new PEAR2_Pyrus_Task_Exception('Use [] operator to access paramgroups');
        }
        if (!array_key_exists($var, $this->info)) {
            $info = array_keys($this->info);
            $a = $this->tasksNs;
            array_walk($info, function(&$key) use ($a) {$key = str_replace($a, '', $key);});
            throw new PEAR2_Pyrus_Task_Exception(
                'Unknown variable ' . $var . ', should be one of ' . implode(', ', $keys)
            );
        }
        $this->info[$var] = null;
    }

    function __set($var, $value)
    {
        return $this->__call($var, array($value));
    }

    function __call($var, $args)
    {
        if (!isset($this->index)) {
            throw new PEAR2_Pyrus_Task_Exception('Use [] operator to access paramgroups');
        }
        if (!array_key_exists($var, $this->info)) {
            $info = array_keys($this->info);
            $a = $this->tasksNs;
            array_walk($info, function(&$key) use ($a) {$key = str_replace($a, '', $key);});
            throw new PEAR2_Pyrus_Task_Exception(
                'Unknown variable ' . $var . ', should be one of ' . implode(', ', $keys)
            );
        }
        if (!count($args) || $args[0] === null) {
            $this->info[$var] = null;
            $this->save();
            return $this;
        }
        if ($var == 'param') {
            if (!isset($this->info[$var])) {
                $this->info[$var] = $args;
            } else {
                if (!is_array($this->info[$var])) {
                    $this->info[$var] = array($this->info[$var]);
                }
                $this->info[$var] = array_merge($this->info[$var], $args);
            }
        } else {
            $this->info[$var] = $args[0];
        }
        $this->save();
        return $this;
    }

    function getInfo()
    {
        return $this->info;
    }

    function setInfo($index, $info)
    {
        foreach ($info as $key => $null) {
            if ($null === null) {
                unset($info[$key]);
                continue;
            }
            if (is_array($null) && count($null) == 1) {
                $info[$key] = $null[0];
            }
        }
        $this->info[$index] = $info;
    }

    function setParam($index, $info)
    {
        if ($info === null) {
            unset($this->info['param']);
            return;
        }
        foreach ($info as $key => $null) {
            if ($null === null) {
                unset($info[$key]);
                continue;
            }
            if (is_array($null) && count($null) == 1) {
                $info[$key] = $null[0];
            }
        }
        $this->info['param'][$index] = $info;
    }

    function save()
    {
        if ($this->parent instanceof self) {
            $this->parent->setInfo($this->index, $this->info);
            $this->parent->save();
        } else {
            $info = $this->info;
            if (count($info) == 1) {
                $info = $info[0];
            } elseif (!count($info)) {
                $info = null;
            }
            $this->parent->setParamgroup($info);
        }
    }
}
?>