<?php
/*
  Plugin Name: CSS Addons
  Description: Lets administrator add CSS addons to any theme
  Version: 0.1
  Author: bastho
  Author URI: http://ba.stienho.fr
  License: GPLv2
  Text Domain: css-addons
  Domain Path: /languages/
  Tags: css, style, theme, customization
 */

if (!function_exists('is_plugin_active_for_network')) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
    // Makes sure the plugin is defined before trying to use it
}
$CSSAddons = new CSSAddons();


class CSSAddons {

    private $option_get;
    private $option_update;
    var $option_cap;
    var $addons;
    var $custom;
    var $static_path;
    var $static_url;
    var $i=0;
    var $version;
    var $current_version;

    public function __call($method, $args){
	if($method == "option_get"){
	    return call_user_func($this->$method, $args[0],isset($args[1])?$args[1]:NULL);
	}
	elseif($method == "option_update"){
	    return call_user_func($this->$method, $args[0], $args[1]);
	}
    }

    //Load plugin
    function CSSAddons() {
	load_plugin_textdomain('css-addons', false, 'css-addons/languages');

	// Check environment
	$this->option_get = 'get_' . ($this->isnetwork() ? 'site_' : '') . 'option';
	$this->option_update = 'update_' . ($this->isnetwork() ? 'site_' : '') . 'option';
	$this->option_cap = 'manage_' . ($this->isnetwork() ? 'network_' : '') . 'options';

	// Static location
	$upload_dir = wp_upload_dir();
	$this->static_path=$upload_dir['basedir'].'/css-addons.css';
	$this->static_url=$upload_dir['baseurl'].'/css-addons.css';
	$this->version = $this->option_get('CSS_Addons_time');
	$this->current_version = filemtime ($this->static_path);

	// Loader
	add_action('init', array($this, 'load'));
	add_action('wp_enqueue_scripts', array($this, 'scripts'),100);

	// Customizer
	add_action('customize_register', array($this, 'customize'));
	add_action('customize_save_after', array($this, 'save'));

	// Admin
	add_action('admin_post_cssaddons_saveoptions', array(&$this, 'available_save'));
	add_action(($this->isnetwork()?'network_':'').'admin_menu', array(&$this, 'menu'));

    }

    /*
     * Core & usefull functions
     */
    function exists(){
	return (count($this->addons) && !empty($this->custom));
    }
    function isnetwork(){
	return (is_multisite() && is_plugin_active_for_network('css-addons/css.php'));
    }
    function is($array,$key){
	return (isset($array[$key]) && !empty($array[$key]));
    }

    /*
     * load
     * loads the settings as soon as there are ready
     */
    function load(){
	$this->addons = $this->get_option('Addons');
	$this->custom = $this->get_option('Custom');

	// Check if addons have been updated
	if($this->current_version < $this->version){
	    $this->save();
	}
    }

    /*
     * scripts
     * add CSS file to pages header
     */
    function scripts(){
	if($this->exists()){
	    wp_enqueue_style('css-addons', $this->static_url, false, null);
	}
    }
    function admin_scripts(){
	wp_enqueue_script('xorax_serialize', plugins_url('/xorax_serialize.js', __FILE__), '', '', true);
	wp_enqueue_script('cssaddons', plugins_url('/addons.js', __FILE__), array('jquery'), '', true);
	wp_enqueue_style('cssaddons', plugins_url('/addons.css', __FILE__),array(),max($this->current_version,$this->version));
    }


    /*
     * get_loaded
     * list all enabled addons
     *
     * @param string $part (wich theme mod to get)
     *
     * @return array or string
     */
    function get_option($part='Addons') {
	$option = get_theme_mod('CSS_'.$part);
	if (is_string($option) && substr($option,0,2)=='a:') {
	    $option = unserialize($option);
	}
	return $option;
    }


    /*
     * Menu
     * Add items in the admin menu
     */
    function menu() {
	add_submenu_page($this->isnetwork()?'settings.php':'options-general.php', __('CSS addons', 'css-addons'), __('CSS addons', 'css-addons'), $this->option_cap, 'css_addons_available_manage', array(&$this, 'available_manage'));
    }


    /*
     * get_addons
     * list all available addons
     *
     * @filter get_addons
     * @return array of CSS addons
     */
    function get_addons() {
	return apply_filters('get_addons', $this->option_get('CSS_Addons',array()));
    }
    /*
     * available_save
     * saves available addons
     */
    function available_save() {
	if (!current_user_can($this->option_cap)){
	    wp_die(__('What are you doing over there ?', 'cssaddons'));
	}
	if (!wp_verify_nonce(\filter_input(INPUT_POST,'css-addons-options'), 'css-addons-options')) {
	    wp_die(__('Security error', 'cssaddons'));
	}
	$available=array();
	foreach ($_POST['addons'] as $addon){
	    if($this->is($addon,'slug') && $this->is($addon,'name') && $this->is($addon,'css')){
		$available[esc_attr($addon['slug'])]=array(
		    'name'=>  esc_attr($addon['name']),
		    'description'=>esc_attr($addon['description']),
		    'css'=>esc_attr($addon['css']),
		);
	    }
	}
	$this->option_update('CSS_Addons', $available);
	$this->option_update('CSS_Addons_time',time());
	wp_redirect(add_query_arg('confirm','saved',\filter_input(INPUT_POST,'_wp_http_referer',FILTER_SANITIZE_URL)));
	exit;
    }

