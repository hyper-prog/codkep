<?php
/*  CodKep - Lightweight web framework core file
 *
 *  Written by Peter Deak (C) hyper80@gmail.com , License GPLv2
 *
 *
 * Forms module
 *  Required modules: core,sql
 */

function h($type,$name='')
{
    if($type == 'table')
        return new HtmlTable($name);
    if($type == 'form')
        return new HtmlForm($name);
    if($type == 'excelxml')
        return new ExcelXmlDocument($name);
}

/** Builds a HTML Table
 *  @package forms */
class HtmlTable
{
    public $name;
    public $h; //head array
    public $b; //body array
    public $o; //table options
    public $tr_open;
    public $ena_f;
    protected $callbacks;

    public function __construct($n = '')
    {
        $this->name = $n;
        $this->h = [];
        $this->b = [];
        $this->o = [];
        $this->tr_open = false;
        $this->ena_f = false;
        $this->callbacks = [];
    }

    public function setCallback($name,callable $callback)
    {
        $this->callbacks[$name] = $callback;
    }

    public function run($name,$parameter = null)
    {
        return call_user_func($this->callbacks[$name],$this,$parameter);
    }

    public function name($n)
    {
        $this->name = $n;
        return $this;
    }

    public function opts(array $o)
    {
        if(isset($o['formatter']) && $o['formatter'])
            $this->ena_f = true;
        if(isset($o['type']) && $o['type'] == 'uni')
            $o = table_options_translator($o,[],true);
        $this->o = array_merge($this->o,$o);
        return $this;
    }

    public function nrow(array $opts=[])
    {
        if(isset($opts['type']) && $opts['type'] == 'uni')
            $opts = table_options_translator($opts,[],true);
        if($this->tr_open)
            array_push($this->b,'</tr>');
        array_push($this->b,
            '<tr'.
            (isset($opts['class']) ? ' class="'.$opts['class'].'"':'').
            (isset($opts['style']) ? ' style="'.$opts['style'].'"':'').
            '>');
        $this->tr_open = true;
        return $this;
    }

    public function head($h,array $opts=[])
    {
        if(isset($opts['type']) && $opts['type'] == 'uni')
            $opts = table_options_translator($opts);
        array_push($this->h,
            '<th'.
            (isset($opts['class']) ? ' class="'.$opts['class'].'"':'').
            (isset($opts['style']) ? ' style="'.$opts['style'].'"':'').
            (isset($opts['align']) ? ' align="'.$opts['align'].'"':'') .
            (isset($opts['colspan']) ? ' colspan="'.$opts['colspan'].'"':'') .
            '>'.$this->f($h).'</th>');
        return $this;
    }

    public function heads($hs,array $opts=[])
    {
        foreach($hs as $h)
            $this->head($h,$opts);
        return $this;
    }

    public function cell($c,array $opts=[])
    {
        if(!$this->tr_open)
            $this->nrow();
        if(isset($opts['type']) && $opts['type'] == 'uni')
            $opts = table_options_translator($opts);
        array_push($this->b,
            '<td'. 
            (isset($opts['class']) ? ' class="'.$opts['class'].'"':'') .
            (isset($opts['style']) ? ' style="'.$opts['style'].'"':'') .
            (isset($opts['align']) ? ' align="'.$opts['align'].'"':'') .
            (isset($opts['colspan']) ? ' colspan="'.$opts['colspan'].'"':'') .
            '>'.$this->f($c).'</td>');
        return $this;
    }

    public function cells($cs,array $opts=[])
    {
        if(is_array($cs))
            foreach($cs as $c)
                $this->cell($c,$opts);
        return $this;
    }

    public function cellss($cs,array $opts=[])
    {
        if(is_array($cs))
            foreach($cs as $c)
                if(is_array($c))
                {
                    $this->nrow();
                    $this->cells($c,$opts);
                }
        return $this;
    }

    public function get()
    {
        $pass = new stdClass();
        $pass->table_ref = &$this;
        run_hook('table_get',$pass);
        ob_start();
        print '<table'.
                (isset($this->o['class']) ? ' class="'.$this->o['class'].'"':'').
                (isset($this->o['style']) ? ' style="'.$this->o['style'].'"':'').
                (isset($this->o['border']) ? ' border="'.$this->o['border'].'"':'').
                '>';
        if($this->tr_open)
            array_push($this->b,'</tr>');

        if(count($this->h) > 0)
        {
            print '<thead>';
            print '<tr>';
            print implode($this->h);
            print '</tr>';
            print '</thead>';
        }
        print '<tbody>';
        print implode($this->b);
        print '</tbody>';
        print "</table>\n";
        $this->h = [];
        $this->b = [];
        $this->tr_open = false;
        return ob_get_clean();
    }

    public function f($str)
    {
        if(!$this->ena_f)
            return $str;
        if(strpos($str,'#') === FALSE)
            return $str;
        $parts = explode('#',$str);
        if(isset($parts[0]) && $parts[0] == 'LINK' && isset($parts[1]) && isset($parts[2]))
        {
            return l($parts[1],$parts[2]);
        }
    }
}

/**
 * Class ExcelXmlDocument generate an Excel XML Spreadsheet
 * cell,cells,cells and nrow function can be used to build a formatted table just like in HtmlTable class
 * The cells can receive an options array which is tell the formatting of the cell(s).
 * These options is an associative array can hold values:
 *
 *  height => NUMBER - The height of the cell's row
 *  width => NUMBER - The width of the cell's row
 *  formula => STRING - Specify a formula to the cell
 *      Examples: "=RC[-1]*2" - Same row 1 column less * 2
 *                "=R[-1]C*2" - Same column 1 row less * 2
 *                "=R3C3*2" - Absolute row 3 column 3 * 2
 *  t => str|num|dat - The type of the cell
 *      str - String
 *      num - Number
 *      dat - Date, also specify the numberformat => "Short Date" and give the data in iso date yyyy-MM-dd!
 *   wrap => on|off - Cell wrapping on or off
 *   vertical => top|center|bottom - Vertical align of the cell
 *   horizontal => left|center|right - Horizontal align of the cell
 *   border => [none|all|top|bottom|left|right] - Borders of the cell. Can be a simple text or an array too.
 *   borderweight => 0|1|2|3 - Border width
 *   background-color => #RRGGBB - The background color of the cell
 *   strong => yes - Bold font
 *   italic => yes - Italic font
 *   size => XX - Point size of the font
 *   underline => yes - The font will be underlined
 *   color => #RRGGBB - The color of the font
 *   numberformat => STRING - The format of the numbers "#,##0\ &quot;Ft&quot;;[Red]\-#,##0\ &quot;Ft&quot;"
 *
 *  These options above can be translated to options works with HtmlTable class with table_options_translator()
 *  @package forms
 */
class ExcelXmlDocument
{
    protected $n;
    protected $style = [];
    protected $b = [];
    protected $colcount;
    protected $rowcount;
    protected $r_open;
    protected $r_empty;
    protected $c_index;
    protected $c_emptycellbefore;
    protected $style_counter;
    protected $row;
    protected $rowh;
    protected $colwarray;
    protected $orientation;
    protected $hc_cc;
    protected $in_header_row;
    protected $table_options;
    protected $row_options;
    protected $callbacks;

    public function __construct($n = 'Generated excel xml table')
    {
        $this->n = $n;
        $this->style = [];
        $this->b = [];
        $this->colcount = 0;
        $this->rowcount = 0;
        $this->r_open = false;
        $this->r_empty = true;
        $this->c_index = 1;
        $this->c_emptycellbefore = false;
        $this->style_counter = 10;
        $this->row = '';
        $this->rowh = 0;
        $this->colwarray = [];
        $this->orientation = "";
        $this->hc_cc=0; // header|cell, 0-no 1-header 2-cell
        $this->in_header_row = false;
        $this->table_options = [];
        $this->row_options = [];
        $this->callbacks = [];
    }

    public function setHtmlHeaders($filename)
    {
        header('Content-Type:application/xml');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
    }

    public function name($n)
    {
        $this->n = $n;
        return $this;
    }

    public function setCallback($name,callable $callback)
    {
        $this->callbacks[$name] = $callback;
    }

    public function run($name,$parameter = null)
    {
        return call_user_func($this->callbacks[$name],$this,$parameter);
    }

    /** Does nothing, for compatibility reasons only */
    public function opts(array $opts)
    {
        $this->table_options = $opts;
        return $this;
    }

    public function nrow(array $opts=[])
    {
        if($this->r_open)
            $this->endRow();
        $this->beginRow($opts);
        return $this;
    }

    public function nrows($count = 1,array $opts=[])
    {
        if($count <= 0)
            return $this;
        for($i = 0 ; $i < $count ; $i++)
            $this->nrow($opts);
        return $this;
    }

    protected function beginRow(array $opts=[])
    {
        if($this->r_open)
            return;
        $this->r_empty = true;
        $this->row = '';
        $this->rowh = 0;
        $this->r_open = true;
        $this->c_index = 1;
        $this->c_emptycellbefore = false;
        $this->rowcount++;
        $this->row_options = $opts;
    }

    protected function endRow()
    {
        if(!$this->r_open)
            return;
        if($this->r_empty)
        {
            array_push($this->b,"<Row><Cell/></Row>\n");
        }
        else
        {
            if($this->rowh != 0)
                array_push($this->b,'<Row ss:AutoFitHeight="0" ss:Height="'.$this->rowh.'">'."\n");
            else
                array_push($this->b,"<Row>\n");
            array_push($this->b,$this->row);
            array_push($this->b, "</Row>\n");
        }
        $this->row = '';
        $this->rowh = 0;
        $this->r_open = false;
        $this->row_options = [];
        $this->in_header_row = false;
    }

    public function cell($c = '',array $opts=[])
    {
        if(!$this->r_open)
            $this->beginRow();

        if(isset($opts['ashead']) && $opts['ashead'])
            $this->in_header_row = true;

        if($this->hc_cc == 1 && !isset($opts['ashead']))
        {
            $this->hc_cc = 2;
            if($this->in_header_row) //We only need to start a new row when already in header
                $this->nrow();
        }

        $opts = array_merge($this->table_options,$this->row_options,$opts);

        if(isset($opts['xheight']) && $opts['xheight'] != '')
            $opts['height'] = $opts['xheight'];
        if(isset($opts['xwidth']) && $opts['xwidth'] != '')
            $opts['width'] = $opts['xwidth'];

        if(isset($opts['height']) && $opts['height'] != '')
            if($this->rowh < $opts['height'])
                $this->rowh = $opts['height'];
        if(isset($opts['width']) && $opts['width'] != '')
            if(!isset($this->colwarray[$this->c_index]) || $this->colwarray[$this->c_index] < $opts['width'])
                $this->colwarray[$this->c_index] = $opts['width'];

        $sId = $this->styleId($opts);
        if($c !== '' || $sId != '' || isset($opts['formula']))
        {
            $ssMerge = '';
            $ssStyle = '';
            $ssIndex = '';
            $ssFormula = '';
            if($this->c_emptycellbefore)
                $ssIndex = ' ss:Index="'.$this->c_index.'"';
            if($sId != '')
                $ssStyle = ' ss:StyleID="'.$sId.'"';
            if(isset($opts['formula']))
                $ssFormula = ' ss:Formula="'.$opts['formula'].'"';
            if(isset($opts['xcolspan']) && intval($opts['xcolspan']) > 1)
                $opts['colspan'] = $opts['xcolspan'];
            if(isset($opts['colspan']) && intval($opts['colspan']) > 1)
                $ssMerge = ' ss:MergeAcross="'.(intval($opts['colspan']) - 1).'"';
            if($c !== '')
            {
                $type = 'String';
                if(isset($opts['t']))
                {
                    if($opts['t'] == 'num')
                    {
                        $type = 'Number';
                        $comma_cnt  = substr_count($c,',');
                        if($comma_cnt == 1)
                            $c = str_replace(',','.',$c);
                        if($comma_cnt > 1)
                            $c = str_replace(',','',$c);
                    }
                    if($opts['t'] == 'dat')
                        $type = 'DateTime';
                }
                $this->row .= ' <Cell' . $ssIndex . $ssMerge . $ssStyle . $ssFormula. '><Data ss:Type="'.$type.'">' . $c . '</Data></Cell>' . "\n";
            }
            else
            {
                $this->row .= ' <Cell' . $ssIndex . $ssMerge . $ssStyle . $ssFormula .'/>' . "\n";
            }
            $this->c_emptycellbefore = false;
            $this->r_empty = false;
        }
        else
        {
            $this->c_emptycellbefore = true;
        }

        $this->c_index++;
        if(isset($opts['colspan']) && intval($opts['colspan']) > 1)
            $this->c_index += (intval($opts['colspan']) - 1);

        if($this->colcount < $this->c_index)
            $this->colcount = $this->c_index;
        return $this;
    }

    public function cells($cs,array $opts=[])
    {
        if(is_array($cs))
            foreach($cs as $c)
                $this->cell($c,$opts);
        return $this;
    }

    public function cellss($cs,array $opts=[])
    {
        if(is_array($cs))
            foreach($cs as $c)
                if(is_array($c))
                {
                    $this->nrow();
                    $this->cells($c, $opts);
                }
        return $this;
    }

    public function head($hs,array $opts=[])
    {
        $this->hc_cc=1;
        $opts['ashead'] = true;
        return $this->cell($hs,$opts);
    }

    public function heads($hs,array $opts=[])
    {
        $this->hc_cc=1;
        $opts['ashead'] = true;
        return $this->cells($hs,$opts);
    }

    protected function styleId(array $opts)
    {
        $sStr = "";

        //wrap & vertical & horizontal
        if(isset($opts['wrap']) || isset($opts['vertical']) || isset($opts['horizontal']))
        {
            $wrap = "1";
            if(isset($opts['wrap']) && $opts['wrap'] == 'off')
                $wrap = "0";
            $vertical = "Top";
            if(isset($opts['vertical']))
            {
                if($opts['vertical'] == 'top'   ) $vertical = 'Top';
                if($opts['vertical'] == 'center') $vertical = 'Center';
                if($opts['vertical'] == 'bottom') $vertical = 'Bottom';
            }
            $horizontal = "Left";
            if(isset($opts['horizontal']))
            {
                if($opts['horizontal'] == 'left'  ) $horizontal = 'Left';
                if($opts['horizontal'] == 'center') $horizontal = 'Center';
                if($opts['horizontal'] == 'right' ) $horizontal = 'Right';
            }
            $sStr .= "    <Alignment ss:Horizontal=\"$horizontal\" ss:Vertical=\"$vertical\" ss:WrapText=\"$wrap\"/>\n";
        }

        //border & borderweight
        if(isset($opts['border']))
        {
            $borders = ['Bottom','Left','Right','Top'];
            $bw = '1';
            if(isset($opts['borderweight']))
                $bw = $opts['borderweight'];
            if(!in_array($bw,['0','1','2','3']))
                $bw = '1';
            $sStr .= "    <Borders>\n";
            if(is_array($opts['border']))
            {
                foreach($borders as $b)
                    if(in_array(strtolower($b),$opts["border"]) || in_array("all",$opts["border"]))
                        $sStr .= '      <Border ss:Position="'.$b.'" ss:LineStyle="Continuous" ss:Weight="'.$bw.'"/>'."\n";
            }
            else
            {
                foreach($borders as $b)
                    if($opts["border"] == strtolower($b) || $opts["border"] == "all")
                        $sStr .= '      <Border ss:Position="'.$b.'" ss:LineStyle="Continuous" ss:Weight="'.$bw.'"/>'."\n";
            }
            $sStr .= "    </Borders>\n";
        }

        //background-color
        if(isset($opts['background-color']))
        {
            $bgcolor = '';
            if(isset($opts['background-color']) && $opts['background-color'] != '')
                $bgcolor = $opts['background-color'];
            $sStr .= "    <Interior ss:Color=\"$bgcolor\" ss:Pattern=\"Solid\"/>\n";
        }

        //strong & italic & size & color
        if(isset($opts['strong']) || isset($opts['italic']) ||
           isset($opts['underline']) || isset($opts['size']) || isset($opts['xsize']) || isset($opts['color']))
        {
            $strong = '';
            if(isset($opts['strong']) && $opts['strong'] == 'yes')
                $strong = ' ss:Bold="1"';

            $italic = '';
            if(isset($opts['italic']) && $opts['italic'] == 'yes')
                $italic = ' ss:Italic="1"';

            $underline = '';
            if(isset($opts['underline']) && $opts['underline'] == 'yes')
                $underline = ' ss:Underline="Single"';

            $size = '';
            if(isset($opts['size']) && $opts['size'] != '')
                $size = ' ss:Size="'.$opts['size'].'"';
            if(isset($opts['xsize']) && $opts['xsize'] != '')
                $size = ' ss:Size="'.$opts['xsize'].'"';

            $color = '';
            if(isset($opts['color']) && $opts['color'] != '')
                $color = ' ss:Color="'.$opts['color'].'"';

            $sStr .= "    <Font ss:FontName=\"Arial\" x:CharSet=\"238\" x:Family=\"Swiss\"$size$strong$italic$underline$color/>\n";
        }

        //numberformat
        if(isset($opts['numberformat']) && $opts['numberformat'] != '')
        {
            $sStr .= "    <NumberFormat ss:Format=\"".$opts['numberformat']."\"/>\n";
        }

        // - End of building style -
        if($sStr == '')
            return '';

        $fidx = array_search($sStr,$this->style);
        if($fidx !== FALSE && substr($fidx,0,1) == 's')
            return $fidx;

        $fidx = 's'.$this->style_counter;
        $this->style[$fidx] = $sStr;
        $this->style_counter++;
        return $fidx;
    }

    public function setTitle($title)
    {
        $this->name($title);
    }

    public function setOrientationLandscape()
    {
        $this->orientation = "Landscape";
    }

    public function setOrientationPortrait()
    {
        $this->orientation = "";
    }

