<?php
/*  CodKep - Lightweight web framework core file
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 *
 * Doc module
 */

function hook_doc_boot()
{
    global $site_config;
    $site_config->disable_system_doc_target = false;
    $site_config->base_theme_for_doc_target = true;
}

function hook_doc_defineroute()
{
    global $site_config;
    $route = [];
    if($site_config->disable_system_doc_target)
        return $route;

    $rm = [
        'title' => "CodKep documentaion",
        'path' => "doc/{section}",
        'callback' => 'codkep_doc_callback',
        'parameters' => ['part'    => ['security' => 'text2ns','default' => '__index__'],
                         'section' => ['security' => 'text2ns','default' => 'codkep']],
    ];

    $rs = [
        'title' => "CodKep documentaion",
        'path' => "doc/{section}/{part}",
        'callback' => 'codkep_doc_callback',
        'parameters' => ['part'    => ['security' => 'text2ns','default' => 'index'],
        'section' => ['security' => 'text2ns','default' => 'codkep']],
    ];

    if($site_config->base_theme_for_doc_target)
    {
        $rm['theme'] = "base_page";
        $rs['theme'] = "base_page";
    }

    $route[] = $rm;
    $route[] = $rs;

    return $route;
}

function codkep_doc_callback()
{
    add_css_file("/sys/doc/docstyle.css");
    add_css_file("/sys/doc/default.css");
    add_js_file("/sys/doc/highlight.pack.js");

    $section = par('section');
    $doc_to_show = par('part');

    $docbase = run_hook('documentation',$section);

    $contents = [];
    $title_is_set = false;

    ob_start();
    foreach($docbase as $doc)
        foreach($doc as $name => $values)
        {
            $raw = file_get_contents($values['path']);
            if($doc_to_show == $name || ($doc_to_show == '__index__' && $values['index']) || $doc_to_show == 'full')
            {
                print "<div class=\"ccdoccontent\">";
                print mmark_to_html($raw,$name,$contents,$values['imagepath']);
                print "</div>";
                if(!$title_is_set && isset($contents[$name]['pos_1']))
                {
                    set_title($contents[$name]['pos_1']);
                    $title_is_set = true;
                }
            }
            else
            {
                mmark_to_html($raw,$name,$contents,$values['imagepath'],true);
            }
        }

    print "<script>hljs.initHighlightingOnLoad();</script>";
    print "<div class=\"ccdocpanel\">";

    print "<ul class=\"docsidemenu no-print\">";
    foreach($contents as $fname => $fcont)
    {
        $closed = "";
        print "<li>".l(isset($fcont['pos_1']) ? $fcont['pos_1'] : $fname,"doc/$section/$fname");
        if("doc/$section/$fname" != current_loc())
            $closed = " closed";
        print "<ul class=\"$closed\">";
        foreach($fcont as $anc => $name)
        {
            if(isset($fcont['pos_1']) && $anc == 'pos_1')
                continue;
            print "<li>".l($name,"doc/$section/$fname#$anc")."</li>";
        }
        print "</ul></li>";

    }
    print "</ul>";
    print "</div>";
    return ob_get_clean();
}

function mmark_to_html($input,$name,&$contents,$imagepath = '',$scan_content_only = false)
{
    $state = [
        'nextheading' => 1,
        'content' => [],
        'imagepath' => $imagepath,
        'stack_tag' => [],
        'stack_cr'  => [],
        'stack_level'  => [],
        'scanonly' => false,
    ];

    $separator = "\r\n";
    $line = strtok($input, $separator);

    $state['scanonly'] = $scan_content_only;
    ob_start();
    while ($line !== false)
    {
        mmint_process_line($line,$state);
        $line = strtok($separator);
    }
    mmint_process_line('.',$state);
    $contents[$name] = $state['content'];
    popAllTagStack($state,0);
    return ob_get_clean();
}


