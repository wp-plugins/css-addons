
function cssaddons_update() {
    var csspis = {};
    var i = 0;
    jQuery('#cssaddons_list input').each(function ($) {
        if (jQuery(this).is(':checked')) {
            csspis[i] = jQuery(this).val();
            i++;
        }
    });

    jQuery('#customize-control-CSS_Addons textarea').val(serialize(csspis)).trigger("change");
}
function str_replace(search,replace,subject){
    while(subject.indexOf(search)>-1){
        subject = subject.replace(search,replace);
    }
    return subject;
}
function cssaddons_refactor(){
    jQuery('#css-addons-form span.dashicons-trash').unbind('click').click(function(){
        if(confirm(cssaddons.remove_confirm)){
            jQuery(this).parent().parent().hide(500,function(){jQuery(this).remove();});
        }
    });
}

jQuery(document).ready(function ($) {
    jQuery('#cssaddons_list input').change(function ($) {
        cssaddons_update();
    });
    cssaddons_update();
    cssaddons_refactor();


    $('#css-addons-form a.button-default').click(function(){
        i = $(this).data('id');
        newrow = $('#css-addons-form-list tr').last().clone();
        newrow.find('input, textarea').val('');
        newrow.html(str_replace('addons['+(i-1)+']','addons['+(i)+']',newrow.html()));
        newrow.appendTo('#css-addons-form-list');
        $(this).data('id',i+1);
        cssaddons_refactor();
        return false;
    });
});
