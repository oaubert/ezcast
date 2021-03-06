<!-- 
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
-->

<script>
    // variable describing which components are displayed on the page
    fullscreen = false; 
    show_panel = false;
    bookmark_form = false;
    shortcuts = false;
</script>
<?php
$share_time = $ezplayer_url . '/index.php?action=view_asset_bookmark'
        . '&album=' . $album
        . '&asset=' . $asset_meta['record_date']
        . '&t=';

// Displays a warning message if the brower is not fully supported
$playbackRate = false;
switch (strtolower($_SESSION['browser_name'])) {
    case 'safari' :
        if ($_SESSION['user_os'] != 'iOS')
            $playbackRate = true;
        break;
    case 'chrome' :
        if ($_SESSION['browser_version'] >= 4)
            $playbackRate = true;
        break;
    case 'ie' :
        if ($_SESSION['browser_version'] >= 9)
            $playbackRate = true;
        break;
    case 'firefox' :
        if ($_SESSION['browser_version'] >= 22)
            $playbackRate = true;
        break;
}
?> 


<div id="main_player">


    <!-- #player_header : contains album title and asset title 
        If the current view is the home page, the header is empty
        If the current view is the album page, the header contains album title only
        If the current view is the asset page, the header contains album title and asset title -->
    <div id="site_map">
        <a href="index.php" title="®Back_to_home®">®Home®</a>    
        <?php
        if (acl_has_album_permissions($album)) {
            $token = acl_token_get($album);
            $token = $token['token'];
            ?>
            <div class="right-arrow"></div>
            <a  href="javascript:show_album_assets('<?php echo $album; ?>', '<?php echo $token; ?>');" title="®Back_to_album®">(<?php echo suffix_remove($album); ?>) <?php echo get_album_title($album); ?></a>   
        <?php } ?>
        <div class="right-arrow"></div><?php print_info($asset_meta['title']); ?>
    </div>

    <div id="video_player">
        <!-- #main_video : HTML5 video player.
            There is no selected source by default.
            Video src is loaded when the user clicks on a link in the side pane.
            The video player is only displayed when an asset is selected. 
            If the current view is the home page or album page, the video player is
            replaced by a simple image.
        -->
        <div id="video_shortcuts">
            <div class="shortcuts">
                <ul>
                    <li><span class="key space"></span><span>Play / Pause</span></li>
                    <li><span class="key back-next"></span><span>Retour / Avance</span></li>
                    <li><span class="key speed"></span><span>Vitesse de lecture</span></li>
                    <li><span class="key volume"></span><span>Volume</span></li>
                    <li><span class="key m"></span><span>Muet</span></li>
                    <li><span class="key shift"></span><span>Afficher les signets</span></li>
                    <li><span class="key l"></span><span>Partager un lien</span></li>
                    <li><span class="key f"></span><span>Plein écran</span></li>
                    <li><span class="key n"></span><span>Nouveau signet</span></li>
                    <li><span class="key s"></span><span>Basculer cam / slide</span></li>
                    <li><span class="key r"></span><span>Raccourcis clavier</span></li>
                </ul>
            </div>
            <div class="shortcuts_tab"><a href="javascript:toggle_shortcuts();"></a></div>
        </div>
        
        <video id="main_video" poster="./images/Generale/poster.jpg" controls src="<?php echo $asset_meta['src']; ?>" type="video/mp4">
            <source id="main_video_source"
                    high_slide_src="<?php echo $asset_meta['high_slide_src'] . '&origin=' . $appname; ?>"
                    high_cam_src="<?php echo $asset_meta['high_cam_src'] . '&origin=' . $appname; ?>"
                    low_slide_src="<?php echo $asset_meta['low_slide_src'] . '&origin=' . $appname; ?>"
                    low_cam_src="<?php echo $asset_meta['low_cam_src'] . '&origin=' . $appname; ?>">  
        </video>

        <?php if ($asset_meta['record_type'] == 'camslide') { ?>

            <video id="secondary_video" poster="./images/Generale/poster.jpg" controls src="<?php echo $asset_meta['low_slide_src'] . '&origin=' . $appname; ?>" type="video/mp4">
            </video>
        <?php } ?>

        <div id="load_warn">®Load_for_switch®</div>


        <script>
            step = 0;
            time = 0;
            slide_loaded = false;
            cam_loaded = false;

            var videos = document.getElementsByTagName('video');
            for (var i = 0, max = videos.length; i < max; i++) {
                videos[i].addEventListener("timeupdate", function() {
                    if (step % 4 == 0) {
                        time = Math.round(this.currentTime);

<?php if ($_SESSION['browser_name'] != 'IE') { ?>
                            clippy.setText('<?php echo $share_time; ?>' + time + '&type=' + type);
<?php } ?>
                        document.getElementById('share_time_link').innerHTML = '<?php echo $share_time; ?>' + time + '&type=' + type;
                    }
                }, false);
            }
