/*  SEL->UL-ize - Select - Ul-Li structure transformer
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

//SEL->UL-ize
function reselulize()
{
    jQuery('.selulized').each(function (index, value) {
        jQuery(value).remove();
    });
    jQuery('.selulize-processed').each(function (index, value) {
        jQuery(value).removeClass('selulize-processed');
    });
    selulize();
}

//SEL->UL-ize
function selulize()
{
    var runner=1;
    itemss1 = jQuery('select.selulize').not('.selulize-processed');
    jQuery.each(itemss1,function(idx,val) {
        jQuery(val).hide();
        var options = jQuery('option',val);
        var currtext = ' - ',valtext = '';
        var toappend1='',toappend2='';
        toappend1 += '<div class=\'selulized selulidx'+runner+'\' data-id="'+val.id+'">';
        toappend1 += '<button><div class=\'arrow-down floatleft\'></div>'+
            '<span>';
        toappend2 += '</span>'+
            '<div class=\'arrow-down floatright\'></div></button>';
        toappend2 += '<ul>';
        runner++;
        ov = jQuery(val).val();
        for(var i=0;i<options.length;i++)
        {
            var item = jQuery(options[i]);
            toappend2 += '<li data-index=\''+item.val()+'\'>'+item.html()+'</li>';
            if(options[i].hasAttribute('selected'))
                currtext = item.html();
            if(typeof ov != 'undefined' && ov != null && ov == item.val())
                valtext = item.html();
        }
        if(valtext != '')
            currtext = valtext;
        toappend2+='</ul>';
        jQuery(val).after(toappend1+currtext+toappend2);
        jQuery(val).addClass("selulize-processed");
    });

    itemsb1 = jQuery('.selulized').not('.selulize-processed');
    jQuery.each(itemsb1,function(idx,val) {
        var ul = jQuery('ul', val);
        var uiw = ul.width();
        var uow = ul.outerWidth(true);
        var biw = jQuery('button',val).width();
        var bow = jQuery('button',val).outerWidth(true);
        if(uow > bow)
            jQuery('button', val).width(uow-(bow-biw));
        if(uow < bow)
            ul.width(bow-(uow-uiw));
        jQuery('button',val).on('click',function(e) {
            if (ul.hasClass('show')) {
                ul.removeClass('show').slideUp(60);
            }
            else
            {
                jQuery('.selulized ul').each(function (index, value) {
                    jQuery(value).removeClass('show').slideUp(60);
                });
                ul.addClass('show').slideDown(300);
                jQuery('html, body').animate({
                    scrollTop: ul.offset().top
                }, 500);
            }
            e.preventDefault();
        });
        jQuery('ul li',val).on('click',function(e) {
            var to = jQuery(this).attr('data-index');
            var txt = jQuery(this).html();
            var selid = jQuery(val).attr('data-id');
            jQuery('button span', val).html(txt);
            jQuery('ul', val).removeClass('show').slideUp(60);
            jQuery('ul', val).removeClass('show');
            jQuery('input', val).val(to);
            jQuery('#'+selid).val(to);
            jQuery(val).trigger('selulichanged');
            e.preventDefault();
        });
        jQuery(val).addClass("selulize-processed");
    });
}

jQuery(document).ready(function() {
    selulize();
});

