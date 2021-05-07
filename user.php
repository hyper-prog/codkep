<?php
/*  CodKep - Lightweight web framework core file
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 *
 * User module
 *  Required modules: core,sql (node,forms in case you enabled define_user_nodetype)
 */

define('ROLE_NONE'  ,0);
define('ROLE_ADMIN' ,1);
define('ROLE_EDITOR',2);
define('ROLE_USER'  ,3);

function hook_user_boot()
{
    global $user;
    global $user_module_settings;
    global $codkep_session;
    global $formsalt;

    $formsalt = '';
    $codkep_session = [];
    $user_module_settings = new stdClass();

    $user_module_settings->session_timeout_sec         = 28800; //8 hour
    $user_module_settings->login_timeout_sec           = 0;
    $user_module_settings->login_garbagecoll           = 28800; //8 hour
    $user_module_settings->keychange_interval_sec      = 300; //5 min
    $user_module_settings->disable_remote_blocking     = false;
    $user_module_settings->faillogin_block_count       = 3;
    $user_module_settings->faillogin_block_exipire_sec = 3600; //1 hour

    $user_module_settings->enable_own_passwordchange   = true;
    $user_module_settings->enable_admin_passwordchange = false;

    $user_module_settings->password_scattered          = true;
    $user_module_settings->password_scatter_salt       = 'OiZ+o-*F4buC31Sw]80(=FeX~ge9D#t@';
    $user_module_settings->password_scatter_log2i      = 14;

    $user_module_settings->password_complexity_check      = false;
    $user_module_settings->password_complexity_minlength  = 20;
    $user_module_settings->password_complexity_minlower   = 2;
    $user_module_settings->password_complexity_minupper   = 2;
    $user_module_settings->password_complexity_minnumber  = 2;
    $user_module_settings->password_complexity_cplx       = true;
    $user_module_settings->password_complexity_checkerfnc = NULL;

    $user_module_settings->form_salt                   = 'Rt0o+i_52PosC6=KeS';

    $user_module_settings->define_user_nodetype = true;
    $user_module_settings->sql_tablename        = 'users';
    $user_module_settings->sql_login_column     = 'login';
    $user_module_settings->sql_name_column      = 'name';
    $user_module_settings->sql_role_column      = 'role';
    $user_module_settings->sql_lastlogin_column = 'lastlogin';
    $user_module_settings->sql_password_column  = 'password';
    $user_module_settings->sql_logindisabled_column = 'lindis';

    $user_module_settings->login_title          = 'Login to the site';
    $user_module_settings->login_location       = 'user/login';

    $user_module_settings->user_login_callback  = NULL;
    $user_module_settings->user_logout_callback = NULL;
    $user_module_settings->user_init_callback   = NULL;
    $user_module_settings->user_after_callback  = NULL;

    $user_module_settings->disable_after_deliver_garbage_collection = false;

    $user = new stdClass();
    $user->uid = 0;
    $user->auth = false;
    $user->login = NULL;
    $user->name = NULL;
    $user->role = ROLE_NONE;
}

function hook_user_init()
{
    global $user_module_settings;
    if($user_module_settings->user_init_callback != NULL)
    {
        call_user_func($user_module_settings->user_init_callback);
        return;
    }
    user_init_local();
}

function hook_user_after_deliver()
{
    global $user_module_settings;
    if($user_module_settings->user_after_callback != NULL)
    {
        call_user_func($user_module_settings->user_after_callback);
        return;
    }
    if(!$user_module_settings->disable_after_deliver_garbage_collection)
        user_garbage_collection_local();
}

function hook_user_defineroute()
{
    $items = [];
    $items[] = [
        "path" => "user/login",
        "callback" => "user_login_page",
        "title" => "User login page",
        "theme" => "base_page",
        "parameters" => [
            'login'       => ['security' => 'text3ns','source' => 'post'],
            'password'    => ['security' => 'text3ns','source' => 'post'],
            'orignal_loc' => ['security' => 'text4'  ,'source' => 'post'],
            'fid'         => ['security' => 'text1ns','source' => 'post'],
            'loginbutton' => ['security' => 'text2ns','source' => 'post'],
        ],
    ];

    $items[] = [
        "path" => "user/logout",
        "callback" => "user_logout_page",
        "title" => "User logout page",
        "theme" => "base_page",
    ];

    $items[] = [
        "path" => "user/whoami",
        "callback" => "user_whoami_page",
        "title" => "User whoami page",
        "theme" => "base_page",
    ];

    $items[] = [
        "path" => "user/ajaxlogin",
        "callback" => "user_block_ajax_handler",
        "type" => "ajax",
        "parameters" => [
            'login'       => ['security' => 'text3ns','source' => 'post'],
            'password'    => ['security' => 'text3ns','source' => 'post'],
            'fid'         => ['security' => 'text1ns','source' => 'post'],
            'act'         => ['security' => 'text1ns','source' => 'post'],
        ],
    ];

    $items[] = [
        "path" => "user/mypasswordchange",
        "callback" => "user_mypasswordchange_page",
        "title" => "User passowrd change page",
    ];
    $items[] = [
        "path" => "user/{uid}/passwordchange",
        "callback" => "user_passwordchange_page",
        "title" => "User passowrd change page",
    ];

    return $items;
}

