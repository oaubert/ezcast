<?php

/*
 * EZCAST EZmanager 
 *
 * Copyright (C) 2014 Université libre de Bruxelles
 *
 * Written by Michel Jansens <mjansens@ulb.ac.be>
 * 	   Arnaud Wijns <awijns@ulb.ac.be>
 *         Antoine Dewilde
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
 * This file is meant to provide media and RSS feeds to students
 * @param action rss|media
 */

require_once 'config.inc';
require_once 'lib_ezmam.php';
require_once 'lib_error.php';
require_once '../commons/lib_template.php';
require_once 'lib_various.php';
require_once 'external_products/rangeDownload.php';

//
// Inits
//
ezmam_repository_path($repository_path);
ezmam_video_distribution_url($ezmanager_url . 'distribute.php');
//template_load_dictionnary('translations.xml');
$input = array_merge($_GET, $_POST);

//
// Page logic goes here
//
switch ($input['action']) {
    // The user wants to grab the RSS feed
    case 'rss':
        if (!isset($input['album']) || !isset($input['quality']) || !isset($input['token'])) {
            echo 'Usage: view.php?action=rss&amp;album=ALBUM&amp;quality=QUALITY&amp;token=TOKEN';
            die;
        }
        view_rss();
        break;

    // The user wants to download a media
    case 'media':
        if (!isset($input['type']) || !isset($input['album']) || !isset($input['asset']) || !isset($input['token'])) {
            echo 'Usage: view.php?action=media&amp;type=TYPE&amp;album=ALBUM&amp;asset=ASSET&amp;token=TOKEN';
            die;
        }
        view_media();
        break;

    // The user wants to view a media in streaming show him a still picture first
    case 'embed_link':
        view_embed_link();
        break;
    // The user wants to view a media in streaming
    case 'embed':
        view_embed();
        break;

    case 'player':
        view_player();
        break;
}

//
// Functions
//

function view_rss() {
    global $input;

    // 0) Sanity checks
    if (!ezmam_album_exists($input['album'])) {
        error_print_http(404);
        log_append('warning', 'view_rss: tried to access non-existant album ' . $input['album']);
        exit;
    }

    if (!ezmam_album_token_check($input['album'], $input['token'])) {
        error_print_http(403);
        log_append('warning', 'view_rss: tried to acces album ' . $input['album'] . ' with invalid token ' . $input['token']);
        die;
    }

    if (!accepted_quality($input['quality'])) {
        error_print_http(403);
        log_append('warning', 'view_rss: tried to access album ' . $input['album'] . 'in forbidden quality "' . $input['quality'] . '"');
        die;
    }

    // 1) Retrieving the feed path
    $feed_handle = ezmam_rss_getpath($input['album'], $input['quality'], false);

    // 2) Providing feed content to client
    header('Content-Type: text/xml');
    readfile($feed_handle);
}

function view_player() {
    global $input;
    global $template_folder;
    global $appname;

    // 0) Sanity checks
    if (!ezmam_album_exists($input['album'])) {
        error_print_http(404);
        log_append('warning', 'view_player: tried to access non-existant album ' . $input['album']);
        exit;
    }

    if (!ezmam_album_token_check($input['album'], $input['token'])) {
        error_print_http(403);
        log_append('warning', 'view_player: tried to acces album ' . $input['album'] . ' with invalid token ' . $input['token']);
        die;
    }

    // 1) Retrieving all assets' metadata
    $asset_list = ezmam_asset_list_metadata($input['album']);

    // 2) Add links to each asset
    foreach ($asset_list as &$asset) {
        $high_cam_link = '';
        $low_cam_link = '';
        $high_slide_link = '';
        $low_slide_link = '';

        if ($asset['metadata']['record_type'] == 'camslide' || $asset['metadata']['record_type'] == 'cam') {
            $high_cam_link = get_link_to_media($input['album'], $asset['name'], 'high_cam') . "&origin=" . $appname ;
            $low_cam_link = get_link_to_media($input['album'], $asset['name'], 'low_cam') . "&origin=" . $appname ;
        }

        if ($asset['metadata']['record_type'] == 'camslide' || $asset['metadata']['record_type'] == 'slide') {
            $high_slide_link = get_link_to_media($input['album'], $asset['name'], 'high_slide') . "&origin=" . $appname;
            $low_slide_link = get_link_to_media($input['album'], $asset['name'], 'low_slide') . "&origin=" . $appname;
        }

        $asset['links'] = array(
            'high_cam' => $high_cam_link,
            'low_cam' => $low_cam_link,
            'high_slide' => $high_slide_link,
            'low_slide' => $low_slide_link);
    }
    template_repository_path($template_folder . get_lang());
    require_once template_getpath('player_header.php');
    require_once template_getpath('player_content.php');
    require_once template_getpath('player_footer.php');
}

