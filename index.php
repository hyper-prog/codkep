<?php
/*  CodKep - Lightweight web framework START file
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2  */

define('VERSION', '1.274');

define('CODKEP_MINIMUM_PHP', '5.6.0');

define('STATUS_BOOT_START'          ,0);
define('STATUS_MODULES_LOADED'      ,10);
define('STATUS_HOOKS_LOADED'        ,20);
define('STATUS_SETTINGS_LOADED'     ,30);
define('STATUS_NAMES_GENERATED'     ,40);
define('STATUS_DATABASE_CONNECTED'  ,50);
define('STATUS_INIT_CALLED'         ,60);
define('STATUS_TEMPLATES_LOADED'    ,70);
define('STATUS_ROUTES_LOADED'       ,80);
define('STATUS_STARTED'             ,90);

global $sys_data;
$sys_data = new stdClass();

global $requested_location;

$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$sys_data->generation_time_start = $time;

if(version_compare(phpversion(),CODKEP_MINIMUM_PHP) < 0)
{
    print "Your PHP installation is too old. CodKep required at least PHP " . CODKEP_MINIMUM_PHP."\n";
    exit(0);
}

$sys_data->sys_remote_address = NULL;
$sys_data->sys_requested_host = NULL;
$sys_data->sys_status = STATUS_BOOT_START;

if(!file_exists('sys/modules.php'))
    exit(1); //Probably call index.php wrong way
//load sys modules
include "sys/modules.php";
foreach($core_modules as $name => $loc)
    include 'sys/'.$loc;

//load site modules
global $site_modules;
$site_modules = [];
if(file_exists('site/_modules.php'))
    include 'site/_modules.php';
foreach($site_modules as $name => $loc)
    include 'site/'.$loc;

global $sys_modules;
$sys_modules = array_merge($core_modules,$site_modules);

$sys_data->sys_status = STATUS_MODULES_LOADED;
ccache_init();
$requested_location = '';
if(isset($_GET['q']) && is_string($_GET['q']) && $_GET['q'] != '')
{
    $requested_location = $_GET["q"];
}
elseif(isset($_SERVER['REQUEST_URI']))
{
    $request_path = strtok($_SERVER['REQUEST_URI'], '?');
    $base_path_len = strlen(rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/'));
    $requested_location = substr(urldecode($request_path), $base_path_len + 1);
    if ($requested_location == basename($_SERVER['PHP_SELF']))
    {
        $requested_location = '';
    }
}
if(strlen($requested_location) > 256)
    $requested_location = '';
$sys_data->original_requested_location = $requested_location;

sys_init_hook_table();
$sys_data->sys_status = STATUS_HOOKS_LOADED;

run_hook("boot");
if(file_exists('site/_settings.php'))
    include 'site/_settings.php';
$sys_data->sys_status = STATUS_SETTINGS_LOADED;

sys_determine_request_data();
generate_authcookie_name();
$sys_data->sys_status = STATUS_NAMES_GENERATED;

if(function_exists('sql_connect'))
    sql_connect();
$sys_data->sys_status = STATUS_DATABASE_CONNECTED;

run_hook("preinit");
run_hook("init");
$sys_data->sys_status = STATUS_INIT_CALLED;
run_hook("postinit");

$sys_data->content = new stdClass();
$sys_data->content->type = 'html';
sys_reset_content();

global $site_config;

//collect themes
$sys_data->loaded_themes = run_hook("theme");
if($site_config->default_theme_name == '' ||
   !array_key_exists($site_config->default_theme_name,$sys_data->loaded_themes))
    $site_config->default_theme_name = 'base_page';
$sys_data->sys_status = STATUS_TEMPLATES_LOADED;

//collect routes
$sys_data->loaded_routes = ccache_get('routecache');
if($sys_data->loaded_routes == NULL)
{
    $sys_data->loaded_routes = run_hook("defineroute");
    sys_preprocess_routes();
    ccache_store('routecache',$sys_data->loaded_routes,600);
}

$sys_data->sys_status = STATUS_ROUTES_LOADED;

if(!isset($requested_location) || $requested_location == '')
    $requested_location = get_startpage();

run_hook("autorun");
$sys_data->sys_status = STATUS_STARTED;
sys_route($requested_location);

run_hook("before_deliver",$sys_data->content);
print sys_assemble($sys_data->content);
flush();
run_hook("after_deliver");
//end.