function user_init_local()
{
    global $db;
    global $sys_data;
    global $user_module_settings;
    global $formsalt;

    if(!$db->open ||
        !isset($_COOKIE[$sys_data->authcookie_name]) ||
        $_COOKIE[$sys_data->authcookie_name] == '')
    {
        return; //Leaved $user object in unauthenticated state
    }

    $cookie_value = substr($_COOKIE[$sys_data->authcookie_name],0,64);
    if(preg_match("/^[a-zA-Z0-9]+$/",$cookie_value) !== 1)
    {
        return;
    }

    $r = sql_exec_noredirect('SELECT uid,created,access,chkname,chkval,changed,ip,fsalt,cksess
                              FROM authsess WHERE authsessval=:authsessvalue;',
        [':authsessvalue' => $cookie_value]
    );

    if($r == NULL)
        return;
    $rows=$r->fetchAll();
    foreach($rows as $row)
    {
        if($row['ip'] != get_remote_address())
            return;

        //Check login and session timeouts
        if(($user_module_settings->session_timeout_sec > 0 &&
                $sys_data->request_time - $row['access'] > $user_module_settings->session_timeout_sec) ||
            ($user_module_settings->login_timeout_sec > 0 &&
                $sys_data->request_time - $row['created'] > $user_module_settings->login_timeout_sec))
        {
            sql_exec_noredirect('DELETE FROM authsess WHERE authsessval = :authsessval;',
                [':authsessval' => $cookie_value]
            );
            return;
        }

        //Check if this session is blocked
        //Possible because the check-key was different before. The timeout will solve this.
        if($row['chkval'] == 'blocked')
            return; //This session is blocked

        //Check the second (control) cookie value, set the session blocked if not success
        //If failed will stay unauthenticated and marked this session to block.
        if(!isset($_COOKIE[$row['chkname']]) ||
            substr($_COOKIE[$row['chkname']],0,64) != $row['chkval'])
        {
            sql_exec_noredirect('UPDATE authsess SET chkval=\'blocked\' WHERE authsessval = :authsessval;',
                [':authsessval' => $cookie_value]
            );
            return;
        }

        //Authentication success

        //In case we reached keychange interval, do a keychange
        if($user_module_settings->keychange_interval_sec > 0 &&
                $sys_data->request_time - $row['changed'] > $user_module_settings->keychange_interval_sec)
        {
            $chkname = encOneway62(hash('sha256',generateRandomString(128),true),32);
            generateRandomString(32);
            $chkval = generateRandomString(16) .
                      encOneway62(hash('sha256',generateRandomString(128),true),32) .
                      generateRandomString(16);

            sql_exec_noredirect('UPDATE authsess SET chkname=:chkname,chkval=:chkval,changed=:changed
                                 WHERE authsessval = :authsessval;',
                [':chkname' => $chkname,
                 ':chkval'  => $chkval,
                 ':changed' => $sys_data->request_time,
                 ':authsessval' => $cookie_value]
            );

            setcookie($row['chkname'],'',time() - 3600,'/');
            unset($_COOKIE[$row['chkname']]);

            setcookie($chkname,$chkval,0,'/','',false,true);
            $_COOKIE[$chkname] = $chkval;
        }

        //Set lastlog
        sql_exec_noredirect('UPDATE authsess SET access=:acctime WHERE
                             authsessval = :authsessval;',
            [':authsessval' => $cookie_value,
                ':acctime' => $sys_data->request_time ]
        );

        $formsalt = $row['fsalt'];

        //Loads the codkep_session data if exists
        global $codkep_session;
        if(isset($row['cksess']) && strlen($row['cksess']) > 0)
            $codkep_session = unserialize(base64_decode($row['cksess']));

        //Loads the user determined by sessions
        user_load($row['uid'],'uid');
        return;
    }
}

/** Deletes all old record from auth session table */
function user_garbage_collection_local()
{
    global $sys_data;
    global $user_module_settings;
    sql_exec_noredirect("DELETE FROM authsess WHERE access < :ltime;",
        [':ltime' => $sys_data->request_time - $user_module_settings->login_garbagecoll]);
}

/** Load an user as current user according to "uid" or "login" */
function user_load($identifier,$type)
{
    global $user;
    global $user_module_settings;
    if(!in_array($type,['uid','login']))
        return false;

    $user = new stdClass();
    $user->auth = false;
    $user->login = NULL;
    $user->name = NULL;
    $user->role = ROLE_NONE;
    $user->login_disabled = false;

    $cname = '';
    if($type == 'uid')
        $cname = 'uid';
    if($type == 'login')
        $cname = $user_module_settings->sql_login_column;

    $r = sql_exec_noredirect('SELECT uid,'.$user_module_settings->sql_login_column.','
        .$user_module_settings->sql_name_column.','
        .$user_module_settings->sql_role_column.','
        .$user_module_settings->sql_logindisabled_column.
        ' FROM '.$user_module_settings->sql_tablename.
        ' WHERE '.$cname.'=:identifier;',
        [':identifier' => $identifier]
    );

    if($r == NULL)
        return false;
    $rows=$r->fetchAll();
    foreach($rows as $row)
    {
        $user->auth  = true;
        $user->uid   = $row['uid'];
        $user->login = $row[$user_module_settings->sql_login_column];
        $user->name  = $row[$user_module_settings->sql_name_column ];
        $user->role  = $row[$user_module_settings->sql_role_column ];
        $user->login_disabled = $row[$user_module_settings->sql_logindisabled_column ];
        run_hook('user_identified');
        return true;
    }
    return false;
}

/** Unloads the current user */
function user_unload()
{
    global $user;
    $user = new stdClass();
    $user->auth = false;
    $user->login = NULL;
    $user->name = NULL;
    $user->login_disabled = false;
    $user->role = ROLE_NONE;
}

