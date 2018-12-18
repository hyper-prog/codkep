<?php
/* CodKep sample module */

function hook_example_base_defineroute()
{
    $r = [];
    $r[] = ['path' => 'start',
            'callback' => 'page_start',
            '#menu' => 'Start', ];

    $r[] = ['path' => 'sub/one',
            'callback' => 'one_page' ];

    $r[] = ['path' => 'sub/tables',
            'title' => 'Base tables',
            'callback' => 'page_tables',
            '#menu' => 'BaseTables' ];

    $r[] = ['path' => 'subword/{word}',
            'callback' => 'word_page',
            'parameters' => ['word' => 'text4']];

    $r[] = ['path' => 'bravo',
            'file' => 'site/bravo.php',
            '#menu' => 'ExternalFile' ];

    return $r;
}

function hook_example_base_sitearea_header()
{
    $r = [];
    $r[] = ['name' => 'site/bravo.php',
            'index' => 1,
            'callback' => 'headerfnc',
           ];
    return $r;
}

function page_start()
{
    set_title('Small exmaple page');
    ob_start();
    print "<h2>Hello this is a start page</h2>";
    print h('table')
            ->cell(l('Simple page 1','sub/one'))
            ->nrow()
            ->cell(l('SpeedForm example','example/speedform'))
            ->get();

    foreach(explode(' ','how much is the fish') as $w)
        print l('Page',"subword/$w").' /  ';
    return ob_get_clean();
}

function one_page()
{
    return 'This is one page';
}

function page_tables()
{
    ob_start();
    print '<p>This is the second page</p>';

    print '<p>Table (build type one)</p>';
    $t = h('table')->opts(['border'=>'1','style'=>'background-color: white; border-collapse: collapse;']);
    $t->heads(['Item','Count','Place','Color'],['class' => 'headcell']);
    $t->cellss([['Banana','21','Shelf','Yellow'],
               ['Apple','15','Box','Red'],
               ['Orange','30','Bag','Orange']]);
    print $t->get();

    print '<p>Table (build type two)</p>';
    $t = h('table')->opts(['border'=>'1','style'=>'background-color: white; border-collapse: collapse;']);
    $t->heads(['Item','Count','Place','Color'],['class' => 'headcell']);
    $t->cells(['Banana','21','Shelf','Yellow']);
    $t->nrow();
    $t->cells(['Apple','15','Box','Red'],['style'=>'background-color: yellow;']);
    $t->nrow();
    $t->cell('Orange');
    $t->cell('30');
    $t->cell('Bag',['style'=>'background-color: #ff9900;']);
    $t->cell('Orange');
    print $t->get();
    add_style('.headcell { background-color: #333333; color: white; }');
    return ob_get_clean();
}

function word_page()
{
    return 'This is a subpage, which got a parameter word: "'.par('word') . '"';
}

function headerfnc()
{
    return 'This is a header text';
}