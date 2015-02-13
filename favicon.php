<?php
add_action('customize_register', 'CSSAddons_favicon_register');
add_action('wp_head','CSSAddons_favicon_link');

function CSSAddons_favicon_getdefault(){
    $file_ext = array('ico','png','gif','svg');
    $template_dir = get_template_directory_uri();
    foreach($file_ext as $ext){
	if(file_exists($template_dir.'/favicon.'.$ext)){
	    return $template_dir.'/favicon.'.$ext;
	}
    }
    return '/favicon.ico';
}

function CSSAddons_favicon_register($wp_customize) {
    $wp_customize->add_section( 'css_addons_favicon_settings', array(
	'title'          => __( 'Favicon', 'css-addons' ),
	'priority'       => 35,
    ) );

    $wp_customize->add_setting( 'favicon_setting', array(
	'default'   => CSSAddons_favicon_getdefault(),
	'transport'=>'postMessage'
    ) );
    $wp_customize->add_control(
	new WP_Customize_Image_Control(
	$wp_customize,
	'favicon_setting',
	array(
	    'label'   => __( 'Favicon', 'css-addons' ),
	    'section' => 'css_addons_favicon_settings',
	    'settings'   => 'favicon_setting',
	    )
	)
    );
}

function CSSAddons_favicon_link(){
    $favicon_url = get_theme_mod('favicon_setting');
    if(!empty($favicon_url)): ?>
    <link rel="shortcut icon" href="<?php echo $favicon_url; ?>" />
    <link rel="apple-touch-icon" href="<?php echo $favicon_url; ?>"/>
<?php endif;
}
