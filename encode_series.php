#!/usr/bin/php
<?php
/*
encode_series. Encodes my Star Trek DVDs
Expects to find the ISOs in the current directory named like so
ST-DS9-S1-D1.iso  (or whatever $showname is)

Outputs Season 1/ST-DS9.S01E01-02.Emissary.mkv

Public domain
*/


$showname="ST-DS9"; // All names are prefixed with this

$episodes=array(
	1 => array(  // Season 1
		"|Emissary", // Put | in front of the title to make it a double episode
		"Past Prologue",
		"A Man Alone",
		"Babel",
		"Captive Pursuit",
		"Q-Less",
		"Dax",
		"The Passenger",
		"Move Along Home",
		"The Nagus",
		"Vortex",
		"Battle Lines",
		"The Storyteller",
		"Progress",
		"If Wishes Were Horses",
		"The Forsaken",
		"Dramatis Personae",
		"Duet",
		"In the Hands of the Prophets"
	),
	2 => array(
		"The Homecoming",
		"The Circle",
		"The Siege"
		// And so on
	)
);



for($season=1;$season<=7;$season++)
{
	@mkdir("Season ${season}");
	@mkdir("Season ${season}/Extras");
	$epno=1;
	$extra=1;
	$eps=$episodes[$season];
	for($disc=1;$disc<=7;$disc++)
	{
		$iso="${showname}-S${season}-D${disc}.iso";
		if(!file_exists($iso)) continue;
		$encoder=new HandBrake($iso);
		foreach($encoder->titles as $title=>$data)
		{
			$eptitle=array_shift($eps);
			if(!empty($eptitle))
			{
				$encoder->titles[$title]["chapters_file"]=sprintf("${showname}.S%02dE%02d.chapters.csv", $season, $epno);
				if($eptitle[0]=='|')
				{
					$eptitle=substr($eptitle,1);
					$encoder->titles[$title]["output"]=sprintf("Season ${season}/${showname}.S0${season}E%02d-%02d.%s.mkv",$epno, $epno+1,$eptitle);			
					$epno+=2;
				}
				else
				{
					$encoder->titles[$title]["output"]=sprintf("Season ${season}/${showname}.S0${season}E%02d.%s.mkv",$epno,$eptitle);			
					$epno++;
				}
			}
			else
			{
				$encoder->titles[$title]["output"]=sprintf("Season ${season}/Extras/${showname}-S${season}-Extra - %02d.mkv",$extra);			
				$extra++;
			}
			echo "\033]0;".basename($encoder->titles[$title]["output"])."\007";
			$encoder->encode($title);
		}
		rename($iso, "Done/$iso");
	}
}
class HandBrake
{
	private $source;
	public $titles;

	public function __construct($source)
	{
		$this->source=$source;
		$this->scan();
	}

	private function scan()
	{
		$titles=array();
		$title=null;
		$cmdline="HandBrakeCLI --scan -t 0 -i \"".$this->source."\" 2>&1";
		$fp=popen($cmdline, "r");
		$find="title";
		while(($row=fgets($fp))!==false)
		{
			$row=rtrim($row);
			if(preg_match('/^\\+ title (\\d+):$/', $row, $matches))
			{
				$title=$matches[1];
				$titles[$title]=array();
				continue;
			}
			else if(preg_match('/^  \\+ audio tracks:$/', $row))
			{
				$find="audio";
				$titles[$title]["audio"]=array();
				continue;
			}
			else if(preg_match('/^  \\+ subtitle tracks:$/', $row))
			{
				$find="subtitle";
				$titles[$title]["subtitles"]=array();
				continue;
			}
			else if( $find=="audio" && preg_match('/^    \\+ (\d+), (.+) \\((.+)\\) \\((.+)\\) \\(.+: (.+)\\).*/', $row, $matches) )
			{
				$track=$matches[1];
				$titles[$title]["audio"][$track]=array(
					"lang"=>$matches[2],
					"codec"=>$matches[3],
					"channels"=>$matches[4],
					"iso"=>$matches[5]
					);
			}

			else if( $find=="subtitle" && preg_match('/^    \\+ (\d+), (.+) \\(.+: (.+)\\) .*/', $row, $matches) )
			{
				$track=$matches[1];
				$titles[$title]["subtitles"][$track]=array(
					"lang"=>$matches[2],
					"iso"=>$matches[3]
					);
			}
		}
		$this->titles=$titles;
		foreach($this->titles as $title=>$data)
		{
			$this->find_primary_audio($title);
		}
		return $this->titles;
	}

	private function find_primary_audio($title)
	{
		foreach($this->titles[$title]["audio"] as $track=>$data)
		{
			if($data['iso']=="eng" && substr($data['channels'],0,3)=='5.1')
			{
				$this->titles[$title]["primary_audio"]=$track;
				return $track;
			}
		}

		foreach($this->titles[$title]["audio"] as $track=>$data)
		{
			if($data['iso']=="eng")
			{
				$this->titles[$title]["primary_audio"]=$track;
				return $track;
			}
		}
		return false;
	}

	public function encode($title)
	{
		$pa=0;
		if(isset($this->titles[$title]["primary_audio"])) $pa=$this->titles[$title]["primary_audio"];
		if(!$pa) print_r($this->titles[$title]);
		$cmdline="HandBrakeCLI -t $title -i \"".$this->source."\" -e x264";
		$cmdline.=" -q 18.0"; // For some reason, lower means better
		if(substr($this->titles[$title]["audio"][$pa]["channels"],0,3)=="5.1")
		{
			// Dual audio. First a downmixed stereo and then the original 5.1
			$cmdline.=" -a $pa,$pa -E faac,copy:ac3 -B 160,160 -6 dpl2,auto -R Auto,Auto -D 0.0";
		}
		else
		{
			$cmdline.=" -a $pa -E faac -B 160 -R Auto -D 0.0";
		}
		$cmdline.=" -f mkv --deinterlace slow --loose-anamorphic -x ref=3:weightp=1:subq=7:rc-lookahead=10:trellis=1:8x8dct=0 -m";
		if(isset($this->titles[$title]["chapters_file"]) && file_exists($this->titles[$title]["chapters_file"]))
		{
			$cmdline.=" " . $this->titles[$title]["chapters_file"]; // Doesn't seem to work with the version of HandBrakeCLI I use
		}
		$cmdline.=" -s " . implode(array_keys($this->titles[$title]["subtitles"]), ",");
		$cmdline.=" -o \"".$this->titles[$title]["output"]."\"";
		//echo $cmdline . "\n";
		passthru($cmdline);
	}
}
?>
