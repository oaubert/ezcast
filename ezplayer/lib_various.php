<?php
/*
 * EZCAST EZplayer
 *
 * Copyright (C) 2014 Université libre de Bruxelles
 *
 * Written by Michel Jansens <mjansens@ulb.ac.be>
 * 	      Arnaud Wijns <awijns@ulb.ac.be>
 *            Carlos Avidmadjessi
 * UI Design by Julien Di Pietrantonio
 *
 * This software is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or (at your option) any later version.
 *
 * This software is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this software; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
     
/*
 * Various useful functions
 */

require_once 'config.inc';
require_once 'lib_ezmam.php';
require_once dirname(__FILE__).'/../commons/lib_template.php';

/**
 * Trims the '-priv' or '-pub' suffix from an album name
 * TODO: check that it's only the end of the name that's removed
 * @param string $album_name
 * @return string 
 */
function suffix_remove($album_name) {
    $res = $album_name;

    if (substr($album_name, -4) == "-pub")
        $res = substr($album_name, 0, -4);
    else if (substr($album_name, -5) == '-priv')
        $res = substr($album_name, 0, -5);

    return $res;
}

/**
 * Changes "priv" to "pub" and controversely. If the album name has neither suffix, returns the same string.
 * @param type $album_name 
 * @return string
 */
function suffix_replace($album_name) {
    $res = $album_name;
    $res = suffix_remove($album_name);

    if (substr($album_name, -4) == "-pub")
        $res .= '-priv';
    else if (substr($album_name, -5) == '-priv')
        $res .= '-pub';

    return $res;
}

/**
 * Returns an album suffix based on its name
 * @param type $album_name
 * @return string|false the suffix (with the initial hyphen) if all went well, false otherwise 
 */
function suffix_get($album_name) {
    if (substr($album_name, -4) == "-pub")
        return '-pub';
    else if (substr($album_name, -5) == '-priv')
        return '-priv';
    else
        return false;
}

/**
 * Checks whether an album is private based on its suffix
 * @param type $album_name
 * @return bool true if album is private 
 */
function album_is_private($album_name) {
    return (suffix_get($album_name) == '-priv');
}

/**
 * Checks whether an album is public based on its suffix
 * @param type $album_name
 * @return bool true if album is public 
 */
function album_is_public($album_name) {
    return (suffix_get($album_name) == '-pub');
}

/**
 * Takes a ls-friendly date and translates it into human-readable
 * @param string $date The date in format YYYY_mm_dd_HHhii
 * @param string $space_char The delimiter to use between digits
 * @param bool $long_months_names(true) If set to "false", the month will be displayed as a number instead of a noun
 * @param string $lang Language the months are displayed in, in cast $long_months_names is set to true
 * @param bool $long_date if set to true, the date will be a "gramatically correct" date, instead of a "easily computable" one
 * @return string The date in format dd_mmmm_YYYY_HH:ii
 */
function get_user_friendly_date($date, $space_char = '_', $long_months_names = true, $lang = 'fr', $long_date = false) {
    if (!isset($date) || empty($date))
        return null;

    $matches = array();
    preg_match('!(\d{4})\_(\d{2})\_(\d{2})\_(\d{2})h(\d{2})!', $date, $matches);

    $new_date = $matches[3] . $space_char; // Day
    // If we want long month names (in letters, that is), we retrieve these names
    // from the translations file, and remove the non-ASCII characters if needed
    if ($long_months_names) {
        template_load_dictionnary('translations.xml');

        if ($lang == 'fr-ASCII')
            $new_date .= str_replace(array('é', 'û'), array('e', 'u'), template_get_message('month_' . $matches[2], 'fr'));
        else
            $new_date .= template_get_message('month_' . $matches[2], $lang);
    }
    // Otherwise, we just display the month as a number
    else
        $new_date .= $matches[2];

    $new_date .= $space_char . $matches[1]; // year
    if ($long_date)
        $new_date .= $space_char . $at; // Separator between date and hour

    $new_date .= $space_char . $matches[4] . 'h' . $matches[5]; // Hours and minutes

    return $new_date;
}

/**
 * Returns a date in RFC822 format from a date in "our" format
 * @param type $date Date in format YYYY_mm_dd_HHhii
 */
function get_RFC822_date($date) {
    //$date_array = date_parse_from_format('Y_m_d_H:i', $date);
    list($year, $month, $day, $hourandminutes) = explode('_', $date);
    list($hours, $minutes) = explode('h', $hourandminutes);

    //$date_array = date_parse('Y_m_d_H:i', $date);
    return date(DATE_RFC822, mktime($hours, $minutes, '0', $month, $day, $year));
}

