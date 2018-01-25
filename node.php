<?php
/*  CodKep - Lightweight web framework core file
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 *
 *
 * Node module
 *  Required modules: core,sql,user,forms
 */

define('NODE_ACCESS_IGNORE',0);
define('NODE_ACCESS_ALLOW',1);
define('NODE_ACCESS_DENY',2);

define('DEF_NONE',0);
define('DEF_ARRAY',1);
define('DEF_OBJECT',2);

function hook_node_boot()
{
    global $site_config;
    $site_config->node_unauth_triggers_login = false;
    $site_config->node_access_early_block_actions = false;

    global $sys_data;
    $sys_data->node_types = array();
    $sys_data->node_otypes = array();
}

function hook_node_init()
{
    global $sys_data;
    $sys_data->node_types = run_hook('nodetype');
    $sys_data->node_otypes = run_hook('objectnodetype');

    foreach($sys_data->node_types as $name => $definition)
    {
        $pass = new stdClass();
        $pass->name = $name;
        $pass->def = &$sys_data->node_types[$name];
        run_hook('nodetype_alter_'.$name,$pass);
        ksort($sys_data->node_types[$name]['fields']);
    }

    spl_autoload_register(function($classname) {
        global $sys_data;
        foreach($sys_data->node_otypes as $otype)
        {
            if($otype['defineclass'] == $classname)
            {
                $file = $otype['file'];
                $pass = new stdClass();
                $pass->file = &$file;
                $pass->classname = &$classname;
                run_hook('load_nodedefclass',$pass);
                include $file;
                return;
            }
        }
    });

}

function hook_node_defineroute()
{
    $i = array();
    $i[] = ['path' => 'node/{nid}',
            'callback' => 'sys_node_callback_nid',
            'parameters' => [
              'nid' => ['security'=>'number0','source'=>'url','acceptempty'=>false,'default'=> NULL,'required' => true],
            ],
           ];

    $i[] = ['path' => 'node/{nid}/view',
            'callback' => 'sys_node_callback_nid',
            'parameters' => [
              'nid' => ['security'=>'number0','source'=>'url','acceptempty' => false,'default'=> NULL,'required'=>true],
            ],
           ];

    $i[] = ['path' => 'node/{nid}/edit',
            'callback' => 'sys_node_edit_callback_nid',
            'parameters' => [
              'nid' => ['security'=>'number0','source'=> 'url','acceptempty'=>false,'default'=> NULL,'required'=>true],
            ],
           ];

    $i[] = ['path' => 'node/{nid}/delete',
            'callback' => 'sys_node_delete_callback_nid',
            'parameters' => [
              'nid' => ['security'=>'number0','source'=> 'url','acceptempty'=>false,'default'=> NULL,'required'=>true],
            ],
           ];

    $i[] = ['path' => 'node/{nodetype}/add',
            'callback' => 'sys_node_create_callback',
            'parameters' => [
              'nodetype' => ['security'=>'text1ns','source'=>'url','acceptempty'=>false,'default'=>NULL,'required'=>true],
            ],
           ];

    $i[] = ['path' => 'nodeintype/{nodetype}/{joinid}',
        'callback' => 'sys_node_callback_intype',
        'parameters' => [
          'nodetype' => ['security'=>'text1ns','source'=>'url','acceptempty'=>false,'default'=>NULL,'required'=>true],
          'joinid'  =>  ['security'=>'text1ns','source'=>'url','acceptempty'=>false,'default'=>NULL,'required'=>true],
        ],
    ];

    $i[] = ['path' => 'nodeintype/{nodetype}/{joinid}/view',
        'callback' => 'sys_node_callback_intype',
        'parameters' => [
          'nodetype' => ['security'=>'text1ns','source'=>'url','acceptempty'=>false,'default'=>NULL,'required'=>true],
          'joinid'  =>  ['security'=>'text1ns','source'=>'url','acceptempty'=>false,'default'=>NULL,'required'=>true],
        ],
    ];

    $i[] = ['path' => 'nodeintype/{nodetype}/{joinid}/edit',
        'callback' => 'sys_node_edit_callback_intype',
        'parameters' => [
          'nodetype' => ['security'=>'text1ns','source'=>'url','acceptempty'=>false,'default'=>NULL,'required'=>true],
          'joinid'  =>  ['security'=>'text1ns','source'=>'url','acceptempty'=>false,'default'=>NULL,'required'=>true],
        ],
    ];

    $i[] = ['path' => 'nodeintype/{nodetype}/{joinid}/delete',
        'callback' => 'sys_node_delete_callback_intype',
        'parameters' => [
          'nodetype' => ['security'=>'text1ns','source'=>'url','acceptempty'=>false,'default'=>NULL,'required'=>true],
          'joinid'  =>  ['security'=>'text1ns','source'=>'url','acceptempty'=>false,'default'=>NULL,'required'=>true],
        ],
    ];

    return $i;
}

