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
        $graphR = new Goat1000\SVGGraph\SVGGraph(640, 480);
        $graphR->colours(['red','green','blue']);
      
        // Search for Embed shortcodes allover the content
        preg_match_all('#\[db_graph *.*?\]#s', $content, $matches);

        // Make sure we found some shortcodes
        if (count($matches[0]) > 0) {
            $error = false;
            // Walk through shortcodes one by one
            foreach ($matches[0] as $match) 
            {
                 // Get page content
                if ( ! preg_match('#query=[\"\']([^\"\']*)[\'\"]#s', $match, $query))
                    $error = true;
                if ( ! preg_match('/db=[\"\']([^\"\']*)[\'\"]/', $match, $dbValue))
                    $error = true;
                if ( ! preg_match('/graph=[\"\']([^\"\']*)[\'\"]/', $match, $graph))
                    $error = true;
               
                if (! $error)
                {
                    // Replace embeding code with the shortcode in the content
                    $result = $this->makeQuery($dbValue[1],$query[1],$graph[1]);
                    $graphR->values($result);
                    $content = preg_replace('#\[db_grap *.*?\]#s',  $graphR->fetch('PieGraph', false), $content, 1);
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
    $results =array();
    if ($result->num_rows > 0) 
    {
        // output data of each row
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
        $results =  "0 results";
    }
    
    $conn->close();
    return $results;
    
    }
       
}
