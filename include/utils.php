<?php
// WebSVN - Subversion repository viewing via the web using PHP
// Copyright (C) 2004-2006 Tim Armes
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// --
//
// utils.php
//
// General utility commands

// {{{ createDirLinks
//
// Create a list of links to the current path that'll be available from the template

function createDirLinks($rep, $path, $rev, $showchanged)
{
   global $vars, $config;
   
   $subs = explode('/', htmlentities($path));
   $sofar = "";
   $count = count($subs);
   $vars["curdirlinks"] = "";
   
   // The number of links depends on the last item.  It's empty if
   // we're looing at a directory, and full if it's a file
   if (empty($subs[$count - 1]))
   {
      $limit = $count - 2;
      $dir = true;
   }
   else
   {
      $limit = $count - 1;
      $dir = false;
   }
      
   for ($n = 0; $n < $limit; $n++)
   {
      $sofar .= html_entity_decode($subs[$n])."/";
      $sofarurl = $config->getURL($rep, $sofar, "dir");
      $vars["curdirlinks"] .= "[<a href=\"${sofarurl}rev=$rev&amp;sc=$showchanged\">".$subs[$n]."/</a>] ";
   }
   
   if ($dir)
   {
      $vars["curdirlinks"] .=  "[<b>".$subs[$n]."</b>/]";
   }
   else
   {
      $vars["curdirlinks"] .=  "[<b>".$subs[$n]."</b>]";
   }
}

// }}}

// {{{ create_anchors
//
// Create links out of http:// and mailto: tags

# TODO: the target="_blank" nonsense should be optional (or specified by the template)
function create_anchors($text)
{
   $ret = $text;

   // Match correctly formed URLs that aren't already links
	$ret = preg_replace("#\b(?<!href=\")([a-z]+?)://(\S*)([\w/]+)#i",
	                    "<a href=\"\\1://\\2\\3\" target=\"_blank\">\\1://\\2\\3</a>",
	                    $ret);
	                    
	// Now match anything beginning with www, as long as it's not //www since they were matched above                    
	$ret = preg_replace("#\b(?<!//)www\.(\S*)([\w/]+)#i",
	                    "<a href=\"http://www.\\1\\2\" target=\"_blank\">www.\\1\\2</a>",
	                    $ret);

	// Match email addresses
	$ret = preg_replace("#\b([\w\-_.]+)@([\w\-.]+)\b#i",
	                    "<a href=\"mailto:\\1@\\2\">\\1@\\2</a>",
	                    $ret);
      
	return ($ret);
}

// }}}

// {{{ getFullURL

