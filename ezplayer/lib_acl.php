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
     
/**
 * This file contains all methods related to access control.
 * NOTE: This library needs session vars, so please call session_start() first
 */
require_once 'config.inc';
require_once 'lib_ezmam.php';
require_once 'lib_user_prefs.php';
require_once '../commons/lib_courses.php';

/**
 * First method to call when using the lib_acl
 * @param string $netid the user's netID
 * @return bool error status
 */
function acl_init($netid) {
    // initializing ezmam (we'll need it later)
    global $repository_path;
    global $user_files_path;

    ezmam_repository_path($repository_path);
    user_prefs_repository_path($user_files_path);

    // Retrieving the permissions
    acl_update_permissions_list();
    acl_update_settings();

    // All is set, we're good to go
    return true;
}

/**
 * Upon calling this function, the user will no longer be identified, and hence won't have access to any of the other ACL functions
 */
function acl_exit() {
    unset($_SESSION['acl_consulted_albums']); // Array of all consulted albums, by album name
    unset($_SESSION['acl_moderated_albums']);
    unset($_SESSION['acl_watched_assets']);
    unset($_SESSION['acl_album_tokens']);
}

/**
 * Updates the (locally stored) list of consulted albums and moderated albums 
 */
function acl_update_permissions_list() {
    global $repository_path;

    ezmam_repository_path($repository_path);
    $courses_list_for_author = array();
    $consulted_albums = array();
    $watched_assets = array();

    if (acl_user_is_logged()) {
        $courses_list_for_author = courses_list($_SESSION['user_login']);
        foreach ($courses_list_for_author as $key => $title){
            if (!ezmam_album_exists("$key-pub")) unset ($courses_list_for_author[$key]);
        }
        $album_tokens_list = user_prefs_tokens_get($_SESSION['user_login']);
        foreach ($album_tokens_list as $album_token) {
            $consulted_albums[] = $album_token['album'];
            $global_count[$album_token['album']] = ezmam_asset_count($album_token['album']);
        }
        $watched_assets = user_prefs_watchedlist_get($_SESSION['user_login']);
        $_SESSION['acl_album_tokens'] = $album_tokens_list;
        $_SESSION['acl_global_count'] = $global_count;
    } else {
        // anonymous user : every consulted album is directly stored in $_SESSION['acl_album_tokens']
        // tokens stored during action "view_album_assets" in web_index.php
        foreach ($_SESSION['acl_album_tokens'] as $album_token) {
            $consulted_albums[] = $album_token['album'];
        }
    }

    $_SESSION['acl_consulted_albums'] = $consulted_albums;
    $_SESSION['acl_moderated_albums'] = $courses_list_for_author;
    $_SESSION['acl_watched_assets'] = $watched_assets;
}

function acl_update_settings(){
    if (acl_user_is_logged()){
        $_SESSION['acl_user_settings'] = user_prefs_settings_get($_SESSION['user_login']);
    }
}

/**
 * Determines if a video has been watched or not.
 * @param type $album
 * @param type $asset
 * @return boolean
 */
function acl_is_watched($album, $asset) {
    if (!acl_user_is_logged())
        return true;
    foreach ($_SESSION['acl_watched_assets'] as $watched) {
        if ($watched['album'] == $album
                && in_array($asset, $watched['assets'])) {
            return true;
        }
    }
    return false;
}

function acl_watched_count($album) {
        
    if (!acl_user_is_logged()) {
        error_print_message('Error: acl_has_album_permissions: You are not logged in');
        return 0;
    }
    $count = 0;
    foreach ($_SESSION['acl_watched_assets'] as $watched) {
        if ($watched['album'] == $album){
            $album_assets = ezmam_asset_list($album);
            foreach($watched['assets'] as $asset){
                if (in_array($asset, $album_assets)){
                    ++$count;
                }
            }            
        }
    }
    return $count;
}

function acl_global_count($album){
    return $_SESSION['acl_global_count'][$album];
}

function acl_value_get($setting){
    $settings = $_SESSION["acl_user_settings"];
    return $settings[$setting];
}

/**
 * Checks whether a user has access to an album
 * @param string $album Name of the album we want to access
 * @return bool true if user can access $album, false otherwise
 */
function acl_has_album_permissions($album) {
    if (!isset($_SESSION['acl_consulted_albums'])) return false;

    return (in_array($album, $_SESSION['acl_consulted_albums']));
}

/**
 * Checks whether a user has moderation rights on an album
 * @param string $album Name of the album we want to access
 * @return bool true if user can moderate $album, false otherwise
 */
function acl_has_album_moderation($album) {
    if (!acl_user_is_logged()) {
        error_print_message('Error: acl_has_album_permissions: You are not logged in');
        return false;
    }
    $album = suffix_remove($album);
    $moderated_albums = array_keys($_SESSION['acl_moderated_albums']);

    return (in_array($album, $moderated_albums));
}

/**
 * Returns the list of albums that can be consulted by the user
 * @return authorized albums list
 */
function acl_authorized_albums_list() {

    return (isset($_SESSION['acl_consulted_albums'])) ? $_SESSION['acl_consulted_albums'] : array() ;
}

function acl_authorized_album_tokens_list() {

    return (isset($_SESSION['acl_album_tokens'])) ? $_SESSION['acl_album_tokens'] : array() ;
}

function acl_token_get($album){
        // Get the list that contains all album tokens
    $token_list = $_SESSION['acl_album_tokens'];

    // if no result, the user and/or album are not correct
    if (!isset($token_list) || $token_list == false)
        return false;

    foreach ($token_list as $album_token) {
        if ($album_token['album'] == $album) {
            return $album_token;
        }
    }

    // no match found
    return false;
}

/**
 * Returns the list of albums that can be moderated by the user
 * @return the list of albums the user can moderate
 */
function acl_moderated_albums_list() {
    if (!acl_user_is_logged()) {
        //    error_print_message('Error: acl_authorized_albums_list: You are not logged in');
        //    return false;
        return array();
    }

    return $_SESSION['acl_moderated_albums'];
}

/**
 * Checks if the user is logged
 * @return type true if the user is logged; false otherwise
 */
function acl_user_is_logged() {
    return (isset($_SESSION['ezplayer_logged']) && !empty($_SESSION['ezplayer_logged']));
}

?>