    public function get()
    {
        global $user;
        if($this->r_open)
            $this->endRow();

        ob_start();
        print "<?xml version=\"1.0\"?>\n".
            "<?mso-application progid=\"Excel.Sheet\"?>\n".
            "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n".
            " xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n".
            " xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n".
            " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"\n".
            " xmlns:html=\"http://www.w3.org/TR/REC-html40\">\n\n";

        $un = "CodKep generated";
        if($user->auth && $user->name != '')
            $un = "CodKep generated by ".$user->name;

        print "<DocumentProperties xmlns=\"urn:schemas-microsoft-com:office:office\">\n".
            "  <Author>$un</Author>\n".
            "  <LastAuthor>$un</LastAuthor>\n".
            "  <Created>".date("Y-m-d\TH:i:s\Z")."</Created>\n".
            "</DocumentProperties>\n";

        print "<Styles>\n";
        print "  <Style ss:ID=\"Default\" ss:Name=\"Normal\">\n".
            "    <Alignment ss:Vertical=\"Top\" ss:WrapText=\"1\"/>\n".
            "    <Borders/>\n".
            "    <Font ss:FontName=\"Arial\" x:CharSet=\"238\"/>\n".
            "    <Interior/>\n".
            "    <NumberFormat/>\n".
            "    <Protection/>\n".
            "  </Style>\n";
        foreach($this->style as $styleKey => $styleValue)
        {
            print "  <Style ss:ID=\"$styleKey\">\n";
            print $styleValue;
            print "  </Style>\n";
        }
        print "</Styles>\n\n";

        if($this->n == '')
            $this->n = 'Generated';
        print "<Worksheet ss:Name=\"" . $this->n . "\">\n";
        print "<Table ss:ExpandedColumnCount=\"".$this->colcount."\" ".
            "ss:ExpandedRowCount=\"".$this->rowcount."\" x:FullColumns=\"1\" x:FullRows=\"1\">\n";
        for($c = 1 ; $c <= $this->colcount ; ++$c)
            if(isset($this->colwarray[$c]))
                print "<Column ss:Index=\"$c\" ss:AutoFitWidth=\"0\" ss:Width=\"".$this->colwarray[$c]."\"/>\n";
        print implode($this->b);
        print "</Table>\n";
        print "<WorksheetOptions xmlns=\"urn:schemas-microsoft-com:office:excel\">\n".
            "  <PageSetup>\n";

        if($this->orientation != '')
            print "    <Layout x:Orientation=\"".$this->orientation."\"/>\n";

        print "    <Header x:Margin=\"0.3\"/>\n".
              "    <Footer x:Margin=\"0.3\"/>\n".
              "    <PageMargins x:Bottom=\"0.75\" x:Left=\"0.25\" x:Right=\"0.25\" x:Top=\"0.75\"/>\n".
              "  </PageSetup>\n".
              "  <ProtectObjects>False</ProtectObjects>\n".
              "  <ProtectScenarios>False</ProtectScenarios>\n".
              "</WorksheetOptions>\n";
        print "</Worksheet>\n";
        print "</Workbook>\n"; //Root element
        return ob_get_clean();
    }
}

/** Transforms ExcelXmlDocument cell options to HtmlTable cell options
 *  @package forms */
function table_options_translator(array $opts,array $additional = [],$style_and_class_only_mode = false)
{
    $o = [];
    $style = '';
    if(isset($opts['style']))
        $style = $opts['style'];

    if(isset($opts['width']) || isset($opts['twidth']))
    {
        $w = 0;
        if(isset($opts['width']))
            $w = $opts['width'];
        if(isset($opts['twidth']))
            $w = $opts['twidth'];
        $style .= 'min-width: ' . $w . 'px; ';
    }
    if(isset($opts['height']) || isset($opts['theight']))
    {
        $h = 0;
        if(isset($opts['height']))
            $h = $opts['height'];
        if(isset($opts['theight']))
            $h = $opts['theight'];
        $style .= 'height: ' . $h . 'px; ';
    }
    if(isset($opts['background-color']))
        $style .= 'background-color: '.$opts['background-color'].'; ';
    if(isset($opts['color']))
        $style .= 'color: '.$opts['color'].'; ';
    if(isset($opts['strong']) && $opts['strong'] == 'yes')
        $style .= 'font-weight: bold; ';
    if(isset($opts['italic']) && $opts['italic'] == 'yes')
        $style .= 'font-style: italic; ';
    if(isset($opts['underline']) && $opts['underline'] == 'yes')
        $style .= 'text-decoration: underline; ';
    if(isset($opts['size']) || isset($opts['tsize']))
    {
        $s = 12;
        if(isset($opts['size']))
            $s = $opts['size'];
        if(isset($opts['tsize']))
            $s = $opts['tsize'];
        $style .= 'font-size: ' . $s . 'px; ';
    }

    if(isset($opts['vertical']))
    {
        if($opts['vertical'] == 'top')
            $style .= 'vertical-align: top; ';
        if($opts['vertical'] == 'bottom')
            $style .= 'vertical-align: bottom; ';
        if($opts['vertical'] == 'center')
            $style .= 'vertical-align: middle; ';
    }
    else
        $style .= 'vertical-align: top; ';

    if(isset($opts['horizontal']))
    {
        if($opts['horizontal'] == 'left')
            $style .= 'text-align: left; ';
        if($opts['horizontal'] == 'right')
            $style .= 'text-align: right; ';
        if($opts['horizontal'] == 'center')
            $style .= 'text-align: center; ';
    }
    else
        $style .= 'text-align: left; ';

    $borders = ['bottom','left','right','top'];
    $bw = '1';
    if(isset($opts['borderweight']))
        $bw = $opts['borderweight'];
    if(!in_array($bw,['0','1','2','3']))
        $bw = '1';

    if(isset($opts["border"]))
    {
        if(is_array($opts['border']))
        {
            foreach($borders as $b)
                if(in_array($b, $opts["border"]) || in_array("all", $opts["border"]))
                    $style .= "border-$b: $bw" . "px solid black; ";
        }
        else
        {
            foreach($borders as $b)
                if($opts["border"] == $b || $opts["border"] == "all")
                    $style .= "border-$b: $bw" . "px solid black; ";
        }
    }

    $o['style'] = $style;
    if(isset($opts['class']))
        $o['class'] = $opts['class'];
    if(!$style_and_class_only_mode)
    {
        if (isset($opts['align']))
            $o['align'] = $opts['align'];
        if (isset($opts['colspan']))
            $o['colspan'] = $opts['colspan'];
        if (isset($opts['tcolspan']))
            $o['colspan'] = $opts['tcolspan'];
    }
    $o = array_merge($o,$additional);
    return $o;
}

/** Builds a HTML form
 *  @package forms */
class HtmlForm
{
    public $name;
    public $d; //data
    public $o; //opts
    public $url;
    public $mode;

    public $formatter;

    protected $callbacks;

    public function __construct($n = '',$formatter = NULL)
    {
        $this->name = $n;
        $this->d = [];
        $this->o = [];
        $this->url = '';
        $this->mode = 'POST';
        $this->formatter = $formatter == NULL ? new HtmlFormFormatter() : $formatter ;
        $this->hidden('harassment-value',getFormSalt());
        $this->callbacks = [];
    }

    public function name($n)
    {
        $this->name = $n;
        return $this;
    }

    public function setCallback($name,callable $callback)
    {
        $this->callbacks[$name] = $callback;
    }

    public function run($name,$parameter = null)
    {
        return call_user_func($this->callbacks[$name],$this,$parameter);
    }

    public function opts(array $o)
    {
        $this->o = array_merge($this->o,$o);
        return $this;
    }

    public function set_formatter($formatter)
    {
        $this->formatter = $formatter;
    }

    public function action_get($rawurl,array $query = [],array $urlopts = [])
    {
        $this->url = url($rawurl,[],$urlopts);
        $this->mode = 'GET';

        foreach($query as $n => $v)
            $this->hidden($n,$v);
        return $this;
    }
    public function action_post($rawurl,array $query = [],array $urlopts = [])
    {
        $this->url = url($rawurl,$query,$urlopts);
        $this->mode = 'POST';
        return $this;
    }
    public function action_ajax($rawurl,array $query = [],array $urlopts = [])
    {
        $this->url = url($rawurl,$query,$urlopts);
        $this->mode = 'AJAX';
        return $this;
    }
    public function action_ajaxcallback($target,array $query = [],array $urlopts = [])
    {
        $this->action_ajax(sysEncodeConnectorTarget('connect',$target),$query,$urlopts);
        return $this;
    }
    public function action_postcallback($target,array $query = [],array $urlopts = [])
    {
        $this->action_post(sysEncodeConnectorTarget('route',$target),$query,$urlopts);
        return $this;
    }

    public function input($type,$n,$v,array $opts=[])
    {
        array_push($this->d,
            ['type' => $type,
             'name' => $n,
             'value' => $v,
             'class' => (isset($opts['class']) ? $opts['class'] : ''),
             'style' => (isset($opts['style']) ? $opts['style'] : ''),
             'onclick' => (isset($opts['onclick']) ? $opts['onclick'] : ''),
             'onchange' => (isset($opts['onchange']) ? $opts['onchange'] : ''),
             'id' => (isset($opts['id']) ? $opts['id'] : ''),
             'before' => (isset($opts['before']) ? $opts['before'] : ''),
             'after' => (isset($opts['after']) ? $opts['after'] : ''),
             'size' => (isset($opts['size']) ? $opts['size'] : ''),
             'maxlength' => (isset($opts['maxlength']) ? $opts['maxlength'] : ''),
             'readonly' => (isset($opts['readonly']) ? $opts['readonly'] : false),
             'autofocus' => (isset($opts['autofocus']) ? $opts['autofocus'] : false),
             'required' => (isset($opts['required']) ? $opts['required'] : false),
             'placeholder' => (isset($opts['placeholder']) ? $opts['placeholder'] : ''),
             'rawattributes' => (isset($opts['rawattributes']) ? $opts['rawattributes'] : ''),
             'lang' => (isset($opts['lang']) ? $opts['lang'] : '')
            ]);
        return $this;
    }

    public function upload($n,$v,array $opts=[])
    {
        array_push($this->d,
            ['type' => 'upload',
             'name' => $n,
             'value' => $v,
             'class' => (isset($opts['class']) ? $opts['class'] : ''),
             'style' => (isset($opts['style']) ? $opts['style'] : ''),
             'onclick' => (isset($opts['onclick']) ? $opts['onclick'] : ''),
             'onchange' => (isset($opts['onchange']) ? $opts['onchange'] : ''),
             'id' => (isset($opts['id']) ? $opts['id'] : ''),
             'before' => (isset($opts['before']) ? $opts['before'] : ''),
             'after' => (isset($opts['after']) ? $opts['after'] : ''),
             'size' => (isset($opts['size']) ? $opts['size'] : ''),
             'maxlength' => (isset($opts['maxlength']) ? $opts['maxlength'] : ''),
             'readonly' => (isset($opts['readonly']) ? $opts['readonly'] : false),
             'autofocus' => (isset($opts['autofocus']) ? $opts['autofocus'] : false),
             'rawattributes' => (isset($opts['rawattributes']) ? $opts['rawattributes'] : ''),
            ]);
        return $this;
    }

    public function datefield($type,$n,$v,array $opts=[])
    {
        array_push($this->d,
            ['type' => $type,
             'name' => $n,
             'value' => $v,
             'class' => (isset($opts['class']) ? $opts['class'] : ''),
             'style' => (isset($opts['style']) ? $opts['style'] : ''),
             'before' => (isset($opts['before']) ? $opts['before'] : ''),
             'after' => (isset($opts['after']) ? $opts['after'] : ''),
             'readonly' => (isset($opts['readonly']) ? $opts['readonly'] : false),
             'rawattributes' => (isset($opts['rawattributes']) ? $opts['rawattributes'] : ''),
             'autofocus' => false,
            ]);
        return $this;
    }

    public function select($type,$n,$v,array $values=[],array $opts=[])
    {
        array_push($this->d,
            ['type' => $type,
             'name' => $n,
             'value' => $v,
             'values' => $values,
             'class' => (isset($opts['class']) ? $opts['class'] : ''),
             'style' => (isset($opts['style']) ? $opts['style'] : ''),
             'onclick' => (isset($opts['onclick']) ? $opts['onclick'] : ''),
             'onchange' => (isset($opts['onchange']) ? $opts['onchange'] : ''),
             'id' => (isset($opts['id']) ? $opts['id'] : ''),
             'before' => (isset($opts['before']) ? $opts['before'] : ''),
             'after' => (isset($opts['after']) ? $opts['after'] : ''),
             'readonly' => (isset($opts['readonly']) ? $opts['readonly'] : false),
             'autofocus' => (isset($opts['autofocus']) ? $opts['autofocus'] : false),
             'itemprefix' => (isset($opts['itemprefix']) ? $opts['itemprefix'] : ''),
             'itemsuffix' => (isset($opts['itemsuffix']) ? $opts['itemsuffix'] : ''),
             'itemlabelprefix' => (isset($opts['itemlabelprefix']) ? $opts['itemlabelprefix'] : ''),
             'itemlabelsuffix' => (isset($opts['itemlabelsuffix']) ? $opts['itemlabelsuffix'] : ''),
             'rawattributes' => (isset($opts['rawattributes']) ? $opts['rawattributes'] : ''),
            ]);
        return $this;
    }

    public function textarea($n,$v,$row,$col,array $opts=[])
    {
        array_push($this->d,
            ['type' => 'textarea',
             'name' => $n,
             'value' => $v,
             'row'   => $row,
             'col'   => $col,
             'class' => (isset($opts['class']) ? $opts['class'] : ''),
             'style' => (isset($opts['style']) ? $opts['style'] : ''),
             'onclick' => (isset($opts['onclick']) ? $opts['onclick'] : ''),
             'onchange' => (isset($opts['onchange']) ? $opts['onchange'] : ''),
             'id' => (isset($opts['id']) ? $opts['id'] : ''),
             'before' => (isset($opts['before']) ? $opts['before'] : ''),
             'after' => (isset($opts['after']) ? $opts['after'] : ''),
             'readonly' => (isset($opts['readonly']) ? $opts['readonly'] : false),
             'softreadonly' => (isset($opts['softreadonly']) ? $opts['softreadonly'] : false),
             'disablercsize' => (isset($opts['disablercsize']) ? $opts['disablercsize'] : false),
             'autofocus' => (isset($opts['autofocus']) ? $opts['autofocus'] : false),
             'required' => (isset($opts['required']) ? $opts['required'] : false),
             'placeholder' => (isset($opts['placeholder']) ? $opts['placeholder'] : ''),
             'rawattributes' => (isset($opts['rawattributes']) ? $opts['rawattributes'] : ''),
            ]);
        return $this;
    }

    public function text($name,$t,array $opts=[])
    {
        array_push($this->d,
            ['type' => 'raw',
             'name' => $name,
             'data' => $t,
             'class' => (isset($opts['class']) ? $opts['class'] : ''),
             'style' => (isset($opts['style']) ? $opts['style'] : ''),
             'onclick' => (isset($opts['onclick']) ? $opts['onclick'] : ''),
             'onchange' => (isset($opts['onchange']) ? $opts['onchange'] : ''),
             'id' => (isset($opts['id']) ? $opts['id'] : ''),
             'before' => (isset($opts['before']) ? $opts['before'] : ''),
             'after' => (isset($opts['after']) ? $opts['after'] : ''),
             'readonly' => (isset($opts['readonly']) ? $opts['readonly'] : false),
             'autofocus' => (isset($opts['autofocus']) ? $opts['autofocus'] : false),
             'rawattributes' => (isset($opts['rawattributes']) ? $opts['rawattributes'] : ''),
            ]);
        return $this;
    }

    public function custom($name,$value,callable $callback,array $opts=[])
    {
        array_push($this->d,
            ['type' => 'custom',
                'name' => $name,
                'value' => $value,
                'callback' => $callback,
                'class' => (isset($opts['class']) ? $opts['class'] : ''),
                'style' => (isset($opts['style']) ? $opts['style'] : ''),
                'onclick' => (isset($opts['onclick']) ? $opts['onclick'] : ''),
                'onchange' => (isset($opts['onchange']) ? $opts['onchange'] : ''),
                'id' => (isset($opts['id']) ? $opts['id'] : ''),
                'before' => (isset($opts['before']) ? $opts['before'] : ''),
                'after' => (isset($opts['after']) ? $opts['after'] : ''),
                'readonly' => (isset($opts['readonly']) ? $opts['readonly'] : false),
                'autofocus' => (isset($opts['autofocus']) ? $opts['autofocus'] : false),
                'itemprefix' => (isset($opts['itemprefix']) ? $opts['itemprefix'] : ''),
                'itemsuffix' => (isset($opts['itemsuffix']) ? $opts['itemsuffix'] : ''),
                'required' => (isset($opts['required']) ? $opts['required'] : false),
                'placeholder' => (isset($opts['placeholder']) ? $opts['placeholder'] : ''),
                'rawattributes' => (isset($opts['rawattributes']) ? $opts['rawattributes'] : ''),
            ]);
        return $this;
    }

    public function hidden($n,$v,array $opts=[])
    {
        $this->input('hidden',$n,$v,$opts);
        return $this;
    }

    public function hidden_array(array $keyvalue,array $opts=[])
    {
        foreach($keyvalue as $key => $value)
            $this->input('hidden',$key,$value,$opts);
    }

    public function input_p($type,$n,$v,array $opts=[])
    {
        if(!isset($opts['no_par_def']) || !$opts['no_par_def'])
            par_def($n,isset($opts['par_sec']) ? $opts['par_sec'] : 'text4');

        if(par_ex($n) && (!isset($opts['no_par_load']) || !$opts['no_par_load']))
            $v = par($n);
        if($type == 'checkbox' && $v == 'on')
            $v = 1;
        if($type == 'checkbox' && $v == 'off')
            $v = 0;
        $this->input($type,$n,$v,$opts);
        return $this;
    }

    public function select_p($type,$n,$v,array $values=[],array $opts=[])
    {
        if(!isset($opts['no_par_def']) || !$opts['no_par_def'])
            par_def($n,isset($opts['par_sec']) ? $opts['par_sec'] : 'text4');

        if(par_ex($n) && (!isset($opts['no_par_load']) || !$opts['no_par_load']))
            $v = par($n);
        $this->select($type,$n,$v,$values,$opts);
        return $this;
    }

    public function textarea_p($n,$v,$row,$col,array $opts=[])
    {
        if(!isset($opts['no_par_def']) || !$opts['no_par_def'])
            par_def($n,isset($opts['par_sec']) ? $opts['par_sec'] : 'text4');

        if(par_ex($n) && (!isset($opts['no_par_load']) || !$opts['no_par_load']))
            $v = par($n);
        $this->textarea($n,$v,$row,$col,$opts);
        return $this;
    }