/**
 * Takes a duration in seconds, and returns a string with the duration using international units
 * @param float $duration A duration in seconds
 * @return string A duration in hours, minutes, seconds
 */
function get_user_friendly_duration($duration) {
    if (!isset($duration) || empty($duration))
        return null;

    $res = round($duration);
    if ($res < 60)
        $res .= ' sec';
    else
        $res = round($res / 60) . ' min. ' . ($res % 60) . ' sec.';
    return $res;
}

/**
 * Takes a date as a year and month, and returns a string representing the academic year thereof
 * @param string $year
 * @param string $month 
 * @return A string of format currentYear-nextYear
 */
function get_anac($year, $month) {
    $year_start = (int) $year;
    // Before July 2011, the academic year is 2010-2011, so the starting year is "one year before" current date
    if ((int) $month <= 6)
        --$year_start;

    $year_end = $year_start + 1;

    return $year_start . '-' . $year_end;
}

/**
 * Returns the asset full title from an asset name
 * @global type $repository_path 
 * @param type $album 
 * @param type $asset the original asset name
 * @return boolean|string the asset full title if the asset exists ; false otherwise
 */
function get_asset_title($album, $asset) {
    global $repository_path;
    global $template_folder;

    ezmam_repository_path($repository_path);

    //
    // Usual sanity checks
    //
    if (!ezmam_album_exists($album)) {
        return false;
    }

    if (!ezmam_asset_exists($album, $asset)) {
        return template_get_message('Inexistant', get_lang());
    }
    $asset_title = ezmam_asset_metadata_get($album, $asset);
    $asset_title = $asset_title['title'];

    return $asset_title;
}

/**
 * Returns the album full title from an album mnemonic
 * @global type $repository_path
 * @param type $album the album mnemonique
 * @return boolean the album full title if the album exists; false otherwise
 */
function get_album_title($album) {
    global $repository_path;

    ezmam_repository_path($repository_path);
    //
    // Usual sanity checks
    //
    if (!ezmam_album_exists($album)) {
        return false;
    }

    $album_title = ezmam_album_metadata_get($album);
    $album_title = $album_title['description'];

    return $album_title;
}

/**
 * Returns a URL that allows the user to view the media
 * @global type $url
 * @param string $album
 * @param string $asset
 * @param string $media
 * @param bool $htmlentities If set to true, the result will be encoded with htmlentities (& replaced by &amp;)
 * @param bool $itunes_friendly If set to true, the link will include a fake .m4v file
 * @return string Media
 */
function get_link_to_media($album, $asset, $media, $htmlentities = true, $itunes_friendly = false) {
    global $ezplayer_url;
    global $distribute_url;
    global $repository_path;

    ezmam_repository_path($repository_path);

    //
    // Usual sanity checks
    //
    if (!ezmam_album_exists($album)) {
        error_print_message('get_link_to_media: Album ' . $album . ' does not exist');
        return false;
    }

    if (!ezmam_asset_exists($album, $asset)) {
        error_print_message('get_link_to_media: Asset ' . $asset . ' does not exist');
        return false;
    }

    // We take the asset's token if it exists.
    // If not, then we use the album's token instead.
    $token = ezmam_asset_token_get($album, $asset);
    if (!$token)
        $token = ezmam_album_token_get($album);

    if (!$token) {
        error_print_message('get_link_to_media: ' . ezmam_last_error());
        return false;
    }

    $media_infos = explode('_', $media); // 'media' is like high_cam, so we want to extract the "high" part (quality) and the "cam" part (type)
    $quality = $media_infos[0];
    $type = $media_infos[1];

    $resurl = $distribute_url;
    if ($itunes_friendly)
        $resurl.= '/' . $type . '.m4v';
    $resurl.= '?action=media&album=' . $album . '&asset=' . $asset . '&type=' . $type . '&quality=' . $quality . '&token=' . $token;
    if ($htmlentities)
        return htmlentities($resurl);
    else
        return $resurl;
}

/**
 * Returns a special code that contains information about the media
 * @global type $url
 * @param string $album
 * @param string $asset
 * @param string $media
 * @return string Media
 */
function get_code_to_media($album, $asset, $media) {
    global $ezplayer_url;
    global $distribute_url;
    global $repository_path;

    ezmam_repository_path($repository_path);

    //
    // Usual sanity checks
    //
    if (!ezmam_album_exists($album)) {
        error_print_message('get_link_to_media: Album ' . $album . ' does not exist');
        return false;
    }

    if (!ezmam_asset_exists($album, $asset)) {
        error_print_message('get_link_to_media: Asset ' . $asset . ' does not exist');
        return false;
    }

    // We take the asset's token if it exists.
    // If not, then we use the album's token instead.
    $token = ezmam_asset_token_get($album, $asset);
    if (!$token)
        $token = ezmam_album_token_get($album);

    if (!$token) {
        error_print_message('get_link_to_media: ' . ezmam_last_error());
        return false;
    }

    $media_infos = explode('_', $media); // 'media' is like high_cam, so we want to extract the "high" part (quality) and the "cam" part (type)
    $quality = $media_infos[0];
    $type = $media_infos[1];

    return $album . '/' . $asset . '/' . $type . '/' . $quality . '/' . $token;
}

