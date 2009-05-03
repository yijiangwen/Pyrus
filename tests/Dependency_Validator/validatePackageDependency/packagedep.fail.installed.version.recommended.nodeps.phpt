--TEST--
Dependency_Validator: package dependency, installed recommended version fail --nodeps
--FILE--
<?php
require __DIR__ . '/../setup.registry.php.inc';

PEAR2_Pyrus_Installer::$options['nodeps'] = true;
$fake = new PEAR2_Pyrus_PackageFile_v2;
$fake->name = 'foo';
$fake->channel = 'pear2.php.net';
$fake->version['release'] = '1.2.3';
$fake->files['foo'] = array('role' => 'php');
$fake->notes = 'hi';
$fake->summary = 'hi';
$fake->description = 'hi';
PEAR2_Pyrus_Config::current()->registry->install($fake);

$foo = $fake->dependencies['required']->package['pear2.php.net/foo']->recommended('1.2.2');

$test->assertEquals(true, $validator->validatePackageDependency($foo, array()), 'foo');
$test->assertEquals(1, count($errs->E_WARNING), 'foo count');
$test->assertEquals(1, count($errs), 'foo count 2');
$test->assertEquals('warning: channel://pear2.php.net/test dependency package "channel://pear2.php.net/foo" installed version 1.2.3 is not the recommended version 1.2.2', $errs->E_WARNING[0]->getMessage(), 'foo message');
?>
===DONE===
--CLEAN--
<?php
$dir = dirname(__DIR__) . '/testit';
include __DIR__ . '/../../clean.php.inc';
?>
--EXPECT--
===DONE===