function pushTagStack($tag,$cr,$level,&$state,$attributes = '')
{
    $state['stack_tag'][] = $tag;
    $state['stack_cr'][] = $cr;
    $state['stack_level'][] = $level;
    if(!$state['scanonly'])
        print '<'.$tag.( $attributes == '' ? '' : ' '.$attributes ).'>';
}

function popTagStack(&$state)
{
    if(!$state['scanonly'])
        print '</'.end($state['stack_tag']) . ">";
    array_pop($state['stack_tag']);
    array_pop($state['stack_cr']);
    array_pop($state['stack_level']);
}

function popAllTagStack(&$state,$until_cr = 0)
{
    while(count($state['stack_tag']) > 0 && end($state['stack_cr']) >= $until_cr)
        popTagStack($state);
}

function topTagStack($type,&$state)
{
    if(count($state['stack_tag']) == 0)
    {
        if($type == 'tag')
            return '';
        if($type == 'cr')
            return -1;
        if($type == 'level')
            return -1;
    }

    if($type == 'tag')
        return end($state['stack_tag']);
    if($type == 'cr')
        return end($state['stack_cr']);
    if($type == 'level')
        return end($state['stack_level']);
    return NULL;
}

function existsTagStack($name,&$state)
{
    foreach($state['stack_tag'] as $t)
        if($t == $name)
            return true;
    return false;
}

//state-t kellene megcsinalni
function mmint_process_line($i,&$state)
{
    $m = [];
    if(substr($i,0,1) == '=' && topTagStack('cr',$state) < 2)
        if(preg_match("/([=]{1,4})\\s+([^=]*)\\s+[=]{1,4}/",$i,$m) == 1)
        {
            $tag = '';
            $ecount = strlen($m[1]);
            if(!$state['scanonly'])
            {
                popAllTagStack($state,0);
                if($ecount == 1) $tag = 'h1';
                if($ecount == 2) $tag = 'h2';
                if($ecount == 3) $tag = 'h3';
                if($ecount == 4) $tag = 'h3';
                print "<a name=\"pos_".$state['nextheading']."\"><$tag>" . $m[2] . "</$tag></a>\n";
            }
            if($ecount < 4)
            {
                $state['content']['pos_' . $state['nextheading']] = $m[2];
                $state['nextheading']++;
            }
            return;
        }

    // CODE stuff
    if(preg_match("/^~~~~*([a-z]+)$/",$i,$m) == 1 && topTagStack('tag',$state) != 'code')
    {
        popAllTagStack($state,0);
        pushTagStack('pre',2,-1,$state);
        pushTagStack('code',2,-1,$state,"class=\"$m[1]\"");
        return;
    }
    if(preg_match("/^~~~~*$/",$i) == 1 && topTagStack('tag',$state) == 'code')
    {
        popAllTagStack($state,0);
        return;
    }
    if(topTagStack('tag',$state) == 'code')
    {
        if(trim($i) == '.')
        {
            print "\n";
            return;
        }
        $i = preg_replace("/\\</","&lt;",$i);
        $i = preg_replace("/\\>/","&gt;",$i);
        print "$i\n";
        return;
    }

    if($state['scanonly'])
        return;

    // UL OL stuff
    if(preg_match("/^(\\s*)([\\-\\#\\\\])\\s/",$i,$m) == 1)
    {
        $spc = strlen($m[1]);
        while(!in_array(topTagStack('tag',$state),['','ul','ol','li']))
            popTagStack($state);

        if(($m[2] == '-' && topTagStack('tag',$state) != 'ul' && topTagStack('level',$state) < $spc) ||
           ($m[2] == '#' && topTagStack('tag',$state) != 'ol' && topTagStack('level',$state) < $spc) ||
            topTagStack('level',$state) < $spc)
        {
            print "\n";
            if($m[2] == '-')
                pushTagStack('ul',1,$spc,$state);
            if($m[2] == '#')
                pushTagStack('ol',1,$spc,$state);
        }

        while(topTagStack('level',$state) != $spc && in_array(topTagStack('tag',$state),['ul','ol','li']))
            popTagStack($state);

        if($m[2] != '\\')
        {
            if (topTagStack('tag', $state) == 'li')
                popTagStack($state);
            pushTagStack('li', 1, $spc, $state);
        }
        else
            print ' ';

        print mmint_process_linestring(substr($i, $spc + 2),$state);
        return;
    }
    else
    {
        while(in_array(topTagStack('tag',$state),['ul','ol','li']))
            popTagStack($state);
    }

    // TABLE stuff
    if(preg_match("/^\\|(.+)\\|\\s*$/",$i,$m) == 1)
    {
        $mline = $m[1];
        if(!existsTagStack('table',$state))
        {
            popAllTagStack($state,0);
            print "\n";
            pushTagStack('table',1,-1,$state);
            pushTagStack('thead',1,-1,$state);
        }
        if(preg_match("/^\\|[\\s\\-\\|]*\\-\\-\\-[\\s\\-\\|]*$/",$i,$m) == 1)
        {
            if(topTagStack('tag',$state) == 'thead')
                popTagStack($state);
            print "\n";
            pushTagStack('tbody',1,-1,$state);
            return;
        }
        $cells = explodeNotIn("|",$mline, [ '['=>']','{'=>'}'  ]);

        print "\n<tr>";
        foreach($cells as $cell)
        {
            $style = "";
            $cellsub = extractStyleFromCellSpec($cell,$style);
            $tcell = trim(mmint_process_linestring($cellsub,$state));
            if(topTagStack('tag',$state) == 'thead')
                print "<th".($style == ""?"":" style=\"$style\"").">" . $tcell . "</th>";
            if(topTagStack('tag',$state) == 'tbody')
                print "<td".($style == ""?"":" style=\"$style\"").">" . $tcell . "</td>";
        }
        print "</tr>";
        return;
    }
    else
    {
        while(in_array(topTagStack('tag',$state),['table','thead','tbody']))
            popTagStack($state);
    }

    if(trim($i) == '.')
    {
        if(topTagStack('tag',$state) == 'p')
            print "</p>\n<p>";
        return;
    }

    if(strlen(trim($i)) > 0 && topTagStack('tag',$state) != 'p')
        pushTagStack('p',0,-1,$state);

    if(substr($i,0,1) == '#')
        if(preg_match("/^#([^#]+)#$/",$i,$m) == 1)
        {
            print "<a name=\"".$m[1]."\"></a>\n";
            return;
        }

    if(substr($i,0,1) == '-')
        if(preg_match("/^----*$/",$i) == 1)
        {
            print "<hr/>\n";
            return;
        }

    print mmint_process_linestring($i,$state) . "\n";
}