function node_access($node,$op,$account)
{
    if(!in_array($op,['create','precreate','delete','update','view']))
        return NODE_ACCESS_DENY;
    $na = run_hook('node_access',$node,$op,$account);
    $ns = run_hook('node_access_'.$node->node_type,$node,$op,$account);
    $nar = array_merge($na,$ns);
    if(in_array(NODE_ACCESS_DENY,$nar))
        return NODE_ACCESS_DENY;
    if(in_array(NODE_ACCESS_ALLOW,$nar))
        return NODE_ACCESS_ALLOW;

    //Default node permissions:
    // Allows everything for admins
    if($account->role == ROLE_ADMIN)
        return NODE_ACCESS_ALLOW;
    // Allows view and precreate for everyone. (You can disable by send DENY from a hook.)
    if($op == 'view' || $op == 'precreate')
        return NODE_ACCESS_ALLOW;
    return NODE_ACCESS_DENY;
}

/**
 *  Node class
 *
 * @property-read int $node_nid The nid identifier of the node
 * @property-read string $node_type The type string of the node
 * @property-read mixed $node_join_id The id of the joined table which hold the data fields
 * @property-read string $node_creator The user identifier of the node creator
 * @property-read string $node_created The creation timestamp of the node
 * @property-read string $node_preferred_theme The preferred theme of the node
 * @property-read int $node_deftype The define method
 * @property-read bool $node_loaded If the node is loaded or not
 */
class Node
{
    protected $nid;
    protected $type;
    protected $join_id;
    protected $creator;
    protected $created;
    protected $preferred_theme;
    protected $dataspeedform;
    protected $deftype;

    public function __construct($type = 'node')
    {
        global $user;
        global $sys_data;

        $this->deftype = DEF_NONE;
        $this->nid = NULL;
        $this->join_id = NULL;
        $this->preferred_theme = NULL;
        $this->creator = $user->auth ? $user->login : '<unauthenticated>';

        if($type == 'node') //Uninitialized node (before load)
        {
            $this->type = NULL;
            $this->dataspeedform = NULL;
        }
        else
        {
            if(array_key_exists($type,$sys_data->node_types))
            {
                $this->deftype = DEF_ARRAY;
                $this->type = $type;
                $this->dataspeedform = new SpeedForm($sys_data->node_types[$type]);
            }
            if(array_key_exists($type,$sys_data->node_otypes))
            {
                $this->deftype = DEF_OBJECT;
                $this->type = $type;
                $definerclass = $sys_data->node_otypes[$type]['defineclass'];
                $this->dataspeedform = new SpeedForm($definerclass::$definition);
            }
            if($this->deftype == DEF_NONE)
            {
                load_loc('error',
                    t('Node creating requested with unknown node type: "_unktype_"',['_unktype_'=>$type]),
                    t('Unknown type error'));
                return;
            }
        }
    }

    function __set($name,$value)
    {
        if($this->dataspeedform === NULL)
        {
            load_loc('error',
                t('Value set request received for an uninitialized (typeless) node: "_namereq_"',['_namereq_'=>$name]),
                t('Uninitialized node set error'));
        }
        else
        {
            if(array_key_exists($name,$this->dataspeedform->values))
                $this->dataspeedform->values[$name] = $value;
        }
    }

