<?php

	function youtubeID($url)
	{   
		if(!empty($url)){
		if (stristr($url,'youtu.be/'))
			{preg_match('/(https:|http:|)(\/\/www\.|\/\/|)(.*?)\/(.{11})/i', $url, $final_ID); return $final_ID[4]; }  
		else 
			{ @preg_match('/(https:|http:|):(\/\/www\.|\/\/|)(.*?)\/(embed\/|watch.*?v=|)([a-z_A-Z0-9\-]{11})/i', $url, $IDD); return $IDD[5]; }
		}
	}

    function vimeoID($url = '') {    
        $regs = array();    
        $videoId = '';      

		$urlParts = explode("/", parse_url($url, PHP_URL_PATH));
		$videoId = (int)$urlParts[count($urlParts)-1];
		return $videoId;    
    }

?>