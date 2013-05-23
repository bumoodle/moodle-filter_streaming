<?php // $Id: filter.php,v 1.38.2.10 2009/05/07 08:55:50 nicolasconnault Exp $
//////////////////////////////////////////////////////////////
//  jwplayer plugin filtering
//
//  This filter will replace any links to an flv, f4v, mp4 or m4v file with
//  a jwplayer media plugin that plays the media inline. 
//
//  To activate this filter, drop it in your filter folder and visit your 
//  admin settings. activate the filter and move it up to the order you want it. 
//  
//  This is a small update to Andy Kemp's mp4filter and rename to reflect
//  the intention of using jwplayer as a replacement for the builtin flowplayer. 
//  Visit the plugins page and turn off filtering by mediaplugin for flv, f4v,
//  mp4 and m4v to prevent double embeds.
//    
//////////////////////////////////////////////////////////////

/// This is the filtering function itself.  It accepts the
/// courseid and the text to be filtered (in HTML form).

require_once($CFG->libdir.'/filelib.php');

class filter_streaming extends moodle_text_filter {

    public static $created_players = 0;

    /**
     * Filters a given block of text, adding streaming media players where appropriate.
     */
    function filter($text, array $options = array()) {
        global $CFG;

        //Replace each instance of a compatible video link with a JWPlayer instance.
        $pattern = '/<a.*?href="([^<]+\.(mp4|m4v|flv|f4v|mov|webm))(\?d=([\d]{1,4}%?)x([\d]{1,4}%?))?"[^>]*>.*?<\/a>/is';
        return preg_replace_callback($pattern, array($this, 'create_embedded_player_from_url'), $text);
    }

    function html5_video_playback($url, $width=800, $height=600) {
       return '<video width="'.$width.'" height="'.$height.'" src="'.$url.'" controls />';
    }

    function determine_max_width_and_height($link) {

        //Determine if we have a mobile device as our viewer.
        $device_type = get_device_type();
        $is_mobile = ($device_type == 'mobile' || $device_type == 'tablet');

        //If we do, use a smaller default size.
        $default_width = $is_mobile ? '640' : '800';
        $default_height = $is_mobile ? '480' : '600';

        //If no width and height were provided, use the defaults.
        $width  = empty($link[3]) ? '800' : $link[3];
        $height = empty($link[4]) ? '600' : $link[4];

        return array($width, $height);

    }


    function create_embedded_player_from_url($link) 
    {
        global $CFG, $PAGE;

        //Ensure that tht JW player loader javascript has been included.
        $PAGE->requires->js('/filter/streaming/lib/jwplayer.js', true);

        //Increment the number of known players on the given page.
        self::$created_players++; 

        //Create a div with a unique ID for the streaming video; this prevents Moodle from caching it.
        $playerid = 'streaming_video_'.uniqid().self::$created_players;

        //Generate the video player itself.
        $content  = html_writer::start_tag('div', array('class' => 'videocontainer'));
        $content .= html_writer::tag('div', $link[0], array('class' => 'videolink'));
        $content .= html_writer::tag('div', get_string('loading', 'filter_streaming'), array('class' => 'streamingvideo', 'id' => $playerid));
        $content .= html_writer::end_tag('div');

        //Parse the link to determine the player's configuration. 
        $url = addslashes_js($link[1]);
        list($width, $height) = $this->determine_max_width_and_height($link);

        //Initialize the JW Player.
        $PAGE->requires->js_init_code('
            jwplayer("'.$playerid.'").setup({
            file: "'.$url.'",
            width: '.$width.',
            height: '.$height.',
            startparam: "start"
        })', true);

        return $content;
    }
}

?>