function getFullURL($loc)
{
   $protocol = 'http';
   
   if (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off'))
   {
   	$protocol = 'https';
   }
   
   $port = ':'.$_SERVER['SERVER_PORT'];
   if ((':80' == $port && 'http' == $protocol) ||
       (':443' == $port && 'https' == $protocol)) 
   {
      $port = '';
   }
   
   if (isset($_SERVER['HTTP_HOST']))
   {
   	$host = $_SERVER['HTTP_HOST'];
   }
   else if (isset($_SERVER['SERVER_NAME']))
   {
   	$host = $_SERVER['SERVER_NAME'].$port;
   }
   else if (isset($_SERVER['SERVER_ADDR']))
   {
   	$host = $_SERVER['SERVER_ADDR'].$port;
   }
   else
   {
      print 'Unable to redirect';
      exit;
   }

   # make sure we have a directory to go to
   if (empty($loc))
   {
      $loc = '/';
   }
   elseif ($loc{0} != '/')
   {
      $loc = '/'.$loc;
   }
   
   $url = $protocol . '://' . $host . $loc;

   return $url;
}

// }}}

// {{{ hardspace
//
// Replace the spaces at the front of a line with hard spaces

# XXX: this is an unnecessary function; you can prevent whitespace from being
#      trimmed via CSS (use the "white-space: pre;" properties). ~J
# in the meantime, here's an improved function (does nothing)

function hardspace($s)
{
   return '<code>' . expandTabs($s) . '</code>';
}

// }}}

// {{{ expandTabs

/**
 * Expands the tabs in a line that may or may not include HTML.
 *
 * Enscript generates code with HTML, so we need to take that into account.
 *
 * @param string $s Line of possibly HTML-encoded text to expand
 * @param int $tabwidth Tab width, -1 to use repository's default, 0 to collapse
 *                      all tabs.
 * @return string The expanded line.
 * @since 2.1
 */

function expandTabs($s, $tabwidth = -1)
{
   global $rep;

   if ($tabwidth == -1) 
      $tabwidth = $rep->getExpandTabsBy();
   $pos = 0;

   // Parse the string into chunks that are either 1 of: HTML tag, tab char, run of any other stuff
   $chunks = preg_split("/((?:<.+?>)|(?:&.+?;)|(?:\t))/", $s, -1, PREG_SPLIT_DELIM_CAPTURE);

   // Count the sizes of the chunks and replace tabs as we go
   for ($i = 0; $i < count($chunks); $i++)
   {
      # make sure we're not dealing with an empty string
      if (empty($chunks[$i])) continue;
      switch ($chunks[$i]{0})
      {
         case '<':       // HTML tag   : ignore its width by doing nothing
         break;

         case '&':       // HTML entity: count its width as 1 char
             $pos        +=   1;
         break;

         case "\t":      // Tab char: replace it with a run of spaces between length tabwidth and 1
             $tabsize    = $tabwidth - ($pos % $tabwidth);
             $chunks[$i] = str_repeat(' ', $tabsize);
             $pos        +=   $tabsize;
         break;

         default:        // Anything else: just keep track of its width
             $pos        +=   strlen($chunks[$i]);
         break;
      }
   }

   // Put the chunks back together and we've got the original line, detabbed.
   return join('', $chunks);
}

// }}}

// {{{ datetimeFormatDuration
//
// Formats a duration of seconds for display.
//
// $seconds the number of seconds until something
// $nbsp true if spaces should be replaced by nbsp
// $skipSeconds true if seconds should be omitted
//
// return the formatted duration (e.g. @c "8h  6m  1s")

function datetimeFormatDuration($seconds, $nbsp = false, $skipSeconds = false)
{
	global $lang;
	
	$neg = false;
	if ($seconds < 0) {
		$seconds = -$seconds;
		$neg = true;
	}
	
	$qty = array();
	$names = array($lang["DAYLETTER"], $lang["HOURLETTER"], $lang["MINUTELETTER"]);
	
	$qty[] = (int)($seconds / (60 * 60 * 24));
	$seconds %= 60 * 60 * 24;
	
	$qty[] = (int)($seconds / (60 * 60));
	$seconds %= 60 * 60;
	
	$qty[] = (int)($seconds / 60);
	
	if (!$skipSeconds) {
		$qty[] = (int)($seconds % 60);
		$names[] = $lang["SECONDLETTER"];
	}
	
	$text = $neg ? '-' : '';
	$any = false;
	$count = count($names);
	for ($i = 0; $i < $count; $i++) {
		// If a "higher valued" time slot had a value or this time slot
		// has a value or this is the very last entry (i.e. all values
		// are 0 and we still want to print seconds)
		if ($any || $qty[$i] > 0 || $i == $count - 1) {
			if ($any) $text .= $nbsp ? '&nbsp;' : ' ';
			if ($any && $qty[$i] < 10) $text .= '0';
			$text .= $qty[$i].$names[$i];
			$any = true;
		}
	}
	return $text;
}

// }}}

// {{{ buildQuery
//
// Build parameters for url query part

function buildQuery($data, $separator = '&amp;', $key = '')
{
    if (is_object($data)) $data = get_object_vars($data);
    $p = array();
    foreach($data as $k => $v)
    {
        $k = urlencode($k);
        if (!empty($key)) $k = $key.'['.$k.']';
        
        if (is_array($v) || is_object($v)) {
            $p[] = buildQuery($v, $separator, $k);
        }
        else
        {
            $p[] = $k.'='.urlencode($v);
        }
    }
    
    return implode($separator, $p);
}

// }}}

// {{{ getParameterisedSelfUrl
//
// Get the relative URL (PHP_SELF) with GET and POST data

function getParameterisedSelfUrl($params = true)
{
   global $config;

   $url = null;

   if ($config->multiViews)
   {
      // Get rid of the file's name
      $url = preg_replace('/\.php/', '', $_SERVER['PHP_SELF'], 1);
   }
   else
   {
      $url = basename($_SERVER['PHP_SELF']);

      // Sometimes the .php isn't on the end.  Damn strange...
      if (strchr($url, '.') === false)
         $url .= '.php';   
   }

   if ($params)
   {
      $arr = $_GET + $_POST;
      # XXX: the point of HTTP POST is that URIs have a set size limit, so POST
      #      data is typically too large to bother with; why include it?
      $url .= '?'.buildQuery($arr);
   }

   return $url;
}

// }}}

// {{{ getUserLanguage

function getUserLanguage() {
   $languages = !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : 'en';
   if (strpos($languages, ',') === false) return $languages; # only one specified
   # split off the languages into an array of languages and q-values
   $qvals = array();
   $langs = array();
   preg_match_all('#(\S+?)\s*(?:;q=([01](?:\.\d{1,3})?))?\s*,\s*#', $languages, $qvals, PREG_SET_ORDER);
   foreach ($qvals as $q) {
      $langs[] = array (
         $q[1],
         floatval(!empty($q[2]) ? $q[2] : 1.0)
      );
   }
   # XXX: now, we should loop through our available languages and choose an
   # appropriate one for the user
   # note that we shouldn't match the region unless we have a specific region
   # to use (e.g. zh-CN vs. zh-TW)
   # FIXME: see above; otherwise, this function doesn't really do anything
}

// }}}

?>