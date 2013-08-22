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
     * Sets up the page to host the Flowplayer plugin.
     */  
    function setup($page, $context) {
        $page->requires->jquery();
        $page->requires->js('/filter/streaming/lib/flowplayer.min.js');
    }

    /**
     * Filters a given block of text, adding streaming media players where appropriate.
     */
    function filter($text, array $options = array()) {
        global $CFG;

        //Replace each instance of a compatible video link with a JWPlayer instance.
        $pattern = '/<a.*?href="([^<]+\.(mp4|m4v|flv|f4v|mov|webm))(\?d=([\d]{1,4}%?)x([\d]{1,4}%?))?"[^>]*>.*?<\/a>/is';
        return preg_replace_callback($pattern, array($this, 'create_embedded_player_from_url'), $text);
    }


    /**
     * Creates an embedded FlowPlayer from the given parsed URL.
     */
    function create_embedded_player_from_url($link) 
    {
        global $CFG, $PAGE;

        //Ensure that tht JW player loader javascript has been included.

        //Increment the number of known players on the given page.
        self::$created_players++; 

        //Create a div with a unique ID for the streaming video; this prevents Moodle from caching it.
        $playerid = 'streaming_video_'.uniqid().self::$created_players;

        //Generate the video player itself.
        $content  = html_writer::start_tag('div', array('class' => 'videocontainer'));
        $content .= html_writer::tag('div', $link[0], array('class' => 'videolink'));
        $content .= html_writer::start_tag('div', array('class' => 'flowplayer'));
        $content .= html_writer::start_tag('video');
        $content .= html_writer::empty_tag('source', array('type' => 'video/mp4', 'src' => $link[1]));
        $content .= html_writer::end_tag('video');
        $content .= html_writer::end_tag('div');
        $content .= html_writer::end_tag('div');

        return $content;
    }
}

?>
