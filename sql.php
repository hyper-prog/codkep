<?php
/*  CodKep - Lightweight web framework core file
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 *
 *
 * Sql module
 *  Required modules: core
 */

global $db;
$db = NULL;

function hook_sql_boot()
{
    global $db;
    $db = new stdClass();
    $db->open = false;
    $db->error = false;
    $db->errormsg = "";
    $db->servertype = "none";
    $db->host = "";
    $db->name = "";
    $db->user = "";
    $db->password = "";
    $db->sql = NULL;
    $db->lastsql = "";
    $db->auto_error_page = true;
    $db->transaction = false;

    $db->schema_editor_password = ""; //empty means disabled!
    $db->schema_editor_allowed_for_admin = true;

    $db->error_locations = [
        'connection_error' => 'sql_connection_error',
        'generic_error'    => 'sql_error',
    ];

    $db->tr = [
        'timestamp_noupd'   => ['mysql' => 'DATETIME'         ,     'pgsql' => 'TIMESTAMP', ],
        'current_timestamp' => ['mysql' => 'CURRENT_TIMESTAMP',     'pgsql' => 'now()'    , ],
        'longtext_type'     => ['mysql' => 'LONGTEXT'         ,     'pgsql' => 'TEXT'     , ],
    ];
}

function hook_sql_defineroute()
{
    $items = array();
    $items[] = [
                "path" => "sqlschema",
                "callback" => "sql_schema_page",
                "theme" => "base_page",
               ];
    $items[] = [
                "path" => "sql_connection_error",
                "callback" => "sql_conection_error_page",
                "theme" => "base_page",
               ];
    $items[] = [
                "path" => "sql_error",
                "callback" => "sql_error_page",
                "theme" => "base_page",
               ];
    return $items;
}

function sql_t($string)
{
    global $db;
    $t = $db->servertype;
    if($t == "none")
        $t = "mysql";
    return $db->tr[$string][$t];
}

/** Connect to the sql database specified in settings */
function sql_connect()
{
    global $db;
    if($db->servertype == "none")
        return;
    $db->lastsql = " - not relevant - ";
    try
    {
        $db->sql = new PDO($db->servertype.':host='.$db->host.';dbname='.$db->name,$db->user,$db->password);
        $db->sql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->open = true;
        $db->errormsg = "";
        $db->error = false;
    }
    catch(PDOException $e)
    {
        $db->open = false;
        $db->errormsg = ($db->errormsg == '' ? '' : $db->errormsg . '<br/>') . $e->getMessage();
        $db->error = true;

        if($db->auto_error_page)
            load_loc($db->error_locations['connection_error']);
        return;
    }
    run_hook('sql_connected');
}

/** Disconnects the current opened database connection 
 *  @package sql */
function sql_disconnect()
{
    global $db;
    if($db->servertype == "none")
        return;
    $db->lastsql = " - not relevant - ";
    try
    {
        $db->sql=NULL;
        $db->open = false;
        $db->errormsg = "";
        $db->error = false;
    }
    catch(PDOException $e)
    {
        $db->open = false;
        $db->errormsg = ($db->errormsg == '' ? '' : $db->errormsg . '<br/>') . $e->getMessage();
        $db->error = true;

        if($db->auto_error_page)
            load_loc($db->error_locations['connection_error']);
    }
}

/** Executes an sql command and do error handlings.
 *  It returns an executed pdo object
 *  @package sql  */
function sql_exec($sql,$parameters=array(),$errormsg='')
{
    global $db;
    if(!$db->open)
    {
        $db->errormsg = ($db->errormsg == '' ? '' : $db->errormsg . '<br/>') .
                        t("There is no opened database connection!");
        $db->error = true;
        if($db->auto_error_page)
            load_loc($db->error_locations['generic_error']);
        return NULL;
    }

    $db->errormsg = "";
    $db->error = false;
    $db->lastsql = $sql;
    run_hook("execute_sql",$sql,$parameters);
    try
    {
        $stmt = $db->sql->prepare($sql);
        $stmt->execute($parameters);
    }
    catch(PDOException $e)
    {
        $db->errormsg = ($db->errormsg == '' ? '' : $db->errormsg . '<br/>') . $e->getMessage();
        $db->error = true;
        if($db->auto_error_page)
            load_loc($db->error_locations['generic_error']);
        return NULL;
    }
    return $stmt;
}