    function __get($name)
    {
        if($name == 'node_nid')
            return $this->nid;
        if($name == 'node_type')
            return $this->type;
        if($name == 'node_join_id')
            return $this->join_id;
        if($name == 'node_deftype')
            return $this->deftype;
        if($name == 'node_creator')
            return $this->creator;
        if($name == 'node_created')
            return $this->created;
        if($name == 'node_loaded')
        {
            if($this->dataspeedform == NULL)
                return false;
            return true;
        }
        if($name == 'node_preferred_theme')
            return $this->preferred_theme;

        if($this->dataspeedform === NULL)
        {
            load_loc('error',
                t('Value request for an uninitialized (typeless) node: "_namereq_"',['_namereq_'=>$name]),
                t('Uninitialized node error'));
            return NULL;
        }
        else
        {
            if(array_key_exists($name,$this->dataspeedform->values))
                return $this->dataspeedform->values[$name];
            return NULL;
        }
    }

    function __isset($name)
    {
        if($name == 'node_nid' && $this->nid !== NULL)
            return true;
        if($name == 'node_type' && $this->type !== NULL)
            return true;
        if($name == 'node_join_id' && $this->join_id !== NULL)
            return true;
        if($name == 'node_creator' && $this->created !== NULL)
            return true;
        if($name == 'node_created' && $this->created !== NULL)
            return true;
        if($name == 'node_preferred_theme' && $this->preferred_theme !== NULL)
            return true;
        if(array_key_exists($name,$this->dataspeedform->values))
            return true;
        return false;
    }

    function __unset($name)
    {
    }

    public function definedFields()
    {
        if($this->dataspeedform === NULL)
            return array();
        return array_keys($this->dataspeedform->values);
    }

    public function insert()
    {
        if($this->dataspeedform === NULL || $this->type == NULL)
        {
            load_loc('error',
                t('Node insert request for an uninitialized (typeless) node!'),
                t('Uninitialized node error'));
            return NULL;
        }

        $p1 = new stdClass();
        $p1->node_ref = &$this;
        run_hook('node_before_insert',$p1);

        $this->m_before_insert();

        sql_transaction();
        global $db;
        global $user;

        $this->join_id = $this->insert_data();

        $ptempl = $this->preferred_theme === NULL ? 'NULL' : "'$this->preferred_theme'";
        sql_exec("INSERT INTO node(type,join_id,ptempl,creator,created)
                  VALUES(:ttype,:tjoin_id,:ptempl,:tcreator,".sql_t('current_timestamp').")",
                [':ttype' => $this->type,
                 ':tjoin_id' => $this->join_id,
                 ':ptempl' => $ptempl,
                 ':tcreator' => $this->creator]);
        $this->nid = sql_exec_single("SELECT nid FROM node WHERE type=:ttype AND join_id=:tjoin_id",
                            [':ttype' => $this->type,
                             ':tjoin_id' => $this->join_id]);

        sql_commit();

        $this->m_after_insert();

        $p2 = new stdClass();
        $p2->node_ref = &$this;
        run_hook('node_inserted',$p2);

        return $this->nid;
    }

    protected function insert_data()
    {
        return $this->dataspeedform->do_insert();
    }

    public static function load($nid = NULL,$trunk = false)
    {
        global $sys_data;
        if($nid === NULL)
            return NULL;
        if($nid !== NULL)
        {
            if(!check_str($nid,'number0'))
                return NULL;
        }

        $n = sql_exec_fetchN("SELECT nid,type,join_id,ptempl,creator,created FROM node WHERE nid=:nidid",
                                [':nidid' => $nid]);
        if($n == NULL || count($n) == 0)
            return NULL;

        $node = Node::getNodeInstanceByType($n['type']);
        $node->load_nodeobject($n['nid'],$n['type'],$n['join_id'],
                         (($n['join_id'] == NULL || $n['join_id']) == '' ? NULL : $n['join_id']),
                         $n['creator'],$n['created'],$trunk);
        return $node;
    }

