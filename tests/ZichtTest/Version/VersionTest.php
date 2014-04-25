<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */

namespace ZichtTest\Version\VersionTest;

use Zicht\Version\Version;

/**
 * @covers Zicht\Version\Version
 */
class VersionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider strVersions
     * @param $input
     * @param $expect
     */
    public function testFromString($input, $expect)
    {
        $this->assertEquals($expect, Version::fromString($input));
    }
    public function strVersions()
    {
        return array(
            array('1', new Version(1)),
            array('1.0', new Version(1, 0)),
            array('1.0-stable', new Version(1, 0, null, 'stable')),
            array('2.34', new Version(2, 34)),
            array('5.67.89', new Version(5, 67, 89)),
            array('5.67.89-alpha', new Version(5, 67, 89, 'alpha')),
            array('5.67.89-beta.2', new Version(5, 67, 89, 'beta', 2)),
            array('invalid', null),
            array('-alpha', null),
            array('5.4.4.1-alpha', null)
        );
    }


    /**
     * @dataProvider strVersions2
     * @param $input
     * @param $expect
     */
    public function testToString($expect, $input)
    {
        $this->assertEquals($expect, (string)$input);
    }
    public function strVersions2()
    {
        return array(
            array('1.0.0',              new Version(1)),
            array('1.0.0',              new Version(1, 0)),
            array('1.0.0',              new Version(1, 0, null, 'stable')),
            array('1.0.0',              new Version(1, null, null, 'stable')),
            array('1.0.0-dev',          new Version(1, null, null, 'dev')),
            array('1.2.3-dev',          new Version(1, 2, 3, 'dev')),
            array('2.34.0',             new Version(2, 34)),
            array('5.67.89',            new Version(5, 67, 89)),
            array('5.67.89-alpha.1',    new Version(5, 67, 89, 'alpha')),
            array('5.67.89-beta.2',     new Version(5, 67, 89, 'beta', 2)),
            array('5.67.89-dev',        new Version(5, 67, 89, 'dev', 123))
        );
    }

    /**
     * @dataProvider versionIncrements
     * @param $input
     * @param $expect
     */
    public function testIncrement($expect, $in, $part)
    {
        $this->assertEquals($expect, $in->increment($part));
    }
    public function versionIncrements()
    {
        $v = function() {
            $args = func_get_args();
            $r = new \ReflectionClass('Zicht\Version\Version');
            return $r->newInstanceArgs($args);
        };

        return array(
            array($v(2, 0, 0, 'dev'),       $v(1),                  Version::MAJOR),
            array($v(2, 1, 0, 'dev'),       $v(2, 0),               Version::MINOR),
            array($v(2, 1, 1, 'dev'),       $v(2, 1),               Version::PATCH),
            array($v(2, 1, 1, 'alpha'),     $v(2, 1, 1, 'dev'),     Version::STABILITY),
            array($v(2, 1, 1, 'beta'),      $v(2, 1, 1, 'alpha'),   Version::STABILITY),
            array($v(2, 1, 1, 'beta', 1),   $v(2, 1, 1, 'alpha', 4),   Version::STABILITY)
        );
    }


    /**
     * @dataProvider comparisons
     * @param $a
     * @param $b
     * @param $expected
     */
    public function testCompare($a, $b, $expected)
    {
        $this->assertEquals($expected, Version::compare($a, $b));
        $this->assertEquals(-$expected, Version::compare($b, $a));
    }
    public function comparisons()
    {
        $v = function() {
            $args = func_get_args();
            $r = new \ReflectionClass('Zicht\Version\Version');
            return $r->newInstanceArgs($args);
        };

        return array(
            array($v(2, 0, 0, 'dev'),       $v(1),                  1),
            array($v(2, 0, 0, 'dev'),       $v(2, 1, 0, 'dev'),     -1),
            array($v(2, 1, 0, 'dev'),       $v(2, 1, 0, 'dev'),     0),
            array($v(2, 1, 0, 'stable'),    $v(2, 1, 0, 'dev'),     1),
            array($v(2, 1, 0, 'alpha', 1),  $v(2, 1, 0, 'dev'),     1),
            array($v(2, 1, 0, 'alpha', 1),  $v(2, 1, 0, 'alpha'),   0),
       );
    }


    public function formatTest()
    {
        $v = function() {
            $args = func_get_args();
            $r = new \ReflectionClass('Zicht\Version\Version');
            return $r->newInstanceArgs($args);
        };

        return array(
            array($v(2, 0, 0, 'dev'),       $v(1),                  1),
            array($v(2, 0, 0, 'dev'),       $v(2, 1, 0, 'dev'),     -1),
            array($v(2, 1, 0, 'dev'),       $v(2, 1, 0, 'dev'),     0),
            array($v(2, 1, 0, 'stable'),    $v(2, 1, 0, 'dev'),     1),
            array($v(2, 1, 0, 'alpha', 1),  $v(2, 1, 0, 'dev'),     1),
            array($v(2, 1, 0, 'alpha', 1),  $v(2, 1, 0, 'alpha'),   0),
            array($v(2, 1, 0),              $v(2, 1, 0),            0),
            array($v(2, 1, 0),              $v(2, 1),               0),
            array($v(2, 1, 0),              $v(2, 1),               0),
       );
    }


    /**
     * @dataProvider strVersions3
     * @param $versionStr
     * @param $isConform
     * @return mixed
     */
    public function testIsConform($versionStr, $isConform)
    {
        return $this->assertEquals($isConform, Version::isConform($versionStr));
    }
    public function strVersions3()
    {
        return array(
            array('1', false),
            array('1.0', false),
            array('1.0-stable', false),
            array('2.34', false),
            array('5.67.89-alpha', false),
            array('5.67.89-dev', true),
            array('5.67.89-beta.2', true),
            array('5.67.89-beta.2', true),
            array('5.67.89-dev.1', false),
            array('5.67.89-stable', false),
            array('5.67.89', true),
            array('5.67.89-stable.1', false),
            array('5.67-rc.1', false),
            array('5.67.0-rc.1', true),
        );
    }
}
