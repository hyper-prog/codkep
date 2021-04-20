<?php
/*  CodKep - Lightweight web framework core file
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 */

define('ABOVE_LOGO',1);
define('BETWEEN_LOGO_HEADER',2);
define('BELOW_HEADER',3);
define('NOT_RENDERED',9);

function hook_flex_boot()
{
    global $flex;
    $flex = new stdClass();

    $flex->mainmenu_pos = BETWEEN_LOGO_HEADER;
    $flex->sidebar_blocks_have_border = false;
    $flex->disable_builtin_flexcss = false;
    $flex->disable_builtin_colorcss = false;
    $flex->allow_empty_footer = false;
    $flex->disable_logo_link = false;

    $flex->size_of_left_sidebar_desktop = 4;
    $flex->size_of_centerarea_desktop = 11;
    $flex->size_of_right_sidebar_desktop = 5;
    $flex->size_of_left_sidebar_mobile = 4;
    $flex->size_of_centerarea_mobile = 11;
    $flex->size_of_right_sidebar_mobile = 20;

    $flex->mainmenu_structure_prefix = '';
    $flex->mainmenu_structure_suffix = '';

    $flex->logoimage_parallax_scrolling = false;
    $flex->logoimage_parallax_height = 200;
    $flex->mainmenu_stay_fixed_scrolldown = 0;
}

function hook_flex_theme()
{
    $items = [];
    $items['flex'] = [
                "pageparts" => [
                    "before_page",
                    "first_in_page",
                    "header",
                    "sidebar_left",
                    "sidebar_right",
                    "pagetop",
                    "highlighted",
                    "footer",
                ],
                "generators" => [
                    "runonce"   => "flex_runonce",
                    "htmlstart" => "flex_htmlstart",
                    "htmlend"   => "flex_htmlend",
                    "body"      => "flex_body",
                ],
        ];
    return $items;
}

function flex_runonce($content)
{
    global $flex;
    header('Content-Type: text/html; charset=utf-8');

    add_header('<meta http-equiv="Content-Type" content="Text/Html;Charset=UTF-8" />'."\n");
    add_header('<meta name="viewport" content="width=device-width, initial-scale=1.0" />'."\n");
    add_header('<meta http-equiv="Cache-Control" content="no-cache" />'."\n");
    add_header('<meta http-equiv="Pragma" content="no-cache" />'."\n");

    if(!$flex->disable_builtin_flexcss)
        add_css_file('/sys/flex.css');
    if(!$flex->disable_builtin_colorcss)
        add_css_file('/sys/flex_colors.css');

    run_hook("flex_runonce"); //Possibility to add custom css and other stuff
}

function flex_htmlstart($route)
{
    ob_start();
    print "<!DOCTYPE html>\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n";
    return ob_get_clean();
}

function flex_htmlend($route)
{
    ob_start();
    print "</html>\n";
    return ob_get_clean();
}