/** Executes an sql command and does not do error handling.
 *  It returns an executed pdo object
 *  @package sql  */
function sql_exec_noredirect($sql,$parameters=array())
{
    global $db;
    if(!$db->open)
        return NULL;

    $db->errormsg = "";
    $db->error = false;
    $db->lastsql = $sql;
    run_hook("execute_sql",$sql,$parameters);
    try
    {
        $stmt = $db->sql->prepare($sql);
        $stmt->execute($parameters);
    }
    catch(PDOException $e)
    {
        $db->error = true;
        return NULL;
    }
    return $stmt;
}

/** Executes and fetch an sql command and do error handlings.
 *  It returns an executed and fetched array
 *  @package sql  */
function sql_exec_fetch($sql,$parameters=array(),$errormsg='')
{
    $r = array();
    $do = sql_exec($sql,$parameters,$errormsg);
    if($do != NULL)
        $r = $do->fetch();
    if($do == NULL || !is_array($r) || $r === NULL)
    {
        global $db;
        $db->errormsg = ($db->errormsg == '' ? '' : $db->errormsg . '<br/>') .
                        ($errormsg == '' ? '' : "$errormsg : ") .
                        t('Cannot fetch data (no valid result)');
        $db->error = true;
        if($db->auto_error_page)
            load_loc($db->error_locations['generic_error']);
        return array();
    }
    return $r;
}

/** Executes and fetch an sql command and do error handlings.
 *  It returns an executed and fetched array 
 *  Allows empty result */
function sql_exec_fetchN($sql,$parameters=array(),$errormsg='')
{
    $r = array();
    $do = sql_exec($sql,$parameters,$errormsg);
    if($do != NULL)
        $r = $do->fetch();
    return $r;
}

/** Executes and fetch an sql command and do error handlings.
 *  It returns a single value. The first field of first row */
function sql_exec_single($sql,$parameters=array(),$errormsg='')
{
    $r = sql_exec_fetch($sql,$parameters,$errormsg);
    if(!isset($r[0]))
    {
        global $db;
        $db->errormsg = ($db->errormsg == '' ? '' : $db->errormsg . '<br/>') .
                        ($errormsg == '' ? '' : "$errormsg : ") .
                        t('Cannot fetch data (no valid result)');
        $db->error = true;
        if($db->auto_error_page)
            load_loc($db->error_locations['generic_error']);
        return NULL;
    }
    return $r[0];
}

/** Executes and fetchAll an sql command and do error handlings.
 *  It returns an executed and fetchAll() fetched array of arrays */
function sql_exec_fetchAll($sql,$parameters=array(),$errormsg='',$fetch_names_only = false)
{
    $r = array();
    $do = sql_exec($sql,$parameters,$errormsg);
    if($do != NULL)
    {
        if($fetch_names_only)
            $r = $do->fetchAll(PDO::FETCH_NAMED);
        else
            $r = $do->fetchAll();
    }
    if($do == NULL || !is_array($r) || $r === NULL)
    {
        global $db;
        $db->errormsg = ($db->errormsg == '' ? '' : $db->errormsg . '<br/>') .
                        ($errormsg == '' ? '' : "$errormsg : ") .
                        t('Cannot fetch data (no valid result)');
        $db->error = true;
        if($db->auto_error_page)
            load_loc($db->error_locations['generic_error']);
        return array();
    }
    return $r;
}

