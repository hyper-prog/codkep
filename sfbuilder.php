<?php
/*  CodKep - Lightweight web framework core file
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 *
 *
 * SfBuilder module
 *  Required modules: core,sql,forms
 */

function hook_sfbuilder_boot()
{
    global $site_config;

    $site_config->enable_speeformbuilder_preview = false;
    $site_config->enable_speeformbuilder_load_definitions = false;
    $site_config->enable_speeformbuilder = false;
}

function hook_sfbuilder_defineroute()
{
    $i = [];
    $i[] = [
            "title" => "SpeedForm definition builder",
            "path" => "speedformbuilder",
            "callback" => "pc_speedform_builder",
            "theme" => "base_page",
           ];
    $i[] = [
            "path" => "speedformbuilder_preview_req",
            "callback" => "pc_speedform_builder_preview",
            "type" => "ajax",
           ];

    $i[] = [
            "path" => "speedformbuilder_nodetypedef_req/{nodename}",
            "callback" => "pc_speedform_builder_nodetypedef_req",
            "type" => "raw",
            'parameters' => [
                'nodename' => ['security' => 'text3ns',
                    'source' => 'url',
                    'required' => 'You have to specify the name of the nodetype',
                ],
            ],
    ];

    $i[] = [
        "path" => "speedformbuilder_nodetypedef_allnames",
        "callback" => "pc_speedform_builder_nodetypedef_allnames",
        "type" => "raw",
    ];
    return $i;
}

define('BTYPE_OPEN'   ,'o');
define('BTYPE_TEXT'   ,'ss');
define('BTYPE_INT'    ,'si');
define('BTYPE_SEL'    ,'sv');
define('BTYPE_SELB'   ,'sb');
define('BTYPE_KEYSVAL','sp');
define('BTYPE_KEYIVAL','ip');
define('BTYPE_FOPEN'  ,'f');

define('BSEC_FUNC'    ,0);
define('BSEC_VALID'   ,1);
define('BSEC_LOOK'    ,2);

function p_gral_item($level,$name,$type,$cat,$opts=[],$text='')
{
/* Array elements:
  0 - level,
  1 - name,
  2 - type BTYPE_...
  3 - Array available under this parents
  4 - Values if the field has selectable values
  5 - Category BSEC_...
  6 - Required 0,1
  7 - Array available under this type
  8 - Text
 */
    print "[$level,'$name','$type',";

    if(isset($opts['parents']))
    {
        $i = true;
        foreach($opts['parents'] as $p)
        {
            print ($i ? "[" : ",") . "'$p'";
            $i = false;
        }
        print "],";
    }
    else
        print "[],";

    if(isset($opts['values']))
    {
        $i = true;
        foreach($opts['values'] as $p)
        {
            print ($i ? "[" : ",") . "'$p'";
            $i = false;
        }
        print "],";
    }
    else
        print "[],";

    $req = 0;
    if(isset($opts['required']) && $opts['required'])
        $req = 1;
    print "$cat,$req,\n";

    if(isset($opts['types']))
    {
        $i = true;
        foreach($opts['types'] as $p)
        {
            print ($i ? "[" : ",") . "'$p'";
            $i = false;
        }
        print "],";
    }
    else
        print "[],";

    print "'$text'],\n";
}

