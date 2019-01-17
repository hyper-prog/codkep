<?php
/*  CodKep - Lightweight web framework core file
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 *
 * Activity module
 *  Required modules: core,sql,user,forms
 */

define('ACTIVITY_ACCESS_IGNORE',0);
define('ACTIVITY_ACCESS_ALLOW',1);
define('ACTIVITY_ACCESS_DENY',2);

function hook_activity_boot()
{
    global $site_config;
    $site_config->comment_containers = [];
    $site_config->poll_containers    = [];

    $site_config->comment_delete_own_until_sec = 60*60; //1 hour
    $site_config->acitvity_comment_block_css_class = 'commentblk_default_style';
    $site_config->acitvity_comment_renderer_callback = 'codkep_render_commentblock';
    $site_config->activity_poll_main_css_class = 'ckpoll_default_style';
    $site_config->activity_poll_show_horizontal = false;
}

function hook_activity_before_start()
{
    add_css_file('/sys/activity.css');
}

function register_comment_container($containername)
{
    global $site_config;
    if(check_str($containername,'text0nsne'))
    {
        if(!in_array($containername, $site_config->comment_containers))
            array_push($site_config->comment_containers, $containername);
        return;
    }
    load_loc('error','Illegal comment container class (text0nsne allowed)','Internal activity module error');
}

function register_poll_container($containername)
{
    global $site_config;
    if(check_str($containername,'text0nsne'))
    {
        if(!in_array($containername, $site_config->poll_containers))
            array_push($site_config->poll_containers, $containername);
        return;
    }
    load_loc('error','Illegal poll container class (text0nsne allowed)','Internal activity module error');
}

function hook_activity_defineroute()
{
    return [
        ['path' => 'addnewcommentajax'        ,'callback' => 'addnewcomment_comment' ,'type' => 'ajax'],
        ['path' => 'delcommentajax'           ,'callback' => 'delcomment_comment'    ,'type' => 'ajax'],
        ['path' => 'votepollajax'             ,'callback' => 'aj_addvote_poll'       ,'type' => 'ajax'],
        ['path' => 'showpoll/{pollname}/{id}' ,'callback' => 'showpollpage_callback' ],
    ];
}

function comment_access($container,$refid,$op,$account)
{
    if(!in_array($op,['view','add']))
        return ACTIVITY_ACCESS_DENY;
    $n = run_hook('comment_access',$container,$refid,$op,$account);

    if(in_array( ACTIVITY_ACCESS_DENY,$n))
        return ACTIVITY_ACCESS_DENY;
    if(in_array( ACTIVITY_ACCESS_ALLOW,$n))
        return ACTIVITY_ACCESS_ALLOW;

    //Default comment permissions:
    // Allows everything for admins
    if($account->role == ROLE_ADMIN)
        return ACTIVITY_ACCESS_ALLOW;
    // Allows view for everyone. (You can disable by send DENY from a hook.)
    if($op == 'view')
        return ACTIVITY_ACCESS_ALLOW;
    return ACTIVITY_ACCESS_DENY;
}

