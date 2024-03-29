<?php
/*  CodKep - Lightweight web framework core file
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 *
 * Debug module
 *  Required modules: core (Works with sql module if present but not required)
 */

global $debug_debugcnt;
global $debug_debugmsg;

$debug_debugcnt = 1;
$debug_debugmsg = "";

/** @ignore The default settings of debug module */
function hook_debug_boot()
{
    global $site_config;
    global $sys_data;

/*
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
*/

    $site_config->enable_hook_table_info = false;
    $site_config->enable_route_table_info = false;
    $site_config->enable_route_table_info_for_admin = false;
    $site_config->show_sql_commands_executed = false;
    $sys_data->debug_executed_sql = [];
}

/** @ignore The route definitions of debug module */
function hook_debug_defineroute()
{
    $i = [];
    $i[] = [
            "title" => "System hook call table",
            "path" => "hookcalls",
            "callback" => "debug_hooks",
            "theme" => "base_page",
           ];
    $i[] = [
            "title" => "Available hooks",
            "path" => "hooks",
            "callback" => "info_hooks",
            "theme" => "base_page",
           ];
    $i[] = [
            "title" => "Available routes",
            "path" => "codkeproutes",
            "callback" => "info_routes",
            "theme" => "base_page",
           ];

    return $i;
}

/** @ignore */
function hook_debug_execute_sql($sql,$parameters)
{
    global $site_config;
    global $sys_data;

    if($site_config->show_sql_commands_executed)
        array_push($sys_data->debug_executed_sql,preg_replace('!\s+!', ' ',$sql));
}

/** @ignore */
function hook_debug_before_deliver($content)
{
    global $site_config;
    global $sys_data;
    global $debug_debugmsg;

    if($site_config->show_sql_commands_executed )
    {
        ob_start();
        $n=0;
        print "\n<div class=\"debug_ex_sql\" style=\"text-align: left;\">\n";
        print "Executed SQL:\n";
        print "<table style=\"width: 100%;\">\n";
        foreach($sys_data->debug_executed_sql as $c)
        {
            print '<tr style="background-color: #efefef;">';
            print '<td>'.$n.'</td>';
            print '<td><pre style="margin: 2px; padding: 2px;">';
            print $c;
            print '</pre></td></tr>';
            $n++;
        }
        print "</table>\n";
        print "</div>\n";
        $content->body .= ob_get_clean();
    }
    if($debug_debugmsg != '')
    {
        ob_start();
        print "\n<div class=\"debug_debugmsg\">\n";
        print "Debug messages:\n";
        $debug_debugmsg = str_replace('<','&lt;',$debug_debugmsg);
        $debug_debugmsg = str_replace('>','&gt;',$debug_debugmsg);
        print "<pre>".$debug_debugmsg."</pre>";
        print "</div>\n";
        $content->body .= ob_get_clean();
    }
}

/** The function html block show a structured array which contains the hooks defined by the current site/system
 *  and the concrete hook function names.
 *  @return string The html code contained the table
 *  @see info_hooks()
 *  @package debug */
function debug_hooks()
{
    global $site_config;
    if(!$site_config->enable_hook_table_info)
        return '';
    global $sys_data;
    ob_start();
    print "<div class=\"debug_hook_table\" style=\"border: 8px solid #f05050;\">";
    print "<pre>";
    print_r($sys_data->available_hooks);
    print "</pre>";
    print "</div>";
    return ob_get_clean();
}

/**
 * The function returns a table contains all available FAKE hooks defined by the current site.
 * The fake hooks means the "_hook_" prefixed functions which are used to
 * document the actual hook possibility. In short we can say this function shows the documented hooks available to
 * define by modules.
 * @return string The html table contains the hook names
 * @see debug_hooks()
 * @package debug */
function info_hooks()
{
    global $site_config;
    if(!$site_config->enable_hook_table_info)
        return '';

    $hooks = [];
    $user_defined_functions = get_defined_functions()["user"];
    foreach($user_defined_functions as $funcname)
    {
        if($funcname[0] == '_' &&
           isset($funcname[1]) && $funcname[1] == 'h' &&
           isset($funcname[2]) && $funcname[2] == 'o' &&
           isset($funcname[3]) && $funcname[3] == 'o' &&
           isset($funcname[3]) && $funcname[4] == 'k' &&
           isset($funcname[4]) && $funcname[5] == '_' )
        {
            $hooks[] = $funcname;
         }
    }

    ob_start();
    print '<h2>Available hooks</h2>';
    $t = h('table')
        ->opts(['border' => '1','style'=>'border-collapse: collapse;']);
    $t->head('Hook name');
    asort($hooks);
    $n = 0;
    foreach($hooks as $v)
    {
        $v = str_replace('_hook_','HOOK_',$v);
        $t->cells(["<strong><pre>$v</pre></strong>"]);
        $t->nrow();
        ++$n;
    }
    print $t->get();
    print "$n element listed.";
    return ob_get_clean();
}

/**
 * The function returns a table contains all available routes defined by the current site.
 * @return string The html table contains the routes
 * @package debug */