/**
 * Checks if a string begins with some other string
 * @param string $string main string
 * @param string $beginning begins with
 * @return bool
 */
function str_begins_with($string, $beginning) {
    $beglen = strlen($beginning);
    $stringbeg = substr($string, 0, $beglen);

    if ($stingbeg == $beginning)
        return true;
    else
        return false;
}

/**
 * scans a filename and extract 'name' and 'ext'(ension) parts return them in an assoc array
 * @param <type> $filename
 * @return false|assoc_array
 */
function file_get_extension($filename) {
    //search last dot in filename
    $pos_dot = strrpos($filename, '.');
    if ($pos_dot === false)
        return array('name' => $filename, 'ext' => "");

    $ext_part = substr($filename, $pos_dot + 1);
    $name_part = substr($filename, 0, $pos_dot);
    $result_assoc['name'] = $name_part;
    $result_assoc['ext'] = $ext_part;
    return $result_assoc;
}

/**
 * Sets the current language to the one chosen in parameter
 * @param type $lang 
 */
//function set_lang($lang) {
//    $_SESSION['lang'] = $lang;
//}

/**
 * Returns current chosen language
 * @return string(fr|en) 
 */
//function get_lang() {
//    //if(isset($_SESSION['lang']) && in_array($_SESSION['lang'], $accepted_languages)) {
//    if (isset($_SESSION['lang']) && !empty($_SESSION['lang'])) {
//        return $_SESSION['lang'];
//    }
//    else
//        return 'en';
//}

/**
 * Isolates keywords from a string 
 * Each keyword must be surrounded by a '#' tag (i.e: #keyword#)
 * @param type $string
 * @return string
 */
function get_keywords(&$string) {
    $keywords = array();
    $string_length = strlen($string);
    // loop on the text
    for ($i = 0; $i < $string_length; $i++) {
        // if there is a keyword (starts by '#')
        if ($string[$i] == '#') {
            // saves the position of the pointer
            $j = $i + 1;
            $keyword = '';
            // saves the keyword
            while ($j <= $string_length && $string[$j] != "#" && $string[$j] != "@") {
                $keyword .= $string[$j];
                $j++;
            }
            // if it ends by '#', it is a keyword
            if ($string[$j] == "#") {
                // pushes the keyword in the array 
                $keywords[] = $keyword;
                // removes the '#' tags from the text
                $string[$i] = ' ';
                $string[$j] = ' ';
                // if it ends by '@' it is not a keyword but a link (we keep it)
            } else if ($string[$j] == "@") {
                while ($j <= $string_length && $string[$j] != " ") {
                    $j++;
                }
            }
            // moves the pointer at the end of the keyword (or link)
            $i = $j++;
        }
    }
    return $keywords;
}

function surround_url($string) {
    // checks for http url
    $pos = 0;
    // all prefixes we want to surround
    $patterns = array('http://', 'https://', 'www.', 'mailto:');

    while ($pos >= 0) {
        // finds the first occurence of each pattern
        $pos_array = array();
        foreach ($patterns as $pattern) {
            // searches from the last known position
            $tmp_pos = stripos($string, $pattern, $pos);
            if (!($tmp_pos === false)) // pattern found
                $pos_array[] = $tmp_pos;
        }
        // saves the position of the first encountered pattern
        $pos = (empty($pos_array)) ? -1 : min($pos_array);
        if ($pos != -1) { // ends if there is no pattern found 
            if ($pos == 0 || $string[$pos - 1] != '*') { // the url is not yet surrounded
                // adds a '*' tag before the url
                $string = substr($string, 0, $pos) . "*" . substr($string, $pos);
                // moves to the end of the url
                while ($pos < strlen($string) && $string[$pos] != ' ') {
                    $pos++;
                }
                // if the url ends with a '.', excludes it from the surrounding
                if ($string[$pos - 1] == '.')
                    $pos--;
                // adds a '*' at the end of the url
                $string = substr($string, 0, $pos) . "*" . substr($string, $pos);
                $pos++;
            } else { // the url is already surrounded, just move to the next '*' tag
                while ($pos < strlen($string) && $string[$pos] != '*') {
                    $pos++;
                }
            }
        }
    }

    return $string;
}

?>