function generateRandomString($length = 10)
{
    $chars = 'opQRwxLMNdefyz01cE2XABCmnYZabFGHI45qrsDOP67JKghiSTUVWjkltuv389';
    if(function_exists('random_bytes'))
        $bytes = random_bytes($length);
    else
    {
        $crypto_strong = false;
        $bytes = openssl_random_pseudo_bytes($length, $crypto_strong);
        if(!$crypto_strong)
            d1('Security warning: Cannot generate cryptographically strong random bytes!');
    }

    $r = substr(base64_encode($bytes),0,$length);
    for($i = 0 ; $i < $length ; ++$i)
        while($r[$i] == '+' || $r[$i] == '/')
            $r[$i] = $chars[(ord($r[$i]) + rand(0,100))%62];
    return $r;
}

function getFormSalt($force_unauth_salt = false)
{
    global $user;
    global $formsalt;
    global $user_module_settings;

    if(!$force_unauth_salt && $user->auth && $formsalt != '')
        return $formsalt;
    $string = get_remote_address().date('Y-z');
    return encOneway62(hash('sha256',$string.$user_module_settings->form_salt,true),32);
}

/** Try to login an user with the passed credentials.
 *  @package user */
function user_login($login,$password)
{
    try
    {
        global $user_module_settings;
        if($user_module_settings->user_login_callback != NULL)
        {
            return call_user_func($user_module_settings->user_login_callback,$login,$password);
        }
        return user_login_local($login,$password);
    }
    catch(Exception $e)
    {
        user_unload();
        return 0;
    }
}

/** Logouts the current logged user
 *  @package user */
function user_logout()
{
    global $user_module_settings;
    if($user_module_settings->user_logout_callback != NULL)
    {
        return call_user_func($user_module_settings->user_logout_callback);
    }
    return user_logout_local();
}

/** Try to login an user with the passed credentials (With built in algorithm)
 *  @package user */
function user_login_local($login,$password)
{
    global $sys_data;
    global $user_module_settings;
    global $formsalt;

    if(userblocking_check())
    {
        run_hook('blocked_client_rejected',"Login-Failed, disabled remote client");
        return 0;
    }

    if(strlen($login) > 128 ||
       strlen($password) > 128 ||
       !check_str($login,'text3ns') ||
       !check_str($password,'text3ns'))
        return 0;

    $r = sql_exec('SELECT uid,'.$user_module_settings->sql_login_column.','
                               .$user_module_settings->sql_password_column.','
                               .$user_module_settings->sql_logindisabled_column.
                  ' FROM '.$user_module_settings->sql_tablename.
                  ' WHERE '.$user_module_settings->sql_login_column.' = :f_login;',
                  [':f_login' => $login ]);
    $rows=$r->fetchAll();
    foreach($rows as $row)
    {
        if($row[$user_module_settings->sql_logindisabled_column] == true)
        {
            userblocking_set("Login-Failed: Disabled user");
            run_hook('user_failed_login',$login,"Login-Failed: Disabled user");
            return 0;
        }
        $cs = substr($row[$user_module_settings->sql_password_column],0,8);
        if(hash_equals($row[$user_module_settings->sql_password_column],scatter_string_local($password,$cs)))
        {
            //success
            return user_login_local_granted($row['uid'],$login);
        }
        else
        {
            userblocking_set("Login-Failed: Bad password");
            run_hook('user_failed_login',$login,"Login-Failed: Bad password");
            return 0;
        }
   }
   userblocking_set("Login-Failed: Unknown user");
   run_hook('user_failed_login',$login,"Login-Failed: Unknown user");
   return 0;
}

function user_login_local_granted($uid,$login)
{
    global $sys_data;
    global $user_module_settings;
    global $formsalt;

    $chkname = encOneway62(hash('sha256',generateRandomString(128),true),32);
    $authsess_value =
        substr(
            generateRandomString(8) .
            encOneway62(hash('sha256',generateRandomString(128).'_'.$login.'_'.get_remote_address(),true),36)
             . base_convert(strval(time()),10,36) . generateRandomString(24)
          ,0,64);

    generateRandomString(32);
    $chkval = generateRandomString(16) .
              encOneway62(hash('sha256',generateRandomString(128),true),32) .
              generateRandomString(16);

    $fsalt =  encOneway62(hash('sha256',generateRandomString(92).getFormSalt(true),true),32);

    sql_exec('INSERT INTO authsess(uid,authsessval,chkname,chkval,changed,created,access,ip,fsalt,cksess)
                      VALUES(:uid,:authserssval,:chkname,:chkval,:changed,:timec,:timea,:ip,:formsalt,:cksess);',
             [':uid' => $uid,
              ':authserssval' => $authsess_value,
              ':chkname' => $chkname,
              ':chkval' => $chkval,
              ':changed' => $sys_data->request_time,
              ':timec' => $sys_data->request_time,
              ':timea' => $sys_data->request_time,
              ':ip' => get_remote_address(),
              ':formsalt' => $fsalt,
              ':cksess' => '',
             ]);

    sql_exec('UPDATE '.$user_module_settings->sql_tablename.' SET '.
                $user_module_settings->sql_lastlogin_column . '='.sql_t('current_timestamp').
             ' WHERE '.$user_module_settings->sql_login_column.' = :f_login;',
        [':f_login' => $login ]
    );

    setcookie($sys_data->authcookie_name,$authsess_value,0,'/','',false,true);
    $_COOKIE[$sys_data->authcookie_name] = $authsess_value;

    setcookie($chkname,$chkval,0,'/','',false,true);
    $_COOKIE[$chkname] = $chkval;

    $formsalt = $fsalt;
    user_load($uid,'uid');
    userblocking_clear();
    run_hook('user_logged_in');
    return 1;
}

/** Logouts the current logged user (With built in algorithm)
 *  @package user */
