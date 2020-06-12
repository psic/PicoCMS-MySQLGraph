<?php

/**
 * MySQLGraphPlugin
 * Extract rows from database query.
 * Insert field value into markdown content
 */
class MySQLGraphPlugin extends AbstractPicoPlugin
{
   /**
	 * Stored config
	 */
	protected $config = array();
    protected $nb_chart= 0;
    protected $footerScript =array();
   
   /**
	 * Triggered after Pico has read its configuration
	 *
	 * @see    Pico::getConfig()
	 * @param  array &$config array of config variables
	 * @return void
	 */
	public function onConfigLoaded(array &$config)
	{
        $this->config['chartistPath'] = '//cdn.jsdelivr.net/chartist.js/latest/chartist.min.js';
        $this->config['chartistPathCSS'] = '//cdn.jsdelivr.net/chartist.js/latest/chartist.min.css';
        if (isset($config['mysql_source']))
        {
            $db_conf = $config['mysql_source'];
            $i=0;
            foreach ($db_conf as $key => $value)
            {
                    foreach ($value as $key_param => $db_param)
                        $this->config[$key][$key_param] = $db_param;
                    $i++;
            }
        
        }
		
	}
	
    public function onContentPrepared(&$content)
    {
    //  public function onPageRendering(Twig_Environment &$twig, array &$twigVariables, &$templateName)
   // {
        $sample ='<div><div class="ct-chart ct-golden-section" id="chart1"></div><div class="ct-chart ct-golden-section" id="chart2">tititi</div></div>'; 
       /** <script>new Chartist.Line(\'#chart1\', {                   labels: [1, 2, 3, 4],  series: [[100, 120, 180, 200]] }); new Chartist.Bar(\'#chart2\', {                    labels: [1, 2, 3, 4],series: [[5, 2, 8, 3]]});</script>';
       **/
       
       //$content =  $twigVariables['content'];
    
        // Search for Embed shortcodes allover the content
        preg_match_all('#\[db_graph *.*?\]#s', $content, $matches);

        // Make sure we found some shortcodes
        if (count($matches[0]) > 0) {
            $error = false;
            // Walk through shortcodes one by one
            foreach ($matches[0] as $match) 
            {
                 // Get page content
               // $new_content = &$twigVariables['content'];
               // $content = preg_replace('#\[db_graph *.*?\]#s', $sample, $content, 1);
                if ( ! preg_match('#query=[\"\']([^\"\']*)[\'\"]#s', $match, $query))
                    $error = true;
                if ( ! preg_match('/db=[\"\']([^\"\']*)[\'\"]/', $match, $dbValue))
                    $error = true;
                if ( ! preg_match('/graph=[\"\']([^\"\']*)[\'\"]/', $match, $graph))
                    $error = true;
               
                if (! $error)
                {
                    // Replace embeding code with the shortcode in the content
                    $div = '<div class="ct-chart ct-golden-section" id="chart'.++$this->nb_chart.'"></div>';
                    $result = $this->makeQuery($dbValue[1],$query[1],$graph[1]);

//                     $this->footerScript[$this->nb_chart] = 'new Chartist.Line("#chart'.$this->nb_chart.'", {labels: [1, 2, 3, 4],  series: [[100, 120, 180, 200]] });';
                    
                    $content = preg_replace('#\[db_grap *.*?\]#s', $div, $content, 1);
                }
                else
                    $content = preg_replace('#\[db_graph *.*?\]#s', '*MySQLGraph ERROR*', $content, 1);
                
                $error = false;
                
            }
        }
       
    }
    
    
    private function makeQuery($dbconf, $query,$line)
    {
   
    $dbhost = $this->config[$dbconf]['db_host'];
    $dbuser = $this->config[$dbconf]['db_user'];
    $dbpwd = $this->config[$dbconf]['db_pwd'];
    $dbname = $this->config[$dbconf]['db_name'];

    // Create connection    
    $conn = new mysqli($dbhost, $dbuser, $dbpwd, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    }
    $result = $conn->query($query);
    $results ="";
    if ($result->num_rows > 0) 
    {
        // output data of each row
        //$chart=
        while($row = $result->fetch_assoc()) 
        {
          //$rowording = array();
          $label=array();
          $serie=array();
          foreach($row as $key => $value)
            {
           // $rowording['{'.$key.'}'] =$value;
            $label[]="'".$key."',";
            $serie[]=$value .',';
            }
         // $results .= $this->getLineWording($line,$rowording) ."\n";
        }
        //$this->footerScript[$this->nb_chart] = 'new Chartist.Line("#chart'.$this->nb_chart.'", {labels: [1, 2, 3, 4],  series: [[100, 120, 180, 200]] });';
        $this->footerScript[$this->nb_chart] = 'new Chartist.Pie("#chart'.$this->nb_chart.'", {labels: [';
        foreach ($label as $lbl)
            $this->footerScript[$this->nb_chart] .= $lbl;
        $this->footerScript[$this->nb_chart] = substr($this->footerScript[$this->nb_chart], 0, -1);
        $this->footerScript[$this->nb_chart] .='],  series: [';
        foreach ($serie as $val)
            $this->footerScript[$this->nb_chart] .= $val;
        $this->footerScript[$this->nb_chart] = substr($this->footerScript[$this->nb_chart], 0, -1);
        $this->footerScript[$this->nb_chart] .='] });';

    } 
    else 
    {
        $results =  "0 results";
    }
    
    $conn->close();
    return $results;
    
    }
    
    
    /**
	 * Triggered after Pico has rendered the page
	 *
	 * @param  string &$output contents which will be sent to the user
	 * @return void
	 */
	public function onPageRendered(&$output)
	{
		// regular pages
		// add css to end of <head>
		$output = str_replace('</head>', ($this->buildExtraHeaders() . '</head>'), $output);
		// add js to end of <body>
		$output = str_replace('</body>', ($this->buildExtraFooters() . '</body>'), $output);
	}

	/**
	 * Add some extra header tags for our styling.
	 */
	private function buildExtraHeaders() {
		$headers = '<link rel="stylesheet" href="'.$this->config['chartistPathCSS'].'" type="text/css" />';
		return $headers;
	}

	/**
	 * Add some extra footer tags we need.
	 */
	private function buildExtraFooters() {
		$footers = '<script src="'.$this->config['chartistPath'].'"type="text/javascript"></script>';
		$footers .="<script>";
		foreach ($this->footerScript as $script)
		{
		 $footers .= $script;
		}
		$footers .="</script>";
		//$footers .='<script>new Chartist.Line("#chart1", {                   labels: [1, 2, 3, 4],  series: [[100, 120, 180, 200]] });new Chartist.Bar(\'#chart2\', {                    labels: [1, 2, 3, 4],series: [[5, 2, 8, 3]]});</script>'; 
		return $footers;
	}
   
}
