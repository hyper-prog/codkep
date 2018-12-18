<?php
/*  CodKep - Lightweight web framework core file
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 *
 *
 * File module
 *  Required modules: core,sql,user
 */

define('FILE_ACCESS_IGNORE',0);
define('FILE_ACCESS_ALLOW',1);
define('FILE_ACCESS_DENY',2);

/** @ignore
 * File module default settings. You can change this values in your site/_settings.php */
function hook_file_boot()
{
    global $sys_data;
    global $site_config;
    $site_config->public_file_path  = 'data';
    $site_config->public_file_url   = '/data';
    $site_config->secure_file_path  = 'data/secure';
    $site_config->secure_file_url   = '/data/secure';

    $sys_data->image_file_sizeclass_list = [];

    $site_config->file_ufi_lastInsertId_name = 'ufi';
}

/**
 * Returns true if a directory exists and writeable
 * @param string $dir The directory path to examine
 * @return bool
 * @package file */
function directory_exists_and_writeable($dir)
{
    return file_exists($dir) && is_dir($dir) && is_writeable($dir);
}

/** @ignore
 *  The check_module_requirements hook of file module.
 *  This checks the prerequisites of file module */
function hook_file_check_module_requirements()
{
    global $site_config;
    ob_start();
    $pub_has = directory_exists_and_writeable($site_config->public_file_path);
    $sec_has = directory_exists_and_writeable($site_config->secure_file_path);

    if(!$pub_has)
    {
        mkdir($site_config->public_file_path,0777,true);
        $pub_has = directory_exists_and_writeable($site_config->public_file_path);
    }

    if(!$sec_has)
    {
        mkdir($site_config->secure_file_path,0777,true);
        $sec_has = directory_exists_and_writeable($site_config->secure_file_path);
    }

    if($pub_has && !file_exists($site_config->public_file_path.'/.htaccess'))
    {
        $str = "Options None\nOptions +FollowSymLinks\n<IfModule mod_php5.c>\n php_flag engine off\n</IfModule>";
        if(file_put_contents($site_config->public_file_path.'/.htaccess',$str) === FALSE)
            $sec_has = false;
    }

    if($sec_has && !file_exists($site_config->secure_file_path.'/.htaccess'))
    {
        $str = "Deny from all\nOptions None\nOptions +FollowSymLinks";
        if(file_put_contents($site_config->secure_file_path.'/.htaccess',$str) === FALSE)
            $sec_has = false;
    }

    print '<tr>';
    print '<td class="normal">Public directory (CODKEP/'.$site_config->public_file_path.')</td>';
    print '<td class="'.($pub_has ? 'green':'red').'">'.($pub_has ? 'Exists & Writeable' : 'Not exists or Not writeable').'</td>';
    print '</tr>';

    print '<tr>';
    print '<td class="normal">Secure directory (CODKEP/'.$site_config->secure_file_path.')</td>';
    print '<td class="'.($sec_has ? 'green':'red').'">'.($sec_has ? 'Exists & Writeable' : 'Not exists or Not writeable').'</td>';
    print '</tr>';

    return ob_get_clean();
}

function hook_file_defineroute()
{
    $i = [];
    $i[] = [
        'path' => 'file/secure/{ufi}/{filename}',
        'callback' => 'file_get_secure',
        'type' => 'raw',
    ];

    $i[] = [
        'path' => 'file/disabled_content',
        'callback' => 'file_disabled_content',
        "theme" => "base_page",
    ];

    return $i;
}

/**
 * File class
 *
 * @property-read string $url The url of the file
 * @property-read string $path The filesystem path of the file
 */
class File
{
    public $ufi;
    public $type;
    public $subdir;
    public $name;
    public $fsname;
    public $mime;
    public $uploaded;
    public $uploader;

    public function __construct($type)
    {
        if(!in_array($type,['public','secure','']))
            load_loc('error',t('Unknown file container: _unk_',['_unk_' => $type]),t('File upload error'));

        $this->type = $type;
        $this->ufi = NULL;
        $this->name = '';
        $this->subdir = '';
        $this->fsname = '';
        $this->mime = '';
        $this->uploader = '';
        $this->uploaded = '';
    }

    public function basePath($t_url_f_path = false,$create = false)
    {
        global $site_config;

        $dir = '';
        if($this->type == '')
            load_loc('error',t('Path request created with empty type!'),t('File error'));
        if($this->type == 'public')
            $dir = $t_url_f_path ? $site_config->public_file_url : $site_config->public_file_path;
        if($this->type == 'secure')
            $dir = $t_url_f_path ? $site_config->secure_file_url : $site_config->secure_file_path;

        if($this->subdir != '')
        {
            $dir .= '/' . $this->subdir;
            if($create && !file_exists($dir))
                mkdir($dir,0777,true);
        }

        return $dir;
    }