    /*
     * available_single
     * output a single row for manage page
     */
    function available_single($slug='',$name='',$desc='',$css=''){
	?>
		<tr>
		    <td>
			<input type="text" name="addons[<?php echo $this->i ?>][slug]" class="widefat" value="<?php echo $slug ?>">
		    </td>
		    <td>
			<input type="text" name="addons[<?php echo $this->i ?>][name]" class="widefat" value="<?php echo $name ?>">
		    </td>
		    <td>
			<input type="text" name="addons[<?php echo $this->i ?>][description]" class="widefat" value="<?php echo $desc ?>">
		    </td>
		    <td>
			<textarea name="addons[<?php echo $this->i ?>][css]" class="widefat"><?php echo $css ?></textarea>
		    </td>
		</tr>
	<?php
	$this->i++;
    }
    /*
     * available_manage
     * outputs settings page
     */
    function available_manage() {
	$this->admin_scripts();
	if (\filter_input(INPUT_GET,'confirm') == 'saved') {?>
	    <div class="updated"><p><?php _e('Available CSS addons have been saved !', 'css-addons') ?></p></div>
	<?php }	?>
	<div class="wrap">
	    <div class="icon32" id="icon-cssaddons"><br></div>
	    <h2><?php _e('Available CSS addons', 'css-addons'); ?></h2>
	    <form id="css-addons-form" method="post" action="<?php echo admin_url() ?>admin-post.php">
		<input type="hidden" name="action" value="cssaddons_saveoptions">
		<?php wp_nonce_field('css-addons-options', 'css-addons-options'); ?>
		<table class="widefat">
		    <thead>
			<tr>
			    <th><?php _e('Slug', 'css-addons'); ?></th>
			    <th><?php _e('Name', 'css-addons'); ?></th>
			    <th><?php _e('Description', 'css-addons'); ?></th>
			    <th><?php _e('CSS', 'css-addons'); ?></th>
			</tr>
		    </thead>
		    <tbody id="css-addons-form-list">
			<?php $addons_available = $this->get_addons();
			foreach ($addons_available as $addon_id => $addon){
			    $this->available_single($addon_id,$addon['name'],$addon['description'],$addon['css']);
			}
			$this->available_single();
			if(\filter_input(INPUT_GET,'add')=='row'){
			    $this->available_single();
			}
			?>
		    </tbody>
		    <tfoot>
			<tr>
			    <td colspan="4">
				<p class="submit">
				    <a href="<?php echo add_query_arg('add', 'row') ?>" data-id="<?php echo $this->i ?>" class="button button-default"><?php _e('Add', 'css-addons'); ?></a>
				    <input type="submit" value="<?php _e('Save', 'css-addons'); ?>" class="button button-primary">
				</p>
			    </td>
			</tr>
		    </tfoot>
		</table>

	    </form>
	</div>
	<?php
    }

    /*
     * save
     * save CSS settings to a static file
     * in order to improve performances
     */
    function save(){
	if($this->exists()){
	    $css='';
	    $addons_available = $this->get_addons();
	    foreach ($this->addons as $addon){
		if(isset($addons_available[$addon])){
		    $css.=$addons_available[$addon]['css'];
		}
	    }
	    $css.=$this->custom;
	    $file=fopen($this->static_path,'w+');
	    fwrite($file,$css);
	    fclose($file);
	}
    }

    /*
     * customize
     * add part to the customizer
     */
    function customize($wp_customize) {
	CSSAddons_register_controls();
	// Add section
	$wp_customize->add_section('css_addons_section', array(
	    'title' => __('CSS addons', 'css-addons'),
	    'priority' => 35,
	));

	// Addons list
	$wp_customize->add_setting('CSS_Addons', array(
	    'default' => '',
	    'transport' => 'postMessage'
	));

	$wp_customize->add_control(new CSS_addons_Control($wp_customize, 'CSS_Addons', array(
	    'label' => __('CSS addons', 'css-addons'),
	    'section' => 'css_addons_section',
	    'settings' => 'CSS_Addons',
	)));

	// Custom CSS
	$wp_customize->add_setting('CSS_Custom', array(
	    'default' => '',
	    'transport' => 'postMessage'
	));
	$wp_customize->add_control(new CSS_addons_textarea_control($wp_customize, 'CSS_Custom', array(
	    'label' => __('Custom CSS', 'css-addons'),
	    'section' => 'css_addons_section',
	    'settings' => 'CSS_Custom',
	)));
    }
}




/*
 * CSS_addon_Control Class
 */
function CSSAddons_register_controls() {

    class CSS_addons_textarea_control extends WP_Customize_Control {

	public $type = 'textarea';

	public function render_content() {
	    ?>
	    <label>
	        <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
	        <textarea rows="5" style="width:100%;" <?php $this->link(); ?>><?php echo esc_textarea($this->value()); ?></textarea>
	    </label>
	    <?php
	}
    }

    class CSS_addons_Control extends WP_Customize_Control {

	public $type = 'csspi';

	public function render_content() {
	    global $CSSAddons;
	    $addons_available = $CSSAddons->get_addons();
	    $addons_enabled = $CSSAddons->addons;
	    $CSSAddons->admin_scripts();
	    ?>
	    <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
	    <textarea  <?php $this->link(); ?>><?php echo $this->value(); ?></textarea>
	    <ul id="cssaddons_list">
			<?php foreach ($addons_available as $addon_id => $addon): ?>
		    <li><label>
			    <input type="checkbox" value="<?php echo $addon_id; ?>" <?php echo checked(in_array($addon_id,$addons_enabled),true)  ?>>
			    <h4><?php echo $addon['name']; ?></h4>
		    <?php echo ($addon['description'] != '') ? '<p>' . $addon['description'] . '</p>' : ''; ?>
			</label></li>
	    <?php endforeach; ?>
	    </ul>
	    <?php
	}
    }
}