    public function datefield_p($type,$n,$v,array $opts=[])
    {
        if(!isset($opts['no_par_def']) || !$opts['no_par_def'])
        {
            par_def($n . '_year', isset($opts['par_sec']) ? $opts['par_sec'] : 'number0');
            par_def($n . '_month', isset($opts['par_sec']) ? $opts['par_sec'] : 'number0');
            par_def($n . '_day', isset($opts['par_sec']) ? $opts['par_sec'] : 'number0');
        }

        $dval = $v;
        if(par_ex($n.'_year' ) && par_ex($n.'_month') && par_ex($n.'_day') &&
          (!isset($opts['no_par_load']) || !$opts['no_par_load']))
        {
            $vy = par($n . '_year');
            $vm = par($n . '_month');
            $vd = par($n . '_day');
            $dval = "$vy-$vm-$vd";
        }

        $this->datefield($type,$n,$dval,$opts);
        return $this;
    }

    public function getId()
    {
        if(isset($this->o['id']))
            return $this->o['id'];
        return '';
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    public function get($readonly = false)
    {
        return $this->get_start($readonly) . $this->get_p($readonly) . $this->get_end($readonly);
    }

    public function get_start($readonly = false)
    {
        $pass = new stdClass();
        $pass->form_ref = &$this;
        run_hook('form_get_start',$pass);
        ob_start();
        $mode = 'POST';
        $enc = ' enctype="multipart/form-data"';
        if($this->mode == 'GET')
        {
            $mode = 'GET';
            $enc = '';
        }
        if($this->mode == 'AJAX')
        {
            if(isset($this->o['class']))
                $this->o['class'] .= ' use-ajax';
            else
                $this->o['class'] = 'use-ajax';
        }
        print $this->formatter->begin_form(
            "<form method=\"$mode\"".
            ($this->url != '' ? ' action="'.$this->url.'"':'').
            (isset($this->o['id']) ? ' id="'.$this->o['id'].'"':'').
            (isset($this->o['class']) ? ' class="'.$this->o['class'].'"':'').
            (isset($this->o['style']) ? ' style="'.$this->o['style'].'"':'').
            "$enc>");
        return ob_get_clean();
    }

    protected function get_item($dta,$readonly = false)
    {
        $ro = $readonly;
        if($dta['readonly'])
            $ro = true;
        $pass = new stdClass();
        $pass->form_name = $this->name;
        $pass->item_ref = &$dta;
        $pass->readonly_ref = &$ro;
        run_hook('form_get_item',$pass);

        if($ro && $dta['type'] != 'submit')
            return $this->get_readonly_item($dta);

        ob_start();
        if($dta['type'] == 'hidden')
        {
            print $dta['before']."<input type=\"". $dta['type'] ."\" name=\"". $dta['name'] .
                    "\" value=\"". $dta['value'] ."\"".
                    ($dta['class']==''?'':' class="'.$dta['class'].'"').
                    ($dta['style']==''?'':' style="'.$dta['style'].'"').
                    ($dta['id']==''?'':' id="'.$dta['id'].'"').
                    ($dta['rawattributes']==''?'':' '.$dta['rawattributes']).
                    "/>".$dta['after']."\n";
        }

        if($dta['type'] == 'raw')
            print $this->formatter->item($dta['before'].$dta['data'].$dta['after'],$dta['name']);

        if($dta['type'] == 'custom')
        {
            $str = $dta['before'] . call_user_func($dta['callback'],$this,$dta,false) . $dta['after'];
            print $this->formatter->item($str, $dta['name']);
        }

        if($dta['type'] == 'text'     ||
           $dta['type'] == 'number'   ||
           $dta['type'] == 'file'     ||
           $dta['type'] == 'password' ||
           $dta['type'] == 'submit'     )
        {
            print $this->formatter->item($dta['before'].
                    "<input type=\"". $dta['type'] ."\" name=\"". $dta['name'] .
                    "\" value=\"". $dta['value'] ."\"".
                    ($dta['class']==''?'':' class="'.$dta['class'].'"').
                    ($dta['style']==''?'':' style="'.$dta['style'].'"').
                    ($dta['onclick']==''?'':' onclick="'.$dta['onclick'].'"').
                    ($dta['onchange']==''?'':' onchange="'.$dta['onchange'].'"').
                    ($dta['id']==''?'':' id="'.$dta['id'].'"').
                    ($dta['size']==''?'':' size="'.$dta['size'].'"').
                    ($dta['type'] == 'password' ? ' autocomplete="off"' : '').
                    ($dta['maxlength']==''?'':' maxlength="'.$dta['maxlength'].'"').
                    ($dta['lang']==''?'':' lang="'.$dta['lang'].'"').
                    ($dta['autofocus']?' autofocus':'').
                    ($dta['required']?' required="required" aria-required="true"':'').
                    ($dta['placeholder']==''?'':' placeholder="'.$dta['placeholder'].'"').
                    ($dta['rawattributes']==''?'':' '.$dta['rawattributes']).
                    "/>".$dta['after'],$dta['name']);
        }

        if($dta['type'] == 'upload')
        {
            $cnt = '';
            if($dta['value'] == '')
            {
                $cnt .= "<input type=\"file\" name=\"". $dta['name'] .
                    "\" value=\"". $dta['value'] ."\"/>";
            }
            else
            {
                $id = 'fuf_'.$dta['name'].'_'.rand(1000,9999);
                $fobject = file_load($dta['value'],true);
                $cnt .= "<input id=\"f_$id\" style=\"display:none;\" type=\"file\" name=\"". $dta['name'] .
                    "\" value=\"". $dta['value'] ."\"/>";
                $cnt .= "<input id=\"h_$id\" type=\"hidden\" name=\"". $dta['name'] . '_delete' .
                    "\" value=\"keep\"/>";
                $cnt .= l($fobject->name,$fobject->url,['id' => "l_$id"]);
                $cnt .= ' ';
                $btnstyle = 'border: 0 none; cursor: pointer; margin: 0; padding: 0;';
                $oncl = "forms_click_delete('$id');";
                $cnt .= "<span id=\"b_$id\" style=\"$btnstyle\" onclick=\"javascript:$oncl\">".
                        "<img src=\"".url('/sys/images/small_red_cross.png').'" style="width: 16px; height: 16px;"/></span>';
            }
            print $this->formatter->item($dta['before'].$cnt.$dta['after'],$dta['name']);
        }

        if($dta['type'] == 'checkbox')
        {
            $s = $dta['value'] ? ' checked="checked"' : '';
            print $this->formatter->item($dta['before'].
                    "<input type=\"hidden\" name=\"". $dta['name'] . "\" value=\"off\"/>".
                    "<input type=\"". $dta['type'] ."\" name=\"". $dta['name'] . "\"".
                    ($dta['class']==''?'':' class="'.$dta['class'].'"').
                    ($dta['style']==''?'':' style="'.$dta['style'].'"').
                    ($dta['onclick']==''?'':' onclick="'.$dta['onclick'].'"').
                    ($dta['id']==''?'':' id="'.$dta['id'].'"').
                    ($dta['rawattributes']==''?'':' '.$dta['rawattributes']).
                    "$s/>".$dta['after'],$dta['name']);
        }

        if($dta['type'] == 'select')
        {
            $t = '<select name="'.$dta['name'].'"'.
                 ($dta['class']==''?'':' class="'.$dta['class'].'"').
                 ($dta['style']==''?'':' style="'.$dta['style'].'"').
                 ($dta['onclick']==''?'':' onclick="'.$dta['onclick'].'"').
                 ($dta['onchange']==''?'':' onchange="'.$dta['onchange'].'"').
                 ($dta['id']==''?'':' id="'.$dta['id'].'"').
                 ($dta['autofocus']?' autofocus':'').
                 ($dta['rawattributes']==''?'':' '.$dta['rawattributes']).
                 '>';
            foreach($dta['values'] as $h => $v)
            {
                $s = $dta['value'] == $h ? ' selected' : ''; 
                $t .= $dta['itemprefix']."<option value=\"$h\"$s>$v</option>".$dta['itemsuffix'];
            }
            $t .= '</select>';
            print $this->formatter->item($dta['before'].$t.$dta['after'],$dta['name']);
        }

        if($dta['type'] == 'optselect')
        {
            $id = 'fsf_'.$dta['name'].'_'.rand(1000,9999);
            $value_is_set = false;
            if($dta['value'] != '')
                $value_is_set = true;

            $t = '<input type="hidden" name="'.$dta['name'].'_sts" value="'.($value_is_set ? 'set' : 'null' ).
                 '"  id="sts_'.$id.'"/>';
            if(!$value_is_set)
                $dta['style'] .= ' display:none;';
            $t .= '<select name="'.$dta['name'].'"'.
                ($dta['class']==''?'':' class="'.$dta['class'].'"').
                ($dta['style']==''?'':' style="'.$dta['style'].'"').
                ' id="sel_'.$id.'"'.
                ($dta['rawattributes']==''?'':' '.$dta['rawattributes']).
                '>';
            foreach($dta['values'] as $h => $v)
            {
                $s = $dta['value'] == $h ? ' selected' : '';
                $t .= $dta['itemprefix']."<option value=\"$h\"$s>$v</option>".$dta['itemsuffix'];
            }
            $t .= '</select> ';
            $onclset = "forms_click_selset('$id');";
            $onclreset = "forms_click_selreset('$id');";
            $btnstyle1 = 'border: 0 none; cursor: pointer; margin: 0; padding: 0;';
            $btnstyle2 = 'border: 0 none; cursor: pointer; margin: 0; padding: 0;';
            if($value_is_set)
                $btnstyle2 .= ' display:none;';
            else
                $btnstyle1 .= ' display:none;';

            $t .= "<span id=\"reset_$id\" style=\"$btnstyle1\" onclick=\"javascript:$onclreset\">".
                    "<img src=\"".url('/sys/images/small_red_cross.png').'" style="width: 16px; height: 16px;"/></span>';
            $t .= "<span id=\"set_$id\" style=\"$btnstyle2\" onclick=\"javascript:$onclset\">".
                "<img src=\"".url('/sys/images/small_green_plus.png').'" style="width: 16px; height: 16px;"/></span>';
            print $this->formatter->item($dta['before'].$t.$dta['after'],$dta['name']);
        }

        if($dta['type'] == 'radio')
        {
            $t = '';
            $cssid = $dta['id'];
            if($cssid == '')
                $cssid = 'r_'.$dta['name'].'_'.rand(1000,9999);
            foreach($dta['values'] as $h => $v)
            {
                $s = $dta['value'] == $h ? ' checked="checked"' : '';
                $t .= $dta['itemprefix'].
                    "<input type=\"radio\" name=\"".$dta['name']."\" value=\"$h\"".
                    ($dta['class']==''?'':' class="'.$dta['class'].'"').
                    ($dta['style']==''?'':' style="'.$dta['style'].'"').
                    ($dta['onclick']==''?'':' onclick="'.$dta['onclick'].'"').
                    ($dta['onchange']==''?'':' onchange="'.$dta['onchange'].'"').
                    ' id="'.$cssid.'_'.$h.'"'.
                    "$s><label for=\"".$cssid.'_'.$h."\">".$dta['itemlabelprefix'].$v.$dta['itemlabelsuffix']."</label>".$dta['itemsuffix'];
            }
            print $this->formatter->item($dta['before'].$t.$dta['after'],$dta['name']);
        }

        if($dta['type'] == 'textarea')
        {
            $t = '<textarea name="'.$dta['name'].'"'.
                 ($dta['disablercsize'] ? '' : (' rows="'.$dta['row'].'" cols="'.$dta['col'].'"')).
                 ($dta['class']==''?'':' class="'.$dta['class'].'"').
                 ($dta['style']==''?'':' style="'.$dta['style'].'"').
                 ($dta['onclick']==''?'':' onclick="'.$dta['onclick'].'"').
                 ($dta['id']==''?'':' id="'.$dta['id'].'"').
                 ($dta['required']?' required="required" aria-required="true"':'').
                 ($dta['autofocus']?' autofocus':'').
                 ($dta['softreadonly']?' readonly':'').
                 ($dta['placeholder']==''?'':' placeholder="'.$dta['placeholder'].'"').
                 ($dta['rawattributes']==''?'':' '.$dta['rawattributes']).
                 '>';
            $t .= $dta['value'];
            $t .= '</textarea>';
            print $this->formatter->item($dta['before'].$t.$dta['after'],$dta['name']);
        }

        if($dta['type'] == 'date' || $dta['type'] == 'dateu')
        {
            $custom_id = 'f_date_'.$dta['name'] . '_'. rand(10000,99999);

            $unknown = false;
            $parts = [];
            if(!preg_match('/(\d+)-(\d+)-(\d+)/',$dta['value'],$parts))
            {
                if($dta['type'] == 'dateu')
                    $unknown = true;
                $parts[1] = 1899;
                $parts[2] = 0;
                $parts[3] = 0;
            }
            $t = '<select name="'.$dta['name'].'_year" id="'.$custom_id.'_sel_y">';
            for($i=1900;$i<2050;++$i)
            {
                $s = $parts[1] == $i ? ' selected' : '';
                $t .= "<option value=\"$i\"$s>".($i==1899 ? ' ' : $i )."</option>";
            }
            $s = $parts[1] == 1899 ? ' selected' : '';
            $t .= "<option value=\"1899\"$s> </option>";
            $t .= '</select>';
            $t .= '<select name="'.$dta['name'].'_month" id="'.$custom_id.'_sel_m">';
            for($i=1;$i<13;++$i)
            {
                global $sys_data;
                $s = $parts[2] == $i ? ' selected' : '';
                $t .= "<option value=\"$i\"$s>" . ($i==0 ? ' ' : t($sys_data->month_names[intval($i)])) . "</option>";
            }
            $s = $parts[2] == 0 ? ' selected' : '';
            $t .= "<option value=\"0\"$s> </option>";
            $t .= '</select>';
            $t .= '<select name="'.$dta['name'].'_day" id="'.$custom_id.'_sel_d">';
            for($i=1;$i<32;++$i)
            {
                $s = $parts[3] == $i ? ' selected' : '';
                $t .= "<option value=\"$i\"$s>".($i==0?' ':$i)."</option>";
            }
            $s = $parts[3] == 0 ? ' selected' : '';
            $t .= "<option value=\"0\"$s> </option>";
            $t .= '</select>';

            if($dta['type'] == 'dateu')
            {
                $s = $unknown ? ' checked="checked"' : '';
                $t .= "<input type=\"hidden\" name=\"". $dta['name'] . "_set\" value=\"off\"/>".
                      "<input type=\"checkbox\" onclick=\"forms_set_reset_unknown_date('$custom_id');\" ".
                        "name=\"". $dta['name'] . "_set\" id=\"".$custom_id."_set\"$s/>".t('Unknown').
                      "<script>forms_set_reset_unknown_date('$custom_id');</script>";
            }
            print $this->formatter->item($dta['before'].$t.$dta['after'],$dta['name']);
        }

        return ob_get_clean();
    }

    public function get_readonly_item($dta)
    {
        ob_start();

        if($dta['type'] == 'raw')
            print $this->formatter->item($dta['before'].$dta['data'].$dta['after'],$dta['name']);

        if($dta['type'] == 'custom')
        {
            $str = $dta['before'] . call_user_func($dta['callback'],$this,$dta,true) . $dta['after'];
            print $this->formatter->item($str, $dta['name']);
        }

        if($dta['type'] == 'text' ||
           $dta['type'] == 'textarea' ||
           $dta['type'] == 'number' ||
           $dta['type'] == 'float' )
        {
            print $this->formatter->item($dta['before'].$dta['value'].$dta['after'],$dta['name']);
        }

        if($dta['type'] == 'upload')
        {
            if($dta['value'] != '')
            {
                $fobject = new File('');
                $fobject->load($dta['value'],true);
                print $this->formatter->item($dta['before'].l($fobject->name,$fobject->url).$dta['after'],$dta['name']);
            }
            else
            {
                print $this->formatter->item($dta['before'].'-'.$dta['after'],$dta['name']);
            }
        }

        if($dta['type'] == 'checkbox')
        {
            $v = $dta['value'] ? t('Yes') : t('No');
            print $this->formatter->item($dta['before'].$v.$dta['after'],$dta['name']);
        }

        if($dta['type'] == 'select' ||
           $dta['type'] == 'radio')
        {
            $dispv = '';
            foreach($dta['values'] as $h => $v)
            {
                if($dta['value'] == $h)
                    $dispv = $v;
            }
            print $this->formatter->item($dta['before'].$dispv.$dta['after'],$dta['name']);
        }

        if($dta['type'] == 'optselect')
        {
            $dispv = '';
            if($dta['value'] == '')
                $dispv = '-';
            else
                foreach($dta['values'] as $h => $v)
                {
                    if($dta['value'] == $h)
                        $dispv = $v;
                }
            print $this->formatter->item($dta['before'].$dispv.$dta['after'],$dta['name']);
        }

        if($dta['type'] == 'date' || $dta['type'] == 'dateu')
        {
            $dispv = '';
            $unknown = false;
            $parts = [];
            if(!preg_match('/(\d+)-(\d+)-(\d+)/',$dta['value'],$parts))
            {
                if($dta['type'] == 'dateu')
                    $unknown = true;
                $parts[1] = 1899;
                $parts[2] = 0;
                $parts[3] = 0;
            }
            if($dta['type'] == 'dateu' && $unknown)
            {
                $dispv = t('Unknown');
            }
            else
            {
                global $sys_data;
                if($parts[1] == 1899 && $parts[2] == 0 && $parts[3] == 0)
                    $dispv = '-- -- --';
                else
                    $dispv = $parts[1] . ' ' . t($sys_data->month_names[intval($parts[2])]) . ' '. $parts[3];
            }

            print $this->formatter->item($dta['before'].$dispv.$dta['after'],$dta['name']);
        }

        return ob_get_clean();
    }

    public function get_p($readonly = false)
    {
        ob_start();
        foreach($this->d as $dta)
            print $this->get_item($dta,$readonly);
        $this->d = [];
        return ob_get_clean();
    }

    public function get_part($first,$last,$readonly = false)
    {
        ob_start();
        $show = false;
        if($first === NULL)
            $show = true;
        foreach($this->d as $dta)
        {
            if($dta['name'] == $first)
                $show = true;
            if($show)
                print $this->get_item($dta,$readonly);
            if($last !== NULL && $dta['name'] == $last)
                $show = false;
        }
        return ob_get_clean();
    }

    public function get_one($name,$readonly = false)
    {
        ob_start();
        foreach($this->d as $dta)
        {
            if($dta['name'] == $name)
                print $this->get_item($dta,$readonly);
        }
        return ob_get_clean();
    }

    public function get_end($readonly = false)
    {
        ob_start();
        print '</form>';
        return $this->formatter->end_form(ob_get_clean());
    }
}

/** Check form source check value. Returns false if OK. */
function form_source_check($disable_auto_redirect = false)
{
    par_def('harassment-value','text1ns');
    if(par_is('harassment-value',getFormSalt()))
        return false;
    if(!$disable_auto_redirect)
        load_loc('error',t('Form validation error'));
    return true;
}

function par_date_def($name)
{
    par_def($name . '_year' ,'number0');
    par_def($name . '_month','number0');
    par_def($name . '_day'  ,'number0');
}

function par_date_ex($name)
{
    if(par_ex($name . '_year') && par_ex($name . '_month') && par_ex($name . '_day'))
        return true;
    return false;
}

function par_date($name,$define = false)
{
    if($define)
        par_date_def($name);
    if(!par_date_ex($name))
        return null;
    return sprintf("%04d-%02d-%02d",
                intval(par($name . '_year')),
                intval(par($name . '_month')),
                intval(par($name . '_day')));
}

class HtmlFormFormatter
{
    public $name;
    public function __construct()
    {
        $this->name = 'null_formatter';
    }

