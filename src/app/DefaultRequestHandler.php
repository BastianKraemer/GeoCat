<?php
	/**
	 * File DefaultRequestHandler.php
	 */
	class DefaultRequestHandler {
		private $responseDataType;
		public $data;
		public function __construct($parameters){
			$dataType = array_key_exists("data_type", $parameters) ? $parameters["data_type"] : null;
			$this->responseDataType = array_key_exists("response_type", $parameters) ? $parameters["response_type"] : ($dataType != null ? $dataType : "json");

			if($dataType == null){
				$this->data = $parameters;
			}
			else{
				if(array_key_exists("data", $parameters)){
					$this->data = self::decodeData($parameters["data"], $dataType);
				}
				if(array_key_exists("cmd", $parameters)){$this->data["cmd"] = $parameters["cmd"];}
			}
		}

		function prepareResponse($responseData){
			return self::encodeData($responseData, $this->responseDataType);
		}

		public static function decodeData($data, $type){
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

		public static function encodeData($data, $type){

			if(strtolower($type) == "json"){
				return json_encode($data);
			}
			else{
				error_log("Unable to encode data to with format type '" . $type . "'. Using 'JSON' instead.");
				return self::encodeData($data, "json");
			}
		}
	}
?>
