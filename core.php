<?php
/*  CodKep - Lightweight web framework core file
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 *
 *  Core module: There is no dependencies of this module. Can be used with index.php
 */

define('NO_APC'  , 0);
define('USE_APC' , 1);
define('USE_APCU', 2);

/** @ignore
 * Boot hook (before settings.php) - It contains system default values
 * You can change this values in your site/_settings.php */
function hook_core_boot()
{
    global $sys_data;
    global $site_config;
    global $translations;

    $site_config = new stdClass();
    $site_config->base_path             = '';
    $site_config->base_path_nonexfiles  = NULL;
    $site_config->cookie_domain         = NULL;

    $site_config->startpage_location    = 'not_configured_startpage';
    $site_config->startpage_location_byhost = [];
    $site_config->default_theme_name    = 'flex';
    $site_config->site_icon             = '/sys/images/cklogo.ico';
    $site_config->notfound_location     = 'notfound';
    $site_config->srv_remoteaddr_spec   = NULL;
    $site_config->lang                  = 'en';
    $site_config->show_generation_time  = true;
    $site_config->hide_module_intros    = false;
    $site_config->custom_mail_sender    = NULL;
    $site_config->route_loop_max        = 3;

    $site_config->logo_img_url = NULL;
    $site_config->site_name    = NULL;
    $site_config->site_slogan  = NULL;

    $site_config->mainmenu = [];
    $site_config->mainmenu_append_tag_mainmenu = true;

    $site_config->main_script           = 'index.php';
    $site_config->clean_urls            = false;
    $site_config->parameter_autodefine  = false;

    $site_config->authcookie_name_salt  = 'Ux8W-pO5zeY+@ews';

    $site_config->cors_requests_enabled_hosts  = '';

    $site_config->param_event_locations = [
        'undefined' => 'param_undefined_error',
        'missing'   => 'missing_parameter_error',
        'security'  => 'param_security_error',
    ];

    $site_config->onoffswitch_icons = [
        'default' => ['/sys/images/on.png','/sys/images/off.png']
    ];

    $translations = [];
    //------------------------------------------------

    $sys_data->current_route = NULL;
    $sys_data->r = NULL;
    $sys_data->route_loop_count = 0;
    $sys_data->route_redirections = [];
    $sys_data->month_names =
        [1  => 'January',
         2  => 'February',
         3  => 'March',
         4  => 'April',
         5  => 'May',
         6  => 'June',
         7  => 'July',
         8  => 'August',
         9  => 'September',
         10 => 'October',
         11 => 'November',
         12 => 'December',
        ];

    $sys_data->parameter_security_classes =
        ['free'     => '/.*/u',
         'bool'     => '/^[01onf]*$/u',
         'number0'  => '/^[0-9]*$/u',
         'number0ne'=> '/^[0-9]+$/u',
         'number1'  => '/^[0-9\.\,\s\-]*$/u',
         'number1ns'=> '/^[0-9\.\,\-]*$/u',
         'numberi'  => '/^[0-9\-]*$/u',
         'number2'  => '/^[0-9\;\+\.\,\s\-]*$/u',
         'text0'    => '/^[\sa-zA-Z0-9]*$/u',
         'text0ns'  => '/^[a-zA-Z0-9]*$/u',
         'text0nsne'=> '/^[a-zA-Z0-9]+$/u',
         'text1'    => '/^[\s\-\_a-zA-Z0-9]*$/u',
         'text1ns'  => '/^[\-\_a-zA-Z0-9]*$/u',
         'text2'    => '/^[\sa-zA-Z0-9\p{L}]*$/u',
         'text2ns'  => '/^[a-zA-Z0-9\p{L}]*$/u',
         'text3'    => '/^[\s\-\_a-zA-Z0-9\p{L}]*$/u',
         'text3ns'  => '/^[\-\_a-zA-Z0-9\p{L}]*$/u',
         'text4'    => '/^[\s\-\_\.\,\:\?\#\/\!\(\)\=\+a-zA-Z0-9\p{L}]*$/u',
         'text4m'   => '/^[\s\-\_\.\,\:\?\#\/\!\(\)\=\@\+a-zA-Z0-9\p{L}]*$/u',
         'text4ns'  => '/^[\-\_\.\,\:\?\#\/\!\(\)\=\+a-zA-Z0-9\p{L}]*$/u',
         'text5'    => '/^[\s\-\_\.\,\:\?\&\#\/\!\(\)\=\%\+\;\@\*a-zA-Z0-9\p{L}]*$/u',
         'text6'    => '/^[\s\-\_\.\,\:\?\&\#\/\!\(\)\=\%\+\;\@\*\"a-zA-Z0-9\p{L}]*$/u',
         'textemail'=> '/^[\-\_\.\@a-zA-Z0-9\p{L}]*$/u',
         'textbase64' => '/^[\=a-zA-Z0-9]*$/',
         'tst'      => '/^[\s\-\:\.\+a-zA-Z0-9\p{L}]*$/u',
         'tstns'    => '/^[\-\:\.\+a-zA-Z0-9\p{L}]*$/u',
         'isodate'  => '/^\d{4}\-\d{2}\-\d{2}$/',
         'neuttext' => '/^[a-zA-Z0-9\s\_\-\?\!\.\(\)\p{L}]*$/u',
         'ipv4address'  => '/^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/',
         'no'       => '/^$/',
        ];
}

// API functions

/** Add a translation text for the given language for one piece of text
 *  @see t()
 *  @param string $lang Specifies the language of the translation
 *  @param string $orig The text which translated.
 *  @param string $translated The translation of $orig text.
 *  @package core  */
function add_t($lang,$orig,$translated)
{
    global $translations;
    if(!isset($translations[$lang]))
        $translations[$lang] = [];
    $translations[$lang][$orig] = $translated;
}

/** Add a translation text array for the given language
 *
 *  @param string $lang Specifies the language of the translation
 *  @param array $trs An associative array contains the translation pairs.
 *                    The key is the english text, the value is the translated.
 *  @package core  */
function add_t_array($lang,array $trs)
{
    global $translations;
    if(!isset($translations[$lang]))
        $translations[$lang] = [];
    $translations[$lang] = array_merge($translations[$lang],$trs);
}

function ccache_init()
{
    global $sys_data;
    $sys_data->cache_apc = NO_APC;
    if(extension_loaded('apc') && ini_get('apc.enabled'))
        $sys_data->cache_apc = USE_APC;

    if(extension_loaded('apcu') && ini_get('apc.enabled'))
        $sys_data->cache_apc = USE_APCU;

    if($sys_data->cache_apc == USE_APC || $sys_data->cache_apc == USE_APCU)
    {
        //We have to genereate a prefix which unique between the sites of the webserver
        // and short because it's prefixed to every key.
        $sys_data->cache_prefix = 'ck'.dechex(crc32(md5($_SERVER['DOCUMENT_ROOT'].$_SERVER['SCRIPT_FILENAME']))).'_';
    }
}

function ccache_check()
{
    global $sys_data;
    if($sys_data->cache_apc == NO_APC)
        return false;
    return true;
}

function ccache_get($key)
{
    global $sys_data;
    if($sys_data->cache_apc == NO_APC)
        return NULL;

    $succ = false;
    if($sys_data->cache_apc == USE_APC)
        $cached = apc_fetch($sys_data->cache_prefix.$key,$succ);
    if($sys_data->cache_apc == USE_APCU)
        $cached = apcu_fetch($sys_data->cache_prefix.$key,$succ);
    if(!$succ)
        return NULL;
    $data = unserialize($cached);
    if($data === FALSE)
        return NULL;
    return $data;
}

function ccache_store($key,$data,$ttl)
{
    global $sys_data;
    if($sys_data->cache_apc == NO_APC)
        return;
    if($sys_data->cache_apc == USE_APC)
        apc_store($sys_data->cache_prefix.$key,serialize($data),$ttl);
    if($sys_data->cache_apc == USE_APCU)
        apcu_store($sys_data->cache_prefix.$key,serialize($data),$ttl);
}

function ccache_delete($key)
{
    global $sys_data;
    if($sys_data->cache_apc == NO_APC)
        return;
    if($sys_data->cache_apc == USE_APC)
        apc_delete($sys_data->cache_prefix.$key);
    if($sys_data->cache_apc == USE_APCU)
        apcu_delete($sys_data->cache_prefix.$key);
}

/** Translates a string to the current language if the translation is available.
 *
 * The current laguage can be set in <i>site/_settings.php</i> by setting <i>$site_config->lang</i>
 * You can also set the current language with the set_lang() function.
 * The translation texts can be set by add_t() and add_t_array() functions
 * <code>
    global $user;
    print t('Your name is _name_!',array('_name_' => $user->name));
  </code>
 * @see add_t()
 * @see add_t_array()
 * @see set_lang()
 * @see get_lang()
 * @param string $string The string to translate
 * @param array $args Optional associative array of replacements to make after translation.
 * @return string The translated string. If no translation found the original (substituted) string is returned 
 * @package core */
function t($string,array $args = [])
{
    global $site_config;
    global $translations;

    if(isset($translations[$site_config->lang][$string]))
        $str = $translations[$site_config->lang][$string];
    else
        $str = $string;

    foreach($args as $pholder => $value)
        $str = str_replace($pholder,$value,$str);
    return $str;
}

/** Sets the current language of the system
 *  @see t()
 *  @param string $lang The new language code to set.
 *  @package core */
function set_lang($lang)
{
    global $site_config;
    $site_config->lang = $lang;
}