    public static function load_intype($join_id,$type)
    {
        global $sys_data;

        $deftype = DEF_NONE;
        if(array_key_exists($type,$sys_data->node_types))
            $deftype = DEF_ARRAY;
        if(array_key_exists($type,$sys_data->node_otypes))
            $deftype = DEF_OBJECT;
        if($deftype == DEF_NONE)
        {
            load_loc('error',
                t('Node load_intype requested with unknown node type: "_unktype_"',['_unktype_'=>$type]),
                t('Unknown type error'));
            return NULL;
        }

        $n = sql_exec_fetchN("SELECT nid,type,join_id,ptempl,creator,created FROM node
                              WHERE type=:type AND join_id=:join_id",
                                [':type' => $type,
                                 ':join_id' => $join_id]);
        if($n == NULL || count($n) == 0)
        {
            return NULL;
        }

        $node = Node::getNodeInstanceByType($n['type']);
        $node->load_nodeobject($n['nid'],$n['type'],$n['join_id'],
            (($n['join_id'] == NULL || $n['join_id']) == '' ? NULL : $n['join_id']),
            $n['creator'],$n['created'],false);
        return $node;
    }

    private function load_nodeobject($nid,$type,$join_id,$theme,$creator,$created,$trunk)
    {
        $this->nid = $nid;
        $this->type = $type;
        $this->join_id = $join_id;
        $this->preferred_theme = $theme;
        $this->creator = $creator;
        $this->created = $created;
        if($this->dataspeedform === NULL)
            load_loc('error','Internal error E87');

        $this->load_data($trunk);

        $this->m_after_loaded();
        $pass = new stdClass();
        $pass->node_ref = &$this;
        run_hook('node_loaded',$pass,$this->nid,$this->type,$this->join_id);
    }

    protected function load_data($trunk)
    {
        $this->dataspeedform->set_key($this->join_id);
        if($trunk)
            return;
        $this->dataspeedform->do_select();
    }

    public static function getNodeInstanceByType($type)
    {
        global $sys_data;

        $classname = 'Node';
        if(array_key_exists($type,$sys_data->node_types))
            if(isset($sys_data->node_types[$type]['classname']))
                $classname = $sys_data->node_types[$type]['classname'];

        if(array_key_exists($type,$sys_data->node_otypes))
        {
            $definerclass = $sys_data->node_otypes[$type]['defineclass'];
            $d = $definerclass::$definition;
            if(isset($d['classname']))
                $classname = $d['classname'];
        }

        if($classname != 'Node')
        {
            $rc = new ReflectionClass($classname);
            if(!$rc->isSubclassOf('Node'))
            {
                load_loc('error',
                    t('Requested node object type is not subclass of Node!'),
                    t('Node type error'));
                return NULL;
            }
        }
        return new $classname($type);
    }

    public function save()
    {
        if($this->dataspeedform === NULL)
        {
            load_loc('error',
                t('Node save requested on uninitialized node!'),
                t('Unknown type error'));
            return;
        }

        $p1 = new stdClass();
        $p1->node_ref = &$this;
        run_hook('node_before_save',$p1);

        $this->m_before_save();
        sql_transaction();
        $this->save_data();
        sql_commit();
        $this->m_after_save();

        $p2 = new stdClass();
        $p2->node_ref = &$this;
        run_hook('node_saved',$p2);
    }

    protected function save_data()
    {
        $this->dataspeedform->set_key($this->join_id);
        $this->dataspeedform->do_update();
    }

    public function remove()
    {
        if($this->nid != NULL && $this->dataspeedform != NULL)
        {
            $this->m_before_delete();
            sql_transaction();
            sql_exec("DELETE FROM node WHERE nid=:nid",[':nid' => $this->nid]);
            $this->remove_data();
            sql_commit();

            run_hook("node_deleted",$this->nid,$this->type,$this->join_id);
        }
    }

    protected function remove_data()
    {
        $table = $this->dataspeedform->def['table'];
        $keyname = $this->dataspeedform->get_key_name();
        $keyval = $this->dataspeedform->get_key_sqlvalue(true);
        $this->dataspeedform->clean_before_delete();
        sql_exec("DELETE FROM $table WHERE $keyname = :keyval",[':keyval' => $keyval]);
    }

    public function getform($mode = '')
    {
        if($this->dataspeedform === NULL)
            return NULL;
        $form = $this->dataspeedform->generate_form($mode);
        return $form;
    }

