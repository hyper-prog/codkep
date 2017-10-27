<?php

global $site_config;

$site_config->base_path           = '';
$site_config->startpage_location  = 'start';
$site_config->default_theme_name  = 'mytheme';

$site_config->show_generation_time  = true;

//$site_config->show_sql_commands_executed = true;

//Enable developer tools
$site_config->enable_hook_table_info = true;
$site_config->enable_speeformbuilder_preview = true;
$site_config->enable_speeformbuilder = true;

global $db;

$db->servertype = "none";
$db->host = "127.0.0.1";
$db->name = "web";
$db->user = "root";
$db->password = "root";
$db->schema_editor_password = ""; //empty means disabled!
$db->schema_editor_allowed_for_admin = true;