/** Returns the current set language of the system
 *  @see set_lang()
 *  @return string the language code
 *  @package core */
function get_lang()
{
    global $site_config;
    return $site_config->lang;
}

/** Generates an internal or external URL.
 *
 *  If you provide a full URL, it will be considered an external URL.
 *  @param string $loc The url to build.
 *  @param array $query An array of query key/value-pairs to append to the URL
 *  @param array $options Options of the build of url
 *  @return string The printable url can be used in browser of clients
 *  @package core */
function url($loc,array $query=[],array $options=[])
{
    global $sys_data;
    global $site_config;

    if(isset($options['query']) && is_array($options['query']))
        $query = array_merge($query,$options['query']);
    $u = parse_url($loc);
    if($u === false)
        return '';
    $uo = (object) $u;
    $uo->original = $loc;
    $uo->basepath = $site_config->base_path;
    $uo->mainscript = $site_config->main_script;
    $uo->additional_path = '';
    if(!isset($uo->path))
        $uo->path = '';
    if(!isset($u['scheme']) && !isset($u['host']) && !isset($u['port']))
    {
        foreach($sys_data->loaded_routes as $r)
        {
            $rtmatch = false;
            if($r["mtype"] == 1 && $r["path"] == $uo->path)
                $rtmatch = true;
            if($r["mtype"] == 2)
                if(preg_match($r["rex"],$uo->path))
                    $rtmatch = true;

            if($rtmatch)
            {
                $p=false;
                run_hook("outbound_internal_url",$uo);
                if(isset($options['skipbasepath']) &&
                    ($options['skipbasepath']=='all' || $options['skipbasepath']=='internal'))
                    $url = '';
                else
                    $url = $uo->basepath;

                if($uo->mainscript != '')
                    $url .= '/' . $uo->mainscript;

                if($uo->additional_path != '')
                    $url .= '/' . $uo->additional_path;

                if(isset($uo->path) && $uo->path != '')
                {
                    $url .= '?q=' . $uo->path;
                    $p = true;
                }
                if(!isset($options['skip_orig_query']) || $options['skip_orig_query'] != 'skip')
                    if(isset($uo->query) && $uo->query != '')
                    {
                        $url .= ($p ? '&' : '?') . $uo->query;
                        $p = true;
                    }
                if(count($query) > 0)
                    foreach($query as $name => $value)
                    {
                        $url .= ($p ? '&' : '?') . urlencode($name)."=".urlencode($value);
                        $p = true;
                    }
                if(isset($options['fragment']) &&  $options['fragment'] != '')
                    $url .= '#' . $options['fragment'];
                elseif(isset($uo->fragment) &&  $uo->fragment != '')
                    $url .= '#' . $uo->fragment;
                return $url;
            }
        }

        //not found in routes
        if(substr($uo->path,0,1) == '/')
        {
            $p=false;
            run_hook("outbound_internal_file_url",$uo);
            if($site_config->base_path_nonexfiles === NULL)
            {
                if(isset($options['skipbasepath']) &&
                    ($options['skipbasepath']=='all' || $options['skipbasepath']=='files'))
                    $url = '';
                else
                    $url = $uo->basepath;
            }
            else
                $url = $site_config->base_path_nonexfiles;

            if(isset($uo->path) && $uo->path != '')
                $url .= $uo->path;

            if(!isset($options['skip_orig_query']) || $options['skip_orig_query'] != 'skip')
                if(isset($uo->query) && $uo->query != '')
                {
                    $url .= '?' . $uo->query;
                    $p = true;
                }

            if(count($query) > 0)
                foreach($query as $name => $value)
                {
                    $url .= ($p ? '&' : '?') . urlencode($name)."=".urlencode($value);
                    $p = true;
                }

            if(isset($options['fragment']) &&  $options['fragment'] != '')
                    $url .= '#' . $options['fragment'];
                elseif(isset($uo->fragment) &&  $uo->fragment != '')
                    $url .= '#' . $uo->fragment;
            return $url;
        }
    }

    run_hook("outbound_external_url",$uo);
    $url = '';
    $p = false;
    if(isset($uo->scheme))
        $url .= $uo->scheme . '://';
    if(isset($uo->host))
        $url .= $uo->host;
    if(isset($uo->port))
        $url .= ':'.$uo->port;
    if(isset($uo->path))
        $url .= $uo->path;
    if(!isset($options['skip_orig_query']) || $options['skip_orig_query'] != 'skip')
        if(isset($uo->query) && $uo->query != '')
        {
            $url .= '?' . $uo->query;
            $p = true;
        }
    if(count($query) > 0)
        foreach($query as $name => $value)
        {
            $url .= ($p ? '&' : '?') . urlencode($name)."=".urlencode($value);
            $p = true;
        }
    if(isset($options['fragment']) &&  $options['fragment'] != '')
        $url .= '#' . $options['fragment'];
    elseif(isset($uo->fragment) &&  $uo->fragment != '')
                    $url .= '#' . $uo->fragment;
    return $url;
}

/** Formats an internal or external URL link as an HTML anchor tag.
 *
 *  @param string $text The link text for the anchor tag.
 *  @param string $loc The url to link.
 *  @param array $options An associative array of additional options. Defaults to an empty array.
            url_options -> url() options 
 *  @param array $query An array of query key/value-pairs to append to the URL
 *  @param array $query An optional url anchor tag to append to the URL
 *  @return string The printable html code
 *  @package core */
function l($text,$loc,array $options = [],array $query = [],$fragment = NULL)
{
    ob_start();
    $url_options = [];
    if(isset($options['query']))
        $query = array_merge($query,$options['query']);
    if(isset($options['url_options']))
        $url_options = $options['url_options'];
    if($fragment != NULL && $fragment != '')
        $url_options['fragment'] = $fragment;

    print '<a ';
    if(isset($options['title']))
        print "title=\"".$options['title']."\" ";
    if(isset($options['target']))
        print "target=\"".$options['target']."\" ";
    if(isset($options['class']))
        print "class=\"".$options['class']."\" "; 
    if(isset($options['id']))
        print "id=\"".$options['id']."\" ";
    if(isset($options['style']))
        print "style=\"".$options['style']."\" ";
    if(isset($options['newtab']) && $options['newtab'])
        print "target=\"_tab\" ";
    if(isset($options['rawattr']))
        print $options['rawattr'].' ';
    print 'href="'.url($loc,$query,$url_options).'">';
    print $text;
    print '</a>';
    return ob_get_clean();
}

/** Returns the "web" or "server" path of the module specified in the first parameter.
 *  @package core */
function codkep_get_path($module,$pathtype = "web")
{
    global $core_modules;
    global $site_modules;

    $pre_p = '';
    $mod_p = '';
    if(array_key_exists($module,$core_modules))
    {
        $pre_p .=  '/sys';
        $mod_p = $core_modules[$module];
    }
    if(array_key_exists($module,$site_modules))
    {
        $pre_p .=  '/site';
        $mod_p = $site_modules[$module];
    }
    if($mod_p == '' || $pre_p == '')
        return NULL;

    $mod_subpath = rtrim(strrev(strstr(strrev($mod_p),'/',FALSE)),'/ ');
    if($mod_subpath === FALSE)
        $mod_subpath = '';
    if($mod_subpath != '')
        $mod_subpath = '/' . $mod_subpath;

    if($pathtype == 'local' || $pathtype == 'server')
        return realpath('.').$pre_p.$mod_subpath;
    return $pre_p.$mod_subpath;
}

/** Returns the internal url of the current executed page.
 *  The return value can be passed to the url() function to generate valid url.
 *  @package core */
function current_loc()
{
    global $sys_data;
    return $sys_data->current_route;
}

/** Loads a different page than currently executed.
 *
 *  Stops (or won't start) the current executing, drops the outputs, and
 *  immediately start execution of the parameter passed page.
 *  This function does internal routing again, so the requested url in the browser will be untouched.
 *  Opposite of this the goto_loc() function do redirection with http redirect header.
 *  @see goto_loc()
 *
 *  The first parameter is the location to go.
 *  The other parameters are passed to the callback function of the requested location
 *  @param string $location The location to go/exec
 *  @package core */
function load_loc()
{
    global $sys_data;
    global $site_config;
    global $requested_location;

    $args = func_get_args();
    $location = array_shift($args);

    if($sys_data->sys_status < STATUS_STARTED)
    {
        $requested_location = $location;
        return;
    }

    //prevent recursion
    if(current_loc() == $location)
    {
        sys_to_internal_server_error("CodKep routing recursion detected");
        exit(0);
    }

    while(ob_get_level() > 0)
        ob_end_clean();

    if(isset($sys_data->r['redirectonly']))
    {
        if($sys_data->r['redirectonly'] == '')
        {
            sys_to_internal_server_error("A redirect request was received for a non-redirect page ".
                                         "(".$sys_data->r['path'] . ' -> ' .$location.")");
            exit(0);
        }
        $location = $sys_data->r['redirectonly'];
    }

    if(in_array($location,$sys_data->route_redirections))
        $sys_data->route_loop_count++;
    $sys_data->route_redirections[] = $location;
    if($sys_data->route_loop_count >= $site_config->route_loop_max)
    {
        sys_to_internal_server_error(
            "CodKep routing loop protection: Limit reached! \n".
            "Routes loaded: ".implode(" -> ",$sys_data->route_redirections));
        exit(0);
    }

    sys_reset_content();
    sys_route($location,$args);

    run_hook("before_deliver",$sys_data->content);
    print sys_assemble($sys_data->content);
    flush();
    run_hook("after_deliver");
    exit(0);
}