function user_logout_local()
{
    global $db;
    global $user;
    global $sys_data;
    global $formsalt;
    if($user->auth)
    {
        run_hook('user_logout');
        $to_remove = substr($_COOKIE[$sys_data->authcookie_name],0,64);

        $r = sql_exec_noredirect('SELECT chkname FROM authsess WHERE authsessval = :authsv;',
            [ ':authsv' => $to_remove ]
        );
        $row = $r->fetch();
        if(isset($row['chkname']))
        {
            setcookie($row['chkname'],'',time() - 3600,'/');
            unset($_COOKIE[$row['chkname']]);
        }

        setcookie($sys_data->authcookie_name,'',time() - 3600,'/');
        unset($_COOKIE[$sys_data->authcookie_name]);

        $login_to_delete = $user->login;

        $formsalt = '';
        user_unload();

        sql_exec_noredirect('DELETE FROM authsess WHERE authsessval = :authsv;',
            [ ':authsv' => $to_remove ]
        );
        if($db->error)
            return 0;
        return 1;
    }
    return 0;
}

function codkepsession_store_local()
{
    global $db;
    global $user;
    global $sys_data;
    global $codkep_session;
    if($user->auth)
    {
        sql_exec_noredirect('UPDATE authsess SET cksess=:cksessvalue WHERE authsessval = :authsv;',
            [ ':authsv' => substr($_COOKIE[$sys_data->authcookie_name],0,64),
              ':cksessvalue' => base64_encode(serialize($codkep_session))]
        );
        if($db->error)
            return 1;
        return 0;
    }
    return 1;
}

function scatter_string_local($string,$with_salt = '')
{
    global $user_module_settings;
    if(!$user_module_settings->password_scattered)
        return $string;
    if($with_salt == '')
        $with_salt = generateRandomString(8);
    return $with_salt . scatter_string($string,
                $user_module_settings->password_scatter_salt . $with_salt,
                $user_module_settings->password_scatter_log2i);
}

function scatter_string($string,$salt,$i_lo2)
{
    $string = substr($string,0,256);
    $iter = 1 << $i_lo2;

    $c = gzcompress($salt.$string.$salt);
    $s = $string;
    for($i=0;$i<$iter;++$i)
    {
        $s = hash("sha512",
                    ($i%2 == 0 ? $salt : $string) .
                    ($i%3 == 0 ? $c : $s) .
                    ($i%3 != 0 ? $s : $c) .
                    ($i%2 != 0 ? $salt : $string),
             true);
        $c = gzcompress(hash('ripemd320',$s).$s);
    }
    return base64_encode($s);
}

function complexity_check_local($string,$fbl)
{
    global $user_module_settings;

    if(!$user_module_settings->password_complexity_check)
        return;

    if(strlen($string) < $user_module_settings->password_complexity_minlength)
        load_loc('error',
            t('The password has to be at least _len_ character long!',
                ['_len_' => $user_module_settings->password_complexity_minlength]),
            t('Password security warning'));

    $l=0; $u=0; $n=0;
    for($i=0;$i<strlen($string);++$i)
    {
        if(is_numeric($string[$i])) ++$n;
        if(ctype_upper($string[$i])) ++$u;
        if(ctype_lower($string[$i])) ++$l;
    }

    if($l < $user_module_settings->password_complexity_minlower)
        load_loc('error',
            t('The password has to contains at least _len_ lowercase letter!',
                ['_len_' => $user_module_settings->password_complexity_minlower]),
            t('Password security warning'));
    if($u < $user_module_settings->password_complexity_minupper)
        load_loc('error',
            t('The password has to contains at least _len_ uppercase letter!',
                ['_len_' => $user_module_settings->password_complexity_minupper]),
            t('Password security warning'));
    if($n < $user_module_settings->password_complexity_minnumber)
        load_loc('error',
            t('The password has to contains at least _len_ numeric letter!',
                ['_len_' => $user_module_settings->password_complexity_minnumber]),
            t('Password security warning'));

    if($user_module_settings->password_complexity_cplx && (strlen($string) > strlen(gzcompress($string,9))))
        load_loc('error',t('The complexity of the password is too low!'),t('Password security warning'));
}


function userblocking_check()
{
    global $sys_data;
    global $user_module_settings;

    if($user_module_settings->disable_remote_blocking)
        return false;

    $r = sql_exec_noredirect('SELECT failhit,created,access FROM blocking WHERE ip = :ipaddr;',
        [ ':ipaddr' => get_remote_address() ]
    );

    $row = $r->fetch();
    if(isset($row['failhit']))
    {
        if($row['failhit'] >= $user_module_settings->faillogin_block_count &&
           $sys_data->request_time - $row['access'] < $user_module_settings->faillogin_block_exipire_sec)
        {
            return true;
        }
    }

    sql_exec_noredirect("DELETE FROM blocking WHERE access < :ltime;",
        [':ltime' => $sys_data->request_time - $user_module_settings->faillogin_block_exipire_sec]);
    return false;
}

function userblocking_set($event)
{
    global $sys_data;
    global $user_module_settings;

    if($user_module_settings->disable_remote_blocking)
        return;

    $r = sql_exec_noredirect('SELECT failhit,created,access FROM blocking WHERE ip = :ipaddr;',
        [ ':ipaddr' => get_remote_address() ]
    );

    $row = $r->fetch();
    if(isset($row['failhit']))
    {
        //update record
        sql_exec_noredirect("UPDATE blocking SET failhit=:failhit,access=:access,event=:event WHERE ip = :ipaddr;",
            [':failhit' => $row['failhit'] + 1,
             ':access'  => $sys_data->request_time,
             ':ipaddr'  => get_remote_address(),
             ':event'   => substr($event,0,62),
            ]);
        return;
    }
    sql_exec_noredirect("INSERT INTO blocking(failhit,created,access,event,ip)
                         VALUES(:failhit,:created,:access,:event,:ipaddr);",
        [':failhit' => 1,
         ':created' => $sys_data->request_time,
         ':access'  => $sys_data->request_time,
         ':ipaddr'  => get_remote_address(),
         ':event'   => substr($event,0,62),
        ]);
}