    public function view()
    {
        if($this->dataspeedform === NULL)
        {
            load_loc('error',
                t('Value request for an uninitialized (typeless) node!'),
                t('Uninitialized node error'));
            return '';
        }

        if(!isset($this->dataspeedform->def['view_callback']) && !isset($this->dataspeedform->def['view_file']))
        {
            $form = $this->getform('select');
            return $this->m_form_code_generation($form,true);
        }
        if(isset($this->dataspeedform->def['view_callback']))
        {
            return call_user_func_array($this->dataspeedform->def['view_callback'],array($this));
        }
        if(isset($this->dataspeedform->def['view_phpfile']))
        {
            ob_start();
            global $node;
            $node = $this;
            include $this->dataspeedform->def['view_phpfile'];
            return ob_get_clean();
        }
    }

    public function get_display_value($fieldname)
    {
        return $this->dataspeedform->get_display_value($fieldname);
    }

    public function get_speedform_object()
    {
        return $this->dataspeedform;
    }

    public function &get_definition_root()
    {
        return $this->dataspeedform->def;
    }

    public function &get_definition_field($sqlname)
    {
        return $this->dataspeedform->get_field($sqlname);
    }

    public function m_after_loaded()  { }
    public function m_before_insert()  { }
    public function m_after_insert()  { }
    public function m_before_save()  { }
    public function m_after_save()  { }
    public function m_before_delete()  { }
    public function m_before_form($op)  { return ''; }
    public function m_after_form($op)   { return ''; }

    public function m_form_code_generation($form,$ro)  { return $form->get($ro); }
}

function node_create($type)
{
    global $sys_data;

    if(array_key_exists($type,$sys_data->node_types))
        return Node::getNodeInstanceByType($type);
    if(array_key_exists($type,$sys_data->node_otypes))
        return Node::getNodeInstanceByType($type);
    return NULL;
}

function node_load($nid)
{
    return Node::load($nid);
}

function node_load_intype($join_id,$type)
{
    return Node::load_intype($join_id,$type);
}

function node_delete($nid)
{
    $node = Node::load($nid);
    $node->remove();
}

function sys_node_callback_nid()    {  return sys_node_view_uni(node_load(par('nid')));  }
function sys_node_callback_intype() {  return sys_node_view_uni(node_load_intype(par('joinid'),par('nodetype'))); }
function sys_node_view_uni($node)
{
    global $site_config;
    global $user;
    $op = 'view';

    if($node->node_nid == NULL)
    {
        load_loc('error',t('The requested node is not found'),t('Not found'));
        return 'Not found';
    }
    if(NODE_ACCESS_ALLOW != node_access($node,'view',$user))
    {
        if(!$user->auth && $site_config->node_unauth_triggers_login)
            require_auth();
        run_hook("node_operation_not_permitted",$node,$op,$user);
        load_loc('error',t('You don\'t have the required permission to access this node'),t('Permission denied'));
        return 'Permission denied';
    }
    run_hook('node_before_action',$node,$op,$user);
    $o = '';
    $o .= implode(run_hook("node_form_before",$node,$op));
    $o .= $node->m_before_form($op);
    $o .= $node->view();
    $o .= $node->m_after_form($op);
    $o .= implode(run_hook("node_form_after",$node,$op));
    return $o;
}

function sys_node_edit_callback_nid()    { return sys_node_edit_uni(node_load(par('nid'))); }
function sys_node_edit_callback_intype() { return sys_node_edit_uni(node_load_intype(par('joinid'),par('nodetype'))); }
function sys_node_edit_uni($node)
{
    global $site_config;
    global $user;
    $op = 'edit';

    if($node->node_nid == NULL)
    {
        load_loc('error',t('The requested node is not found'),t('Not found'));
        return 'Not found';
    }
    $action_to_check = 'view';
    if($node->get_speedform_object()->in_action('update') || $site_config->node_access_early_block_actions)
        $action_to_check = 'update';
    if(NODE_ACCESS_ALLOW != node_access($node, $action_to_check, $user))
    {
        if(!$user->auth && $site_config->node_unauth_triggers_login)
            require_auth();
        run_hook("node_operation_not_permitted",$node,$op,$user);
        load_loc('error', t('You don\'t have the required permission to access this node'), t('Permission denied'));
        return 'Permission denied';
    }

    if($node->get_speedform_object()->in_action('update'))
    {
        $node->get_speedform_object()->load_parameters();
        run_hook("node_will_update",$node);
        $node->save();
        run_hook("node_operation_done",$node->node_type,$op,$node->node_nid);
        load_loc("node/".$node->node_nid);
        return;
    }
    run_hook('node_before_action',$node,$op,$user);
    $form = $node->getform('update');
    $form->action_post(current_loc());
    $o = '';
    $o .= implode(run_hook("node_form_before",$node,$op));
    $o .= $node->m_before_form($op);
    $o .= $node->m_form_code_generation($form,false);
    $o .= $node->m_after_form($op);
    $o .= implode(run_hook("node_form_after",$node,$op));
    return $o;
}