function info_routes()
{
    global $site_config;
    global $sys_data;

    if(!$site_config->enable_route_table_info)
    {
        global $user;
        if(!isset($user->admin) || !$user->admin || !$site_config->enable_route_table_info_for_admin)
            return '';
    }

    ob_start();
    $t = h('table');
    $t->heads(['path','type','theme','callback','definition<br/>of the callback','callback<br/>file'],
              ['style' => 'background-color: #555555; color: #eeeeee;']);
    $t->opts(['border' => '1','style' => 'border-collapse: collapse;']);
    $routes = $sys_data->loaded_routes;
    uasort($routes,function($a,$b) {
        if ($a['path'] == $b['path']) {
            return 0;
        }
        return ($a['path'] < $b['path']) ? -1 : 1;
    });
    $n = 0; $nj = 0; $na = 0; $nr = 0;
    foreach($routes as $r)
    {
        $t->cell($r['path'],['style' => 'background-color: #ffffdd;']);
        $c = '#99ff99';
        ++$n;
        if(isset($r['type']) && $r['type'] == 'raw' ) { $c = '#ff9999'; ++$nr; }
        if(isset($r['type']) && $r['type'] == 'ajax') { $c = '#ffff99'; ++$na; }
        if(isset($r['type']) && $r['type'] == 'json') { $c = '#9999ff'; ++$nj; }
        $t->cell(isset($r['type']) ? $r['type'] : 'html',['style' => "background-color: $c;"]);

        if(!isset($r['type']) || $r['type'] == 'html')
            $t->cell(isset($r['theme']) ? $r['theme'] : '<i>'.$site_config->default_theme_name.'</i>',['style' => 'background-color: #ddffdd;']);
        else
            $t->cell('');

        $t->cell(isset($r['callback']) ? $r['callback'] : '-',['style' => 'background-color: #ddddff;']);
        $fr = new ReflectionFunction($r['callback']);
        $t->cell('<small>'.$fr->getFileName() . ':' . $fr->getStartLine().'</small>',['style' => 'background-color: #cccccc;']);
        $t->cell(isset($r['file']) ? $r['file'] : '-',['style' => 'background-color: #ddddff;']);
        $t->nrow();
    }
    print $t->get();
    print "$n route listed ($na ajax, $nj json, $nr raw)";
    return ob_get_clean();
}

/**
 * This function returns a html table shows the defined parameters of the current page.
 * Shows the POST/GET/URL parameter names, values and the security class too.
 * @return string the html code of table
 * @package debug */
function parameter_debug()
{
    $t = h('table')
            ->opts(['border' => 1])
            ->heads(['Name','Security','Defined','Value'])
            ->nrow();

    global $sys_data;
    foreach($sys_data->content->par as $p)
    {
        $t->cell($p['name']);
        $t->cell($p['sc']);
        $t->cell(par_ex($p['name']) ? 'Yes' : 'No');
        $t->cell(par($p['name']));
        $t->nrow();
    }
    return $t->get();
}

/**
 * This is the ZERO level debugger function. It prints the content of the received parameter to the /tmp/DEBUG
 * file unbuffered way. It can prints the whole structure of the passed parameter
 * @param mixed $object The variable which content's is written to the output file
 * @param string $rdf An optional suffix string which added to the output file /tmp/DEBUG.
 *      For example if the $rdf is "cache" the output is written to the /tmp/DEBUG_cache file.
 * @see d1()
 * @see d2()
 * @package debug */
function d0($object,$rdf='')
{
    ob_start();
    print "[";
    if($object === NULL)
        print "NULL";
    else if(!isset($object))
        print "UNDEFINED";
    else
        print_r($object);
    print "]\n";
    $out = ob_get_clean();
    file_put_contents(($rdf == '' ? "/tmp/DEBUG" : "/tmp/DEBUG_".$rdf),$out,FILE_APPEND);
}

/**
 * This is the FIRST level debugger function. It prints the content of the received parameter to the
 * standard output which is often printed to the webservers error log.
 * It can prints the whole structure of the passed parameter
 * @param mixed $object The variable which content's is written to the standard error
 * @see d0()
 * @see d2()
 * @package debug */
function d1($object)
{
    ob_start();
    if($object === NULL)
        print "NULL";
    else if(!isset($object))
        print "UNDEFINED";
    else
        print_r($object);
    print "\n";
    $out = ob_get_clean();
    file_put_contents('php://stderr','['.date('Y-m-d H:i:s')."] ".$out);
}

/**
 * This is the SECOND level debugger function. It prints the content of the received parameter to the
 * end of the generated web page between PRE tags. Every time this function called the outputs is appended to a
 * buffer with a counter number. The content of this buffer is printed at the end of the page generation.
 * It can prints the whole structure of the passed parameter.
 * @param mixed $object The variable which content's is written to the standard error
 * @see d0()
 * @see d1()
 * @package debug */
function d2($object)
{
    global $debug_debugcnt;
    global $debug_debugmsg;

    ob_start();
    print $debug_debugcnt.": ";
    $debug_debugcnt++;
    if($object === NULL)
        print "NULL";
    else if(!isset($object))
        print "UNDEFINED";
    else
        print_r($object);
    print "\n";
    $debug_debugmsg .= ob_get_clean();
}

function hook_debug_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = ['debug' => ['path' => 'sys/doc/debug.mdoc','index' => false , 'imagepath' => '/sys/doc/images']];
    }
    return $docs;
}

//end.
