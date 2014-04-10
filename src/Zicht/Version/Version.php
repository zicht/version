<?php
/**
 * @author Gerard van Helden <gerard@zicht.nl>
 * @copyright Zicht online <http://zicht.nl>
 */

namespace Zicht\Version;


/**
 * Version parsing/formatting/normalizer class
 */
final class Version
{
    /**
     * Formats used per part
     */
    public static $formats = array(
        self::MAJOR         => '%d',
        self::MINOR         => '.%d',
        self::PATCH         => '.%d',
        self::STABILITY     => '-%s',
        self::STABILITY_NO  => '.%d'
    );

    /**
     * The semantic value of each of the parts
     */
    public static $ordinality = array(
        self::MAJOR,
        self::MINOR,
        self::PATCH,
        self::STABILITY,
        self::STABILITY_NO
    );

    /**
     * Available stability suffixes
     */
    public static $stabilities = array(
        'dev', 'alpha', 'beta', 'rc', 'stable'
    );

    /**
     * Default values for all parts
     */
    public static $defaults = array(
        self::MINOR         => 0,
        self::PATCH         => 0,
        self::STABILITY     => 'stable',
        self::STABILITY_NO  => 1
    );

    /**
     * The first part of the version ("1" in "1.2.3-beta.4")
     */
    const MAJOR         = 'MAJOR';

    /**
     * The second part of the version ("2" in "1.2.3-beta.4")
     */
    const MINOR         = 'MINOR';

    /**
     * The third part of the version ("3" in "1.2.3-beta.4")
     */
    const PATCH         = 'PATCH';

    /**
     * The stability of the version ("beta" in "1.2.3-beta.4")
     */
    const STABILITY     = 'STABILITY';

    /**
     * The stability increment of the version ("4" in "1.2.3-beta.4")
     */
    const STABILITY_NO  = 'STABILITY_NO';


    /**
     * Parse the string following the MAJOR[.MINOR[.PATCH]][-STABILITY[.STABILITY_NO]] pattern
     *
     * @param string $versionString
     * @return null|Version
     */
    public static function fromString($versionString)
    {
        if (
            preg_match('/^
                (?P<MAJOR>\d+)
                (?:
                    .(?P<MINOR>\d+)
                    (?:
                        .(?P<PATCH>\d+)
                    )?
                )?
                (?:
                    -(?P<STABILITY>' . join('|', self::$stabilities) . ')
                    (?:.(?P<STABILITY_NO>\d+))?
                )?
            $/x', $versionString, $m)
        ) {
            return new self(
                $m[self::MAJOR],
                isset($m[self::MINOR])          ? (int)$m[self::MINOR]           : null,
                isset($m[self::PATCH])          ? (int)$m[self::PATCH]           : null,
                isset($m[self::STABILITY])      ?      $m[self::STABILITY]       : null,
                isset($m[self::STABILITY_NO])   ? (int)$m[self::STABILITY_NO]    : null
            );
        }
        return null;
    }


    /**
     * Comparator for version objects
     *
     * @param Version $a
     * @param Version $b
     * @return int
     */
    public static function compare(Version $a, Version $b)
    {
        foreach (self::$ordinality as $key) {
            $aVal = $a->get($key);
            $bVal = $b->get($key);

            if ($key === self::STABILITY) {
                $aVal = array_search($aVal, self::$stabilities);
                $bVal = array_search($bVal, self::$stabilities);
            }

            if ($aVal < $bVal) {
                return -1;
            } elseif ($aVal > $bVal) {
                return 1;
            }
        }
        return 0;
    }

    /**
     * Contains the part values.
     *
     * @var array
     */
    private $parts = array();

    /**
     * Construct the version object based on the passed
     *
     * @param int $major
     * @param int $minor
     * @param int $patch
     * @param string $stability
     * @param int $stabilityRelease
     */
    public function __construct($major, $minor = null, $patch = null, $stability = null, $stabilityRelease = null)
    {
        $this
            ->set(self::MAJOR, $major)
            ->set(self::MINOR, $minor)
            ->set(self::PATCH, $patch)
            ->set(self::STABILITY, $stability)
            ->set(self::STABILITY_NO, $stabilityRelease)
        ;
    }


    /**
     * Increment a specific part of the version.
     *
     * E.g.:
     *  incrementing "stability" of "2.0.0-alpha.4" would b
     *
     * @param string $part
     * @return $this
     */
    public function increment($part)
    {
        foreach (array_reverse(self::$ordinality) as $currentPart) {
            if ($currentPart === $part) {
                switch ($part) {
                    case self::STABILITY:
                        $this->set($currentPart, self::$stabilities[array_search($this->get($currentPart), self::$stabilities) +1]);
                        break;
                    default:
                        $this->set($currentPart, $this->get($currentPart) +1);
                        break;
                }
                break;
            } else {
                switch ($currentPart) {
                    case self::STABILITY_NO:
                        $this->set($currentPart, null);
                        break;
                    case self::STABILITY:
                        $this->set($currentPart, 'dev');
                        break;
                    default:
                        $this->set($currentPart, '0');
                }
            }
        }

        return $this;
    }


    /**
     * Return one part of the version
     *
     * @param string $part
     * @return mixed
     */
    private function get($part)
    {
        return $this->parts[$part];
    }


    /**
     * Set a specific part of the Version
     *
     * @param string $part
     * @param mixed $value
     * @return self
     */
    private function set($part, $value)
    {
        if (null === $value && isset(self::$defaults[$part])) {
            $value = self::$defaults[$part];
        }
        $this->parts[$part] = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->format();
    }

    /**
     * Formats the version.
     *
     * @return string
     */
    public function format()
    {
        $ret = '';

        foreach (self::$formats as $key => $format) {
            $value = $this->get($key);

            // -stable is not added to the version, it is implied
            if ($key == self::STABILITY && $value == end(self::$stabilities)) {
                break;
            }

            $ret .= sprintf($format, $value);

            // -dev has no stability increments
            if ($key == self::STABILITY && $value == self::$stabilities[0]) {
                break;
            }
        }
        return $ret;
    }
}