/** Begins an sql transaction on the current connection. */
function sql_transaction()
{
    global $db;
    if($db->transaction)
    {
        $db->errormsg = ($db->errormsg == '' ? '' : $db->errormsg . '<br/>') .
                        t('Cannot start a new transaction in an already opened transaction!');
        $db->error = true;
        if($db->auto_error_page)
            load_loc($db->error_locations['generic_error']);
        return;
    }

    $db->errormsg = "";
    $db->error = false;
    $db->lastsql = " transaction : begin ";
    run_hook("execute_sql","transaction : begin",array());
    if(!$db->sql->beginTransaction())
    {
        $db->errormsg = ($db->errormsg == '' ? '' : $db->errormsg . '<br/>');
        $db->error = true;
        if($db->auto_error_page)
            load_loc($db->error_locations['generic_error']);
    }

    $db->transaction = true;
}

/** Commits and finish the current sql transaction.
 *  It has to be preceded by sql_transaction() call. */
function sql_commit()
{
    global $db;
    if(!$db->transaction)
    {
        $db->errormsg = ($db->errormsg == '' ? '' : $db->errormsg . '<br/>') .
                        t('Cannot commit, there is no opened transaction!');
        $db->error = true;
        if($db->auto_error_page)
            load_loc($db->error_locations['generic_error']);
        return;
    }

    $db->errormsg = "";
    $db->error = false;
    $db->lastsql = " transaction : commit ";
    run_hook("execute_sql","transaction : commit",array());
    if(!$db->sql->commit())
    {
        $db->errormsg = ($db->errormsg == '' ? '' : $db->errormsg . '<br/>');
        $db->error = true;
        if($db->auto_error_page)
            load_loc($db->error_locations['generic_error']);
    }

    $db->transaction = false;
}

/** Rollbacks and finish the current sql transaction.
 *  It has to be preceded by sql_transaction() call. */
function sql_rollback()
{
    global $db;
    if(!$db->transaction)
    {
        $db->errormsg = ($db->errormsg == '' ? '' : $db->errormsg . '<br/>') .
                        t('Cannot rollback, there is no opened transaction!');
        $db->error = true;
        if($db->auto_error_page)
            load_loc($db->error_locations['generic_error']);
        return;
    }

    $db->errormsg = "";
    $db->error = false;
    $db->lastsql = " transaction : rollback ";
    run_hook("execute_sql","transaction : rollback",array());
    if(!$db->sql->rollback())
    {
        $db->errormsg = ($db->errormsg == '' ? '' : $db->errormsg . '<br/>');
        $db->error = true;
        if($db->auto_error_page)
            load_loc($db->error_locations['generic_error']);
    }
    $db->transaction = false;
}

/** Call commit on the current transaction if exists. Otherwise do nothing. */
function sql_commit_if_needed()
{
    global $db;
    if($db->transaction)
    {
        sql_commit();
    }
}

/** Call rollback on the current transaction if exists. Otherwise do nothing. */
function sql_rollback_if_needed()
{
    global $db;
    if($db->transaction)
    {
        sql_rollback();
    }
}

function sql_conection_error_page()
{
    global $db;
    ob_start();
    print "<h2>".t('Sql connection error')."</h2>";
    print $db->errormsg."<br/>";
    return ob_get_clean();
}

function sql_error_page()
{
    global $db;
    ob_start();
    print "<h2>".t('Sql error')."</h2>\n";
    print $db->errormsg;
    print "<h3>".t('Last SQL command').":</h3><pre>".$db->lastsql."</pre>";
    print "<h3>".t('Backtrace').":</h3><pre>\n";
    $tr = debug_backtrace();
    foreach($tr as $t)
    {
        if(isset($t['file']) && isset($t['line']))
            print $t['file'].":".$t['line']." (".$t['function'].")\n";
    }
    print "</pre>";

    return ob_get_clean();
}

function sql_table_exists($tablename)
{
    global $db;
    if(!$db->open)
        return NULL;
    try
    {
        $stmt = $db->sql->prepare("SELECT count(*) FROM $tablename");
        $stmt->execute();
    }
    catch(PDOException $e)
    {
        return false;
    }
    return true;
}

function sql_column_exists($tablename,$colname)
{
    global $db;
    if(!$db->open)
        return NULL;
    try
    {
        $stmt = $db->sql->prepare("SELECT $colname FROM $tablename LIMIT 1");
        $stmt->execute();
    }
    catch(PDOException $e)
    {
        return false;
    }
    return true;
}

