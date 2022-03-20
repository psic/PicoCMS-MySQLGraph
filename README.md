# [PicoCMS](https://github.com/picocms/Pico) plugin : MySQLGraph

Fetch data from mysqlDB.
Draw charts 
(Use [SVGGraph](https://github.com/goat1000/SVGGraph) to generate SVG charts on server side)

# Motivations

Adding database connection for the great flat file CMS Pico sound weird! Yes it is.
The goal is to use pico as a base to monitor a MySQL database and show some key values.
It will be completed with a MySQLList plugin and a GETFromAPI plugin.

# Install

Copy the `MySQLGraphPlugin.php` and the `MySQLConfig.php` files into the `plugins` folder.

# Config

First, config your database access in the `MySQLConfig.php` :

```
return array(
    'db1'=>array ( // database settings name for the plugin 
        'host' => 'localhost', //database host
        'username' => 'admin', //database username
        'password' => 'passwd1', //database password
        'db_name' => 'db1_name') //database name
);


```
You can add several database in this file. If you also use [the MySQLList plugin](https://github.com/psic/PicoCMS-MySQLList), you can share the conf file : put MySQLListPlugin.php, MySQLGraphPlugin.php and a MySQLConfig.php files in the root plugin folder.

Then, you should write queries, and give them names in the Pico's config :

```
mysql_source:
 db1:                             # First database config name
  #query_name: "SQL Query, SELECT only"
  count1: "SELECT sum(case when is_android = 1 then 1 else 0 end) AS android,  sum(case when is_android = 0 then         1 else 0 end) AS iphone FROM user where date_signin = curdate();"
  sales: "SELECT month, sales FROM sales_by_month"

```
For queries delimitation, only use `"`, not `` ` ``,  since it can be use in the SQL query.

Finally, use those queries in your markdown file :

+ `query` : the name of the query used as it is in the Pico's conf file.
+ `graph` : Choose any of the value in [grap type](https://www.goat1000.com/svggraph.php#graph-types). BarGraph, LineGraph, PieGraph, ...
+ `is_data_column` : boolean 0/1 (default : 1). Set if the data are in row or colum.
    + `is_data_column = "1"` : data are in columns and columns headers are used for x-axis. 
    
    |is_android|is_iphone|
    |----------|---------|
    |    5     |    2    |

    
    + `is_data_column = "0"` : data are in rows and the first column is used for x-axis.
    
    |   Month     |   Sale  |
    |-------------|---------|
    |    January  |    300  |
    |    February |    250  |
    |    March    |    123  |
    |    April    |    29   |
    
+ `width` & `height` (optional) : the width and the heigth of your chart (default : 640x480) 
+ `title` : the title of your chart
+ `colours` : you can add colours to your graph lines, bar, ... `colours="green,red"` where colours are defined in CSS style
+ `settings` : you can add any of settings in *JSON style*. See [setting](https://www.goat1000.com/svggraph-settings.php#general-options). `settings="{'back_colour': 'white', 'graph_title': 'Start of Fibonacci series'}"` (use `` ` `` in this JSON settings instead of `"`)

```
[db_graph  query="count1" width="500" height="400" title="My Graph Title" graph="PieGraph"]
```

```
[db_graph  query="sale" title="Sale By Month" graph="LineGraph" is_data_column="0"]
```
