<?php
/*
Plugin Name: Raven Analytics
Plugin URI: http://firm-media.com/analytics
Description: This plugin makes it simple to add Raven Analytics to your WordPress blog. 
Author: Thom Meredith for Firm Media based on Rich Boakes Google Analytics code
Version: 0.2
Author URI: http://thommeredith.com
License: GPL

0.2 - Fixed an incorrect reference to Google Analytics Plugin
0.1 - Conception, from http://boakes.org
*/

$ruastring = "00000000";
$wp_uastring_takes_precedence = true;
$includeUDN = false;

/*
 * Admin User Interface
 */
if ( ! class_exists( 'RA_Admin' ) ) {

	class RA_Admin {

		function add_config_page() {
			global $wpdb;
			if ( function_exists('add_submenu_page') ) {
				add_submenu_page('plugins.php', 'Raven Analytics Configuration', 'Raven Analytics', 1, basename(__FILE__), array('RA_Admin','config_page'));
			}
		} // end add_RA_config_page()

		function config_page() {
			global $ruastring;
			if ( isset($_POST['submit']) ) {
				if (!current_user_can('manage_options')) die(__('You cannot edit the UA string.'));
				check_admin_referer();
				$ruastring = $_POST['uastring'];
				update_option('raven_analytics_uastring', $ruastring);
			}
			$mulch = ($ruastring=""?"########":$ruastring);
	
			?>
			<div class="wrap">
				<h2>Raven Analytics Configuration</h2>
				<p>Raven Analytics is a statistics service provided
					by <a href="http://raven-seo-tools.com">Raven SEO Tools</a>.  This plugin simplifies
					the process of including the Raven
					Analytics code in your blog, so you don't have to
					edit any PHP. If you don't have a Raven SEO account
					 yet, you can get one at 
					<a href="http://raven-seo-tools.com">raven-seo-tools.com</a>.</p>

				<p>In the Raven interface, when you are in the account that corresponds to your blog, click on ANALYTICS > Settings. All you need is the 8-letter/number code similar to this: {_raven._init("00000000")}. Enter that 8-letter/number code in the box below.</p>

				<p>Once you have entered your User Account String in
				   the box below your pages will be trackable by
					Raven Analytics.</p>
				
				<form action="" method="post" id="analytics-conf" style="margin: auto; width: 25em; ">
					<h3><label for="uastring">Raven Analytics User Account</label></h3>
					<p><input id="uastring" name="uastring" type="text" size="20" maxlength="40" value="<?php echo get_option('raven_analytics_uastring'); ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;" /></p>
					<p class="submit"><input type="submit" name="submit" value="Update UA String &raquo;" /></p>
				</form>

			</div>
			<?php
			$opt = get_option('raven_analytics_uastring');
			if (isset($opt)) {
				if ($opt == "") {
					add_action('admin_footer', array('RA_Admin','warning'));
				} else {
					if (isset($_POST['submit'])) {
						add_action('admin_footer', array('RA_Admin','success'));
					}
				}
			} else {
				add_action('admin_footer', array('RA_Admin','warning'));
			}

		} // end config_page()

		function success() {
			echo "
			<div id='analytics-warning' class='updated fade-ff0000'><p><strong>Congratulations! You have just activated Raven Analytics - take a look at the source of your blog pages and search for the word 'raven' to see how your pages have been affected.</p></div>
			<style type='text/css'>
			#adminmenu { margin-bottom: 7em; }
			#analytics-warning { position: absolute; top: 7em; }
			</style>";
		} // end analytics_warning()

		function warning() {
			echo "
			<div id='analytics-warning' class='updated fade-ff0000'><p><strong>Raven Analytics is not active.</strong> You must <a href='plugins.php?page=ravenanalytics.php'>enter your Tracking Code String</a> for it to work.</p></div>
			<style type='text/css'>
			#adminmenu { margin-bottom: 6em; }
			#analytics-warning { position: absolute; top: 7em; }
			</style>";
		} // end analytics_warning()

	} // end class RA_Admin

} //endif


/**
 * Code that actually inserts stuff into pages.
 */
