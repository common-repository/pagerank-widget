<?php
/*
Plugin Name: PageRank Widget
Plugin URI: http://geeklad.com/wordpress-pagerank-widget-plugin
Description: Display your PageRank on your WordPress website.  Go to your <a href="widgets.php">widgets configuration</a> to add it to your WordPress website.
Author: GeekLad
Version: 0.7
Author URI: http://geeklad.com/
*/

function control_pageRankWidget() {
	$options = $newoptions = get_option('widget_pageRankWidget');
	if ( $_POST["pagerank-widget-submit"] ) {
		$newoptions['level'] = $_POST["pagerank-widget-level"];
	}
	
	if ( empty($options['level']) )
		$level = "pages";
	else
		$level = $options['level'];
	
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_pageRankWidget', $options);
	}
?>
			<p><label for="pagerank-widget-title"><?php _e('Display top-level PageRank, or individual pages?<br>'); ?><select id="pagerank-widget-level" name="pagerank-widget-level"><option value="pages"<?php if($level == "pages") echo ' selected="selected"'; ?>>Individual Pages</option><option value="top"<?php if($level == "top") echo ' selected="selected"'; ?>>Top-Level</option></select>
				<br />
			</p>
			<input type="hidden" id="pagerank-widget-submit" name="pagerank-widget-submit" value="1" />
<?php
}

function widget_pageRankWidget() {
	global $wpdb;

	$options = get_option('widget_pageRankWidget');
	if ( empty($options['level']) )
		$level = "pages";
	else
		$level = $options['level'];
	
	if ( empty($options['root_rank'] ) ) {
		$options['root_rank'] = getpr($_SERVER["HTTP_HOST"]);
		if (!$options['root_rank']) {
			$options['root_rank'] = "N/A";
		}
		$options['root_last_update'] = time();
	}
	else if(time() - $options['root_last_update'] > 60*60*24*5) {
		$options['root_rank'] = getpr($_SERVER["HTTP_HOST"]);
		if (!$options['root_rank']) {
			$options['root_rank'] = "N/A";
		}
		$options['root_last_update'] = time();
	}
	update_option('widget_pageRankWidget', $options);
		
	if ($_SERVER["REQUEST_URI"] != "/" && $level == "pages") {
		$query = "SELECT ID FROM " . $wpdb->prefix . "posts WHERE post_name = '" . mysql_real_escape_string(preg_replace("/^\//", "", $_SERVER["REQUEST_URI"])) . "' AND (post_type = 'page' OR post_type = 'post')";
		$post = $wpdb->get_results($query);
		$postid = $post[0]->ID;
		
		if ($postid != 0) {			
			$pagerank = get_post_meta($postid, "pageRankWidget_pagerank");
			if (!$pagerank) {
				add_post_meta($postid, "pageRankWidget_pagerank", getpr($_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]));
				add_post_meta($postid, "pageRankWidget_last_update", time());
			}
			else {
				$pagerank = $pagerank[0];
				$lastupdate = get_post_meta($postid, "pageRankWidget_last_update");
				if (time() - $lastupdate[0] > 60*60*24*5) {
					$pagerank = getpr($_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
					update_post_meta($postid, "pageRankWidget_pagerank", $pagerank);
					update_post_meta($postid, "pageRankWidget_last_update", time());
				}
			}
		}
	}
	
	if ($level == "pages" && $_SERVER["REQUEST_URI"] != "/")
		echo display_pageRankWidget($postid);
	else
		echo display_pageRankWidget("root");
}

