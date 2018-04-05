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

    $db->qinterface_default_db_handler_class = 'DatabaseQuerySql';

    $db->error_locations = [
        'connection_error' => 'sql_connection_error',
        'generic_error'    => 'sql_error',
    ];

    $db->tr = [
        'timestamp_noupd'   => ['mysql' => 'DATETIME'         ,     'pgsql' => 'TIMESTAMP', ],
        'current_timestamp' => ['mysql' => 'CURRENT_TIMESTAMP',     'pgsql' => 'now()'    , ],
        'longtext_type'     => ['mysql' => 'LONGTEXT'         ,     'pgsql' => 'TEXT'     , ],
        'regex'             => ['mysql' => 'REGEXP'           ,     'pgsql' => '~'        , ],
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

/* ========= General database query/mode parts ========= */

/** General database query/modify interface starter function */
function db_action($action,$container,$alias = '',array $options = [])
{
    global $db;
    return new $db->qinterface_default_db_handler_class($action,$container,$alias,$options);
}

/** General database query starter function */
function db_query($container,$alias = '',array $options = [])
{
    return db_action('query',$container,$alias,$options);
}

/** General database insert starter function */
function db_insert($container,array $options = [])
{
    return db_action('insert',$container,'',$options);
}

/** General database update starter function */
function db_update($container,array $options = [])
{
    return db_action('update',$container,'',$options);
}

/** General database delete starter function */
function db_delete($container,array $options = [])
{
    return db_action('delete',$container,'',$options);
}

/** Creates a database condition object */
function cond($l)
{
    return new DatabaseCond($l,false);
}

/** Creates a database condition object in inverse */
function not_cond($l)
{
    return new DatabaseCond($l,true);
}

class DatabaseQuery
{
    protected $querytype;
    protected $conf;
    public function __construct($querytype,$container,$container_alias,array $container_options)
    {
        $this->querytype = $querytype;
        $this->conf = [
            'cont'       => $container,
            'cont_alias' => $container_alias,
            'cont_opts'  => $container_options,
            'mods'       => [],
            'gets'       => [],
            'sets'       => [],
            'joins'      => [],
            'conditions' => new DatabaseCond('and'),
            'sorts'      => [],
            'start'      => null,
            'length'     => null,
        ];
    }

    public function get_a(array $fieldnames,$container = '',array $options = [])
    {
        foreach($fieldnames as $f)
            $this->get([$container,$f],'',$options);
        return $this;
    }

    public function get($fieldspec,$alias = '',array $options = [])
    {
        $f = [];
        if(is_array($fieldspec))
        {
            $f['cont'] = $fieldspec[0];
            $f['name'] = $fieldspec[1];
        }
        else
        {
            $f['name'] = $fieldspec;
            $f['cont'] = '';
        }
        $f['opts'] = $options;
        $f['alias'] = $alias;
        $this->conf['gets'][] = $f;
        return $this;
    }

    public function get_clean()
    {
        $this->conf['gets'] = [];
        return $this;
    }

    public function set_clean()
    {
        $this->conf['sets'] = [];
        return $this;
    }

    public function join_clean()
    {
        $this->conf['joins'] = [];
        return $this;
    }

    public function cond_clean()
    {
        $this->conf['conditions'] = new DatabaseCond('and');
        return $this;
    }

    public function sort_clean()
    {
        $this->conf['sorts'] = [];
        return $this;
    }

    public function counting($fieldspec = '',$alias = '')
    {
        $this->conf['gets'] = [];
        if($alias == '')
            $alias = 'cnt';
        if($fieldspec == '')
            $fieldspec = '*';
        $this->get($fieldspec,$alias,['function' => 'count']);
        return $this;
    }

    public function set_fv($fieldspec,$value,array $options = [])
    {
        $this->conf['sets'][] = ['name' => $fieldspec,'value' => $value,'type' => 'value','opts' => $options];
        return $this;
    }

    public function set_fv_a($field_value_array)
    {
        foreach($field_value_array as $n => $v)
            $this->set_fv($n,$v);
        return $this;
    }

    public function set_fe($fieldspec,$expression,array $options = [])
    {
        $this->conf['sets'][] = ['name' => $fieldspec,'value' => $expression,'type' => 'expr','opts' => $options];
        return $this;
    }
    public function join($container,$alias,$conditions)
    {
        $this->conf['joins'][] =
            ['type' => 'normal','container' => $container,'alias' => $alias,'conditions' => $conditions];
        return $this;
    }
    public function join_ffe($container,$alias,$field1,$field2)
    {
        $this->conf['joins'][] = [
            'type' => 'normal',
            'container' => $container,
            'alias' => $alias,
            'conditions' => cond('and')->ff($field1,$field2,'='),
        ];
        return $this;
    }
    public function join_opt($container,$alias,$conditions)
    {
        $this->conf['joins'][] =
            ['type' => 'optional','container' => $container,'alias' => $alias,'conditions' => $conditions];
        return $this;
    }
    public function join_opt_ffe($container,$alias,$field1,$field2)
    {
        $this->conf['joins'][] = [
            'type' => 'optional',
            'container' => $container,
            'alias' => $alias,
            'conditions' => cond('and')->ff($field1,$field2,'='),
        ];
        return $this;
    }
    public function cond($cond)
    {
        $this->conf['conditions']->cond($cond);
        return $this;
    }
    public function cond_ff($fieldspec1,$fieldspec2,$op,array $options = [])
    {
        $this->conf['conditions']->ff($fieldspec1,$fieldspec2,$op,$options);
        return $this;
    }
    public function cond_fv($fieldspec,$value,$op,array $options = [])
    {
        $this->conf['conditions']->fv($fieldspec,$value,$op,$options);
        return $this;
    }
    public function cond_fe($fieldspec,$expression,$op,array $options = [])
    {
        $this->conf['conditions']->fe($fieldspec,$expression,$op,$options);
        return $this;
    }
    public function cond_fb($fieldspec,array $options = [])
    {
        $this->conf['conditions']->fb($fieldspec,$options);
        return $this;
    }
    public function cond_fnull($fieldspec,array $options = [])
    {
        $this->conf['conditions']->fnull($fieldspec,$options);
        return $this;
    }
    public function cond_sql($sqlpart)
    {
        $this->conf['conditions']->sql($sqlpart);
        return $this;
    }

    public function sort($fieldspec,array $options = [])
    {
        $this->conf['sorts'][] = ['field' => $fieldspec,'opts' => $options];
        return $this;
    }

    public function start($start)
    {
        $this->conf['start'] = $start;
        return $this;
    }
    public function length($length)
    {
        $this->conf['length'] = $length;
        return $this;
    }

    public function local_cmd()
    {
        return '';
    }

    public function execute(array $eopts = [])
    {
        return null;
    }
    public function execute_and_fetch(array $eopts = [])
    {
        return null;
    }
    public function execute_to_single(array $eopts = [])
    {
        return null;
    }
    public function execute_to_row(array $eopts = [])
    {
        return [];
    }
    public function execute_to_arrays(array $eopts = [])
    {
        return [];
    }
}

class DatabaseCond
{
    public $logic;
    public $conds;
    public $not;
    public function __construct($logic,$not = false)
    {
        $this->not = $not;
        $this->logic = $logic;
        $this->conds = [];
    }
    public function cond($cond)
    {
        $this->conds[] = $cond;
    }
    public function ff($fieldspec1,$fieldspec2,$op,array $options = [])
    {
        $this->conds[] = ['type' => 'ff','op' => $op,'f1' => $fieldspec1,'f2' => $fieldspec2,'opts' => $options];
        return $this;
    }
    public function fv($fieldspec,$value,$op,array $options = [])
    {
        $this->conds[] = ['type' => 'fv','op' => $op,'f' => $fieldspec,'v' => $value,'opts' => $options];
        return $this;
    }
    public function fe($fieldspec,$expression,$op,array $options = [])
    {
        $this->conds[] = ['type' => 'fe','op' => $op,'f' => $fieldspec,'e' => $expression,'opts' => $options];
        return $this;
    }
    public function fb($fieldspec,array $options = [])
    {
        $this->conds[] = ['type' => 'fb','f' => $fieldspec,'opts' => $options];
        return $this;
    }
    public function fnull($fieldspec,array $options = [])
    {
        $this->conds[] = ['type' => 'fn','f' => $fieldspec,'opts' => $options];
        return $this;
    }
    public function sql($sqlpart,array $options = [])
    {
        $this->conds[] = ['type' => 'sql','sql' => $sqlpart,'opts' => $options];
        return $this;
    }
}

/* General sql activity class SQL specific class */
class DatabaseQuerySql extends DatabaseQuery
{
    protected $calculated_query;
    protected $passed_parameters;
    protected $phidx;

    protected $valid_operands;

    public function __construct($querytype,$container,$container_alias, array $container_options)
    {
        parent::__construct($querytype,$container, $container_alias, $container_options);
        $this->passed_parameters = [];
        $this->valid_operands = ['=','!=','>','<','>=','<=','regex'];
    }

    public function build_sql_query()
    {
        $fc = 0;
        $this->phidx = 1;
        $this->calculated_query = '';
        if($this->querytype == 'insert')
        {
            $this->calculated_query = "INSERT INTO " . $this->conf['cont'];
            $np = '(';
            $vp = ' VALUES(';
            foreach($this->conf['sets'] as $set)
            {
                if($fc > 0)
                {
                    $np .= ',';
                    $vp .= ',';
                }
                ++$fc;
                if($set['type'] == 'value')
                {
                    $np .= $set['name'];

                    if(isset($set['opts']['function']) && $set['opts']['function'] != '')
                        $vp .= $set['opts']['function'] . '(';
                    $vp .= ':phi_'.$this->phidx;
                    if(isset($set['opts']['function']) && $set['opts']['function'] != '')
                    {
                        if(isset($set['opts']['more_args']) && $set['opts']['more_args'] != '')
                            $vp .= ','.$set['opts']['more_args'];
                        $vp .= ')';
                    }

                    $this->passed_parameters[':phi_'.$this->phidx] = $set['value'];
                    $this->phidx++;
                }
                if($set['type'] == 'expr')
                {
                    $np .= $set['name'];

                    if(isset($set['opts']['function']) && $set['opts']['function'] != '')
                        $vp .= $set['opts']['function'] . '(';
                    $vp .= $set['value'];
                    if(isset($set['opts']['function']) && $set['opts']['function'] != '')
                    {
                        if(isset($set['opts']['more_args']) && $set['opts']['more_args'] != '')
                            $vp .= ','.$set['opts']['more_args'];
                        $vp .= ')';
                    }
                }
            }
            $this->calculated_query .= $np. ')'.$vp.')';
        }
        if($this->querytype == 'query')
        {
            $this->calculated_query = "SELECT ";
            foreach($this->conf['gets'] as $field)
            {
                if($fc > 0)
                    $this->calculated_query .= ',';
                ++$fc;
                if(isset($field['opts']['function']) && $field['opts']['function'] != '')
                    $this->calculated_query .= $field['opts']['function'].'(';
                if($field['cont'] != '')
                    $this->calculated_query .= $field['cont'].'.';
                $this->calculated_query .= $field['name'];
                if(isset($field['opts']['function']) && $field['opts']['function'] != '')
                {
                    if(isset($field['opts']['more_args']) && $field['opts']['more_args'] != '')
                        $this->calculated_query .= ','.$field['opts']['more_args'];
                    $this->calculated_query .= ')';
                }
                if($field['alias'] != '')
                    $this->calculated_query .= ' AS '.$field['alias'];
            }
            if($fc == 0)
                $this->calculated_query .= '*';

            $this->calculated_query .= "\nFROM " . $this->conf['cont'];

            if($this->conf['cont_alias'] != '' && $this->conf['cont_alias'] != $this->conf['cont'])
                $this->calculated_query .= ' AS ' . $this->conf['cont_alias'];

            foreach($this->conf['joins'] as $join)
            {
                if($join['type'] == 'normal')
                    $this->calculated_query .= "\nINNER JOIN ".$join['container'];
                if($join['type'] == 'optional')
                    $this->calculated_query .= "\nLEFT OUTER JOIN ".$join['container'];
                if($join['alias'] != '' && $join['alias'] != $join['container'])
                    $this->calculated_query .= ' AS ' . $join['alias'];
                $this->calculated_query .= ' ON ' . $this->build_condition_part($join['conditions']);
            }
        }

        if($this->querytype == 'update')
        {
            $this->calculated_query = "UPDATE " . $this->conf['cont'] . ' SET ';
            foreach($this->conf['sets'] as $set)
            {
                if($fc > 0)
                    $this->calculated_query .= ',';
                ++$fc;
                if($set['type'] == 'value')
                {
                    $this->calculated_query .= $set['name'] . '=';

                    if(isset($set['opts']['function']) && $set['opts']['function'] != '')
                        $this->calculated_query .= $set['opts']['function'] . '(';
                    $this->calculated_query .= ':phi_'.$this->phidx;
                    if(isset($set['opts']['function']) && $set['opts']['function'] != '')
                    {
                        if(isset($set['opts']['more_args']) && $set['opts']['more_args'] != '')
                            $this->calculated_query .= ','.$set['opts']['more_args'];
                        $this->calculated_query .= ')';
                    }

                    $this->passed_parameters[':phi_'.$this->phidx] = $set['value'];
                    $this->phidx++;
                }
                if($set['type'] == 'expr')
                {
                    $this->calculated_query .= $set['name'] . '=';
                    if(isset($set['opts']['function']) && $set['opts']['function'] != '')
                        $this->calculated_query .= $set['opts']['function'] . '(';
                    $this->calculated_query .= $set['value'];
                    if(isset($set['opts']['function']) && $set['opts']['function'] != '')
                    {
                        if(isset($set['opts']['more_args']) && $set['opts']['more_args'] != '')
                            $this->calculated_query .= ','.$set['opts']['more_args'];
                        $this->calculated_query .= ')';
                    }
                }
            }
        }

        if($this->querytype == 'delete')
        {
            $this->calculated_query = "DELETE FROM " . $this->conf['cont'];
        }

        if($this->querytype == 'query' || $this->querytype == 'update' || $this->querytype == 'delete')
        {
            $condtext = $this->build_condition_part($this->conf['conditions']);
            if ($condtext != '')
                $this->calculated_query .= "\nWHERE " . $condtext;
        }

        if($this->querytype == 'query')
        {
            $sortcount = 0;
            foreach($this->conf['sorts'] as $sort)
            {
                if($sortcount == 0)
                    $this->calculated_query .= "\nORDER BY ";
                else
                    $this->calculated_query .= ',';

                if(isset($sort['opts']['function']) && $sort['opts']['function'] != '')
                    $this->calculated_query .= $sort['opts']['function'] . '(';

                if(is_array($sort['field']))
                    $this->calculated_query .= $sort['field'][0] . '.' . $sort['field'][1];
                else
                    $this->calculated_query .= $sort['field'];

                if(isset($sort['opts']['function']) && $sort['opts']['function'] != '')
                {
                    if(isset($sort['opts']['more_args']) && $sort['opts']['more_args'] != '')
                        $this->calculated_query .= ','.$sort['opts']['more_args'];
                    $this->calculated_query .= ')';
                }

                if(isset($sort['opts']['direction']) && $sort['opts']['direction'] = 'REVERSE')
                    $this->calculated_query .= ' DESC';
                ++$sortcount;
            }

            if($this->conf['length'] !== null)
                $this->calculated_query .= "\nLIMIT ".$this->conf['length'];
            if($this->conf['start'] !== null)
                $this->calculated_query .= "\nOFFSET ".$this->conf['start'];
        }
    }

    public function build_condition_part($c)
    {
        $qsp = '';
        foreach($c->conds as $cond)
        {
            if(is_object($cond))
            {
                if($qsp != '')
                    $qsp .= ' '.strtoupper($c->logic).' ';
                if($cond->not)
                    $qsp .= 'NOT ';
                $qsp .= '('.$this->build_condition_part($cond).')';
                continue;
            }
            if($qsp != '')
                $qsp .= ' '.$c->logic.' ';
            $op = '';
            if(isset($cond['op']))
                $op = $cond['op'];
            if($cond['type'] != 'sql' && $cond['type'] != 'fb' && $cond['type'] != 'fn')
            {
                if($op == '')
                    throw new Exception('Missing operand');
                if(in_array($op,$this->valid_operands) !== TRUE)
                    throw new Exception('Unknown operand');
            }
            if($op == '!=')
                $op = '<>';
            if($op == 'regex')
                $op = sql_t($op);
            if($cond['type'] == 'ff')
            {
                if(isset($cond['opts']['opposite']) && $cond['opts']['opposite'])
                    $qsp .= 'NOT ';

                if(isset($cond['opts']['f1function']) && $cond['opts']['f1function'] != '')
                    $qsp .= $cond['opts']['f1function'] . '(';
                if(is_array($cond['f1']))
                    $qsp .= $cond['f1'][0] . '.' . $cond['f1'][1];
                else
                    $qsp .= $cond['f1'];
                if(isset($cond['opts']['f1function']) && $cond['opts']['f1function'] != '')
                    $qsp .= ')';

                $qsp .= $op;

                if(isset($cond['opts']['f2function']) && $cond['opts']['f2function'] != '')
                    $qsp .= $cond['opts']['f2function'] . '(';
                if(is_array($cond['f2']))
                    $qsp .= $cond['f2'][0] . '.' . $cond['f2'][1];
                else
                    $qsp .= $cond['f2'];
                if(isset($cond['opts']['f2function']) && $cond['opts']['f2function'] != '')
                    $qsp .= ')';
            }
            if($cond['type'] == 'fv')
            {
                if(isset($cond['opts']['opposite']) && $cond['opts']['opposite'])
                    $qsp .= 'NOT ';

                if(isset($cond['opts']['ffunction']) && $cond['opts']['ffunction'] != '')
                    $qsp .= $cond['opts']['ffunction'] . '(';

                if(is_array($cond['f']))
                    $qsp .= $cond['f'][0] . '.' . $cond['f'][1];
                else
                    $qsp .= $cond['f'];

                if(isset($cond['opts']['ffunction']) && $cond['opts']['ffunction'] != '')
                    $qsp .= ')';

                $qsp .= $op;

                if(isset($cond['opts']['vfunction']) && $cond['opts']['vfunction'] != '')
                    $qsp .= $cond['opts']['vfunction'] . '(';
                $qsp .= ':phi_'.$this->phidx;
                if(isset($cond['opts']['vfunction']) && $cond['opts']['vfunction'] != '')
                    $qsp .=  ')';

                $this->passed_parameters[':phi_'.$this->phidx] = $cond['v'];
                $this->phidx++;
            }
            if($cond['type'] == 'fe')
            {
                if(isset($cond['opts']['opposite']) && $cond['opts']['opposite'])
                    $qsp .= 'NOT ';

                if(isset($cond['opts']['ffunction']) && $cond['opts']['ffunction'] != '')
                    $qsp .= $cond['opts']['ffunction'] . '(';

                if(is_array($cond['f']))
                    $qsp .= $cond['f'][0] . '.' . $cond['f'][1];
                else
                    $qsp .= $cond['f'];

                if(isset($cond['opts']['ffunction']) && $cond['opts']['ffunction'] != '')
                    $qsp .= ')';

                $qsp .= $op;

                if(isset($cond['opts']['efunction']) && $cond['opts']['efunction'] != '')
                    $qsp .= $cond['opts']['efunction'] . '(';
                $qsp .= $cond['e'];
                if(isset($cond['opts']['efunction']) && $cond['opts']['efunction'] != '')
                    $qsp .= ')';
            }
            if($cond['type'] == 'fb')
            {
                if(isset($cond['opts']['opposite']) && $cond['opts']['opposite'])
                    $qsp .= 'NOT ';

                if(isset($cond['opts']['ffunction']) && $cond['opts']['ffunction'] != '')
                    $qsp .= $cond['opts']['ffunction'] . '(';

                if(is_array($cond['f']))
                    $qsp .= $cond['f'][0] . '.' . $cond['f'][1];
                else
                    $qsp .= $cond['f'];

                if(isset($cond['opts']['ffunction']) && $cond['opts']['ffunction'] != '')
                    $qsp .= ')';
            }
            if($cond['type'] == 'fn')
            {
                if(isset($cond['opts']['opposite']) && $cond['opts']['opposite'])
                    $qsp .= 'NOT ';

                if(isset($cond['opts']['ffunction']) && $cond['opts']['ffunction'] != '')
                    $qsp .= $cond['opts']['ffunction'] . '(';

                if(is_array($cond['f']))
                    $qsp .= $cond['f'][0] . '.' . $cond['f'][1];
                else
                    $qsp .= $cond['f'];

                if(isset($cond['opts']['ffunction']) && $cond['opts']['ffunction'] != '')
                    $qsp .= ')';

                $qsp .= ' IS NULL';
            }
            if($cond['type'] == 'sql')
            {
                $qsp .= $cond['sql'];
            }
        }
        return $qsp;
    }
    public function local_cmd()
    {
        $this->build_sql_query();
        return $this->calculated_query;
    }

    public function execute(array $eopts = [])
    {
        $this->build_sql_query();
        $errormsg = '';
        if(isset($eopts['errormsg']))
            $errormsg = $eopts['errormsg'];
        if(isset($eopts['noredirect']) && $eopts['noredirect'])
            return sql_exec_noredirect($this->calculated_query,$this->passed_parameters);
        return sql_exec($this->calculated_query,$this->passed_parameters,$errormsg);
    }

    public function execute_and_fetch(array $eopts = [])
    {
        $this->build_sql_query();
        $errormsg = '';
        if(isset($eopts['errormsg']))
            $errormsg = $eopts['errormsg'];
        return sql_exec_fetchN($this->calculated_query,$this->passed_parameters,$errormsg);
    }

    public function execute_to_row(array $eopts = [])
    {
        return $this->execute_and_fetch($eopts);
    }

    public function execute_to_single(array $eopts = [])
    {
        $this->build_sql_query();
        $errormsg = '';
        if(isset($eopts['errormsg']))
            $errormsg = $eopts['errormsg'];
        return sql_exec_single($this->calculated_query,$this->passed_parameters,$errormsg);
    }

    public function execute_to_arrays(array $eopts = [])
    {
        $this->build_sql_query();
        $errormsg = '';
        if(isset($eopts['errormsg']))
            $errormsg = $eopts['errormsg'];
        $fetch_names_only = false;
        if(isset($eopts['fetch_names_only']) && $eopts['fetch_names_only'])
            $fetch_names_only = true;
        return sql_exec_fetchAll($this->calculated_query,$this->passed_parameters,$errormsg,$fetch_names_only);
    }
}

/* == End of general database query/mode parts == */

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