    function begin_form($txt)
    {
        return $txt;
    }

    function end_form($txt)
    {
        return $txt;
    }

    function item($txt,$name)
    {
        return $txt;
    }
}

function hook_forms_boot()
{
    global $site_config;
    global $speedform_formatters;

    $site_config->speedform_number_langtag = ""; //Defautl empty -> not added
    $speedform_formatters = [
        'null'  => 'HtmlFormFormatter',
        'div'   => 'Div_SpeedFormFormFormmater',
        'table' => 'Table_SpeedFormFormFormmater',
    ];
}

function hook_forms_init()
{
    global $speedform_handlers;
    global $field_repository;
    global $datadef_repository;

    $speedform_handlers = [];
    $field_repository = [];
    $datadef_repository = [];

    $speedform_handlers['keyn'] = [
             'sqlname'  => 'sfh_name_nomod',
             'sqlvalue' => 'sfh_value_numtype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_simple',
             'form'     => 'sfh_key_form',
             'loadpar'  => 'sfh_key_loadpar',
             'par_sec'  => 'number0',
             'sqltype'  => 'SERIAL',
             'validator'=> NULL, ];

    $speedform_handlers['keys'] = [
             'sqlname'  => 'sfh_name_nomod',
             'sqlvalue' => 'sfh_value_textype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_simple',
             'form'     => 'sfh_key_form',
             'loadpar'  => 'sfh_key_loadpar',
             'par_sec'  => 'text1ns',
             'sqltype'  => 'VARCHAR(32)',
             'validator'=> NULL,  ];

    $speedform_handlers['smalltext'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_textype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_simple',
             'form'     => 'sfh_smalltext_form',
             'loadpar'  => NULL,
             'par_sec'  => 'text4',
             'sqltype'  => 'VARCHAR(128)',
             'validator'=> NULL, ];

    $speedform_handlers['number'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_numtype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_simple',
             'form'     => 'sfh_number_form',
             'loadpar'  => NULL,
             'par_sec'  => 'numberi',
             'sqltype'  => 'NUMERIC(10)',
             'validator'=> 'sfh_number_validator', ];

    $speedform_handlers['largetext'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_textype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_simple',
             'form'     => 'sfh_largetext_form',
             'loadpar'  => NULL,
             'par_sec'  => 'text4',
             'sqltype'  => sql_t('longtext_type'),
             'validator'=> NULL, ];

    $speedform_handlers['txtselect'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_textype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_arrayvalues',
             'form'     => 'sfh_select_form',
             'loadpar'  => NULL,
             'par_sec'  => 'text1ns',
             'sqltype'  => 'VARCHAR(16)',
             'validator'=> NULL, ];

    $speedform_handlers['numselect'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_numtype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_arrayvalues',
             'form'     => 'sfh_select_form',
             'loadpar'  => NULL,
             'par_sec'  => 'number0',
             'sqltype'  => 'NUMERIC(4)',
             'validator'=> NULL, ];

    $speedform_handlers['float'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_numtype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_simple',
             'form'     => 'sfh_smalltext_form',
             'loadpar'  => NULL,
             'par_sec'  => 'number1ns',
             'sqltype'  => 'NUMERIC(15,5)',
             'validator'=> 'sfh_number_validator', ];

    $speedform_handlers['password'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_textype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_none',
             'form'     => 'sfh_password_form',
             'loadpar'  => NULL,
             'par_sec'  => 'text4',
             'sqltype'  => 'VARCHAR(128)',
             'validator'=> NULL, ];

    $speedform_handlers['static'] = [
             'sqlname'  => 'sfh_name_empty',
             'sqlvalue' => 'sfh_value_empty',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_none',
             'form'     => 'sfh_static_form',
             'loadpar'  => NULL,
             'par_sec'  => 'no',
             'sqltype'  => '',
             'validator'=> NULL, ];

    $speedform_handlers['rotext'] = [
             'sqlname'  => 'sfh_name_nomod',
             'sqlvalue' => 'sfh_value_textype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_simple',
             'form'     => 'sfh_key_form',
             'loadpar'  => NULL,
             'par_sec'  => 'text1ns',
             'sqltype'  => 'VARCHAR(128)',
             'validator'=> NULL,  ];

    $speedform_handlers['txtselect_intrange'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_textype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_simple',
             'form'     => 'sfh_select_intrange_form',
             'loadpar'  => NULL,
             'par_sec'  => 'text1ns',
             'sqltype'  => 'VARCHAR(16)',
             'validator'=> 'sfh_number_validator', ];

    $speedform_handlers['numselect_intrange'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_numtype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_simple',
             'form'     => 'sfh_select_intrange_form',
             'loadpar'  => NULL,
             'par_sec'  => 'number0',
             'sqltype'  => 'NUMERIC(4)',
             'validator'=> 'sfh_number_validator', ];

    $speedform_handlers['txtradio'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_textype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_arrayvalues',
             'form'     => 'sfh_radio_form',
             'loadpar'  => NULL,
             'par_sec'  => 'text1ns',
             'sqltype'  => 'VARCHAR(16)',
             'validator'=> NULL, ];

    $speedform_handlers['numradio'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_numtype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_arrayvalues',
             'form'     => 'sfh_radio_form',
             'loadpar'  => NULL,
             'par_sec'  => 'number0',
             'sqltype'  => 'NUMERIC(4)',
             'validator'=> NULL, ];

    $speedform_handlers['check'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_checktype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_check',
             'form'     => 'sfh_check_form',
             'loadpar'  => 'sfh_check_lpar',
             'par_sec'  => 'bool',
             'sqltype'  => 'BOOLEAN',
             'validator'=> NULL, ];

    $speedform_handlers['date'] = [
             'sqlname'  => 'sfh_name_simple',
             'defvaltr' => 'sfh_default_datetype',
             'sqlvalue' => 'sfh_value_datetype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_date',
             'form'     => 'sfh_date_form',
             'loadpar'  => 'sfh_date_lpar',
             'par_sec'  => 'number0',
             'sqltype'  => 'DATE',
             'validator'=> NULL, ];

    $speedform_handlers['dateu'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_datetype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_date',
             'form'     => 'sfh_dateu_form',
             'loadpar'  => 'sfh_dateu_lpar',
             'par_sec'  => 'number0',
             'sqltype'  => 'DATE',
             'validator'=> NULL, ];

    $speedform_handlers['timestamp_create'] = [
             'sqlname'  => 'sfh_name_timestamp',
             'sqlvalue' => 'sfh_value_timestamp',
             'directval'=> true,
             'dispval'  => 'sfh_dispval_simple',
             'form'     => 'sfh_timestamp_form',
             'loadpar'  => NULL,
             'par_sec'  => 'tst',
             'sqltype'  => sql_t('timestamp_noupd'),
             'validator'=> NULL, ];

    $speedform_handlers['timestamp_mod'] = [
             'sqlname'  => 'sfh_name_timestamp',
             'sqlvalue' => 'sfh_value_timestamp',
             'directval'=> true,
             'dispval'  => 'sfh_dispval_simple',
             'form'     => 'sfh_timestamp_form',
             'loadpar'  => NULL,
             'par_sec'  => 'tst',
             'sqltype'  => sql_t('timestamp_noupd'),
             'validator'=> NULL, ];

    $speedform_handlers['modifier_user'] = [
            'sqlname'  => 'sfh_name_simple',
            'sqlvalue' => 'sfh_value_modifieruser',
            'directval'=> false,
            'dispval'  => 'sfh_dispval_simple',
            'form'     => 'sfh_key_form',
            'loadpar'  => NULL,
            'par_sec'  => 'text5',
            'sqltype'  => 'VARCHAR(128)',
            'validator'=> NULL, ];

    $speedform_handlers['sqlnchoose'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_numtype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_sqlchoose',
             'form'     => 'sfh_sqlchoose_form',
             'loadpar'  => NULL,
             'par_sec'  => 'number0',
             'sqltype'  => 'NUMERIC(4)',
             'validator'=> NULL, ];

    $speedform_handlers['sqlschoose'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_textype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_sqlchoose',
             'form'     => 'sfh_sqlchoose_form',
             'loadpar'  => NULL,
             'par_sec'  => 'text1ns',
             'sqltype'  => 'VARCHAR(16)',
             'validator'=> NULL, ];

    $speedform_handlers['file'] = [
             'sqlname'  => 'sfh_name_simple',
             'sqlvalue' => 'sfh_value_textype',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_file',
             'form'     => 'sfh_file_form',
             'loadpar'  => 'sfh_file_lpar',
             'loadpar_deferred' => 'sfh_file_lpar_deferred',
             'par_sec'  => 'text4ns',
             'sqltype'  => 'VARCHAR(64)',
             'validator'=> NULL, ];

    $speedform_handlers['submit'] = [
             'sqlname'  => 'sfh_name_empty',
             'sqlvalue' => 'sfh_value_empty',
             'directval'=> false,
             'dispval'  => 'sfh_dispval_none',
             'form'     => 'sfh_submit_form',
             'loadpar'  => NULL,
             'par_sec'  => 'text4',
             'sqltype'  => '',
             'validator'=> NULL, ];

    $speedform_handlers = array_merge($speedform_handlers,run_hook('custom_formtypes'));

    $field_repository = array_merge($field_repository,run_hook('field_repository'));
    $datadef_repository = run_hook('datadef_repository');
}

function register_speedform_formatter($name,$classname)
{
    global $speedform_formatters;

    $rc = new ReflectionClass($classname);
    if(!$rc->isSubclassOf('HtmlFormFormatter'))
        throw new Exception("The registred SpeedformFormatter class name is not sublass of HtmlFormFormatter!\n");
    $speedform_formatters[$name] = $classname;
}

function registered_speedform_formatters()
{
    global $speedform_formatters;
    return array_keys($speedform_formatters);
}

function datadef_from_repository($name)
{
    global $datadef_repository;
    if(array_key_exists($name,$datadef_repository))
        return call_user_func($datadef_repository[$name]);
    return NULL;
}

function sfh_name_empty($field_def,$op)
{
    return '';
}

function sfh_name_simple($field_def,$op)
{
    if(isset($field_def['table']) && $op == 'SELECT')
        return $field_def['table'].'.'.$field_def['sql'];
    return $field_def['sql'];
}

function sfh_name_nomod($field_def,$op)
{
    if($op != 'SELECT')
        return '';
    if(isset($field_def['table']))
        return $field_def['table'].'.'.$field_def['sql'];
    return $field_def['sql'];
}

function sfh_value_empty($strip,$field_def,$op,$value)
{
    return "";
}

function sfh_value_textype($strip,$field_def,$op,$value)
{
    if(isset($field_def['optional']) && $field_def['optional'] == "yes" && $value == '')
    {
        if($strip)
            return null;
        return 'NULL';
    }
    if($strip)
        return $value;
    return "'$value'";
}

function sfh_value_numtype($strip,$field_def,$op,$value)
{
    if($value == '')
    {
        if($strip)
            return null;
        return 'NULL';
    }
    if($strip)
        return $value;
    return "$value";
}

function sfh_value_checktype($strip,$field_def,$op,$value)
{
    if($value !== 'off' && ($value || $value == 'on'))
    {
        if($strip)
            return 1;
        return 'TRUE';
    }
    if($strip)
        return 0;
    return 'FALSE';
}

function sfh_value_datetype($strip,$field_def,$op,$value)
{
    if($value == '' || $value == 'u')
    {
        if($strip)
            return null;
        return 'NULL';
    }
    if(preg_match('/\d+-\d+-\d+/',$value))
    {
        if($strip)
            return $value;
        return "date('$value')";
    }
    if($strip)
        return null;
    return 'NULL';
}

function sfh_value_modifieruser($strip,$field_def,$op,$value)
{
    global $user;
    if(isset($field_def["userdata"]) && $field_def["userdata"] == "fullname")
    {
        if($strip)
            return $user->name;
        return "'" . $user->name . "'";
    }
    if($strip)
        return $user->login;
    return "'".$user->login."'";
}

function sfh_static_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    $form->text($htmlname,$value,$opts);
}

function sfh_submit_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    $form->input('submit',$htmlname,$value,$opts);
}

function sfh_key_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    $form->hidden($htmlname,$value);
    if($value != '' && isset($field_def['link']))
    {
        $url = str_replace('<key>',$value,$field_def['link']);
        $linkedvalue = l($value,$url,['class'=>'f_key_linked f_key_link_'.$field_def['sql']]);
        $value = $linkedvalue;
    }
    $form->text($htmlname,$value,$opts);
}

function sfh_smalltext_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    $form->input('text',$htmlname,$value,$opts);
}

function sfh_number_form($field_def,$form,$value,$opts)
{
    global $site_config;
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    if(!array_key_exists('lang',$opts) && $site_config->speedform_number_langtag != '')
        $opts['lang'] = $site_config->speedform_number_langtag;
    $form->input('number',$htmlname,$value,$opts);
}

function sfh_password_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    //pointless to bring value if converter function is exists thus will reset the value
    if(isset($field_def['converter']) && $field_def['converter'] != '')
        $value = '';
    $form->input('password',$htmlname,$value,$opts);
}

function sfh_largetext_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    $form->textarea($htmlname,$value,
                (isset($field_def['row']) ? $field_def['row'] : 5),
                (isset($field_def['col']) ? $field_def['col'] : 35),
                $opts
              );
}

function sfh_select_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    $type = 'select';
    if(isset($field_def['optional']) && $field_def['optional'] == "yes")
        $type = 'optselect';
    $form->select($type,$htmlname,$value,$field_def['values'],$opts);
}

function sfh_select_intrange_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    $values = [];
    for($i = $field_def['start'];$i<=$field_def['end'];++$i)
        $values[$i] = $i;
    $form->select('select',$htmlname,$value,$values,$opts);
}

function sfh_radio_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    $form->select('radio',$htmlname,$value,$field_def['values'],$opts);
}

function sfh_check_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    $v = false;
    if($value !== 'off' && ($value || $value == 'on'))
        $v = true;
    $form->input('checkbox',$htmlname,$v,$opts);
}

function sfh_date_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    $form->datefield('date',$htmlname,$value,$opts);
}

function sfh_dateu_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    $form->datefield('dateu',$htmlname,$value,$opts);
}

function sfh_date_lpar($field_def,$tablename)
{
    global $speedform_handlers;

    $v = '';
    $vy = 1899;
    $vm = 0;
    $vd = 0;

    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];

    par_def($htmlname.'_year' ,$speedform_handlers[$field_def['type']]['par_sec']);
    par_def($htmlname.'_month',$speedform_handlers[$field_def['type']]['par_sec']);
    par_def($htmlname.'_day'  ,$speedform_handlers[$field_def['type']]['par_sec']);

    if(par_ex($htmlname.'_year') &&
       par_ex($htmlname.'_month') &&
       par_ex($htmlname.'_day')  )
    {
        $vy = par($htmlname.'_year');
        $vm = par($htmlname.'_month');
        $vd = par($htmlname.'_day');
        return "$vy-$vm-$vd";
    }
    return NULL;
}

function sfh_dateu_lpar($field_def,$tablename)
{
    global $speedform_handlers;

    $v = '';
    $vy = 1899;
    $vm = 0;
    $vd = 0;

    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];

    par_def($htmlname.'_year' ,$speedform_handlers[$field_def['type']]['par_sec']);
    par_def($htmlname.'_month',$speedform_handlers[$field_def['type']]['par_sec']);
    par_def($htmlname.'_day'  ,$speedform_handlers[$field_def['type']]['par_sec']);
    par_def($htmlname.'_set'  ,'bool');

    if(par_is($htmlname.'_set','on'))
        return 'u';

    if(par_ex($htmlname.'_year') &&
       par_ex($htmlname.'_month') &&
       par_ex($htmlname.'_day')  )
    {
        $vy = par($htmlname.'_year');
        $vm = par($htmlname.'_month');
        $vd = par($htmlname.'_day');
        return "$vy-$vm-$vd";
    }
    return NULL;
}

function sfh_key_loadpar($field_def,$tablename)
{
    global $speedform_handlers;

    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    par_def($htmlname,(isset($field_def['par_sec']) ? $field_def['par_sec'] : $speedform_handlers[$field_def['type']]['par_sec']));
    if(par_ex($htmlname))
    {
        if(par_is($htmlname,isset($field_def['default']) ? $field_def['default'] : '' ))
            return '';
        return par($htmlname);
    }
}

function sfh_name_timestamp($field_def,$op)
{
    if($field_def['type'] == 'timestamp_create' && $op == 'UPDATE')
        return '';

    if($op == 'UPDATE' && isset($field_def['autoupdate']) && $field_def['autoupdate'] == 'disable')
        return '';

    if(isset($field_def['table']) && $op == 'SELECT')
        return $field_def['table'].'.'.$field_def['sql'];
    return $field_def['sql'];
}

function sfh_value_timestamp($strip,$field_def,$op,$value)
{
    return sql_t('current_timestamp');
}

function sfh_timestamp_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    $form->hidden($htmlname,$value);
    $form->text($htmlname,$value,$opts);
}

function sfh_sqlchoose_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];

    $type = 'select';
    if(isset($field_def['optional']) && $field_def['optional'] == "yes")
        $type = "optselect";

    $values = [];
    $keyname    = $field_def['keyname'];
    $showpart   = $field_def['showpart'];
    $ctable     = $field_def['connected_table'];
    $where_order_part = $field_def['where_orderby_part'];
    $ra = sql_exec_fetchAll("SELECT $keyname,$showpart FROM $ctable $where_order_part");
    foreach($ra as $r)
        $values[$r[0]] = $r[1];
    $form->select($type,$htmlname,$value,$values,$opts);
}