function sys_to_internal_server_error($message_to_log = '')
{
    http_response_code(500);
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal server error');
    file_put_contents('php://stderr',
        date('Y-m-d H:i:s')." Error, ".$message_to_log . " \n");
    print "Site error\n";
    exit(0);
}

/** Sends the user to a different page.
 *
 *  Stops (or won't start) the current executing, drops the outputs, and
 *  immediately send redirection headers to the browsers with the parameter passed location.
 *  This function does redirection with http redirect header.
 *  @see load_loc()
 *  @param string $location The location to go/exec
 *  @package core */
function goto_loc($location,array $query = [])
{
    while(ob_get_level() > 0)
        ob_end_clean();
    header('Location: ' . url($location,$query),true,302);
    exit(0);
}

/** Empty the ajax command buffer.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' ) 
 *  @package core */
function ajax_reset()
{
    global $sys_data;
    $sys_data->content->commands = [];
}

/** Creates an ajax (jQuery) 'html' command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' )
 *  @package core */
function ajax_add_html($selector,$content)
{
    global $sys_data;
    $sys_data->content->commands[] = ['html',$selector,$content];
}

/** Creates an ajax (jQuery) 'append' command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' )
 *  @package core */
function ajax_add_append($selector,$content)
{
    global $sys_data;
    $sys_data->content->commands[] = ['append',$selector,$content];
}

/** Creates an ajax (jQuery) 'remove' command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' )
 *  @package core */
function ajax_add_remove($selector)
{
    global $sys_data;
    $sys_data->content->commands[] = ['remove',$selector];
}

/** Creates an ajax (jQuery) 'css' command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' )
 *  @package core */
function ajax_add_css($selector,$css)
{
    global $sys_data;
    $sys_data->content->commands[] = ['css',$selector,$css];
}

/** Creates an ajax (jQuery) 'addClass' command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' )
 *  @package core */
function ajax_add_addclass($selector,$class)
{
    global $sys_data;
    $sys_data->content->commands[] = ['addClass',$selector,$class];
}

/** Creates an ajax (jQuery) 'removeClass' command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' ) 
 *  @package core */
function ajax_add_removeclass($selector,$class)
{
    global $sys_data;
    $sys_data->content->commands[] = ['removeClass',$selector,$class];
}

/** Creates an ajax (jQuery) 'show' command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' )
 *  @package core */
function ajax_add_show($selector,$showparam)
{
    global $sys_data;
    $sys_data->content->commands[] = ['show',$selector,$showparam];
}

/** Creates an ajax (jQuery) 'hide' command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' )
 *  @package core */
function ajax_add_hide($selector,$hideparam)
{
    global $sys_data;
    $sys_data->content->commands[] = ['hide',$selector,$hideparam];
}

/** Creates an ajax (jQuery) 'toggle' command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' )
 *  @package core */
function ajax_add_toggle($selector,$toggleparam)
{
    global $sys_data;
    $sys_data->content->commands[] = ['toggle',$selector,$toggleparam];
}

/** Creates an ajax (javascript) 'alert' command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' ) 
 *  @package core */
function ajax_add_alert($message)
{
    global $sys_data;
    $sys_data->content->commands[] = ['alert',$message];
}

/** Creates an ajax (javascript) 'console.log' command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' ) 
 *  @package core */
function ajax_add_log($message)
{
    global $sys_data;
    $sys_data->content->commands[] = ['log',$message];
}

/** Creates an ajax custom function execute command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' ) 
 *  @package core */
function ajax_add_run($command,$arg = [])
{
    global $sys_data;
    $sys_data->content->commands[] = ['run','global',$command,$arg];
}

/** Creates a delayed ajax call command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' )
 *  @package core */
function ajax_add_delayed_ajaxcall($ajax_url,$msec)
{
    global $sys_data;
    $sys_data->content->commands[] = ['delaycall',url($ajax_url),$msec];
}

/** Creates an ajax (javascript) full page refresh command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' ) 
 *  @package core */
function ajax_add_refresh()
{
    global $sys_data;
    $sys_data->content->commands[] = ['refresh'];
}

/** Creates an ajax (jQuery) html overlay show command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' ) 
 *  @package core */
function ajax_add_showol($content,$timeout = 0)
{
    global $sys_data;
    $sys_data->content->commands[] = ['showol',$content,$timeout];
}

/** Creates an ajax (javascript) go to url command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' ) 
 *  @package core */
function ajax_add_goto($url)
{
    global $sys_data;
    $sys_data->content->commands[] = ['goto',url($url)];
}

/** Creates an ajax (jQuery) 'val' command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' )
 *  @package core */
function ajax_add_val($selector,$value)
{
    global $sys_data;
    $sys_data->content->commands[] = ['val',$selector,$value];
}

/** Creates an ajax (jQuery) 'prop' command and adds to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' )
 *  @package core */
function ajax_add_prop($selector,$propname,$value)
{
    global $sys_data;
    $sys_data->content->commands[] = ['prop',$selector,$propname,$value];
}

/** Creates an ajax (jQuery) 'val' commands to append a text to the output queue.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' )
 *  @package core */
function ajax_add_appendval($selector,$value,$linebreak_if_nonempty = false)
{
    global $sys_data;
    $sys_data->content->commands[] = ['appendval',$selector,$value,$linebreak_if_nonempty];
}

/** Creates an ajax commands to popup a dialog.
 *
 *  Only use in ajax handler. ( _defineroute 'type'=>'ajax' )
 *  @package core */
function ajax_add_popupdialog($title,$content)
{
    ajax_add_run("prepare_ckdialog_a",[$title,$content]);
    ajax_add_run("popup_ckdialog");
}

/** Returns the html code which placed a delayed ajax call to the page.
 *  @param string $ajax_url The url of the ajax call to be call. (Will be processed by url())
 *  @param mixed $msec The delay time in millisecundum after document.ready
 *  @package core */
function place_delayed_ajax_call($ajax_url,$msec)
{
    $aurl = url($ajax_url);
    return "<script>".
     "jQuery(document).ready(function() { delayedAjaxCall({url: '$aurl',msec: $msec}); });".
     "</script>";
}

/** Adds a style string to the headers style tag.  
 *  @package core */
function add_style($text)
{
    global $sys_data;
    $sys_data->content->style .= $text . "\n";
}

/** Adds a html header line to the current page 
 *  @package core */
function add_header($text)
{
    global $sys_data;
    if(!in_array($text,$sys_data->content->head))
        array_push($sys_data->content->head,$text);
}

/** Adds author data to the page (html header metadata)
 *  @package core */
function add_author_data($author)
{
    add_header("<meta name=\"Author\" content=\"$author\" />\n");
}

/** Adds SEO data to the page (html header metadata)
 *  @package core */
function add_seo_data($description,$keywords,$revisitafter)
{
    add_header("<meta name=\"description\" content=\"$description\" />\n");
    add_header("<meta name=\"keywords\" content=\"$keywords\" />\n");
    add_header("<meta name=\"revisit-after\" content=\"$revisitafter\" />\n");
}

/** Add a CSS file reference to the html header.
 * @package core */
function add_css_file($loc)
{
    add_header("<link rel=\"stylesheet\" type=\"text/css\" href=\"".url($loc)."\"/>\n");
}

/** Add a javascript file reference to the html header.
 * @package core */
function add_js_file($loc)
{
    add_header("<script type=\"text/javascript\" src=\"".url($loc)."\"></script>\n");
}

/** Sets the html title of the current page.
 *  It does not modify any settings, only sets for the current generated page's title.
 *  @package core */
function set_title($text)
{
    global $sys_data;
    $sys_data->content->title = $text;
}

/** Sets the favicon of the current page.
 *  It does not modify any settings, only sets for the current generated page's icon.
 *  @package core */
function set_icon($iconurl)
{
    global $sys_data;
    $sys_data->content->icon = url($iconurl);
}

/** Returns the location of startpage associated the current site */
function get_startpage()
{
    global $site_config;
    if(isset($site_config->startpage_location_byhost[$_SERVER['HTTP_HOST']]))
        return $site_config->startpage_location_byhost[$_SERVER['HTTP_HOST']];
    return $site_config->startpage_location;
}

/** Define a parameter (GET or POST or URL)
 *
 *  @param string $name The name of the parameter
 *  @param string $security_class regex restriction class of the parameter
 *  @param string $source Source restriction of the parameter (all/get/post/url)
 *  @param bool $accept_empty If par_ex function accepts empty value as defined parameter
 *  @param string $default The default value (Returned by par when parameter is undefined)
 *  @param string $required If the required text is not NULL  the system throws error
 *            with this text when the parameter is missing/not defined
 *  @package core */
function par_def($name,$security_class,$source = 'all',$accept_empty = true,$default = NULL,$required = NULL)
{
    global $site_config;
    global $sys_data;
    $sys_data->content->par[$name] = ['name' => $name,
                                      'sc' => $security_class,
                                      'source' => $source,
                                      'accempty' => $accept_empty,
                                      'def' => $default,
                                      'req' => $required,
                                      ];
    if($required != NULL && $required != '' && !par_ex($name))
    {
        run_hook('parameter_missing',$name,$required);
        load_loc($site_config->param_event_locations['missing'],$name,$required);
    }
}

/** Empty/reset defined parameters 
 *  @package core */
function par_reset()
{
    global $sys_data;
    $sys_data->content->par = [];
}