function get_comment_block($container,$refid)
{
    global $user;
    global $user_module_settings;
    global $site_config;

    if(!in_array($container,$site_config->comment_containers))
        return '';
    if(comment_access($container,$refid,'view',$user) != ACTIVITY_ACCESS_ALLOW)
        return '';

    $usrtable = $user_module_settings->sql_tablename;
    $usrnamecol = $user_module_settings->sql_name_column;
    ob_start();
    $r = sql_exec("SELECT cid,cmmt.uid,created,body,ut.$usrnamecol AS uname
                   FROM comment_$container AS cmmt
                   INNER JOIN $usrtable AS ut ON ut.uid = cmmt.uid
                   WHERE ref = :refid
                   ORDER BY created",
            [':refid' => $refid]);
    $count = 0;

    print '<div class="'.$site_config->acitvity_comment_block_css_class.'">'; //changeable upper container css class
    print "<div id=\"fullcommentarea_$refid\" class=\"comment_module_fullc_area fullcommentblock_width\">";
    print "<div id=\"showcommentarea_$refid\" class=\"comment_module_comment_area\">";
    $t_curr = time();
    while($rec = $r->fetch())
    {
        $erasable = false;
        if($user->uid == $rec['uid'])
        {
            $t_created = (new DateTime($rec['created']))->format('U');
            if(($t_curr-$t_created) < $site_config->comment_delete_own_until_sec  &&
               comment_access($container,$refid,'add',$user) == ACTIVITY_ACCESS_ALLOW )
                $erasable = true;
        }
        print call_user_func_array($site_config->acitvity_comment_renderer_callback,
                    [$container,$rec['cid'],
                     $rec['uname'],
                     $rec['created'],
                     $rec['body'],
                     $erasable]);
        ++$count;
    }
    print "</div>"; // .comment_module_comment_area #showcommentarea_$refid

    if(comment_access($container,$refid,'add',$user) == ACTIVITY_ACCESS_ALLOW)
    {
        $f = new HtmlForm('addcomment');
        $f->opts(['class' => 'cadder_form_cmts']);
        $f->action_ajax("addnewcommentajax");
        $f->hidden('ref', $refid);
        $f->hidden('cont', $container);
        $f->text('t0', '<div class="cadder_visible_parts addcommentblock_width">');
        $f->textarea('commenttext', '', 2, 40, ['id' => 'new_comment_area', 'class' => 'new_comment_body_box']);
        $f->input('submit', 'send', t("Send"), ['class' => 'new_comment_send_btn']);
        $f->text('t1', '</div>');
        print '<div class="addnew_c_form">' . $f->get() . '</div>';
    }
    print "</div>"; //.comment_module_fullc_area #fullcommentarea_$refid
    print '</div>'; //changeable upper container css class
    return ob_get_clean();
}

function addnewcomment_comment()
{
    global $user;
    global $site_config;

    form_source_check();
    par_def('commenttext','text5');
    par_def('ref','number0');
    par_def('cont','text0nsne');

    $container = par('cont');
    $refid = par('ref');
    $bodytext = par('commenttext');

    if(!in_array($container,$site_config->comment_containers))
        return;
    if($bodytext == '')
        return;
    if(comment_access($container,$refid,'add',$user) != ACTIVITY_ACCESS_ALLOW)
        return;

    global $db;
    sql_exec("INSERT INTO comment_$container(ref,uid,created,body)
              VALUES(:refid,:uid,".sql_t('current_timestamp').",:bodytxt)",
                [':refid' => $refid,
                 ':uid' => $user->uid,
                 ':bodytxt' => $bodytext ] );
    $cid = $db->sql->lastInsertId('cid');
    ajax_add_append("#showcommentarea_$refid",
        call_user_func_array($site_config->acitvity_comment_renderer_callback,
                         [$container,$cid,$user->name,t('Just now'),
                          $bodytext,$site_config->comment_delete_own_until_sec > 0]));
    ajax_add_val('#new_comment_area','');
}

function delcomment_comment()
{
    global $user;
    global $site_config;

    if($user->auth)
    {
        par_def('id', 'number0');
        $cid = par('id');
        par_def('cont', 'text0nsne');
        $container = par('cont');

        if(!in_array($container, $site_config->comment_containers))
            return;

        $r = sql_exec_fetchN("SELECT cid,cmmt.uid,created,ref
                              FROM comment_$container AS cmmt
                              WHERE cid = :cidp",
                            [':cidp' => $cid]);
        if(!isset($r) || $r == null)
            return;
        if($r['uid'] != $user->uid)
            return;
        if(comment_access($container,$r['ref'],'add',$user) != ACTIVITY_ACCESS_ALLOW)
            return;
        $t_curr = time();
        $t_created = (new DateTime($r['created']))->format('U');
        if(($t_curr-$t_created) >= $site_config->comment_delete_own_until_sec)
            return;
        sql_exec("DELETE FROM comment_$container
                  WHERE cid = :cidp",
                  [':cidp' => $cid]);
        ajax_add_remove('#cmt_'.$cid.'_idx');
    }
}

