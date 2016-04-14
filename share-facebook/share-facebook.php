<?php
/*
  Plugin Name: Facebook Share Feed Dialog
  Plugin URI: http://google.com/
  Description: This plugin creates a javascript function fb_share_feed_dialog(). You can use this function to let people post directly to their Timeline
  

  Version: 1.01
  Author: The Anh
  Author URI: http://google.com/
 */

function ta_share_fb_script() {
    wp_enqueue_script( 'social-share-fb', plugins_url('js/share-fb.js', __FILE__), array( 'jquery' ), '1.0.0', true );
}

add_action( 'wp_enqueue_scripts', 'ta_share_fb_script' );

function ta_print_fb_sdk_script() {
  $options = get_option('share_fb_plugin_main_settings');
?>
    <script>
      window.fbAsyncInit = function() {
        FB.init({
          appId      : '<?php echo $options["app_id"]?>', //1619383618315607
          xfbml      : true,
          version    : 'v2.5'
        });
      };

      (function(d, s, id){
         var js, fjs = d.getElementsByTagName(s)[0];
         if (d.getElementById(id)) {return;}
         js = d.createElement(s); js.id = id;
         //js.src = "//connect.facebook.net/en_US/sdk.js";
         js.src = "https://connect.facebook.net/en_US/sdk.js";
         fjs.parentNode.insertBefore(js, fjs);
       }(document, 'script', 'facebook-jssdk'));
    </script>

<?php
}  
add_action( 'wp_footer', 'ta_print_fb_sdk_script' );

add_action('wp_head', 'ta_add_fb_open_graph_tags');
function ta_add_fb_open_graph_tags() {
  $options = get_option('share_fb_plugin_additonal_settings');  
  if($options['render_meta_tag'] == 1)
  {
    if (is_single()) 
    {
      global $post;
      $title = get_the_title();
      $url = get_the_permalink();
      //$description = get_bloginfo('description');
      $description = ta_share_fb_excerpt( $post->post_content, $post->post_excerpt );
      $description = strip_tags($description);
      $description = str_replace("\"", "'", $description);
      if(get_the_post_thumbnail($post->ID, 'thumbnail')) {
          $thumbnail_id = get_post_thumbnail_id($post->ID);
          $thumbnail_object = get_post($thumbnail_id);
          $image = $thumbnail_object->guid;
      } else {    
          $image = ta_catch_first_image_in_content($post->post_content, 200, 200);
      }
    }
    else//default
    {
      $title = $options['title'];
      $description = $options['description'];
      $image = $options['image_url'];
    }
?>
  <meta property="og:title" content="<?php echo $title; ?>" />
  <meta property="og:type" content="article" />
  <meta property="og:image" content="<?php echo $image; ?>" />
  <meta property="og:url" content="<?php echo $url ?>" />
  <meta property="og:description" content="<?php echo $description ?>" />
  <meta property="og:site_name" content="<?php echo get_bloginfo('name'); ?>" />
  
  <!-- <meta property="article:author" content="https://www.facebook.com/dongydinhtuan" />
  <meta property="article:publisher" content="https://www.facebook.com/dongydinhtuan" /> -->
<?php
  } //if render_meta_tag
}//function

function ta_share_fb_excerpt($text, $excerpt){
    
    if ($excerpt) return $excerpt;

    $text = strip_shortcodes( $text );

    $text = apply_filters('the_content', $text);
    $text = str_replace(']]>', ']]>', $text);
    $text = strip_tags($text);
    $excerpt_length = apply_filters('excerpt_length', 55);
    $excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
    $words = preg_split("/[\n
     ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
    if ( count($words) > $excerpt_length ) {
            array_pop($words);
            $text = implode(' ', $words);
            $text = $text . $excerpt_more;
    } else {
            $text = implode(' ', $words);
    }

    return apply_filters('wp_trim_excerpt', $text, $excerpt);
}

function ta_catch_first_image_in_content($post_content, $w = 150, $h = 150, $zc = 1) {       
  $output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post_content, $matches);
  $first_img = $matches[1][0];
  if(empty($first_img)) {
    $first_img = get_template_directory_uri() . "/images/no-image-found.jpg";
  }
  else
  {
    $first_img = get_template_directory_uri() . '/timthumb.php?src=' . urlencode($first_img) . '&h=' . $h . '&w=' . $w . '&zc=' . $zc . ''; //&h=150&w=150&zc=1
  }       
  return $first_img;
}

//Add Setting link below plugin name
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'ta_share_fb_action_links' );

function ta_share_fb_action_links( $links ) {
  $mylinks = array( '<a href="' . admin_url( 'options-general.php?page=facebook-share' ) . '">Settings</a>', );
  return array_merge( $mylinks, $links );
}

/**
* Create Setting Page class
*/
class ShareFBSettingsPage {
  
  function __construct() {
    add_action( 'admin_menu', array( $this, 'add_plugin_settings_menu' ) );
    add_action( 'admin_init', array( $this, 'register_settings' ) );
  }
  
