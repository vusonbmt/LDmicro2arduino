<?php

define("LADDER_CPP_FILE"      , "ladder.cpp" );
define("LADDER_H_FILE"        , "ladder.h" );
define("LADDER_PINMAP_FILE"   , "pinmap.ini" );

define("LADDER_DEF_PIN_PREFIX", "d");

// --- Command line interface --------------------------------------------------

define("LADDER_GEN_VERSION" , "1.0");
define("CLI", !isset($_SERVER['HTTP_USER_AGENT']));

if (CLI)
{
    $path =  realpath(isset($argv[1]) ? $argv[1] : getcwd()) . '/';

    $c_file = @file_get_contents($path . LADDER_CPP_FILE);
    if ($c_file === false)
    {
        error(LADDER_CPP_FILE . " file not found");
    }
    echo "Info: File to parse: $path" . LADDER_CPP_FILE . "\n";
    
    $map = false;
    if (file_exists($path . LADDER_PINMAP_FILE))
    {
        $map = @parse_ini_file($path . LADDER_PINMAP_FILE, false);
        if ($map !== false)  
        {
            echo "Info: Use " . LADDER_PINMAP_FILE . " file:\n\n";
            foreach ($map as $name=>$pin) 
            {
            	echo "  $name -> $pin\n";
            }
            echo "\n";
        }
    }
    
    if ($map === false)
    {
        echo "Info: Use default pin maping (" . LADDER_DEF_PIN_PREFIX . "n)\n";
        $map = default_map();
    }
    
    if (file_exists($path . LADDER_H_FILE))
    {
        echo "Info: " . LADDER_H_FILE . " already exists, will be overwrite.\n";
    }
    
    try 
    {
        $h = process_code($c_file, $map);
    }
    catch(Exception $e) 
    {
        error($e->getMessage());
    }
    
    $size = file_put_contents($path . LADDER_H_FILE, $h);
    
    if ($size !== false) 
    {
        echo "\nFile " . LADDER_H_FILE . " successfully generated (size: $size B).";
    }
    else
    {
        error("Unable to save file " . LADDER_H_FILE . ".");
    }

    exit(0);
}

function error($msg)
{
    echo "Error: $msg";
    exit(1);
}

function default_map()
{
    $map = array();
    for ($i = 0; $i <= 13; $i++) $map[LADDER_DEF_PIN_PREFIX . $i] = $i;
    return $map;
}

// --- functions ---------------------------------------------------------------

function process_code($code, $pin_map)
{
    $variables = array();
    $pins = array();
    $functions = array();

    preg_match_all('~PROTO\((.*);\)~', $code, $matches);
    foreach($matches[1] as $fn)
    {
        if (preg_match('~(\w+)_U_b_([X,Y])(.+)\(~', $fn, $m) != 1)
        { 
            throw new Exception("Unknown format '$fn'.");
        }
        list($_, $method, $prefix, $name) = $m;
                
        if (isset($pin_map[$name]))
        {
            $pin = $pin_map[$name];
        
            if ($prefix == 'X')
            {
                if (isset($variables[$name]) && $variables[$name] != 'IN') throw new Exception("Variable '$name' is duplicated.");
                $variables[$name] = 'IN';
                $pins[$pin] = 'INPUT';
            
                if ($method == 'Read')
                {
                    $functions[$fn] = "return digitalRead($pin);";
                }
                else
                {
                    throw new Exception("Unknown method '$method'.");
                }
            }
            else if ($prefix == 'Y')
            {
                if (isset($variables[$name]) && $variables[$name] != 'OUT') throw new Exception("Variable '$name' is duplicated.");
                $variables[$name] = 'OUT';
                $pins[$pin] = 'OUTPUT';
            
                if ($method == 'Read')
                {
                    $functions[$fn] = "return digitalRead($pin);";
                }
                else if ($method = 'Write')
                {
                    $functions[$fn] = "digitalWrite($pin, v);";
                }
                else
                {
                    throw new Exception("Unknown method '$method'.");
                }
            }
            else
            {
                 throw new Exception("Unknown prefix '$prefix'.");
            }
        }
        else
        {
            $variables[$name] = '?';
            $functions[$fn] = "// TODO";
        }
    }
    
    return gen_header_file(gen_setup($pins), gen_functions($functions));
}


function gen_functions($functions)
{
    $result = '';
    
    foreach($functions as $head=>$body)
    {
        $result .= "inline " . $head . "\n{\n" . 
                   "    " . $body . "\n" .
                   "}\n\n";
    }
    
    return $result;
}


function gen_setup($pins)
{
    $result = '';
    
    $result .= "inline void PlcSetup()\n{\n";
    foreach ($pins as $pin=>$dir)
    {
        $result .= "    pinMode($pin, $dir);\n";    	
    }
    $result .= "}\n";
    
    return $result;
}

function gen_header_file($setup_code, $digital_io_code)
{
    $time = date("r");
    $ver  = LADDER_GEN_VERSION;
    return <<<EOT
/* This is autogenerated code. */

/* (generated $time by ladder-gen v $ver) */

#ifndef LADDER_H
#define LADDER_H

#if ARDUINO >= 100
    #include "Arduino.h"   
#else
    #include "WProgram.h"
#endif

#define BOOL boolean
#define SWORD int

#define EXTERN_EVERYTHING
#define NO_PROTOTYPES

void PlcCycle(void);

/* Configure digital I/O according to LD (call this in setup()). */

$setup_code

/* Individual pins (this code is used in ladder.cpp) */

$digital_io_code
#endif

EOT;
}


