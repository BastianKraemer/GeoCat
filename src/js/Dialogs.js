/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer, Raphael Harzer
 *
 * ChallengeInfoController.js
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

var Dialogs = (function(){
	// private functions
	var createTextInputField = function(id, type){
		var input = document.createElement("input");
		if(id != null){
			input.name = id;
			input.id = id;
		}
		input.type = type;
		return input;
	};

	var createLabel = function(text, forId){
		var label = document.createElement("label");
		label.innerHTML = text;
		label.setAttribute("for", forId);
		return label;
	};

	var createCheckbox = function(id, checked){
		var input = document.createElement("input");
		input.id = id;
		input.className = "substance-checkbox substance-animated";
		input.name = id;
		input.type = "checkbox";
		input.checked = (checked ? "checked" : "");
		return input;
	};

	var simulatePageReload = function(){
		$.mobile.activePage.trigger("pagebeforehide");
		$.mobile.activePage.trigger("pageshow");
	};

	var createCheckboxAndLabel = function(id, text, checkboxValue){
		var checkboxContainer = document.createElement("div");
		checkboxContainer.setAttribute("data-role", "none");
		var checkboxLabel = createLabel(text, id);
		var checkbox = createCheckbox(id, checkboxValue);
		checkboxContainer.appendChild(checkbox);
		checkboxContainer.appendChild(checkboxLabel);
		checkbox.setAttribute("data-role", "none");
		checkboxLabel.setAttribute("data-role", "none");
		return checkboxContainer;
	}

	// Public function
	return {
		// Display the 'login dialog'
		showLoginDialog: function(){
			// Create the div container
			var container = document.createElement("div");
			container.className = "input-dialog";

			var h = document.createElement("h3");
			h.innerHTML = "GeoCat Login"

			// The form is neccessary, otherwise he browser won't ask you to store your credentials
			var form = document.createElement("form");
			form.setAttribute("action", "./app/GeoCat.php");
			form.setAttribute("target", "formtarget");

			// ...this iframe is a workaround - the browser will only ask to store your passwords if a submit button sends the request
			// To realize this we will target this useless iframe
			var iframe = document.createElement("iframe");
			iframe.name= "formtarget";
			iframe.style.display = "none";

			// Create input elements
			var userInput = createTextInputField("login-username", "text");
			var pwInput = createTextInputField("login-password", "password");

			var userLabel = createLabel(GeoCat.locale.get("login.username", "Username or E-Mail address") + ":", "login-username");
			var pwLabel = createLabel(GeoCat.locale.get("login.password", "Password") + ":", "login-password");

			var p = document.createElement("p");

			var checkboxContainer = createCheckboxAndLabel("rememberme", GeoCat.locale.get("login.stayloggedin", "Stay logged in"), GeoCat.hasCookie('GEOCAT_LOGIN'));

			var span1 = document.createElement("span");
			span1.textContent = GeoCat.locale.get("login.create_account", "Create an account");
			span1.onclick = function(){
				SubstanceTheme.hideCurrentNotification();
				setTimeout(function(){Dialogs.showCreateAccountDialog();}, 400);
			};
			span1.style.float = "right";

			p.appendChild(span1);

			container.appendChild(h);
			form.appendChild(userLabel);
			form.appendChild(userInput);
			form.appendChild(pwLabel);
			form.appendChild(pwInput);
			form.appendChild(checkboxContainer);
			container.appendChild(form);
			container.appendChild(iframe);
			container.appendChild(p);

			// Callback - this function will be executed when the user clicks on the green tick
			var onAcceptFunction = function(enableDialogAcceptButton){
				if(!$('#rememberme').is(":checked")){
					GeoCat.deleteLoginCookie("GEOCAT_LOGIN");
				}
				GeoCat.login(
					$("#login-username").val(), $("#login-password").val(),
					$('#rememberme').is(':checked'),
					function(success){
						if(success){
							form.submit();
							SubstanceTheme.hideCurrentNotification();
						}
						else{
							enableDialogAcceptButton(true);
							container.classList.add('shake-horizontal');
							$("#login-username, #login-password").css("borderColor", "red").css("color", "red");
							setTimeout(function(){
								container.classList.remove('shake-horizontal');
							}, 500);

							setTimeout(function(){
								$("#login-username, #login-password").removeAttr('style');
							}, 1500);
						}
					});
			};

			var performAccept = SubstanceTheme.showYesNoDialog(container, $.mobile.activePage[0], onAcceptFunction, null, "substance-white", false);

			var onKeyDownFunction = function(event){
				var key = event.keyCode;
				if(key == 13){
					performAccept();
				}
			};

			userInput.onkeydown = onKeyDownFunction;
			pwInput.onkeydown = onKeyDownFunction;
			userInput.select();
		},

		// Display the 'createAccount dialog'
		showCreateAccountDialog: function(){
			// Create the div container
			var container = document.createElement("div");
			container.className = "input-dialog";

			var h = document.createElement("h3");
			h.innerHTML = "Account erstellen"

			// Input elements
			var userInput = createTextInputField("create-account-username", "text");
			var emailInput = createTextInputField("create-account-email", "text");
			var pw1Input = createTextInputField("create-account-password1", "password");
			var pw2Input = createTextInputField("create-account-password2", "password");

			var userLabel = createLabel(GeoCat.locale.get("createacc.username", "Username") + ":", "create-account-username");
			var emailLabel = createLabel(GeoCat.locale.get("createacc.email", "E-mail address") + ":", "create-account-email");
			var pw1Label = createLabel(GeoCat.locale.get("createacc.pw", "Please enter a password") + ":", "create-account-password1");
			var pw2Label = createLabel(GeoCat.locale.get("createacc.pw_repeat", "Please enter the password again") + ":", "create-account-password2");

			var privacyPolicy = GeoCat.locale.get("createacc.privacy_policy", "privacy policy");
			var text = sprintf(GeoCat.locale.get("createacc.accept_policy", "I accept the {0}"),
								["<a href=\"" + GeoCat.privacyPolicHref + "\" style=\"font-weight: 500\" target=\"_blank\">" + privacyPolicy + "</a>"]);

			var checkbox = createCheckboxAndLabel("create-account-accept_policy", text, false);

			var p = document.createElement("p");
			p.className = "small-margin";
			p.style = "color: red;";

			// Append all child nodes
			container.appendChild(h);
			container.appendChild(userLabel);
			container.appendChild(userInput);
			container.appendChild(emailLabel);
			container.appendChild(emailInput);
			container.appendChild(pw1Label);
			container.appendChild(pw1Input);
			container.appendChild(pw2Label);
			container.appendChild(pw2Input);
			container.appendChild(checkbox);
			container.appendChild(p);

			// Display the created elements
			SubstanceTheme.showYesNoDialog(
				container, $.mobile.activePage[0],
				function(enableDialogAcceptButton){
					if($("#create-account-password1").val() == $("#create-account-password2").val()){
						if(!$("#create-account-accept_policy").is(":checked")){
							p.textContent = GeoCat.locale.get("createacc.policy_not_accepted", "Please accept the privacy policy first.");
							//Re-enable the button - the button has been disabled to avoid multiple clicks
							enableDialogAcceptButton(true);
						}
						else{
							// Everything okay: Send the crate Account request
							GeoCat.createAccount(
								$("#create-account-username").val(), $("#create-account-email").val(), $("#create-account-password1").val(),
								function(success, responseMsg){
									if(success){
										// Account successfully created
										SubstanceTheme.hideCurrentNotification();
									}
									else{
										enableDialogAcceptButton(true);
										p.textContent = responseMsg;
									}
								});
						}
					}
					else{
						p.textContent = GeoCat.locale.get("createacc.passwd_not_equal", "The entered passwords doesn't match.")
						enableDialogAcceptButton(true);
					}
				},
				null, "substance-white", false);

			setTimeout(function(){
				// Workaround:
				// If the browser stores username and password - these fields are sometimes filled too...
				userInput.value = "";
				emailInput.value = "";
				pw1Input.value = "";
			}, 100);
		},

		showInputDialog: function(title, textContent, autoHide, acceptCallback, canceledCallback){
			var container = document.createElement("div");
			container.className = "input-dialog";

			var h = document.createElement("h3");
			h.innerText = title

			var span = document.createElement("span");
			span.innerText = textContent;

			var textInput = createTextInputField(null, "text");

			container.appendChild(h);
			container.appendChild(span);
			container.appendChild(textInput);

			var performAccept = SubstanceTheme.showYesNoDialog(
									container, $.mobile.activePage[0],
									function(){acceptCallback(textInput.value);},
									canceledCallback, "substance-white", autoHide);

			textInput.onkeydown = function(event){
				var key = event.keyCode;
				if(key == 13){
					performAccept();
				}
			};
			textInput.select();
		}
	}
})();