function sfh_number_validator($field_def,$value)
{
    if(isset($field_def['minimum']) && $field_def['minimum'] > $value)
        return t('The "_field_" field is lower than minimum',['_field_' => $field_def['sql']]);
    if(isset($field_def['maximum']) && $field_def['maximum'] < $value)
        return t('The "_field_" field is higher than maximum',['_field_' => $field_def['sql']]);
    return '';
}

function sfh_file_form($field_def,$form,$value,$opts)
{
    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    $form->upload($htmlname,$value,$opts);
}

function sfh_check_lpar($field_def,$tablename)
{
    global $speedform_handlers;

    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    par_def($htmlname,(isset($field_def['par_sec']) ? $field_def['par_sec'] : $speedform_handlers[$field_def['type']]['par_sec']));
    if(par_ex($htmlname))
    {
        $v = par($htmlname);
        if($v === 'off')
            $v = false;
        if($v === 'on')
            $v = true;
        return $v;
    }
    return NULL;
}

function sfh_file_lpar($field_def,$tablename)
{
    global $speedform_handlers;

    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    par_def($htmlname,(isset($field_def['par_sec']) ? $field_def['par_sec'] : $speedform_handlers[$field_def['type']]['par_sec']));

    if($_FILES[$htmlname]['error'] == UPLOAD_ERR_OK)
    {
        if(isset($field_def['filetypes']))
        {
            $req_filetypes = explode(';', $field_def['filetypes']);
            if(!in_array($_FILES[$htmlname]['type'], $req_filetypes))
            {
                load_loc('error',
                    t('The uploaded file type is "_upltype_" not in "_reqtype_". Please upload the allowed kind of file',
                        ['_upltype_' => $_FILES[$htmlname]['type'],
                            '_reqtype_' => $field_def['filetypes']]),
                    t('Form validation error'));
            }
        }

        if(in_array($_FILES[$htmlname]['type'],
            ['image/jpeg', 'image/png', 'image/tiff', 'image/gif']
        ))
        {
            $check = getimagesize($_FILES[$htmlname]["tmp_name"]);
            if($check === false)
                load_loc('error', t('The uploaded file type is not image file. Please upload image file'),
                    t('Form validation error'));
        }

        return 'deferred_new_ufi';
    }

    if(par_is($htmlname.'_delete','delete'))
        return '';
    return NULL; //Will not set, the previous will left in table
}

function sfh_file_lpar_deferred($field_def,$tablename)
{
    global $speedform_handlers;

    $htmlname = $field_def['sql'];
    if(isset($field_def['htmlname']) && $field_def['htmlname'] != '')
        $htmlname = $field_def['htmlname'];
    if($_FILES[$htmlname]['error'] == UPLOAD_ERR_OK)
    {
        //File type validations done in sfh_file_lpar()

        $f = new File(isset($field_def['container']) ? $field_def['container'] : 'public');
        $f->addFromTemp($_FILES[$htmlname]['name'],
                        $_FILES[$htmlname]["tmp_name"],
                        (isset($field_def['subdir']) ? $field_def['subdir'] : ''),
                        ['sql' => $field_def['sql'], 'htmlname' => $htmlname, 'table' => $tablename]
        );
        return $f->ufi;
    }

    if(par_is($htmlname.'_delete','delete'))
        return '';
    return NULL; //Will not set, the previous will left in table
}

function sfh_dispval_none($field_def,$value)
{
    return '';
}

function sfh_dispval_simple($field_def,$value)
{
    return $value;
}

function sfh_dispval_arrayvalues($field_def,$value)
{
    if(isset($field_def['values'][$value]))
        return $field_def['values'][$value];
    return '';
}

function sfh_dispval_check($field_def,$value)
{
    if($value)
        return t('Yes');
    return t('No');
}

function sfh_dispval_date($field_def,$value)
{
    if($value == NULL || $value == 'u')
        return t('Unknown');
    return $value;
}

function sfh_dispval_sqlchoose($field_def,$value)
{
    if(isset($field_def['optional']) && $field_def['optional'] == "yes" && ($value == NULL || $value == ''))
        return '-';

    $keyname    = $field_def['keyname'];
    $showpart   = $field_def['showpart'];
    $ctable     = $field_def['connected_table'];
    $dv = sql_exec_single("SELECT $showpart FROM $ctable WHERE $keyname=:kk",[':kk' => $value]);
    return $dv;
}

function sfh_dispval_file($field_def,$value)
{
    if($value == '' || $value == NULL)
        return '';
    $f = file_load($value,true);
    if($f->mime == '')
        return '';
    return l($f->name,$f->url);
}

function sfh_default_datetype($field_def,$default_value)
{
    if($default_value == 'now')
        return date('Y-m-d');
    return $default_value;
}

class SpeedForm
{
    public $def;
    public $values;
    public $highlighted;
    public $validate_errortext;
    public $loaded_values;

    private $load_parameters_done;
    private $load_parameters_deferred_done;

    protected static $valid_form_modes = ['all','select','insert','update','delete'];
    protected static $valid_validate_modes = ['insert','update','delete'];
    protected static $skipwords = [
        'loadvalues' => ['all','sql'],
        'sqlselect'  => ['all','sql','select','exceptinsert'],
        'sqlupdate'  => ['all','sql','modify','update','exceptinsert','exceptdelete'],
        'sqlinsert'  => ['all','sql','modify','insert','exceptupdate','exceptdelete'],
        'all'        => ['all','visual'],
        'select'     => ['all','visual','select','exceptinsert','exceptupdate','exceptdelete'],
        'update'     => ['all','visual','modify','update','exceptinsert','exceptdelete'],
        'insert'     => ['all','visual','modify','insert','exceptupdate','exceptdelete'],
        'delete'     => ['all','visual','modify','delete','exceptinsert','exceptupdate'],
        'valupdate'  => ['all','modify','update','exceptinsert','exceptdelete'],
        'valinsert'  => ['all','modify','insert','exceptupdate','exceptdelete'],
        'valdelete'  => ['all','modify','delete','exceptinsert','exceptupdate'],
    ];

    public function __construct($definition)
    {
        global $speedform_handlers;

        $this->def = $definition;

        $this->highlighted = [];
        $this->validate_errortext = '';
        $this->values = [];
        $this->load_parameters_done = false;
        $this->load_parameters_deferred_done = false;

        foreach($this->def['fields'] as $idx => $f)
        {
            if(isset($speedform_handlers[$f['type']]['defvaltr']) &&
               $speedform_handlers[$f['type']]['defvaltr'] != NULL)
            {
                $dip = $speedform_handlers[$f['type']]['defvaltr'];

                $v = call_user_func($dip,$f,isset($f['default']) ? $f['default'] : '');
                $this->values[$f['sql']] = $v;
            }
            else
            {
                $this->values[$f['sql']] = isset($f['default']) ? $f['default'] : '';
            }
        }
        $this->loaded_values = $this->values;

        $pass = new stdClass();
        $pass->definition_ref = &$this->def;
        $pass->values_ref = &$this->values;
        run_hook("speedform_created",$pass);
    }

    public function index_of($sql)
    {
        foreach($this->def['fields'] as $idx => $f)
        {
            if($f['sql'] == $sql)
                return $idx;
        }
        return NULL;
    }

    public function get_key_name()
    {
        foreach($this->def['fields'] as $idx => $f)
        {
            if($f['type'] == 'keys' || $f['type'] == 'keyn')
                return $f['sql'];
        }
        return NULL;
    }

    public function get_key()
    {
        return $this->values[$this->get_key_name()];
    }

    public function get_key_sqlvalue($strip = false)
    {
        global $speedform_handlers;

        $keyname = $this->get_key_name();
        if($keyname == NULL)
            return NULL;
        $keyindex = $this->index_of($keyname);
        $fnc = $speedform_handlers[$this->def['fields'][$keyindex]['type']]['sqlvalue'];
        return call_user_func($fnc,$strip,$this->def['fields'][$keyindex],'SELECT',$this->values[$keyname]);
    }

    public function set_key($v)
    {
        $pass = new stdClass();
        $pass->definition = $this->def;
        $pass->newval_ref = &$v;
        run_hook("speedform_set_key",$pass);
        $this->values[$this->get_key_name()] = $v;
        return $this;
    }

    public function set_value($sql,$value)
    {
        $this->values[$sql] = $value;
    }

    public function get_value($sql)
    {
        return $this->values[$sql];
    }

    public function get_display_value($sql)
    {
        return $this->get_display_for_external_value($sql,$this->values[$sql]);
    }

    public function get_display_for_external_value($sql,$value)
    {
        global $speedform_handlers;

        $f = $this->get_field($sql);
        if($f == NULL)
            return '';
        $fnc = $speedform_handlers[$f['type']]['dispval'];
        $dispval = call_user_func($fnc,$f,$value);
        return $dispval;
    }

    public function &get_field($sql)
    {
        foreach($this->def['fields'] as $idx => $f)
        {
            if($f['sql'] == $sql)
                return $this->def['fields'][$idx];
        }
        return NULL;
    }

    /** Check post parameters if any action in progress
     *  @param string $action Action to test. Values can be: "submit" "insert" "update" "delete" "select" */
    public function in_action($action = 'submit')
    {
        foreach($this->def['fields'] as $idx => $f)
        {
            if($f['type'] == 'submit')
            {
                $htmlname = $f['sql'];
                if(isset($f['htmlname']) && $f['htmlname'] != '')
                    $htmlname = $f['htmlname'];

                par_def($htmlname,'text5');
                if( ($action == 'submit' || !isset($f['in_mode']) || $action == $f['in_mode']) &&
                    par_is($htmlname,$f['default'])
                  )
                    return true;
            }
        }
        return false;
    }

    public function in_specified_action($sql)
    {
        foreach($this->def['fields'] as $idx => $f)
        {
            if($f['type'] == 'submit' && $f['sql'] == $sql)
            {
                $htmlname = $f['sql'];
                if(isset($f['htmlname']) && $f['htmlname'] != '')
                    $htmlname = $f['htmlname'];

                par_def($htmlname,'text5');
                if(par_is($htmlname,$f['default']))
                    return true;
            }
        }
        return false;
    }

    public function load_parameters()
    {
        global $speedform_handlers;
        form_source_check();
        foreach($this->def['fields'] as $idx => $f)
        {
            $fnc = $speedform_handlers[$f['type']]['sqlname'];
            $sqlname = call_user_func($fnc,$f,'UPDATE');

            $htmlname = $f['sql'];
            if(isset($f['htmlname']) && $f['htmlname'] != '')
                $htmlname = $f['htmlname'];

            if($f['type'] == 'file')
            {
                par_def($htmlname.'_delete','text1ns');
            }

            if($sqlname != '')
            {
                $lp = $speedform_handlers[$f['type']]['loadpar'];
                if ($lp != NULL)
                {
                    $v = call_user_func($lp, $f, $this->def['table']);
                    if(isset($f['check_loaded_function']) && $f['check_loaded_function'] != '')
                        call_user_func($f['check_loaded_function'],$v,$f);
                    if(isset($f['converter']) && $f['converter'] != '')
                        $v = call_user_func($f['converter'],$v);
                    if($v !== NULL)
                        $this->values[$f['sql']] = $v;
                }
                else
                {
                    $skip = false;
                    if(isset($f['optional']) && $f['optional'] == "yes")
                    {
                        par_def($htmlname . '_sts', 'text1ns');
                        if(par_is($htmlname . '_sts', 'null'))
                        {
                            $skip = true;
                            $this->values[$f['sql']] = '';
                        }
                    }
                    par_def($htmlname, (isset($f['par_sec']) ? $f['par_sec'] : $speedform_handlers[$f['type']]['par_sec']));
                    if (par_ex($htmlname) && !$skip)
                    {
                        $v = par($htmlname);
                        if(isset($f['check_loaded_function']) && $f['check_loaded_function'] != '')
                            call_user_func($f['check_loaded_function'],$v,$f);
                        if(isset($f['converter']) && $f['converter'] != '')
                            $v = call_user_func($f['converter'],$v);
                        $this->values[$f['sql']] = $v;
                    }
                }
            }
        }

        $pass = new stdClass();
        $pass->definition = $this->def;
        $pass->values_ref = &$this->values;
        run_hook("speedform_parameters_loaded",$pass);

        $this->load_parameters_done = true;
        return $this;
    }

    protected function load_parameters_deferred()
    {
        if(!$this->load_parameters_done)
            return;
        if($this->load_parameters_deferred_done)
            return;

        $files_marked_to_delete = [];
        global $speedform_handlers;
        foreach($this->def['fields'] as $idx => $f)
        {
            $fnc = $speedform_handlers[$f['type']]['sqlname'];
            $sqlname = call_user_func($fnc,$f,'UPDATE');

            $htmlname = $f['sql'];
            if(isset($f['htmlname']) && $f['htmlname'] != '')
                $htmlname = $f['htmlname'];

            if($sqlname != '')
            {
                $lp = NULL;
                if(isset($speedform_handlers[$f['type']]['loadpar_deferred']))
                    $lp = $speedform_handlers[$f['type']]['loadpar_deferred'];
                if($lp != NULL)
                {
                    $v = call_user_func($lp, $f, $this->def['table']);
                    if(isset($f['converter']) && $f['converter'] != '')
                        $v = call_user_func($f['converter'],$v);
                    if($v !== NULL)
                        $this->values[$f['sql']] = $v;
                }
            }

            //In the special case the field is file
            if($f['type'] == 'file')
            {
                if(par_is($htmlname.'_delete','delete'))
                    array_push($files_marked_to_delete,$f['sql']);
            }
        }

        if(!empty($files_marked_to_delete))
            $this->delete_previous_files($files_marked_to_delete);

        $this->load_parameters_deferred_done = true;
        return;
    }

    public function load_values($vals)
    {
        global $speedform_handlers;
        foreach($this->def['fields'] as $idx => $f)
        {
            if(isset($f['skip']) && in_array($f['skip'],SpeedForm::$skipwords['loadvalues']))
                continue;

            $fnc = $speedform_handlers[$f['type']]['sqlname'];
            $str = call_user_func($fnc,$f,'SELECT');
            if($str != '' && isset($vals[$f['sql']]))
                $this->values[$f['sql']] = $vals[$f['sql']];
        }
        $this->loaded_values = $this->values;

        $pass = new stdClass();
        $pass->definition = $this->def;
        $pass->values_ref = &$this->values;
        run_hook("speedform_values_loaded",$pass);
        return $this;
    }

    protected function delete_previous_files($afsql)
    {
        foreach($afsql as $fsql)
        {
            $sql = 'SELECT '. $fsql .
                   ' FROM ' . $this->def['table'] .
                   ' WHERE ' . $this->get_key_name() . '=:keyval';

            $ufi = sql_exec_single($sql,[':keyval' => $this->get_key_sqlvalue(true)]);
            if($ufi != '')
            {
                $file_object = new File('public');
                if($file_object->load($ufi,true) != NULL)
                    $file_object->remove();
            }
        }
    }

    public function clean_before_delete()
    {
        foreach($this->def['fields'] as $idx => $f)
            if($f['type'] == 'file' && !isset($f['ondelete']) && $f['ondelete'] != 'keep')
            {
                if($this->values[$f['sql']] != '')
                {
                    $file_object = new File('');
                    if($file_object->load($this->values[$f['sql']],true) != NULL)
                        $file_object->remove();
                }
            }
    }

    public function sql_select_part($tablename = '')
    {
        global $speedform_handlers;
        $sf = '';
        foreach($this->def['fields'] as $idx => $f)
        {
            if(isset($f['skip']) && in_array($f['skip'],SpeedForm::$skipwords['sqlselect']))
                continue;

            if(!isset($f['table']) || $tablename == '' || $tablename == $f['table'])
            {
                $fnc = $speedform_handlers[$f['type']]['sqlname'];
                $str = call_user_func($fnc, $f, 'SELECT');
                if($str != '')
                    $sf .= ($sf == '' ? '' : ',') . "$str";
            }
        }
        return $sf;
    }

    public function sql_update_part($tablename = '')
    {
        return $this->sql_update_part_CMN($tablename,0);
    }

    public function sql_update_part_PQ($tablename = '')
    {
        return $this->sql_update_part_CMN($tablename,1);
    }

    public function sql_update_part_PA($tablename = '')
    {
        return $this->sql_update_part_CMN($tablename,2);
    }

    protected function sql_update_part_CMN($tablename,$mode) // 0-normal,1-PQ,2-PA
    {
        global $speedform_handlers;
        $uf = '';
        $pa = [];
        $this->load_parameters_deferred();
        foreach($this->def['fields'] as $idx => $f)
        {
            if(isset($f['skip']) && in_array($f['skip'],SpeedForm::$skipwords['sqlupdate']))
                continue;

            if(!isset($f['table']) || $tablename == '' || $tablename == $f['table'])
            {
                $fnc = $speedform_handlers[$f['type']]['sqlname'];
                $sqlname = call_user_func($fnc,$f,'UPDATE');
                $sqlnamemod = str_replace('.','_',$sqlname);
                if($sqlname != '')
                {
                    if($mode == 0) //Normal mode
                    {
                        $fnc = $speedform_handlers[$f['type']]['sqlvalue'];
                        $sqlvalue = call_user_func($fnc, false, $f, 'UPDATE', $this->values[$f['sql']]);
                        $uf .= ($uf == '' ? '' : ',') . "$sqlname=$sqlvalue";
                    }
                    if($mode == 1) //PQ
                    {
                        if($speedform_handlers[$f['type']]['directval'])
                        {
                            $fnc = $speedform_handlers[$f['type']]['sqlvalue'];
                            $sqlvalue = call_user_func($fnc,false,$f,'UPDATE',$this->values[$f['sql']]);
                            $uf .= ($uf == '' ? '' : ',') . "$sqlname=$sqlvalue";
                        }
                        else
                        {
                            $uf .= ($uf == '' ? '' : ',') . "$sqlname=:phu_$sqlnamemod";
                        }
                    }
                    if($mode == 2) //PA
                    {
                        if(!$speedform_handlers[$f['type']]['directval'])
                        {
                            $fnc = $speedform_handlers[$f['type']]['sqlvalue'];
                            $sqlvalue = call_user_func($fnc, true, $f, 'UPDATE', $this->values[$f['sql']]);
                            $pa[':phu_' . $sqlnamemod] = $sqlvalue;
                        }
                    }
                }
            }
        }
        if($mode == 2)
            return $pa;
        return $uf;
    }