    protected function scCacheFilePath($sc,$create = false)
    {
        $dir = $this->basePath(false) . '/' . $sc;
        if($create && !file_exists($dir))
            mkdir($dir, 0777, true);
        return  $dir . '/' . $this->fsname;
    }

    protected function scCacheFileUrl($sc)
    {
        return $this->basePath(true) . '/' . $sc . '/' . $this->fsname;
    }

    public function addFromTemp($name,$from,$subdir='',array $context = [])
    {
        global $db;
        global $user;
        global $site_config;

        $this->ufi = NULL;
        $this->subdir = $subdir;
        $this->name = $name;

        if(file_access($this,'create',$user) != FILE_ACCESS_ALLOW)
        {
            load_loc('error',t('You don\'t have the required permission to create the file'),t('Permission denied'));
            return 'Permission denied';
        }

        $dir = $this->basePath(false,true);

        $uidx = 1;
        $path_parts = pathinfo($name);

        $p0 = new stdClass();
        $p0->pp_ref = &$path_parts;
        $p0->file_ref = &$this;
        run_hook('upload_filename_alter',$p0);
        //Due to usability reasons the spaces are replaced to underscores
        $path_parts['filename'] = preg_replace('/\s/','_',$path_parts['filename']);
        $this->name = preg_replace('/\s/','_',$this->name);

        $fsname = $path_parts['filename'] . '.' . $path_parts['extension'];
        while(file_exists($dir.'/'.$fsname))
        {
            $fsname = $path_parts['filename'] . "_$uidx" . '.' . $path_parts['extension'];
            ++$uidx;
        }

        $mime = '';
        try
        {
            if(isset($context['noupload']) && $context['noupload'])
            {
                if(!rename($from,$dir.'/'.$fsname))
                    load_loc('error','Sorry to inform you, something wrong with the file moving','File access error');
            }
            else
            {
                if(!move_uploaded_file($from,$dir.'/'.$fsname))
                    load_loc('error','Sorry to inform you, something wrong with the file upload','File upload error');
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo,$dir.'/'.$fsname);
            finfo_close($finfo);
        }
        catch(Exception $e)
        {
            load_loc('error','Error on file upload:'.$e->getMessage(),'File upload error');
        }

        $this->mime = $mime;
        $this->fsname = $fsname;
        $this->uploader = $user->auth ? $user->login : '<unauthenticated>';

        $p1 = new stdClass();
        $p1->file_ref = &$this;
        $p1->context = $context;
        run_hook('file_upload_in',$p1);

        sql_exec("INSERT INTO file(type,sub,name,fsname,mime,uploader,uploaded)
                  VALUES(:ttype,:subdir,:tname,:fsname,:mime,:tuploader,".sql_t('current_timestamp').")",
                [':ttype' => $this->type,
                 ':subdir' => $subdir,
                 ':tname' => $this->name,
                 ':fsname' => $fsname,
                 ':mime' => $mime,
                 ':tuploader' => $this->uploader]);

        $this->ufi = $db->sql->lastInsertId($site_config->file_ufi_lastInsertId_name);

        $p2 = new stdClass();
        $p2->file_ref = &$this;
        $p2->context = $context;
        run_hook('file_uploaded',$p2);
        return $this->ufi;
    }

    public function load($ufi,$disable_show_error = false)
    {
        $this->ufi = NULL;
        $f = sql_exec_fetchN("SELECT ufi,type,sub,mime,name,fsname,uploader,uploaded FROM file WHERE ufi=:ufi",
                               [':ufi' => $ufi]);
        if($f == NULL || empty($f))
        {
            if(!$disable_show_error)
                load_loc('error','File UFI not found','Not found file error');
            return NULL;
        }

        $this->ufi      = $f['ufi'];
        $this->type     = $f['type'];
        $this->name     = $f['name'];
        $this->subdir   = $f['sub'];
        $this->mime     = $f['mime'];
        $this->fsname   = $f['fsname'];
        $this->uploader = $f['uploader'];
        $this->uploaded = $f['uploaded'];

        $p1 = new stdClass();
        $p1->file_ref = &$this;
        run_hook('file_loaded',$p1);

        return $this->ufi;
    }

