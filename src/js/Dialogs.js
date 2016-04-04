var Dialogs = (function(){
	// private functions
	var createTextInputField = function(id, type){
		var input = document.createElement("input");
		input.id = id
		input.name = id;
		input.type = type;
		return input;
	};

	var createLabel = function(text, forId){
		var label = document.createElement("label");
		label.className = "no-shadow";
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
	}

	var simulatePageReload = function(){
		$.mobile.activePage.trigger("pagebeforehide");
		$.mobile.activePage.trigger("pageshow");
	}

	// Public function
	return {
		showLoginDialog: function(pathToRoot){
			var container = document.createElement("div");
			container.className = "login-dialog";

			var h = document.createElement("h3");
			h.className = "no-shadow";
			h.innerHTML = "GeoCat Login"

			var form = document.createElement("form");
			form.setAttribute("action", "./app/GeoCat.php");
			form.setAttribute("target", "formtarget");

			var iframe = document.createElement("iframe");
			iframe.name= "formtarget";
			iframe.style.display = "none";

			var userInput = createTextInputField("login-username", "text");
			var pwInput = createTextInputField("login-password", "password");

			var userLabel = createLabel(GeoCat.locale.get("login.username", "Username or E-Mail address") + ":", "login-username");
			var pwLabel = createLabel(GeoCat.locale.get("login.password", "Password") + ":", "login-password");

			var p = document.createElement("p");
			p.className = "no-shadow";

			var checkboxContainer = document.createElement("div");
			checkboxContainer.setAttribute("data-role", "none");
			var checkboxLabel = createLabel(GeoCat.locale.get("login.stayloggedin", "Stay logged in"), "rememberme");
			var checkbox = createCheckbox("rememberme", GeoCat.hasCookie('GEOCAT_LOGIN'));
			checkboxContainer.appendChild(checkbox);
			checkboxContainer.appendChild(checkboxLabel);
			checkbox.setAttribute("data-role", "none");
			checkboxLabel.setAttribute("data-role", "none");

			var span1 = document.createElement("span");
			span1.textContent = GeoCat.locale.get("login.create_account", "Create an account");
			span1.onclick = function(){
				SubstanceTheme.hideCurrentNotification();
				setTimeout(function(){Dialogs.showCreateAccountDialog(pathToRoot);}, 400);
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
					},
					pathToRoot);
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

		showCreateAccountDialog: function(pathToRoot){
			var container = document.createElement("div");
			container.className = "login-dialog";

			var h = document.createElement("h3");
			h.className = "no-shadow";
			h.innerHTML = "Account erstellen"

			var userInput = createTextInputField("create-account-username", "text");
			var emailInput = createTextInputField("create-account-email", "text");
			var pw1Input = createTextInputField("create-account-password1", "password");
			var pw2Input = createTextInputField("create-account-password2", "password");

			var userLabel = createLabel(GeoCat.locale.get("createacc.username", "Username") + ":", "create-account-username");
			var emailLabel = createLabel(GeoCat.locale.get("createacc.email", "E-mail address") + ":", "create-account-email");
			var pw1Label = createLabel(GeoCat.locale.get("createacc.pw", "Please enter a password") + ":", "create-account-password1");
			var pw2Label = createLabel(GeoCat.locale.get("createacc.pw_repeat", "Please enter the password again") + ":", "create-account-password2");

			var p = document.createElement("p");
			p.className = "no-shadow small-margin";
			p.style = "color: red;";

			container.appendChild(h);
			container.appendChild(userLabel);
			container.appendChild(userInput);
			container.appendChild(emailLabel);
			container.appendChild(emailInput);
			container.appendChild(pw1Label);
			container.appendChild(pw1Input);
			container.appendChild(pw2Label);
			container.appendChild(pw2Input);
			container.appendChild(p);

			SubstanceTheme.showYesNoDialog(
				container, $.mobile.activePage[0],
				function(enableDialogAcceptButton){
					if($("#create-account-password1").val() == $("#create-account-password2").val()){
						GeoCat.createAccount(
							$("#create-account-username").val(), $("#create-account-email").val(), $("#create-account-password1").val(),
							function(success, responseMsg){
								if(success){
									SubstanceTheme.hideCurrentNotification();
								}
								else{
									enableDialogAcceptButton(true);
									p.textContent = responseMsg;
								}
							},
							pathToRoot);
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
		}
	}
})();