function userblocking_clear()
{
    global $user_module_settings;
    if($user_module_settings->disable_remote_blocking)
        return;

    sql_exec_noredirect("DELETE FROM blocking WHERE ip = :ipaddr;",
        [':ipaddr' => get_remote_address()]);
}

function user_login_page()
{
    global $sys_data;
    global $user;
    global $user_module_settings;

    $fs = getFormSalt(true);
    ob_start();
    print '<section class="loginform-block-wrp"
            style="width: 100%; display: flex; flex-flow: row wrap; align-items: center; justify-content: center; text-align: center;">';

    if(par_is('loginbutton',t('Login')))
    {
        $pfs = par('fid');
        if($fs != $pfs)
        {
            userblocking_set('Login-from-trick');
            sleep(1);
            return ob_get_clean();
        }
        if(!user_login( par('login'),par('password') ))
        {
            print '<div style="width: 100%;"><h2>'.t('Failed to log in! Wrong user name or password.').'</h2></div>';
        }
    }

    if($user->auth)
    {
        if(par_ex('orignal_loc') && par('orignal_loc') != '')
        {
            ob_get_clean();
            goto_loc(par('orignal_loc'));
            return;
        }
        $sp = get_startpage();
        if($sp != '' && $sp != 'not_configured_startpage')
            goto_loc($sp);
        print '<div style="width: 100%;"><h2>'."User ".$user->name." logged in".'</h2></div>';
        print '</section>'; // .loginform-block-wrp
        return ob_get_clean();
    }

    print '<div style="width: 100%;"><h3>'.$user_module_settings->login_title.'</h3></div>';
    print '<form method="POST" action="'.url($sys_data->original_requested_location).'"
            style="text-align: left;">';
    print "<input type=\"hidden\" name=\"fid\" value=\"$fs\"/>";
    print '<div class="login_div_internal">';
    print '<table class="login_table_internal">';
    print '<tr>';
    print '<td>'.t('Username').'</td>';
    print '<td><input type="text" name="login" value="" maxlength="128" id="ulitid"
                   autocorrect="none" spellcheck="false" required="required" aria-required="true"/></td>';
    print '</tr>';
    print '<tr>';
    print '<td>'.t('Password').'</td>';
    print '<td><input type="password" name="password" value="" maxlength="128" autocomplete="off"
                   autocorrect="none" spellcheck="false" required="required" aria-required="true"/></td>';
    print '</tr>';
    print '<tr><td colspan="2" align="center">';
    print '<input type="submit" name="loginbutton" value="'.t('Login').'"/>';
    print '</td></tr>';
    if(trim($sys_data->original_requested_location) != '' && $sys_data->original_requested_location != current_loc())
    {
        print "<input type=\"hidden\" name=\"orignal_loc\" value=\"".
              $sys_data->original_requested_location."\"/></td></tr>";
    }
    elseif(par_ex('orignal_loc'))
    {
        print "<input type=\"hidden\" name=\"orignal_loc\" value=\"".
            par('orignal_loc')."\"/></td></tr>";
    }
    print "</table>";
    print '</div>'; // .login_div_internal
    print "</form>";
    print '</section>'; // .loginform-block-wrp

    add_style('body { background-color: #eeeeee; }');
    add_style('table.login_table_internal { background-color: #cccccc; margin: 6px; padding: 10px; border: 1px solid #aaaaaa; box-shadow: 0px 10px 20px #454545;}');
    add_style('table.login_table_internal td { margin: 4px; padding: 4px;}');
    add_style('table.login_table_internal input { padding: 2px 8px 2px 8px; border-radius: 6px; }');
    print "<script>jQuery(document).ready(function() { document.getElementById('ulitid').focus(); });</script>";

    global $site_config;
    $site_config->show_generation_time = false;
    return ob_get_clean();
}

function user_logout_page($location_after = '')
{
    global $user;
    if(!$user->auth)
        return 'There is no logged user.';
    user_logout();
    goto_loc(get_startpage());
    return 'User logged out.';
}

/** This function can used as page part block callback to make an user login/logout */
function user_login_block()
{
    return '<div id="login-div-block-wrapper"
             style="display: flex; flex-flow: row wrap; align-items: center; justify-content: center; text-align: center;">' .
             user_login_block_inner() .
           '</div>';
}

function user_login_block_inner()
{
    global $user;
    global $user_module_settings;

    ob_start();
    if($user->auth)
    {
        print '<div style="width: 100%">'."User:".$user->name.'</div>';
        print '<form method="POST" action="'.url('user/ajaxlogin').'" class="use-ajax">';
        print '<input type="submit" name="loginoutblockbutton" value="'.t('Logout').'"/>';
        print '<input type="hidden" name="act" value="out"/>';
        print "</form>";
        return ob_get_clean();
    }

    $fs = getFormSalt(true);
    print '<div style="width: 100%">'.$user_module_settings->login_title.'</div>';
    print '<form method="POST" action="'.url('user/ajaxlogin').'" class="use-ajax">';
    print "<input type=\"hidden\" name=\"fid\" value=\"$fs\"/>";
    print '<table class="login_table_block">';
    print '<tr><td>';
     print '<input type="text" name="login" value="" maxlength="128" autocorrect="none" spellcheck="false"
                   placeholder="'.t("Username").'" required="required" aria-required="true"
                   style="padding: 2px; border-radius: 4px;"/>';
    print '</td></tr>';
    print '<tr><td>';
     print '<input type="password" name="password" value="" maxlength="128" autocomplete="off" autocorrect="none"
                   spellcheck="false" placeholder="'.t("Password").'" required="required" aria-required="true"
                   style="padding: 2px; border-radius: 4px;"/>';
    print '</td></tr>';
    print '<tr><td align="center">';
    print '<input type="submit" name="loginblockbutton" value="'.t('Login').'"
                  style="padding: 2px; border-radius: 4px;"/>';
    print '</td></tr>';
    print '<input type="hidden" name="act" value="in" />';
    print '</table>';
    print '</form>';
    return ob_get_clean();
}

