/*  CodKep - Core javascript file
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */
function initializeAjaxLinks()
{
    var items;

    items = jQuery("a.use-ajax").not(".ajaxl-processed");
    jQuery.each(items,function(idx,val) {
        var aurl= jQuery(val).attr('href');
        jQuery(this).on("click",function(e) {
            jQuery.ajax({
                url: aurl,
                context: document.body,
                error: function() {
                    console.log('The ajax request failed: '+aurl);
                },
                success: function(data) {
                    processAjaxResponse(data);
                    initializeAjaxLinks();
                }
            });
            e.preventDefault();
        });
        jQuery(val).addClass("ajaxl-processed");
    });

    items = jQuery("form.use-ajax").not(".ajaxl-processed");
    jQuery.each(items,function(idx,val) {
        var aurl= jQuery(val).attr('action');
        jQuery(this).on("submit",function(e) {
            jQuery.ajax({
                type: 'POST',
                url: aurl,
                data: jQuery(val).serializeArray(),
                error: function() {
                    console.log('The ajax request failed: '+aurl);
                },
                success: function(data) {
                    processAjaxResponse(data);
                    initializeAjaxLinks();
                }
            });
            e.preventDefault();
        });
        jQuery(val).addClass("ajaxl-processed");
    });
}

function processAjaxResponse(data)
{
    if(data == "null")
        return;
    var obj = jQuery.parseJSON(data);
    if(!obj)
        return;
    for (i = 0; i < obj.length; i++)
    {
        if(obj[i][0] == "html")
            jQuery(obj[i][1]).html(obj[i][2]);
        if(obj[i][0] == "append")
            jQuery(obj[i][1]).append(obj[i][2]);
        if(obj[i][0] == "remove")
            jQuery(obj[i][1]).remove();
        if(obj[i][0] == "val")
            jQuery(obj[i][1]).val(obj[i][2]);
        if(obj[i][0] == "addClass")
            jQuery(obj[i][1]).addClass(obj[i][2]);
        if(obj[i][0] == "removeClass")
            jQuery(obj[i][1]).removeClass(obj[i][2]);
        if(obj[i][0] == "css")
            jQuery(obj[i][1]).css(obj[i][2]);
        if(obj[i][0] == "show")
            jQuery(obj[i][1]).show(obj[i][2]);
        if(obj[i][0] == "hide")
            jQuery(obj[i][1]).hide(obj[i][2]);
        if(obj[i][0] == "toggle")
            jQuery(obj[i][1]).toggle(obj[i][2]);
        if(obj[i][0] == "alert")
            alert(obj[i][1]);
        if(obj[i][0] == "log")
            console.log(obj[i][1]);
        if(obj[i][0] == "delaycall")
            delayedAjaxCall({url: obj[i][1],msec: obj[i][2]});
        if(obj[i][0] == "run")
        {
            var fn = obj[i][2];
            var arg = obj[i][3];
            console.log('Will call '+fn+'()');
            window[fn](arg);
        }
        if(obj[i][0] == "showol")
        {
            var txt = obj[i][1];
            var timeout = obj[i][2];
            var overlay = jQuery('<div class="ajax_overlay" '+
              'style="position: fixed; top: 0; left: 0; width: 100%; height:100%; '+
                     'background-color: #000000; color: #ffffff; opacity: 0.85; z-index: 100; padding:20px;">'+
              txt+
              '</div>');
            overlay.appendTo(document.body);
            if(timeout > 0)
                window.setTimeout(function() { jQuery('.ajax_overlay').remove(); },timeout * 1000);
            jQuery('.ajax_overlay').click(function() {
                jQuery('.ajax_overlay').remove();
            });
        }
        if(obj[i][0] == "refresh")
            location.reload();
        if(obj[i][0] == "goto")
        {
            window.location.replace(obj[i][1]);
        }
    }
}

function delayedAjaxCall(arg)
{
    //console.log('Will call ajax to url '+arg['url']+ ' in '+arg['msec']+ ' msec...');
    setTimeout(function() {
        jQuery.ajax({
            type: 'POST',
            url: arg['url'],
            error: function() {
                console.log('The ajax request failed: '+arg['url']);
            },
            success: function(data) {
                processAjaxResponse(data);
                initializeAjaxLinks();
            }
        });
    },arg['msec']);
}


//Helper for forms module unknown date field type
var forms_save_array = [];
function forms_set_reset_unknown_date($idstr)
{
    if(document.getElementById($idstr+'_set').checked &&
       !document.getElementById($idstr+'_sel_y').disabled &&
       !document.getElementById($idstr+'_sel_m').disabled &&
       !document.getElementById($idstr+'_sel_d').disabled
      )
    {
        forms_save_array[$idstr+'_sel_y'] = document.getElementById($idstr+'_sel_y').value;
        forms_save_array[$idstr+'_sel_m'] = document.getElementById($idstr+'_sel_m').value;
        forms_save_array[$idstr+'_sel_d'] = document.getElementById($idstr+'_sel_d').value;
        document.getElementById($idstr+'_sel_y').disabled = true;
        document.getElementById($idstr+'_sel_m').disabled = true;
        document.getElementById($idstr+'_sel_d').disabled = true;
        document.getElementById($idstr+'_sel_y').value = 1969;
        document.getElementById($idstr+'_sel_m').value = 0;
        document.getElementById($idstr+'_sel_d').value = 0;
        return;
    }

    if(!document.getElementById($idstr+'_set').checked &&
       document.getElementById($idstr+'_sel_y').disabled &&
       document.getElementById($idstr+'_sel_m').disabled &&
       document.getElementById($idstr+'_sel_d').disabled
      )
    {
        var today = new Date();
        if(forms_save_array[$idstr+'_sel_y'] == undefined ||
           forms_save_array[$idstr+'_sel_y'] == 1969)
            forms_save_array[$idstr+'_sel_y'] = today.getFullYear();
        if(forms_save_array[$idstr+'_sel_m'] == undefined ||
           forms_save_array[$idstr+'_sel_m'] == 0)
            forms_save_array[$idstr+'_sel_m'] = today.getMonth()+1;
        if(forms_save_array[$idstr+'_sel_d'] == undefined ||
           forms_save_array[$idstr+'_sel_d'] == 0)
            forms_save_array[$idstr+'_sel_d'] = today.getDate();

        document.getElementById($idstr+'_sel_y').disabled = false;
        document.getElementById($idstr+'_sel_m').disabled = false;
        document.getElementById($idstr+'_sel_d').disabled = false;

        document.getElementById($idstr + '_sel_y').value = forms_save_array[$idstr+'_sel_y'];
        document.getElementById($idstr + '_sel_m').value = forms_save_array[$idstr+'_sel_m'];
        document.getElementById($idstr + '_sel_d').value = forms_save_array[$idstr+'_sel_d'];
    }
}

function forms_click_delete(id)
{
    jQuery('#f_'+id).show();
    jQuery('#h_'+id).val('delete');
    jQuery('#b_'+id).hide();
    jQuery('#l_'+id).hide();
}

function forms_click_selset(id)
{
    jQuery('#sel_'+id).show();
    jQuery('#sts_'+id).val('set');
    jQuery('#set_'+id).hide();
    jQuery('#reset_'+id).show();
}

function forms_click_selreset(id)
{
    jQuery('#sel_'+id).hide();
    jQuery('#sts_'+id).val('null');
    jQuery('#set_'+id).show();
    jQuery('#reset_'+id).hide();
}

jQuery(document).ready(function() {
    initializeAjaxLinks();
});