    public function sql_insert_part($tablename = '',array $extra = [])
    {
        return $this->sql_insert_part_CMN($tablename,0,$extra);
    }

    public function sql_insert_part_PQ($tablename = '',array $extra = [])
    {
        return $this->sql_insert_part_CMN($tablename,1,$extra);
    }

    public function sql_insert_part_PA($tablename = '',array $extra = [])
    {
        return $this->sql_insert_part_CMN($tablename,2,$extra);
    }

    protected function sql_insert_part_CMN($tablename,$mode,array $extra) // 0-normal,1-PQ,2-PA
    {
        global $speedform_handlers;
        $npart = '';
        $vpart = '';
        $pa = [];
        $this->load_parameters_deferred();
        foreach($this->def['fields'] as $idx => $f)
        {
            if(isset($f['skip']) && in_array($f['skip'],SpeedForm::$skipwords['sqlinsert']))
                continue;

            if(!isset($f['table']) || $tablename == '' || $tablename == $f['table'])
            {
                $fnc = $speedform_handlers[$f['type']]['sqlname'];
                $sqlname = call_user_func($fnc,$f,'INSERT');
                $sqlnamemod = str_replace('.','_',$sqlname);
                if($sqlname != '')
                {
                    if($mode == 0) //Normal
                    {
                        $fnc = $speedform_handlers[$f['type']]['sqlvalue'];
                        $sqlvalue = call_user_func($fnc, false, $f, 'INSERT', $this->values[$f['sql']]);
                        $npart .= ($npart == '' ? '' : ',') . "$sqlname";
                        $vpart .= ($vpart == '' ? '' : ',') . "$sqlvalue";
                    }
                    if($mode == 1) //PQ
                    {
                        $npart .= ($npart == '' ? '' : ',') . "$sqlname";
                        if($speedform_handlers[$f['type']]['directval'])
                        {
                            $fnc = $speedform_handlers[$f['type']]['sqlvalue'];
                            $sqlvalue = call_user_func($fnc,false,$f,'INSERT',$this->values[$f['sql']]);
                            $vpart .= ($vpart == '' ? '' : ',') . "$sqlvalue";
                        }
                        else
                        {
                            $vpart .= ($vpart == '' ? '' : ',') . ":phi_$sqlnamemod";
                        }
                    }
                    if($mode == 2) //PA
                    {
                        if(!$speedform_handlers[$f['type']]['directval'])
                        {
                            $fnc = $speedform_handlers[$f['type']]['sqlvalue'];
                            $sqlvalue = call_user_func($fnc, true, $f, 'INSERT', $this->values[$f['sql']]);
                            $pa[':phi_' . $sqlnamemod] = $sqlvalue;
                        }
                    }
                }
            }
        }
        foreach($extra as $idx => $val)
        {
            if($mode == 0 || $mode == 1) //Normal || PQ
            {
                $npart .= ($npart == '' ? '' : ',') . "$idx";
                $vpart .= ($vpart == '' ? '' : ',') . "$val";
            }
        }
        if($mode == 2)
            return $pa;
        return "($npart) VALUES($vpart)";
    }

    public function sql_select_string($tablename = '')
    {
        return 'SELECT ' . $this->sql_select_part($tablename) .
               ' FROM ' . $this->def['table'] .
               ' WHERE ' . $this->get_key_name() . '='. $this->get_key_sqlvalue();
    }

    public function sql_select_string_PQ($tablename = '')
    {
        return 'SELECT ' . $this->sql_select_part($tablename) .
               ' FROM ' . $this->def['table'] .
               ' WHERE ' . $this->get_key_name() . '=:ph_'. $this->get_key_name();
    }

    public function sql_select_string_PA($tablename = '')
    {
        return [':ph_' . $this->get_key_name() => $this->get_key_sqlvalue(true) ];
    }

    public function sql_update_string($tablename = '')
    {
        return 'UPDATE ' . ($tablename != '' ? $tablename : $this->def['table']) .
               ' SET ' . $this->sql_update_part($tablename) .
               ' WHERE ' . $this->get_key_name() . '='. $this->get_key_sqlvalue();
    }

    public function sql_update_string_PQ($tablename = '')
    {
        return 'UPDATE ' . ($tablename != '' ? $tablename : $this->def['table']) .
               ' SET ' . $this->sql_update_part_PQ($tablename) .
               ' WHERE ' . $this->get_key_name() . '=:ph_'. $this->get_key_name();
    }

    public function sql_update_string_PA($tablename = '')
    {
        $pa = $this->sql_update_part_PA($tablename);
        $pa[':ph_'.$this->get_key_name()] = $this->get_key_sqlvalue(true);
        return $pa;
    }

    public function sql_insert_string($tablename = '',array $extra = [])
    {
        return 'INSERT into ' . ($tablename != '' ? $tablename : $this->def['table']) .
               $this->sql_insert_part($tablename,$extra);
    }

    public function sql_insert_string_PQ($tablename = '',array $extra = [])
    {
        return 'INSERT into ' . ($tablename != '' ? $tablename : $this->def['table']) .
        $this->sql_insert_part_PQ($tablename,$extra);
    }

    public function sql_insert_string_PA($tablename = '',array $extra = [])
    {
        return $this->sql_insert_part_PA($tablename,$extra);
    }

    public function sql_create_string()
    {
        global $speedform_handlers;
        ob_start();
        print "CREATE TABLE ".$this->def['table']."(\n";
        $ff = true;
        foreach($this->def['fields'] as $i => $f)
        {
            if(!isset($f['table']) || $f['table'] == '' || $this->def['table'] == $f['table'])
            {
                if($speedform_handlers[$f['type']]['sqltype'] != '')
                {
                    print ($ff ? "" : ",\n") . "\t" . $f['sql'] . ' ' . $speedform_handlers[$f['type']]['sqltype'];
                    $ff = false;
                }
            }
        }
        print "\n);\n";
        return ob_get_clean();
    }

    public function sql_create_schema()
    {
        global $speedform_handlers;
        ob_start();
        $r = [];
        $r['tablename'] = $this->def['table'];

        $cols = [];
        foreach($this->def['fields'] as $i => $f)
        {
            if(!isset($f['table']) || $f['table'] == '' || $r['tablename'] == $f['table'])
            {
                if(isset($f['sqlcreatetype']))
                {
                    $cols[$f['sql']] = $f['sqlcreatetype'];
                }
                else
                {
                    if($speedform_handlers[$f['type']]['sqltype'] != '')
                        $cols[$f['sql']] = $speedform_handlers[$f['type']]['sqltype'];
                }
            }
        }

        $r['columns'] = $cols;
        return $r;
    }

    /** Do form expirity check according to the timespamp_mod field.
     *  Return: 0 - Check ok, doesn't require refresh 
     *          1 - Check indicates the form is expired. Needs refresh.
     *          2 - Error, Can't run the check  */
    public function do_expirity_check($auto_handle = true)
    {
        $msn = '';
        $msv = '';
        $dsv = '';
        foreach($this->def['fields'] as $idx => $f)
        {
            if($f['type'] == 'timestamp_mod')
            {
                $msn = $f['sql'];
                $msv = $this->values[$f['sql']];
                $dsv = isset($f['default']) ? $f['default'] : '';
            }
        }
        if($msn == '' || $msv == '')
            return 2;

        if($msv == $dsv) //The form is not queried from the database (may insert)
            return 2;

        $tablename = $this->def['table'];
        $keyname   = $this->get_key_name();
        $keyvalue  = $this->get_key_sqlvalue(true);
        if($tablename == '' || $keyname == '' || $keyvalue == '')
            return 2;
        $res = sql_exec_fetch("SELECT $msn FROM $tablename WHERE $keyname=:ph_keyval",[':ph_keyval' => $keyvalue]);
        $csv = $res[$msn];
        if($csv == $msv)
            return 0;

        if($auto_handle)
            load_loc('error',
                t('The form is expired. Please refresh the page!')."<br/>\n".
                 t('(The form is loaded _msv_ but modified _csv_)',
                    ['_msv_'=>$msv,'_csv_'=>$csv]),
                t('Expired form error'));
        return 1;
    }

    public function do_validate($mode,$auto_handle = true)
    {
        global $speedform_handlers;

        if(!in_array($mode,SpeedForm::$valid_validate_modes))
            throw new Exception("The SpeedForm::do_validate() method's mode parameter contains bad value!\n".
                                " Accepted values: ".implode(','.SpeedForm::$valid_validate_modes));
        $i = 1;
        $this->highlighted = [];
        $this->validate_errortext = '';

        $pass = new stdClass();
        $pass->definition = $this->def;
        $pass->values_ref = &$this->values;
        $pass->highlighted_ref = &$this->highlighted;
        $pass->validate_errortext_ref = &$this->validate_errortext;
        run_hook("speedform_before_validate",$pass);
        if($this->validate_errortext != '' && $auto_handle)
            load_loc('error',"<pre>".$this->validate_errortext."</pre>",t('Form validation error'));
        foreach($this->def['fields'] as $idx => $f)
        {
            if(isset($f['skip']) && in_array($f['skip'],SpeedForm::$skipwords['val'.$mode]))
                continue;

            //regex check
            if(isset($f['check_regex']))
            {
                if(is_array($f['check_regex']))
                {
                    foreach($f['check_regex'] as $regex => $errortext)
                    {
                        if(preg_match($regex,$this->values[$f['sql']]) != 1)
                        {
                            $this->highlighted[$f['sql']] = 1;
                            if($errortext != '')
                                $this->validate_errortext .= "$i: ".t($errortext,['_field_' => $f['sql'],
                                                                                  '_value_' => $this->values[$f['sql']]
                                                                                 ])."\n";
                            else
                                $this->validate_errortext .= "$i: " .
                                        t('Validation error on field: _field_ (_value_)',
                                                    ['_field_' => $f['sql'],
                                                     '_value_' => $this->values[$f['sql']]]) . "\n";
                            ++$i;
                        }
                    }
                }
                else
                {
                    if(preg_match($f['check_regex'],$this->values[$f['sql']]) != 1)
                    {
                        $this->highlighted[$f['sql']] = 1;
                        $this->validate_errortext .= "$i: " . 
                                        t('Validation error on field: _field_ (_value_)',
                                                    ['_field_' => $f['sql'],
                                                     '_value_' => $this->values[$f['sql']]]) . "\n";
                        ++$i;
                    }
                }
            }

            //empty check
            if(isset($f['check_noempty']))
            {
                if($this->values[$f['sql']] === NULL || $this->values[$f['sql']] == '')
                {
                    $this->highlighted[$f['sql']] = 1;
                    if($f['check_noempty'] == '')
                        $this->validate_errortext .= "$i: " . 
                                t('Field "_field_" cannot be empty',['_field_' => $f['sql']]) . "\n";
                    else
                        $this->validate_errortext .= "$i: ". $f['check_noempty'] . "\n";
                    ++$i;
                }
            }

            //custom checks
            $vf = $speedform_handlers[$f['type']]['validator'];
            if($vf != NULL)
            {
                $v = call_user_func($vf,$f,$this->values[$f['sql']]);
                if($v != '')
                {
                    $this->highlighted[$f['sql']] = 1;
                    $this->validate_errortext .= "$i: $v\n";
                    ++$i;
                }
            }
        }

        if(count($this->highlighted) > 0)
        {
            if($auto_handle)
                load_loc('error',"<pre>".$this->validate_errortext."</pre>",t('Form validation error'));
            return 1;
        }
        return 0;
    }

    public function generate_form($mode = 'all', $tablename = '')
    {
        global $speedform_handlers;
        global $speedform_formatters;

        if(!in_array($mode,SpeedForm::$valid_form_modes))
            throw new Exception("The SpeedForm::generate_form() method's mode parameter contains bad value!\n".
                                " Accepted values: ".implode(','.SpeedForm::$valid_form_modes));

        $ff = NULL;
        if(isset($this->def['show']))
        {
            if(!isset($speedform_formatters[$this->def['show']]))
                throw new Exception("The requested SpeedformFormatter class is unknown!\n".
                    " Accepted values: ".implode(','.array_keys($speedform_formatters)));
            $fclassname = $speedform_formatters[$this->def['show']];
            $ff = new $fclassname($this->def,$this->highlighted);
        }
        if($ff === NULL) //fallback
            $ff = new HtmlFormFormatter();

        $n = isset($this->def['name']) ? $this->def['name'] : 'unnamed';
        $form = new HtmlForm("speedform:$n",$ff);

        foreach($this->def['fields'] as $idx => $f)
        {
            if(!isset($f['hide']) || !$f['hide'])
            {
                if((!isset($f['skip']) || !in_array($f['skip'],SpeedForm::$skipwords[$mode])) &&
                   ($f['type'] != 'submit' || !isset($f['in_mode']) || $mode == 'all' || $f['in_mode'] == $mode))
                {
                    if(!isset($f['table']) || $tablename == '' || $tablename == $f['table'])
                    {
                        $opts = [];
                        if(isset($f['form_options']))
                            $opts = $f['form_options'];
                        if(isset($f['readonly']) && $f['readonly'])
                            $opts['readonly'] = true;
                        $fnc = $speedform_handlers[$f['type']]['form'];
                        call_user_func($fnc,$f,$form,$this->values[$f['sql']],$opts);
                    }
                }
            }
        }
        $pass = new stdClass();
        $pass->definition = $this->def;
        $pass->form_ref = &$form;
        run_hook("speedform_form_generated",$pass);
        return $form;
    }

    public function do_select()
    {
        $pq = $this->sql_select_string_PQ();
        $pa = $this->sql_select_string_PA();
        $v = sql_exec_fetch($pq,$pa);
        $this->load_values($v);
    }

    public function do_insert($disable_checks = false)
    {
        global $db;
        if(!$disable_checks)
        {
            $this->do_validate('insert'); //auto_handle=true by default
        }

        $pq = $this->sql_insert_string_PQ();
        $pa = $this->sql_insert_string_PA();
        sql_exec($pq,$pa);

        $keyname = $this->get_key_name();
        $k = $this->get_field($keyname);
        $id_hint = isset($k['sql_sequence_name']) ? $k['sql_sequence_name'] : $keyname;
        if($db->servertype == "pgsql" && isset($k['pgsql_sql_sequence_name']))
            $id_hint = $k['pgsql_sql_sequence_name'];
        if($db->servertype == "mysql" && isset($k['mysql_sql_sequence_name']))
            $id_hint = $k['mysql_sql_sequence_name'];
        $ikey = sql_getLastInsertId($this->def['table'],$keyname,$id_hint);

        if($k != NULL && isset($k['keyprefix']))
            $ikey = $k['keyprefix'] . $ikey;
        if($k != NULL && isset($k['keysuffix']))
            $ikey =  $ikey .$k['keysuffix'];

        return $ikey;
    }

    public function do_update($disable_checks = false)
    {
        if(!$disable_checks)
        {
            $this->do_validate('update'); //auto_handle=true by default
            $this->do_expirity_check(); //auto_handle=true by default
        }

        $us = $this->sql_update_string_PQ();
        $up = $this->sql_update_string_PA();
        sql_exec($us,$up);
    }

    public function do_create()
    {
        $cs = $this->sql_create_string();
        sql_exec($cs);
    }
}


class HtmlFormFormatter_WithDefinition extends HtmlFormFormatter
{
    public $def;

    public function __construct($definition)
    {
        $this->name = 'definitionbased_speedform_formatter';
        $this->def = $definition;
    }

    public function get_definition_of_field($name)
    {
        foreach($this->def['fields'] as $f)
        {
            if(isset($f['htmlname']) && $f['htmlname'] != '')
            {
                if($f['htmlname'] == $name || (isset($f['table']) && $name == $f['table'] . '.' . $f['htmlname']))
                    return $f;
            }
            else
            {
                if($f['sql'] == $name || (isset($f['table']) && $name == $f['table'] . '.' . $f['sql']))
                    return $f;
            }
        }
        return [];
    }
}

class Table_SpeedFormFormFormmater extends HtmlFormFormatter_WithDefinition
{
    public $highl;
    public function __construct($definition,$highlighted = [])
    {
        parent::__construct($definition);
        $this->name = 'table_speedform_formatter';
        $this->highl = $highlighted;
    }

    public function begin_form($txt)
    {
        $before = '';
        if(isset($this->def['before']))
            $before = $this->def['before'];

        $b = 0;
        $tc = 'f_gener_table f_gener_table_'.$this->def['table'];
        if(isset($this->def['table_class']))
            $tc .= ' '.$this->def['table_class'];
        if(isset($this->def['table_border']))
            $b = $this->def['table_border'];
        $stylepart = '';
        if(isset($this->def['table_style']))
            $stylepart = ' style="'.$this->def['table_style'].'"';
        $bgc = '';
        if(isset($this->def['color']))
            $bgc = ' bgcolor="'.$this->def['color'].'"';
        $t = "<table border=\"$b\" class=\"$tc\"$stylepart$bgc>";
        return $before . $txt . $t;
    }

    public function end_form($txt)
    {
        $after = '';
        if(isset($this->def['after']))
            $after = $this->def['after'];

        $t = '</table>';
        return $t . $txt . $after;
    }

    public function item($txt,$name)
    {
        $f = $this->get_definition_of_field($name);

        $cs = false;
        ob_start();
        if(isset($f['before']))
            print $f['before'];
        if(!isset($f['formatters']) || $f['formatters'] == 'before' || $f['formatters'] == 'all')
        {
            $style = '';
            $lc = 'f_tgen_line f_tgen_line_'.$name;

            if(array_key_exists($name,$this->highl) && $this->highl[$name] == 1)
            {
                $style = ' style="border: 8px solid red;"';
                $lc .= " validation_highlighted";
            }
            $tc = 'f_gen_title';
            $vc = 'f_gen_value';

            if(isset($f['line_class']))
                $lc .= ' ' . $f['line_class'];
            if(isset($f['title_class']))
                $tc .= ' ' . $f['title_class'];
            if(isset($f['value_class']))
                $vc .= ' ' . $f['value_class'];

            print '<tr'.(isset($f['color'])?' bgcolor="'.$f['color'].'"':'')."$style class=\"$lc\">";
            if(isset($f['text']))
            {
                print '<td class="'.$tc.'">'.$f['text'].'</td>';
            }
            else
            {
                $cs = true;
            }
            print '<td'.($cs?' colspan="2" ':'').' class="'.$vc.'">';

            if(isset($f['centered'])  && $f['centered'])
                print "<center>";
        }

        if(isset($f['prefix']))
            print $f['prefix'];
        if(isset($f['neval_prefix']) && $txt != '')
            print $f['neval_prefix'];
        print $txt;
        if(isset($f['neval_suffix']) && $txt != '')
            print $f['neval_suffix'];
        if(isset($f['suffix']))
            print $f['suffix'];

        if(isset($f['script']))
            print '<script>'.$f['script'].'</script>';

        if(!isset($f['formatters']) || $f['formatters'] == 'after' || $f['formatters'] == 'all')
        {
            if(isset($f['centered']) && $f['centered'])
                print "</center>";
            print '</td></tr>';
        }
        if(isset($f['after']))
            print $f['after'];
        return ob_get_clean();
    }
}

