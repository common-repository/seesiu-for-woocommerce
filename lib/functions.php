<?php
if( !function_exists('sefw_is_valid_key')) {
	function sefw_is_valid_key( $key, $shop ){
		$url		= 'https://admin.seesiu.com/wp-json/gminus/v1/authenticate/key='.$key.'&shop='.$shop;
		
		$response	= wp_remote_get( $url );
		$body		= wp_remote_retrieve_body( $response );
		$http_code	= wp_remote_retrieve_response_code( $response );
		
		if( $http_code == 200 ){
			$result	= json_decode( $body );
			return $result->message;
		}else{
			return 'Something went wrong.Try again later.';
		}
	}
}

if( !function_exists('sefw_get_exchange_rates')) {
	function sefw_get_exchange_rates(){	
		$endpoint	= 'latest';
		$access_key	= '59102ba814569632b9ad83f1c3ea0a5c';
		$url		= 'http://data.fixer.io/api/'.$endpoint.'?access_key='.$access_key;
		
		$response	= wp_remote_get( $url );
		$body		= wp_remote_retrieve_body( $response );
		$http_code	= wp_remote_retrieve_response_code( $response );
		
		if( $http_code == 200 ){
			$result	= json_decode( $body, true );
			return $result['rates'];
		}else{
			return 0;
		}
	}
}
?>