    public function remove()
    {
        global $user;
        if($this->ufi == NULL)
            return;

        if(file_access($this,'delete',$user) != FILE_ACCESS_ALLOW)
        {
            load_loc('error',t('You don\'t have the required permission to remove this file'),t('Permission denied'));
            return 'Permission denied';
        }

        $p1 = new stdClass();
        $p1->file_ref = &$this;
        run_hook('file_will_delete',$p1);

        if($this->sizeClassesAllowed())
        {
            global $sys_data;
            foreach($sys_data->image_file_sizeclass_list as $sc => $sc_sizes)
                if(file_image_sizeclass($this,$sc) == FILE_ACCESS_ALLOW)
                    unlink($this->scCacheFilePath($sc));
        }

        if(file_exists($this->basePath(false) . '/' . $this->fsname))
            unlink($this->basePath(false) . '/' . $this->fsname);

        sql_exec("DELETE FROM file WHERE ufi=:ufi",
                  [':ufi' => $this->ufi]);
    }

    public function __get($n)
    {
        if($n == 'url' && $this->ufi != NULL)
        {
            if($this->type == 'secure')
            {
                return url("file/secure/".$this->ufi."/".$this->name);
            }
            return $this->basePath(true) . '/' . $this->fsname;
        }
        if($n == 'path' && $this->ufi != NULL)
        {
            return $this->basePath(false) . '/' . $this->fsname;
        }
        return NULL;
    }

    public function __isset($n)
    {
        if($n == 'url' && $this->ufi != NULL)
            return true;
        if($n == 'path' && $this->ufi != NULL)
            return true;
        return false;
    }

    public function sizeClassesAllowed()
    {
        if($this->mime == 'image/jpeg' ||
           $this->mime == 'image/png'  ||
           $this->mime == 'image/gif'    )
            return true;
        return false;
    }

    public function getContent()
    {
        ob_start();
        readfile($this->basePath(false) . '/' . $this->fsname);
        return ob_get_clean();
    }

    public function serveFile($name_in_url)
    {
        global $user;
        global $sys_data;

        par_def('sc','text1ns');
        if($name_in_url == $this->name)
        {
            if(file_access($this,'view',$user) != FILE_ACCESS_ALLOW)
            {
                run_hook('file_denied',$this,$user);
                goto_loc('file/disabled_content');
            }

            if(par_ex('sc'))
            {
                $sc = par('sc');
                if(array_key_exists($sc,$sys_data->image_file_sizeclass_list) &&
                   $this->sizeClassesAllowed() &&
                   file_image_sizeclass($this,$sc) == FILE_ACCESS_ALLOW )
                {
                    header('Content-Type:'.$this->mime);
                    return $this->getContentInSizeClass($sc);
                }
            }
            header('Content-Type:'.$this->mime);
            return $this->getContent();
        }
        goto_loc('file/disabled_content');
        return '';
    }

    public function getContentInSizeClass($sc)
    {
        global $sys_data;

        $resized_img_path = $this->scCacheFilePath($sc);
        if(!file_exists($resized_img_path))
        {
            $resized_img_path = $this->scCacheFilePath($sc,true);
            image_resample($this->path, $resized_img_path,
                $sys_data->image_file_sizeclass_list[$sc][0], $sys_data->image_file_sizeclass_list[$sc][1]);
        }
        return file_get_contents($resized_img_path);
    }

    public function urlResized($sizeclass)
    {
        $url = $this->url;
        if($this->type == 'public')
        {
            global $sys_data;

            if(array_key_exists($sizeclass,$sys_data->image_file_sizeclass_list) &&
               $this->sizeClassesAllowed() &&
               file_image_sizeclass($this,$sizeclass) == FILE_ACCESS_ALLOW )
            {
                $resized_img_path = $this->scCacheFilePath($sizeclass,false);
                if(file_exists($resized_img_path))
                    return $this->scCacheFileUrl($sizeclass);

                $resized_img_path = $this->scCacheFilePath($sizeclass,true);
                image_resample($this->path,$resized_img_path,
                    $sys_data->image_file_sizeclass_list[$sizeclass][0],$sys_data->image_file_sizeclass_list[$sizeclass][1]);
                return $this->scCacheFileUrl($sizeclass);
            }
            return $url;
        }
        if($this->type == 'secure')
            return url($url,['sc' => $sizeclass]);
    }
};

function file_access(File $file,$op,$account)
{
    if(!in_array($op,['create','delete','view']))
        return FILE_ACCESS_DENY;
    $far = run_hook('file_access',$file,$op,$account);
    if(in_array(FILE_ACCESS_DENY,$far))
        return FILE_ACCESS_DENY;
    if(in_array(FILE_ACCESS_ALLOW,$far))
        return FILE_ACCESS_ALLOW;

    //Default file permissions:
    // Allows everything for admins
    if($account->role == ROLE_ADMIN)
        return FILE_ACCESS_ALLOW;
    // Allows creation for authenticated users
    if($op == 'create' && $account->auth)
        return FILE_ACCESS_ALLOW;
    // Allows to view public files (pointless to disable it)
    if($op == 'view' && $file->type == 'public')
        return FILE_ACCESS_ALLOW;
    // Allows delete/view to the owner/uploader
    if($account->auth && $account->login == $file->uploader)
        return FILE_ACCESS_ALLOW;
    return FILE_ACCESS_DENY;
}

