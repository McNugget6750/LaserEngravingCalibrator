<?php

// Function runs a defined rectangle pattern ar the given speed with the given laser power to create an engraving
function runEngravingPattern($offsetX, $offsetY, $patternSizeX, $patternSizeY, $resX, $resY, $feedrate, $laserPower, $overscan)
{
	$runnerX = 0;
	$runnerY = 0;
	$forward = 1;
	
	print("G1 X$offsetX Y$offsetY S0 F".round($feedrate)."\n");			// Move to the offset position with the laser off
	print("M3 S0\n");								// Turn on laser
	
	// Run all requested lines in a back and forth pattern
	for ($runnerY = 0; $runnerY < $patternSizeY; $runnerY += $resY)
	{
		print("G1 Y".round($offsetY+$runnerY, 4)." S0 F".round($feedrate)."\n");	// Go to the next line and restart pattern in the other direction until finished
		if ($forward == 1)
		{
			// Run the line forward
			for ($runnerX = -$overscan; $runnerX < $patternSizeX + $overscan; $runnerX += $resX)
			{
				if ($runnerX > -$overscan && $runnerX < 0)
					print("G1 X".round($offsetX + $runnerX, 4)." S0 F".round($feedrate)."\n");	// Engrave the line piece with the given power and speed
				else if ($runnerX >= 0 && $runnerX <= $patternSizeX)
					print("G1 X".round($offsetX + $runnerX, 4)." S".round($laserPower)." F".round($feedrate)."\n");	// Engrave the line piece with the given power and speed
				else if ($runnerX > $patternSizeX && $runnerX < $patternSizeX + $overscan)
					print("G1 X".round($offsetX + $runnerX, 4)." S0 F".round($feedrate)."\n");	// Engrave the line piece with the given power and speed
			}
			print("\n");
			$forward = 0; // Reverse engraving direction
		}
		else
		{
			// Run the line backward
			for ($runnerX = $patternSizeX + $overscan; $runnerX > -$overscan; $runnerX -= $resX)
			{
				if ($runnerX < $patternSizeX + $overscan && $runnerX > $patternSizeX)
					print("G1 X".round($offsetX + $runnerX, 4)." S0 F".round($feedrate)."\n");	// Engrave the line piece with the given power and speed
				else if ($runnerX <= $patternSizeX && $runnerX >= 0)
					print("G1 X".round($offsetX + $runnerX, 4)." S".round($laserPower)." F".round($feedrate)."\n");	// Engrave the line piece with the given power and speed
				else if ($runnerX < 0 && $runnerX > -$overscan)
					print("G1 X".round($offsetX + $runnerX, 4)." F".round($feedrate)."\n");	// Engrave the line piece with the given power and speed
			}
			print("\n");
			$forward = 1; // Reverse engraving direction
		}
	}
	
	print("M5 S0\n");								// Turn off laser
	print("\n");									// Add a new line for better clarity
	
	return true; // Return to last position in code and continue 
}

// Function runs a line from offsetXY to toRelXY with the given speed and the given laser power
function runCuttingPattern($offsetX = 0, $offsetY = 0, $toRelX = 0, $toRelY = 0, $feedrate = 0, $laserPower = 0)
{
	print("M3 S0\n");								// Turn on laser
	print("G1 X$offsetX Y$offsetY S$laserPowerOff F$travelRate\n");			// Move to the offset position with the laser off
	print("G1 X".round($offsetX+$toRelX, 4)." Y".round($offsetY+$toRelY, 4)." S$laserPower F$feedrate\n");	// Cut the line with the given power and speed
	print("G1 X$offsetX Y$offsetY S$laserPowerOff F$travelRate\n");			// Return to the offset position with the laser off
	print("M5 S0\n");								// Turn off laser
	print("\n");									// Add a new line for better clarity
	
	return true;  // Return to last position in code and continue 
}

set_time_limit(300);

// Variable declarations to get all the data from the HTML form
$laserMin = $_POST['laserPowerMin'];		//$laserMax=10; //out of [Your settings in grbl]
$laserMax = $_POST['laserPowerMax']; 		//$laserMin=750; //out of [Your settings in grbl]
$laserOff = $_POST['laserPowerOff'];		//$laserOff=0; //out of [Your settings in grbl]

