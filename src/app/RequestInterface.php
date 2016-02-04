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
		protected $locale;
		private $session = null;

		protected function __construct($args, $locale){
			$this->args = $args;
			$this->locale = $locale;
		}

		public function handle($methodName){
			if(method_exists($this, $methodName)){
				$reflection = new ReflectionMethod($this, $methodName);

				// Verify that it is allowed to call this method
				if (!$reflection->isProtected() || $reflection->isStatic() || $reflection->getNumberOfParameters() != 0) {
					throw new RuntimeException(sprintf($this->locale->get("query.generic.unknown_command"), $methodName));
				}

				// Call the method dynamically
				return $this->$methodName();
			}
			else{
				throw new RuntimeException(sprintf($this->locale->get("query.generic.unknown_command"), $methodName));
			}
		}

		public function handleByArgsKey($key){
			if(!array_key_exists($key, $this->args)){return self::buildResponse(false, array("msg" => sprintf("Required parameter '%s' is not defined"), $key));}
			return $this->handle($this->args[$key]);
		}

		public function handleAndSendResponseByArgsKey($key){
			if(!array_key_exists($key, $this->args)){
				print(json_encode(self::buildResponse(false, array("msg" => sprintf("Required parameter '%s' is not defined", $key)))));
			}
			else{
				$this->handleAndSendResponse($this->args[$key]);
			}
		}

		public function handleAndSendResponse($methodName){
			try{
				print(json_encode($this->handle($methodName)));
			}
			catch(RuntimeException $e){
				print(json_encode(self::buildResponse(false, array("msg" => $e->getMessage()))));
			}
			catch(InvalidArgumentException $e){
				print(json_encode(self::buildResponse(false, array("msg" => $e->getMessage()))));
			}
			catch(MissingSessionException $e){
				print(json_encode(self::buildResponse(false, array("msg" => $this->locale->get("query.generic.no_login")))));
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

		/**
		 * Verifies the parameters in '$this->args'.
		 * <p>Therefore '<i>$requiredKeys</i>' has to be a map from key -> option.</p>
		 * <p>Possible 'options':</p>
		 * <ul>
		 * <li><b>null</b>: Just check that the parameter exists</li>
		 * <li><b>string</b>: A regular expression that is used to verify the string</li>
		 * <li><b>integer</b>: The string will be encoded using 'htmlspecialchars'. This int value represents the maximum length of the string.</li>
		 * </ul>
		 *
		 * @param array<string,mixed> $requiredKeys Map key -> [null | regex | strlengh]
		 * @param boolean $areOptional If this value is <code>true</code>, this function will throw a <code>InvalidArgumentException</code> if one parameter does not exist.
		 * @throws InvalidArgumentException
		 */
		private function verifyParameters($requiredKeys, $areOptional){
			foreach ($requiredKeys as $key => $value){
				// Check if the argument exists
				if(array_key_exists($key, $this->args)){
					// Apply a regular expression to verify thae argument
					if($value != null){
						if(is_int($value)){
							$str = htmlspecialchars($this->args[$key], ENT_QUOTES);
							if(strlen($str) > $value){
								throw new InvalidArgumentException(sprintf($this->locale->get("query.generic.max_str_length"), $key));
							}
							$this->args[$key] = $str;
						}
						else{
							if(!preg_match($value, $this->args[$key])){
								throw new InvalidArgumentException(sprintf($this->locale->get("query.generic.invalid_value"), $key));
							}
						}
					}
				}
				else{
					if(!$areOptional){
						throw new InvalidArgumentException(sprintf("Required parameter '%s' is not defined", $key));
					}
				}

			}
		}

		public function requireLogin(){
			require_once(__DIR__ . "/../app/SessionManager.php");

			if($this->session == null){
				$this->session = new SessionManager();
			}

			if(!$this->session->isSignedIn()){
				throw new MissingSessionException();
			}
			return $this->session;
		}

		public static function buildResponse($success, $data = null){
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