/** Returns a key-value array with the defined and set parameters
 *  @param array $change Value overwrite array, contains name-value pairs.
 *          (It does'nt modify real parameter value just the returned array)
 *  @param array $infilter Filter the returned parameters.
 *          The names of the returned parameters.
 *  @param array $outfilter Filter the returned parameters.
 *          The names of parameters which are skipped from the returned parameters.
 *  @return array Returns an associative array contains the parameters set on this page
 *  @package core  */
function parameters(array $change = [],array $infilter = [],array $outfilter = [])
{
    $r = [];
    global $sys_data;
    foreach($sys_data->content->par as $p)
    {
        if((empty($infilter) || in_array($p['name'],$infilter)) &&
           (empty($outfilter) || !in_array($p['name'],$outfilter)) )
        {
            if(par_ex($p['name']))
                $r[$p['name']] = array_key_exists($p['name'],$change) ? $change[$p['name']] : par($p['name']);
            else
                if(array_key_exists($p['name'],$change))
                    $r[$p['name']] = $change[$p['name']];
        }
    }
    return $r;
}

/** Check if a given parameter is exists 
 *  @package core */
function par_ex($name,$autodefine_type = 'no')
{
    global $sys_data;
    $p = [];
    if(!isset($sys_data->content->par[$name]))
    {
        global $site_config;
        if($site_config->parameter_autodefine)
        {
            par_def($name,$autodefine_type);
        }
        else
        {
            global $site_config;
            run_hook('parameter_undefined',$name);
            load_loc($site_config->param_event_locations['undefined'],$name);
            return false;
        }
    }
    $p = $sys_data->content->par[$name];

    if(isset($sys_data->r['url_par_names']) && $sys_data->r['url_par_values'])
        if(($r = array_search($name,$sys_data->r['url_par_names'])) !== FALSE && ($p['source'] == 'all' || $p['source'] == 'url') )
        {
            if(array_key_exists($r,$sys_data->r['url_par_values']))
            {
                if(!$p['accempty'] && $sys_data->r['url_par_values'][$r] == '')
                    return false;
                return true;
            }
            return false;
        }

    if(isset($_GET[$name]) && ($p['source'] == 'all' || $p['source'] == 'get'))
    {
        if(!$p['accempty'] && $_GET[$name] == '')
            return false;
        return true;
    }
    if(isset($_POST[$name]) && ($p['source'] == 'all' || $p['source'] == 'post'))
    {
        if(!$p['accempty'] && $_POST[$name] == '')
            return false;
        return true;
    }
    return false;
}

/** Returns true if the specified parameter is exists and contains same value passed in parameter. 
 *  @package core */
function par_is($name,$value,$autodefine_type = 'no')
{
    if(par_ex($name,$autodefine_type))
        if(par($name,$autodefine_type) == $value)
            return true;
    return false;
}

/** Returns the value of the parameter according to the definition 
 *  @package core */
function par($name,$autodefine_type = 'no')
{
    global $sys_data;
    global $site_config;
    if(!isset($sys_data->content->par[$name]))
    {
        if($site_config->parameter_autodefine)
        {
            par_def($name,$autodefine_type);
        }
        else
        {
            run_hook('parameter_undefined',$name);
            load_loc($site_config->param_event_locations['undefined'],$name);
            return false;
        }
    }
    $p = $sys_data->content->par[$name];

    $v = NULL;

    if(isset($sys_data->r['url_par_names']) && $sys_data->r['url_par_values'])
        if(($r = array_search($name,$sys_data->r['url_par_names'])) !== FALSE && ($p['source'] == 'all' || $p['source'] == 'url') )
            if(array_key_exists($r,$sys_data->r['url_par_values']))
                $v = $sys_data->r['url_par_values'][$r];

    if($v == NULL && isset($_GET[$name]) && ($p['source'] == 'all' || $p['source'] == 'get') )
        $v = $_GET[$name];

    if($v == NULL && isset($_POST[$name]) && ($p['source'] == 'all' || $p['source'] == 'post'))
        $v = $_POST[$name];

    if($v == NULL)
    {
        if($p['req'] != NULL && $p['req'] != '')
        {
            run_hook('parameter_missing',$name,$p['req']);
            load_loc($site_config->param_event_locations['missing'],$name,$p['req']);
        }

        if($v == NULL)
            $v = $p['def'];

        return $v;
    }

    if(isset($sys_data->parameter_security_classes[$p['sc']]))
    {
        if(preg_match($sys_data->parameter_security_classes[$p['sc']],$v) == 1)
            return $v;
    }
    run_hook('parameter_security_error',$name,$p['sc']);
    load_loc($site_config->param_event_locations['security'],$name,$p['sc']);
    return NULL;
}

/** Returns true if the given named parameter is defined. */
function is_par_defined($name)
{
    global $sys_data;
    if(isset($sys_data->content->par[$name]))
        return true;
    return false;
}

/** Checks if the given string is meet the requirements of
 *  the specified parameter_class (See par_def()) 
 *  @package core */
function check_str($string,$security_class)
{
    global $sys_data;
    if(isset($sys_data->parameter_security_classes[$security_class]))
        if(preg_match($sys_data->parameter_security_classes[$security_class],$string) == 1)
            return true;
    return false;
}

/** Registers a new (user defined) parameters security class - regex 
 *  @package core */
function register_parameter_security_class($name,$regex)
{
    global $sys_data;
    if(!isset($sys_data->parameter_security_classes[$name]))
        $sys_data->parameter_security_classes[$name] = $regex;
}

/** Returns an array which collected from routes tag elements (begins with #)
 *  The keys will be the tag value of the route
 *  The values will be the location of the route 
 *  @package core */
function routes_tag_array($tag)
{
    global $sys_data;

    $r = [];
    foreach($sys_data->loaded_routes as $route)
        if(isset($route['#'.$tag]))
            $r[$route['#'.$tag]] = $route['path'];
    return $r;
}

/** Resample an image keeping ascpect ration.
 *  @param string $orig The original file path & name
 *  @param string $new The resampled file path & name
 *  @param mixed $new_w The new requested width of image, may changed maintain the corrent ascpect ratio
 *  @param mixed $new_h The new requested height of image, may changed maintain the corrent ascpect ratio
 *  @package core */
function image_resample($orig,$new,$new_w,$new_h)
{
    $type = exif_imagetype($orig);
    if($type != IMAGETYPE_JPEG && $type != IMAGETYPE_JPEG2000 &&
       $type != IMAGETYPE_PNG  && $type != IMAGETYPE_GIF )
        return;

    list($width_orig, $height_orig) = getimagesize($orig);

    $width = $new_w;
    $height = $new_h;
    $ratio_orig = $width_orig/$height_orig;
    if ($width/$height > $ratio_orig)
        $width = $height*$ratio_orig;
    else
        $height = $width/$ratio_orig;

    $image_copy = imagecreatetruecolor($width,$height);

    $image = NULL;
    if($type == IMAGETYPE_JPEG || $type == IMAGETYPE_JPEG2000)
        $image = imagecreatefromjpeg($orig);
    if($type == IMAGETYPE_PNG)
        $image = imagecreatefrompng($orig);
    if($type == IMAGETYPE_GIF)
        $image = imagecreatefromgif($orig);

    imagecopyresampled($image_copy, $image, 0, 0, 0, 0, $width,$height,$width_orig,$height_orig);

    if($type == IMAGETYPE_JPEG || $type == IMAGETYPE_JPEG2000)
        imagejpeg($image_copy,$new, 92);
    if($type == IMAGETYPE_PNG)
        imagepng($image_copy,$new,9);
    if($type == IMAGETYPE_GIF)
        imagegif($image_copy,$new);

    imagedestroy($image);
    imagedestroy($image_copy);
}

/** Rotate an image with the specified angle.
 *  @param string $orig The original file path & name
 *  @param string $new The resampled file path & name
 *  @param int $angle The rotation angle
 *  @package core */
function image_rotate($orig,$new,$angle)
{
    $type = exif_imagetype($orig);
    if($type != IMAGETYPE_JPEG && $type != IMAGETYPE_JPEG2000 &&
        $type != IMAGETYPE_PNG  && $type != IMAGETYPE_GIF )
        return;

    $image = NULL;
    if($type == IMAGETYPE_JPEG || $type == IMAGETYPE_JPEG2000)
        $image = imagecreatefromjpeg($orig);
    if($type == IMAGETYPE_PNG)
        $image = imagecreatefrompng($orig);
    if($type == IMAGETYPE_GIF)
        $image = imagecreatefromgif($orig);

    $rotated = imagerotate($image,$angle,0);

    if($type == IMAGETYPE_JPEG || $type == IMAGETYPE_JPEG2000)
        imagejpeg($rotated,$new, 92);
    if($type == IMAGETYPE_PNG)
        imagepng($rotated,$new,9);
    if($type == IMAGETYPE_GIF)
        imagegif($rotated,$new);

    imagedestroy($rotated);
}

function div($class,$content)
{
    return "<div class=\"$class\">$content</div>";
}

/**
 * Sends a html bodied e-mail via php email()
 * @param string $to The email address where the email is send
 * @param string $from The sender email address (Somebody <somewhere@here.com)
 * @param string $subject The subject of the email
 * @param string $message The message body of the email. This string is inserted in <body> tag. */