$feedRateMin = $_POST['minFeedRate'];		//$feedRate = 300; //in mm/min
$feedRateMax = $_POST['maxFeedRate'];		//$feedRate = 2500; //in mm/min
$travelRate = $_POST['travelFeedRate'];		//$travelRate = 3000;  //in mm/min

$overScan = $_POST['overScan'];			//$overScan = 3; // leave a frame of 3mm around the engraved area

$resX = $_POST['resX'];				//$resY=0.1; // The horizontal resolution with which the area is engraved
$resY = $_POST['resY'];				//$resY=0.1; // The vertical resolution with which the area is engraved

$offsetX = $_POST['offsetX'];			//$offsetY=5; // Offset in the world for starting everything
$offsetY = $_POST['offsetY'];			//$offsetX=5; // Offset in the world for starting everything
$sizeX = $_POST['sizeX'];			//$sizeX=290; // total size exclusing the offset
$sizeY = $_POST['sizeY'];			//$sizeY=190; // total size exclusing the offset
$patternSizeX = $_POST['patternSizeX'];		//$sizeX=290; // total size exclusing the offset
$patternSizeY = $_POST['patternSizeY'];		//$sizeY=190; // total size exclusing the offset

$numLaserPowerPatterns = $_POST['numLaserPower'];//$numLaserPower=5; // total number of horizontal patterns
$numFeedRatesPatterns = $_POST['numFeedRates'];	//$numFeedRates=5; // total number of vertical patterns

// Create the gCode output file
header("Content-Disposition: attachment; filename=TestPattern.gcode");

// Comment lines for the file
print(";Created using MicroForge Laser Engraving Test Pattern Generator v0.1\n");
print(";http://microforge.de 2016\n");

// Start with the actual gcode generation
print("$H; Home Laser Cutter\n");
print("G21; Programming in millimeters (mm)\n");
print("M5 S$laserOff; Turn laser off and set to 0 power\n"); 	// might now work on my grbl version
print("G1 F$travelRate; Travel Feedrate (mm/min)\n");

print("G0 X$offsetX Y$offsetY F$travelRate\n");			// Move to start offset position


// *********************** Start creating the pattern ****************************** //

$sizeXdevider = $sizeX / $numLaserPowerPatterns;
$sizeYdevider = $sizeY / $numFeedRatesPatterns;
$laserPowerDevider = ($laserMax - $laserMin) / ($numLaserPowerPatterns - 1);
$feedRateDevider =  ($feedRateMax - $feedRateMin) / ($numFeedRatesPatterns - 1);

print("; laserMin: $laserMin\n");
print("; laserMax: $laserMax\n");
print("; laserOff: $laserOff\n");

print("; feedRateMin: $feedRateMin\n");
print("; feedRateMax: $feedRateMax\n");
print("; travelRate: $travelRate\n");

print("; overScan: $overScan\n");

print("; resX: $resX\n");
print("; resY: $resY\n");

print("; offsetX: $offsetX\n");
print("; offsetY: $offsetY\n");
print("; sizeX: $sizeX\n");
print("; sizeY: $sizeY\n");
print("; patternSizeX: $patternSizeX\n");
print("; patternSizeY: $patternSizeY\n");
print("; numLaserPowerPatterns: $numLaserPowerPatterns\n");
print("; numFeedRatesPatterns: $numFeedRatesPatterns\n");
print("\n");
print(";sizeXdevider: $sizeXdevider\n");
print(";sizeYdevider: $sizeYdevider\n");
print(";laserPowerDevider: $laserPowerDevider\n");
print(";feedRateDevider: $feedRateDevider\n");

for ($y = 0; $y < $numFeedRatesPatterns; $y++)
{
	for ($x = 0; $x < $numLaserPowerPatterns; $x++)
	{
		runEngravingPattern($offsetX + $sizeXdevider * $x, $offsetY + $sizeYdevider * $y, $patternSizeX, $patternSizeY, $resX, $resY, ($feedRateMax - $feedRateDevider * $y), ($laserMin + $laserPowerDevider * $x), $overScan);
	}
}

// *********************** End creating the pattern ****************************** //


print("M5 S$laserOff ;Turn laser off\n");
//print("G1 X$offsetX Y$offsetY F$travelRate ;Go home\n");
print("G1 X0 Y0 S0 F$travelRate ;Go home\n");

?>