function flex_body($content,$route)
{
    global $site_config;
    global $flex;

    $startpage_rawurl = url($site_config->startpage_location);
    $home_text = t('Home');

    $flex->body_classes = "center ";
    $flex->sidebar_classes = "column sidebar ";

    if($flex->sidebar_blocks_have_border)
        $flex->sidebar_classes .= "sidebar-with-border ";

    if(current_loc() == $site_config->startpage_location)
        $flex->body_classes .= "front ";

    //This body_classes markers does not needs for css thus we using responsive design, leaved it for other uses
    if($content->pageparts['sidebar_left'] != '' && $content->pageparts['sidebar_right'] != '')
    {
        $flex->body_classes .= "two-sidebars ";
    }
    else if($content->pageparts['sidebar_left'] != '' || $content->pageparts['sidebar_right'] != '')
    {
        $flex->body_classes .= "one-sidebar ";
        if($content->pageparts['sidebar_left'] != '')
        {
            $flex->size_of_centerarea_desktop += $flex->size_of_right_sidebar_desktop;
            $flex->size_of_centerarea_mobile += $flex->size_of_right_sidebar_mobile;
        }
        if($content->pageparts['sidebar_right'] != '')
        {
            $flex->size_of_centerarea_desktop += $flex->size_of_left_sidebar_desktop;
            $flex->size_of_centerarea_mobile += $flex->size_of_left_sidebar_mobile;
        }
    }
    else
    {
        $flex->body_classes .= "no-sidebars ";
        $flex->size_of_centerarea_desktop += $flex->size_of_left_sidebar_desktop;
        $flex->size_of_centerarea_desktop += $flex->size_of_right_sidebar_desktop;
        $flex->size_of_centerarea_mobile += $flex->size_of_left_sidebar_mobile;
        $flex->size_of_centerarea_mobile += $flex->size_of_right_sidebar_mobile;
    }

    ob_start();
    print "<body class=\"$flex->body_classes\">\n";
    print $content->pageparts['before_page'];
    print "<div id=\"page\" class=\"pagebgcolor center\">\n";
    print $content->pageparts['first_in_page'];

    if($flex->mainmenu_pos == ABOVE_LOGO)
        print flex_show_mainmenu();
    if($site_config->logo_img_url != NULL)
    {
        $logo_image_rawurl = url($site_config->logo_img_url);
        print " <div id=\"thelogoarea\" class=\"flex-layout-row center logobgcolor\">\n";
        if(!$flex->disable_logo_link)
            print "  <a href=\"$startpage_rawurl\" title=\"$home_text\" rel=\"home\" id=\"logo\">\n";
        print "   <div id=\"thelogoimage\" class=\"".($flex->logoimage_parallax_scrolling ? "parallaxlogo" : "")."\">\n";
        if(!$flex->logoimage_parallax_scrolling)
            print "    <img src=\"$logo_image_rawurl\" alt=\"$home_text\"/>\n";
        print "   </div> <!-- #thelogoimage -->\n";
        if(!$flex->disable_logo_link)
            print "  </a>\n";
        print " </div> <!-- #thelogoarea -->\n";

        if($flex->logoimage_parallax_scrolling)
            add_style("#thelogoimage {
                background-image: url(\"$logo_image_rawurl\");
                height: ".$flex->logoimage_parallax_height."px; }");
    }

    if($flex->mainmenu_pos == BETWEEN_LOGO_HEADER)
        print flex_show_mainmenu();

    print " <div id=\"header\" class=\"flex-layout-row headerbgcolor\">\n";
    print "  <div class=\"section c\">\n";

    if($site_config->site_name != NULL || $site_config->site_slogan != NULL)
    {
        print "   <div id=\"name-and-slogan\">\n";
        if($site_config->site_name != NULL)
        {
            print "    <div id=\"site-name\">\n";
            print "     <strong>\n";
            print "      <a href=\"$startpage_rawurl\" title=\"$home_text\" rel=\"home\">\n";
            print "       <span>".$site_config->site_name."</span>\n";
            print "      </a>\n";
            print "     </strong>\n";
            print "    </div>\n";
        }
        if($site_config->site_slogan != NULL)
        {
            print "    <div id=\"site-slogan\">\n";
            print "     ".$site_config->site_slogan."\n";
            print "    </div>\n";
        }
        print "   </div> <!-- #name-and-slogan --> \n";
    }

    print $content->pageparts['header'];

    print "  </div> <!-- .section -->\n";
    print " </div> <!-- #header -->\n";

    if($flex->mainmenu_pos == BELOW_HEADER)
        print flex_show_mainmenu();

    print " <div id=\"mainarea\" class=\"pagebgcolor c\">\n";

    print "  <div id=\"page-top\" class=\"flex-layout-row page-top-area\">\n";
    print "   <div class=\"section\">\n";
    print $content->pageparts['pagetop'];
    print "   </div> <!-- .section -->\n";
    print "  </div> <!-- #page-top -->\n";

    print "  <div id=\"sliderblocks\" class=\"flex-layout-row\">";

    if($content->pageparts['sidebar_left'] != '')
    {
        print "   <div id=\"sidebar-left\" class=\"col-".$flex->size_of_left_sidebar_desktop.
               " col-m-".$flex->size_of_left_sidebar_mobile." $flex->sidebar_classes\">\n";
        print "    <div class=\"section\">\n";
        print $content->pageparts['sidebar_left'];
        print "    </div> <!-- .section -->\n";
        print "   </div> <!-- #sidebar-left -->\n";
    }

    print "   <div id=\"maincontent\" class=\"col-".$flex->size_of_centerarea_desktop.
           " col-m-".$flex->size_of_centerarea_mobile." column\">\n";
    print "    <div class=\"section\">\n"; //SecMainC-b
    if($content->pageparts['highlighted'] != '')
    {
        print "     <div id=\"highlighted\">\n";
        print $content->pageparts['highlighted'];
        print "     </div> <!-- #highlighted -->\n";
    }

    print "     <a id=\"main-content-pos\"></a>\n";
    print "     <div id=\"flex-main-content-html\" class=\"content\">\n";
    print $content->generated;
    print "     </div> <!-- .content --> \n";
    print "     <div id=\"dialog_placeholder\"></div>";

    print "    </div> <!-- .section -->\n"; //SecMainC-e
    print "    <div class=\"c\"></div>\n";
    print "   </div> <!-- #maincontent -->\n";

    if($content->pageparts['sidebar_right'] != '')
    {
        print "   <div id=\"sidebar-right\" class=\"col-".$flex->size_of_right_sidebar_desktop.
               " col-m-".$flex->size_of_right_sidebar_mobile." $flex->sidebar_classes\">\n";
        print "    <div class=\"section\">\n";
        print $content->pageparts['sidebar_right'];
        print "    </div> <!-- .section -->\n";
        print "   </div> <!-- #sidebar-right -->\n";
    }

    print "  </div> <!-- #sliderblocks -->"; 

    print " </div> <!-- #mainarea -->\n";

    if($content->pageparts['footer'] != '' || $flex->allow_empty_footer)
    {
        print " <div id=\"footer\" class=\"flex-layout-row footerbgcolor\">\n";
        print "  <div class=\"section\">\n";
        print $content->pageparts['footer'];
        print "  </div> <!-- .section -->\n";
        print "  <div class=\"c\"></div>\n";
        print " </div> <!-- #footer -->\n";
    }

    print "</div> <!-- /#page,  -->\n";

    if($flex->mainmenu_stay_fixed_scrolldown > 0)
    {
        print "<script>
        jQuery(window).bind('scroll', function () {
          if(jQuery(window).scrollTop() > ".$flex->mainmenu_stay_fixed_scrolldown.") {
            jQuery('#flex-mainmenu').addClass('position-fixed');
          } else {
            jQuery('#flex-mainmenu').removeClass('position-fixed');
          }
        });
        </script>";
    }

    print "</body>\n";
    return ob_get_clean();
}

function flex_show_mainmenu()
{
    global $flex;

    ob_start();
    print "<div id=\"mainmenu-show-area\" class=\"mmenubgcolor_out pageout\">\n";
    print " <div id=\"flex-mainmenu\" class=\"mmenubgcolor\">\n";
    print $flex->mainmenu_structure_prefix;
    print generate_menu_structure('  ');
    print $flex->mainmenu_structure_suffix;
    print "  <div class=\"c\"></div>\n";
    print " </div>\n";

    print "</div>\n";
    return ob_get_clean();
}

/** This hook is ivoked by the flex theme every time before the content generation. */
function _HOOK_flex_runonce() {}

//end.