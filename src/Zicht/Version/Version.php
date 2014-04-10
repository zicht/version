<?php

namespace Zicht\Version;

final class Version
{
    public static $formats = array(
        self::MAJOR         => '%d',
        self::MINOR         => '.%d',
        self::PATCH         => '.%d',
        self::STABILITY     => '-%s',
        self::STABILITY_NO  => '.%d'
    );
    public static $ordinality = array(
        self::MAJOR,
        self::MINOR,
        self::PATCH,
        self::STABILITY,
        self::STABILITY_NO
    );
    public static $stabilities = array(
        'dev', 'alpha', 'beta', 'rc', 'stable'
    );

    const MAJOR         = 'MAJOR';
    const MINOR         = 'MINOR';
    const PATCH         = 'PATCH';
    const STABILITY     = 'STABILITY';
    const STABILITY_NO  = 'STABILITY_NO';

    public static function next($version, $increment = self::STABILITY)
    {
        return (string)self::fromString($version)->increment($increment);
    }


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
                isset($m[self::MINOR])          ? $m[self::MINOR]           : null,
                isset($m[self::PATCH])          ? $m[self::PATCH]           : null,
                isset($m[self::STABILITY])      ? $m[self::STABILITY]       : null,
                isset($m[self::STABILITY_NO])   ? $m[self::STABILITY_NO]    : null
            );
        }
        return null;
    }


    public static function compare($a, $b)
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




    private $parts = array();

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


    private function get($part)
    {
        return $this->parts[$part];
    }


    private function set($part, $value)
    {
        $this->parts[$part] = $value;
        return $this;
    }


    public function __toString()
    {
        return $this->format();
    }


    public function format()
    {
        $ret = '';

        foreach (self::$formats as $key => $format) {
            if (null === $this->get($key)) {
                break;
            } else {
                $ret .= sprintf($format, $value = $this->get($key));
            }
        }
        return $ret;
    }


    protected function isHeadOrTailStability($value)
    {
//        return (&& $value === end(self::$stabilities) || $value === self::$stabilities[0]);
    }
}