if ( ! class_exists( 'RA_Filter' ) ) {
	class RA_Filter {

		function analytics_cats() {
      	global $dir, $post;
		      foreach (get_the_category($post->ID) as $cat) {
      		 	$profile = get_option('analytics_'.$cat->category_nicename);
		         if ($profile != "") {
						return $profile;
					}
      		}
			return '';
		} //end analytics_cats()

		function spool_analytics() {
			global $ruastring, $post, $version;

			echo("\n\n<!--\nRaven Analytics Plugin for Wordpress \nhttp://firm-media.com/analytics\n-->\n");

			// check if there's a post level profile
			// and if so, use it.
			if (function_exists("get_post_meta")) {
				$rua = get_post_meta($post->ID, $ruakey);
				if ($rua[0] != "") {
					RA_Filter::spool_this($rua);
					return;
				}
			}

			// use the default channel if there is 
			if ($ruastring != "") {
				RA_Filter::spool_this($ruastring);
				return;
			}

			// if we get here there is a problem
			echo("<!-- The plugin is enabled but no channel account number is available. -->\n");
		} // end spool_analytics()

		
		
		function spool_this($rua) {
			global $version, $includeUDN;
		
		echo("<script type=\"text/javascript\">\n");
		echo("var ravenProt = ((\"https:\" == document.location.protocol) ? \"https://\" : \"http://\");\n");
		echo("document.write(unescape(\"%3Cscript src='\" + ravenProt + \"raven-seo-tracker.com/rt.js' type='text/javascript'%3E%3C/script%3E\"));\n");
		echo("</script>\n");
		echo("<script type=\"text/javascript\">\n");
		echo("var ravenTracker = _raven._init(\"$rua\");\n");
		echo("ravenTracker._track();\n");
		echo("</script>	\n");
	}

		/* Create an array which contains:
		 * "domain" e.g. thommeredith.org
		 * "host" e.g. store.thommeredith.org
		 */
		function ra_get_domain($uri){

			$hostPattern = "/^(http:\/\/)?([^\/]+)/i";
			$domainPattern = "/[^\.\/]+\.[^\.\/]+$/";

			preg_match($hostPattern, $uri, $matches);
			$host = $matches[2];
			preg_match($domainPattern, $host, $matches);
			return array("domain"=>$matches[0],"host"=>$host);    

		}

		/* Take the result of parsing an HTML anchor ($matches)
		 * and from that, extract the target domain.  If the 
		 * target is not local, then when the anchor is re-written
		 * then an urchinTracker call is added.
		 *
		 * the format of the outbound link is definedin the $leaf
		 * variable which must begin with a / and which may 
		 * contain multiple path levels:
		 * e.g. /outbound/x/y/z 
		 * or which may be just "/"
		 *
		 */
		function ra_parse_link($leaf, $matches){
			global $origin ;
			$target = RA_Filter::ra_get_domain($matches[3]);
			$coolbit = "";
			if ( $target["domain"] != $origin["domain"]  ){
				$coolBit .= "onclick=\"javascript:urchinTracker ('".$leaf."/".$target["host"]."');\"";
			} 
			return '<a href="' . $matches[2] . '//' . $matches[3] . '"' . $matches[1] . $matches[4] . ' '.$coolBit.'>' . $matches[5] . '</a>';    
		}

		function ra_parse_article_link($matches){
			return RA_Filter::ra_parse_link("/outbound/article",$matches);
		}

		function ra_parse_comment_link($matches){
			return RA_Filter::ra_parse_link("/outbound/comment",$matches);
		}

		function the_content($text) {
			static $anchorPattern = '/<a (.*?)href="(.*?)\/\/(.*?)"(.*?)>(.*?)<\/a>/i';
			$text = preg_replace_callback($anchorPattern,array('RA_Filter','ra_parse_article_link'),$text);
			return $text;
		}

		function comment_text($text) {
			static $anchorPattern = '/<a (.*?)href="(.*?)\/\/(.*?)"(.*?)>(.*?)<\/a>/i';
			$text = preg_replace_callback($anchorPattern,array('RA_Filter','ra_parse_comment_link'),$text);
			return $text;
		}

		function comment_author_link($text) {
	
			static $anchorPattern = '(.*href\s*=\s*)[\"\']*(.*)[\"\'] (.*)';
			ereg($anchorPattern, $text, $matches);
			if ($matches[2] == "") return $text;
	
			$target = RA_Filter::ra_get_domain($matches[2]);
			$coolbit = "";
			$origin = RA_Filter::ra_get_domain($_SERVER["HTTP_HOST"]);
			if ( $target["domain"] != $origin["domain"]  ){
				$coolBit .= " onclick=\"javascript:urchinTracker('/outbound/commentauthor/".$target["host"]."');\" ";
			} 
			return $matches[1] . "\"" . $matches[2] . "\"" . $coolBit . $matches[3];    
		}
	} // class RA_Filter
} // endif

$version = "0.3";
$ruakey = "analytics";

if (function_exists("get_option")) {
	if ($wp_uastring_takes_precedence) {
		$ruastring = get_option('raven_analytics_uastring');
	}
} 

$mulch = ($ruastring=""?"########":$ruastring);
$gaf = new RA_Filter();
$origin = $gaf->ra_get_domain($_SERVER["HTTP_HOST"]);

if (!function_exists("add_RA_config_page")) {
} //endif

// adds the menu item to the admin interface
add_action('admin_menu', array('RA_Admin','add_config_page'));

// adds the footer so the javascript is loaded
add_action('wp_footer', array('RA_Filter','spool_analytics'));
// adds the footer so the javascript is loaded
add_action('bb_foot', array('RA_Filter','spool_analytics'));

// filters alter the existing content
add_filter('the_content', array('RA_Filter','the_content'), 99);
add_filter('the_excerpt', array('RA_Filter','the_content'), 99);
add_filter('comment_text', array('RA_Filter','comment_text'), 99);
add_filter('get_comment_author_link', array('RA_Filter','comment_author_link'), 99);

?>