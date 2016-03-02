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

	// Public function
	return {
		showLoginDialog: function(pathToRoot){
			var container = document.createElement("div");
			container.className = "login-dialog";

			var h = document.createElement("h3");
			h.className = "no-shadow";
			h.innerHTML = "GeoCat Login"

			var userInput = createTextInputField("login-username", "text");
			var pwInput = createTextInputField("login-password", "password");

			var userLabel = createLabel(GeoCat.locale.get("login.username", "Username or E-Mail address") + ":", "login-username");
			var pwLabel = createLabel(GeoCat.locale.get("login.password", "Password") + ":", "login-password");

			var p = document.createElement("p");
			p.className = "no-shadow";

			var span1 = document.createElement("span");
			span1.textContent = GeoCat.locale.get("login.create_account", "Create an account");
			span1.onclick = function(){
				SubstanceTheme.hideCurrentNotification();
				setTimeout(function(){Dialogs.showCreateAccountDialog(pathToRoot);}, 400);
			};
			span1.style.float = "left";

			var span2 = document.createElement("span");
			span2.textContent = GeoCat.locale.get("login.guest_account", "Login as guest");
			span2.style.float = "right";

			p.appendChild(span1);
			p.appendChild(span2);

			container.appendChild(h);
			container.appendChild(userLabel);
			container.appendChild(userInput);
			container.appendChild(pwLabel);
			container.appendChild(pwInput);
			container.appendChild(p);

			var callback = function(success){
				if(success){
					SubstanceTheme.hideCurrentNotification();
				}
				else{
					container.classList.add('shake-horizontal');
					$("#login-username, #login-password").css("borderColor", "red").css("color", "red");
					setTimeout(function(){
						container.classList.remove('shake-horizontal');
					}, 500);

					setTimeout(function(){
						$("#login-username, #login-password").removeAttr('style');
					}, 1500);
				}
			}

			SubstanceTheme.showYesNoDialog(
				container, $.mobile.activePage[0],
				function(){
					GeoCat.login($("#login-username").val(), $("#login-password").val(), callback, pathToRoot)
				},
				null, "substance-white", false);
		},

		showCreateAccountDialog:  function(pathToRoot){
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
			var callback = function(success, responseMsg){
				if(success){
					SubstanceTheme.hideCurrentNotification();
				}
				else{
					p.textContent = responseMsg;
				}
			}

			SubstanceTheme.showYesNoDialog(
				container, $.mobile.activePage[0],
				function(){
					if($("#create-account-password1").val() == $("#create-account-password2").val()){
						GeoCat.createAccount($("#create-account-username").val(), $("#create-account-email").val(), $("#create-account-password1").val(),
											 callback, pathToRoot);
					}
					else{
						p.textContent = GeoCat.locale.get("createacc.passwd_not_equal", "The entered passwords doesn't match.")
					}
				},
				null, "substance-white", false);
		}
	}
})();