function mmint_process_linestring($i,&$state)
{
    $i = preg_replace("/(\\b)___([^_]+)___(\\b)/",'$1<code>$2</code>$3',$i);
    $i = preg_replace("/(\\b)__([^_]+)__(\\b)/",'$1<strong>$2</strong>$3',$i);
    $i = preg_replace("/(\\b)_([^_]+)_(\\b)/",'$1<em>$2</em>$3',$i);

    $i = preg_replace("/\\@\\@\\@([^\\@]+)\\@\\@\\@/",'<span class="definition">$1</span>',$i);

    $i = preg_replace("/\\*\\*\\*([^\\*]+)\\*\\*\\*/",'<code>$1</code>',$i);
    $i = preg_replace("/\\*\\*([^\\*]+)\\*\\*/",'<strong>$1</strong>',$i);
    $i = preg_replace("/\\*([^\\*]+)\\*/",'<em>$1</em>',$i);

    $mm = [];
    $m_sub = [];
    $orig_i = $i;

    if(preg_match_all("/\\{([^\\{\\}]+)\\}/",$orig_i,$mm,PREG_SET_ORDER) > 0)
    {
        foreach($mm as $m)
        {
            $parts = explode("|", $m[1]);
            $src = '';
            $add = '';
            $style = '';
            $alt = '';
            foreach($parts as $part)
            {
                if(substr($part, 0, 5) == "file:")
                {
                    $src = $state['imagepath'] . '/' . substr($part, 5);
                    if($alt == '')
                        $alt = substr($part, 5);
                }
                if(substr($part, 0, 4) == "url:")
                {
                    $src = substr($part, 4);
                    if($alt == '')
                        $alt = substr($part, 4);
                }
                if(substr($part, 0, 6) == "width:")
                    $style .= 'width:' . substr($part, 6) . 'px; ';
                if(substr($part, 0, 7) == "height:")
                    $style .= 'height:' . substr($part, 7) . 'px; ';
                if(substr($part, 0, 4) == "css:")
                    $style .= substr($part, 4) . ' ';
                if(substr($part, 0, 6) == "title:")
                    $add .= 'title="' . substr($part, 6) . '" ';
                if(substr($part, 0, 4) == "alt:")
                    $alt = substr($part, 4);
            }
            if($src != '')
            {
                $imglink = "<img src=\"" . url($src) . "\" $add style=\"$style\" alt=\"$alt\"/>";
                $i = str_replace("{" . $m[1] . "}", $imglink, $i);
            }
        }
    }

    if(preg_match_all("/\\[+([^\\[\\]]+)\\]+/",$orig_i,$mm,PREG_SET_ORDER) > 0)
    {
        foreach($mm as $m)
        {
            $parts = explode("|", $m[1]);
            $txt = '';
            $url = '';
            $add = ' ';
            $style = '';
            foreach($parts as $part)
            {
                if(strpos($part, ':') === FALSE)
                    $txt = $part;
                if(substr($part, 0, 4) == "url:")
                    $url = substr($part, 4);
                if(substr($part, 0, 4) == "css:")
                    $style .= substr($part, 4) . ' ';
                if(substr($part, 0, 6) == "title:")
                    $add .= 'title="' . substr($part, 6) . '" ';
                if(substr($part, 0, 8) == "add_par:")
                    $add .= substr($part, 8) . ' ';
            }
            if($url != '')
            {
                $link = "<a href=\"" . url($url) . "\"";
                if($add != ' ')
                    $link .= $add;
                if($style != '')
                    $link = "style=\"$style\"";
                $link .= ">$txt</a>";
                $i = str_replace("[" . $m[1] . "]", $link, $i);
            }
        }
    }
    return $i;
}

