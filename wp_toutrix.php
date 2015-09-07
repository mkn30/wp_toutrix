<?php
/*
Plugin Name: TouTrix AdServer
Plugin URI:  http://toutrix.com/wp_toutrix
Description: This plugin connect to TouTrix AdMedia Server, create zone to earn money to show ads. You can also ask a withdrawal without leaving your website.
Version:     0.4
Author:      TouTrix
Author URI:  http://toutrix.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: toutrix-adserver
*/

// TODO - Error manager from the API. We don't check for error at all for the moment.
// TODO - Validation before submiting
// TODO - Add ads inside article

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once "classes/toutrix_php/api_toutrix.php";
require "creative.php";
require "campaign.php";
require "flights.php";
require "target.php";
require "widget.php";

add_option( 'ad_toutrix_username', '', '', 'yes' );
add_option( 'ad_toutrix_password', '', '', 'yes' );
add_option( 'ad_toutrix_access_token', '', '', 'yes' );
add_option( 'ad_toutrix_website_id', '', '', 'yes' );
add_option( 'ad_toutrix_zone_id', '', '', 'yes' );

// Hook for adding admin menus
add_action('admin_menu', 'toutrix_add_pages');

wp_enqueue_style('admin_css_toutrix', plugins_url('/wp_toutrix/css/toutrix.css'), false, '1.0.0', 'all');

//add_action( 'widgets_init', 'register_my_widget' );

global $adserver;
$adserver = new api_toutrix_adserver();

global $zoneId;

/*
function toutrix_get_solde() {
  global $adserver;
  $user = $adserver->get_user();
  $toutrix_user_solde = $adserver->funds;
}
*/

// action function for above hook
function toutrix_add_pages() {
    add_menu_page(__('TouTrix','menu-toutrix'), __('TouTrix','menu-toutrix'), 'manage_options', 'mt_toutrix_page-handle', 'mt_toutrix_page');

    add_submenu_page('mt_toutrix_page-handle', __('Stats','menu-stats'), __('Stats','menu-stats'), 'manage_options', 'mt_stats_page', 'mt_stats_page');

    add_submenu_page('mt_toutrix_page-handle', __('Creatives','menu-stats'), __('Creatives','menu-stats'), 'manage_options', 'toutrix_creative', 'toutrix_creative_page');

    add_submenu_page('mt_toutrix_page-handle', __('Campaigns','menu-stats'), __('Campaigns','menu-stats'), 'manage_options', 'mt_campaign', 'mt_campaign_page');

    add_submenu_page('mt_toutrix_page-handle', __('Marketplace','menu-stats'), __('Marketplace','menu-stats'), 'manage_options', 'mt_marketplace', 'mt_marketplace_page');
}

function get_channels() {
  global $adserver;
  return $adserver->channels_get(array());
}

function toutrix_get_token() {
    global $adserver;

    $toutrix_username = get_option("ad_toutrix_username");
    $toutrix_password  = get_option("ad_toutrix_password");
    $toutrix_access_token  = get_option("ad_toutrix_access_token");

    //if (strlen($toutrix_access_token)>0) {
    //  $adserver->setAccessToken($toutrix_access_token);
    //} else
    if (strlen($toutrix_username)>0 && strlen($toutrix_password)>0) {
        // Si nous n'avons pas d'access token, ca nous en prend un
        //if (strlen($toutrix_access_token)==0) {
           if ($adserver->login($toutrix_username, $toutrix_password)) {
             //echo "Access Token is now: " . $adserver->access_token . "<br/>";
             update_option( "ad_toutrix_access_token", $adserver->access_token );
             return true;
           } else {
?>
<div class="updated"><p><strong><?php _e('Cant connect with these credentials.', 'menu-test' ); ?></strong></p></div>
<?php
             update_option( "ad_toutrix_access_token", '' );
           }
        //}
    }
    return false;
}

function mt_marketplace_page() {
  global $adserver;
  $adserver->toutrix_get_token();

    if (empty($_GET['subpage'])) {
      echo "<h2>Marketplace</h2>";
      echo "Coming soon";
    }
}