function user_block_ajax_handler()
{
    global $user;
    if(!$user->auth && par_is('act','in'))
    {
        $fs = getFormSalt(true);
        $pfs = par('fid');
        if($fs != $pfs)
        {
            userblocking_set('Login-from-trick-x');
            sleep(1);
            return;
        }
        if(user_login(par('login'), par('password')))
        {
            run_hook("user_ajax_logged_in");
        }
        else
        {
            ajax_add_alert(t("Login failed!"));
        }
    }
    else if($user->auth && par_is('act','out'))
    {
        user_logout();
        run_hook("user_ajax_logout");
    }
    ajax_add_html("#login-div-block-wrapper",user_login_block_inner());
}

function user_whoami_page()
{
    global $user;

    if(!$user->auth)
        return "Not authenticated user.";
    return "User ".$user->name." logged in.";
}

/** Requires valid authentication for the current page. 
 *  If the current user is not authenticated the execution will be redirected to the login page. */
function require_auth()
{
    global $user;
    global $user_module_settings;
    if(!$user->auth)
        load_loc($user_module_settings->login_location);
}

function hook_user_nodetype()
{
    global $user_module_settings;

    $pcc = 'complexity_check_local';
    if($user_module_settings->password_complexity_checkerfnc != NULL)
        $pcc = $user_module_settings->password_complexity_checkerfnc;

    $def = [];
    if($user_module_settings->define_user_nodetype)
    {
        $def['user'] = [
                "name" => "codkep_users",
                "table" => $user_module_settings->sql_tablename,
                "show" => "table",
                "access_earlyblock" => true,
                "color" => "#8888ff",
                "before" => '<div style="display: flex; align-items: center; justify-content: center;">',
                "after" => '</div>',
                "table_border" => "1",
                "table_style" => "border-collapse: collapse;",
                "fields" => [
                    10 => [
                        "sql" => "statictitle",
                        "type" => "static",
                        "default" => t('User'),
                        "centered" => true,
                        "prefix" => "<strong>",
                        "suffix" => "</strong>",
                    ],
                    20 => [
                        "sql" => "uid",
                        "text" => t('User identifier'),
                        "type" => "keyn",
                        "centered" => true,
                        "pgsql_sql_sequence_name" => "users_uid_seq",
                    ],
                    30 => [
                        "sql" => "login",
                        "text" => t('Login'),
                        "type" => "smalltext",
                        "check_noempty" => t('You have to fill the login name'),
                        "par_sec" => "text3ns",
                        "form_options" => [
                            "required" => true,
                            "rawattributes" => "autocorrect=\"none\" spellcheck=\"false\"",
                        ],
                    ],
                    40 => [
                        "sql" => "name",
                        "text" => t('Full name'),
                        "type" => "smalltext",
                        "check_noempty" => t('You have to fill the full name field'),
                        "form_options" => [
                            "size" => 40,
                            "required" => true,
                        ],
                    ],
                    50 => [
                        "sql" => "password",
                        "text" => t('Password'),
                        "type" => "password",
                        "check_loaded_function" => $pcc,
                        "converter" => 'scatter_string_local',
                        "default" => "",
                        "check_noempty" => t('You have to fill the password field'),
                        "skip" => "exceptinsert",
                        "par_sec" => "text3ns",
                        "form_options" => [
                            "required" => true,
                            "rawattributes" => "autocorrect=\"none\" spellcheck=\"false\"",
                        ],
                    ],
                    60 => [
                        "sql" => "lindis",
                        "text" => t('Login disabled'),
                        "type" => "check",
                        "default" => false,
                        "hide" => true,
                    ],
                    70 => [
                        "sql" => "role",
                        "text" => t('Role'),
                        "type" => "numselect",
                        "default" => 3,
                        "values" => [
                            1 => t('Administrator'),
                            2 => t('Editor'),
                            3 => t('User'),
                        ],
                    ],
                    80 => [
                        "sql" => "lastlogin",
                        "type" => "timestamp_create",
                        "text" => t('Last login'),
                        "readonly" => true,
                    ],
                    500 => [
                        "sql" => "submit_add",
                        "type" => "submit",
                        "default" => t('Create'),
                        "centered" => true,
                        "in_mode" => "insert",
                    ],
                    510 => [
                        "sql" => "submit_edit",
                        "type" => "submit",
                        "default" => t('Save'),
                        "centered" => true,
                        "in_mode" => "update",
                    ],
                    520 => [
                        "sql" => "submit_del",
                        "type" => "submit",
                        "default" => t('Delete'),
                        "centered" => true,
                        "in_mode" => "delete",
                    ],
                ],
            ];

        $user_module_settings->userpass_reset =
        [
            "name" => "codkep_user_pwd_reset",
            "table" => $user_module_settings->sql_tablename,
            "show" => "table",
            "color" => "#cc2222",
            "table_border" => "1",
            "table_style" => "border-collapse: collapse;",
            "fields" => [
                10 => [
                    "sql" => "statictitle",
                    "type" => "static",
                    "default" => t('Reset user password'),
                    "centered" => true,
                    "prefix" => "<strong>",
                    "suffix" => "</strong>",
                ],
                20 => [
                    "sql" => "uid",
                    "text" => t('User identifier'),
                    "type" => "keyn",
                    "readonly" => true,
                    "hide" => true,
                    "pgsql_sql_sequence_name" => "users_uid_seq",
                ],
                30 => [
                    "sql" => $user_module_settings->sql_login_column,
                    "text" => t('Login'),
                    "type" => "smalltext",
                    "readonly" => true,
                    "par_sec" => "text3ns",
                ],
                50 => [
                    "sql" => $user_module_settings->sql_password_column,
                    "text" => t('Password'),
                    "type" => "password",
                    "check_loaded_function" => $pcc,
                    "converter" => 'scatter_string_local',
                    "default" => "",
                    "check_noempty" => t('You have to fill the password field'),
                    "par_sec" => "text3ns",
                    "form_options" => [
                        "required" => true,
                        "rawattributes" => "autocorrect=\"none\" spellcheck=\"false\"",
                    ],
                ],
                100 => [
                    "sql" => "submit_edit",
                    "type" => "submit",
                    "default" => t('Save'),
                    "centered" => true,
                    "in_mode" => "update",
                ],
            ],
        ];

        $user_module_settings->mypassword_set =
        [
            "name" => "codkep_my_pwd_reset",
            "table" => $user_module_settings->sql_tablename,
            "show" => "table",
            "color" => "#ff6666",
            "table_border" => "1",
            "table_style" => "border-collapse: collapse;",
            "fields" => [
                10 => [
                    "sql" => "statictitle",
                    "type" => "static",
                    "default" => t('Change my password'),
                    "centered" => true,
                    "prefix" => "<strong>",
                    "suffix" => "</strong>",
                ],
                20 => [
                    "sql" => "uid",
                    "text" => t('User identifier'),
                    "type" => "keyn",
                    "readonly" => true,
                    "hide" => true,
                    "pgsql_sql_sequence_name" => "users_uid_seq",
                ],
                30 => [
                    "sql" => $user_module_settings->sql_login_column,
                    "text" => t('User login name'),
                    "type" => "smalltext",
                    "readonly" => true,
                    "hide" => true,
                    "par_sec" => "text3ns",
                ],
                40 => [
                    "sql" => "oldpwd",
                    "text" => t('Old password'),
                    "type" => "password",
                    "skip" => "sql",
                    "form_options" => [
                        "required" => true,
                        "rawattributes" => "autocorrect=\"none\" spellcheck=\"false\"",
                    ],
                ],
                50 => [
                    "sql" => $user_module_settings->sql_password_column,
                    "text" => t('New password'),
                    "type" => "password",
                    "check_loaded_function" => $pcc,
                    "converter" => 'scatter_string_local',
                    "default" => "",
                    "check_noempty" => t('You have to fill the password field'),
                    "par_sec" => "text3ns",
                    "form_options" => [
                        "required" => true,
                        "rawattributes" => "autocorrect=\"none\" spellcheck=\"false\"",
                    ],
                ],
                100 => [
                    "sql" => "submit_edit",
                    "type" => "submit",
                    "default" => t('Save'),
                    "centered" => true,
                    "in_mode" => "update",
                ],
            ],
        ];

    }

    return $def;
}