function sys_node_delete_callback_nid()    { return sys_node_delete_uni(node_load(par('nid'))); }
function sys_node_delete_callback_intype() { return sys_node_delete_uni(node_load_intype(par('joinid'),par('nodetype'))); }
function sys_node_delete_uni($node)
{
    global $site_config;
    global $user;
    $op = 'delete';

    $type = $node->node_type;
    if($node->node_nid == NULL)
    {
        load_loc('error',t('The requested node is not found'),t('Not found'));
        return 'Not found';
    }

    $action_to_check = 'view';
    if($node->get_speedform_object()->in_action('delete') || $site_config->node_access_early_block_actions)
        $action_to_check = 'delete';
    if(NODE_ACCESS_ALLOW != node_access($node, $action_to_check, $user))
    {
        if(!$user->auth && $site_config->node_unauth_triggers_login)
            require_auth();
        run_hook("node_operation_not_permitted",$node,$op,$user);
        load_loc('error', t('You don\'t have the required permission to delete this node'), t('Permission denied'));
        return 'Permission denied';
    }

    if($node->get_speedform_object()->in_action('delete'))
    {
        run_hook("node_will_delete",$node);
        $node->remove();
        run_hook("node_operation_done",$type,$op,'0');
        load_loc(get_startpage());
        return;
    }
    run_hook('node_before_action',$node,$op,$user);
    $form = $node->getform('delete');
    $form->action_post(current_loc());
    $o = '';
    $o .= implode(run_hook("node_form_before",$node,$op));
    $o .= $node->m_before_form($op);
    $o .= $node->m_form_code_generation($form,true);
    $o .= $node->m_after_form($op);
    $o .= implode(run_hook("node_form_after",$node,$op));
    return $o;
}

function sys_node_create_callback()
{
    global $site_config;
    global $user;
    $op = 'add';
    $type = par('nodetype');
    $node = Node::getNodeInstanceByType($type);

    $action_to_check = 'precreate';
    if($node->get_speedform_object()->in_action('insert') || $site_config->node_access_early_block_actions)
        $action_to_check = 'create';
    if(NODE_ACCESS_ALLOW != node_access($node,$action_to_check,$user))
    {
        if(!$user->auth && $site_config->node_unauth_triggers_login)
            require_auth();
        run_hook("node_operation_not_permitted",$node,$op,$user);
        load_loc('error',t('You don\'t have the required permission to create this node'),t('Permission denied'));
        return 'Permission denied';
    }
    if($node->get_speedform_object()->in_action('insert'))
    {
        $node->get_speedform_object()->load_parameters();
        $nid = $node->insert();
        run_hook("node_operation_done",$node->node_type,$op,$node->node_nid);
        goto_loc("node/$nid");
        return;
    }
    run_hook('node_before_action',$node,$op,$user);
    $form = $node->getform('insert');
    $form->action_post(current_loc());
    $o = '';
    $o .= implode(run_hook("node_form_before",$node,$op));
    $o .= $node->m_before_form($op);
    $o .= $node->m_form_code_generation($form,false);
    $o .= $node->m_after_form($op);
    $o .= implode(run_hook("node_form_after",$node,$op));
    return $o;
}

