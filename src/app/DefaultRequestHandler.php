<?php
	/**
	 * The class allows you to extract the data from the request parameters (usually "$_POST")
	 * The advantage of this class is the possibility to extract encoded information from parameter, by default a JSON object and append this to the parameters array.
	 * The parameter has a fixed name called "data", the encoding of the data parameter can be specified by the parrameter "data_type" (this name ist also predefined).
	 *
	 * At the moment only the type "JSON" is supported.
	 *
	 * Example:
	 *
	 * The request <br>
	 * <i>'./my-site.php?param1=Value1&data_type=json&data={"param1": "value1", "param2": "value2"}'</i><br />
	 * will return <br />
	 * <i>array("param1" => "value1", "param2" => "value2", "param3" => "value3");</i>
	 *
	 * After handling the request you can use this class to encode your response by using the econding from the request.
	 */
	class DefaultRequestHandler {

		/**
		 * Data type of the request
		 * @var string
		 */
		private $responseDataType;

		/**
		 * The decoded data (object) of the request
		 * @var string[]
		 */
		public $data;

		/**
		 * Creates a new DefaultRequestHandler object
		 * @param string[] $parameters The parameters of your request (usually $_POST)
		 */
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

		/**
		 * Encodes a response by using the encoding of the request
		 * @param string[] $responseData
		 * @return string
		 */
		public function prepareResponse($responseData){
			return self::encodeData($responseData, $this->responseDataType);
		}

		/**
		 * Decodes an object
		 * Currently supported data types: json
		 * @param string[] $data
		 * @param string $type
		 * @throws InvalidArgumentException If the encoding type is not supported or $data and $type are <code>null</code>
		 */
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

		/**
		 * Encodes an object.
		 * Currently supported data types: json
		 * @param string[] $data
		 * @param string $type
		 */
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