function mail_html($to,$from,$subject,$message)
{
    global $site_config;
    $mess_s = "<html>".
                "<head><title>$subject</title></head>".
                "<body>$message</body>".
              "</html>";
    $headers = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
    $headers .= "From: $from" . "\r\n";
    if($site_config->custom_mail_sender != NULL && is_callable($site_config->custom_mail_sender))
    {
        return call_user_func($site_config->custom_mail_sender,[
                                    "from" => $from,
                                    "to" => $to,
                                    "subject" => $subject,
                                    "htmlbody" => $mess_s,
                                ]);
    }
    return mail($to, $subject, $mess_s, $headers);
}

/** Invoke a hook in all enabled modules that implement it.
 *  The first parameter is the name of the hooks to run.
 *  Other parameters are passed to the modules.
 *  The run_hook("green") runs
 *      the "hook_custom_green" function in "custom" named module and
 *      the "hook_daves_green" function in "daves" named module.
 *  The first parameter is the name of the hook.
 *  The other parameters are passed to the hook functions 
 *  @package core */
function run_hook()
{
    global $sys_data;
    $args = func_get_args();
    $name = array_shift($args);
    $ret_array = [];
    if(array_key_exists($name,$sys_data->available_hooks))
        foreach($sys_data->available_hooks[$name] as $fn)
        {
            $v = call_user_func_array($fn,$args);
            if(is_array($v))
                $ret_array = array_merge($ret_array,$v);
            else
                array_push($ret_array,$v);
        }
    return $ret_array;
}

// Core system hooks

/** @ignore system defined hook */
function hook_core_before_start()
{
    global $site_config;
    add_js_file('/sys/jquery.js');
    //System's javascript file
    add_js_file('/sys/core.js');
    add_header('<meta name="Content-Language" content="'.$site_config->lang.'" />'."\n");
    add_css_file('/sys/ckcomm.css');

    global $sys_data;
    global $site_config;
    if($sys_data->content->icon == '')
        set_icon($site_config->site_icon);

    if($sys_data->r != NULL && isset($sys_data->r['parameters']) && is_array($sys_data->r['parameters']))
    {
        $pars = $sys_data->r['parameters'];
        foreach($pars as $pname => $p)
        {
            if(is_array($p))
            {
                par_def($pname,
                        isset($p['security']) ? $p['security'] : 'no',
                        isset($p['source']) ? $p['source'] : 'all',
                        isset($p['acceptempty']) ? $p['acceptempty'] : true,
                        isset($p['default']) ? $p['default'] : NULL,
                        isset($p['required']) ? $p['required'] : NULL);
            }
            else
            {
                par_def($pname,$p,'all',true);
            }
        }
    }
}

/** @ignore system defined hook */
function hook_core_defineroute()
{
    $r = [];
    $r[] = ["path" => "not_configured_startpage", "callback" => "core_notconfigured_startpage"   ];
    $r[] = ["path" => "emptycache",               "callback" => "pc_core_emptycache"             ];

    $r[] = ["path" => "notfound",                 "callback" => "core_notfound_page",
            "theme" => "base_page","redirectonly" => ""];
    $r[] = ["path" => "param_security_error",     "callback" => "core_paramsecurityerr_page",
            "theme" => "base_page","redirectonly" => ""];
    $r[] = ["path" => "param_undefined_error",    "callback" => "core_paramundefinederror_page",
            "theme" => "base_page","redirectonly" => ""];
    $r[] = ["path" => "error",                    "callback" => "core_customerror_page",
            "theme" => "base_page","redirectonly" => ""];
    $r[] = ["path" => "missing_parameter_error",  "callback" => "core_missingparametererror_page",
            "theme" => "base_page","redirectonly" => ""];

    $r[] = ["path" => "connector/connect/{target}", "callback" => "core_connector", "type" => "ajax"];
    $r[] = ["path" => "connector/fill/{target}"   , "callback" => "core_connector", "type" => "ajax"];
    $r[] = ["path" => "connector/route/{target}"  , "callback" => "core_connector" ];
    return $r;
}

/** @ignore Handles "connector/..." routes (autorouted callbacks) */
function core_connector()
{
    par_def('target','text0nsne');
    $encoded = par('target');
    if(strlen($encoded) > 128)
        return '';
    $target = crockford32_decode($encoded);
    if($target == null || $target == '')
        return '';
    if(substr($target, 0, 12) != 'extcallable_' || !function_exists($target))
        return '';

    if(substr(current_loc(),0,16) == 'connector/route/')
        return call_user_func($target);
    if(substr(current_loc(),0,18) == 'connector/connect/')
        call_user_func($target);
    if(substr(current_loc(),0,15) == 'connector/fill/')
    {
        par_def('changeid','text1ns');
        $changeid = par('changeid');
        ajax_add_html('#'.$changeid,call_user_func($target));
    }
    return '';
}

function sysEncodeConnectorTarget($fnc,$target)
{
    if(substr($target,0,12) != 'extcallable_')
        throw new Exception('The external callable function have to prefixed by "extcallable_" !');
    if(strlen($target) > 64)
        throw new Exception('The external callable function name have to be shorter than 64 character!');

    if($fnc == 'connect')
        return 'connector/connect/' . crockford32_encode($target);
    if($fnc == 'fill')
        return 'connector/fill/' . crockford32_encode($target);
    if($fnc == 'route')
        return 'connector/route/' . crockford32_encode($target);
}

/** Creates an ajax processed link */
function lx($text,$loc,array $options = [],array $query = [])
{
    if(!isset($options['class']))
        $options['class'] = 'use-ajax';
    if(strpos($options['class'], 'use-ajax') === false)
        $options['class'] .= ' use-ajax';
    return l($text,$loc,$options,$query);
}

/** Creates an autorouted ajax processed callback link */
function lxc($text,$targetcallback,array $options = [],array $query = [])
{
    return lx($text,sysEncodeConnectorTarget('connect',$targetcallback),$options,$query);
}

/** Creates a html code parts which filled by the specified ajax callback function. */
function fill_through_ajax($target,$queryparams = [],$bypass = false)
{
    if($bypass)
    {
        if(substr($target, 0, 13) != 'fillcallback_' || function_exists($target))
            return '<div>' . call_user_func($target) . '</div>';
        return '';
    }
    $htmlid = 'autofill_'.substr(hash('md5',$target,FALSE),0,16).'_'.substr(time(),6).'_'.rand(1000,9999);
    $queryparams['changeid'] = $htmlid;
    $aurl = url(sysEncodeConnectorTarget('fill',$target),$queryparams);
    return "<div id=\"$htmlid\">".
             "<script>jQuery(document).ready(function(){ executeCodkepAjaxCall('$aurl'); });</script>".
           "</div>";
}

function crockford32_encode($data)
{
    $chars = '0123456789abcdefghjkmnpqrstvwxyz';
    $mask = 0b11111;
    $dataSize = strlen($data);
    $res = '';
    $remainder = 0;
    $remainderSize = 0;
    for($i = 0; $i < $dataSize; $i++)
    {
        $b = ord($data[$i]);
        $remainder = ($remainder << 8) | $b;
        $remainderSize += 8;
        while($remainderSize > 4)
        {
            $remainderSize -= 5;
            $c = $remainder & ($mask << $remainderSize);
            $c >>= $remainderSize;
            $res .= $chars[$c];
        }
    }
    if($remainderSize > 0)
    {
        $remainder <<= (5 - $remainderSize);
        $c = $remainder & $mask;
        $res .= $chars[$c];
    }
    return $res;
}

function crockford32_decode($data)
{
    $map = [
        '0' => 0 ,'1' => 1 ,'2' => 2 ,'3' => 3 ,'4' => 4 ,
        '5' => 5 ,'6' => 6 ,'7' => 7 ,'8' => 8 ,'9' => 9 ,
        'A' => 10,'a' => 10,'b' => 11,'c' => 12,'d' => 13,
        'e' => 14,'f' => 15,'g' => 16,'h' => 17,'j' => 18,
        'k' => 19,'m' => 20,'n' => 21,'p' => 22,'q' => 23,
        'r' => 24,'s' => 25,'t' => 26,'v' => 27,'w' => 28,
        'x' => 29,'y' => 30,'z' => 31,
    ];

    $data = strtolower($data);
    $dataSize = strlen($data);
    $buf = 0;
    $bufSize = 0;
    $res = '';
    for($i = 0; $i < $dataSize; $i++)
    {
        $c = $data[$i];
        if(!isset($map[$c]))
            return null;

        $b = $map[$c];
        $buf = ($buf << 5) | $b;
        $bufSize += 5;
        if($bufSize > 7)
        {
            $bufSize -= 8;
            $b = ($buf & (0xff << $bufSize)) >> $bufSize;
            $res .= chr($b);
        }
    }
    return $res;
}

function pc_core_emptycache()
{
    if(ccache_check())
    {
        ccache_delete('hookcache');
        ccache_delete('routecache');
        run_hook('emptycache');
        return 'All cache is cleared.';
    }
    return 'Cache is not available!';
}

/** @ignore system defined hook */
function hook_core_before_deliver($content)
{
    global $sys_data;
    global $site_config;
    if($sys_data->content->type != 'html')
        return;
    if(isset($site_config->show_generation_time) && $site_config->show_generation_time)
    {
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];
        $finish = $time;
        $total_time = round(($finish - $sys_data->generation_time_start), 4);
        $content->body .= "\n";
        $content->body .= 'Page generated in '.$total_time.' seconds.';
    }
}

/** @ignore system defined hook */
function hook_core_outbound_internal_url($u)
{
    global $site_config;
    if(!$site_config->clean_urls)
        return;
    $u->additional_path = $u->path;
    $u->mainscript = '';
    $u->path = '';
}

// System functions

