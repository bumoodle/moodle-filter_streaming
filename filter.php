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


function jwplayerfilter_filter($courseid, $text) {
    global $CFG;

    if (!is_string($text)) {
        // non string data can not be filtered anyway
        return $text;
    }
    $newtext = $text; // fullclone is slow and not needed here

//Filter for M4V
        $search = '/<a.*?href="([^<]+\.m4v)(\?d=([\d]{1,4}%?)x([\d]{1,4}%?))?"[^>]*>.*?<\/a>/is';
        $newtext = preg_replace_callback($search, 'mediaplugin_filter_mp4_callback', $newtext);

//Filter for MP4
        $search = '/<a.*?href="([^<]+\.mp4)(\?d=([\d]{1,4}%?)x([\d]{1,4}%?))?"[^>]*>.*?<\/a>/is';
        $newtext = preg_replace_callback($search, 'mediaplugin_filter_mp4_callback', $newtext);
		
//Filter for FLV
        $search = '/<a.*?href="([^<]+\.flv)(\?d=([\d]{1,4}%?)x([\d]{1,4}%?))?"[^>]*>.*?<\/a>/is';
        $newtext = preg_replace_callback($search, 'mediaplugin_filter_mp4_callback', $newtext);
		
//Filter for F4V
        $search = '/<a.*?href="([^<]+\.f4v)(\?d=([\d]{1,4}%?)x([\d]{1,4}%?))?"[^>]*>.*?<\/a>/is';
        $newtext = preg_replace_callback($search, 'mediaplugin_filter_mp4_callback', $newtext);
	
    return $newtext;
}


function filter_link_startswith($haystack, $needle)
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function existing_modifiers($link)
{
   //TODO: more modifiers if necessary?
   $valid_modifiers = array('_veryslow' => .50, '_slow' => .70, '' => 1, '_fast' => 1.3, '_veryfast' => 1.5);

   //initially, assume no modifiers exist
   $existing = array();

    //for each of the modifier types, check to see if the item exists
    foreach($valid_modifiers as $mod => $speed)
    {
        //get the link to the modified video
        $new_link = modifier_link($link, $mod);

        //if we weren't able to figure out a modifier link, fail
        if($new_link === false)
            return array();

        //if the local link exists, add it to our array
        if($mod == '' || local_link_exists($new_link))
            $existing[$mod] = array('url' => $new_link, 'speed' => $speed);
    }

    //return the list of existing links
    return $existing;
}

function modifier_link($link, $modname)
{
        //find the location of MP4 in the string
        $mp4pos = strrpos($link, '.mp4');

        //compute the new link
        $new_link = substr($link, 0, $mp4pos) . $modname . substr($link, $mp4pos);

        //return the new link
        return $new_link;

}

function modifier_exists($link, $modname)
{
        //return true iff the local link exists
        return local_link_exists(modifier_link($link, $modname));
}

/**
 * Returns true iff the given link exists after several replacement rules.
 */
function local_link_exists($link)
{
    //TODO: abstract to setting?
    $server_alias = array('/var/www/vstream/' => '|^'.preg_quote('http://video.bumoodle.com/').'|');

    //apply each server replacement rule
    foreach($server_alias as $path => $url)
       $link = preg_replace($url, $path, $link); 

    //return true iff the file exists
    return file_exists($link);
}

function html5_video_playback($url, $width=800, $height=600) {
    return '<video width="'.$width.'" height="'.$height.'" src="'.$url.'" />';
}

///===========================
/// callback filter functions

function mediaplugin_filter_mp4_callback($link) 
{
    global $CFG, $PAGE;

    static $count = 0;
    $count++;
    $id = 'filter_mp4_'.uniqid().$count; //we need something unique because it might be stored in text cache

    $videomod = optional_param('modifier', '', PARAM_RAW);
     
    //if the requested modifier exists, use it
    if(modifier_exists($link[1], $videomod))
        $url = addslashes_js(modifier_link($link[1], $videomod));
    else
        $url = addslashes_js($link[1]);

    $width  = empty($link[3]) ? '800' : $link[3];
    $height = empty($link[4]) ? '600' : $link[4];

    if($PAGE->theme->name == "bumobile" || $PAGE->theme->name == "mymobile") {
        return html5_video_playback($url, '100%', 'auto' );
    }

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

    //get all modified videos which exist for this link
    $existing_mods = existing_modifiers($link[1]);

    //for each of the existing modifiers
    foreach($existing_mods as $mod => $data)
    {
        //FIXME: use external target (moodle filter paradigm)
        $url = new moodle_url($PAGE->url);
        $url->param('modifier',  $mod);

        //get the link to the vmod
        $vmod_link = html_writer::link($url, get_string('vmod'.$mod, 'filter_jwplayerfilter'));

        //add the vmod_active class if the vmod is active
        $active = ($mod == $videomod) ? 'active' : '';

        //and add it as a span
        $return_val .= html_writer::tag('span', $vmod_link, array('class' => 'vmod vmod'.$mod.' '.$active));        

    }
  
    //FIXME: add JS for in-place load (progressive enhancement)

    $return_val .= html_writer::end_tag('div');

    return $return_val;


}

?>
