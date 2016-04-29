
function Account () {

  var popups = {
    userdata: "#popup-edit",
    password: "#popup-pw"
  }

  var sendBTN = {
    userdata: "#edit-submit",
    pwdata: "#pw-submit"
  }

  var userData = {
    email: "#acc-email",
    username: "#acc-username",
    firstname: "#acc-firstname",
    lastname: "#acc-lastname",
    password: "#acc-password"
  }

  var inputFields = {
    userdata: "#edit-field",
    oldpw: "#pwold",
    newpw1: "#pwnew1",
    newpw2: "#pwnew2"
  }

  this.onPageOpened = function(){
	if(GeoCat.loginStatus.isSignedIn){
		loadUserData();
		$(userData.email + ", " + userData.username + ", " + userData.firstname + ", " + userData.lastname).click(handleClickOnField);
		$(userData.password).click(handleClickOnPWBTN);
		$(sendBTN.userdata).click(handleClickOnSubmit);
		$(sendBTN.pwdata).click(handleClickOnSubmitPassword);
	}
	else{
		$.mobile.changePage("#Home");
	}
  }

  this.onPageClosed = function(){
    $(sendBTN.userdata).unbind();
    $(sendBTN.pwdata).unbind();
  }

  var loadUserData = function(){
    $.ajax({
			type: "POST", url: "./query/account.php",
			encoding: "UTF-8",
			data: {task: "getUserData"},
			cache: false,
			success: function(response){
					var responseData = response;
					if(responseData.status == "ok"){
            updateGUI(responseData);
					} else {
						$.mobile.changePage("#Home");
						setTimeout(function(){
							SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("account.err.title", "Unable to download user data") + "</h3>" +
															"<p>" + responseData.msg + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
						}, 750);
					}
			}
		});
  }

  var updateGUI = function(responseData){
    $(userData.email).html(responseData.email);
    $(userData.username).html(responseData.username);
    $(userData.firstname).html(responseData.fname);
    $(userData.lastname).html(responseData.lname);
  }

  var handleClickOnField = function(){
    $(inputFields.userdata).val($(this).text());
    $(sendBTN.userdata).attr("data-id", this.id);
  }

  var handleClickOnPWBTN = function(){
    $(inputFields.oldpw).val("");
    $(inputFields.newpw1).val("");
    $(inputFields.newpw2).val("");
    $(sendBTN.pwdata).attr("data-id", this.id);
  }

  var handleClickOnSubmit = function(){
    $(popups.userdata).popup("close");
    $.ajax({
			type: "POST", url: "./query/account.php",
			encoding: "UTF-8",
			data: {task: "updateUserData", id: $(sendBTN.userdata).attr("data-id"), text: $(inputFields.userdata).val()},
			cache: false,
			success: function(response){
					var responseData = response;
					if(responseData.status == "ok"){
            $("#" + $(sendBTN.userdata).attr("data-id")).html($(inputFields.userdata).val());
            setTimeout(function(){
							SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("account.update.success", "Success") + "</h3>" +
															"<p>" + responseData.msg + "</p>", 7, $.mobile.activePage[0], "substance-green no-shadow white");
						}, 750);
            if($(sendBTN.userdata).attr("data-id") == "acc-username"){
              GeoCat.loginStatus.username = $(inputFields.userdata).val();
              $(".login-button").text($(inputFields.userdata).val());
            }
					} else {
						setTimeout(function(){
							SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("account.update.error", "Error") + "</h3>" +
															"<p>" + responseData.msg + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
						}, 750);
					}
			}
		});
  }

  var handleClickOnSubmitPassword = function(){
    $(popups.password).popup("close");
    var newpassword = "";
    if($(inputFields.newpw1).val() != $(inputFields.newpw2).val()){
      setTimeout(function(){
				SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("account.update.error", "Error") + "</h3>" +
					"<p>" + GeoCat.locale.get("account.update.oddpw") + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
			}, 750);
      return;
    }
    newpassword = $(inputFields.newpw1).val();
    $.ajax({
			type: "POST", url: "./query/account.php",
			encoding: "UTF-8",
			data: {task: "changePassword", id: $(sendBTN.pwdata).attr("data-id"), oldpw: $(inputFields.oldpw).val(), newpw: newpassword},
			cache: false,
			success: function(response){
					var responseData = response;
					if(responseData.status == "ok"){
            setTimeout(function(){
							SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("account.update.success", "Success") + "</h3>" +
															"<p>" + responseData.msg + "</p>", 7, $.mobile.activePage[0], "substance-green no-shadow white");
						}, 750);
					} else {
						setTimeout(function(){
							SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("account.update.error", "Error") + "</h3>" +
															"<p>" + responseData.msg + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
						}, 750);
					}
			}
		});
  }

}

Account.currentInstance = null;

Account.init = function(){
  $(document).on("pageshow", "#Account", function(){
		Account.currentInstance = new Account();
		Account.currentInstance.onPageOpened();
	});

	$(document).on("pagebeforehide", "#Account", function(){
		Account.currentInstance.onPageClosed();
		Account.currentInstance = null;
	});
}