function display_pageRankWidget($level) {	
	if ($level == "root") {
		$options = get_option('widget_pageRankWidget');
		$pagerank = $options['root_rank'];
	}
	else {
		$pagerank = get_post_meta($level, "pageRankWidget_pagerank");
		$pagerank = $pagerank[0];
		if ($pagerank == "")
			$pagerank = "N/A";
	}
		
	$output = '<span id="pagerank-widget" style="font-family: Tahoma,Verdana,Arial; font-size: 12px; line-height=12px;">';
	$output .= '	<p></p>';
	$output .= '	<table cellspacing="0" cellpadding="5" style="border: 1px solid black;">';
	$output .= '		<tr>';
	$output .= '			<td align="center" style="line-height: 7px">PageRank</td>';
	$output .= '			<td style="margin: 5px; padding: 10px; background: #008000; font-size: 20px; color: #FFFFFF; line-height: 7px; font-family: Arial;" rowspan="2">' . $pagerank . '</td>';
	$output .= '		</tr>';
	$output .= '		<tr>';
	$output .= '		<td>';
	$output .= '				<table cellspacing="0" cellpadding="0" width="60px" height="8px" style="border: 1px solid black;">';
	$output .= '					<tr>';
	if ($pagerank == "N/A") {
		$output .= '						<td style="width: 80px; background: #808080;"></td>';
	} else {
		$x = (int) $x;
		for ($x=1; $x<=$pagerank; $x++) {
			$output .= '						<td style="background: #008000; width: 8px"></td>';
		}		
		for ($x=$x; $x<=10; $x++) {
			$output .= '						<td style="background: #FFFFFF; width: 8px"></td>';
		}
	}
	$output .= '					</tr>';
	$output .= '				</table>';
	$output .= '			</td>';
	$output .= '		</tr>';
	$output .= '	</table>';
	$output .= '	<span style="font-size: 9px; line-height:9px;">Powered by <a href="http://geeklad.com/wordpress-pagerank-widget-plugin">PageRank Widget</a></span>';
	$output .= '</span>';
	return $output;
}

function post_pageRankWidget($content) {
	global $wpdb;
	
	if( preg_match('/<!--pagerank-->/i', $content)) {
		$query = "SELECT ID FROM " . $wpdb->prefix . "posts WHERE post_name = '" . mysql_real_escape_string(preg_replace("/^\//", "", $_SERVER["REQUEST_URI"])) . "' AND (post_type = 'page' OR post_type = 'post')";
		$post = $wpdb->get_results($query);
		$postid = $post[0]->ID;
	
		$splitcontent = preg_split('/<!--pagerank-->/i', $content, 2);
		$content = $splitcontent[0] . display_pageRankWidget($postid) . $splitcontent[1];
	}
	return $content;
}

function pageRankWidget_init()
{
	register_sidebar_widget(__('PageRank Widget'), 'widget_pageRankWidget');
	register_widget_control(__('PageRank Widget'), 'control_pageRankWidget');
}
add_action("plugins_loaded", "pageRankWidget_init");
add_filter("the_content", "post_pageRankWidget");

// This is the actual PageRank checking code
//PageRank Lookup v1.1 by HM2K (update: 31/01/07)
//based on an alogoritham found here: http://pagerank.gamesaga.net/
//Download from http://www.hm2k.com/projects/pagerank

//settings - host and user agent
$googlehost='toolbarqueries.google.com';
$googleua='Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.6) Gecko/20060728 Firefox/1.5';

//convert a string to a 32-bit integer
function StrToNum($Str, $Check, $Magic) {
    $Int32Unit = 4294967296;  // 2^32

    $length = strlen($Str);
    for ($i = 0; $i < $length; $i++) {
        $Check *= $Magic; 	
        //If the float is beyond the boundaries of integer (usually +/- 2.15e+9 = 2^31), 
        //  the result of converting to integer is undefined
        //  refer to http://www.php.net/manual/en/language.types.integer.php
        if ($Check >= $Int32Unit) {
            $Check = ($Check - $Int32Unit * (int) ($Check / $Int32Unit));
            //if the check less than -2^31
            $Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check;
        }
        $Check += ord($Str{$i}); 
    }
    return $Check;
}

//genearate a hash for a url
function HashURL($String) {
    $Check1 = StrToNum($String, 0x1505, 0x21);
    $Check2 = StrToNum($String, 0, 0x1003F);

    $Check1 >>= 2; 	
    $Check1 = (($Check1 >> 4) & 0x3FFFFC0 ) | ($Check1 & 0x3F);
    $Check1 = (($Check1 >> 4) & 0x3FFC00 ) | ($Check1 & 0x3FF);
    $Check1 = (($Check1 >> 4) & 0x3C000 ) | ($Check1 & 0x3FFF);	
	
    $T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) <<2 ) | ($Check2 & 0xF0F );
    $T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000 );
	
    return ($T1 | $T2);
}