  function add_plugin_settings_menu() {
    add_options_page( 'Share FB Dialog Feed Settings', 'Share FB Dialog Feed', 'manage_options', 'facebook-share', array($this, 'create_plugin_settings_page') );
  }
  
  function create_plugin_settings_page() {
  ?>
    <div class="wrap">
      <?php screen_icon(); ?>
        <h2>Share FB Settings</h2>        
        <form method="post" action="options.php">
          <?php
          // This prints out all hidden setting fields
          // settings_fields( $option_group )
          settings_fields( 'share-fb-sections-settings-group' );
          // do_settings_sections( $page )
          do_settings_sections( 'share-fb-sections-plugin' );
          ?>
          <?php submit_button('Save Changes'); ?>
        </form>
    </div>
  <?php
  }
  
  function register_settings() {
    
    // add_settings_section( $id, $title, $callback, $page )
    add_settings_section(
      'main-settings-section',
      'Main Settings',
      array($this, 'print_main_settings_section_info'),
      'share-fb-sections-plugin'
    );
    
    // add_settings_field( $id, $title, $callback, $page, $section, $args )
    add_settings_field(
      'app_id',
      'App ID', 
      array($this, 'create_input_app_id'), 
      'share-fb-sections-plugin', 
      'main-settings-section'
    );
    
    // register_setting( $option_group, $option_name, $sanitize_callback )
    register_setting( 'share-fb-sections-settings-group', 'share_fb_plugin_main_settings', array($this, 'plugin_main_settings_validate') );
    
    // add_settings_section( $id, $title, $callback, $page )
    add_settings_section(
      'additional-settings-section',
      'Additional Settings & Default Value',
      array($this, 'print_additional_settings_section_info'),
      'share-fb-sections-plugin'
    );
    
    // add_settings_field( $id, $title, $callback, $page, $section, $args )
    add_settings_field(
      'render_meta_tag',
      'Render Meta Tag',
      array($this, 'create_input_render_meta_tag'), 
      'share-fb-sections-plugin', 
      'additional-settings-section'
    );

    add_settings_field(
      'title', 
      'Default Title', 
      array($this, 'create_input_title'), 
      'share-fb-sections-plugin', 
      'additional-settings-section'
    );

    add_settings_field(
      'description', 
      'Default Description', 
      array($this, 'create_input_description'), 
      'share-fb-sections-plugin', 
      'additional-settings-section'
    );

    add_settings_field(
      'image_url', 
      'Default Image URL', 
      array($this, 'create_input_image_url'), 
      'share-fb-sections-plugin', 
      'additional-settings-section'
    );
    
    // register_setting( $option_group, $option_name, $sanitize_callback )
    register_setting( 'share-fb-sections-settings-group', 'share_fb_plugin_additonal_settings', array($this, 'plugin_additional_settings_validate') );
  }
  
  function print_main_settings_section_info() {
    echo '<p>Your app\'s unique identifier.</p>';
  }
  
  function create_input_app_id() {
    $options = get_option('share_fb_plugin_main_settings');?>
    <input type="text" name="share_fb_plugin_main_settings[app_id]" value="<?php echo $options['app_id']; ?>" class="regular-text" /><?php
  }
  
  function plugin_main_settings_validate($arr_input) {
    $options = get_option('share_fb_plugin_main_settings');
    $options['app_id'] = trim( $arr_input['app_id'] );
    return $options;
  }
  
  function print_additional_settings_section_info() {
    //echo '<p>Additional Settings Description.</p>';
  }
  
  function create_input_render_meta_tag() {
    $options = get_option('share_fb_plugin_additonal_settings');
    $checked = ( (int)$options['render_meta_tag'] == 1 ) ? 'checked' : '';
  ?>
    <input type="checkbox" name="share_fb_plugin_additonal_settings[render_meta_tag]" value="1" <?php echo $checked; ?> />
  <?php
  }

  function create_input_title() {
    $options = get_option('share_fb_plugin_additonal_settings');
?>
    <input type="text" name="share_fb_plugin_additonal_settings[title]" value="<?php echo $options['title']; ?>" class="regular-text" />
<?php
  }

  function create_input_description() {
    $options = get_option('share_fb_plugin_additonal_settings');
  ?>    
    <textarea name="share_fb_plugin_additonal_settings[description]" rows="5" cols="60" class=""><?php echo $options['description']; ?></textarea>
  <?php
  }

  function create_input_image_url() {
    $options = get_option('share_fb_plugin_additonal_settings');
  ?>
    <input type="text" name="share_fb_plugin_additonal_settings[image_url]" value="<?php echo $options['image_url']; ?>" class="regular-text" />
  <?php
  }

  
  function plugin_additional_settings_validate($arr_input) {
    $options = get_option('share_fb_plugin_additonal_settings');    
    $options['render_meta_tag'] = trim( $arr_input['render_meta_tag'] );
    $options['title'] = trim( $arr_input['title'] );
    $options['description'] = trim( $arr_input['description'] );
    $options['image_url'] = trim( $arr_input['image_url'] );    
    return $options;
  }
  
}

if( is_admin() )
    $share_fb_settings_page = new ShareFBSettingsPage();