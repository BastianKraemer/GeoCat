<?php
/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer, Raphael Harzer
 *
 * RequestInterface.php
 *
 * This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * GeoCat Request interface
 * @package app
 */

	/**
	 * Base class for each GeoCat query interface
	 *
	 * This class can be used for a simple http request handling.
	 * Furthermore you can use the 'buildDResponse' method to implement a RESTful service which will return a JSON object
	 *
	 * <h3>Usage:</h3>
	 * To do use this class, you have to extend it by your own class and implement at least one protected function which will handle a specific request.
	 * After this you can select the executed function from the request itself, therefore you have to choose a
	 * request paremeter (for example 'task') which has to contain your method name.
	 *
	 * Note: You can only call proteced functions without parameters.
	 *
	 */
	abstract class RequestInterface {

		/**
		 * @ignore
		 */
		protected $args;

		/**
		 * @ignore
		 */
		protected $locale;

		/**
		 * @ignore
		 */
		private $session = null;

		/**
		 * Create new RequestInterface object
		 * @param array $args Arguments for the query interface, most likely the POST-Parameters
		 * @param JSONLocale $locale The language for this request
		 */
		protected function __construct($args, $locale){
			$this->args = $args;
			$this->locale = $locale;
		}

		/**
		 * Handle a request by specifing the called function name
		 * @param string $methodName The name of the (protected) function that should be called
		 * @return mixed The value that has been returned by the called function
		 * @throws RuntimeException If there is no protected function with this name (and without parameters)
		 */
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

		/**
		 * Handle a request by specifing the called function name using on parameter of your request
		 * @param string $key The name of the request parameters
		 * @return mixed The value that has been returned by the called function
		 */
		public function handleByArgsKey($key){
			if(!array_key_exists($key, $this->args)){return self::buildResponse(false, array("msg" => sprintf("Required parameter '%s' is not defined"), $key));}
			return $this->handle($this->args[$key]);
		}

		/**
		 * Handle a request by specifing the called function name using on parameter of your request
		 *
		 * In comparison to {@link handleByArgsKey}, this function will send the response directly instead of returning it.
		 * @param string $key The name of the request parameters
		 */
		public function handleAndSendResponseByArgsKey($key){
			if(!array_key_exists($key, $this->args)){
				print(json_encode(self::buildResponse(false, array("msg" => sprintf("Required parameter '%s' is not defined", $key)))));
			}
			else{
				$this->handleAndSendResponse($this->args[$key]);
			}
		}

		/**
		 * Handle a request by specifing the called function name
		 *
		 * In comparison to {@link handle}, this function will send the response directly instead of returning it.
		 * @param string $methodName The name of the (protected) function that should be called
		 */
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

		/**
		 * This function can be used verify a list of required parameters
		 *
		 * It is highly recommended to call this method for every required parameter.
		 * Furthermore all parameters will be converted with 'htmlspecialchars' to prevent Cross-Side-Scripting (XSS).
		 * @param array $requiredKeys A map of request parameter (string) -> regular expression (string)
		 */
		public function requireParameters($requiredKeys){
			$this->verifyParameters($requiredKeys, false);
		}

		/**
		 * This function can be used verify a list of optional parameters
		 *
		 * It is highly recommended to call this method for every optional parameter.
		 * Furthermore all parameters will be converted with 'htmlspecialchars' to prevent Cross-Side-Scripting (XSS).
		 * @param array $requiredKeys A map of request parameter (string) -> regular expression (string)
		 */
		public function verifyOptionalParameters($requiredKeys){
			$this->verifyParameters($requiredKeys, true);
		}

		/**
		 * Assings a value for an optional parameter
		 * @param string $key
		 * @param mixeed $value
		 */
		public function assignOptionalParameter($key, $value){
			if(!array_key_exists($key, $this->args)){
				$this->args[$key] = $value;
			}
		}

		/**
		 * Returns if tehre is a request parameter with this name
		 * @param string $key name of the request parameter
		 * @return boolean
		 */
		public function hasParameter($key){
			return array_key_exists($key, $this->args);
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
					// Apply a regular expression to verify the parameters
					$this->args[$key] = htmlspecialchars($this->args[$key]);
					if($value != null){
						if(!preg_match($value, $this->args[$key])){
							throw new InvalidArgumentException(sprintf($this->locale->get("query.generic.invalid_value"), $key));
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

		/**
		 * This function returns the current session or throw a in
		 * @return SessionManager
		 * @throws MissingSessionException If the user is not signed in
		 */
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

		/**
		 * Build a default response (as JSON object) for a RESTful service
		 * @param boolean $success This will set the 'status' property to 'ok' or 'failed'
		 * @param array $data Data for your resonse (optional)
		 *
		 */
		public static function buildResponse($success, $data = null){
			if($data == null){$data = array();}
			$data["status"] = $success ? "ok" : "failed";
			return $data;
		}

		/**
		 * Returns a regular expression for a default text match
		 * @param integer $minLength Minimum text length
		 * @param integer $maxLength Maximum text length
		 * @param boolean $allowMultipleLines Are mutliple lines allowed for the content?
		 * @return string The regular expression string
		 */
		protected static function defaultTextRegEx($minLength, $maxLength, $allowMultipleLines = false){
			return "/^.{" . $minLength . "," . $maxLength . "}$/" . ($allowMultipleLines ? "m" : "");
		}

		/**
		 * Returns a regular expression for a default timestamp match
		 * @return string The regular expression string
		 */
		protected static function defaultTimeRegEx(){
			return "/^(\d{4})-(\d{2})-(\d{2}) (\d{2})\:(\d{2})(\:(\d{2}))?$/";
		}
	}
?>
