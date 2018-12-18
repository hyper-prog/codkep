<?php
/* CodKep sample module */

function hook_example_tables_defineroute()
{
    $r = [];
    $r[] = ['path' => 'querytables',
            'callback' => 'page_querytables',
            '#menu' => 'QueryTables',
    ];

    return $r;
}

function page_querytables()
{
    ob_start();
    $data = fake_query_result();

    print "<h1>to_table(\$queryresult,\$control_array) function examples</h1>";
    print "This tables can directly generated from an sql query with the control array below<br/>";

    //Example - 1
    print "<h3>1 - Table without any modifier</h3>";
    print to_table($data);

    //Example - 2
    $c = [
        '#tableopts' => [
            'border' => '1',
            'style' => 'background-color: white; border-collapse: collapse;']
    ];
    print "<h3>2 - Table with border and color modifier</h3>";
    print to_table($data,$c);

    //Example - 3
    $c = [
        '#tableopts' => [
            'border' => '1',
            'style' => 'background-color: white; border-collapse: collapse;'],
        '#fields' => ['city','name'],
    ];
    print "<h3>3 - Table with redefined fields</h3>";
    print to_table($data,$c);

    //Example - 4
    $c = [
        '#tableopts' => [
            'border' => '1',
            'style' => 'background-color: white; border-collapse: collapse;'],
        '#fields' => ['name','city'],
        'name' => [
            'headertext' => 'The name',
            'headeropts' => ['style' => 'background-color: yellow;'],
        ],
        'city' => [
            'headertext' => 'Works in',
            'headeropts' => ['style' => 'background-color: brown;'],
            'cellprefix' => '<i>',
            'cellsuffix' => '</i>',
            'cellopts' => ['style' => 'background-color: lightgrey;'],
        ],
    ];
    print "<h3>4 - Table with styled columns & headers</h3>";
    print to_table($data,$c);

    //Example - 5
    $c = [
        '#tableopts' => [
            'border' => '1',
            'style' => 'background-color: white; border-collapse: collapse;'],
        '#fields' => ['name','city'],
        'name' => [
            'headertext' => 'The name',
            'headeropts' => ['style' => 'background-color: yellow;'],
            'valuecallback' => function($r) {
                return l($r['name'],'useredit',[],['id' => $r['pid']]);
            },
        ],
    ];
    print "<h3>5 - Table with callback function</h3>";
    print to_table($data,$c);

    //Example - 6
    $c = [
        '#tableopts' => [
            'border' => '1',
            'style' => 'background-color: white; border-collapse: collapse;'],
        '#fields' => ['#name_decorated','#city_decorated'],
    ];
    print "<h3>6 - Table with redefined fields used from field repository (defined by hook_MODULE_field_repository())</h3>";
    print to_table($data,$c);

    //Example - 7
    $data = fake_query_result2();
    print "<h3>7 - Table without control array, field repository used directly from sql</h3>";
    print to_table($data);

    //Example - 8
    $c = [
        '#tableopts' => [
            'border' => '1',
            'style' => 'background-color: white; border-collapse: collapse;'],
        '#fields' => ['#city_decorated','#name_decorated'],
    ];
    print "<h3>8 - Specified from sql side, added some style and order from code (Combined)</h3>";
    print to_table($data,$c);

    return ob_get_clean();
}

function hook_example_tables_field_repository()
{
    $c = [
        'pidfield' => [
            'skip' => true,
            'sqlname' => 'pid',
        ],
        'name_decorated' => [
            'headertext' => 'The name',
            'headeropts' => ['style' => 'background-color: lightgreen;'],
            'sqlname' => 'name',
            'cellopts' => ['style' => 'background-color: white;'],
            'valuecallback' => function($r) {
                return l($r['name'],'useredit',[],['id' => $r['pid']]);
            },
        ],
        'city_decorated' => [
            'headertext' => 'Works in',
            'headeropts' => ['style' => 'background-color: lightblue;'],
            'cellprefix' => '<i>',
            'cellsuffix' => '</i>',
            'cellopts' => ['style' => 'background-color: lightgrey;'],
            'sqlname' => 'city',
        ],
    ];
    return $c;
}

function fake_query_result()
{
    return [
        ['pid'=>'01',   'name'=>'Adam',     'city'=>'Washington'    ],
        ['pid'=>'02',   'name'=>'Eve',      'city'=>'Miami'         ],
        ['pid'=>'03',   'name'=>'Frank',    'city'=>'Detroit'       ],
        ['pid'=>'04',   'name'=>'Paul',     'city'=>'London'        ],
        ['pid'=>'05',   'name'=>'Alex',     'city'=>'Paris'         ],
    ];
}

function fake_query_result2()
{
    return [
        ['#pidfield'=>'01',   '#name_decorated'=>'Adam',     '#city_decorated'=>'Washington'    ],
        ['#pidfield'=>'02',   '#name_decorated'=>'Eve',      '#city_decorated'=>'Miami'         ],
        ['#pidfield'=>'03',   '#name_decorated'=>'Frank',    '#city_decorated'=>'Detroit'       ],
        ['#pidfield'=>'04',   '#name_decorated'=>'Paul',     '#city_decorated'=>'London'        ],
        ['#pidfield'=>'05',   '#name_decorated'=>'Alex',     '#city_decorated'=>'Paris'         ],
    ];
}

//end.
