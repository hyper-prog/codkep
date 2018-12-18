<?php

function hook_example_node_defineroute()
{
    $i = [];
    $i[] = ['path' => 'testnode',
            'title' => 'Node example',
            'callback' => 'testnode',
            '#menu' => 'Node example',
           ];
    return $i;
}

function testnode()
{
    ob_start();

    $t = h("table")->opts(['border' => '1']);
    $t->cells(['Adding a new node',l('node/NODETYPE/add','node/person/add')]);
    $t->nrow();
    $t->cells(['View a node',l('node/NID','node/2')]);
    $t->nrow();
    $t->cells(['Edit a node',l('node/NID/edit','node/2/edit')]);
    $t->nrow();
    $t->cells(['Delete a node',l('node/NID/delete','node/2/delete')]);
    print $t->get();
    return ob_get_clean();
}

function hook_example_node_node_access_person($node,$op,$account)
{
    return NODE_ACCESS_ALLOW;
}

function hook_example_node_nodetype()
{
    $r = [];
    $r['person'] =
    [
      "name" => "person",
      "table" => "person",
      "show" => "table",
      "before" => "<center>",
      "after" => "</center>",
      "color" => "#77ff88",
      "fields" => [
          10 => [
              "sql" => "pid",
              "text" => "Azonosító",
              "type" => "keyn",
          ],
          20 => [
              "sql" => "name",
              "text" => "Name",
              "type" => "smalltext",
          ],
          30 => [
              "sql" => "birt",
              "text" => "Birth",
              "type" => "date",
              "default" => "2000-01-01",
          ],
          40 => [
              "sql" => "weight",
              "text" => "Weight",
              "type" => "numselect_intrange",
              "start" => 20,
              "end" => 120,
              "default" => 60,
          ],
          50 => [
              "sql" => "s1",
              "type" => "submit",
              "default" => "Felvesz",
              "in_mode" => "insert",
              "centered" => true,
          ],
          60 => [
              "sql" => "s2",
              "type" => "submit",
              "default" => "Ment",
              "in_mode" => "update",
              "centered" => true,
          ],
          70 => [
              "type" => "submit",
              "sql" => "s3",
              "default" => "Töröl",
              "in_mode" => "delete",
              "centered" => true,
          ],
      ],
      "table_border" => "1",
      "table_style" => "	border-collapse: collapse;",
    ];

    return $r;
}