<?php
if (isset($asset_meta['record_type']) && $asset_meta['record_type'] == 'camslide' && ($_SESSION['user_os'] == 'iOS' || $_SESSION['user_os'] == 'Android')) {
    ?>
                document.getElementById('main_video').addEventListener('loadeddata', function() {
                    cam_loaded = true;
                    document.getElementById("load_warn").style.display = 'none';
                }, false);
                document.getElementById('secondary_video').addEventListener('loadeddata', function() {
                    slide_loaded = true;
                    document.getElementById("load_warn").style.display = 'none';
                }, false);
<?php } ?>
<?php
if ($_SESSION['ezplayer_mode'] == 'view_asset_bookmark') {
    if (isset($asset_meta['record_type']) && $asset_meta['record_type'] == 'camslide' && isset($_SESSION['loaded_type']) && $_SESSION['loaded_type'] == 'slide') {
        ?>
                    type = 'slide';
                    $('#main_video').hide();
                    $('#secondary_video').show();
                    $('.movie-button, .slide-button').toggleClass('active');
                    document.getElementById('secondary_video').addEventListener('loadeddata', function() {
                        this.currentTime = <?php echo $timecode; ?>;
                        this.play();
                    }, false);
    <?php } else { ?>
                    type = 'cam';
        <?php if ($asset_meta['record_type'] != 'camslide') { ?>
                        type = '<?php echo $asset_meta['record_type']; ?>';
        <?php } ?>
                    document.getElementById('main_video').addEventListener('loadeddata', function() {
                        this.currentTime = <?php echo $timecode; ?>;
                        this.play();
                    }, false);
    <?php } ?>
                var videos = document.getElementsByTagName('video');
                for (var i = 0, max = videos.length; i < max; i++) {
                    videos[i].addEventListener("seeked", function() {
                        document.getElementById('bookmark_timecode').value = Math.round(this.currentTime);
                    }, false);
                }
    <?php
}

