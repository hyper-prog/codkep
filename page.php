<?php
/*  CodKep - Lightweight web framework core file
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 *
 * Page module
 *  Required modules: core,sql,user,forms,node
 */

function hook_page_boot()
{
    global $site_config;
    $site_config->page_show_control_on_top = true;
    $site_config->page_show_internal_full_topcss = 'page-internal-view-full';
    $site_config->page_edit_autopath_by_title = true;
}

function hook_page_defineroute()
{
    global $db;
    $paths = [];
    $r = sql_exec_noredirect("SELECT pid,path,tag1n,tag1v,tag2n,tag2v FROM page WHERE published");
    if($db->error || $r == NULL)
       return $paths;

    $results=$r->fetchAll();
    foreach($results as $ritem)
    {
        $pobj = [
            'path' => $ritem['path'],
            'callback' => 'page_page_callback',
        ];

        if(strlen($ritem['tag1n']) > 0 && strlen($ritem['tag1v']) > 0)
            $pobj['#'.$ritem['tag1n']] = $ritem['tag1v'];
        if(strlen($ritem['tag2n']) > 0 && strlen($ritem['tag2v']) > 0)
            $pobj['#'.$ritem['tag2n']] = $ritem['tag2v'];

        $paths[] = $pobj;
    }
    $pass = new stdClass();
    $pass->paths = &$paths;
    run_hook('alter_pageroutes',$pass);
    return $paths;
}

function page_page_callback()
{
    $cl = current_loc();
    $pass = new stdClass();
    $pass->page_route_will_load = &$cl;
    run_hook('alter_pageshow',$pass);
    $results = sql_exec_fetchAll("SELECT pid,path FROM page WHERE published AND path = :path",
                        [':path' => $cl]);
    foreach($results as $ritem)
    {
        $pnode = node_load_intype($ritem['pid'],'page');
        return page_page_view($pnode);
    }
    return '';
}

function page_page_view(Node $node)
{
    global $user;
    global $site_config;
    set_title($node->title);
    ob_start();
    print '<section class="'.$site_config->page_show_internal_full_topcss.'">';
    print implode('',run_hook('pageview_before',$node));
    print '<div class="page-show-titleline">';
        print '<div class="page-title-str">';
            print '<h1>' . $node->title . '</h1>';
        print '</div>';

        if($site_config->page_show_control_on_top)
        {
            print '<div class="page-control-btns">';
            if(node_access($node,'update',$user) == NODE_ACCESS_ALLOW)
            {
                print '<div class="page-control-edit">';
                print l('<img class="pe-btn-img btn-img" src="' . codkep_get_path('page', 'web') . '/images/edit35.png"/>',
                        'node/' . $node->node_nid . '/edit',
                        ['title' => t('Edit this page')]);
                print '</div>';
            }
            if(node_access($node,'delete',$user) == NODE_ACCESS_ALLOW)
            {
                print '<div class="page-control-del">';
                print l('<img class="pd-btn-img btn-img" src="' . codkep_get_path('page', 'web') . '/images/del35.png"/>',
                        'node/' . $node->node_nid . '/delete',
                        ['title' => t('Delete this page')]);
                print '</div>';
            }
            print '</div>';
        }
    print '</div>';
    print implode('',run_hook('pageview_aftertitle',$node));
    print $node->body;
    print implode('',run_hook('pageview_after',$node));
    print '</section>';
    return ob_get_clean();
}

function hook_page_node_access(Node $node,$op,$acc)
{
    if($node->node_type == 'page')
    {
        if(in_array($op,['create','delete','update']))
        {
            if($acc->role == ROLE_ADMIN || $acc->role == ROLE_EDITOR)
                return NODE_ACCESS_ALLOW;
            return NODE_ACCESS_DENY;
        }
        return NODE_ACCESS_ALLOW;
    }
    return NODE_ACCESS_IGNORE;
}

function hook_page_node_saved($obj)
{
    if($obj->node_ref->node_type == 'page')
        ccache_delete('routecache');
}

function hook_page_node_deleted($nid,$type,$join_id)
{
    if($type == 'page')
        ccache_delete('routecache');
}

function hook_page_node_inserted($obj)
{
    if($obj->node_ref->node_type == 'page')
        ccache_delete('routecache');
}

function validator_page_path(&$path,$def,$values)
{
    if(sys_route_exists($path,'page_page_callback'))
        return t('The path of the page used by the system! Please choose a different path!');

    $q = db_query('page')
        ->counting('pid','count')
        ->cond_fv('path',$path,'=');
    if(isset($values['pid']) && $values['pid'] != null && $values['pid'] != '')
        $q->cond_fv('pid',$values['pid'],'!=');
    $c = $q->execute_to_single();

    if($c > 0)
        return t('The path of the page must be unique! There is an existing page in the database which already have a path you set.');
    return '';
}

