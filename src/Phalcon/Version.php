<?php
/**
 * Version
 *
 * @author Andres Gutierrez <andres@phalconphp.com>
 * @author Eduar Carvajal <eduar@phalconphp.com>
 * @author Wenzel Pünter <wenzel@phelix.me>
 * @version 1.2.6
 * @package Phalcon
*/
namespace Phalcon;

/**
 * Phalcon\Version
 *
 * This class allows to get the installed version of the framework
 *
 * @see https://github.com/phalcon/cphalcon/1.2.6/master/ext/version.c
 */
class Version
{
    /**
     * Area where the version number is set. The format is as follows:
     * ABBCCDE
     *
     * A - Major version
     * B - Med version (two digits)
     * C - Min version (two digits)
     * D - Special release: 1 = Alpha, 2 = Beta, 3 = RC, 4 = Stable
     * E - Special release version i.e. RC1, Beta2 etc.
     *
     * @return array
     */
    protected static function _getVersion()
    {
        return array(1, 2, 6, 4, 1);
    }

    /**
     * Returns the active version (string)
     *
     * <code>
     * echo \Phalcon\Version::get();
     * </code>
     *
     * @return string
     */
    public static function get()
    {
        $version = self::_getVersion();

        $result = $version[0].'.'.$version[1].'.'.$version[2];

        switch ($version[3]) {
            case 1:
                $result .= ' ALPHA '.$version[4];
                break;
            case 2:
                $result .= ' BETA '.$version[4];
                break;
            case 3:
                $result .= ' RC '.$version[4];
                break;
            default:
                break;
        }

        return $result;
    }

    /**
     * Returns the numeric active version
     *
     * <code>
     * echo \Phalcon\Version::getId();
     * </code>
     *
     * @return int
     */
    public static function getId()
    {
        $version = self::_getVersion();

        $realMedium = sprintf('%02s', $version[1]);
        $realMinor = sprintf('%02s', $version[2]);
        return $version[0].$realMedium.$realMinor.$version[3].$version[4];
    }
}
