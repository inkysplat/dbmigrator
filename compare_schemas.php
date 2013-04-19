<?php

/**
 * Used for importing the data from the old database
 * schema into the new one, run this with caution 
 */
define('PATH', dirname(__FILE__));

date_default_timezone_set('Europe/London');


$_config = array(
    'hostname'      => '127.0.0.1',
    'username'      => 'root',
    'password'      => '',
    'live_database' => 'myproject_production',
    'dev_database'  => 'myproject_development',
);


$_db = mysql_connect($_config['hostname'], $_config['username'], $_config['password']);

$_dev  = $_config['dev_database'];
$_live = $_config['live_database'];

echo "\nOk... here it goes!";

linebreak();

$live_tables = array();
$sql = "SHOW TABLES FROM {$_live};";
$tables = query($sql);
while($table = mysql_fetch_assoc($tables))
{
    $live_tables[] = $table['Tables_in_'.$_live];
}

$sql = "SHOW TABLES FROM {$_dev};";
$tables = query($sql);
while($table = mysql_fetch_assoc($tables))
{
    $dev_table = $table['Tables_in_'.$_dev];
    
    if(!in_array($dev_table, $live_tables))
    {
        echo "\nMissing {$dev_table} schema in {$_live}";
        
        $sql = "SHOW CREATE TABLE `{$_dev}`.`{$dev_table}`;";
        $c = query($sql);
        
        $table = mysql_fetch_assoc($c);
        
        linebreak();
        echo "\n".$table['Create Table'];
        linebreak();
        echo "\n";
        
        continue;
    }
    
    $sql = "DESCRIBE {$_dev}.{$dev_table};";
    $dev_schema = query($sql);
    
    
    $sql = "DESCRIBE {$_live}.{$dev_table};";
    $live_schema = query($sql);
    
    $live_columns = array();
    
    $c=0;
    while($schema = mysql_fetch_assoc($live_schema))
    {
        foreach($schema as $column_key=>$column_value)
        {
            $live_columns[$c][$column_key] = $column_value;
        }
        $c++;
    }
   
    $dev_columns = array();
    
    $c=0;
    while($schema = mysql_fetch_assoc($dev_schema))
    {
        foreach($schema as $column_key=>$column_value)
        {
            $dev_columns[$c][$column_key] = $column_value;
        }
        $c++;
    }
    
    if(count($dev_columns) <> count($live_columns))
    {
        echo "\nColumn count doesn't match for `{$dev_table}`";
        echo "\nDEV has ".count($dev_columns)." columns";
        echo "\nLIVE has ".count($live_columns)." columns";
        echo "\n";
    }
    
    for($i=0; $i< count($dev_columns);$i++)
    {
        
        $found['field'] = false;
        $found['type'] = false;
        for($j=0; $j<count($live_columns); $j++)
        {
            if($live_columns[$j]['Field'] == $dev_columns[$i]['Field'])
            {
                $found['field'] = true;
                
                if($live_columns[$j]['Type'] == $dev_columns[$i]['Type'])
                {
                    $found['type'] = true;
                }else{
                    $found['type'] = $j;
                }
            }
        }
        
        if(!$found['field'])
        {
            echo "\nMissing column `{$dev_columns[$i]['Field']}` {$dev_columns[$i]['Type']} from DEV in LIVE";
            
            $sql = "ALTER TABLE {$_live}.{$dev_table} ".
                    "\nADD `{$dev_columns[$i]['Field']}` {$dev_columns[$i]['Type']} ".
                    ($dev_columns[$i]['Null'] == 'NO'?"NOT NULL ":"NULL ").
                    "\nAFTER `{$dev_columns[($i-1)]['Field']}`";
            linebreak();
            echo "\n".$sql;
            linebreak();
            echo "\n";
        }
        
        if($found['field'] && is_numeric($found['type']))
        {
            echo "\nColumn types don't match";
            echo "\nColumn `{$dev_columns[$i]['Field']}` in DEV has type of {$dev_columns[$i]['Type']}";
            echo "\nColumn `{$live_columns[$found['type']]['Field']}` in LIVE has type of {$live_columns[$found['type']]['Type']}";
            
            $sql = "ALTER TABLE {$_live}.{$dev_table} ".
                    "\nCHANGE `{$dev_columns[$i]['Field']}` `{$dev_columns[$i]['Field']}` {$dev_columns[$i]['Type']}".
                   ($dev_columns[$i]['Null'] == 'NO'?"NOT NULL ":"NULL ").
                    "\nAFTER `{$dev_columns[($i-1)]['Field']}`";
            linebreak();
            echo "\n".$sql;
            linebreak();
            echo "\n";
        }
        
    }
}

function query($sql)
{
    global $_db;

    $r = mysql_query($sql, $_db) or die(
                "\n\n".
                "\n".str_repeat('=',80).
                "\n".str_repeat('=',80).
                "\n".mysql_error() . 
                "\n\n\n\n" . $sql . 
                "\n".str_repeat('=',80).
                "\n".str_repeat('=',80).
                "\n\n");

    if ($r !== false)
    {
        return $r;
    }

    return false;
}

function linebreak()
{
    echo "\n" . str_repeat('-', 25);
}