function extractStyleFromCellSpec($line,&$style)
{
    if(strlen($line) < 1 || $line[0] != '(')
    {
        $style = '';
        return $line;
    }

    if(preg_match("/^\\(([^\\(\\)]+)\\)(.*)$/",$line,$m) == 1)
    {
        $style = $m[1];
        return $m[2];
    }

    $style = '';
    return $line;
}

function explodeNotIn($sep,$string,$start_end)
{
    $ret_array = [];
    $len = strlen($string);
    $spos = 0;
    $in_deep = 0;
    $closer = [];
    for($i=0 ; $i<$len ; ++$i)
    {
        if(array_key_exists($string[$i],$start_end))
        {
            $in_deep++;
            array_push($closer,$start_end[$string[$i]]);
            //go forward until the first is closed or the string is ended
            for($inext = $i+1 ; $inext<$len && $in_deep > 0 ; ++$inext)
            {
                if(array_key_exists($string[$inext],$start_end))
                {
                    $in_deep++;
                    array_push($closer,$start_end[$string[$i]]);
                }
                if($string[$inext] == end($closer))
                {
                    $in_deep--;
                    array_pop($closer);
                }
                if($in_deep == 0)
                    $i = $inext;
            }
            $closer = [];
            $in_deep = 0;
            continue;
        }
        //found a part
        if($in_deep == 0 && $string[$i] == $sep)
        {
            if($i == 0)
            {
                $spos = $i+1;
                continue;
            }
            $ret_array[] = substr($string,$spos,$i-$spos);
            $spos = $i+1;
        }
    }
    if($spos < $i)
        $ret_array[] = substr($string,$spos);
    return $ret_array;
}

function hook_doc_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = ['doc' => ['path' => 'sys/doc/doc.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
    }
    return $docs;
}

//end.