/** @ignore Initializes the system's hook table. Collect / Select and structures the defined hooks */
function sys_init_hook_table()
{
    global $sys_data;

    $sys_data->available_hooks = ccache_get('hookcache');
    if($sys_data->available_hooks != NULL)
        return;

    global $sys_modules;
    $user_defined_functions = get_defined_functions()["user"];
    $sys_data->available_hooks = [];
    $module_names = array_keys($sys_modules);
    $module_hookhelper=[];
    foreach($module_names as $mname)
        $module_hookhelper["hook_".$mname."_"] = strlen("hook_".$mname."_");
    foreach($user_defined_functions as $funcname)
    {
        if($funcname[0] == 'h' && 
           isset($funcname[1]) && $funcname[1] == 'o' &&
           isset($funcname[2]) && $funcname[2] == 'o' &&
           isset($funcname[3]) && $funcname[3] == 'k' &&
           isset($funcname[4]) && $funcname[4] == '_' )
        {
            foreach($module_hookhelper as $hpx => $hpxs)
            {
                if(substr($funcname,0,$hpxs) == $hpx)
                {
                    $sys_data->available_hooks[substr($funcname,$hpxs)][] = $funcname;
                }
            }
        }
    }

    run_hook('hooktable_generated');
    ccache_store('hookcache',$sys_data->available_hooks,3600);
}

/** @ignore Reset the buffered content */
function sys_reset_content()
{
    global $sys_data;
    $sys_data->content->html_start = '';
    $sys_data->content->html_end = '';
    $sys_data->content->head = [];
    $sys_data->content->style = '';
    $sys_data->content->title = '';
    $sys_data->content->icon = '';
    $sys_data->content->generated = '';
    $sys_data->content->body = '';
    $sys_data->content->commands = [];
    $sys_data->content->par = [];
    $sys_data->content->pageparts = [];
}

/** @ignore This function does the required preprocessing of collected routes before the real routing. */
function sys_preprocess_routes()
{
    global $site_config;
    global $sys_data;

    foreach($sys_data->loaded_routes as $key => $route)
    {
        $sys_data->loaded_routes[$key]['t'] = 0;
        if(strpos($route['path'],'{') === false && strpos($route['path'],'}') === false)
        {
            $sys_data->loaded_routes[$key]['mtype'] = 1; //no need regex match
            continue;
        }
        if(strpos($route['path'],'{') !== false)
        {
             if(strpos($route['path'],'}') !== false)
             {
                 $matches = [];
                 $url_par_names = [];
                 if(preg_match_all('/\{([a-zA-Z0-9\-\_]+)\}/',$route['path'],$matches,PREG_SET_ORDER))
                 {
                     $i = 0;
                     $rex = $route['path'];
                     foreach($matches as $m)
                     {
                         $rex = str_replace($m[0],'([\p{L}a-zA-Z0-9\-\_\.]+)',$rex);
                         $url_par_names[$i] = $m[1];
                         ++$i;
                     }
                     $rex = str_replace('/','\/',$rex);
                     $sys_data->loaded_routes[$key]['rex'] = '/^'.$rex.'$/u';
                     $sys_data->loaded_routes[$key]['mtype'] = 2; //regex match
                     $sys_data->loaded_routes[$key]['url_par_names'] = $url_par_names;
                 }
                 else
                     $sys_data->loaded_routes[$key]['mtype'] = 8; // error 
             }
             else
                 $sys_data->loaded_routes[$key]['mtype'] = 9; // error
        }
    }
}

/** @ignore The rounting function.
 *  Selects and execute the necessary route's callbacks/file. */
function sys_route($location,array $args = [])
{
    global $site_config;
    global $sys_data;

    $html = '';
    $hit = false;
    $loc = new stdClass();
    $loc->original = $location;
    $loc->executed = $location;
    run_hook('inbound_url',$loc);
    foreach($sys_data->loaded_routes as $route)
    {
        $rtmatch = false;
        if($route["mtype"] == 1 && $route["path"] == $loc->executed)
            $rtmatch = true;
        if($route["mtype"] == 2)
        {
            $matches = [];
            if(preg_match($route["rex"],$loc->executed,$matches))
            {
                $url_parameters = [];
                for($i = 1;$i<count($matches);++$i)
                    $route['url_par_values'][$i-1] = $matches[$i];
                $rtmatch = true;
            }
        }
        if($rtmatch)
        {
            $hit = true;
            $sys_data->current_route = $loc->executed;
            $sys_data->r = $route;
            $templ = $sys_data->loaded_themes[$site_config->default_theme_name];
            if(isset($route['theme']) && array_key_exists($route['theme'],$sys_data->loaded_themes))
                $templ = $sys_data->loaded_themes[$route['theme']];

            if(isset($route['type']) && $route['type'] == 'raw')
                $sys_data->content->type = 'raw';
            if(isset($route['type']) && $route['type'] == 'json')
                $sys_data->content->type = 'json';
            if(isset($route['type']) && $route['type'] == 'ajax')
                $sys_data->content->type = 'ajax';

            run_hook("before_start");

            if(isset($route["pre_callback"]))
                call_user_func_array($route["pre_callback"],$args);

            call_user_func($templ['generators']['runonce'],$sys_data->content);
            if($sys_data->content->type == 'html')
            {
                $sys_data->content->html_start = call_user_func($templ['generators']['htmlstart'],$route);
                if(isset($route["title"]) && $route["title"] != '')
                    $sys_data->content->title = $route["title"];

                foreach($templ["pageparts"] as $partname)
                    $sys_data->content->pageparts[$partname] =
                        sys_get_sitearea($partname,'block');

            }

            //before_generation
            if($sys_data->content->type != 'raw' && $sys_data->content->type != 'json')
                $sys_data->content->generated .= implode(run_hook("before_generation",$route,$templ));

            //main generation
            if(isset($route["callback"]))
            {
                if($sys_data->content->type == 'raw' || $sys_data->content->type == 'json')
                    $sys_data->content->generated = call_user_func_array($route["callback"], $args);
                else
                    $sys_data->content->generated .= call_user_func_array($route["callback"], $args);
            }
            if(isset($route["file"]) && file_exists($route["file"]))
            {
                ob_start();
                include $route["file"];
                $sys_data->content->generated .= ob_get_clean();
            }

            //after_generation
            if($sys_data->content->type != 'raw' && $sys_data->content->type != 'json')
                $sys_data->content->generated .= implode(run_hook("after_generation",$route,$templ));

            if($sys_data->content->type == 'html')
            {
                $sys_data->content->body .= call_user_func($templ['generators']['body'],$sys_data->content,$route);
                $sys_data->content->html_end .= call_user_func($templ['generators']['htmlend'], $route);
            }
            break;
        }
    }
    $sys_data->current_route = NULL;

    if(!$hit)
        load_loc($site_config->notfound_location);
}

/** @ignore Assamble the page before the final delivery according to the type of the request. */
function sys_assemble($c)
{
    if($c->type == 'raw')
        return $c->generated;
    if($c->type == 'json')
    {
        header('Content-Type: application/json');
        return json_encode($c->generated);
    }
    if($c->type == 'ajax')
    {
        if($c->generated != '')
            $c->commands[] = ['showol',$c->generated,0];
        return json_encode($c->commands);
    }

    return $c->html_start .
           "<head>\n" .
           "<title>" . $c->title . "</title>\n" .
           '<link rel="shortcut icon" href="'.$c->icon.'"/>'."\n".
           implode($c->head) .
           ($c->style == '' ? '' : "<style>\n".$c->style."\n</style>") .
           "\n</head>\n" .
           $c->body .
           $c->html_end;
}

/** Set CORS headers according to the $site_config->cors_requests_enabled_hosts settings */
function core_set_cors_headers()
{
    global $site_config;
    if($site_config->cors_requests_enabled_hosts != '')
    {
        header("Access-Control-Allow-Origin: ".$site_config->cors_requests_enabled_hosts);
        header("Access-Control-Allow-Methods: GET,POST,PUT,PATCH,DELETE,OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type");
    }
}
/** @ignore Page callback of the "notfound" location */
function core_notfound_page()
{
    http_response_code(404);
    header("HTTP/1.0 404 Not Found");
    set_title(t('Location not found'));
    ob_start();
    print '<h2>'.t('Location not found')."</h2>\n";
    print '(404) '.t('Page or content not found')."\n";
    return ob_get_clean();
}

/** @ignore Page callback of the "error" location */
function core_customerror_page($text='',$title='')
{
    if($title == '')
        $title = 'Site error.';
    set_title($title);
    ob_start();
    print "<h2>$title</h2>\n";
    if($text != '')
        print $text.'<br/> ';
    return ob_get_clean();
}

/** @ignore Page callback of the "param_security_error" location */
function core_paramsecurityerr_page($name = '',$type = '')
{
    set_title(t('Parameter security error'));
    ob_start();
    print '<h2>'.t('Parameter security error')."</h2>\n";
    if(isset($name) && $name != '' && isset($type) && $type != '')
        print t('Parameter "_name_" does not match with type "_type_"',
                ['_name_' => $name,'_type_' => $type]).'<br/>';
    return ob_get_clean();
}

/** @ignore Page callback of the "missing_parameter_error" location */
function core_missingparametererror_page($name = '',$text = '')
{
    set_title(t('Missing parameter error'));
    ob_start();
    print '<h2>'.t('Missing parameter error')."</h2>\n";
    if(isset($name) && $name != '' && isset($text) && $text != '')
        print t('This page require the parameter "_name_" <br/>_text_',
                ['_name_' => $name,'_text_' => $text]).'<br/>';
    return ob_get_clean();
}

