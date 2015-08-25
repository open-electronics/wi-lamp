<?php

new WiLamp($_POST);

class WiLamp {
	
	protected $parameters;
	
	function __construct ($params) {
		
		$this->parameters = $params;

        echo $this->{$this->parameters["Command"]}();

	}

	function writeFile() {

		do {
			$file = fopen("storage/".$this->parameters["Name"], "w");
		} while ($file == false);

		fwrite($file, $this->parameters["Content"]);

		fclose($file);
	
	}
	
	function parseFileContent($name, $content) {
	
		switch($name) {
			case "sunrise_time":
				return explode(":", $content);
			break;
			case "temperature":
				return explode(",", $content);
			break;
			case "buttons":
				return explode(",", $content);
			break;
			case "auto":
                $entries =explode("\n", $content);
                $result=[];
                foreach($entries as $e){
                    $data = explode("/",$e);
                    $tmp = [];
                    $tmp["start_day"] = $data[0]; 
                    $tmp["start_time"] = $data[1];
                    $tmp["duration"] = $data[2];
                    $tmp["color"] = $data[3];
                    array_push($result, $tmp);
                }
				return $result;
			break;
			default:
				return $content;
			break;
		}
	
	}
    
	function readFile($name = null) {

        if($name == null) {
            $name = $this->parameters["Name"];
        }
    
		do {
			$file = fopen("storage/".$name, "r");
		} while ($file == false);

		$content = fread($file, filesize("storage/".$name));

		fclose($file);
	
		return $this->parseFileContent($name, trim($content));
	
	}
    
	function getDataMode() {

        $response = [];
    
        switch($this->parameters["Mode"]) {
            case 1:
                $response = [
                    "color" => $this->readFile("color")
                ];
            break;
            case 2:
                $response = [
                    "speed" => $this->readFile("speed")
                ];
            break;
            case 3:
                $response = [
                    "speed" => $this->readFile("speed")
                ];
            break;
            case 4:
                $response = [
                    "sunset_duration" => $this->readFile("sunset_duration"),
                    "sunset_start" => $this->readFile("sunset_start"),
                ];
            break;
            case 5:
                $response = [
                    "sunrise_duration" => $this->readFile("sunrise_duration"),
                    "sunrise_time" => $this->readFile("sunrise_time"),
                ];
            break;
            case 6:
                $response = [
                    "temperature" => $this->readFile("temperature")
                ];
            break;
            case 8:
                $response = [
                    "auto" => $this->readFile("auto")
                ];
            break;
            case 9:
                $response = [
                    "buttons" => $this->readFile("buttons")
                ];
            break;
        }
        
        return json_encode($response);
	
	}
    
    function getTemperature() {
        
        $sensor = $this->readFile("temperature_sensor");
        
		do {
			$file = fopen("/sys/bus/w1/devices/w1_bus_master1/".$sensor."/w1_slave", "r");
		} while ($file == false);

		$content = fread($file, filesize("/sys/bus/w1/devices/w1_bus_master1/".$sensor."/w1_slave"));

		fclose($file);
	
        $content = substr(trim($content), -5);
        
        $content = substr($content, 0, strlen($content)-3).",".substr($content, -3);
    
		return substr($content, 0, strlen($content)-2);
        
    }

}

?>