function sql_table_of_nodetype($type)
{
    global $sys_data;
    if(array_key_exists($type,$sys_data->node_types))
    {
        $s = new SpeedForm($sys_data->node_types[$type]);
        return $s->sql_create_string();
    }
    if(array_key_exists($type,$sys_data->node_otypes))
    {
        $definerclass = $sys_data->node_otypes[$type]['defineclass'];
        $s = new SpeedForm($definerclass::$definition);
        return $s->sql_create_string();
    }
    return '';
}

function hook_node_required_sql_schema()
{
    global $sys_data;
    $t = array();

    $t['node_module_node_table'] =
    [
        "tablename" => 'node',
        "columns" => [
                'nid'       => 'SERIAL',
                'type'      => 'VARCHAR(64)',
                'join_id'   => 'VARCHAR(64)',
                'ptempl'    => 'VARCHAR(32)',
                'creator'   => 'VARCHAR(64)',
                'created'   => 'TIMESTAMP',
        ],
    ];

    foreach($sys_data->node_types as $nname => $ndef)
    {
        $sf = new SpeedForm($ndef);
        $t['Node: '.$nname] = $sf->sql_create_schema();
    }
    foreach($sys_data->node_otypes as $nname => $nval)
    {
        $definerclass = $nval['defineclass'];
        $sf = new SpeedForm($definerclass::$definition);
        $t['dNode: '.$nname] = $sf->sql_create_schema();
    }
    return $t;
}

function hook_node_check_module_requirements()
{
    $classautoloader = function_exists('spl_autoload_register');
    $reflection = class_exists('ReflectionClass');

    ob_start();
    print '<tr>';
    print '<td class="normal">Php Dynamic class loader (spl_autoload_register)</td>';
    print '<td class="'.($classautoloader ? 'green':'red').'">'.($classautoloader ? 'Available' : 'Not available').'</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="normal">Php ReflectionClass</td>';
    print '<td class="'.($reflection ? 'green':'red').'">'.($reflection ? 'Available' : 'Not available').'</td>';
    print '</tr>';
    return ob_get_clean();
}


function hook_node_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = ['node' => ['path' => 'sys/doc/node.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
    }
    return $docs;
}

/**
 * Define one or more node type.
 * @package node */
function _HOOK_nodetype() {}

/**
 * Define one or more node type.
 * @package node */
function _HOOK_objectnodetype() {}

/**
 * Invoked when the node module loads a php file for Nodetype define class.
 * @package node */
function _HOOK_load_nodedefclass() {}

/**
 * You can change the definition of the specified node type by this hook.
 * @package node */
function _HOOK_nodetype_alter_NODETYPE() {}

/**
 * This hook can put some content before the node add/edit/view/delete forms
 * @package node */
function _HOOK_node_form_before() {}

/**
 * This hook can put some content after the node add/edit/view/delete forms
 * @package node */
function _HOOK_node_form_after() {}

/**
 * This hook controls the access to a node
 * @package node */
function _HOOK_node_access() {}

/**
 * This hook controls the access to a node which type is NODETYPE
 * @package node */
function _HOOK_node_access_NODETYPE() {}

/**
 * Runs before the node is saved
 * @package node */
function _HOOK_node_before_save() {}

/**
 * Runs immediately after a node is saved
 * @package node */
function _HOOK_node_saved() {}

/**
 * Runs before the node is inserted
 * @package node */
function _HOOK_node_before_insert() {}

/**
 * Runs immediately after a node is inserted
 * @package node */
function _HOOK_node_inserted() {}

/**
 * Runs immediately after a node is deleted
 * @package node */
function _HOOK_node_deleted() {}

/**
 * Runs immediately after a node is loaded
 * @package node */
function _HOOK_node_loaded() {}

/**
 * Runs on node view/add/edit/delete before the form is generated.
 * @package node */
function _HOOK_node_before_action() {}

/**
 * This hook runs after an operation is done on some node.
 * This hook is useful to do some redirections
 * @package node */
function _HOOK_node_operation_done() {}

/**
 * This hook runs immediately before a node is updated.
 * If you do redirect in this hook, the operation will be cancelled.
 * @package node */
function _HOOK_node_will_update() {}

/**
 * This hook runs immediately before a node is deleted.
 * If you do redirect in this hook, the operation will be cancelled.
 * @package node */
function _HOOK_node_will_delete() {}

// end.