function sql_check_and_build($name,$tablename,$fields)
{
    $r = array();
    $table_exists = sql_table_exists($tablename);
    $full_exists = true;
    $str = '';
    $str2 = '';
    $str .= "-- $name  \n";
    $str .= "CREATE TABLE ".$tablename." (\n";
    $first = true;
    foreach($fields as $name => $type)
    {
        if(!$first)
            $str .= ",\n";
        $str .= "\t$name $type";
        $first = false;

        if($table_exists && !sql_column_exists($tablename,$name))
        {
            $full_exists = false;
            $str2 = "ALTER TABLE ".$tablename." ADD ".$name." ".$type.";\n";
        }
    }
    $str .= "\n);";

    if(!$table_exists)
        $str2 = $str;
    return array("table_exists" => $table_exists,
                 "full_exists" => $full_exists,
                 "create_string" => $str,
                 "exec_string" => $str2,
                );
}

function sql_schema_page()
{
    global $user;
    global $db;
    par_def("sep","text3ns");
    par_def("execute","text3ns");

    if(!isset($db->schema_editor_allowed_for_admin) ||
       !$db->schema_editor_allowed_for_admin ||
       !isset($user->role) ||
       !isset($user->login) ||
       $user->login == '' || $user->login == NULL ||
       $user->role != ROLE_ADMIN ||
       ( isset($db->schema_editor_allowed_only_for_login) &&
         $db->schema_editor_allowed_only_for_login != NULL &&
         $db->schema_editor_allowed_only_for_login != $user->login
       )
    )
    {
        if(!isset($db->schema_editor_password) ||
           $db->schema_editor_password == '' ||
           !par_ex("sep") ||
           !par_is("sep",$db->schema_editor_password))
        {
            ob_start();
            print '<center>';
            print '<h3>Schema info page</h3>';
            print '<form method="POST" action="'.url(current_loc()).'">';
            print '<div class="login_div_internal">';
            print '<table class="login_table_internal">';
            print '<tr><td>'.t('Password').'</td><td><input type="password" name="sep" value="" autocomplete="off" maxlength="128" /></td></tr>';
            print '<tr><td colspan="2" align="center">';
            print '<input type="submit" name="loginbutton" value="'.t('Login').'"/></td></tr>';
            add_style('body { background-color: #eeeeee; }');
            add_style('table.login_table_internal { background-color: #cccccc; margin: 6px; padding: 10px; border: 1px solid #aaaaaa; }');
            add_style('table.login_table_internal td { margin: 4px; padding: 4px;}');
            add_style('table.login_table_internal input { padding: 2px; border-radius: 6px; }');
            print "</form>";
            print "</table>";
            print "</div>";
            print "The schema editor password was located in your site's settings file<br/>";
            print "by set the <i>\$db->schema_editor_password</i> variable.";
            print "</center>";
            return ob_get_clean();
        }
    }

    $sdes = run_hook('required_sql_schema');

    ob_start();
    print '<table class="sql_schema_table" border="1">';

    $fa = '';
    $fa .= '<form method="POST" action="'.url(current_loc()).'">';
    $fa .= '<input type="hidden" name="sep" value="'.par('sep').'"/>';
    $fa .= '<input type="hidden" name="execute" value="--forall--"/>';
    $fa .= '<input type="submit" name="s" value="'.t("EXECUTE TO ALL").'"/>';
    $fa .= '</form>';

    $f_refresh = '';
    $f_refresh .= '<form method="POST" action="'.url(current_loc()).'">';
    $f_refresh .= '<input type="hidden" name="sep" value="'.par('sep').'"/>';
    $f_refresh .= '<input type="submit" name="s" value="'.t("Refresh").'"/>';
    $f_refresh .= '</form>';

    print "<thead><tr><th>Table</th><th>Needs execute</th><th>$f_refresh</th></tr></thead>";

    print '<tbody>';
    print "<tr><td>All tables</td><td></td><td align=\"center\">$fa</td></tr>";
    foreach($sdes as $defname => $def)
    {
        if(par_is("execute",$def['tablename']) || par_is("execute","--forall--"))
        {
            $a = sql_check_and_build($defname,$def['tablename'],$def['columns']);
            if($a['exec_string'] != '')
                sql_exec($a['exec_string']);
        }

        $a = sql_check_and_build($defname,$def['tablename'],$def['columns']);

        $style = '';
        if($a['table_exists'] && $a['full_exists'])
            $style = 'background-color: #77ff66';
        if($a['table_exists'] && !$a['full_exists'])
            $style = 'background-color: #ffaa44';
        if(!$a['table_exists'])
        {
            $style = 'background-color: #ff4444';
        }

        $exec_str = $a['exec_string'];
        if($exec_str == '')
            $exec_str = '-- Ok ';

        print '<tr>';
        print "<td style=\"$style\"><pre>".$a['create_string']."</pre></td>";
        print "<td style=\"color: #ffffff; background-color: #000000;\"><pre>$exec_str</pre></td>";

        if(!$a['table_exists'] || !$a['full_exists'])
        {
            $f = '';
            $f .= '<form method="POST" action="'.url(current_loc()).'">';
            $f .= '<input type="hidden" name="sep" value="'.par('sep').'"/>';
            $f .= '<input type="hidden" name="execute" value="'.$def['tablename'].'"/>';
            $f .= '<input type="submit" name="s" value="'.t("EXECUTE").'"/>';
            $f .= '</form>';
            print "<td>$f</td>";
        }
        else
        {
            $count = sql_exec_single("SELECT count(*) FROM ".$def['tablename']);
            global $user_module_settings;
            if($def['tablename'] == 'users' &&
               isset($user_module_settings->define_user_nodetype) &&
               isset($user_module_settings->userpass_reset) &&
               $user_module_settings->define_user_nodetype &&
               function_exists('node_create'))
            {
                if($count == 0)
                {
                    $u = node_create('user');
                    $u->get_definition_field('uid')['hide'] = true;
                    $u->get_definition_field('role')['readonly'] = true;
                    $u->statictitle = t('Create first admin user');
                    $u->role = 1;
                    if($u->get_speedform_object()->in_action('insert'))
                    {
                        $u->get_speedform_object()->load_parameters();
                        $nid = $u->insert();
                        print "<td>Ok<br/>" . l('User login page', 'user/login') . "</td>";
                    }
                    else
                    {
                        $form = $u->getform('insert');
                        $form->action_post(current_loc());
                        $form->hidden("sep", par("sep"));
                        print "<td>Ok ($count row)<br/>" . $form->get() . "</td>";
                    }
                }
                else
                {
                    $sf = new SpeedForm($user_module_settings->userpass_reset);
                    if($sf->in_action('update'))
                    {
                        $sf->set_key(1);
                        $sf->do_select();
                        $sf->load_parameters();
                        $sf->do_update();
                        print "<td>Ok<br/>" . l('User login page', 'user/login') . "</td>";
                    }
                    else
                    {
                        $sf->set_key(1);
                        $sf->do_select();
                        $form = $sf->generate_form('update');
                        $form->action_post(current_loc());
                        $form->hidden("sep", par("sep"));
                        print "<td>Ok ($count row)<br/>" . $form->get() . "<br/>".
                              l('User login page', 'user/login')."</td>";
                    }
                }
            }
            else
            {
                print "<td>Ok ($count row)</td>";
            }
        }
        print '</tr>';
    }
    print '</tbody>';
    print '</table>';

    return ob_get_clean();
}

function hook_sql_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = ['sql' => ['path' => 'sys/doc/sql.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
    }
    return $docs;
}

/**
 * This hook defines the required sql tables/columns for the module
 * @package sql */
function _HOOK_required_sql_schema() {}

/**
 * Runs after the sql connection is established.
 * @package sql */
function _HOOK_sql_connected() {}

/**
 * Runs before an sql command is executed. It received the sql command and parameters but cannot modify its.
 * @package sql */
function _HOOK_execute_sql() {}

//end.
