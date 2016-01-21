<?php
	/*	GeoCat - Geocaching and -Tracking platform
	 Copyright (C) 2016 Bastian Kraemer

	 RequestInterface.php

	 This program is free software: you can redistribute it and/or modify
	 it under the terms of the GNU General Public License as published by
	 the Free Software Foundation, either version 3 of the License, or
	 (at your option) any later version.

	 This program is distributed in the hope that it will be useful,
	 but WITHOUT ANY WARRANTY; without even the implied warranty of
	 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 GNU General Public License for more details.

	 You should have received a copy of the GNU General Public License
	 along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 */

	abstract class RequestInterface {

		protected $args;

		protected function __construct($args){
			$this->args = $args;
		}

		public function handle($methodName){
			if(method_exists($this, $methodName)){
				$reflection = new ReflectionMethod($this, $methodName);

				// Verify that it is allowed to call this method
				if (!$reflection->isProtected() || $reflection->isStatic() || $reflection->getNumberOfParameters() != 0) {
					throw new RuntimeException("Unknown command '" . $methodName . "'.");
				}

				// Call the method dynamically
				return $this->$methodName();
			}
			else{
				throw new RuntimeException("Unknown command '" . $methodName . "'.");
			}
		}

		public function handleByArgsKey($key){
			if(!array_key_exists($key, $this->args)){throw new InvalidArgumentException("Key '" . $task ."' is not defined.");}
			return $this->handle($this->args[$key]);
		}

		public function handleAndSendResponseByArgsKey($key){
			if(!array_key_exists($key, $this->args)){throw new InvalidArgumentException("Key '" . $task ."' is not defined.");}
			$this->handleAndSendResponse($this->args[$key]);
		}

		public function handleAndSendResponse($methodName){
			try{
				print(json_encode($this->handle($methodName)));
			}
			catch(RuntimeException $e){
				print(json_encode(self::buildResponse(false, array("msg" => $e->getMessage()))));
			}
			catch(InvalidArgumentException $e){
				print(json_encode(self::buildResponse(false, array("msg" => "Invalid request: " . $e->getMessage()))));
			}
			catch(MissingSessionException $e){
				print(json_encode(self::buildResponse(false, array("msg" => "Access denied. Please sign in at first."))));
			}
			catch(Exception $e){
				print(json_encode(self::buildResponse(false, array("msg" => "Internal server error: " . $e->getMessage()))));
			}
		}

		public function requireParameters($requiredKeys){
			$this->verifyParameters($requiredKeys, false);
		}

		public function verifyOptionalParameters($requiredKeys){
			$this->verifyParameters($requiredKeys, true);
		}

		public function assignOptionalParameter($key, $value){
			if(!array_key_exists($key, $this->args)){
				$this->args[$key] = $value;
			}
		}

		private function verifyParameters($requiredKeys, $areOptional){
			foreach ($requiredKeys as $key => $value){
				// Check if the argument exists
				if(array_key_exists($key, $this->args)){
					// Apply a regular expression to verify thae argument
					if($value != null){
						if(!preg_match($value, $this->args[$key])){
							throw new InvalidArgumentException("Value of parameter '" . $key . "' is invalid.");
						}
					}
				}
				else{

					if(!$areOptional){
						throw new InvalidArgumentException("Required paremeter '" . $key . "' is not defined.");
					}
				}

			}
		}

		protected static function buildResponse($success, $data = null){
			if($data == null){$data = array();}
			$data["status"] = $success ? "ok" : "failed";
			return $data;
		}

		protected static function defaultNameRegEx($minLength, $maxLength){
			return "/^[A-Za-z0-9ÄäÖöÜüß_ \,\;\.\:\!\#\-\*\(\)]{" . $minLength . "," . $maxLength . "}$/";
		}

		protected static function defaultTimeRegEx(){
			return "/^(\d{4})-(\d{2})-(\d{2}) (\d{2})\:(\d{2})(\:(\d{2}))?$/";
		}
	}
?>