if ($_SESSION['load_video'] == true) {
    ?>

                load_player('<?php
    if ($asset_meta['record_type'] == 'slide')
        echo 'low_slide';
    else
        echo 'low_cam';
    ?>');
<?php } ?>
        </script>
        <div class="form" id="bookmark_form">
            <div id='bookmark_form_wrapper'>
                <form action="index.php" method="post" id="submit_bookmark_form" onsubmit="return false">
                    <input type="hidden" name="album" id="bookmark_album" value="<?php echo $album; ?>"/>
                    <input type="hidden" name="asset" id="bookmark_asset" value="<?php echo $asset; ?>"/>
                    <!-- bookmark_type and bookmark_source are filled in in player.js (show_bookmark_form(...)) -->
                    <input type="hidden" name="type" id="bookmark_type" value=""/>
                    <input type="hidden" name="source" id="bookmark_source" value=""/><br/>

                    <br/>

                    <!-- Title field -->           
                    <label>®Title®&nbsp;:
                        <span class="small">®Title_info®</span>
                    </label>
                    <input name="title" tabindex='11' id="bookmark_title" type="text" maxlength="70"/>


                    <!-- keywords field -->
                    <label>®Keywords®&nbsp;:
                        <span class="small">®Keywords_info®</span>
                    </label>
                    <input name="keywords" tabindex='13' id="bookmark_keywords" type="text"/>

                    <!-- Description field -->
                    <label>®Description®&nbsp;:
                        <span class="small">®optional®</span>
                    </label>
                    <textarea name="description" tabindex='12' id="bookmark_description" rows="4" ></textarea>

                    <br/>

                    <!-- level field -->
                    <label>®Level®&nbsp;:
                        <span class="small">®Level_info®</span>
                    </label>
                    <input type="number" name="level" tabindex='14' id="bookmark_level" min="1" max="3" value="1"/>

                    <!-- Timecode field -->           
                    <label>®Timecode®&nbsp;:
                        <span class="small">®Timecode_info®</span>
                    </label>
                    <input name="timecode" tabindex='15' id="bookmark_timecode" type="text" value="0"/>

                    <br/><br/>
                    <!-- Submit button -->
                    <div class="cancelButton">
                        <a class="button" tabindex='16' href="javascript: hide_bookmark_form();">®Cancel®</a>
                    </div>
                    <div class="submitButton">
                        <a class="button blue" tabindex='17' href="javascript: if(check_bookmark_form()) submit_bookmark_form();">®Submit®</a>
                    </div>
                    <br />
                </form>
            </div>
        </div>
        <script>
            $('#bookmark_form input').keydown(function(e) {
                if (e.keyCode == 13) {
                    if(check_bookmark_form()) submit_bookmark_form();
                }
            });
        </script>
        <div class="video_controls">
            <ul>
                <?php if ($playbackRate) { ?>
                    <li>
                        <!--<a class="slow-button" title="®Rewind®" href="javascript:video_playbackspeed('down');"></a><!--
                        <div id="speedRate">x1.0</div><!--
                        <a class="fast-button" title="®Forward®" href="javascript:video_playbackspeed('up');"></a>
                        -->
                        <a id="toggleRate" href="javascript:toggle_playbackspeed();" title="®Change_speedrate®">1.0x</a>
                    </li>
                    <?php
                }
                if (isset($asset_meta['record_type']) && $asset_meta['record_type'] == 'camslide') {
                    ?>
                    <li>
                        <a class="movie-button active" title="®Watch_video®" href="javascript:switch_video('cam');"></a>
                        <a class="slide-button" title="®Watch_slide®" href="javascript:switch_video('slide');"></a>
                    </li>
                <?php } ?>
                <li>
                    <a class="high-button" title="®Watch_high®" href="javascript:toggle_video_quality('high');"></a>
                    <a class="low-button active" title="®Watch_low®" href="javascript:toggle_video_quality('low');"></a>
                </li>
                <?php if (acl_user_is_logged() && acl_has_album_permissions($album)) { ?>
                    <li>
                        <a class="add-bookmark-button" title="®Add_bookmark®" href="javascript:toggle_bookmark_form('custom');"></a>
                        <?php if (acl_user_is_logged() && acl_has_album_moderation($album)) { ?>
                            <a class="add-toc-button" title="®Add_toc®" href="javascript:toggle_bookmark_form('official');"></a>
                        <?php } ?>
                    </li>
                <?php } ?>                
                <li>
                    <a class="share-button" href="#" data-reveal-id="popup_share_time" title="®Share_time®" onclick="getElementById('main_video').pause();
                if (getElementById('secondary_video'))
                    getElementById('secondary_video').pause();"></a>
                </li>      
                <li>
                    <a class="fullscreen-button" href="javascript:video_fullscreen(!fullscreen);" title="®Toggle_fullscreen®" ></a>
                </li>   
                <li>
                    <a class="panel-button" href="javascript:toggle_panel();" title="®Display_tab®" ></a>
                </li>
            </ul>
        </div>
    </div> <!-- END VIDEO PLAYER -->
    <div class="asset_info">
        <div class="asset_title">
            <b><?php print_info(substr(get_user_friendly_date($asset_meta['record_date'], '/', false, get_lang(), false), 0, 10)); ?></b>
            <div class="right-arrow"></div>
            <?php print_info($asset_meta['title']); ?>
        </div>
        <div class="asset_author">{ <?php print_info($asset_meta['author']); ?> }</div>
        <div class="asset_details">
            <b class="green-title">®Description®:</b>
            <?php print_info($asset_meta['description']); ?>
        </div>
        <div>
            <?php if ($asset_meta['record_type'] == 'camslide' || $asset_meta['record_type'] == 'slide') { ?>
                <a class="button" href="#" data-reveal-id="popup_slide_link">®Download_slide®</a>
                <?php
            }
            if ($asset_meta['record_type'] == 'camslide' || $asset_meta['record_type'] == 'cam') {
                ?>
                <a class="button" href="#" data-reveal-id="popup_movie_link">®Download_movie®</a>
                <?php
            }
            if (acl_user_is_logged() && acl_has_album_moderation($album)) {
                ?>
                <a class="button" href="#" data-reveal-id="popup_asset_link">®Share_asset®</a>
            <?php } ?>
        </div>
    </div>
</div>