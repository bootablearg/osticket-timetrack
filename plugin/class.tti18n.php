<?php
/*********************************************************************
    class.tti18n.php

    Minimal translation layer for the Time Tracking plugin.

    osTicket's own plugin i18n expects a gettext .mo file compiled into a
    .mo.php hash, which means anyone contributing a translation needs the
    gettext toolchain. This uses plain PHP arrays instead: to add a
    language, copy i18n/es_AR.php, rename it to your locale and translate
    the values. Nothing to compile.

    Time Tracking plugin for osTicket
    Released under the GNU General Public License version 2
**********************************************************************/

class TimeTrackI18n {

    static $strings = null;
    static $locale = null;

    /**
     * Current locale, agent's language first, system language second.
     */
    static function getLocale() {
        if (isset(static::$locale))
            return static::$locale;

        $lang = null;
        if (class_exists('Internationalization')
                && method_exists('Internationalization', 'getCurrentLanguage'))
            $lang = Internationalization::getCurrentLanguage();

        if (!$lang) {
            global $cfg;
            $lang = ($cfg && method_exists($cfg, 'getSystemLanguage'))
                ? $cfg->getSystemLanguage() : 'en_US';
        }

        return static::$locale = ($lang ?: 'en_US');
    }

    static function load() {
        if (isset(static::$strings))
            return;

        static::$strings = array();
        $locale = static::getLocale();

        // es_AR falls back to es, pt_BR to pt, and so on
        $candidates = array($locale);
        if (strpos($locale, '_') !== false)
            $candidates[] = substr($locale, 0, strpos($locale, '_'));

        foreach ($candidates as $c) {
            $file = dirname(__FILE__) . '/i18n/' . basename($c) . '.php';
            if (file_exists($file)) {
                $strings = include $file;
                if (is_array($strings))
                    static::$strings = $strings;
                break;
            }
        }
    }

    /** Translate a single string */
    static function get($msgid) {
        static::load();
        return isset(static::$strings[$msgid]) ? static::$strings[$msgid] : $msgid;
    }

    /** Translate with plural forms */
    static function plural($singular, $plural, $n) {
        static::load();
        $key = $n == 1 ? $singular : $plural;
        return isset(static::$strings[$key]) ? static::$strings[$key] : $key;
    }
}

/**
 * Shorthand used across the plugin. Deliberately NOT named __() so it
 * never collides with the core's own translation domain.
 */
function tt__($msgid) {
    return TimeTrackI18n::get($msgid);
}

function tt_n($singular, $plural, $n) {
    return TimeTrackI18n::plural($singular, $plural, $n);
}
?>
