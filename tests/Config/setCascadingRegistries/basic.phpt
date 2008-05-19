--TEST--
PEAR2_Pyrus_Config::setCascading Registries() basic test
--FILE--
<?php
require dirname(__FILE__) . '/setup.php.inc';
@mkdir(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'foo');
set_include_path(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'foo');
$c = $configclass::singleton(dirname(__FILE__) . '/something' . PATH_SEPARATOR . dirname(__FILE__) . '/foo', dirname(__FILE__) . '/something/blah');
restore_include_path();
$test->assertEquals(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'something', $c->registry->path, 'registry path');
$test->assertEquals(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'foo', $c->registry->parent->path, 'registry->parent path');
$test->assertNull($c->registry->parent->parent, 'registry parent parent');
$test->assertEquals(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'something', $c->channelregistry->path, 'channelregistry path');
$test->assertEquals(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'foo', $c->channelregistry->parent->path, 'channelregistry->parent path');
$test->assertNull($c->channelregistry->parent->parent, 'channelregistry parent parent');
?>
===DONE===
--CLEAN--
<?php
$dir = __DIR__ . '/foo';
include __DIR__ . '/../../clean.php.inc';
?>
<?php
$dir = __DIR__ . '/something';
include __DIR__ . '/../../clean.php.inc';
?>
--EXPECT--
===DONE===