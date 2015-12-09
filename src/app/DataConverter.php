<?php
	/**
	 * File DataConverter.php
	 */
	class DataConverter {

		public static function decodeData($type, $data){
			if($type != null && $data != null){
				if(strtolower($type) == "json"){
					return (array) json_decode($data);
				}
				else{
					throw new InvalidArgumentException("Unsupported message format.");
				}
			}
			else{
				throw new InvalidArgumentException("Missing parameters: 'type' or 'data' is undefined.");
			}
		}

		public static function encodeData($type, $data){
			if(strtolower($type) == "json"){
				return json_encode($data);
			}
			else{
				error_log("Unable to encode data to with format type '" . $type . "'. Using 'JSON' instead.");
				return self::encodeData("json", $data);
			}
		}
	}
?>
