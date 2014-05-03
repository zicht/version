<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */

namespace Zicht\Version;

/**
 * Constraint class, exposes some methods to easily match 'composer style' version constraints.
 */
final class Constraint
{
    /**
     * Check whether the passed version matches the passed constraint. Will add explanation about failing constraints
     * to the optional $failures parameter.
     *
     * @param string $constraint
     * @param Version $version
     * @param array &$failures
     * @return array
     */
    public static function isMatch($constraint, Version $version, &$failures = array())
    {
        foreach (explode(',', $constraint) as $constraintExpression) {
            $c = self::fromString($constraintExpression);
            if (!$c->match($version)) {
                $failures[]= "Constraint '$constraintExpression' failed";
            }
        }
        return count($failures) === 0;
    }


    /**
     * Construct a Constraint instance based on the passed string constraint.
     *
     * @param string $constraintExpression
     * @return Constraint
     *
     * @throws \InvalidArgumentException
     */
    public static function fromString($constraintExpression)
    {
        if (!preg_match('/^(==?|\!=?|~|[<>]=?)?((?:\*|[0-9]+)(?:.(?:\*|[0-9]+))*)(?:\@(\w+))?$/', $constraintExpression, $m)) {
            throw new \InvalidArgumentException("Constraint expression '$constraintExpression' could not be parsed");
        }
        return new self(
            !empty($m[1]) ? $m[1] : '=',
            $m[2],
            !empty($m[3]) ? $m[3] : 'stable'
        );
    }


    /**
     * Constructor for a single version constraint.
     *
     * @param string $operator
     * @param string $version
     * @param string $stability
     */
    public function __construct($operator, $version, $stability)
    {
        $this->operator = $operator;
        $this->version = explode('.', $version);
        while (end($this->version) === '*') {
            array_pop($this->version);
        }
        $this->version = array_map('intval', $this->version);
        $this->stability = $stability;
    }


    /**
     * Match the constraint against the passed version.
     *
     * @param Version $version
     * @return bool
     *
     * @throws \UnexpectedValueException
     */
    public function match(Version $version)
    {
        $versionStabilityIndex = array_search($version->getStability(), Version::$stabilities);
        $constraintStabilityIndex = array_search($this->stability, Version::$stabilities);

        if ($this->compare($versionStabilityIndex, $constraintStabilityIndex) !== 0) {
            return false;
        }

        $numericValues = $version->numeric();
        $comparisons = array();

        foreach ($this->version as $i => $value) {
            $comparisons[$i] = $this->compare($numericValues[$i], $value);
        }

        switch ($this->operator) {
            case '=': case '==':
                // all parts must be equal:
                return array_filter($comparisons) === array();
                break;
            case '!': case '!=':
                // any part must be unequal:
                return array_filter($comparisons) !== array();
                break;
            case '>=':
            case '<=':
            case '>':
            case '<':
                // the first mismatch is the tie breaker
                $mismatches = array_filter($comparisons);
                if (!count($mismatches)) {
                    // if no mismatches, only ok if equality operator
                    return in_array($this->operator, array('<=', '>='));
                } else {
                    $first = array_shift($mismatches);
                    return
                           ($this->operator === '<'  && $first < 0)
                        || ($this->operator === '<=' && $first < 0)
                        || ($this->operator === '>'  && $first > 0)
                        || ($this->operator === '>=' && $first > 0)
                    ;
                }
        }

        throw new \UnexpectedValueException("An unknown edge case was encountered. Is the constraint valid?");
    }


    /**
     * Do a numeric comparison of two parts
     *
     * @param int $versionPart
     * @param int $constraintPart
     * @return int
     */
    private function compare($versionPart, $constraintPart)
    {
        if ($versionPart < $constraintPart) {
            return -1;
        }
        if ($versionPart > $constraintPart) {
            return 1;
        }
        return 0;
    }
}