/** @ignore Page callback of the "param_undefined_error" location */
function core_paramundefinederror_page($name = '')
{
    set_title(t('Undefined parameter error'));
    ob_start();
    print '<h2>'.t('Undefined parameter error')."</h2>\n";
    if(isset($name) && $name != '')
        print t('This page try to access a parameter which is undefined "_name_"',
                ['_name_' => $name]).'<br/>';
    return ob_get_clean();
}

/** @ignore Page callback of the startpage set by default
 *  Location of "not_configured_startpage"
 *  This will be show if no override in _settings.php */
function core_notconfigured_startpage()
{
    global $site_config;

    set_title("Welcome");
    ob_start();
    print "<h2>Welcome on CodKep start page!</h2>";
    print '<img style="float:left; margin: 2 8 2 8;" src="'.url('/sys/images/cklogo_small.png').'"/>';
    print "<p>If you can read this, the CodKep framework was succefully installed.<br/>";
    print "See ".l("CodKep documentation","doc/codkep")." to start using the system. ";
    print "<small>(<code>/doc/codkep</code>)</small></p>";
    //print "You can customize the site by edit <i>site/_settings.php</i> and add modules to <i>site/_modules.php</i>";

    print 'Requirements';
    print core_requirements_table();
    if(!$site_config->hide_module_intros)
    {
        print "<h3>Message from modules:</h3>";
        $module_introducers = run_hook('introducer');
        print '<div class="module_introducers">';
        foreach($module_introducers as $name => $string)
        {
            if($string == '')
                continue;
            print '<div class="single_module_introducer" style="margin: 10px 0px 10px 0px;">';
            print '<strong>' . $name . '</strong>';
            print '<div class="rstr" style="margin: 0px; padding: 0px 0px 0px 15px;">';
            print $string;
            print '</div>'; //.rstr
            print '</div>'; //.single_module_introducer
        }
        print '</div>';
    }
    return ob_get_clean();
}

/** @ignore Shown a table with the CodKep requirements
 *  Part of location "not_configured_startpage" */