// mt_toplevel_page() displays the page content for the custom Test Toplevel menu
function mt_toutrix_page() {
    global $adserver;

    echo "<div class='container'>";

    echo "<center><a href='http://toutrix.com/2015/09/07/we-are-looking-for-developpers/'>We are looking for developpers</a></center><br/>";

    echo "<h2>AdMedia configuration</h2>";

    //must check that the user has the required capability 
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }

    // Read in existing option value from database
    $toutrix_username = get_option("ad_toutrix_username");
    $toutrix_password  = get_option("ad_toutrix_password");
    $toutrix_access_token  = get_option("ad_toutrix_access_token");
    $toutrix_website_id  = get_option("ad_toutrix_website_id");
    $toutrix_zone_id  = get_option("ad_toutrix_zone_id");

    if( isset($_POST[ "signup" ]) && $_POST[ "signup" ] == 'Y' ) {
        update_option( "ad_toutrix_access_token", "" );
        update_option( "ad_toutrix_website_id", "" );
        update_option( "ad_toutrix_zone_id", "" );
        update_option( "ad_channel_id", $_POST[ 'channelId'] );

        // Read their posted value
        $toutrix_username = $_POST[ "ad_toutrix_username" ];
        $toutrix_password = $_POST[ "ad_toutrix_password" ];
        $user = new stdClass();
        $user->username = $_POST[ "ad_toutrix_username" ];
        $user->password = $_POST[ "ad_toutrix_password" ];
        $user->email = $_POST[ "ad_toutrix_email" ];
        $user->refererId = $_POST[ "refererId" ];
//var_dump($adserver);
        $user = $adserver->user_create($user);
        if ($user->error && !$user->error->message == "path is not defined") {
?>
<div class="updated"><p><strong><?php _e($user->error->message, 'menu-test' ); ?></strong></p></div>
<?php
        } else {
          update_option( "ad_toutrix_username", $toutrix_username  );
          update_option( "ad_toutrix_password", $toutrix_password  );
?>
<div class="updated"><p><strong><?php _e('TouTrix account is created.', 'menu-test' ); ?></strong></p></div>
<?php
        }
    }

    if( isset($_POST[ "config" ]) && $_POST[ "config" ] == 'Y' ) {
        // Read their posted value
        $toutrix_username = $_POST[ "ad_toutrix_username" ];
        $toutrix_password = $_POST[ "ad_toutrix_password" ];

        // Save the posted value in the database
        update_option( "ad_toutrix_username", $toutrix_username  );
        update_option( "ad_toutrix_password", $toutrix_password  );

        // Put a "settings saved" message on the screen

?>
<div class="updated"><p><strong><?php _e('settings saved.', 'menu-test' ); ?></strong></p></div>
<?php
    }
	
  if (strlen($toutrix_username)==0 && strlen($toutrix_password)==0) {
     $channels = get_channels();
     echo '<div class="wrap">';
?>

<h1>Create your account now</h2>
Fill-up the form to create your account now.<br/>
<form name="form1" method="post" action="">
<input type="hidden" name="signup" value="Y">
<input type="hidden" name="refererId" value="1">

<p><?php _e("Username:", 'menu-test' ); ?> 
<input type="text" name="ad_toutrix_username" value="<?php echo $toutrix_username; ?>" size="20">
</p>

<p><?php _e("Password:", 'menu-test' ); ?> 
<input type="text" name="ad_toutrix_password" value="<?php echo $toutrix_password; ?>" size="20">
</p>

<p><?php _e("Channel:", 'menu-test' ); ?> 
<select name='channelId'>
<?php foreach ($channels as $channel) { ?>
<option value='<?php echo $channel->id; ?>'><?php echo $channel->Title; ?></option>
<?php } ?>
</select> Choose the good channel for your website. We may change it for you later.
</p>

<p><?php _e("Your email:", 'menu-test' ); ?> 
<input type="text" name="ad_toutrix_email" value="<?php echo $toutrix_email; ?>" size="20">
</p>

<hr />

<p class="submit">
<input type="submit" name="Submit" class="button-primary" value="<?php _e("Signup on TouTrix", 'menu-test'); ?>" />
</p>

</form>
</div>

<?php } elseif (strlen($toutrix_username)>0 && strlen($toutrix_password)>0) {
       //echo "Getting token<br/>";
       if (toutrix_get_token()) {
          //echo "Got Token<br/>";
          $toutrix_access_token  = get_option("ad_toutrix_access_token");
          $toutrix_website_id  = get_option("ad_toutrix_website_id");
          if (strlen($toutrix_website_id)==0) {
              echo "Creating website...<br/>";
              $toutrix_channel_id  = get_option("ad_channel_id");

              $site = new stdClass();
              $site->Title = get_bloginfo();;
              $site->Url = get_site_url();
              $site->Description = get_bloginfo ( 'description' );
              $site->channelId = $toutrix_channel_id;
//var_dump($site);
//echo "<br/>";
              $site = $adserver->site_create($site);
//              echo "Create website: ";
//              var_dump($site);
//echo "<br/>";
              if ($site->id > 0) {
                update_option( "ad_toutrix_website_id", $site->id);
                $toutrix_website_id = $site->id;
              } else {
                echo "<font color='red'>Can't find WebSite ID</font>";
              }
              echo "<br/>";
          }
          if (strlen($toutrix_zone_id)==0 && strlen($toutrix_website_id)>0) {
              //echo "Creating zone...<br/>";
              $zone = new stdClass();
              $zone->Name = get_bloginfo();;
              $zone->siteId = $toutrix_website_id;
              $zone->channelId = channel_mainstream;

              $zone = $adserver->zone_create($zone);
              //echo "Create zone: ";
              //var_dump($zone);
              if ($zone->id > 0) {
                update_option( "ad_toutrix_zone_id", $zone->id);
                $toutrix_zone_id = $zone->id;
              } else {
                echo "<font color='red'>Can't find Zone ID</font>";
              }
              echo "<br/>";
          }

       }
    }

    $user = $adserver->get_user();

    echo "<font size='5'><b>Funds available: </b> <font color='green'>$" . number_format($user->funds,2) . "</font></font><br/>";

?>
<h1>Connect with your TouTrix account</h2>
<?php
    echo '<div class="wrap">';
    ?>
<form name="form1" method="post" action="">
<input type="hidden" name="config" value="Y">

<p><?php _e("TouTrix Username:", 'menu-test' ); ?> 
<input type="text" name="ad_toutrix_username"" value="<?php echo $toutrix_username; ?>" size="20">
</p>

<p><?php _e("TouTrix password:", 'menu-test' ); ?> 
<input type="text" name="ad_toutrix_password" value="<?php echo $toutrix_password; ?>" size="20">
</p>

<p><?php _e("Access Token:", 'menu-test' ); ?> 
<?php echo $toutrix_access_token; ?>
</p>

<p><?php _e("Website ID:", 'menu-test' ); ?> 
<?php echo $toutrix_website_id; ?>
</p>

<p><?php _e("Default Zone ID:", 'menu-test' ); ?> 
<?php echo $toutrix_zone_id; ?>
</p>

<hr />

<p class="submit">
<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
</p>

</form>
</div>

<?php
    echo "</div>";
}

function mt_stats_page() {
  echo "<h2>Stats</h2>";

  echo "Coming in a next update";
}
?>
