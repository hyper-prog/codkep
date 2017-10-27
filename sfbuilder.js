/*  CodKep - Builder javascript file
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

function strKnead(str,type)
{
    if(typeof str !== 'string')
        return str;
    s = str;
    s = s.replace(/\\/g, '\\\\');
    s = s.replace(/\"/g, '\\"'); //'
    return s;
}

function builderGetByIdx(idx,obj)
{
    if(obj['##i'] == idx)
        return obj;
    for(var key in obj)
    {
        if (!obj.hasOwnProperty(key))
            continue;
        if(key.charAt(0) == '#')
            continue;
        if (typeof obj[key] == "object" && obj[key] !== null)
        {
            var v = builderGetByIdx(idx,obj[key]);
            if(v !== null)
                return v;
        }
    }
    return null;
}

function builderMaxIdx(obj)
{
    var idx = 0;
    for(var key in obj)
    {
        if (!obj.hasOwnProperty(key))
            continue;
        if(key == '##i')
            if(obj[key] > idx)
                idx = obj[key];
        if(typeof obj[key] == "object" && obj[key] !== null)
        {
            var v = builderMaxIdx(obj[key]);
            if(v > idx)
                idx = v;
        }
    }
    return idx;
}

function builderMaxItemcounter(obj)
{
    var ic = 0;
    for(var key in obj)
    {
        if (!obj.hasOwnProperty(key))
            continue;
        if(obj['##pn'] == 'fields')
            if(parseInt(key) > ic)
                ic = parseInt(key);
        if(typeof obj[key] == "object" && obj[key] !== null)
        {
            var v = builderMaxItemcounter(obj[key]);
            if(v > ic)
                ic = v;
        }
    }
    return ic;
}

function buiderUpdateControlPanel()
{
    var conf = '',conv = '',conl = '',pos = '';
    var hasReq=false;
    if(true) //detect the end of the generation
    {
        var c = curr;
        while(c['##pn'] != '')
        {
            pos = c['##pn'] + ' &gt;&gt; ' + pos;
            c = builderGetByIdx(c['##pi'],struct);
        }
        pos = 'ROOT  &gt;&gt; ' + pos;
        conf += '<input id="btnCloser" type="button" value="Close '+curr['##pn']+'" onclick="builderCloseItem()"/><br/>';

        for (i = 0; i < gral.length; ++i)
        {
            var s = builderGenerateControl(gral[i]);
            if(s != '' && gral[i][6] == 1)
                hasReq = true;
            if(s != '')
            {
                s = '<span class="field" title="'+gral[i][8]+'">'+s+(gral[i][6]?'<span class="required">*</span>':'')+'</span><br/>';
                if(gral[i][5] == 0)
                    conf += s;
                if(gral[i][5] == 1)
                    conv += s;
                if(gral[i][5] == 2)
                    conl += s;
            }
        }
    }

    if(saved_curr == '')
        jQuery("#warningfield").html('');
    else
    {
        var h='';
        h += '<span style="background-color: #ff9999; padding: 1px 5px 1px 5px; margin: 1px 8px 1px 8px;">Temporally edited section</span>';
        h += '<button onclick="builderReturnCurr()">Return</button>';
        h += '<span style="background-color: #ff9999; padding: 1px 5px 1px 5px; margin: 1px 8px 1px 8px;">Temporally edited section</span>';
        jQuery("#warningfield").html(h);
    }

    jQuery("#controls_pos").html(pos);
    jQuery("#controls_func").html(conf);
    jQuery("#controls_valid").html(conv);
    jQuery("#controls_look").html(conl);

    jQuery('#btnCloser').attr('disabled',hasReq);

    jQuery('.numberonly').keyup(function () {
        this.value = this.value.replace(/[^0-9]/g,'');
    });
}

function builderGenerateControl(spec)
{
    ret = '';

    //not match level skip
    if(curr['##l'] != spec[0])
        return '';

    //used skip
    if(spec[2] != 'f' && spec[2].charAt(2) != 'p')
        for(var k in curr)
            if(curr.hasOwnProperty(k))
                if(spec[1] == k)
                    return '';

    //not match type skip
    if(spec[7].length > 0 && jQuery.inArray(curr['##t'],spec[7]) == -1)
        return '';

    //not match parent skip
    if(spec[3].length > 0 && jQuery.inArray(curr['##pn'],spec[3]) == -1)
        return '';

    if(spec[2].charAt(1) == 's' || spec[2].charAt(1) == 'i')
    {
        var cla = '';
        if(spec[2].charAt(1) == 'i')
            cla += (cla==''?'':' ') + 'numberonly';
        if(spec[1] == 'color')
            cla += (cla==''?'':' ') + 'input_html_color';
        ret += '<input type="text" name="'+spec[1]+'" id="id_'+spec[1]+'" value="" class="'+cla+'"/>';
    }
    if(spec[2].charAt(1) == 'v' || spec[2].charAt(1) == 'b')
    {
        ret += '<select id="id_'+spec[1]+'" >';
        for(iv = 0;iv<spec[4].length;++iv)
            ret += '<option value="'+spec[4][iv]+'">'+spec[4][iv]+'</option>';
        ret += '</select>';
    }
    if(spec[2].charAt(1) == 'p')
        ret += '<input type="text" id="id_i_'+spec[1]+'" value=""'+(spec[2].charAt(0) == 'i' ? ' class="numberonly"' : '')+'/>'+
               '<input type="text" id="id_v_'+spec[1]+'" value=""/>';
    ret += '<input type="button" value="'+spec[1]+'" onclick="builderAppendItem(\''+window.btoa(JSON.stringify(spec))+'\')"/>';
    return ret;
}

function genpad(i)
{
    x = '';
    for(j=0;j<((i+1)*2);++j)
        x += ' ';
    return x;
}

function builderAppendItem(jsonstr)
{
    var spec = JSON.parse(window.atob(jsonstr));
    if(spec[2] == 'o' || spec[2] == 'f') //open section
    {
        parname = spec[1];
        if(spec[2] == 'f') //new field item
        {
            bt = 2;
            bv = itemcounter;
            parname = ''+itemcounter;
            itemcounter += 10;
        }

        curr['#'+parname] = spec[2];
        curr[parname] = {};
        curr[parname]['##i'] = tidx++;
        curr[parname]['##l'] = curr['##l'] + 1;
        curr[parname]['##pi'] = curr['##i'];
        curr[parname]['##pn'] = parname;
        curr[parname]['##t'] = curr['##t'];
        curr = curr[parname];

        builderShowData(true);
        buiderUpdateControlPanel();
        return;
    }

    if(spec[1] == 'type')
        curr['##t'] = jQuery('#id_'+spec[1]).val();

    if(spec[2].charAt(1) == 'p')
    {
        var bv = jQuery('#id_i_'+spec[1]).val();
        var ev = jQuery('#id_v_'+spec[1]).val();

        curr[bv] = ev;
        curr['#'+bv] = spec[2];
    }
    else
    {
        if(spec[2].charAt(1) == 'b')
        {
            var v = jQuery('#id_'+spec[1]).val();
            if(v.toLowerCase() == 'true')
                curr[spec[1]] = true;
            else
                curr[spec[1]] = false;
        }
        else
            curr[spec[1]] = jQuery('#id_'+spec[1]).val();

        curr['#'+spec[1]] = spec[2];
    }

    if(editor_current == curr['##i'])
        builderEditShow(editor_current);
    builderShowData(true);
    buiderUpdateControlPanel();
}

function builderEditInsert(index,ab)
{
    curr = builderGetByIdx(index,struct);
    var fieldnumber = parseInt(curr['##pn']);
    curr = builderGetByIdx(curr['##pi'],struct);
    fieldnumber += ab;

    bt = 2;
    bv = itemcounter;
    parname = ''+fieldnumber;

    if(parname in curr )
    {
        alert('Sorry, The field key is already exists!');
        return;
    }

    curr['#'+parname] = 'f';
    curr[parname] = {};
    curr[parname]['##i'] = tidx++;
    curr[parname]['##l'] = curr['##l'] + 1;
    curr[parname]['##pi'] = curr['##i'];
    curr[parname]['##pn'] = parname;
    curr[parname]['##t'] = curr['##t'];

    curr = curr[parname];

    builderShowData(true);
    buiderUpdateControlPanel();
    builderEditShow(curr['##pi']);
    builderSetCurrent(curr['##i']);
    return;
}

function builderCloseItem()
{
    curr = builderGetByIdx(curr['##pi'],struct);
    builderShowData(true);
    buiderUpdateControlPanel();
}

function builderUndo()
{
    var lastk=null

    for(var key in curr)
    {
        if (!curr.hasOwnProperty(key) || key.charAt(0) == '#')
            continue;
        lastk = key;
    }

    if(lastk == null) //last step was: create a section
    {
        var pn = curr['##pn'];
        if(pn == '')
            return; //no more undo
        curr = builderGetByIdx(curr['##pi'],struct);
        if(curr['##pn'] == 'fields')
            itemcounter -= 10;
        delete curr[pn];
        delete curr['#'+pn];
    }

    if(typeof curr[lastk] == "object") //last step was: close a section
    {
        curr = curr[lastk];
    }

    if(typeof curr[lastk] != "object") //last step was: create normal value
    {
        delete curr[lastk];
        delete curr['#'+lastk];
    }

    if(editor_current == curr['##i'])
        builderEditShow(editor_current);
    builderShowData(true);
}

function builderShowData(scrolldown)
{
    var php;
    php = '[\n'+builderGetPhp(struct,1)+'];\n';

    jQuery("#data").val(php);

    if(scrolldown)
    {
        var psconsole = jQuery('#data');
        if(psconsole.length)
           psconsole.scrollTop(psconsole[0].scrollHeight - psconsole.height());
    }

    buiderUpdateControlPanel();
}

function builderGetPhp(obj,l)
{
    var txt='';
    for(var key in obj)
    {
        if (!obj.hasOwnProperty(key))
            continue;
        if(key.charAt(0) == '#')
            continue;
        txt += genpad(l*2);
        if(typeof obj[key] == "object" && obj[key] !== null)
        {
            if(obj['#'+key].charAt(0) == 'o')
                txt += '"'+strKnead(key,'php')+'"';
            if(obj['#'+key].charAt(0) == 'f')
                txt += key;

            txt += ' => [\n'+
                    builderGetPhp(obj[key],l+1)+
                    genpad(l*2) + '],\n';
        }
        else
        {
            if(obj['#'+key].charAt(0) == 's')
                txt += '"'+strKnead(key,'php')+'" => ';
            if(obj['#'+key].charAt(0) == 'i')
                txt += key + ' => ';

            if(obj['#'+key].charAt(1) == 's' || obj['#'+key].charAt(1) == 'v' || obj['#'+key].charAt(1) == 'p')
                txt += '"'+strKnead(obj[key],'php')+'",\n';
            if(obj['#'+key].charAt(1) == 'i' || obj['#'+key].charAt(1) == 'b')
            {
                var value = strKnead(obj[key], 'php');
                if(obj['#'+key].charAt(1) == 'i' && value === "")
                    txt += '"",\n';
                else
                    txt += value + ',\n';
            }
        }
    }
    return txt;
}

function builderPreview()
{
    editor_current = '';
    var json_text = JSON.stringify(struct);
    jQuery.ajax({
        type: 'POST',
        url: builder_ajax_preview_url,
        data: {level: 0,json_send:json_text},
        error: function() {
            console.log('The ajax request failed: '+aurl);
        },
        success: function(data) {
            processAjaxResponse(data);
            initializeAjaxLinks();
        }
    });
}

function builderGetSpec(name,type)
{
    for (i = 0; i < gral.length; ++i)
    {
        if(gral[i][1] == name && gral[i][2] == type)
            return gral[i];
    }
    return null;
}

function builderEdit()
{
    builderEditShow(0);
}
function builderSetCurrent(cIdx)
{
    if(saved_curr == '')
        saved_curr = curr['##i'];
    curr = builderGetByIdx(cIdx,struct);
    builderShowData(false);
}

function builderReturnCurr()
{
    c = builderGetByIdx(saved_curr,struct);
    if(c !== null)
    {
        curr = c;
        saved_curr = '';
    }
    else
    {
        curr = struct;
    }
    builderShowData(true);
}

function delBtn(par)
{
    return '<button style="border:0; padding:0; margin:0; background-color: transparent; cursor:pointer;" '+
        'title="Delete this item" onclick="builderEditDelete(\''+par+'\')">'+
        '<img style="border:0; padding:0; margin:0; width: 15px; height: 15px;" src="'+builder_redcross_url+'"/></button>'
}

function insBtnU(par)
{
    return '<button style="border:0; padding:0; margin:0; background-color: transparent; cursor:pointer;" '+
        'title="Insert a new field before this field" onclick="builderEditInsert(\''+par+'\',-1)">'+
        '<img style="border:0; padding:0; margin:0; width: 15px; height: 15px;" src="'+builder_luas_url+'"/></button>'
}
function insBtnD(par)
{
    return '<button style="border:0; padding:0; margin:0; background-color: transparent; cursor:pointer;" '+
        'title="Insert a new field after this field" onclick="builderEditInsert(\''+par+'\',1)">'+
        '<img style="border:0; padding:0; margin:0; width: 15px; height: 15px;" src="'+builder_ldas_url+'"/></button>'
}

function incrBtn(par)
{
    return '<button style="border:0; padding:0; margin:0; background-color: transparent; cursor:pointer;" '+
        'title="Increase the field index by one" onclick="builderFieldRenumber(\''+par+'\',1)">'+
        '<img style="border:0; padding:0; margin:0; width: 15px; height: 15px;" src="'+builder_down_url+'"/></button>'
}
function decrBtn(par)
{
    return '<button style="border:0; padding:0; margin:0; background-color: transparent; cursor:pointer;" '+
        'title="Reduce the field index by one" onclick="builderFieldRenumber(\''+par+'\',-1)">'+
        '<img style="border:0; padding:0; margin:0; width: 15px; height: 15px;" src="'+builder_up_url+'"/></button>'
}

function builderFieldRenumber(index,change)
{
    var cf = builderGetByIdx(index,struct);
    var oldkey = parseInt(cf['##pn']);
    var newkey = oldkey + change;
    var parent = builderGetByIdx(cf['##pi'],struct);

    parent[newkey] = parent[oldkey];
    delete parent[oldkey];

    parent['#'+newkey] = parent['#'+oldkey];
    delete parent['#'+oldkey];

    parent[newkey]['##pn'] = ''+newkey;

    builderShowData(true);
    buiderUpdateControlPanel();
    builderEditShow(curr['##pi']);
    return;
}

function builderEditShow(startIdx)
{
    var i;
    var h='';

    var showObj;
    if(startIdx == 0)
        showObj = struct;
    else
        showObj = builderGetByIdx(startIdx,struct);
    editor_current = showObj['##i'];

    h += "<button onclick=\"builderEditShow(" + showObj['##pi'] + ")\" "+(showObj['##pi'] != 0 ? '' : 'disabled')+'>Up</button>';
    h += (showObj['##pn'] == '' ? '' : '('+showObj['##pn']+')');
    h += '\n';
    for(var key in showObj)
    {
        if (!showObj.hasOwnProperty(key))
            continue;
        if(key.charAt(0) == '#')
            continue;
        if(typeof showObj[key] == "object" && showObj[key] !== null)
        {
            var additionalInfo = '';
            if(showObj['#'+key] == 'f')
            {
                var keyint = parseInt(key);
                if(!(''+(keyint-1) in showObj))
                    h += decrBtn(showObj[key]["##i"]);
                h += ' <strong>'+key+'</strong> ';
                if(!(''+(keyint+1) in showObj))
                    h += incrBtn(showObj[key]["##i"]);
                h += ' =&gt; ';
            }
            else
            {
                h += key + ' =&gt; ';
            }
            if(showObj['#'+key] == 'f' && showObj[key]['sql'] != null)
                additionalInfo = '('+showObj[key]['sql']+') ';
            h += '<button onclick="builderEditShow('+showObj[key]['##i']+')">show</button>&nbsp;';
            if(showObj['#'+key] == 'f')
            {
                var keyint = parseInt(key);
                if(!(''+(keyint-1) in showObj))
                    h += insBtnU(showObj[key]["##i"]);
                if(!(''+(keyint+1) in showObj))
                    h += insBtnD(showObj[key]["##i"]);
                h += ' ';
            }
            h += delBtn(showObj['##i']+'#'+key)+' '+additionalInfo+'\n';
        }
        else
        {
            //key row
            if(showObj['#'+key].charAt(0) == 's')
                h += '"' + key+ '" =&gt; ';
            if(showObj['#'+key].charAt(0) == 'i')
                h += key+ ' =&gt; ';

            //value row
            if(showObj['#'+key].charAt(1) == 's' || showObj['#'+key].charAt(1) == 'p')
            {
                h += '"' + '<input style=\"border:0; background-color: #eeffee;\" '+
                                   'class="builder_editor_cell' + (key == 'color' ? ' input_html_color':'') + '" ' +
                                   'type=\"text\" name=\"' + showObj['##i']+'#'+key + '\" '+
                                   'value=\"'+showObj[key]+'\"/>' + '",&nbsp;'+delBtn(showObj['##i']+'#'+key)+'\n';
            }
            if(showObj['#'+key].charAt(1) == 'i' || showObj['#'+key].charAt(1) == 'b')
            {
                h += '<input style=\"border:0; background-color: #eeffee;\" '+
                            'class="builder_editor_cell' + (showObj['#'+key].charAt(1) == 'i' ? ' numberonly' : '') + '" ' +
                            'type=\"text\" name=\"' + showObj['##i']+'#'+key + '\" '+
                            'value=\"'+showObj[key]+'\"/>' + ',&nbsp;'+delBtn(showObj['##i']+'#'+key)+'\n';
            }
            if(showObj['#'+key].charAt(1) == 'v')
            {
                var spec;
                spec = builderGetSpec(key,showObj['#'+key]);
                if(spec === null)
                {
                    h += 'Error';
                }
                else
                {
                    h += '<select class="builder_select_cell" name="'+showObj['##i']+'#'+key+
                         '" style=\"background-color: #eeffee;\">';
                    for(iv = 0;iv<spec[4].length;++iv)
                        h += '<option value="'+spec[4][iv]+'"'+
                                (spec[4][iv] == showObj[key]?' selected':'')+'>'+
                                spec[4][iv]+'</option>';
                    h += '</select>&nbsp;'+delBtn(showObj['##i']+'#'+key)+'\n';
                }
            }
        }
    }
    h += "<button onclick=\"builderSetCurrent(" +
                showObj['##i'] +
        ")\" " + (showObj['##i'] != 0 ? '' : 'disabled')+'>Make this current</button>';
    h += '\n';
    jQuery('#preview_area').html('<pre>'+h+'</pre>');
}

function builderEditDelete(todelete)
{
    var parts = todelete.split('#');
    if(parts == null || parts.length == 0)
    {
        console.log('Address error.');
        return;
    }
    var o = builderGetByIdx(parts[0],struct);
    delete o[parts[1]];
    delete o['#'+parts[1]];
    if(editor_current != '')
        builderEditShow(editor_current);
    builderShowData(false);
}

function builderEditorCellChanged(e)
{
    var name = jQuery(e.target).attr('name');
    var val = jQuery(e.target).val();
    var parts = name.split('#');
    if(parts == null || parts.length == 0)
    {
        console.log('Address error.');
        return;
    }
    var o = builderGetByIdx(parts[0],struct);
    if(o['#'+parts[1]].charAt(1) == 'i')
        val = val.replace(/[^0-9]/g,'');
    o[parts[1]] = val;
    builderShowData(false);
}

function builderLoad()
{
    var str = jQuery('#stateholder').val();
    if(str == '')
    {
        buiderSetZero();
        builderShowData(true);
        return;
    }
    var obj = JSON.parse(decodeURI(window.atob(str)));
    struct = obj.struct;
    curr = builderGetByIdx(obj.curr_idx,struct);
    itemcounter = obj.itemcounter;
    tidx = builderMaxIdx(struct) + 1;
    builderShowData(true);
}

function builderLoadFrom()
{
    var str = '';
    if(!enable_speeformbuilder_load_definitions)
    {
        alert("The speedformbuilder can not access the CodKep server side to load definitions.\n"+
            "You may forget to set $site_config->enable_speeformbuilder_load_definitions = true\n");
        return;
    }

    if(jQuery("#codkep_def_name_id").val() == "")
        return;

    var requrl = builder_ajax_loaddef_url.replace('NODETYPENAME',jQuery("#codkep_def_name_id").val());

    jQuery.ajax({
        type: 'GET',
        url: requrl,
        error: function() {
            console.log('The ajax request failed: '+requrl);
            buiderSetZero();
            builderShowData(true);
            return;
        },
        success: function(data) {
            buiderSetZero();
            if(data.toString() == 'NOTFOUND')
            {
                alert('Error: Cannot found the requested definition!');
                builderShowData(true);
                return;
            }
            if(data.toString() == 'ERROR')
            {
                alert('Error: Unknown error occured on server side!');
                builderShowData(true);
                return;
            }

            var obj = JSON.parse(window.atob(data.toString()));
            struct = rebuildEditorStructure(obj.struct);
            tidx = builderMaxIdx(struct);
            itemcounter = builderMaxItemcounter(struct) + 10;
            curr = builderGetByIdx(tidx,struct);
            ++tidx;

            builderShowData(true);

            var str = window.btoa(encodeURI(JSON.stringify({'struct':struct,'curr_idx':curr['##i'],'itemcounter':itemcounter})));
            jQuery('#stateholder').val(str);

            alert("Warning: \nThe loaded data may differ from the php source code. \n" +
                  "The indentation, white spaces comments are completely stripped. \n" +
                  "Some other changes in code may appears, so always check your code before overwrite!");
        }
    });
}

function builderLoadAvailableFromNames()
{
    var str = '';

    if(!enable_speeformbuilder_load_definitions)
    {
        alert('The speedformbuilder can not access the CodKep server side to load definitions.'+
              'You may forget to set $site_config->enable_speeformbuilder_load_definitions = true;');
        return;
    }
    jQuery.ajax({
        type: 'GET',
        url: builder_ajax_loadnames_url,
        error: function() {
            console.log('The ajax request failed: '+builder_ajax_loadnames_url);
            return;
        },
        success: function(data) {
            if(data == '')
                return;

            var namearray = JSON.parse(window.atob(data.toString()));
            if(Array.isArray(namearray))
            {
                jQuery('#codkep_def_name_id').html("");
                jQuery.each(namearray, function(key, value) {
                    jQuery('#codkep_def_name_id')
                        .append(jQuery("<option></option>")
                            .attr("value",value)
                            .text(value));
                });
            }
        }
    });
}

function builderState()
{
    var str = window.btoa(encodeURI(JSON.stringify({'struct':struct,'curr_idx':curr['##i'],'itemcounter':itemcounter})));
    var h;
    h = 'Save this text to backup current state<br/>';
    h += '<textarea name="stateholder" id="stateholder" rows="15" cols="60">'+str+'</textarea>';
    h += '<br/>';
    h += '<button onclick="builderClear()">Clear</button>';
    h += '<button onclick="builderLoad()">Load state from text</button>';
    h += '<br/>';
    h += '<button onclick="builderLoadAvailableFromNames()">Query site definition names</button> : ';
    h += '<select name="codkep_def_name" id="codkep_def_name_id"></select>';
    h += '<button onclick="builderLoadFrom()">Load selected</button>';
    jQuery('#preview_area').html(h);
    editor_current = '';
}

function builderClear()
{
    jQuery('#stateholder').val('');
}

function calcVisibleColorOn(color)
{
    if(parseInt(color.substring(1,3),16) +
       parseInt(color.substring(3,5),16) +
       parseInt(color.substring(5),16) > 128*3)
        return '#000000';
    return '#ffffff';
}

function builderColorShow(e)
{
    var val = jQuery(e.target).val();
    var patt = new RegExp("^\#[0-9a-fA-F]{6}$");
    if(patt.test(val))
    {
        jQuery(e.target).css('background-color',val);
        jQuery(e.target).css('color',calcVisibleColorOn(val));
    }
}

function buiderSetZero()
{
    tidx = 1;
    struct = {};
    struct['##i'] = tidx++;
    struct['##l'] = 0;
    struct['##pi'] = 0;
    struct['##pn'] = '';
    struct['##t'] = '';
    curr = struct;
    saved_curr = '';
    editor_current = '';
    itemcounter = 10;
}


function rebuildEditorStructure(struct_pre)
{
    var newstruct = new Object();
    var state = new Object();
    state.index = 1;
    rebuildEditorStructure_inner(struct_pre,newstruct,state,0,0,'','');
    return newstruct;
}

function rebuildEditorStructure_inner(nold,nnew,state,level,parentindex,parentname,intype)
{
    var i;
    var cindex = state.index;
    state.index++;
    nnew['##i'] = cindex;
    nnew['##l'] = level;
    nnew['##pi'] = parentindex;
    nnew['##pn'] = parentname;

    if(level == 2 && 'type' in nold)
        intype = nold['type'];

    nnew['##t'] = intype;
    for(var key in nold)
    {
        var typespec = '';
        for(i=0;i<gral.length;++i)
            if(gral[i][0] == level && gral[i][1] == key &&
                (level < 3 || (gral[i][3].length == 0 || (jQuery.inArray(parentname,gral[i][3]) !== -1 ))) &&
                (level < 2 || (gral[i][7].length == 0 || (jQuery.inArray(intype,gral[i][7]) !== -1 )))
              )
            {
                typespec = gral[i][2];
                break;
            }
        if(typespec == '' && level == 1 && parentname == 'fields')
            typespec = 'f';
        if(typespec == '' && level == 3)
        {
            for(i=0;i<gral.length;++i)
                if(gral[i][0] == 3 &&
                    (gral[i][3].length == 0 || (jQuery.inArray(parentname,gral[i][3]) !== -1 )) &&
                    (gral[i][7].length == 0 || (jQuery.inArray(intype,gral[i][7]) !== -1 )) &&
                    (jQuery.inArray(gral[i][2],['sp','ip']) !== -1)
                )
                {
                    typespec = gral[i][2];
                    break;
                }
        }

        if(Array.isArray(nold[key]) || (nold[key] !== null && typeof nold[key] === 'object'))
        {
            nnew[key.toString()] = new Object();
            rebuildEditorStructure_inner(nold[key],nnew[key], state, level + 1, cindex, key, intype);
        }
        else
        {
            nnew[key] = nold[key];
        }
        nnew['#'+key] = typespec;
    }
}

function buiderStart()
{
    buiderSetZero();
    jQuery(document).on('input','.builder_editor_cell',builderEditorCellChanged);
    jQuery(document).on('change','.builder_select_cell',builderEditorCellChanged);
    jQuery(document).on('input','.input_html_color',builderColorShow);
}
//end.
