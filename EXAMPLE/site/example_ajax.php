<?php
/* CodKep sample module */

function hook_example_ajax_defineroute()
{
    $r = array();
    $r[] = ['path' => 'example/ajax',
            'callback' => 'page_ajaxexample',
            '#menu' => 'Ajax',
        ];

    $r[] = ['path' => 'ajaxhandle/{direction}/{value}',
            'callback' => 'ajax_handler',
            'type' => 'ajax', // tells the system to handle this as an ajax handler
        ];

    return $r;
}

function page_ajaxexample()
{
    ob_start();
    print "<h2>Simple ajax exmaple</h2>";
    print "Page is loaded:".time();
    print '<br/>';
    print '<div id="change_this">'.counter_inner(55).'</div>';
    return ob_get_clean();
}

function counter_inner($value)
{
    ob_start();
    print l('Up',"ajaxhandle/up/$value",['class' => 'use-ajax']);
    print '<br/>';
    print "The value is $value";
    print '<br/>';
    print l('Down',"ajaxhandle/down/$value",['class' => 'use-ajax']);
    return ob_get_clean();
}

function ajax_handler()
{
    par_def('direction','text4'  ,'url');
    par_def('value'    ,'number0','url');

    $value = par('value');
    if(par_is('direction','up'))
        ++$value;
    if(par_is('direction','down'))
        --$value;

    ajax_add_html('#change_this',counter_inner($value));
}