function codkep_render_commentblock($cont,$cid,$name,$created,$text,$deletelink)
{
    ob_start();
    print '<div class="commentitem" id="cmt_'.$cid.'_idx">';
    print '<div class="commentheader">';
    print '<div class="commentername">'.$name.'</div>';

    if($deletelink)
        print l("✕",'delcommentajax',['class' => 'use-ajax comment_delete_ajax_lnk',
                'title' => t('Delete this comment')],
            ['cont' => $cont,'id' => $cid]);
    print '<div class="timeofcomm">'.$created.'</div>';
    print '</div>'; //.commentheader
    print '<div class="bodytext">'.$text.'</div>';

    print "</div>";
    return ob_get_clean();
}

// Poll codes -----------------------------------------------------------

function register_poll($pollname,$container,$title,$choices,$default = '',$date_start = '',$date_end = '')
{
    $cnt = db_query('poll_parameters')
        ->counting()
        ->cond_fv('name',$pollname,'=')
        ->execute_to_single();
    if($cnt > 0)
        return false;

    $ii = db_insert('poll_parameters')
            ->set_fv_a([
                'name' => $pollname,
                'container' => $container,
                'titletext' => $title,
                'defidx' => $default
            ]);
    if($date_start != '')
    {
        if(!check_str($date_start,'isodate'))
            throw new Exception("The date_start parameter of register_poll() has wrong format. Only isodate accepted");
        $ii->set_fv('dstart',$date_start);
    }
    if($date_end != '')
    {
        if(!check_str($date_end,'isodate'))
            throw new Exception("The date_end parameter of register_poll() has wrong format. Only isodate accepted");
        $ii->set_fv('dend',$date_end);
    }

    sql_transaction();
    $ii->execute();
    foreach($choices as $i => $v)
    {
        if(strlen($i) > 5)
        {
            sql_rollback();
            throw new Exception("The choice index defined with register_poll() only accepts length <= 5 character");
        }
        db_insert('poll_choices')
            ->set_fv_a(['name' => $pollname,'choice_idx' => $i,'choice_text' => $v])
            ->execute();
    }
    sql_commit();
    run_hook('poll_registered',$pollname);
    return true;
}

function unregister_poll($pollname)
{
    sql_transaction();
    $container = db_query('poll_parameters')->get('container')->cond_fv('name',$pollname,'=')->execute_to_single();
    db_delete('poll_parameters')
        ->cond_fv('name',$pollname,'=')
        ->execute();
    db_delete('poll_choices')
        ->cond_fv('name',$pollname,'=')
        ->execute();
    db_delete('pollcont_' . $container)
        ->cond_fv('name',$pollname,'=')
        ->execute();
    sql_commit();
    run_hook('poll_unregistered',$pollname);
}

function get_poll_list($container = '',$now_active = false,$fromdate = '',$todate = '')
{
    $q = db_query('poll_parameters')
        ->get_a(['name','container','titletext','defidx','dstart','dend']);
    if($container != '')
        $q->cond_fv('container',$container,'=');
    if($now_active)
    {
        $q->cond(cond('or')->fnull('dstart')->fe('dstart',sql_t('current_timestamp'),'<=',['efunction' => 'date']));
        $q->cond(cond('or')->fnull('dend')  ->fe('dend'  ,sql_t('current_timestamp'),'>=',['efunction' => 'date']));
    }
    if($fromdate != '' && check_str($fromdate,'isodate'))
        $q->cond(cond('or')->fnull('dstart')->fv('dstart',$fromdate,'>=',['vfunction' => 'date']));
    if($todate != '' && check_str($todate,'isodate'))
        $q->cond(cond('or')->fnull('dend')  ->fv('dend',$todate,'<=',['vfunction' => 'date']));
    $r = $q->execute_to_arrays();
    return $r;
}

function get_poll_parameters_by_pollname($pollname)
{
    $rp = db_query('poll_parameters')
        ->get_a(['container','titletext','defidx','dstart','dend'])
        ->cond_fv('name',$pollname,'=')
        ->execute_and_fetch();
    if(!isset($rp['container']))
        return null;
    return $rp;
}

