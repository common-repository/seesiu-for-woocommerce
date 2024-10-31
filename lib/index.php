<?php
function sefw_include_dir($path){
	if ($dir=opendir($path."/")) {
		while (false !== ($entry = readdir($dir))) {
			$filename=explode(".",$entry);
			$ext=end($filename);
			if(!in_array($entry,array('index.php','..','.'))){				
				if(is_dir($path.'/'.$entry)){
					sefw_include_dir($path.'/'.$entry);
				}elseif($ext=="php"){
					require_once($path.'/'.$entry);					
				}
			}
		}
	}
}
sefw_include_dir( SEFW_BMC_LIB_PATH );
?>