<?php
/* CodKep sample module */

function hook_example_speedform_defineroute()
{
    $r = [];
    $r[] = ['path' => 'example/speedform',
            'callback' => 'page_speedform',
    ];

    return $r;
}

function page_speedform()
{
    ob_start();
    print "<h2>Speedform  exmaple</h2>";
    print l('<small>Definition builder</small>','speedformbuilder');

    $def = user_definition();

    $speedform = new SpeedForm($def);

    par_def('submit','text2ns');
    if(par_is('submit','Save'))
    {
        $speedform->load_parameters();
        $speedform->do_validate();

        $t = h('table')
            ->opts(['border'=>'1',
                    'style'=>'border-collapse: collapse; background-color: #444444; color: #44ff44; '.
                              'width: 400px; padding: 5px;']);
        $t->cell($speedform->sql_update_string());
        $t->nrow();
        $t->cell($speedform->sql_insert_string());

        print '<p>'.$t->get().'</p>';
    }

    $speedform->set_key('111'); //need for update
    $form = $speedform->generate_form();
    $form->action_post(current_loc());
    print $form->get();
    add_style('.user_table td { padding: 2px; }');
    return ob_get_clean();
}

function user_definition()
{
    $d =
    [
      "name" => "user_form",
      "table" => "user_table",
      "show" => "table",
      "table_class" => "user_table",
      "table_border" => "1",
      "table_style" => "background-color: #aaaaff; border-collapse: collapse;",
      "fields" => [
          10 => [
              "sql" => "id",
              "type" => "keyn",
              "text" => "User identifier",
              "default" => "",
              "centered" => true,
          ],
          20 => [
              "sql" => "static_1",
              "type" => "static",
              "default" => "Personal data",
              "centered" => true,
          ],
          30 => [
              "sql" => "fullname",
              "type" => "smalltext",
              "text" => "Name",
              "color" => "#7777ff",
              "check_noempty" => "You must fill the name field",
              "form_options" => [
                  "size" => 40,
              ],
          ],
          40 => [
              "sql" => "email",
              "type" => "smalltext",
              "text" => "E-mail",
              "par_sec" => "text5",
              "color" => "#aa99ff",
              "check_regex" => [
                  "/[a-zA-Z]+\\@[\\.a-zA-Z]+[a-z]+/" => "It seems the email address is not valid",
              ],
              "form_options" => [
                  "size" => 30,
              ],
          ],
          50 => [
              "sql" => "birth",
              "type" => "date",
              "text" => "Birth",
              "default" => "1970-01-01",
          ],
          60 => [
              "sql" => "worker",
              "type" => "txtselect",
              "text" => "Specialist",
              "values" => [
                  "e" => "Electric",
                  "p" => "Politican",
                  "i" => "Informatic",
                  "m" => "Mechanic",
                  "l" => "Plumber",
                  "o" => "Other",
              ],
              "default" => "o",
          ],
          70 => [
              "sql" => "sex",
              "type" => "txtradio",
              "text" => "Gender",
              "values" => [
                  "m" => "Male",
                  "f" => "Female",
              ],
          ],
          80 => [
              "sql" => "married",
              "type" => "check",
              "text" => "Married",
              "default" => false,
          ],
          90 => [
              "sql" => "weight",
              "type" => "numselect_intrange",
              "text" => "Weight",
              "suffix" => " kg",
              "start" => 20,
              "end" => 120,
              "default" => 80,
          ],
          100 => [
              "sql" => "lastedu",
              "type" => "dateu",
              "text" => "Last education",
              "default" => "u",
          ],
          110 => [
              "sql" => "comment",
              "type" => "largetext",
              "text" => "Comments",
              "row" => 5,
              "col" => 40,
              "color" => "#aaaaaa",
          ],
          120 => [
              "sql" => "mod",
              "type" => "timestamp_mod",
              "text" => "Last modification",
              "default" => "Not modified yet",
          ],
          130 => [
              "sql" => "submit",
              "type" => "submit",
              "default" => "Save",
              "centered" => true,
          ],
      ],
    ];
    return $d;
}
