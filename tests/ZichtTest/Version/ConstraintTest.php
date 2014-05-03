<?php
    /**
     * @author Gerard van Helden <gerard@zicht.nl>
     * @copyright Zicht online <http://zicht.nl>
     */

namespace ZichtTest\Version\VersionTest;

use Zicht\Version\Version;
use Zicht\Version\Constraint;

/**
 * @covers Zicht\Version\Constraint
 */
class ConstraintTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider constraints
     *
     * @param $constraintStr
     * @param $versionStr
     * @param $expectMatch
     */
    public function testConstraint($constraintStr, $versionStr, $expectMatch, $defaultStability = 'stable')
    {
        $failures = array();
        $isMatch = Constraint::isMatch($constraintStr, Version::fromString($versionStr), $failures);
        if ($expectMatch) {
            if (count($failures)) {
                $this->fail(join("; ", $failures));
            } else {
                $this->assertTrue($isMatch);
            }
        } else {
            $this->assertGreaterThan(0, count($failures));
        }
    }


    public function constraints()
    {
        return array(
            // equality
            array('1',              '1.0.0', true),
            array('1',              '1.1.0', true),
            array('1.*',            '1.1.0', true),
            array('1.*',            '1.2.0', true),

            array('=1',             '1.0.0', true),
            array('=1',             '1.1.0', true),
            array('=1.*',           '1.1.0', true),
            array('=1.*',           '1.2.0', true),
            array('==1',            '1.0.0', true),
            array('==1',            '1.1.0', true),
            array('==1.*',          '1.1.0', true),
            array('==1.*',          '1.2.0', true),

            array('1.*@stable',     '1.2.0', true),
            array('1.*@dev',        '1.2.0', false),
            array('1.2.0@stable',   '1.2.0', true),
            array('1.2.0@stable',   '1.2.0-alpha.1', false),

            // inequality
            array('!1.*',           '1.2.0', false),
            array('!1.*@stable',    '1.2.0', false),
            array('!1.*',           '2.2.0', true),
            array('!=1.*',          '1.2.0', false),
            array('!=1.*',          '2.2.0', true),

            // comparison
            array('<1.2.0@stable',  '1.1.0', true),
            array('<1.2.0',         '1.1.0', true),
            array('<1.2.0@stable',  '1.2.0', false),
            array('<1.2.0@stable',  '1.2.1', false),

            array('<=1.2.0@stable', '1.1.0', true),
            array('<=1.2.0',        '1.1.0', true),
            array('<=1.2.0@stable', '1.2.0', true),
            array('<=1.2.0@stable', '1.2.1', false),

            array('>1.2.0@stable',  '1.1.1', false),
            array('>1.2.0@stable',  '1.2.0', false),
            array('>1.2.0@stable',  '1.2.1', true),
            array('>1.2.0@stable',  '2.2.1', true),

            array('>=1.2.0@stable', '1.1.1', false),
            array('>=1.2.0@stable', '1.2.0', true),
            array('>=1.2.0@stable', '1.2.1', true),
            array('>=1.2.0@stable', '2.2.1', true),

            array('>=1.2.0@stable', '2.2.1-alpha', false),

            // combinations
            array('>=1.2.0,<=2',    '1.3.0', true),
            array('>=1.2.0,<=2',    '2.0.0', true),
            array('>=1.2.0,<2',     '2.0.0', false),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidConstraintWillThrowException()
    {
        Constraint::fromString('foo');
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testInvalidConstraintOperatorWillThrowExceptionWhenMatching()
    {
        $c = new Constraint('%', '1.2.3', 'stable');
        $c->match(Version::fromString('1.2.3'));
    }
}