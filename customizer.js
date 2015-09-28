/**
 * Theme Customizer enhancements for a better user experience.
 *
 * Contains handlers to make Theme Customizer preview reload changes asynchronously.
 */
//console.log(cssaddons_customizer);

function addcss(where, css){
    jQuery('#cssaddons-css'+where).remove();
    jQuery('<style type="text/css" id="cssaddons-css'+where+'">'+css+'</style>').appendTo("head");
    
    return;
    cssaddons_head = document.head || document.getElementsByTagName('head')[0];
    cssaddons_element = document.createElement("style");
    //cssaddons_element.href = "<?php echo str_replace(array('http:','https:'),'',$this->plugin_url.'/css/style.css') ?>";
    cssaddons_element.setAttribute('rel', 'stylesheet');
    cssaddons_element.setAttribute('type', 'text/css');
    cssaddons_element.setAttribute('media', 'all');

    cssaddons_content = document.createTextNode(css); 
    cssaddons_element.appendChild(cssaddons_content);

    cssaddons_head.appendChild(cssaddons_element);
}
( function( $ ) {
    // Addons
    wp.customize( 'CSS_Addons', function( value ) {
        value.bind( function( to ) {
            addons_enabled = unserialize(to);
            console.log(addons_enabled);
            css = '';
            for(a in addons_enabled){
                css+=cssaddons_customizer.addons[addons_enabled[a]].css;
            }      
            addcss('', css);            
        } );
    } );
    // Custom CSS
    wp.customize( 'CSS_Custom', function( value ) {
        value.bind( function( to ) {
            addcss('-custom', to);            
        } );
    } );
} )( jQuery );