function view_media() {
    global $accepted_media_qualities;
    global $accepted_media_types;
    global $input;

    // 0) Sanity checks
    if (!ezmam_album_exists($input['album'])) {
        error_print_http(404);
        log_append('warning', 'view_media: tried to access non-existant album ' . $input['album']);
        die;
    }

    if (!ezmam_asset_exists($input['album'], $input['asset'])) {
        error_print_http(404);
        log_append('warning', 'view_media: tried to access non-existant asset ' . $input['asset'] . ' from album ' . $input['album']);
        die;
    }

    if (!ezmam_album_token_check($input['album'], $input['token']) && !ezmam_asset_token_check($input['album'], $input['asset'], $input['token'])) {
        error_print_http(404);
        log_append('warning', 'view_media: tried to access asset ' . $input['asset'] . ' from album ' . $input['album'] . ' with invalid token ' . $input['token']);
        die;
    }

    if (!isset($input['quality']))
        $input['quality'] = 'high';
    if (!accepted_quality($input['quality'])) {
        error_print_http(403);
        log_append('warning', 'view_media: tried to access forbidden quality "' . $input['quality'] . '"');
        die;
    }
    if (!accepted_type($input['type'])) {
        error_print_http(403);
        log_append('warning', 'view_media: tried to access forbidden media type "' . $input['type'] . '"');
        die;
    }

    // 1) First we retrieve the media path
    $quality = strtolower($input['quality']);
    $type = strtolower($input['type']);
    $media_name = $quality . '_' . $type;

    $media_handle = ezmam_media_getpath($input['album'], $input['asset'], $media_name, false);

    // If we couldn't find our file, we check whether it exists in another quality
    if (!$media_handle) {
        if ($quality == 'low')
            $quality = 'high';
        else if ($quality == 'high')
            $quality = 'low';
        $media_name = $quality . '_' . $type;

        $media_handle = ezmam_media_getpath($input['album'], $input['asset'], $media_name, false);

        // If we still can't find a file, we just tell the users so
        if (!$media_handle) {
            error_print_http(404);
            log_append('view_media: couldn\'t find the media file for asset ' . $input['asset'] . ' of album ' . $input['album']);
            die;
        }
    }

    // 2) Then we save some statistics on it
    /*if (!isset($input['origin']) || 
            ($input['origin'] != 'podman' && $input['origin'] != 'ezplayerdev')){*/
        ezmam_media_viewcount_increment($input['album'], $input['asset'], $media_name, $input['origin']);
        
   // }

    // 3) And finally, we deliver it!
    $filename = suffix_remove($input['album']);
    $filename .= '_-_';
    $filename .= get_user_friendly_date($input['asset'], '_', true, 'fr-ASCII');
    //add a quality part in filename
    if ($quality == 'low')
        $quality_fn_part = 'SQ';
    else
        $quality_fn_part = 'HQ';
    $filename .= '_' . $quality_fn_part;
    //add a type video/slide part in filename
    if ($type == 'cam')
        $type_fn_part = 'video';
    else
        $type_fn_part = 'slide';
    $filename .= '_' . $type_fn_part;

  //  header('Content-Type: video/x-m4v');
    header('Content-Type: video/mp4');
    if (isset($_SERVER['HTTP_RANGE'])) {
        rangeDownload($media_handle);
    } else {
        header('Content-Disposition: attachment; filename=' . $filename . '.m4v');
        //header('Content-Transfer-Encoding: binary');
        //header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Length: ' . filesize($media_handle));
        header('Accept-Ranges: bytes');
        //readfile($media_handle);
        //fpassthru($fh);
        passthru('/bin/cat ' . escapeshellarg($media_handle));
        fclose($fh);
    }
}

/**
 * Displays the flash player
 * @global type $input 
 * @global type $distribute_url
 */
function view_embed_link() {
    global $input;
    global $distribute_url;
    global $ezmanager_url;
    global $template_folder;
    // Sanity checks
    if (!isset($input['album']) || !isset($input['asset']) || !isset($input['quality']) || !isset($input['type']) || !isset($input['token'])) {
        echo "Usage: distribute.php?action=embed&amp;album=ALBUM&amp;asset=ASSET&amp;type=TYPE&amp;quality=QUALITY&amp;token=TOKEN<br/>";
        echo "Optional parameters: width: Video width in pixels. height: video height in pixels. iframe: set to true if you want the return code to be an iframe instead of a full HTML page";
        die;
    }
    $lang = $input['lang'];
    $imgsrc = $ezmanager_url . "/images/embed_link_$lang.png";
    $width = $input['width'];
    $href = $distribute_url . '?action=embed&album=' . $input['album'] . '&asset=' . $input['asset'] . '&type=' . $input['type'] . '&quality=' . $input['quality'] . '&token=' . $input['token'] . '&width=' . $input['width'] . '&height=' . $input['height'] . '&lang=' . $lang;
    template_repository_path($template_folder . 'fr');
    require_once template_getpath('embed_header.php');
    require_once template_getpath('embed_link.php');
    require_once template_getpath('embed_footer.php');
}

