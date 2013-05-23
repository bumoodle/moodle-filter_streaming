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

class filter_jwplayerfilter extends moodle_text_filter {

    function filter($text, array $options = array()) {
        global $CFG;

        if (!is_string($text)) {
            // non string data can not be filtered anyway
            return $text;
        }

        //Filter for M4V
        $search = '/<a.*?href="([^<]+\.m4v)(\?d=([\d]{1,4}%?)x([\d]{1,4}%?))?"[^>]*>.*?<\/a>/is';
        $text = preg_replace_callback($search, 'mediaplugin_filter_mp4_callback', $text);

        //Filter for MP4
        $search = '/<a.*?href="([^<]+\.mp4)(\?d=([\d]{1,4}%?)x([\d]{1,4}%?))?"[^>]*>.*?<\/a>/is';
        $text = preg_replace_callback($search, array($this, 'modify_link'), $text);
        
        //Filter for FLV
        $search = '/<a.*?href="([^<]+\.flv)(\?d=([\d]{1,4}%?)x([\d]{1,4}%?))?"[^>]*>.*?<\/a>/is';
        $text = preg_replace_callback($search, 'mediaplugin_filter_mp4_callback', $text);
        
        //Filter for F4V
        $search = '/<a.*?href="([^<]+\.f4v)(\?d=([\d]{1,4}%?)x([\d]{1,4}%?))?"[^>]*>.*?<\/a>/is';
        $text = preg_replace_callback($search, 'mediaplugin_filter_mp4_callback', $text);
        
        return $text;
    }

    /*
    function filter_link_startswith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }
    */


    function html5_video_playback($url, $width=800, $height=600) {
       return '<video width="'.$width.'" height="'.$height.'" src="'.$url.'" controls />';
    }

    ///===========================
    /// callback filter functions

    function modify_link($link) 
    {
        global $CFG, $PAGE;

        static $count = 0;
        $count++;
        $id = 'filter_mp4_'.uniqid().$count; //we need something unique because it might be stored in text cache

        $videomod = optional_param('modifier', '', PARAM_RAW);
         
        $url = addslashes_js($link[1]);

        //If we have a mobile or a tablet device, embed HTML5 video instead of flash.
        $device_type = get_device_type();
        if($device_type == 'mobile' || $device_type == 'tablet') {
            
            //Pick a sane width/height for most tablets.
            $width  = empty($link[3]) ? '640' : $link[3];
            $height = empty($link[4]) ? '480' : $link[4];


            return html5_video_playback($url, $width, $height );
        }

        $width  = empty($link[3]) ? '800' : $link[3];
        $height = empty($link[4]) ? '600' : $link[4];


        //FIXME change the swfobj to a requires
        $return_val =  '<div style="text-align: center">'.$link[0].'</div><div style="text-align:center">'.
    '<span class="mediaplugin mediaplugin_mp4" id="'.$id.'">Just a moment while we try to load the video...</span>
    <script type="text/javascript" src="'.$CFG->wwwroot.'/filter/jwplayerfilter/swfobject.js"></script> 
    <script type="text/javascript">

    var s1 = new SWFObject("'.$CFG->wwwroot.'/filter/jwplayerfilter/player.swf","player","'.$width.'","'.$height.'","9.0.115");
    s1.addParam("allowfullscreen","true");
    s1.addParam("allowscriptaccess","always");
    s1.addVariable("skin", "'.$CFG->wwwroot.'/filter/jwplayerfilter/skin/lulu/lulu.xml");
    s1.addVariable("start","1");
    s1.addVariable("file", "'.$url.'");
    s1.addVariable("controlbar","over");
    s1.addVariable("provider", "http");
    s1.addVariable("http.startparam","start");

    //hack for remote control
    var player;
    function playerReady(newPlayer)
    {
        player = window.document[newPlayer.id];
        addListeners();
    }

    ';

        $return_val .= 's1.write("'.$id.'");  </script></div>';

        //start the videomods div
        $return_val .= html_writer::start_tag('div', array('class' => 'videomodifiers'));

      
        //FIXME: add JS for in-place load (progressive enhancement)

        $return_val .= html_writer::end_tag('div');

        return $return_val;


    }
}

?>