function hook_user_required_sql_schema()
{
    $t = [];
    $t['user_module_authsess_table'] =
        [
            "tablename" => 'authsess',
            "columns" => [
                'uid'         => 'BIGINT NOT NULL',
                'authsessval' => 'VARCHAR(64)',
                'chkname'     => 'VARCHAR(32)',
                'chkval'      => 'VARCHAR(64)',
                'changed'     => 'BIGINT',
                'created'     => 'BIGINT',
                'access'      => 'BIGINT',
                'ip'          => 'VARCHAR(48)',
                'fsalt'       => 'VARCHAR(32)',
                'cksess'      => sql_t('longtext_type'),
            ],
        ];

    $t['user_module_blocing_table'] =
        [
            "tablename" => 'blocking',
            "columns" => [
                'event'       => 'VARCHAR(64)',
                'failhit'     => 'NUMERIC(3)',
                'created'     => 'BIGINT',
                'access'      => 'BIGINT',
                'ip'          => 'VARCHAR(48)',
            ],
        ];

    return $t;
}

function hook_user_node_access($node,$op,$acc)
{
    global $user_module_settings;
    if($node->node_type == 'user' && $user_module_settings->define_user_nodetype)
    {
        if($acc->role == ROLE_ADMIN)
            return NODE_ACCESS_ALLOW;
        return NODE_ACCESS_DENY;
    }
    return NODE_ACCESS_IGNORE;
}

function usernid_from_loginname($login)
{
    global $user_module_settings;
    $r = sql_exec_fetchN("SELECT uid FROM ".$user_module_settings->sql_tablename.
                         ' WHERE '.$user_module_settings->sql_login_column.' = :f_login;',
                         [':f_login' => $login ]);
    if($r == null || !isset($r[0]) || $r[0] == null)
        return null;
    return $r[0];
}

function user_mypasswordchange_page()
{
    global $user;
    global $user_module_settings;

    if(!$user->auth)
        return '';
    if(!$user_module_settings->enable_own_passwordchange)
        return '';
    return user_mypasswordchange();
}

