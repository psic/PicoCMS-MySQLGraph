<?php

require 'svggraph/autoloader.php';



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
   
   /**
	 * Triggered after Pico has read its configuration
	 *
	 * @see    Pico::getConfig()
	 * @param  array &$config array of config variables
	 * @return void
	 */
	public function onConfigLoaded(array &$config)
	{
        $this->config = include('MySQLConfig.php'); 
        if (isset($config['mysql_source']))
        {
            $db_conf = $config['mysql_source'];
            $i=0;

            foreach ($this->config as $db_config_key => $db_config_array)
            {
                $one_db_conf = $db_conf[$db_config_key];
                foreach($one_db_conf as $query_name => $query_string)
                {
                    $this->config[$db_config_key][$query_name] = $query_string;
                    $i++;
                }
            } 
        }
	}
	
    public function onContentPrepared(&$content)
    {
        // Search for Embed shortcodes allover the content
        preg_match_all('#\[db_graph *.*?\]#s', $content, $matches);

        // Make sure we found some shortcodes
        if (count($matches[0]) > 0) {
            $error = false;
            // Walk through shortcodes one by one
            foreach ($matches[0] as $match) 
            {
                if ( ! preg_match('#query=[\"]([^\"]*)[\"]#s', $match, $query))
                    $error = true;
                if ( ! preg_match('/graph=[\"]([^\"]*)[\"]/', $match, $graph))
                    $error = true;
                preg_match('/title=[\"]([^\"]*)[\"]/', $match, $title); 
                preg_match('/height=[\"]([^\"]*)[\"]/', $match, $height);
                preg_match('/width=[\"]([^\"]*)[\"]/', $match, $width);
                preg_match('/settings=[\"]([^\"]*)[\"]/', $match, $settings_conf);
                preg_match('/is_data_column=[\"]([^\"]*)[\"]/', $match, $column);
                if (! $error)
                {
                    $query_string="";
                    $db_name_string="";
                    $found = 0;
                    foreach ($this->config as $db_name => $db_conf_array)
                    {
                        foreach($db_conf_array as $key => $value)
                         {
                            if($key == $query[1])
                            {
                                    $query_string = $value;
                                    $found = 1;
                            }
                        }
                        if($found == 1)
                           $db_name_string = $db_name;
                    }
                    if(strtoupper(substr(trim($query_string),0,6) ) != 'SELECT')
                    {
                    	    $content = preg_replace('#\[db_graph *.*?\]#s', '*MySQLGraph ERROR*', $content, 1);
                    }
                    else
                    {
                        if($column != null)
                            $is_column = $column[1];
                        else
                            $is_column = 1;

                        $result = $this->makeQuery($db_name_string,$query_string,$is_column );
                        // Replace embeding code with the shortcode in the content
                        $settings = array();
                        if ($title != null)
                            $settings['graph_title']=$title[1];
                        if ($settings_conf != null)
                        {
                            $settings = json_decode(str_replace('\'','"',$settings_conf[1]),true);
                        }
                        if($width != null && $height != null)
                            $graphR = new Goat1000\SVGGraph\SVGGraph($width[1], $height[1],$settings);
                        else
                            $graphR = new Goat1000\SVGGraph\SVGGraph(640, 480,$settings);
        //			    $graphR->colours(['red','green','blue']);
                        $graphR->values($result);
                        $content = preg_replace('#\[db_grap *.*?\]#s',  $graphR->fetch($graph[1], false), $content, 1);
                    }
                }
                else
                    $content = preg_replace('#\[db_graph *.*?\]#s', '*MySQLGraph ERROR*', $content, 1);
                
                $error = false;
                
            }
        }
    }
    
    private function makeQuery($dbconf, $query,$is_column)
    {
    $dbhost = $this->config[$dbconf]['host'];
    $dbuser = $this->config[$dbconf]['username'];
    $dbpwd = $this->config[$dbconf]['password'];
    $dbname = $this->config[$dbconf]['db_name'];

    // Create connection    
    $conn = new mysqli($dbhost, $dbuser, $dbpwd, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    $result = $conn->query($query);
    $results =array();
    if ($result->num_rows > 0) 
    {
        // output data of each row
	  if($is_column)
	  {
	        while($row = $result->fetch_assoc()) 
       		{
			foreach($row as $key => $value)
			{
			  $results[$key] = $value;
			}
		}
	  }
	  else
	  {
		while($row = $result->fetch_array())
			$results[$row[0]] = $row[1];
	  }
    } 
    else 
    {
        $results =  "0 results";
    }
    $conn->close();
    return $results;
    
    }
       
}