function hook_page_nodetype()
{
    global $site_config;

    $n = [];
    $n['page'] = [
        "name" => "page",
        "table" => "page",
        "show" => "table",
        "div_class" => "page_edit_area",
        "view_callback" => "page_page_view",
        "javascript_files" => [codkep_get_path('core','web') . '/ckeditor/ckeditor.js'],
        "form_script" => "window.onload = function() { CKEDITOR.replace('page_body_ckedit'); };
                          jQuery(document).ready(function() {
                              jQuery('.autopath').each(function() {
                                  codkep_set_autofill(this);
                              });
                          });",
        "fields" => [
            10 => [
                "sql" => "pid",
                "type" => "keyn",
                "text" => t('Page identifier'),
                "hide" => true,
                "pgsql_sql_sequence_name" => "page_pid_seq",
            ],
            20 => [
                "sql" => "title",
                "text" => t('Page title'),
                "type" => "smalltext",
                "form_options" => [
                    "size" => 60,
                    "id" => "page-title-edit",
                ],
            ],
            30 => [
                "sql" => "path",
                "text" => t('Page path (location)'),
                "type" => "smalltext",
                "form_options" => [
                    "size" => 60,
                    "class" => ($site_config->page_edit_autopath_by_title ? "autopath" : ""),
                    "rawattributes" => "data-autopath-from=\"page-title-edit\" data-autopath-type=\"als\"",
                ],
                'check_noempty' => t('You have to fill the path field'),
                'check_callback' => 'validator_page_path',
            ],
            40 => [
                "sql" => "published",
                "text" => t('Published'),
                "type" => "check",
                "default" => false,
            ],
            50 => [
                "sql" => "body",
                "text" => t('Page body html'),
                "type" => "largetext",
                "par_sec" => "free",
                "row" => 25,
                "col" => 80,
                "form_options" => [
                    "id" => "page_body_ckedit",
                ],
            ],
            60 => [
                "sql" => "tag1n",
                "text" => t('Tag 1'),
                "type" => "smalltext",
                "par_sec" => "text3",
                "formatters" => "before",
            ],
            61 => [
                "sql" => "tag1v",
                "text" => t('Tag 1'),
                "type" => "smalltext",
                "par_sec" => "text3",
                "formatters" => "after",
            ],

            70 => [
                "sql" => "tag2n",
                "text" => t('Tag 2'),
                "type" => "smalltext",
                "par_sec" => "text3",
                "formatters" => "before",
            ],
            71 => [
                "sql" => "tag2v",
                "text" => t('Tag 2'),
                "type" => "smalltext",
                "par_sec" => "text3",
                "formatters" => "after",
            ],
            110 => [
                "sql" => "modified",
                "type" => "timestamp_mod",
                "text" => t('Modification time'),
                "readonly" => true,
            ],
            120 => [
                "sql" => "moduser",
                "type" => "modifier_user",
                "text" => t('Modifier user'),
            ],
            200 => [
                "sql" => "submit_add",
                "type" => "submit",
                "default" => t('Create'),
                "centered" => true,
                "in_mode" => "insert",
            ],
            210 => [
                "sql" => "submit_edit",
                "type" => "submit",
                "default" => t('Save'),
                "centered" => true,
                "in_mode" => "update",
            ],
            220 => [
                "sql" => "submit_del",
                "type" => "submit",
                "default" => t('Delete'),
                "centered" => true,
                "in_mode" => "delete",
            ],
        ],
    ];

    return $n;
}

function hook_page_introducer()
{
    global $user;

    if(!$user->auth || $user->role != ROLE_ADMIN)
        return ['Page' => ''];

    $html = l(t('Add page'),'node/page/add');
    $r = sql_exec_fetchAll("SELECT pid,path,title FROM page WHERE published");
    $ps = [];
    $eps = [];
    foreach($r as $rr)
    {
        $ps[] = l($rr['title'],$rr['path']);
        $eps[] = l($rr['title'],'nodeintype/page/' . $rr['pid'] . '/edit');
    }
    if(count($ps) > 0)
        $html .= '<br> View: '.implode(', ',$ps) . '<br> Edit: '.implode(', ',$eps);
    return ['Page' => $html];
}

function hook_page_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = ['page' => ['path' => 'sys/doc/page.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
    }
    return $docs;
}

/** It can alter the locations before defined by the page module.
 *  @package page */
function _HOOK_alter_pageroutes() {}

/** It runs before the page will show, it receives the page route. (It can modify the loaded location or do a redirection.)
 *  @package page */
function _HOOK_alter_pageshow() {}

/** It runs before the page will show, it receives the page node. The return will prepend before the page content.
 *  @package page */
function _HOOK_pageview_before() {}

/** It runs after the page will show, it receives the page node. The return will append after the page content.
 *  @package page */
function _HOOK_pageview_after() {}
