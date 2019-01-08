<?php
global $site_config;

$site_config->base_path = '';
$site_config->clean_urls = true;

global $db;

$db->servertype  = "mysql";
$db->host        = "ckdatabase";
$db->name        = "codkep";
$db->user        = "root";
$db->password    = "codkepapptest";
$db->sqlencoding = "utf8";

$db->schema_editor_password = "secret";