function get_poll_parametervalue_by_pollname($pollname,$what)
{
    return db_query('poll_parameters')
        ->get($what)
        ->cond_fv('name',$pollname,'=')
        ->execute_to_single();
}

function get_poll_choices_array_by_pollname($pollname)
{
    $rc = db_query('poll_choices')
        ->get_a(['choice_idx','choice_text'])
        ->cond_fv('name',$pollname,'=')
        ->execute_to_arrays();

    $chs = [];
    foreach($rc as $rcc)
        $chs[$rcc['choice_idx']] = $rcc['choice_text'];
    return $chs;
}

function poll_is_user_voted($pollname,$container,$id,$uid)
{
    $cnt = db_query('pollcont_'.$container)
        ->counting()
        ->cond_fv('name',$pollname,'=')
        ->cond_fv('ref',$id,'=')
        ->cond_fv('uid',$uid,'=')
        ->execute_to_single();
    if($cnt > 0)
        return true;
    return false;
}

function poll_is_valid_by_date($date_start,$date_end)
{
    $currentdate = date('Y-m-d');
    if($date_start != '' && $currentdate < $date_start)
        return false;
    if($date_end   != '' && $currentdate > $date_end  )
        return false;
    return true;
}

function get_poll_results($container,$pollname,$id)
{
    $choices = get_poll_choices_array_by_pollname($pollname);
    $ns = sql_exec_fetchAll("SELECT COUNT(pid) as cnt,choice
                             FROM pollcont_$container
                             WHERE name=:pollname AND ref=:refid
                             GROUP BY choice",[':pollname' => $pollname,':refid' => $id]);
    $all = 0;
    foreach($ns as $nss)
        $all += $nss['cnt'];
    $results = [];
    foreach($choices as $idx => $text)
    {
        $cnt = 0;
        foreach($ns as $nss)
            if($nss['choice'] == $idx)
            {
                $cnt = $nss['cnt'];
                break;
            }
        $results[$text] = [
            'all' => $all,
            'count' => $cnt,
            'percent' => ($all == 0 ? 0 : intval(($cnt * 100) / $all)),
        ];
    }
    return $results;
}

function get_poll_block($pollname,$id,$maincssclass = '')
{
    global $user;
    global $site_config;

    if(!$user->auth)
        return '';

    $mcssclass = $site_config->activity_poll_main_css_class;
    if($maincssclass != '')
        $mcssclass = $maincssclass;

    $rp = get_poll_parameters_by_pollname($pollname);
    if($rp == null)
        return '';

    if(poll_access($pollname,$id,'view',$user) != ACTIVITY_ACCESS_ALLOW &&
       poll_access($pollname,$id,'add',$user) != ACTIVITY_ACCESS_ALLOW )
        return '';

    ob_start();
    print "<div class=\"$mcssclass\">";
    print '<div class="ckpoll_title">'.$rp['titletext'].'</div>';
    print '<div class="ckpoll_body_' . $pollname . '_' . $id . ' ckpoll_mbody">';
    print get_poll_block_inner($rp['container'],$pollname,$id,$rp['dstart'],$rp['dend']);
    print '</div>'; // .ckpoll_body
    print '</div>'; // .$mcssclass
    return ob_get_clean();
}

function get_poll_resultblock($results)
{
    global $site_config;
    $t = new HtmlTable('poll_result_table');
    if($site_config->activity_poll_show_horizontal)
    {
        foreach($results as $text => $values)
            $t->cell($text,['type' => 'uni',"horizontal" => "center"]);
        $t->nrow();
        foreach($results as $text => $values)
            $t->cell('<div class="ckpoll_innerbar" style="height: '.$values['percent'].'%;"></div>',
                ['class' => 'ckpoll_outbar']);
        $t->nrow();
        foreach($results as $text => $values)
            $t->cell($values['percent'] . '%<br/> <small>(' . $values['count'] . ')</small>',
                ['type' => 'uni',"horizontal" => "center"]);
    }
    else
    {
        foreach($results as $text => $values)
        {
            $t->cell($text);
            $t->cell('<div class="ckpoll_innerbar" style="width: ' . $values['percent'] . '%;"></div>',
                ['class' => 'ckpoll_outbar']);
            $t->cell($values['percent'] . '% <small>(' . $values['count'] . ')</small>');
            $t->nrow();
        }
    }

    return '<div class="ckpoll_result">' . $t->get() . '</div>';
}

function get_poll_block_inner($container,$pollname,$id,$date_start = '',$date_end = '')
{
    global $user;

    if(poll_is_user_voted($pollname,$container,$id,$user->uid))
    {
        if(poll_access($pollname,$id,'view',$user) != ACTIVITY_ACCESS_ALLOW)
            return '<div class="ckpoll_msg">' . t("You've already cast your vote.") . '</div>';

        $results = get_poll_results($container,$pollname,$id);
        return get_poll_resultblock($results);
    }

    if(!poll_is_valid_by_date($date_start,$date_end))
    {
        if(poll_access($pollname,$id,'view',$user) != ACTIVITY_ACCESS_ALLOW)
            return '<div class="ckpoll_msg">' . t("The vote is not active.") . '</div>';

        $results = get_poll_results($container,$pollname,$id);
        return get_poll_resultblock($results);
    }

    if(poll_access($pollname,$id,'add',$user) != ACTIVITY_ACCESS_ALLOW)
        return '';

    $f = new HtmlForm('form_poll_' . $pollname . '_' . $id);
    $f->action_ajax('votepollajax');

    $default = get_poll_parametervalue_by_pollname($pollname,'defidx');
    $choices = get_poll_choices_array_by_pollname($pollname);
    $name = 'poll_' . $pollname . '_' . $id;
    $f->select('radio',$name,$default,$choices,
        ['id' => $name .'_'. rand(100,999),
         'itemprefix' => '<div class="ckpoll_vitem">',
         'itemsuffix' => '</div>',
         'after' => '<div style="clear: both;"></div>',
        ]);

    $f->hidden('pollname',$pollname);
    $f->hidden('pollid',$id);
    $f->input('submit','Ok','Ok');
    return $f->get();
}

function aj_addvote_poll()
{
    global $user;
    if(!$user->auth)
        return '';

    par_def('pollname','text0nsne');
    par_def('pollid','number0');
    if(!par_ex('pollname') || !par_ex('pollid'))
        return;

    $pollname = par('pollname');
    $pollvarname = 'poll_'.$pollname.'_'.par('pollid');
    par_def($pollvarname,'text0nsne');

    if(poll_access($pollname,par('pollid'),'add',$user) != ACTIVITY_ACCESS_ALLOW)
        return;

    $parameters = get_poll_parameters_by_pollname($pollname);
    if($parameters == null)
        return; //Cannot fetch the vote prameters
    $container = $parameters['container'];
    $currentdate = date('Y-m-d');
    if($parameters['dstart'] != '' && $currentdate < $parameters['dstart'])
    {
        ajax_add_alert(t('You can not vote, because the vote is not started yet!'));
        return;
    }
    if($parameters['dend'] != '' && $currentdate > $parameters['dend'])
    {
        ajax_add_alert(t('You can not vote, because the vote is already expired!'));
        return;
    }
    if(poll_is_user_voted($pollname,$container,par('pollid'),$user->uid))
    {
        run_hook('poll_already_vote');
        ajax_add_html('.ckpoll_body_' . $pollname . '_' . par('pollid'),
            get_poll_block_inner($container,$pollname,par('pollid')),$parameters['dstart'],$parameters['dend']);
        return; //Aready vote.
    }

    $choices = get_poll_choices_array_by_pollname($pollname);
    if(!array_key_exists(par($pollvarname),$choices))
        return;
    db_insert('pollcont_'.$container)
        ->set_fv_a([
            'name' => $pollname,
            'ref' => par('pollid'),
            'uid' => $user->uid,
            'choice' => par($pollvarname)])
        ->set_fe('created',sql_t('current_timestamp'))
        ->execute();
    run_hook('poll_vote',$pollname,par('pollid'),par($pollvarname));
    ajax_add_html('.ckpoll_body_' . $pollname . '_' . par('pollid'),
        get_poll_block_inner($container,$pollname,par('pollid')),$parameters['dstart'],$parameters['dend']);
}

function poll_access($pollname,$refid,$op,$account)
{
    if(!in_array($op,['view','add']))
        return ACTIVITY_ACCESS_DENY;
    $n = run_hook('poll_access',$pollname,$refid,$op,$account);

    if(in_array( ACTIVITY_ACCESS_DENY,$n))
        return ACTIVITY_ACCESS_DENY;
    if(in_array( ACTIVITY_ACCESS_ALLOW,$n))
        return ACTIVITY_ACCESS_ALLOW;

    //Default comment permissions:
    // Allows everything for admins
    if($account->role == ROLE_ADMIN)
        return ACTIVITY_ACCESS_ALLOW;
    return ACTIVITY_ACCESS_DENY;
}

function showpollpage_callback()
{
    global $site_config;
    global $user;

    par_def('pollname','text0nsne');
    par_def('id','number0');
    if(!par_ex('pollname') || !par_ex('id'))
        return '';
    if(count($site_config->poll_containers) == 0)
        return '';

    $pollname = par('pollname');
    $id = par('id');

    $rp = get_poll_parameters_by_pollname($pollname);
    if($rp == null)
        return '';
    if(poll_access($pollname,$id,'view',$user) != ACTIVITY_ACCESS_ALLOW)
        return '';

    $container = get_poll_parametervalue_by_pollname($pollname,'container');
    $results = get_poll_results($container,$pollname,$id);

    ob_start();
    print '<div class="'.$site_config->activity_poll_main_css_class.'">';
    print '<div class="ckpoll_title">'.$rp['titletext'].'</div>';
    print '<div class="ckpoll_body_' . $pollname . '_' . $id . ' ckpoll_mbody">';
    print get_poll_resultblock($results);
    print '</div>';
    return ob_get_clean();
}

function hook_activity_required_sql_schema()
{
    global $site_config;
    $t = [];
    foreach($site_config->comment_containers as $cnt)
    {
        $t["activity_module_comment_table_$cnt"] =
            [
                "tablename" => "comment_$cnt",
                "columns" => [
                    'cid' => 'SERIAL',
                    'ref' => 'BIGINT UNSIGNED',
                    'uid' => 'BIGINT UNSIGNED',
                    'created' => 'TIMESTAMP',
                    'body' => sql_t('longtext_type'),
                ],
            ];
    }
    $poll_active = false;
    foreach($site_config->poll_containers as $cnt)
    {
        $t["activity_module_poll_table_$cnt"] =
            [
                "tablename" => "pollcont_$cnt",
                "columns" => [
                    'pid'     => 'SERIAL',
                    'name'    => 'VARCHAR(16)',
                    'ref'     => 'BIGINT',
                    'uid'     => 'BIGINT',
                    'created' => 'TIMESTAMP',
                    'choice'  => 'VARCHAR(5)',
                ],
            ];
        $poll_active = true;
    }
    if($poll_active)
    {
        $t["activity_module_pollparameters_table"] =
            [
                "tablename" => "poll_parameters",
                "columns" => [
                    'name'      => 'VARCHAR(16) UNIQUE',
                    'container' => 'VARCHAR(128)',
                    'titletext' => sql_t('longtext_type'),
                    'defidx'    => 'VARCHAR(5)',
                    'dstart'    => 'DATE DEFAULT NULL',
                    'dend'      => 'DATE DEFAULT NULL',
                ],
            ];
        $t["activity_module_pollchoices_table"] =
            [
                "tablename" => "poll_choices",
                "columns" => [
                    'name'        => 'VARCHAR(16)',
                    'choice_idx'  => 'VARCHAR(5)',
                    'choice_text' => sql_t('longtext_type'),
                ],
            ];
    }
    return $t;
}
//end.
