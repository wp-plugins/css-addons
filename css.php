<?php
/*
  Plugin Name: CSS Addons
  Description: Lets administrator add CSS addons to any theme
  Version: 1.5.0
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
    var $libs;
    var $static_path;
    var $static_url;
    var $i=0;
    var $version;
    var $current_version;
    var $libs_path;
    var $libs_list;

    public function __call($method, $args){
	if($method == "option_get"){
	    return call_user_func($this->$method, $args[0],isset($args[1])?$args[1]:NULL);
	}
	elseif($method == "option_update"){
	    return call_user_func($this->$method, $args[0], $args[1]);
	}
    }

    //Load plugin
    function __construct() {
	load_plugin_textdomain('css-addons', false, 'css-addons/languages');

	// Check environment
	$this->option_get = 'get_' . ($this->isnetwork() ? 'site_' : '') . 'option';
	$this->option_update = 'update_' . ($this->isnetwork() ? 'site_' : '') . 'option';
	$this->option_cap = 'manage_' . ($this->isnetwork() ? 'network_' : '') . 'options';

	$this->libs_path=plugin_dir_path(__FILE__) . 'libs/';

	// Loader
	add_action('init', array($this, 'load'));
	add_action('wp_enqueue_scripts', array(&$this, 'scripts'),900);
	add_action('admin_enqueue_scripts', array(&$this, 'admin_scripts'),100);
        add_action( 'customize_preview_init', array(&$this, 'customizer_scripts'), 100 );

	// Customizer
	add_action('customize_register', array(&$this, 'customize'));
	add_action('customize_save_after', array(&$this, 'save'));

	// Admin
	add_action('admin_post_cssaddons_saveoptions', array(&$this, 'available_save'));
	add_action(($this->isnetwork()?'network_':'').'admin_menu', array(&$this, 'menu'));
    }

    //PHP4 constructor
    public function CSSAddons(){
        $this->__construct();
    }

    /*
     * Core & usefull functions
     */
    function exists(){
	return (count($this->addons) || !empty($this->custom));
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
	// Static location
	$upload_dir = wp_upload_dir();
	$this->static_path=$upload_dir['basedir'].'/css-addons.css';
	$base_url = $upload_dir['baseurl'].'/css-addons.css';
	$exploded_url = parse_url($base_url);
	$this->static_url=site_url().$exploded_url['path'];
	$this->version = $this->option_get('CSS_Addons_time');
	$this->current_version = is_file($this->static_path)?filemtime ($this->static_path):0;

	$this->libs_list=$this->lib_scan();

	$this->addons = $this->get_option('Addons',array());
	$this->libs = $this->get_option('Libs',array());
	$this->custom = $this->get_option('Custom',array());

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
        if(!is_array($this->libs)){
            return;
        }
	foreach ($this->libs as $lib){
	    $this->lib_load($lib);
	}
    }
    function admin_scripts($hook_suffix){
	if('widgets.php'!=$hook_suffix && 'customizer.php'!=$hook_suffix && 'settings_page_css_addons_available_manage'!=$hook_suffix){
	    return;
	}
        wp_enqueue_style('codemirror', plugins_url('/CodeMirror/lib/codemirror.css', __FILE__), false, null);
        wp_enqueue_style('codemirror-show-hint', plugins_url('/CodeMirror/addon/hint/show-hint.css', __FILE__), false, null);

        wp_enqueue_script('codemirror', plugins_url('/CodeMirror/lib/codemirror.js', __FILE__), '', '', true);
        wp_enqueue_script('codemirror-css', plugins_url('/CodeMirror/mode/css/css.js', __FILE__), array('codemirror'), '', true);
        wp_enqueue_script('codemirror-show-hint', plugins_url('/CodeMirror/addon/hint/show-hint.js', __FILE__), array('codemirror'), '', true);
        wp_enqueue_script('codemirror-css-hint', plugins_url('/CodeMirror/addon/hint/css-hint.js', __FILE__), array('codemirror'), '', true);
	wp_enqueue_script('xorax_serialize', plugins_url('/xorax_serialize.js', __FILE__), '', '', true);
	wp_enqueue_script('cssaddons', plugins_url('/addons.js', __FILE__), array('jquery','codemirror'), '', true);
	wp_localize_script('cssaddons', 'cssaddons', array(
	    'remove_confirm' => __('Do you really want to remove this addon ?','css-addons'),
	));
	wp_enqueue_style('cssaddons', plugins_url('/addons.css', __FILE__),array(),max($this->current_version,$this->version));
    }
    function customizer_scripts() {
	wp_enqueue_script('xorax_serialize', plugins_url('/xorax_serialize.js', __FILE__), '', '', true);
	wp_enqueue_script( 'cssaddons_customizer', plugins_url('/customizer.js', __FILE__), array( 'customize-preview' ), time(), true );
        wp_localize_script('cssaddons_customizer', 'cssaddons_customizer', array(
	    'addons'=>$this->get_addons()
	));
    }

    function lib_load($library){
	if(!isset($this->libs_list[$library])){
	    return;
	}
	foreach ($this->libs_list[$library] as $file_name=>$file_url){
	    if(!is_file($this->libs_path.$library.'/'.$file_name)){
		continue;
	    }
	    $file_extension = strtolower(substr(strrchr($file_name,"."),1));
	    if($file_extension=='css'){
		wp_enqueue_style('css-addons-'.$library.'-'.$file_name, $file_url, false, null);
	    }
	    elseif($file_extension=='js'){
		wp_enqueue_script('css-addons-'.$library.'-'.$file_name, $file_url, array(), '', true);
	    }
	}

    }
    function lib_scan($dir=''){
	$libs=array();
	$directories = scandir($this->libs_path.$dir);
	foreach($directories as $entry){
	    if($entry!='.' && $entry!='..' && is_dir($this->libs_path.$dir.$entry)){
		$libs[$entry]=$this->lib_scan($entry.'/');
	    }
	    elseif(is_file($this->libs_path.$dir.$entry)){
		$libs[$entry]=plugins_url('libs/'.$dir.$entry, __FILE__);
	    }
	}
	return $libs;
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
		    'name'=> stripslashes(esc_attr($addon['name'])),
		    'description'=>stripslashes(esc_attr($addon['description'])),
		    'css'=>stripslashes($addon['css']),
		);
	    }
	}
	$names=array();
	foreach ($available as $addon_id => $addon) {
	    $names[$addon_id]  = $addon['name'];
	}
	array_multisort($names, SORT_ASC, $available);

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
                        <a class="thickbox" href="#TB_inline?width=900&height=600&inlineId=cssaddons-box-<?php echo $this->i; ?>">
                            <?php echo substr($css, 0, 30); ?>...
                        </a>
                        <div id="cssaddons-box-<?php echo $this->i; ?>" style="display: none;">
			<textarea name="addons[<?php echo $this->i ?>][css]" class="widefat cssaddons-multi-editor" id="cssaddons-textarea-<?php echo $this->i; ?>"><?php echo $css ?></textarea>
                        </div>
		    </td>
		    <td>
			<span class="dashicons dashicons-trash"></span>
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
	if (\filter_input(INPUT_GET,'confirm') == 'saved') {?>
	    <div class="updated"><p><?php _e('Available CSS addons have been saved !', 'css-addons') ?></p></div>
	<?php }
        add_thickbox();
        ?>
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

	// Librairies list
	$wp_customize->add_setting('CSS_Libs', array(
	    'default' => '',
	    'transport' => 'postMessage'
	));

	$wp_customize->add_control(new CSS_libs_Control($wp_customize, 'CSS_Libs', array(
	    'label' => __('CSS librairies', 'css-addons'),
	    'section' => 'css_addons_section',
	    'settings' => 'CSS_Libs',
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
	    </label>
            <textarea rows="5" style="width:100%;" <?php $this->link(); ?> id="cssaddons-custom-css"  onfocus="cssaddons_editor(this);"><?php echo esc_textarea($this->value()); ?></textarea>
            <div id="cssaddons-custom-css-editor"></div>
                <?php
	}
    }



    class CSS_addons_Control extends WP_Customize_Control {

	public $type = 'csspi';

	public function render_content() {
	    global $CSSAddons;
	    $addons_available = $CSSAddons->get_addons();
	    $addons_enabled = (array) $CSSAddons->addons;
	    ?>
	    <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
	    <div class="css-addons-control">
	    <textarea <?php $this->link(); ?> class="css-addons-textarea"><?php echo $this->value(); ?></textarea>
	    <ul id="cssaddons_list" class="css-addons-list">
			<?php foreach ($addons_available as $addon_id => $addon): ?>
		    <li><label>
			    <input type="checkbox" value="<?php echo $addon_id; ?>" <?php echo checked(in_array($addon_id,$addons_enabled),true)  ?>>
			    <strong><?php echo $addon['name']; ?></strong>
		    <?php echo ($addon['description'] != '') ? '<p>' . $addon['description'] . '</p>' : ''; ?>
			</label></li>
	    <?php endforeach; ?>
	    </ul>
	    </div>
	    <?php
	}
    }

    class CSS_libs_Control extends WP_Customize_Control {

	public $type = 'csslib';

	public function render_content() {
	    global $CSSAddons;
	    $libs_enabled = (array) $CSSAddons->libs;
	    ?>
	    <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
	    <div class="css-addons-control">
	    <textarea <?php $this->link(); ?> class="css-addons-textarea"><?php echo $this->value(); ?></textarea>
	    <ul id="csslibs_list" class="css-addons-list">
			<?php foreach ($CSSAddons->libs_list as $lib => $files): ?>
		    <li><label>
			    <input type="checkbox" value="<?php echo $lib; ?>" <?php echo checked(in_array($lib,$libs_enabled),true)  ?>>
			    <strong><?php echo ucfirst($lib); ?></strong>
			</label></li>
	    <?php endforeach; ?>
	    </ul>
	    </div>
	    <?php
	}
    }
}