class Div_SpeedFormFormFormmater extends HtmlFormFormatter_WithDefinition
{
    public $highl;
    public $last_fs;
    public $has_fs;
    public $default_css_class;
    public function __construct($definition,$highlighted = [])
    {
        parent::__construct($definition);
        $this->name = 'div_speedform_formatter';
        $this->highl = $highlighted;
        $this->last_fs = '';
        $this->has_fs = false;
        $this->default_css_class = 'f_gendiv_defaultcodkepstyle';
        if(isset($this->def['default_csstop_class']) && $this->def['default_csstop_class'] != '')
            $this->default_css_class = $this->def['default_csstop_class'];
    }

    public function begin_form($txt)
    {
        $before = '';
        if(isset($this->def['before']))
            $before = $this->def['before'];

        $dc = 'f_gener_div f_gener_div_'.$this->def['table'].' '.$this->default_css_class;
        if(isset($this->def['div_class']))
            $dc .= ' '.$this->def['div_class'];
        $t = "<div class=\"$dc\">";
        return $before . $txt . $t;
    }

    public function end_form($txt)
    {
        $after = '';
        $closefieldset = '';

        if($this->last_fs != '')
        {
            $closefieldset = "</div></fieldset></div>"; // .fieldset-body-div-wrapper , fieldset , .fieldset-div-wrapper
            $this->last_fs = '';
        }

        if(isset($this->def['after']))
            $after = $this->def['after'];

        $t = '</div>';

        $fieldset_script = '';
        if($this->has_fs && isset($this->def['collapsable_fieldsets']) && $this->def['collapsable_fieldsets'])
        {
            $fcls = 'f_gener_div_' . $this->def['table'];
            $fieldset_script = "<script>
                jQuery(document).ready(function() {
                    jQuery('.$fcls .fieldset-body-div-wrapper').each(function() {
                        var id = jQuery(this).attr('id');
                        if(!id.startsWith('fs-bdy-'))
                            return;
                        id = id.replace('fs-bdy-','');
                        if(jQuery(this).hasClass('collapsed'))
                        {
                            jQuery(this).hide();
                            jQuery('.$fcls #fs-lpfx-'+id).html('⮞ ');
                        }
                        else
                            jQuery('.$fcls #fs-lpfx-'+id).html('⮟ ');
                    });
                    jQuery('.$fcls .fs-legend-line').click(function() {
                        var id = jQuery(this).attr('id');
                        if(!id.startsWith('fs-lgd-'))
                            return;
                        id = id.replace('fs-lgd-','');
                        var fs_body = jQuery('#fs-bdy-'+id);
                        if(fs_body.hasClass('collapsed'))
                        {
                            fs_body.show('fast');
                            fs_body.removeClass('collapsed');
                            jQuery('.$fcls #fs-lpfx-'+id).html('⮟ ');
                        }
                        else
                        {
                            fs_body.hide('fast');
                            fs_body.addClass('collapsed');
                            jQuery('.$fcls #fs-lpfx-'+id).html('⮞ ');
                        }

                    });
                });
            </script>";
        }
        return $closefieldset . $t . $txt . $after . $fieldset_script;
    }

    public function item($txt,$name)
    {
        $f = $this->get_definition_of_field($name);

        ob_start();
        if(isset($f['fieldset']) && $f['fieldset'] != '') //field in field set
        {
            if($f['fieldset'] != $this->last_fs)
            {
                $bdy_extra_classes = '';
                if($this->last_fs != '')
                    print "</div></fieldset></div>"; // .fieldset-body-div-wrapper , fieldset , .fieldset-div-wrapper
                print '<div class="fieldset-div-wrapper"><fieldset>';
                $fs_text = $f['fieldset'];
                if(isset($f['fieldset_text']) && $f['fieldset_text'] != '')
                    $fs_text = $f['fieldset_text'];
                print '<div class="fieldset-legend-div-wrapper">'.
                        '<legend id="fs-lgd-'.$f['fieldset'].'" class="fs-legend-line">'.
                          '<span id="fs-lpfx-'.$f['fieldset'].'"></span>'.
                          $fs_text.
                        '</legend></div>';
                if(isset($f['fieldset_body_extraclass']))
                    $bdy_extra_classes = $f['fieldset_body_extraclass'];
                if($bdy_extra_classes != '')
                    $bdy_extra_classes = ' '.$bdy_extra_classes;
                print '<div class="fieldset-body-div-wrapper'.$bdy_extra_classes.'" id="fs-bdy-'.$f['fieldset'].'">';
                $this->last_fs = $f['fieldset'];
                $this->has_fs = true;
            }
        }
        else if($this->last_fs != '') //need close field set
        {
            print "</div></fieldset></div>"; // .fieldset-body-div-wrapper , fieldset , .fieldset-div-wrapper
            $this->last_fs = '';
        }

        if(isset($f['before']))
                print $f['before'];
        if(!isset($f['formatters']) || $f['formatters'] == 'before' || $f['formatters'] == 'all')
        {
            $style = '';
            if(isset($f['color']))
            $style .= ' background-color:'.$f['color'].';';

            $lc = 'f_gen_line f_gen_line_'.$this->def['table'].'_'.$name;
            if(array_key_exists($name,$this->highl) && $this->highl[$name] == 1)
            {
                $style .= ' border: 8px solid red;';
                $lc .= " validation_highlighted";
            }

            if(isset($f['line_class']))
                $lc .= ' ' . $f['line_class'];
            $tc = 'f_gen_title';
            if(isset($f['title_class']))
                $tc .= ' ' . $f['title_class'];
            $vc = 'f_gen_value';
              if(isset($f['value_class']))
                $vc .= ' ' . $f['value_class'];

            print "<div style=\"$style\" class=\"$lc\">";
            if(isset($f['text']))
            {
                print "<div class=\"$tc\">".$f['text'].'</div>';
            }
            print "<div class=\"$vc\">";
            if(isset($f['centered']) && $f['centered'])
                print "<center>";
        }

        if(isset($f['prefix']))
            print $f['prefix'];
        if(isset($f['neval_prefix']) && $txt != '')
            print $f['neval_prefix'];
        print $txt;
        if(isset($f['neval_suffix']) && $txt != '')
            print $f['neval_suffix'];
        if(isset($f['suffix']))
            print $f['suffix'];

        if(isset($f['script']))
            print '<script>'.$f['script'].'</script>';

        if(!isset($f['formatters']) || $f['formatters'] == 'after' || $f['formatters'] == 'all')
        {
            if(isset($f['centered'])  && $f['centered'])
                print "</center>";
            print '</div>';

            if(isset($this->def['div_c_afterv']) && $this->def['div_c_afterv'])
                print '<div class="c"></div>';
            if(isset($f['description']) && $f['description'] != '')
                print '<div class="field-description">'.$f['description'].'</div>';
            print '</div>';
            if(isset($this->def['div_c_afterl']) && $this->def['div_c_afterl'])
                print '<div class="c"></div>';
        }
        if(isset($f['after']))
            print $f['after'];
        return ob_get_clean();
    }
}

function speedform_available_types()
{
    global $speedform_handlers;
    return array_keys($speedform_handlers);
}

/** Get the field definition part of the data definition array according to the $sqlname */
function speedform_get_field_array($definition,$sqlname)
{
    if(isset($definition['fields']))
        foreach($definition['fields'] as $f => $v)
            if($v['sql'] == $sqlname)
                return $v;
    return [];
}

/** Get the field's specific attribute value from the data definition array according to the $sqlname */
function speedform_get_field_attribute($definition,$sqlname,$attributename)
{
    if(isset($definition['fields']))
        foreach($definition['fields'] as $f => $v)
            if($v['sql'] == $sqlname)
            {
                if(isset($definition['fields'][$f][$attributename]))
                    return $definition['fields'][$f][$attributename];
                return null;
            }
    return null;
}

/** Get the field's display value to the passed value */
function speedform_get_field_display_value($definition,$sqlname,$value)
{
    global $speedform_handlers;
    $f = speedform_get_field_array($definition,$sqlname);
    if(isset($f['type']))
        if(array_key_exists($f['type'],$speedform_handlers))
        {
            $typedef = $speedform_handlers[$f['type']];
            if(isset($typedef['dispval']) && $typedef['dispval'] != null && $typedef['dispval'] != '')
            {
                $fnc = $speedform_handlers[$f['type']]['dispval'];
                $dispval = call_user_func($fnc,$f,$value);
                return $dispval;
            }
        }
    return $value;
}

/** Change the sort order sql string by option array usable by to_table function
 * @param mixed $sp The original sort parameter string
 * @param array $options The customisation orders of the table by to_table
 * @return string Returns the modified sort string
 * @see to_table
 * @package forms */
function to_sqlsort($sp,array $options=[])
{
    if(isset($options[$sp]['sqlsort']))
        return $options[$sp]['sqlsort'];

    $oo = '';
    if(isset($options['#output_object']))
        $oo = $options['#output_object'];
    else
        $oo = 'table';

    if(isset($options['#fields']))
        foreach($options['#fields'] as $f)
            if(substr($f,0,1) == '#')
            {
                $iopt = get_field_repository_definition(substr($f, 1));
                if(isset($iopt['sqlname:'.$oo]) && $iopt['sqlname:'.$oo] == $sp)
                {
                    if(isset($iopt['sqlsort:'.$oo]))
                        return $iopt['sqlsort:'.$oo];
                    if(isset($iopt['sqlsort']))
                        return $iopt['sqlsort'];
                }

                if(isset($iopt['sqlname']) && $iopt['sqlname'] == $sp)
                {
                    if(isset($iopt['sqlsort:'.$oo]))
                        return $iopt['sqlsort:'.$oo];
                    if(isset($iopt['sqlsort']))
                        return $iopt['sqlsort'];
                }

                if(substr($f,1) == $sp)
                {
                    if(isset($iopt['sqlsort:'.$oo]))
                        return $iopt['sqlsort:'.$oo];
                    if(isset($iopt['sqlsort']))
                        return $iopt['sqlsort'];
                }
            }
    return $sp;
}

/** Generates a html table from an executed sql query or 2dimensional array
 * The output is highly customizable by the $options array.
 * @param mixed $dataobj The executed sql query or a 2dimensional array which holds the data cells
 * @param array $options The customisation orders of the table
 * @param array $results An optional array to get some data back from generation
 * @return string The generated html table code
 * @package forms */
function to_table($dataobj,array $options=[],array &$results = null)
{
    $is_array = false;
    $is_pdo = false;
    $beforetext = '';
    $aftertext = '';
    $def_headeropts = [];
    $def_cellopts = [];

    if($dataobj === null)
        return '';
    if(is_array($dataobj))
        $is_array = true;
    if(is_object($dataobj) && get_class($dataobj) == "PDOStatement")
        $is_pdo = true;

    $oo = '';
    $table = null;
    if($results != null && isset($results['target']) && $results['target'] != null)
    {
        $table = $results['target'];
        if(isset($options['#output_object']))
            $oo = $options['#output_object'];
    }
    else
    {
        if(isset($options['#output_object']))
        {
            $oo = $options['#output_object'];
            $table = h($oo, isset($options['#name']) ? $options['#name'] : 'Generated');
        }
        else
        {
            $oo = 'table';
            $table = new HtmlTable('array_generated');
        }
        if(isset($options['#tableopts']))
            $table->opts($options['#tableopts']);
    }

    if(isset($options['#before']) && is_callable($options['#before']))
    {
        ob_start();
        $beforetext = call_user_func($options['#before'], $table);
        $ostr = ob_get_clean();
        if($beforetext == '' && $ostr != '')
            $beforetext = $ostr;
    }

    if(isset($options['#default_headeropts']))
        $def_headeropts = $options['#default_headeropts'];
    if(isset($options['#default_cellopts']))
        $def_cellopts = $options['#default_cellopts'];

    $rowcount = 0;
    $first = true;
    $end = false;

    $columns = []; //columns to show
    $rcv_columns = []; //columns in received data
    if(isset($options["#fields"]))
        $columns = $options["#fields"];

    $r_prefixes = [];
    $r_suffixes = [];
    $r_cellopts = [];
    $r_valuecallback = [];
    $r_sqlname = [];
    $r_show = [];

    while(true)
    {
        if($is_pdo)
        {
            $r = $dataobj->fetch(PDO::FETCH_NAMED);
            if(!$r)
                $end = true;
        }
        if($is_array)
        {
            if($first)
                $r = reset($dataobj);
            else
                $r = next($dataobj);
            if($r === FALSE)
                $end = true;
        }

        //There is no more data row, exiting...
        if($end)
        {
            if($results !== null)
                $results['rowcount'] = $rowcount;
            if(isset($options['#after']) && is_callable($options['#after']))
            {
                ob_start();
                $aftertext = call_user_func($options['#after'], $table);
                $astr = ob_get_clean();
                if($aftertext == '' && $astr != '')
                    $aftertext = $astr;
            }
            if(isset($options['#return_disabled']) && $options['#return_disabled'])
                return null;
            return $beforetext.$table->get().$aftertext;
        }

        if($first)
        {
            $rcv_columns = array_keys($r);
            if(empty($columns))
                $columns = $rcv_columns;

            foreach($columns as $hitem)
            {
                $iopt = [];
                if(substr($hitem,0,1) == '#')
                    $iopt = get_field_repository_definition(substr($hitem,1));
                else
                    if(isset($options[$hitem]))
                        $iopt = $options[$hitem];

                $r_show[$hitem] = true;
                if(isset($iopt['skip:'.$oo]))
                {
                    if($iopt['skip:'.$oo])
                        $r_show[$hitem] = false;
                }
                else
                {
                    if(isset($iopt['skip']) && $iopt['skip'])
                        $r_show[$hitem] = false;
                }

                $r_sqlname[$hitem] = optSel($iopt , ['sqlname:'.$oo , 'sqlname'],
                            (substr($hitem,0,1) == '#' ? substr($hitem,1) : $hitem ) );

                if($r_show[$hitem])
                {
                    $headertext = optSel($iopt,['headertext:'.$oo,'headertext'],$hitem);
                    $headertextcallback = optSel($iopt,['headertextcallback:'.$oo,'headertextcallback'],'');
                    if(is_callable($headertextcallback))
                        $txt = call_user_func($headertextcallback,$hitem);
                    else
                        $txt = $headertext;

                    $table->head($txt, optSel($iopt,['headeropts:'.$oo,'headeropts'],$def_headeropts));
                    $r_prefixes[$hitem] = optSel($iopt,['cellprefix:'.$oo,'cellprefix'],'');
                    $r_suffixes[$hitem] = optSel($iopt,['cellsuffix:'.$oo,'cellsuffix'],'');

                    $celloptscallback = optSel($iopt,['celloptscallback:'.$oo,'celloptscallback'],'');
                    if(is_callable($celloptscallback))
                        $r_cellopts[$hitem] = call_user_func($celloptscallback,$hitem);
                    else
                        $r_cellopts[$hitem] = optSel($iopt,['cellopts:'.$oo,'cellopts'],$def_cellopts);

                    $r_valuecallback[$hitem] = optSel($iopt,['valuecallback:'.$oo,'valuecallback'],NULL);
                }
            }

            //If I redefined the fields to show (means that $columns differs from $rcv_columns),
            //I even check query received headers to load repository controlled fields
            //in order to resolve names to build correct $r_mapped below for callbacks
            foreach($rcv_columns as $rname)
                if(substr($rname,0,1) == '#')
                    if(!isset($r_sqlname[$rname]))
                    {
                        $iopt2 = get_field_repository_definition(substr($rname,1));
                        $r_sqlname[$rname] = isset($iopt2['sqlname']) ? $iopt2['sqlname'] : substr($rname, 1);
                    }

            $first = false;
        }

        //This $r_mapped array is built for callback called below
        //It contains the specified sqlname indexes instead of field repository or other fictive name indexes
        $r_mapped = [];
        $r_mapped['__rownumber__'] = $rowcount + 1;
        if(isset($options['#callback_array_external']))
            $r_mapped['__external__'] = $options['#callback_array_external'];
        foreach($rcv_columns as $rname)
        {
            $keyname = isset($r_sqlname[$rname]) ? $r_sqlname[$rname] : $rname;
            $r_mapped[$keyname] = $r[$rname];
        }

        $lineskip = false;
        if(isset($options['#lineskip_callback']))
            $lineskip = call_user_func($options['#lineskip_callback'],$r_mapped);

        if(!$lineskip)
        {
            $tr_opts = [];
            if(isset($options['#lineoptions_callback']))
                $tr_opts = call_user_func($options['#lineoptions_callback'], $r_mapped);
            $table->nrow($tr_opts);

            foreach($columns as $k)
                if($r_show[$k])
                {
                    if($r_valuecallback[$k] !== NULL)
                    {
                        $value = call_user_func($r_valuecallback[$k], $r_mapped, $k);
                        $table->cell($r_prefixes[$k] . $value . $r_suffixes[$k], $r_cellopts[$k]);
                    } else
                    {
                        $keyname = $k;
                        if(!isset($r[$k]))
                            $keyname = $r_sqlname[$k];
                        $table->cell(isset($r[$keyname]) ? ($r_prefixes[$k] . $r[$keyname] . $r_suffixes[$k]) : '', $r_cellopts[$k]);
                    }
                }

            ++$rowcount;
        }
    }
}

/** Retruns the first set option or the default value if non of them were set */
function optSel($options,$keys,$default)
{
    foreach($keys as $t)
    {
        if(isset($options[$t]))
            return $options[$t];
    }
    return $default;
}