function core_requirements_table()
{
    ob_start();
    $pdo_has = class_exists('PDO');
    $gd_has = function_exists('gd_info');
    $json_has = function_exists('json_encode');
    $apc_has = false;
    if((extension_loaded('apc') || extension_loaded('apcu')) && ini_get('apc.enabled'))
        $apc_has = true;

    print '<table border="1" style="background-color: white; border-collapse: collapse; margin: 5px;">';
    print '<tbody>';

    print '<tr>';
    print '<td class="normal">php PDO Data Objects</td>';
    print '<td class="'.($pdo_has ? 'green':'red').'">'.($pdo_has ? 'Installed' : 'Not installed').'</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="normal">php GD graphics library</td>';
    print '<td class="'.($gd_has ? 'green':'red').'">'.($gd_has ? ('Installed ('.gd_info()['GD Version'].')') : 'Not installed').'</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="normal">Alternative PHP Cache - APC(u) (Optional)</td>';
    print '<td class="'.($apc_has ? 'green':'yellow').'">'.($apc_has ? 'Installed' : 'Not installed').'</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="normal">php JSON extension</td>';
    print '<td class="'.($json_has ? 'green':'red').'">'.($json_has ? 'Installed' : 'Not installed').'</td>';
    print '</tr>';

    print implode(run_hook('check_module_requirements'));

    print '</tbody>';
    print '</table>';
    add_style('.normal {background-color: #aaaaaa;}
               .green {background-color: #66ff66;}
               .yellow {background-color: #ffff00;}
               .red {background-color: #ff6666;}');
    return ob_get_clean();
}

function hook_core_introducer()
{
    return ['Core' => l('emptycache','emptycache')];
}

/** Returns true if the $host seems valid host name
 *  @package core */
function ck_valid_http_host($host)
{
    // Limit the length of the host name to 1000 bytes to prevent DoS attacks with
    // long host names.
    // Limit the number of subdomains and port separators to prevent DoS attacks
    // in conf_path().
    return strlen($host) <= 256
            && substr_count($host, '.') <= 64
            && substr_count($host, ':') <= 32
            && preg_match('/^\[?(?:[a-zA-Z0-9-:\]_]+\.?)+$/', $host);
}

function ck_valid_ip_address($addr)
{
    if($addr == NULL || $addr == '' || strlen($addr) > 45)
        return false;
    if(preg_match('/^[\:\.0-9a-f]+$/',$addr) !== 1)
        return false;
    return true;
}

/** @ignore */
function encOneway62($binarydata,$maxlength)
{
    $chars = 'v389NdSTwxL267JKghioMtuXABCmnYZabFGHI45qrUVWjeRfyz01cEpQsDOPkl';
    $ecstr = str_replace('=','',base64_encode($binarydata));
    $length = strlen($ecstr);
    for($i = 0 ; $i < $length ; ++$i)
        while($ecstr[$i] == '+' || $ecstr[$i] == '/')
            $ecstr[$i] = $chars[(ord($ecstr[$i]) + $i)%62];
    return substr($ecstr,0,$maxlength);
}

/** @ignore */
function generate_authcookie_name()
{
    global $sys_data;
    global $site_config;

    $is_https = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on';
    $http_protocol = $is_https ? 'https' : 'http';
    $base_root = $http_protocol . '://' . $_SERVER['HTTP_HOST'];
    $sys_data->base_url = $base_root.$site_config->base_path;
    $cookie_domain = $site_config->cookie_domain;
    if($cookie_domain == NULL)
        $cookie_domain = $sys_data->base_url;
    $prefix = $is_https ? 'CACS' : 'CAC';
    $sys_data->authcookie_name =
        $prefix . encOneway62(hash('sha256',$site_config->authcookie_name_salt.$cookie_domain.get_remote_address(),true),32);
}
/** Returns the remote (client) ip address. (Validated) */
function get_remote_address()
{
    global $sys_data;
    return $sys_data->sys_remote_address;
}

/** Returns the requested host name */
function get_requested_host()
{
    global $sys_data;
    return $sys_data->sys_requested_host;
}

/*  @ignore */
function sys_determine_request_data()
{
    global $sys_data;
    global $site_config;

    if(!ck_valid_http_host($_SERVER['HTTP_HOST']))
    {
        http_response_code(400);
        header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
        exit;
    }
    $sys_data->sys_requested_host = $_SERVER['HTTP_HOST'];
    if(strlen($_SERVER['REQUEST_TIME']) < 16 && ctype_digit($_SERVER['REQUEST_TIME']))
        $sys_data->request_time = $_SERVER['REQUEST_TIME'];
    else
        $sys_data->request_time = strval(time());

    if($site_config->srv_remoteaddr_spec != NULL &&
       isset($_SERVER[$site_config->srv_remoteaddr_spec]) &&
       ck_valid_ip_address($_SERVER[$site_config->srv_remoteaddr_spec]) )
    {
        $sys_data->sys_remote_address = $_SERVER[$site_config->srv_remoteaddr_spec];
        return;
    }

    if(ck_valid_ip_address($_SERVER['REMOTE_ADDR']))
    {
        $sys_data->sys_remote_address = $_SERVER['REMOTE_ADDR'];
        return;
    }

    http_response_code(400);
    header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
    exit;
}

/*  @ignore */
function sitearea_definition_cmp_callback($a,$b)
{
    if($a['index'] < $b['index'])
        return -1;
    if($a['index'] > $b['index'])
        return 1;
    return 0;
}

/** Gererate and returns the html code of the specified site area.
 *  It will invoke the "sitearea_$areaname" hook, and will build the codes with the appropriate callback
 *  @package core */
function sys_get_sitearea($areaname,$classforblocks = '')
{
    $area = '';
    $definitions = run_hook("sitearea_".$areaname);
    usort($definitions,'sitearea_definition_cmp_callback');
    foreach($definitions as $d)
    {
        $rv = '';
        if(isset($d["callback"]))
            $rv = call_user_func($d['callback']);
        if(isset($d["file"]) && file_exists($d["file"]))
        {
            ob_start();
            include $d["file"];
            $rv = ob_get_clean();
        }
        $pass = new stdClass();
        $pass->html = &$rv;
        run_hook('alter_sitearea',$areaname,$d['name'],$pass);
        if($rv != '')
        {
            $c = $classforblocks;
            if(isset($d["class"]))
            {
                if(strlen($c) > 0)
                    $c .= ' ';
                $c .= $d["class"];
            }
            $area .= "<div class=\"$c\">$rv</div>";
        }
    }
    return $area;
}

/* Generate html menu structure from menu array */
function menu_expand(array $m,$pad='',$toplevelclassname = 'menu',$level = 0)
{
    ob_start();
    print "$pad<ul class=\"";
    print $toplevelclassname;
    if($toplevelclassname != '')
        print ' ';
    print $level == 0 ? 'topmul' : 'submul';
    print "\">\n";
    end($m);
    $l = key($m);
    reset($m);
    $f = key($m);
    foreach($m as $k => $v)
    {
        $lopts = [];
        $classes = ($level == 0 ? 'topmli' : 'submli') . ' ';
        if(is_array($v) && isset($v['__special__']) && $v['__special__'])
        {
            if(isset($v['class']))
                $classes = $v['class'].' ';
            if(isset($v['lopts']))
                $lopts = $v['lopts'];
            $v = $v['href'];
        }

        if($f == $k)
            $classes .= "first ";
        if($l == $k)
            $classes .= "last ";
        if(is_array($v))
            $classes .= "expanded ";
        else
            $classes .= "leaf ";

        $vv = $v;
        while(is_array($vv))
          $vv = array_values($vv)[0];

        print "$pad <li class=\"$classes\">".l($k,is_array($v) ? $vv : $v,$lopts);
        if(is_array($v))
        {
            print "\n" . menu_expand($v, $pad . '  ','',$level + 1);
            print "$pad </li>\n";
        }
        else
        {
            print "</li>\n";
        }
    }
    print "$pad</ul>\n";
    return ob_get_clean();
}

/**
 * Build a menu structure ul-li according to the mainmenu settings.
 * @return string html
 * @package core
 */
function generate_menu_structure($pad='',$toplevelclassname = 'menu')
{
    global $site_config;
    $menu = $site_config->mainmenu;

    if($site_config->mainmenu_append_tag_mainmenu)
    {
        $mma = routes_tag_array('mainmenu');
        foreach($mma as $mi => $mv)
            $menu[$mi] = $mv;
    }
    $pass = new stdClass();
    $pass->menu = &$menu;
    $pass->pad = &$pad;
    run_hook('alter_mainmenu',$pass);
    return menu_expand($menu,$pad,$toplevelclassname);
}

/** Gives the current state of an on off switch according to the parameter name */
function is_OnOff($parametername)
{
    if(par($parametername) == 'on')
        return true;
    return false;
}

/** Returns the html codes of a switchable on - off switch */
function put_OnOffSwitch($label,$parametername,$baseurl,$excluded_parameters = [],$theme = 'default')
{
    global $site_config;

    $to = '';
    if(par($parametername) == 'on')
    {
        $imgurl = url($site_config->onoffswitch_icons[$theme][0]);
        $to = 'off';
    }
    else
    {
        $imgurl = url($site_config->onoffswitch_icons[$theme][1]);
        $to = 'on';
    }
    $url = url($baseurl,parameters([$parametername => $to],[],$excluded_parameters));
    return "<table border=\"0\" class=\"onoffsw_tbl_mini_switch\"><tr>
                <td style=\"vertical-align:middle;\">
                    <a href=\"$url\" class=\"onoffsw_lnk_mini_switch\"><img src=\"$imgurl\"/></a></td>
                <td style=\"vertical-align:middle;\">
                    <a href=\"$url\" class=\"onoffsw_lnk_mini_switch\">$label</a></td>
            </tr></table>";
}

// ==================================================
// hook_theme - to define the "base_page" theme
// ==================================================

/** @ignore */
function hook_core_theme()
{
    $items = [];
    $items['base_page'] = [
                "pageparts" => [],
                "generators" => [
                    "runonce"   => "basepage_runonce",
                    "htmlstart" => "basepage_htmlstart",
                    "htmlend"   => "basepage_htmlend",
                    "body"      => "basepage_body",
                 ],
        ];
    return $items;
}

/** @ignore Runs on every page generation */
function basepage_runonce($content)
{
    header('Content-Type: text/html; charset=utf-8');

    add_header('<meta http-equiv="Content-Type" content="Text/Html;Charset=UTF-8" />'."\n");
    add_header('<meta name="viewport" content="width=device-width, initial-scale=1.0" />'."\n");
    add_header('<meta http-equiv="Cache-Control" content="no-cache" />'."\n");
    add_header('<meta http-equiv="Pragma" content="no-cache" />'."\n");
}

/** @ignore */
function basepage_htmlstart($route)
{
    ob_start();
    print "<!DOCTYPE html>\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
    return ob_get_clean();
}

/** @ignore */
function basepage_body($content,$route)
{
    ob_start();
    print "<body>\n";
    print "<div class=\"content\">\n";
    print $content->generated;
    print "</div>\n"; //content
    print "</body>\n";
    return ob_get_clean();
}

/** @ignore */
function basepage_htmlend($route)
{
    ob_start();
    print "</html>\n";
    return ob_get_clean();
}

function hook_core_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = ['start' => ['path' => 'sys/doc/start.mdoc','index' => true , 'imagepath' => '/sys/doc/images']];
        $docs[] = ['modules' => ['path' => 'sys/doc/modules.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
        $docs[] = ['routes' => ['path' => 'sys/doc/routes.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
        $docs[] = ['hooks' => ['path' => 'sys/doc/hooks.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
        $docs[] = ['parameters' => ['path' => 'sys/doc/parameters.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
        $docs[] = ['structure' => ['path' => 'sys/doc/structure.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
        $docs[] = ['ajax' => ['path' => 'sys/doc/ajax.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
    }
    return $docs;
}

/**
 * This hook is run after the hook table is generated but the result is not stored to the cache yet.
 * @package core */
function _HOOK_hooktable_generated() {}

/**
 * This hook is run at first before all hooks and before the settings is loaded.
 * It is typically used to set up default values which can be set in settings.php by sites/users.
 * When this hook is called, the themes and routes is not loaded yet.
 * @package core */
function _HOOK_boot() {}

/**
 * This hook is run after the settings.php is loaded.
 * It can works with the loaded settings but the themes and routes is not loaded yet.
 * @package core */
function _HOOK_init() {}

/**
 * This hook is run after the routes are loaded.
 * It can works with the loaded settings, themes and routes but the routing process is not started yet.
 * @package core */
function _HOOK_autorun() {}

/**
 * This hook returns an associative array which defines a new theme for the system.
 * This themes ensures the visual structure/environment of the generated content.
  * @package core */
function _HOOK_theme() {}

/**
 * Defines routes/targets for the system.
 * @package core */
function _HOOK_defineroute() {}

/**
 * This hook runs when the data/content is generated and ready to assemble and deliver.
 * This hook has the last chance to modify the data before sended to the client.
 * @param object $content_object The content object of global $system_data.
 *  Contains all things generated by previous routines to deliver the final html.
 * @see _HOOK_after_deliver()
 * @package core */
function _HOOK_before_deliver($content_object) {}

/**
 * This hook runs after the data was sended and flushed.
 * It can do any works which doesn't need to be send to the client.
 * @see _HOOK_before_deliver()
 * @package core */
function _HOOK_after_deliver() {}

/**
 * This hook is runs befor the content generation will started. When this hook called everything is ready to start the execution.
 * The settings are loaded, themes roututes available and the necessary route was selected to execute. The page parameters are also loaded.
 * The return values of this hook is dropped, but it can modify the environment of the execution.
 * If you would like to add content to the generated data use the before_generation and after_generation hooks
 * @package core */
function _HOOK_before_start() {}

/**
 * This hook runs before the current route's content generation is started.
 * The returned data will be prepend to the generated content.
 * @param array $route The selected array of the route which is executed.
 * @param array $theme The selected array of the theme which is used.
 * @return string The returned string will be shown in body html
 * @package core */
function _HOOK_before_generation($route,$theme) {}

/**
 * This hook runs after the current route's content generation is executed.
 * The returned data will be append to the generated content.
 * @param array $route The selected array of the route which is executed.
 * @param array $theme The selected array of the theme which is used.
 * @return string The returned string will be shown in body html
 * @package core */
function _HOOK_after_generation($route,$theme) {}

/**
 * Modified a generated url which targets an internal location of site (defined by defineroute)
 * @param object $url_object An object contains the parts of the url
 * @see _HOOK_outbound_internal_file_url()
 * @see _HOOK_outbound_external_url()
 * @see _HOOK_inbound_url()
 * @package core */
function _HOOK_outbound_internal_url($url_object) {}

/**
 * Modified a generated url which targets a location of site but not defined by defineroute (Probably file urls)
 * @param object $url_object An object contains the parts of the url
 * @see _HOOK_outbound_internal_url()
 * @see _HOOK_outbound_external_url()
 * @see _HOOK_inbound_url()
 * @package core */
function _HOOK_outbound_internal_file_url($url_object) {}

/**
 * Modified a generated url which is appeared to be targets out from the site
 * @param object $url_object An object contains the parts of the url
 * @see _HOOK_outbound_internal_url()
 * @see _HOOK_outbound_internal_file_url()
 * @see _HOOK_inbound_url()
 * @package core */
function _HOOK_outbound_external_url($url_object) {}

/**
 * Modified an inbound url immediatly before the routing process
 * @param object $iu_object An object the original and the modifiable url/location to before the routing
 * @see _HOOK_outbound_internal_url()
 * @see _HOOK_outbound_internal_file_url()
 * @see _HOOK_outbound_external_url()
 * @package core */
function _HOOK_inbound_url($iu_object) {}

/**
 * This hooks can be used to check additional requirements of modules.
 * @return string The returned string will be inserted to the requirements html table
 * @package core */
function _HOOK_check_module_requirements() {}

/** You can assign your content to site areas of the current theme by this hook*/
function _HOOK_sitearea_SITEAREANAME() {}

/** You can modify the generated content of blocks by this hook*/
function _HOOK_alter_sitearea() {}

/** This can modify the menu array before the menu is translated to html structure */
function _HOOK_alter_mainmenu($object) {}

/** The system was dropped the cache. All modules should drop their own caches too. */
function _HOOK_emptycache() {}

/** The hook is activated when a required parameter is missing. */
function _HOOK_parameter_missing($name,$required) {}

/** The hook is activated when a parameter is queried but not defined. */
function _HOOK_parameter_undefined($name) {}

/** The hook is activated when a parameter does not fit in the defined security class. */
function _HOOK_parameter_security_error($name,$sc) {}
//end.
