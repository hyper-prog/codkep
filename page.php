<?php
/*  CodKep - Lightweight web framework core file
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 *
 *
 * Page module
 *  Required modules: core,sql,user,forms,node
 */

function hook_page_boot()
{

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
    ob_start();
    set_title($node->title);
    print implode('',run_hook('pageview_before',$node));
    print $node->body;
    print implode('',run_hook('pageview_after',$node));
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


function hook_page_node_form_before(Node $node,$op)
{
    if($node->node_type == 'page' && ($op == 'add' || $op == 'edit'))
    {
        add_js_file('/sys/ckeditor/ckeditor.js');
        add_header("<script> window.onload = function() { CKEDITOR.replace('page_body_ckedit'); }; </script>");
    }
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

function hook_page_nodetype()
{
    $n = [];
    $n['page'] = [
        "name" => "page",
        "table" => "page",
        "show" => "table",
        "div_class" => "page_edit_area",
        "view_callback" => "page_page_view",
        "fields" => [
            10 => [
                "sql" => "pid",
                "type" => "keyn",
                "text" => "Page identifier",
                "hide" => true,
            ],
            20 => [
                "sql" => "title",
                "text" => "Page title",
                "type" => "smalltext",
                "form_options" => [
                    "size" => 60,
                ],
            ],
            30 => [
                "sql" => "path",
                "text" => "Page path (location)",
                "type" => "smalltext",
                "form_options" => [
                    "size" => 30,
                ],

            ],
            40 => [
                "sql" => "published",
                "text" => "Published",
                "type" => "check",
                "default" => false,
            ],
            50 => [
                "sql" => "body",
                "text" => "Page body html",
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
                "text" => "Tag 1",
                "type" => "smalltext",
                "par_sec" => "text3",
                "formatters" => "before",
            ],
            61 => [
                "sql" => "tag1v",
                "text" => "Tag 1",
                "type" => "smalltext",
                "par_sec" => "text3",
                "formatters" => "after",
            ],

            70 => [
                "sql" => "tag2n",
                "text" => "Tag 2",
                "type" => "smalltext",
                "par_sec" => "text3",
                "formatters" => "before",
            ],
            71 => [
                "sql" => "tag2v",
                "text" => "Tag 2",
                "type" => "smalltext",
                "par_sec" => "text3",
                "formatters" => "after",
            ],

            110 => [
                "sql" => "created",
                "type" => "timestamp_create",
                "text" => "Create time",
                "readonly" => true,
            ],
            120 => [
                "sql" => "modified",
                "type" => "timestamp_mod",
                "text" => "Modification time",
                "readonly" => true,
            ],
            130 => [
                "sql" => "moduser",
                "type" => "modifier_user",
                "text" => "Modifier user",
            ],
            200 => [
                "sql" => "submit_add",
                "type" => "submit",
                "default" => "Create",
                "centered" => true,
                "in_mode" => "insert",
            ],
            210 => [
                "sql" => "submit_edit",
                "type" => "submit",
                "default" => "Save",
                "centered" => true,
                "in_mode" => "update",
            ],
            220 => [
                "sql" => "submit_del",
                "type" => "submit",
                "default" => "Delete",
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
