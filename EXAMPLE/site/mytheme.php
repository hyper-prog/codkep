<?php
/* CodKep sample module */

function hook_mytheme_theme()
{
    $items = array();
    $items['mytheme'] = [
                'pageparts' => [
                    'header',
                    'footer',
                ],
                'generators' => [
                    "runonce"   => "myt_runonce",
                    "htmlstart" => "myt_htmlstart",
                    "htmlend"   => "myt_htmlend",
                    "body"      => "myt_body",
                ],
            ];
    return $items;
}

function myt_runonce($content)
{
    header('Content-Type: text/html; charset=utf-8');
    add_header('<META HTTP-EQUIV="Content-Type" CONTENT="Text/Html;Charset=UTF-8">'."\n");
}

function myt_htmlstart($route)
{
    return '<!DOCTYPE html>'."\n" . '<html xmlns="http://www.w3.org/1999/xhtml" lang="hu-HU">'."\n";
}

function myt_htmlend($route)
{
    return "</html>\n";
}

function myt_body($content,$route)
{
    ob_start();
    print "<body>\n";
    add_style('div.page { background-color: #aaaaaa; padding: 5px; }');
    add_style('div.header { background-color: #888888; padding: 5px; }');
    print "<div class=\"page\">\n";
    print "<div class=\"header\">\n";
    print $content->pageparts['header'];
    print "</div>\n";

    print mymenu();
    print "<div class=\"content\">\n";
    
    print $content->generated;
    print "</div>\n"; // content

    print "<div class=\"footer\">\n";
    print $content->pageparts['footer'];
    print "</div>\n";
    print "</div>\n";

    print "</body>\n";
    return ob_get_clean();
}

function mymenu()
{
    ob_start();
    print '<div class="mmdiv">';
    print '<ul class="mymenu">';
    foreach(routes_tag_array('menu') as $name => $url)
        print '<li>'.l($name,$url).'</li>';
    print '<div style="clear:both"></div>';
    print '</ul>';
    print '</div>';
    print '<div style="clear:both"></div>';
    add_style('.mmdiv { display: block; width:100%; background-color: #aaaaee; } ');
    add_style('.mymenu { display: block; margin: 0px; padding: 0px; } ');
    add_style('.mymenu li { display: block;  margin: 0px; padding: 0px; float: left; } ');
    add_style('.mymenu li a { display:block; text-decoration: none; color: black; padding: 5px 15px 5px 15px; background-color: #aaaaee; } ');
    add_style('.mymenu li a:hover { background-color: #8888ff; } ');
    return ob_get_clean();
}