function pc_speedform_builder()
{
    global $site_config;

    if(!$site_config->enable_speeformbuilder)
        return t("SpeedForm builder is disabled.<br/>".
                 "<small>The SpeedForm builder is disabled by default. ".
                  "You can enable it to add <br/><i>\$site_config->enable_speeformbuilder = true;</i> ".
                  " to your <i>site/_settings.php</i></small>");

    add_header('<script type="text/javascript" src="'.url('sys/sfbuilder.js').'"></script>');
    ob_start();

    print '<div style="margin:5px; padding:5px;">';
    print '<img style="float:left; margin: 2 8 2 2;" src="'.url('sys/images/cklogo_small.png').'"/>';
    print '<h3 style="float:left; margin:3px; padding:3px;">CodKep - Content type / SpeedForm builder</h3>';
    print '<div style="clear:both"></div>';
    print '</div>';

    print '<table>';
    print '<tr><td>';
        print '<button style="float:left;" onclick="builderUndo();">&lt;&lt;Undo</button>';
        print '<button style="float:right;" onclick="builderPreview();">Preview</button>';
        print '<button style="float:right;" onclick="builderEdit();">Edit</button>';
        print '<button style="float:right;" onclick="builderState();">State</button>';
    print '<div style="clear:both"></div>';
    print '$your_definition = <br/>';
    print '<textarea id="data" rows="16" cols="60" name="data" readonly>'."[\n";
    print '</textarea>';

    print '</td><td><div id="preview_area"></div></td></tr>';
    print '</table>';

    add_style(".required { color: #ff6666; /*background-color: #faaaaa; border: 3px solid #ff6666; */ }");
    add_style("#data { background-color: #dcdcdc; }");
    add_style(".graycell { background-color: #aaaaaa; }");
    add_style(".cyancell { background-color: #aaeeff; }");
    add_style(".greencell { background-color: #baffaa; }");
    add_style(".orangecell { background-color: #ffccaa; }");

    print '<table>';
    print '<tr><td class="orangecell" colspan="3"><div id="controls_pos">Empty definition</td></tr>';
    print '<tr><td colspan="3" align="center"><div id="warningfield"></td></tr>';
    print '<tr><td class="graycell"><i>Function / Structure</i></td>';
    print '<td class="cyancell"><i>Style / Look&Feel</i></td>';
    print '<td class="greencell"><i>Validator / Constraint</i></td></tr>';
    print '<tr>';
    print '<td class="graycell"><div id="controls_func"></div></td>';
    print '<td class="cyancell"><div id="controls_look"></div></td>';
    print '<td class="greencell"><div id="controls_valid"></div></td>';
    print '</tr>';
    print '</table>';

    print "<script>
            var struct;
            var curr;
            var saved_curr;
            var editor_current;
            var tidx;
            var itemcounter;
            var gral =
            [";

    p_gral_item(0,'name'         ,BTYPE_TEXT ,BSEC_FUNC,[],'Identifier name of the form object. It will be marked in classes, hooks.');
    p_gral_item(0,'table'        ,BTYPE_TEXT ,BSEC_FUNC,['required'=>true],'The SQL table name in the database');
    p_gral_item(0,'show'         ,BTYPE_SEL  ,BSEC_FUNC,['required'=>true,'values' => ['table','div']],'The formatter mode of the form. Html table or divs');
    p_gral_item(0,'fields'       ,BTYPE_OPEN ,BSEC_FUNC,[],'Add fields to the form object');
    p_gral_item(0,'classname'    ,BTYPE_TEXT ,BSEC_FUNC,[],'If you specify node type you can tell a classname, which a subclass of Node. The node instances created with this type.');
    p_gral_item(0,'access_earlyblock'    ,BTYPE_SELB ,BSEC_FUNC,['values' => ['true','false']],'If this value is true the system will block the first phase (loading to view) of a node before the a not permitted update/insert/delete request.');
    p_gral_item(0,'access_loadp_before_create_perm',BTYPE_SELB ,BSEC_FUNC,['values' => ['true','false']],'If this value is true the node create page will load the html parameters before the node_access check is executed.');
    p_gral_item(0,'access_loadp_before_update_perm',BTYPE_SELB ,BSEC_FUNC,['values' => ['true','false']],'If this value is true the node edit page will load the html parameters before the node_access check is executed.');
    p_gral_item(0,'view_callback',BTYPE_TEXT ,BSEC_FUNC,[],'If you specify node type you can tell a php fuction name here to view the node');
    p_gral_item(0,'view_phpfile' ,BTYPE_TEXT ,BSEC_FUNC,[],'If you specify node type you can tell a php file name here to view the node');
    p_gral_item(0,'rest_enabled' ,BTYPE_TEXT ,BSEC_FUNC,[],'You can enable REST API interface for this node type. You can write combinations of lowercase letters here to enable main functions. c - enable create, r - enable read, u - enable update, d - enable delete, l - enable list. You should type crudl here to enable all functions or leave blank to disable all.');
    p_gral_item(0,'disable_ui'   ,BTYPE_SELB ,BSEC_FUNC,['values' => ['false','true']],'You can disable the user interface of node operations. (Create/Read/Update/Delete) Do it when you only use node from REST and PHP api.');

    p_gral_item(0,'color'        ,BTYPE_TEXT ,BSEC_LOOK,[],'The bgcolor of the entire table');
    p_gral_item(0,'before'       ,BTYPE_TEXT ,BSEC_LOOK,[],'Printed before the whole form');
    p_gral_item(0,'after'        ,BTYPE_TEXT ,BSEC_LOOK,[],'Printed after the whole form');
    p_gral_item(0,'table_class'  ,BTYPE_TEXT ,BSEC_LOOK);
    p_gral_item(0,'table_style'  ,BTYPE_TEXT ,BSEC_LOOK);
    p_gral_item(0,'table_border' ,BTYPE_TEXT ,BSEC_LOOK);
    p_gral_item(0,'div_class'    ,BTYPE_TEXT ,BSEC_LOOK);
    p_gral_item(0,'div_c_afterv' ,BTYPE_TEXT ,BSEC_LOOK);
    p_gral_item(0,'div_c_afterl' ,BTYPE_TEXT ,BSEC_LOOK);
    p_gral_item(0,'collapsable_fieldsets',BTYPE_SELB ,BSEC_LOOK,['values' => ['true','false']]);

    p_gral_item(1,'- NEW FIELD -',BTYPE_FOPEN,BSEC_FUNC);

    p_gral_item(2,'sql'          ,BTYPE_TEXT,BSEC_FUNC,['required'=>true],'The name of the sql row holds this data field. Act like an identifier in this table');
    p_gral_item(2,'text'         ,BTYPE_TEXT,BSEC_FUNC,[],'The describe text of the field. This will be displayed to the user');
    p_gral_item(2,'type'         ,BTYPE_SEL ,BSEC_FUNC,['required'=>true,'values' => speedform_available_types()],'The visual and sql type of the field');
    p_gral_item(2,'values'       ,BTYPE_OPEN,BSEC_FUNC,['required'=>true,'types' => ['txtselect','numselect','txtradio','numradio']],'The possible values');

    p_gral_item(2,'default'      ,BTYPE_TEXT,BSEC_FUNC,['types' => ['keys','keyn','static','rotext','smalltext','static','password','float',
                                                                     'largetext','txtselect','txtradio','txtselect_intrange','date',
                                                                     'dateu','timestamp_mod','timestamp_create','sqlschoose','submit']]);
    p_gral_item(2,'default'      ,BTYPE_INT ,BSEC_FUNC,['types' => ['number','numselect','numradio','numselect_intrange','sqlnchoose']]);
    p_gral_item(2,'default'      ,BTYPE_SELB,BSEC_FUNC,['types' => ['check'],'values' => ['true','false']]);

    p_gral_item(2,'start'        ,BTYPE_INT ,BSEC_FUNC,['required'=>true,'types' => ['txtselect_intrange','numselect_intrange']]);
    p_gral_item(2,'end'          ,BTYPE_INT ,BSEC_FUNC,['required'=>true,'types' => ['txtselect_intrange','numselect_intrange']]);

    p_gral_item(2,'link'         ,BTYPE_TEXT,BSEC_FUNC,['types' => ['keys','keyn','rotext']]);

    p_gral_item(2,'sql_sequence_name',BTYPE_TEXT,BSEC_FUNC,['types' => ['keys','keyn']],'Some database system does not return correct value '.
                                                                                        'after insert with PDO::lastInsertId() when the key is generated '.
                                                                                        'by a sequence (Pgsql). That case you have to put the '.
                                                                                        'sequence name here and may use keyprefix,keysuffix options.');
    p_gral_item(2,'mysql_sql_sequence_name',BTYPE_TEXT,BSEC_FUNC,['types' => ['keys','keyn']],'Same as sql_sequence name, but only works on mysql.');
    p_gral_item(2,'pgsql_sql_sequence_name',BTYPE_TEXT,BSEC_FUNC,['types' => ['keys','keyn']],'Same as sql_sequence name, but only works on pgsql.');

    p_gral_item(2,'userdata'     ,BTYPE_SEL,BSEC_FUNC,['types' => ['modifier_user'],'values' => ['fullname','login']]);
    p_gral_item(2,'keyprefix'    ,BTYPE_TEXT,BSEC_FUNC,['types' => ['keys']],'This text will be automatically prepended to the sql sequence '.
                                                                             'generated number returned by PDO::lastInsertId(). '.
                                                                             'Only use if you have special id field.');
    p_gral_item(2,'keysuffix'    ,BTYPE_TEXT,BSEC_FUNC,['types' => ['keys']],'This text will be automatically appended to the sql sequence '.
                                                                             'generated number returned by PDO::lastInsertId(). '.
                                                                             'Only use if you have special id field.');

    p_gral_item(2,'in_mode'      ,BTYPE_SEL ,BSEC_FUNC,['types' => ['submit'],'values' => ['insert','update','delete','select']],
                                                                            'Restrict the rendering of the submit typed fields to a specific action.');

    p_gral_item(2,'row'          ,BTYPE_INT ,BSEC_FUNC,['types' => ['largetext']]);
    p_gral_item(2,'col'          ,BTYPE_INT ,BSEC_FUNC,['types' => ['largetext']]);
    p_gral_item(2,'autoupdate'   ,BTYPE_SEL ,BSEC_FUNC,['types' => ['timestamp_mod'],'values' => ['','disable']]);

    p_gral_item(2,'connected_table'   ,BTYPE_TEXT,BSEC_FUNC,['required'=>true,'types' => ['sqlnchoose','sqlschoose']]);
    p_gral_item(2,'keyname'           ,BTYPE_TEXT,BSEC_FUNC,['required'=>true,'types' => ['sqlnchoose','sqlschoose']]);
    p_gral_item(2,'showpart'          ,BTYPE_TEXT,BSEC_FUNC,['required'=>true,'types' => ['sqlnchoose','sqlschoose']]);
    p_gral_item(2,'where_orderby_part',BTYPE_TEXT,BSEC_FUNC,['required'=>true,'types' => ['sqlnchoose','sqlschoose']]);

    p_gral_item(2,'optional'      ,BTYPE_SEL,BSEC_FUNC,['values'=>['yes','no'],'types' => ['sqlnchoose','sqlschoose','txtselect','numselect']],'If the optional=yes '.
                                            'is set the field can accept empty value. '.
                                            'The user can reset the value with a button. '.
                                            'This case the sql value will set NULL. Otherwise only a select box will appears so the user '.
                                            'have to select a value.');

    p_gral_item(2,'par_sec'       ,BTYPE_TEXT,BSEC_FUNC,[],'Set custom parameter security class other than default');
    p_gral_item(2,'htmlname'      ,BTYPE_TEXT,BSEC_FUNC,[],'Set special html form and parameter name other than default sql name. Use this to avoid html parameter name collisions.');
    p_gral_item(2,'sqlcreatetype' ,BTYPE_TEXT,BSEC_FUNC,[],'You can set a custom sql type on create the table');

    p_gral_item(2,'check_regex'   ,BTYPE_OPEN,BSEC_VALID);
    p_gral_item(2,'check_noempty' ,BTYPE_TEXT,BSEC_VALID);
    p_gral_item(2,'maximum'       ,BTYPE_INT ,BSEC_VALID,['types' => ['number','float','txtselect_intrange','numselect_intrange']]);
    p_gral_item(2,'minimum'       ,BTYPE_INT ,BSEC_VALID,['types' => ['number','float','txtselect_intrange','numselect_intrange']]);

    p_gral_item(2,'filetypes'     ,BTYPE_TEXT,BSEC_VALID,['types' => ['file']],'The allowed mime types of file separated by ; sign.');

    p_gral_item(2,'container'     ,BTYPE_SEL ,BSEC_FUNC,['required'=>true,'types' => ['file'],'values'=>['public','secure']]);
    p_gral_item(2,'subdir'        ,BTYPE_TEXT,BSEC_FUNC,['types' => ['file']]);

    p_gral_item(2,'ondelete'      ,BTYPE_SEL  ,BSEC_FUNC,['types' => ['file'],'values' => ['keep']]);

    p_gral_item(2,'skip'          ,BTYPE_SEL ,BSEC_FUNC,['values' => ['all','visual','sql','modify','select','update','insert','exceptinsert','exceptupdate','exceptdelete']]);
    p_gral_item(2,'readonly'      ,BTYPE_SELB,BSEC_FUNC,['values' => ['true','false']]);

    p_gral_item(2,'color'         ,BTYPE_TEXT ,BSEC_LOOK,[],'The color of the table row of the field (only in table mode)');
    p_gral_item(2,'before'        ,BTYPE_TEXT ,BSEC_LOOK,[],'Text displayed before the table row');
    p_gral_item(2,'prefix'        ,BTYPE_TEXT ,BSEC_LOOK,[],'Text displayed immediately before value (always)');
    p_gral_item(2,'neval_prefix'  ,BTYPE_TEXT ,BSEC_LOOK,[],'Text displayed immediately before value if the value is not empty');
    p_gral_item(2,'neval_suffix'  ,BTYPE_TEXT ,BSEC_LOOK,[],'Text displayed immediately after value if the value is not empty');
    p_gral_item(2,'suffix'        ,BTYPE_TEXT ,BSEC_LOOK,[],'Text displayed immediately after value (always)');
    p_gral_item(2,'after'         ,BTYPE_TEXT ,BSEC_LOOK,[],'Text displayed after the table row');

    p_gral_item(2,'formatters'    ,BTYPE_SEL  ,BSEC_LOOK,['values' => ['all','before','after','none']]);

    p_gral_item(2,'centered'      ,BTYPE_SELB ,BSEC_LOOK,['values' => ['true','false']]);
    p_gral_item(2,'no_rest'       ,BTYPE_TEXT,BSEC_FUNC,[],'You can disable/bypass this field from REST API actions. You can write combinations of lowercase letters here to bypass this field from main functions. c - create, r - read, u - update, a - all.');
    p_gral_item(2,'script'        ,BTYPE_TEXT ,BSEC_FUNC);
    p_gral_item(2,'converter'     ,BTYPE_TEXT ,BSEC_FUNC,[],'Sets a callback function which convert the form value to db value');

    p_gral_item(2,'form_options'  ,BTYPE_OPEN,BSEC_LOOK,[],'Options for form element (class,style,id,onclick,before,after,size,maxlength)');

    p_gral_item(2,'line_class'    ,BTYPE_TEXT ,BSEC_LOOK);
    p_gral_item(2,'title_class'   ,BTYPE_TEXT ,BSEC_LOOK);
    p_gral_item(2,'value_class'   ,BTYPE_TEXT ,BSEC_LOOK);
    p_gral_item(2,'hide'          ,BTYPE_SELB ,BSEC_LOOK,['values' => ['true','false']]);

    p_gral_item(2,'fieldset'      ,BTYPE_TEXT ,BSEC_LOOK,[],'If this text is set, the field belongs a html fieldset tag. (Only if show=div)');
    p_gral_item(2,'fieldset_text' ,BTYPE_TEXT ,BSEC_LOOK,[],'If the field is belongs a fieldset and the field is the first (opener tag) of the fieldset this value will be the title of the fieldset. (Only if show=div)');
    p_gral_item(2,'fieldset_body_extraclass'   ,BTYPE_SEL ,BSEC_LOOK,['values' => ['','collapsed']]);
    p_gral_item(2,'description'   ,BTYPE_TEXT ,BSEC_LOOK,[],'Longer description text put after the value line. (Only if show=div)');

    p_gral_item(3,'intkey-strval'   ,BTYPE_KEYIVAL ,BSEC_FUNC,['parents' => ['values'],'types' => ['numselect','numradio']]);
    p_gral_item(3,'strkey-strval'   ,BTYPE_KEYSVAL ,BSEC_FUNC,['parents' => ['values'],'types' => ['txtselect','txtradio']]);
    p_gral_item(3,'regex-errortext' ,BTYPE_KEYSVAL ,BSEC_VALID,['parents' => ['check_regex']]);

    p_gral_item(3,'class'         ,BTYPE_TEXT ,BSEC_LOOK,['parents' => ['form_options']],"The class attribute of the html tag");
    p_gral_item(3,'style'         ,BTYPE_TEXT ,BSEC_LOOK,['parents' => ['form_options']],"The style (CSS) attribute of the html tag");
    p_gral_item(3,'placeholder'   ,BTYPE_TEXT ,BSEC_LOOK,['parents' => ['form_options']],"The placeholder string, value hint");
    p_gral_item(3,'required'      ,BTYPE_SELB ,BSEC_LOOK,['parents' => ['form_options'],'values' => ['true','false']],"If this option is true, the field will receive the required and aria-required attributes");
    p_gral_item(3,'onclick'       ,BTYPE_TEXT ,BSEC_FUNC,['parents' => ['form_options']],"The content of the html onlick attribute");
    p_gral_item(3,'id'            ,BTYPE_TEXT ,BSEC_FUNC,['parents' => ['form_options']],"The html id of the tag");
    p_gral_item(3,'before'        ,BTYPE_TEXT ,BSEC_LOOK,['parents' => ['form_options']],"Raw string which prepended before the html tag");
    p_gral_item(3,'after'         ,BTYPE_TEXT ,BSEC_LOOK,['parents' => ['form_options']],"Raw string which appended after the html tag");
    p_gral_item(3,'size'          ,BTYPE_INT  ,BSEC_LOOK,['parents' => ['form_options']],"The size attribute of the html input tag");
    p_gral_item(3,'maxlength'     ,BTYPE_INT  ,BSEC_LOOK,['parents' => ['form_options']],"The maxlength attribute of the html input tag");
    p_gral_item(3,'rawattributes' ,BTYPE_TEXT ,BSEC_LOOK,['parents' => ['form_options']],"This is a raw string appended into the attribute section of the html tag .");

    $builder_ajax_preview_url = url('speedformbuilder_preview_req');
    $builder_ajax_loaddef_url = url('speedformbuilder_nodetypedef_req/NODETYPENAME');
    $builder_ajax_loadnames_url = url('speedformbuilder_nodetypedef_allnames');
    $builder_redcross_url = url('/sys/images/small_red_cross.png');
    $builder_luas_url = url('/sys/images/luas.png');
    $builder_ldas_url = url('/sys/images/ldas.png');
    $builder_up_url = url('/sys/images/up.png');
    $builder_down_url = url('/sys/images/down.png');
    print "];
    var builder_ajax_preview_url = '$builder_ajax_preview_url';
    var builder_ajax_loaddef_url = '$builder_ajax_loaddef_url';
    var builder_ajax_loadnames_url = '$builder_ajax_loadnames_url';
    var builder_redcross_url = '$builder_redcross_url';
    var builder_luas_url = '$builder_luas_url';
    var builder_ldas_url = '$builder_ldas_url';
    var builder_up_url = '$builder_up_url';
    var builder_down_url = '$builder_down_url';
    var enable_speeformbuilder_load_definitions ='$site_config->enable_speeformbuilder_load_definitions';

    $(document).ready(function() {
        buiderStart();
        buiderUpdateControlPanel();
    });
    </script>";
    return ob_get_clean();
}

function pc_speedform_builder_preview()
{
    global $site_config;

    if(!$site_config->enable_speeformbuilder_preview)
    {
        $html = t("<strong>The preview is disabled</strong><br/>".
                  "<small>The preview function is disabled by default.<br/>".
                  "You can enable it to add <br/><i>\$site_config->enable_speeformbuilder_preview = true;</i><br/>".
                  " to your <i>site/_settings.php</i></small>");
        ajax_add_html("#preview_area",$html);
        return;
    }

    par_def('json_send','free','post');
    $data = par('json_send');


    $def = json_decode($data,true);
    if($def === NULL)
    {
        ajax_add_html("#preview_area",'No preview');
        return;
    }

    correct_bool_values($def);
    remove_technical_items($def);

    $speedform = new SpeedForm($def);
    $form = $speedform->generate_form('all');
    $form->action_ajax('preview_submit');
    $predata = $form->get();

    ajax_add_html("#preview_area",$predata);
}

function pc_speedform_builder_nodetypedef_req()
{
    global $sys_data;
    global $site_config;
    global $datadef_repository;

    if(!$site_config->enable_speeformbuilder_load_definitions)
        return '';

    $def = NULL;
    if(array_key_exists(par('nodename'),$sys_data->node_types))
    {
        $def = $sys_data->node_types[par('nodename')];
    }

    if(array_key_exists(par('nodename'),$sys_data->node_otypes))
    {
        $c = $sys_data->node_otypes[par('nodename')]['defineclass'];
        $rc = new ReflectionClass($c);
        if(!$rc->isSubclassOf('Node'))
        {
            return 'ERROR';
        }
        $def = $rc->getStaticPropertyValue('definition');
    }
    if(array_key_exists(par('nodename'),$datadef_repository))
    {
        $c = $datadef_repository[par('nodename')];
        $def = call_user_func($c);
        if(!isset($def['fields']))
            return 'ERROR'; //This probably a DynTable which is unsupported by SpeeformBuilder
    }
    if($def == NULL)
        return 'NOTFOUND';

    $send = ['struct' => $def,'curr_idx' => 0,'itemcounter' => 0];
    return base64_encode(json_encode($send));
}

function pc_speedform_builder_nodetypedef_allnames()
{
    global $sys_data;
    global $site_config;
    global $datadef_repository;

    if(!$site_config->enable_speeformbuilder_load_definitions)
        return '';

    $defnames = array_merge(array_keys($sys_data->node_types),
                            array_keys($sys_data->node_otypes),
                            array_keys($datadef_repository));
    return base64_encode(json_encode($defnames));
}

function remove_technical_items(&$d)
{
    foreach($d as $k => $v)
    {
        if($k[0] == '#')
            unset($d[$k]);
        else
            if(is_array($d[$k]))
                remove_technical_items($d[$k]);
    }
}

function correct_bool_values(&$d)
{
    foreach($d as $k => $v)
    {
        if(!is_array($v) && substr($k,0,1) == '#' && $v === 'sb')
        {
                $rkey = substr($k,1);
                $val = $d[$rkey];
                if(strtolower($val) == 'true' || $val == '1')
                    $d[$rkey] = true;
                else
                    $d[$rkey] = false;
        }
        if(is_array($d[$k]))
            correct_bool_values($d[$k]);
    }
}

function hook_sfbuilder_introducer()
{
    global $user;
    if(!$user->auth || $user->role != ROLE_ADMIN)
        return ['SpeedForm builder' => ''];
    return ['SpeedForm builder' => l('Speedform builder','speedformbuilder')];
}

//end code.
