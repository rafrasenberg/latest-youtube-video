<?php
/*
	Plugin Name: Latest YouTube Video
	Description: Create a direct link to your latest YouTube video!
    Author: Raf Rasenberg
	Version: 1.0.0
*/
class Latest_Video_Plugin {

    public function __construct() {
    	// Hook into the admin menu
    	add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );

        // Add Settings and Fields
    	add_action( 'admin_init', array( $this, 'setup_sections' ) );
    	add_action( 'admin_init', array( $this, 'setup_fields' ) );
    }

    public function create_plugin_settings_page() {
    	// Add the menu item and page
    	$page_title = 'Latest YouTube Video';
    	$menu_title = 'Latest YouTube Video';
    	$capability = 'manage_options';
    	$slug = 'latest_video';
    	$callback = array( $this, 'plugin_settings_page_content' );
    	$icon = 'dashicons-video-alt2';
    	$position = 100;

    	add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
    }

    public function plugin_settings_page_content() {?>
    	<div class="wrap">
    		<h2>Latest YouTube Video</h2><?php
            if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ){
                  $this->admin_notice();
            } ?>
    		<form method="POST" action="options.php">
                <?php
                    settings_fields( 'latest_video' );
                    do_settings_sections( 'latest_video' );
                    submit_button();
                ?>
    		</form>
    	</div> <?php
    }
    
    public function admin_notice() { ?>
        <div class="notice notice-success is-dismissible">
            <p>Your settings have been updated!</p>
        </div><?php
    }

    public function setup_sections() {
        add_settings_section( 'our_first_section', 'Settings', array( $this, 'section_callback' ), 'latest_video' );
    }

    public function section_callback( $arguments ) {
    	switch( $arguments['id'] ){
    		case 'our_first_section':
                echo 'Enter your API-key, Channel ID and the slug you want to use down below. This plugin is open-source and 
                made for the YouTube channel of Raf Rasenberg. If you enjoy this plugin, please considering subscribing to my channel. It would help me out greatly! <a href="https://www.youtube.com/channel/UCAKlyZ9eOsQ8K8OqMi5HPJw?sub_confirmation=1">YouTube channel.</a>';
    			break;
    	}
    }

    public function setup_fields() {
        $fields = array(
        	array(
        		'uid' => 'channel_id_field',
        		'label' => 'Channel ID',
        		'section' => 'our_first_section',
        		'type' => 'text',
        		'placeholder' => 'Your channel ID',
        		'supplimental' => 'Enter your Channel ID here',
        	),
        	array(
        		'uid' => 'api_key_field',
        		'label' => 'API Key',
        		'section' => 'our_first_section',
                'type' => 'text',
                'placeholder' => 'Your API key',
        		'supplimental' => 'Enter your API key here. This is stored as plain text, so please keep your site secure!',
        	),
        	array(
        		'uid' => 'page_slug_field',
        		'label' => 'Page Slug',
        		'section' => 'our_first_section',
        		'type' => 'text',
        		'placeholder' => 'Your page slug',
        		'supplimental' => 'Enter your page slug here',
        	),
        );
    	foreach( $fields as $field ){

        	add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), 'latest_video', $field['section'], $field );
            register_setting( 'latest_video', $field['uid'] );
    	}
    }

    public function field_callback( $arguments ) {

        $value = get_option( $arguments['uid'] );

        if( ! $value ) {
            $value = $arguments['default'];
        }

        switch( $arguments['type'] ){
            case 'text':
            case 'password':
            case 'number':
                printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
                break;
        }

        if( $helper = $arguments['helper'] ){
            printf( '<span class="helper"> %s</span>', $helper );
        }

        if( $supplimental = $arguments['supplimental'] ){
            printf( '<p class="description">%s</p>', $supplimental );
        }

    }

}

new Latest_Video_Plugin();

$page_slug = get_option('page_slug_field');

function youtube_redirect() {
    if( is_page( $page_slug ) ) { 

        // Code for registering function
        $api_key = get_option('api_key_field');
        $channel_id = get_option('channel_id_field');

        $base_url = 'https://www.youtube.com/watch?v=';

        // Create the url that we want to call and use wp_remote_get to retrieve
        $url = "https://www.googleapis.com/youtube/v3/search?part=snippet&channelId={$channel_id}&maxResults=1&order=date&type=video&key={$api_key}";
        $request = wp_remote_get($url);

        // Get the request body and use json_decode to generate a PHP array
        $api_response = json_decode( wp_remote_retrieve_body( $request ), true );
        
        // Get the video ID from the array and construct our latest video url
        $video_id = $api_response["items"]["0"]["id"]["videoId"];
        $latest_video = $base_url . $video_id;

        wp_redirect( $latest_video );
        exit();
    }
  }

add_action( 'template_redirect', 'youtube_redirect' );