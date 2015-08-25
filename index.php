<!DOCTYPE html>
<html>

	<head>
	
		<title>Wi-Lamp</title>
	
        <!--Import materialize.css-->
        <link type="text/css" rel="stylesheet" href="common/materialize/css/materialize.min.css"  media="screen,projection"/>

        <!-- Import custom CSS style -->
        <link rel="stylesheet" href="common/custom-style.css">
        
        <!--Let browser know website is optimized for mobile-->
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    
		<!--	jQuery & Materialize	-->
		<script type = "text/javascript" src="common/jquery-2.1.1.min.js"></script>
        <script type="text/javascript" src="common/materialize/js/materialize.min.js"></script>
		
		<!--	Spectrum Color Picker	-->
		<link rel="stylesheet" type="text/css" href="common/spectrum.css">
		<script type = "text/javascript" src="common/spectrum.js"></script>

		<script type = "text/javascript">

			var timer_updateLed;
			var timeout_loadMode;

			/*	GENERAL	*/
		
			function loadMode() {
			
				//Load the current mode
			
				$.post("wi-lamp.php", {Command : "readFile", Name : "mode"}, function(response) {
					$("#Mode").val($.trim(response));
					changeMode($.trim(response), 0);
				});
			
			}

            function writeFile(name, content) {
                
                $.post("wi-lamp.php", {Command : "writeFile", Name : name, Content : content});
                
            }
        
			function changeMode(mode, changeMode) {

				//Start new mode

				if(changeMode == 1) {

                    writeFile("mode", mode);
                    
                }
            
				//Try to disable eventual timer of Self mode
				
				try{
					window.clearInterval(timer);
				}catch(err){}
                
                //Retrieve data from the current Mode
                
                $.post("wi-lamp.php", {Command : "getDataMode", Mode: mode}, function(response){
                    if(response != "") {
                        switch(mode){
                            case "1":
                                rgb = response.color;
                                $("#Color").spectrum("set", "rgb " + rgb.replace(new RegExp(',', 'g'), " "));
                                timer = setInterval(function () {updateLed()}, 150);
                            break;
                            case "2":
                                $("#Speed2").val(response.speed);
                            break;
                            case "3":
                                $("#Speed3").val(response.speed);
                            break;
                            case "4":
                                $("#Duration4").val(response.sunset_duration);
                                if(response.sunset_start == "1") {
                                    $("#Duration4").addClass('disabled');
                                    $("#SunsetStart").addClass('disabled');
                                }
                                $("#sunsetDuration").html("<h4>" + $("#Duration4").val() + "</h4>");
                            break;
                            case "5":
                                $("#Duration5").val(response.sunrise_duration);
                                $("#SunriseHour").val(response.sunrise_time[0]);
                                $("#SunriseMin").val(response.sunrise_time[1]);
                                $('#sunriseDuration').html('<h4>' + $("#Duration5").val() + '</h4>');
                            break;
                            case "6":
                                $("#TempMin").val(response.temperature[0]);
                                $("#TempMax").val(response.temperature[1]);
                                currentMin = response.temperature[0];
                                currentMax = response.temperature[1];
                                $("#tMin").html("<font color = 'blue'><h4>" + $("#TempMin").val() + "</h4></font>");
                                $("#tMax").html("<font color = 'red'><h4>" + $("#TempMax").val() + "</h4></font>");
                                getTemperature();
                                timer = setInterval(function () {getTemperature()}, 5000);
                            break;
                            case "8":
                                renderAuto(response.auto);
                            break;
                            case "9":
                                $("#Mode1").val(response.buttons[0]);
                                $("#Mode2").val(response.buttons[1]);
                            break;
                        }
                    }
                }, "json");

				//Hide all panels
			
				for(var i=0;i<=9;i++) {
				
					$("#"+i).hide();
				
				}
				
				//Show the panel of the current mode
				
				$("#"+mode).show();
				
				$("#Mode").blur();

			}
			

			var rgb = "0,0,0";
			var currentRgb = "0,0,0";

			$(document).ready(function() {
				
				/*	SELF	*/	
				
				$("#Color").spectrum({
					flat: true,
					preferredFormat: "rgb",
					togglePaletteOnly: true,
					showButtons: false,
					color: "red",
					move: function(color) {
						rgb = color.toRgbString();
                        rgb = rgb.replace("rgb(", "");
                        rgb = rgb.replace(")", "");
                        rgb = rgb.replace(new RegExp(' ', 'g'), "");
					}
				});
				$(".sp-picker-container").css("width", $(document).width() * 70 / 100);
			});

			//Save RGB color

			function updateLed() {
				if(rgb != currentRgb) {
					currentRgb = rgb;
                    writeFile("color", currentRgb);
				}			
			}
			
			
			/*	TEMPERATURE 	*/
			
            var currentMin = 0;
            var currentMax = 45;
            
			function changeTemp() {

                if(parseInt($("#TempMax").val()) > parseInt($("#TempMin").val())) {
                    writeFile("temperature", $("#TempMin").val()+","+$("#TempMax").val());
                    currentMin = $("#TempMin").val();
                    currentMax = $("#TempMax").val();
                } else {
                    $("#TempMin").val(currentMin);
                    $("#TempMax").val(currentMax);
                    Materialize.toast("Values must be different!", 2000, 'rounded');
                }
                
                $("#tMin").html("<font color = 'blue'><h4>" + $("#TempMin").val() + "</h4></font>");
                $("#tMax").html("<font color = 'red'><h4>" + $("#TempMax").val() + "</h4></font>");

			}
            
            function getTemperature() {
                
                $.post("wi-lamp.php", {Command : "getTemperature"}, function(response){
                    $("#CurrentTemperature").html("<h4>"+response+"&deg;C</h4>");
                });
            
            }
			
			/*	AUTO 	*/	
			
            var tabSchedules = new Array();
            var maxLine = 0;
            var rw = "";
            
            function createAuto(){
                
                maxLine++;
                
                tabSchedules[maxLine] = $("#AutoDay").val() + "/" + $("#AutoHour").val()+":"+$("#AutoMin").val() + "/" + $("#AutoDuration").val() + "/" + $("#AutoColor").val() + "\n";
                
                rw = "<tr id = 'row_" + maxLine + "'>";
                rw += "<td>" + dayOfWeek($("#AutoDay").val()) + "</td>";
                rw += "<td>" + $("#AutoHour").val()+":"+$("#AutoMin").val() + "</td>";
                rw += "<td>" + $("#AutoDuration").val() + "</td>";
                rw += "<td>" + getColorName($("#AutoColor").val()) + "</td>";
                rw += "<td><img src = 'common/remove.png' border = '0' style = 'cursor: pointer;' onClick = 'removeAuto(" + maxLine + ");'></td>";
                rw += "</tr>";
                
                $("#Schedules").append(rw);
                
                $("#AutoDay").val("-1");
                $("#AutoHour").val("00");
                $("#AutoMin").val("00");
                $("#AutoDuration").val("5");
                $("#AutoColor").val("255,0,0");
                
                saveAuto();
                
            }
            
			function removeAuto(line) {
			
                tabSchedules.splice(line, 1);
                $("#row_" + line).remove();
                saveAuto();

			}

			function saveAuto() {

                var content = "";
            
                for (var line in tabSchedules) {
                    content += tabSchedules[line];
                }
                
                writeFile("auto", content);
                
			}

			function refreshAuto() {

                $.post("wi-lamp.php", {Command : "getDataMode", Mode: 8}, function(response){
                    if(response != "") {
                        renderAuto(response.auto);
                    }
                }, "json");
			
			}

            function renderAuto(data) {
                
                maxLine = 0;
                $("#Schedules").html("");
                
                for (var line in data) {
                    if(data[line].start_time != null) {
                        maxLine++;
                        tabSchedules[maxLine] = data[line].start_day + "/" + data[line].start_time + "/" + data[line].duration + "/" + data[line].color + "\n";
                        rw = "<tr id = 'row_" + maxLine + "'>";
                        rw += "<td>" + dayOfWeek(data[line].start_day) + "</td>";
                        rw += "<td>" + data[line].start_time + "</td>";
                        rw += "<td>" + data[line].duration + "</td>";
                        rw += "<td>" + getColorName(data[line].color) + "</td>";
                        rw += "<td><img src = 'common/remove.png' border = '0' style = 'cursor: pointer;' onClick = 'removeAuto(" + maxLine + ");'></td>";
                        rw += "</tr>";
                        $("#Schedules").append(rw);
                    }
                }
                
            }
            
            function dayOfWeek(day) {

                switch(parseInt(day)) {
                    case -1:
                        return "All";
                    break;
                    case 0:
                        return "Monday";                    
                    break;
                    case 1:
                        return "Tuesday";                    
                    break;
                    case 2:
                        return "Wednesday";                    
                    break;
                    case 3:
                        return "Thursday";                    
                    break;
                    case 4:
                        return "Friday";                    
                    break;
                    case 5:
                        return "Saturday";                    
                    break;
                    case 6:
                        return "Sunday";
                    break;
                }
                
            }
            
            function getColorName(color) {
                
                switch(color) {
                    case "255,255,255":
                        return "White";
                    break;
                    case "255,0,0":
                        return "Red";
                    break;
                    case "255,255,0":
                        return "Yellow";                    
                    break;
                    case "0,255,0":
                        return "Green";                    
                    break;
                    case "0,255,255":
                        return "Cyan";                    
                    break;
                    case "0,0,255":
                        return "Blue";                    
                    break;
                    case "255,0,255":
                        return "Violet";                    
                    break;
                }
                
            }

		</script>

	</head>

	<body onLoad = "loadMode();">

        <div class = "container">
        
            <br>
        
			<div class = "row">

				<div class="col s2">
                    Mode:
                </div>
            
				<div class="col s10">
                    
                    <select class="browser-default" id = "Mode" onChange = "changeMode(this.value, 1);">
                        <option value = "0" SELECTED>OFF</option>
                        <option value = "1">Self</option>
                        <option value = "2">Fade</option>
                        <option value = "3">Party</option>
                        <option value = "4">Sunset</option>
                        <option value = "5">Sunrise</option>
                        <option value = "6">Temperature</option>
                        <option value = "7">Fire</option>
                        <option value = "8">Auto</option>
                        <option value = "9">Settings</option>
                    </select>

				</div>
				
			</div>
            
            <br>

			<!--  OFF -->
			
			<div class="row" id = "0" style = "display: none;">
			
				<div class="col s12 center-align">
					<img src = "common/light-bulb.png" border = "0" style = "height: 90%; width: 90%;">
				</div>

			</div>

			<!--  Self -->
			
			<div class="row" id = "1" style = "display: none;">
			
				<div class="col s12 center-align">
					<input type = "text" id = "Color" value="#000000">
				</div>

			</div>
            
			<!--  Fade -->
			
			<div class="row" id = "2" style = "display: none;">
			
                <br>
            
				<div class="col s2">
                    Speed:
                </div>
            
				<div class="col s10 center-align">
                    <input type="range" id="Speed2" class="range-custom" min="0" max="4" onChange = "writeFile('speed', this.value);">
				</div>

			</div>
            
			<!--  Party -->
			
			<div class="row" id = "3" style = "display: none;">
			
                <br>
            
				<div class="col s2">
                    Speed:
                </div>
            
				<div class="col s10 center-align">
                    <input type="range" id="Speed3" class="range-custom" min="0" max="4" onChange = "writeFile('speed', this.value);">
				</div>

			</div>
            
			<!--  Sunset -->
			
			<div class="row" id = "4" style = "display: none;">
			
                <br>
            
				<div class="col s2">
                    Duration (min):
                </div>
            
				<div class="col s9 center-align">
                    <input type="range" id="Duration4" class="range-custom" min="1" max="20"  onChange = "writeFile('sunset_duration', this.value); $('#sunsetDuration').html('<h4>' + this.value + '</h4>');">
                    <br>
                    <br><br>
                    <a class="waves-effect waves-light btn" id = "SunsetStart" onClick = "writeFile('sunset_start', '1'); $('#Duration4').addClass('disabled'); $('#SunsetStart').addClass('disabled');">Start</a>
				</div>
                
                <div class="col s1 center-align" id = "sunsetDuration">

                </div>

			</div>

			<!--  Sunrise -->
			
			<div class="row" id = "5" style = "display: none;">
            
				<div class="col s12">
                
                    <div class="row">
                
                        <div class="col s2">
                            Duration (min):
                        </div>
                        
                        <div class="col s9 center-align">
                            <input type="range" id="Duration5" class="range-custom" min="1" max="20" onChange = "writeFile('sunrise_duration', this.value); $('#sunriseDuration').html('<h4>' + this.value + '</h4>');">
                        </div>
                            
                        <div class="col s1 center-align" id = "sunriseDuration">

                        </div>
                    
                    </div>
                    
                    <div class = "row">
                    
                        <div class="col s4">
                            Starting time (hh:mm):
                        </div>
                        
                        <div class="col s4">
                            <select class="browser-default" id = "SunriseHour" onChange = "writeFile('sunrise_time', this.value+':'+$('#SunriseMin').val());">
                                <?php
                                    for($i=0;$i<=23;$i++) {
                                        $h = substr("0".$i, -2);
                                        echo "<option value = '".$h."'>".$h."</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col s4">
                            <select class="browser-default" id = "SunriseMin" onChange = "writeFile('sunrise_time', $('#SunriseHour').val()+':'+this.value);">
                                <?php
                                    for($i=0;$i<=59;$i+=5) {
                                        $m = substr("0".$i, -2);
                                        echo "<option value = '".$m."'>".$m."</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        
                    </div>

				</div>

			</div>
            
			<!--  Temperature -->
			
			<div class="row" id = "6" style = "display: none;">
            
            
				<div class="col s12">
                
                    <div class="row">
                
                        <div class="col s1">
                            <font color = "blue">Min.:</font>
                        </div>
                        
                        <div class="col s10 center-align">
                            <input type="range" id="TempMin" class="range-custom" min="0" max="45" onChange = "changeTemp();">
                        </div>
                        
                        <div class="col s1 center-align" id = "tMin">

                        </div>
                    
                    </div>
                    
                    <div class="row">
                
                        <div class="col s1">
                            <font color = "red">Max.:</font>
                        </div>
                        
                        <div class="col s10 center-align">
                            <input type="range" id="TempMax" class="range-custom" min="0" max="45" onChange = "changeTemp();">
                        </div>
                        
                        <div class="col s1 center-align" id = "tMax">

                        </div>

                    </div>
                    
                    <div class="row">
                
                        <div class="col s2">
                            Current:
                        </div>
                        
                        <div class="col s10 left-align" id = "CurrentTemperature">

                        </div>

                    </div>

				</div>

			</div>
            
			<!--  Fire -->
			
			<div class="row" id = "7" style = "display: none;">
			
				<div class="col s12 center-align">
                    <img src = "common/fire.png" border = "0" style = "height: 90%; width: 90%;">
				</div>

			</div> 
            
			<!--  Auto -->
			
			<div class="row" id = "8" style = "display: none;">
			
				<div class="col s12">
                
                    <div class = "row">
                    
                        <div class="col s2">
                            Day:
                        </div>
                        
                        <div class="col s8 offset-s2">
                            <select class="browser-default" id = "AutoDay">
                                <option value = "-1" SELECTED>All</option>
                                <option value = "0">Monday</option>
                                <option value = "1">Tuesday</option>
                                <option value = "2">Wednesday</option>
                                <option value = "3">Thursday</option>
                                <option value = "4">Friday</option>
                                <option value = "5">Saturday</option>
                                <option value = "6">Sunday</option>
                            </select>
                        </div>
                        
                    </div>
                    
                    <div class = "row">
                    
                        <div class="col s4">
                            Starting time (hh:mm):
                        </div>
                        
                        <div class="col s4">
                            <select class="browser-default" id = "AutoHour">
                                <?php
                                    for($i=0;$i<=23;$i++) {
                                        $h = substr("0".$i, -2);
                                        echo "<option value = '".$h."'>".$h."</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col s4">
                            <select class="browser-default" id = "AutoMin">
                                <?php
                                    for($i=0;$i<=59;$i+=5) {
                                        $m = substr("0".$i, -2);
                                        echo "<option value = '".$m."'>".$m."</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        
                    </div>

                    <div class = "row">
                    
                        <div class="col s3">
                            Duration (min):
                        </div>
                        
                        <div class="col s8 offset-s1">
                            <select class="browser-default" id = "AutoDuration">
                                <?php
                                    for($i=5;$i<=60;$i += 5) {
                                        echo "<option value = '".$i."'>".$i."</option>";
                                    }
                                ?>
                            </select>
                        </div>

                        
                    </div>
                    
                    <div class = "row">
                    
                        <div class="col s2">
                            Color:
                        </div>
                        
                        <div class="col s8 offset-s2">
                            <select class="browser-default" id = "AutoColor">
                                <option value = "255,255,255">White</option>
                                <option value = "255,0,0">Red</option>
                                <option value = "255,255,0">Yellow</option>
                                <option value = "0,255,0">Green</option>
                                <option value = "0,255,255">Cyan</option>
                                <option value = "0,0,255">Blue</option>
                                <option value = "255,0,255">Violet</option>
                            </select>
                        </div>
                        
                    </div>
                    
                    <div class = "row">
                    
                        <div class="col s12 center-align">
                            <a class="waves-effect waves-light btn" id = "AutoSave" onClick = "createAuto();">Create</a>
                        </div>
                        
                    </div>

                    <div class = "row">
                    
                        <div class="col s12 center-align">
                            <table class="responsive-table centered striped">
                                <thead>
                                  <tr>
                                      <th>Day</th>
                                      <th>Time</th>
                                      <th>Duration</th>
                                      <th>Color</th>
                                  </tr>
                                </thead>
                                <tbody id = "Schedules">
                                
                                </tbody>
                            </table>
                        </div>

                    </div>

				</div>

			</div>
            
			<!--  Settings -->
			
			<div class="row" id = "9" style = "display: none;">
			
				<div class="col s12">
                
                    <div class="row">
                    
                        <div class="col s2">
                            Button 1:
                        </div>
                        
                        <div class="col s10">
                        
                            <select class="browser-default" id = "Mode1" onChange = "writeFile('buttons', this.value+','+$('#Mode2').val());">
                                <option value = "0" SELECTED>OFF</option>
                                <option value = "1">Self</option>
                                <option value = "2">Fade</option>
                                <option value = "3">Party</option>
                                <option value = "4">Sunset</option>
                                <option value = "5">Sunrise</option>
                                <option value = "6">Temperature</option>
                                <option value = "7">Fire</option>
                                <option value = "8">Auto</option>
                            </select>
                        
                        </div>
                        
                    </div>
                        
                    <div class="row">
                        
                        <div class="col s2">
                            Button 2:
                        </div>
                        
                        <div class="col s10">
                        
                            <select class="browser-default" id = "Mode2" onChange = "writeFile('buttons', $('#Mode1').val()+','+this.value);">
                                <option value = "0" SELECTED>OFF</option>
                                <option value = "1">Self</option>
                                <option value = "2">Fade</option>
                                <option value = "3">Party</option>
                                <option value = "4">Sunset</option>
                                <option value = "5">Sunrise</option>
                                <option value = "6">Temperature</option>
                                <option value = "7">Fire</option>
                                <option value = "8">Auto</option>
                            </select>
                        
                        </div>
                        
                    </div>
                    
                    <div class="row">
                        
                        <div class="col s12 center-align">
                            <a class="waves-effect waves-light btn" id = "Shutdown" onClick = "writeFile('shutdown', '1'); Materialize.toast('Goodbye!', 2000, 'rounded');">ShutDown Lamp</a>
                        </div>
                        
                    </div>

                </div>

			</div>
            
        </div>
	
	</body>
	
</html>