/**
 * Displays the flash player
 * @global type $input 
 */
function view_embed() {
    global $input;
    global $repository_path;
    global $flash_only_browsers;
    global $template_folder;
    global $ezmanager_url;

    // Sanity checks
    if (!isset($input['album']) || !isset($input['asset']) || !isset($input['quality']) || !isset($input['type']) || !isset($input['token'])) {
        echo "Usage: distribute.php?action=embed&amp;album=ALBUM&amp;asset=ASSET&amp;type=TYPE&amp;quality=QUALITY&amp;token=TOKEN<br/>";
        echo "Optional parameters: width: Video width in pixels. height: video height in pixels. iframe: set to true if you want the return code to be an iframe instead of a full HTML page";
        die;
    }

    if (!ezmam_album_exists($input['album'])) {
        error_print_http(404);
        log_append('warning', 'view_embed: tried to access non-existant album ' . $input['album']);
        die;
    }

    if (!ezmam_asset_exists($input['album'], $input['asset'])) {
        error_print_http(404);
        log_append('warning', 'tried to access non-existant asset ' . $input['asset'] . ' of album ' . $input['album']);
        die;
    }

    if (!ezmam_album_token_check($input['album'], $input['token']) && !ezmam_asset_token_check($input['album'], $input['asset'], $input['token'])) {
        error_print_http(403);
        log_append('warning', 'view_media: tried to access asset ' . $input['asset'] . ' from album ' . $input['album'] . ' with invalid token ' . $input['token']);
        die;
    }

    // Then we retrieve the useful information, i.e. the media path and the dimensions
    // Fallback: if the media doesn't exist in the requested quality,
    // we try to find it in another one available
    $media_name = $input['quality'] . '_' . $input['type'];
    if (!ezmam_media_exists($input['album'], $input['asset'], $media_name)) {
        if ($input['quality'] == 'high')
            $media_name = 'low_' . $input['type'];
        else if ($input['quality'] == 'low')
            $media_name = 'high_' . $input['type'];

        // If no quality is available, we tell that to the user.
        if (!ezmam_media_exists($input['album'], $input['asset'], $media_name)) {
            error_print_http(404);
            die;
        }
    }

    $metadata = ezmam_media_metadata_get($input['album'], $input['asset'], $media_name);

    $width = $metadata['width'];
    if (isset($input['width']) && !empty($input['width']))
        $width = $input['width'] - 5;

    $height = $metadata['height'];
    if (isset($input['height']) && !empty($input['height']))
        $height = $input['height'] - 5;

    $origin = ($input['origin'] == 'ezmanager') ? 'ezmanager' : 'embed';

    $media_url = urlencode(ezmam_media_geturl($input['album'], $input['asset'], $media_name) . '&origin=' . $origin);
    $player_url = $ezmanager_url . '/swf/bugatti.swf';

    // And finally we display the player through a template!
    // If the user wanted to have the player in an iframe, we must change the code a little bit
    if (isset($input['iframe']) && $input['iframe'] == 'true') {
    $origin = ($input['origin'] == 'ezmanager') ? 'ezmanager' : 'embed';
        echo '<iframe style="padding: 0; z-index: 100;" frameborder="0" scrolling="no" src="distribute.php?action=embed&amp;album=' . $input['album'] . '&amp;asset=' . $input['asset'] . '&amp;type=' . $input['type'] . '&amp;quality=' . $input['quality'] . '&amp;token=' . $input['token'] . '&amp;width=' . $width . '&amp;height=' . $height . '&amp;origin=' . $origin . '" width="' . $width . '" height="' . $height . '"></iframe>';
    } else {
        template_repository_path($template_folder . 'en');
        require_once template_getpath('embed_header.php');

        // We check if the user's browser is a flash-only browser or if it accepts HTML5
        // It's a Flash browser IIF
        // UA includes 'Firefox' OR UA includes 'MSIE' BUT UA does not include 'MSIE 9.'
        // TODO: prepare for future revisions of MSIE
        if ((strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== false) || ((strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.') !== false)) || ((strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7.') !== false)) || ((strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.') !== false))) {
            require_once template_getpath('embed_flash.php');
            require_once template_getpath('embed_footer.php');
            die;
        }

        // Otherwise, if it accepts HTML5, we display the HTML5 browser
        require_once template_getpath('embed_html5.php');
        require_once template_getpath('embed_footer.php');
    }
}

//
// Helper functions
//

function accepted_quality($quality) {
    global $accepted_media_qualities;
    return in_array($quality, $accepted_media_qualities);
}

function accepted_type($type) {
    global $accepted_media_types;
    return in_array($type, $accepted_media_types);
}

?>