//genearate a checksum for the hash string
function CheckHash($Hashnum) {
    $CheckByte = 0;
    $Flag = 0;

    $HashStr = sprintf('%u', $Hashnum) ;
    $length = strlen($HashStr);
	
    for ($i = $length - 1;  $i >= 0;  $i --) {
        $Re = $HashStr{$i};
        if (1 === ($Flag % 2)) {              
            $Re += $Re;     
            $Re = (int)($Re / 10) + ($Re % 10);
        }
        $CheckByte += $Re;
        $Flag ++;	
    }

    $CheckByte %= 10;
    if (0 !== $CheckByte) {
        $CheckByte = 10 - $CheckByte;
        if (1 === ($Flag % 2) ) {
            if (1 === ($CheckByte % 2)) {
                $CheckByte += 9;
            }
            $CheckByte >>= 1;
        }
    }

    return '7'.$CheckByte.$HashStr;
}

//return the pagerank checksum hash
function getch($url) { return CheckHash(HashURL($url)); }

//return the pagerank figure
function getpr($url) {
	global $googlehost,$googleua;
	$ch = getch($url);
	$fp = fsockopen($googlehost, 80, $errno, $errstr, 30);
	if ($fp) {
	   $out = "GET /search?client=navclient-auto&ch=$ch&features=Rank&q=info:$url HTTP/1.1\r\n";
	   //echo "<pre>$out</pre>\n"; //debug only
	   $out .= "User-Agent: $googleua\r\n";
	   $out .= "Host: $googlehost\r\n";
	   $out .= "Connection: Close\r\n\r\n";
	
	   fwrite($fp, $out);
	   
	   //$pagerank = substr(fgets($fp, 128), 4); //debug only
	   //echo $pagerank; //debug only
	   while (!feof($fp)) {
			$data = fgets($fp, 128);
			//echo $data;
			$pos = strpos($data, "Rank_");
			if($pos === false){} else{
				$pr=substr($data, $pos + 9);
				$pr=trim($pr);
				$pr=str_replace("\n",'',$pr);
				return $pr;
			}
	   }
	   //else { echo "$errstr ($errno)<br />\n"; } //debug only
	   fclose($fp);
	}
}

//generate the graphical pagerank
function pagerank($url,$width=40,$method='style') {
	if (!preg_match('/^(http:\/\/)?([^\/]+)/i', $url)) { $url='http://'.$url; }
	$pr=getpr($url);
	$pagerank="PageRank: $pr/10";

	//The (old) image method
	if ($method == 'image') {
	$prpos=$width*$pr/10;
	$prneg=$width-$prpos;
	$html='<img src="http://www.google.com/images/pos.gif" width='.$prpos.' height=4 border=0 alt="'.$pagerank.'"><img src="http://www.google.com/images/neg.gif" width='.$prneg.' height=4 border=0 alt="'.$pagerank.'">';
	}
	//The pre-styled method
	if ($method == 'style') {
	$prpercent=100*$pr/10;
	$html='<div style="position: relative; width: '.$width.'px; padding: 0; background: #D9D9D9;"><strong style="width: '.$prpercent.'%; display: block; position: relative; background: #5EAA5E; text-align: center; color: #333; height: 4px; line-height: 4px;"><span></span></strong></div>';
	}
	
	$out='<a href="'.$url.'" title="'.$pagerank.'">'.$html.'</a>';
	return $out;
}

//if ((!isset($_POST['url'])) && (!isset($_GET['url']))) { echo '<form action="" method="post"><input name="url" type="text"><input type="submit" name="Submit" value="Get Pagerank"></form>'; }
if (isset($_REQUEST['url'])) { echo pagerank($_REQUEST['url']); }
?>