function user_mypasswordchange()
{
    global $user;
    global $user_module_settings;

    if(!$user->auth)
        return '';
    if(!isset($user_module_settings->mypassword_set))
        return '';

    ob_start();
    $sf = new SpeedForm($user_module_settings->mypassword_set);
    if($sf->in_action('update'))
    {
        $sf->set_key($user->uid);
        $sf->do_select();
        $sf->load_parameters();
        $old_is = sql_exec_single("SELECT " . $user_module_settings->sql_password_column .
                                  " FROM " . $user_module_settings->sql_tablename .
                                  " WHERE uid=:uid",[':uid' => $user->uid]);
        $cs = substr($old_is,0,8);
        if(userblocking_check())
        {
            run_hook('blocked_client_rejected',$user->login,"Password change failed, the remote client is blocked!");
            load_loc('error',t('The client is blocked due to previous errors!'),t('Warning!'));
        }

        if($old_is == '*')
            $old_get = $old_is;
        else
            $old_get = scatter_string_local($sf->values['oldpwd'],$cs);

        if( !isset($old_is) && $old_is == NULL ||
            !isset($old_get) && $old_get == NULL ||
            !hash_equals($old_get,$old_is) )
        {
            run_hook('passwordchange_failed',$user->login,"Password change failed, the given old password is wrong!");
            userblocking_set('Pwd-Change: wrong old pwd');
            load_loc('error',t('The given old password is wrong!'),t('Warning!'));
        }

        $sf->do_update();
        print '<h2 style="width: 100%; text-align: center;">Ok</h2><br/>' .
              l(t('Startpage'),get_startpage(),['class' => 'lnk']);
    }
    else
    {
        $sf->set_key($user->uid);
        $sf->do_select();
        $form = $sf->generate_form('update');
        $form->action_post(current_loc());
        print $form->get();
    }
    return '<div class="usr0-pwd-change-block"
                 style="display: flex; flex-flow: row wrap; align-items: center; justify-content: center;">'.
              ob_get_clean() . '</div>';
}

function user_passwordchange_page()
{
    global $user;
    global $user_module_settings;

    par_def('uid','number0');
    if(!$user->auth)
        return '';
    if(!$user_module_settings->enable_admin_passwordchange)
        return '';
    if($user->role != ROLE_ADMIN)
        return '';
    return user_passwordchange(par('uid'));
}

function user_passwordchange($uid)
{
    global $user;
    global $user_module_settings;

    if(!$user->auth)
        return '';
    if(!isset($user_module_settings->userpass_reset))
        return '';

    ob_start();
    $sf = new SpeedForm($user_module_settings->userpass_reset);
    if($sf->in_action('update'))
    {
        $sf->set_key($uid);
        $sf->do_select();
        $sf->load_parameters();
        $sf->do_update();
        print '<h2 style="width: 100%; text-align: center;">Ok</h2><br/>' . l(t('Startpage'),get_startpage(),['class' => 'lnk']);
    }
    else
    {
        $sf->set_key($uid);
        $sf->do_select();
        $form = $sf->generate_form('update');
        $form->action_post(current_loc());
        print $form->get();
    }
    return '<div class="usr1-pwd-change-block"
                 style="display: flex; flex-flow: row wrap; align-items: center; justify-content: center;">'.
             ob_get_clean() . '</div>';
}

function hook_user_introducer()
{
    $html = '';
    global $db;
    if(isset($db->open) && $db->open)
    {
        global $user;
        if($user->auth)
        {
            $html .= t('The "_username_" user logged in',['_username_' => $user->name]).'<br/>';
            $html .= l(t('Logout'), 'user/logout').'<br/>';
            $html .= l(t('Whoami?'), 'user/whoami').'<br/>';
            $html .= l(t('Change my password'), 'user/mypasswordchange').'<br/>';
            //user/{uid}/passwordchange
        }
        else
        {
            $html .= t('Nobody logged in').'<br/>';
            $html .= l(t('Login'), 'user/login') . '<br/>';
        }
    }
    return ['User' => $html];
}

/** @ignore
 *  The check_module_requirements hook of user module.
 *  This checks the prerequisites of user module */
function hook_user_check_module_requirements()
{
    ob_start();

    $cryptrand_has = function_exists('random_bytes') || function_exists('openssl_random_pseudo_bytes');
    $hash_has = function_exists('hash');
    $hash1a_has = in_array('sha512',hash_algos());
    $hash2a_has = in_array('ripemd320',hash_algos());
    $gzc_has = function_exists('gzcompress');

    print '<tr>';
    print '<td class="normal">Php random_bytes or OpenSSL random</td>';
    print '<td class="'.($cryptrand_has ? 'green':'red').'">'.($cryptrand_has ? 'Available' : 'Not available').'</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="normal">Php hash function</td>';
    print '<td class="'.($hash_has ? 'green':'red').'">'.($hash_has ? 'Installed' : 'Not installed').'</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="normal">SHA512 hash algorithm</td>';
    print '<td class="'.($hash1a_has ? 'green':'red').'">'.($hash1a_has ? 'Installed' : 'Not installed').'</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="normal">RIPEMD320 hash algorithm</td>';
    print '<td class="'.($hash2a_has ? 'green':'red').'">'.($hash2a_has ? 'Installed' : 'Not installed').'</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="normal">Php gzcompress function</td>';
    print '<td class="'.($gzc_has ? 'green':'red').'">'.($gzc_has ? 'Installed' : 'Not installed').'</td>';
    print '</tr>';

    return ob_get_clean();
}

function hook_user_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = ['user' => ['path' => 'sys/doc/user.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
    }
    return $docs;
}

/**
 * This hook runs when the users successfully logged in.
 * Only runs on real login, it is not called when an already authenticated user loads a new page.
 * @package user */
function _HOOK_user_logged_in() {}

/**
 * This hook runs when the users explicitly logged out from the system.
 * @package user */
function _HOOK_user_logout() {}

/**
 * This hook runs when the users re-authentication between pages.
 * If an already logined users is indentified successfully by the system this hook is called.
 * Note that this hook is also called after user_logged_in hook.
 * This hook is tipically used to load some additional user data to the global $user object
 * which should be accessible by other modules.
 * @package user */
function _HOOK_user_identified() {}

/**
 * This hook is run after an unsuccessful user login.
 * @package user */
function _HOOK_user_failed_login() {}

//end.
