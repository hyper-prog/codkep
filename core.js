/*  CodKep - Core javascript file
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */
function initializeAjaxLinks()
{
    var items;

    jQuery.ajaxSetup({ cache: false });
    items = jQuery("a.use-ajax").not(".ajaxl-processed");
    jQuery.each(items,function(idx,val) {
        var aurl= jQuery(val).attr('href');
        jQuery(this).on("click",function(e) {
            jQuery.ajax({
                cache: false,
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
            var submit_element = jQuery('input[type="submit"]',this);
            var name = submit_element.attr('name');
            var value = submit_element.val();
            jQuery(this).append('<input type="hidden" name="'+name+'" value="'+value+'" />');
            jQuery.ajax({
                cache: false,
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
    var i;
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
        if(obj[i][0] == "prop")
            jQuery(obj[i][1]).prop(obj[i][2],obj[i][3]);
        if(obj[i][0] == "appendval") {
            var x,v;
            x = jQuery(obj[i][1]);
            v = x.val();
            x.val(v+(obj[i][3] && v != '' ? '\n' : '')+obj[i][2]);
        }
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
            cache: false,
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
        document.getElementById($idstr+'_sel_y').value = 1899;
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
           forms_save_array[$idstr+'_sel_y'] == 1899)
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

function dialogDragElement(elmnt)
{
    var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    document.getElementsByClassName('ck_dialog_header')[0].onmousedown = dragMouseDown;
    function dragMouseDown(e)
    {
        e = e || window.event;
        pos3 = e.clientX;
        pos4 = e.clientY;
        document.onmouseup = closeDragElement;
        document.onmousemove = elementDrag;
    }

    function elementDrag(e)
    {
        e = e || window.event;
        pos1 = pos3 - e.clientX;
        pos2 = pos4 - e.clientY;
        pos3 = e.clientX;
        pos4 = e.clientY;
        elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
        elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
     }

    function closeDragElement()
    {
        document.onmouseup = null;
        document.onmousemove = null;
    }
}

function prepare_ckdialog_a(arr) { prepare_ckdialog(arr[0],arr[1]); }
function prepare_ckdialog(title,content)
{
    jQuery('#dialog_placeholder').html(
            '<div class="ck_modalpane">' +
              '<div class="ck_dialog_body" style="background-color: #d0d0d0;">' +
                '<div class="ck_dialog_header">' +
                  '<div class="ck_dialog_title" id="popupped_title">' + title + '</div>' +
                  '<div class="ck_dialog_close">&times;</div>' +
                  '<div class="c"></div>' +
                '</div>' +
                '<div class="c"></div>' +
                '<div class="ck_dialog_prec">' +
                  '<div class="ck_dialog_content" id="popupped_content">' + content + '</div>' +
                '</div>' +
                '<div class="c"></div>' +
              '</div>' +
              '<div class="c"></div>' +
            '</div>');
}

function popup_ckdialog()
{
    console.log("Popupping dialog...");
    dialogDragElement(document.getElementsByClassName("ck_dialog_body")[0]);

    // Get the modal
    var modal = document.getElementsByClassName('ck_modalpane')[0];
    var modalin = document.getElementsByClassName('ck_dialog_body')[0];

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("ck_dialog_close")[0];

    // When the user clicks the button, open the modal
    modal.style.display = "block";
    modalin.style.display = "block";

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        console.log('Dialog close');
        modal.style.display = "none";
        modalin.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            // But we don't want this now: modal.style.display = "none";
        }
    }
}

function close_ckdialog()
{
    var modal = document.getElementsByClassName('ck_modalpane')[0];
    var modalin = document.getElementsByClassName('ck_dialog_body')[0];
    modal.style.display = "none";
    modalin.style.display = "none";
}

function b64EncodeUnicodeString(str) {
    return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function(match, p1) {
        return String.fromCharCode(parseInt(p1, 16))
    }))
}

/* ******************* DynTable functions ***************************************** */
var dyntable_objs = [];

function re_fireup_dyntableedit(id) {
    fireup_dyntableedit(dyntable_objs[id]);
}

function fireup_dyntableedit(settings) {
    dyntable_objs[settings.id] = settings;

    var items_dyncellclick = jQuery('.dyntable_' + settings.id + ' .dyncell').not('.ep');
    jQuery.each(items_dyncellclick, function (idx1, val1) {
        jQuery(this).on('click',function(e) {
            var value = jQuery(this).html();
            var cellid = jQuery(this).attr('id');
            var text = jQuery(this).attr('data-rn') + ' - ' + jQuery(this).attr('data-cn');
            var id = jQuery(this).closest('table').attr('data-id');
            var content =
                '<div class="dyntable_edit_dlg">'+ text +
                '<input id="dynpopup_mname" type="hidden" name="mname" value="'+cellid+'"/>' +
                '<br/><input class="dynpopup_input" id="dynpopup_valueinput" type="text" name="mvalue" value="' + value + '"/>' +
                '<button class="dynpopup_input" id="dynpopup_save" onclick="save_and_close_dyntable_dialog(\''+id+'\');">' +
                dyntable_objs[id].btntext+'</button>' +
                '</div>';
            prepare_ckdialog(dyntable_objs[id].title,content);
            popup_ckdialog();

            var items_returnpress = jQuery('.dynpopup_input').not('.ep');
            jQuery.each(items_returnpress, function (idx2, val2) {
                jQuery(this).on('enterKey',function(e) {
                    save_and_close_dyntable_dialog(id);
                });
                jQuery(this).on('escKey',function(e) {
                    close_ckdialog();
                });
                jQuery(this).keyup(function(e) {
                    if (e.keyCode == 13)
                        jQuery(this).trigger("enterKey");
                    if (e.keyCode == 27)
                        jQuery(this).trigger("escKey");
                });
                jQuery(val2).addClass("ep");
            });

            var inputbox = document.getElementById('dynpopup_valueinput');
            inputbox.focus();
            inputbox.setSelectionRange(0, inputbox.value.length)
        });
        jQuery(val1).addClass("ep");
    });
}

function save_and_close_dyntable_dialog(id)
{
    var mname = jQuery('#dynpopup_mname').val();
    var mval = jQuery('#dynpopup_valueinput').val();

    var sep = '?';
    if ((dyntable_objs[id].ajaxurl).indexOf('?') > -1)
        sep = '&';
    var rurl = dyntable_objs[id].ajaxurl + sep + 'subtype=' + dyntable_objs[id].ajaxsubtype + '&id=' + dyntable_objs[id].id +
        '&modname=' + mname + '&value='+b64EncodeUnicodeString(mval);
    jQuery.ajax({
        cache: false,
        url: rurl,
        context: document.body,
        error: function() {
            alert('Communication error #49');
        },
        success: function(data) {
            close_ckdialog();
            processAjaxResponse(data);
            initializeAjaxLinks();
        }
    });
}

function executeCodkepAjaxCall(tourl)
{
    jQuery.ajax({
        cache: false,
        url: tourl,
        context: document.body,
        error: function() {
            console.log('The ajax request failed: '+tourl);
        },
        success: function(data) {
            processAjaxResponse(data);
            initializeAjaxLinks();
        }
    });
}

jQuery(document).ready(function() {
    initializeAjaxLinks();
});