function get_field_repository_definition($name)
{
    global $field_repository;

    $p = strpos($name,'#');
    if($p !== FALSE)
        $name = substr($name,0,$p);
    $r = $field_repository[$name];
    if(!isset($r['base']))
        return $r;
    $ro = get_field_repository_definition($r['base']);
    foreach($r as $i => $v)
    {
        if(isset($ro[$i]) && is_array($ro[$i]) && is_array($v))
            $ro[$i] = array_merge($ro[$i],$v);
        else
            $ro[$i] = $v;
    }
    return $ro;
}

class DynTable
{
    protected $def;
    protected $data;
    protected $inSQL;
    protected $id;
    protected $numtype_formatstr;

    protected $readonly;
    public function __construct($definition)
    {
        $this->data = [];
        $this->def = $definition;
        $this->zeroData();
        $this->inSQL = false;
        $this->id = 'temp_'.time().'_'.rand(1000,9999);
        $this->numtype_formatstr = '%.2f';
        if(isset($this->def['numeric_format_string']))
            $this->numtype_formatstr = $this->def['numeric_format_string'];
        $this->readonly;
    }

    public function zeroData()
    {
        foreach($this->def['datacells'] as $name => $opts)
        {
            $d = null;
            if(!isset($opts['type']))
            {
                if($this->def['default_type'] == 'num') $d = 0;
                if($this->def['default_type'] == 'str') $d = '';
            }
            else
            {
                if($opts['type'] == 'num') $d = 0;
                if($opts['type'] == 'str') $d = '';
            }
            $this->data[$name] = $d;
        }
    }

    public function isReadonly()
    {
        return $this->readonly;
    }

    public function setReadonly($ro)
    {
        $this->readonly = $ro;
    }

    public function __set($name, $value)
    {
        $this->setData($name,$value,'__set');
    }

    public function __get($name)
    {
        if($name == 'dyntable_id')
            return $this->id;
        if($name == 'dyntable_definition')
            return $this->def;
        if(array_key_exists($name,$this->data))
            return $this->data[$name];
        return null;
    }

    public function __isset($name)
    {
        if(array_key_exists($name,$this->data))
            return true;
        return false;
    }

    function isInSql()
    {
        return $this->inSQL;
    }

    public function arithmeticForAllNumeric($operation,$operand)
    {
        foreach($this->def['datacells'] as $name => $opts)
        {
            if( (!isset($opts['type']) && $this->def['default_type'] == 'num') ||
                (isset($opts['type']) && $opts['type'] == 'num')                  )
            {
                if(is_numeric($operand) && !is_object($operand) && !is_array($operand))
                {
                    if($operation == '+') $this->data[$name] += $operand;
                    if($operation == '-') $this->data[$name] -= $operand;
                    if($operation == '*') $this->data[$name] *= $operand;
                    if($operation == '/') $this->data[$name] /= $operand;
                }
                if(is_object($operand) && is_subclass_of($operand,'DynTable'))
                {
                    if($operation == '+') $this->data[$name] += $operand->$name;
                    if($operation == '-') $this->data[$name] -= $operand->$name;
                    if($operation == '*') $this->data[$name] *= $operand->$name;
                    if($operation == '/') $this->data[$name] /= $operand->$name;
                }
            }
        }
    }

    public function collectForAllNumeric($operation)
    {
        $sum = 0.0;
        $max = 0.0;
        $min = null;
        $count = 0;
        $nzcount = 0;
        foreach($this->def['datacells'] as $name => $opts)
        {
            if( (!isset($opts['type']) && $this->def['default_type'] == 'num') ||
                (isset($opts['type']) && $opts['type'] == 'num')                  )
            {
                $sum += $this->data[$name];
                if($max == null || $max < $this->data[$name])
                    $max = $this->data[$name];
                if($min == null || $min > $this->data[$name])
                    $min = $this->data[$name];
                if($this->data[$name] != 0.0)
                    ++$nzcount;
                ++$count;
            }
        }
        if($operation == 'sum')          return $sum;
        if($operation == 'count')        return $count;
        if($operation == 'nonzerocount') return $nzcount;
        if($operation == 'max')          return $max;
        if($operation == 'min')          return $min;
        if($operation == 'avg' && $count != 0)
            return $sum/$count;
        return null;
    }

    public function getHtml($readonly = false,$skipheaders = false)
    {
        $divclass = 'dyntable_'.$this->id;
        ob_start();
        print "<div class=\"$divclass\">";
        print $this->html_table_body($readonly || $this->readonly,$skipheaders);
        print "</div>";

        $ajaxurl = '';
        $ajaxsub = 'none';
        $titletext = 'Edit field value';
        $btntext   = 'Save';
        if(isset($this->def['popupedit_ajaxurl']))
            $ajaxurl = url($this->def['popupedit_ajaxurl']);
        if(isset($this->def['popupedit_ajaxsubtype']))
            $ajaxsub = url($this->def['popupedit_ajaxsubtype']);
        if(isset($this->def['popupedit_title']))
            $titletext = $this->def['popupedit_title'];
        if(isset($this->def['popupedit_btntext']))
            $btntext = $this->def['popupedit_btntext'];
        //We have to add the following js code even in readonly mode because the table can change through ajax interface
        //so we need to memorize the base in case the readonly table turns to read-write.
        print "<script>
            jQuery(document).ready(function() {
              fireup_dyntableedit({ajaxurl:'$ajaxurl',ajaxsubtype:'$ajaxsub',id:'$this->id',title:'$titletext',btntext:'$btntext'});
            });
        </script>";

        return ob_get_clean();
    }

    public function html_table_body($readonly = false,$skipheaders = false)
    {
        ob_start();
        print $this->html_table_body_before();
        $border = "0";
        $tclass = "dyntable_table";
        $hclass = "dyntable_table_col";
        $rclass = "dyntable_table_row";
        $cclass = "dyntable_table_cell";
        $missclass = "dyntable_table_missingcell";
        if(isset($this->def['table_border']))
            $border = $this->def['table_border'];
        if(isset($this->def['table_class']))
            $tclass = $this->def['table_class'];
        if(isset($this->def['table_columnlabel_class']))
            $hclass = $this->def['table_columnlabel_class'];
        if(isset($this->def['table_rowlabel_class']))
            $rclass = $this->def['table_rowlabel_class'];
        if(isset($this->def['table_cell_class']))
            $cclass = $this->def['table_cell_class'];
        if(isset($this->def['table_missing_cell_class']))
            $missclass = $this->def['table_missing_cell_class'];

        print "<table border=\"$border\" class=\"$tclass\" data-id=\"$this->id\">";
        if(!$skipheaders)
        {
            print "<tr>";
            print "<th></th>";
            foreach($this->def['cols'] as $cIdx => $colname)
                print "<th class=\"$hclass\">$colname</th>";
            print "</tr>";
        }
        foreach($this->def['rows'] as $rIdx => $rowname)
        {
            print "<tr>";
            if(!$skipheaders)
                print "<td class=\"$rclass\">$rowname</td>";
            foreach($this->def['cols'] as $cIdx => $colname)
            {
                $put = false;
                foreach($this->def['datacells'] as $name => $opts)
                    if($opts['row'] == $rIdx && $opts['col'] == $cIdx)
                    {
                        $ac = ($readonly || $this->readonly) ? "staticcell" : "dyncell";
                        print "<td class=\"$cclass $ac\" id=\"dync_$name\"
                               data-rn=\"$rowname\" data-cn=\"$colname\">";

                        if(isset($opts['type']))
                            $t = $opts['type'];
                        else
                            $t = $this->def['default_type'];

                        if($t == 'str')
                            print $this->data[$name];
                        if($t == 'num')
                        {
                            $value = 0;
                            if($this->data[$name] != 0.0)
                                $value = sprintf($this->numtype_formatstr, $this->data[$name]);
                            print $value;
                        }
                        print "</td>";
                        $put = true;
                        break 1;
                    }
                if(!$put)
                {
                    print "<td class=\"$missclass\"></td>";
                }
            }
            print "</tr>";
        }
        print "</table>";
        print $this->html_table_body_after();
        return ob_get_clean();
    }

    protected function html_table_body_before()
    {
        return '';
    }

    protected function html_table_body_after()
    {
        return '';
    }

    public function generateIntoTable($receiverobj,$skipheaders = false)
    {
        $this->generateIntoTable_before($receiverobj);

        $ch_opts    = ['type' => 'uni','t' => 'str','border' => 'all','background-color' => '#eeeeee'];
        $rh_opts    = ['type' => 'uni','t' => 'str','border' => 'all','background-color' => '#eeeeee'];
        $sc_opts    = ['type' => 'uni','t' => 'str','border' => 'all','background-color' => '#ffffff'];
        $nc_opts    = ['type' => 'uni','t' => 'num','border' => 'all','background-color' => '#ffffff'];
        $missc_opts = ['type' => 'uni','t' => 'str','border' => 'all','background-color' => '#777777'];

        if(isset($this->def['gentable_colheader_opts']))
            $ch_opts = $this->def['gentable_colheader_opts'];
        if(isset($this->def['gentable_rowheader_opts']))
            $rh_opts = $this->def['gentable_rowheader_opts'];
        if(isset($this->def['gentable_strcell_opts']))
            $sc_opts = $this->def['gentable_strcell_opts'];
        if(isset($this->def['gentable_numcell_opts']))
            $nc_opts = $this->def['gentable_numcell_opts'];
        if(isset($this->def['gentable_misscell_opts']))
            $missc_opts = $this->def['gentable_misscell_opts'];

        if(!$skipheaders)
        {
            $receiverobj->cell('',$ch_opts);
            foreach($this->def['cols'] as $cIdx => $colname)
                $receiverobj->cell($colname,$ch_opts);
            $receiverobj->nrow();
        }

        foreach($this->def['rows'] as $rIdx => $rowname)
        {
            if(!$skipheaders)
                $receiverobj->cell($rowname,$rh_opts);
            foreach($this->def['cols'] as $cIdx => $colname)
            {
                $put = false;
                foreach($this->def['datacells'] as $name => $opts)
                    if($opts['row'] == $rIdx && $opts['col'] == $cIdx)
                    {
                        if(isset($opts['type']))
                            $t = $opts['type'];
                        else
                            $t = $this->def['default_type'];

                        if($t == 'str')
                            $receiverobj->cell($this->data[$name],$sc_opts);
                        if($t == 'num')
                        {
                            $value = 0;
                            if($this->data[$name] != 0.0)
                                $value = sprintf($this->numtype_formatstr, $this->data[$name]);
                            $receiverobj->cell($value,$nc_opts);
                        }

                        $put = true;
                        break 1;
                    }
                if(!$put)
                {
                    $receiverobj->cell('',$missc_opts);
                }
            }
            $receiverobj->nrow();
        }

        $this->generateIntoTable_after($receiverobj);
    }
    protected function generateIntoTable_before($receiverobj)
    {
    }
    protected function generateIntoTable_after($receiverobj)
    {
    }

    public function setData($name,$toValue,$method = '')
    {
        if(!array_key_exists($name,$this->def['datacells']))
            return false;
        if($this->data[$name] == $toValue)
            return false;
        $this->data[$name] = $toValue;
        return true;
    }

    public function setDataFromAjax($name,$toValue,$method = 'ajax')
    {
        if(substr($name,0,5) == 'dync_')
            return $this->setData(substr($name,5),$toValue,$method);
        return false;
    }


    public function sql_create_schema()
    {
        $cols = [];
        $numeric_sqltype  = 'NUMERIC(15,5)';
        $string_sqltype   = 'VARCHAR(128)';
        $idfield_sqltype  = 'SERIAL';

        if(isset($this->def['sqltype_numeric']))
            $numeric_sqltype = $this->def['sqltype_numeric'];
        if(isset($this->def['sqltype_string']))
            $string_sqltype = $this->def['sqltype_string'];
        if(isset($this->def['sqltype_idfield']))
            $idfield_sqltype = $this->def['sqltype_idfield'];

        $cols[$this->def['idfield']] = $idfield_sqltype;
        foreach($this->def['datacells'] as $name => $opts)
        {
            $t = $string_sqltype;
            if(!isset($opts['type']))
            {
                if($this->def['default_type'] == 'num') $t = $numeric_sqltype;
                if($this->def['default_type'] == 'str') $t = $string_sqltype;
            }
            else
            {
                if($opts['type'] == 'num') $t = $numeric_sqltype;
                if($opts['type'] == 'str') $t = $string_sqltype;
            }
            $cols[isset($opts['sql']) ? $opts['sql'] : $name] = $t;
        }
        return [
            'tablename' => $this->def['sqltable'],
            'columns' => $cols,
        ];
    }

    public function readFromDatabase($id)
    {
        $this->zeroData();
        $q = db_query($this->def['sqltable'])
            ->get($this->def['idfield'])
            ->cond_fv($this->def['idfield'],$id,'=');
        foreach($this->def['datacells'] as $name => $opts)
            $q->get(isset($opts['sql']) ? $opts['sql'] : $name);
        $this->readFromDatabase_preaction($q);
        $r = $q->execute_and_fetch();
        if(!isset($r[$this->def['idfield']]))
            return true;
        $this->id = $r[$this->def['idfield']];
        foreach($this->def['datacells'] as $name => $opts)
            $this->data[$name] = $r[isset($opts['sql']) ? $opts['sql'] : $name];
        $this->readFromDatabase_postaction($r);
        $this->inSQL = true;
        return false;
    }

    protected function readFromDatabase_preaction($queryobject)
    {
    }

    protected function readFromDatabase_postaction($resultobject)
    {
    }

    public function saveToDatabase()
    {
        if($this->id == '')
            return true;
        $q = db_update($this->def['sqltable'])
            ->cond_fv($this->def['idfield'],$this->id,'=');
        $this->saveToDatabase_preaction($q);
        foreach($this->def['datacells'] as $name => $opts)
            $q->set_fv((isset($opts['sql']) ? $opts['sql'] : $name),$this->data[$name]);
        $q->execute();
        $this->saveToDatabase_postaction();
        $this->inSQL = true;
        return false;
    }

    protected function saveToDatabase_preaction($queryobject)
    {
    }

    protected function saveToDatabase_postaction()
    {
    }

    public function storeToDatabase()
    {
        global $db;
        $q = db_insert($this->def['sqltable']);
        foreach($this->def['datacells'] as $name => $opts)
            $q->set_fv((isset($opts['sql']) ? $opts['sql'] : $name),$this->data[$name]);
        $this->storeToDatabase_preaction($q);
        $q->execute();
        $new_id = (isset($this->def['table_key_prefix']) ? $this->def['table_key_prefix'] : '') .
            sql_getLastInsertId(
                        $this->def['sqltable'],
                        $this->def['idfield'],
                        isset($this->def['table_seq_name']) ? $this->def['table_seq_name'] : ''
                    ) .
            (isset($this->def['table_key_suffix']) ? $this->def['table_key_suffix'] : '');
        $this->id = $new_id;
        $this->storeToDatabase_postaction();
        $this->inSQL = true;
    }

    protected function storeToDatabase_preaction($queryobject)
    {
    }

    protected function storeToDatabase_postaction()
    {
    }

    public function ajax_add_refreshHtmlTable($readonly = false,$skipheaders = false)
    {
        ajax_add_html(".dyntable_".$this->id,$this->html_table_body($readonly,$skipheaders));
        ajax_add_run("re_fireup_dyntableedit",$this->id);
    }
}

function hook_forms_required_sql_schema()
{
    global $datadef_repository;
    $t = [];
    foreach($datadef_repository as $ddname => $ddef)
    {
        $datastruct = call_user_func($ddef);
        if(isset($datastruct['sql_schema_bypass']) && $datastruct['sql_schema_bypass'])
            continue;
        //Here we will autodetect the type of the data definition structure by the content
        if(isset($datastruct['fields']))
        {
            $sf = new SpeedForm($datastruct);
            $t['DDefRepo: '.$ddname] = $sf->sql_create_schema();
        }
        if(isset($datastruct['datacells']))
        {
            $dt = new DynTable($datastruct);
            $t['DDefRepo: '.$ddname] = $dt->sql_create_schema();
        }
    }
    return $t;
}

function hook_forms_documentation($section)
{
    $docs = [];
    if($section == "codkep")
    {
        $docs[] = ['forms' => ['path' => 'sys/doc/forms.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
        $docs[] = ['tablegen' => ['path' => 'sys/doc/tablegen.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
        $docs[] = ['totable' => ['path' => 'sys/doc/totable.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
        $docs[] = ['dyntable' => ['path' => 'sys/doc/dyntable.mdoc','index' => false, 'imagepath' => '/sys/doc/images']];
    }
    return $docs;
}

/**
 * This hook runs before the HtmlTable class data rendered. It can modify the table data.
 * @package forms */
function _HOOK_table_get() {}

/**
 * This hook runs before the HtmlForm class started to render the form. It can modify some data.
 * @package forms */
function _HOOK_form_get_start() {}

/**
 * This hook runs every time when HtmlForm item is started to render. It can modify some data.
 * @package forms */
function _HOOK_form_get_item() {}

/**
 * This hook can defines new type in SpeedForm class. It should return a prepared associative array.
 * @package forms */
function _HOOK_custom_formtypes() {}

/**
 * This hook is run after a SpeedForm is started and got the definition. Is can modify definition and initial values.
 * @package forms */
function _HOOK_speedform_created() {}

/**
 * This hooks run when a SpeedForm key is set. It can modify the value of the set key itself.
 * @package forms */
function _HOOK_speedform_set_key() {}

/**
 * This hook run immediately after a SpeedForm is loaded the values from page parameters (POST/GET)
 * @package forms */
function _HOOK_speedform_parameters_loaded() {}

/**
 * This hook run immediately after a SpeedForm is loaded the values from function
 * which is usally means the values loaded from database (SELECT)
 * @package forms */
function _HOOK_speedform_values_loaded() {}

/**
 * This hook is runs before the SpeedFrom validation process.
 * In can raise a validation error or can modify values, highlights.
 * @package forms */
function _HOOK_speedform_before_validate() {}

/**
 * This hook runs immediately before the SpeedForm is genereted a HtmlForm object. It can modify some data.
 * @package forms */
function _HOOK_speedform_form_generated() {}

/**
 * You can define global field types with this hook, which can be used and reused in to_table() function anywhere
 * to build nice and well formatted tables from a simple sql query without any additional coding.
 * @package forms */
function _HOOK_field_repository() {}

/** You can add one or more data definition structure to a global repository by this hook and receive its when needed.
 * (By datadef_from_repository($name) function)
 * This data definition are accessible by speedform builder, if the settings makes it possible. */
function _HOOK_datadef_repository() {}

//end.
