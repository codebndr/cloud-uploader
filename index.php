<?php
$directory = "tempfiles/";
if(!isset($_REQUEST['hex']))
	die(json_encode(array('success' => 0, 'text' => "NO HEX!")));

if(!isset($_REQUEST['ip']))
	die(json_encode(array('success' => 0, 'text' => "NO IP!")));
	
$value = $_REQUEST['hex'];
$ip = $_REQUEST['ip'];

// echo($value);

$filename = "";
do
{
	$filename = genRandomString(10);
}
while(file_exists($filename.".hex"));
$file = fopen($directory.$filename.".hex", 'x');
if($file)
{
	fwrite($file, $value);
	fclose($file);
}

if(isset($_REQUEST['pass']))
{
	//
}

$output = dothat($filename, "avr-objcopy  -I ihex ".$directory.$filename.".hex -O binary ".$directory.$filename.".bin 2>&1");
if($output["error"])
{
	$output["success"] = 0;
	$output["text"] = "Uknown Upload Error!";
	$output["lines"] = array(0);
	cleanDir($directory.$filename);
	die(json_encode($output));
}
	
// $curDir = getcwd();
// chdir($directory);
$output = dothat($filename, "cd $directory ; tftp -R 4000:5000 -v -m octet $ip -c put ".$filename.".bin 2>&1"); // *.o -> *.elf
// chdir($curDir);
if($output["error"])
{
	foreach($output["output"] as $text)
	{
		if(strpos($text,"Transfer timed out.")  === FALSE)
			continue;
		else
		{
			$output["success"] = 0;
			$output["text"] = "Transfer timed out.";
			$output["lines"] = array(0);
			cleanDir($directory.$filename);
			die(json_encode($output));			
		}
	}
}


cleanDir($directory.$filename);
// echo(json_encode($output));
echo(json_encode(array('success' => 1, 'text' => "Uploaded successfully! ".$output["output"][count($output["output"])-1] )));	

function genRandomString($length)
{
    // $length = 10;
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = "";    
    for ($p = 0; $p < $length; $p++)
	{
        $string .= $characters{mt_rand(0, strlen($characters)-1)};
    }
    return $string;
}

function cleanDir($filename)
{
	
	// if(file_exists($filename.".hex")) unlink($filename.".hex");	
	// if(file_exists($filename.".bin")) unlink($filename.".bin");	
	// Remeber to suggest a cronjob, in case something goes wrong...
	// find $path -name $filename.{o,cpp,elf,hex} -mtime +1 -delete
}

function dothat($filename, $cmd)
{
	exec($cmd, $out, $ret); 
	$return_val = false;
	if($ret)
	{
		// cleanDir($filename);
		$return_val = true;
	}
	// echo json_encode(array("error" => $return_val, "cmd" => $cmd, "output" => $out));
	return array("error" => $return_val, "cmd" => $cmd, "output" => $out);
}


?>