function register_image_sizeclass($name,$w,$h)
{
    global $sys_data;
    $sys_data->image_file_sizeclass_list[$name] = [$w,$h];
}

function file_image_sizeclass(File $file,$sizeclass)
{
    $far = run_hook('file_image_sizeclass',$file,$sizeclass);
    if(in_array(FILE_ACCESS_DENY,$far))
        return FILE_ACCESS_DENY;
    if(in_array(FILE_ACCESS_ALLOW,$far))
        return FILE_ACCESS_ALLOW;
    //Default:
    return FILE_ACCESS_DENY;
}

function file_load($ufi,$disable_show_error = false)
{
    $file = new File('');
    $file->load($ufi,$disable_show_error);
    return $file;
}

function file_remove($ufi,$disable_show_error = false)
{
    $file = new File('');
    $file->load($ufi,$disable_show_error);
    $file->remove();
}

function file_get_secure()
{
    par_def('ufi','number0');
    par_def('filename','text4ns');

    $ufi = par('ufi');
    $f = new File('');
    $f->load($ufi);
    return $f->serveFile(par('filename'));
}

/** Manages after the form submitted part of a file upload procedure */
function file_create_upload($name,array $opts = [])
{
    if($_FILES[$name]['error'] == UPLOAD_ERR_OK)
    {
        if(isset($opts['filetypes']))
        {
            $req_filetypes = explode(';', $opts['filetypes']);
            if(!in_array($_FILES[$name]['type'],$req_filetypes))
            {
                load_loc('error',
                    t('The uploaded file type is "_upltype_" not in "_reqtype_". Please upload the allowed kind of file',
                        ['_upltype_' => $_FILES[$name]['type'],
                            '_reqtype_' => $opts['filetypes']]),
                    t('Form validation error'));
            }
        }

        if(in_array($_FILES[$name]['type'],
            ['image/jpeg', 'image/png', 'image/tiff', 'image/gif']
        ))
        {
            $check = getimagesize($_FILES[$name]["tmp_name"]);
            if($check === false)
                load_loc('error', t('The uploaded file type is not image file. Please upload image file'),
                    t('Form validation error'));
        }

        $f = new File(isset($opts['container']) ? $opts['container'] : 'public');
        $f->addFromTemp($_FILES[$name]['name'],
            $_FILES[$name]["tmp_name"],
            (isset($opts['subdir']) ? $opts['subdir'] : ''));
        return $f->ufi;
    }
    return NULL;
}

function file_disabled_content()
{
    ob_start();
    print '<h2>Access denied</h2>';
    print 'You are not authorized to access this file';
    return ob_get_clean();
}

function hook_file_required_sql_schema()
{
    $t = [];

    $t['file_module_file_table'] =
    [
        "tablename" => 'file',
        "columns" => [
                'ufi'       => 'SERIAL',
                'type'      => 'VARCHAR(8)',
                'name'      => 'VARCHAR(128)',
                'sub'       => 'VARCHAR(32)',
                'fsname'    => 'VARCHAR(128)',
                'mime'      => 'VARCHAR(64)',
                'uploader'  => 'VARCHAR(32)',
                'uploaded'  => sql_t('timestamp_noupd'),
        ],
    ];

    return $t;
}

function hook_file_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = ['file' => ['path' => 'sys/doc/files.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
    }
    return $docs;
}

/**
 * Controls the access to a secured file
 * @package file */
function _HOOK_file_access() {}

/**
 * Controls the size classes of a file
 * @package file */
function HOOK_file_image_sizeclass() {}

/**
 * Runs before the user will redirect to access denied page.
 * Change to display some "not found" message instead
 * @package file */
function _HOOK_file_denied() {}

/**
 * Runs when a file is uploaded and the local fs name is determined
 * This hook can change the local stored filename
 */
function _HOOK_upload_filename_alter() {}
/**
 * Runs when a file is uploaded, stored in sql
 * @package file */
function _HOOK_file_uploaded() {}

/**
 * Runs when a file is uploaded but not yet stored in sql
 * @package file */
function _HOOK_file_upload_in() {}

/**
 * Runs when a file is loaded
 * @package file */
function _HOOK_file_loaded() {}

/**
 * Runs before a file is removed
 * @package file */
function _HOOK_file_will_delete() {}
