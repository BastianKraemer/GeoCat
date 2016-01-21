function LoginController(){

	this.pageOpened = function(){
		$("#form-login").submit(function(e){
			e.preventDefault();
			$.ajax({
				type: $(this).attr('method'),
				url: $(this).attr('action'),
				data: $(this).serialize(),
				success: function(response){
					var result = JSON.parse(response);
					if(result.login){
						location.href = LoginController.pathToRoot  + "index.php";
					} else {
						//trigger window shake
						var page = document.getElementById('login');
						page.classList.add('shake-horizontal');
						setTimeout(function(){
							page.classList.remove('shake-horizontal');
						}, 500);
					}
				}
			});
		});
		$("body").addClass("gray-color-gradient");
	};

	this.pageClosed = function(){
		$("#form-login").unbind();
		$("body").removeClass("gray-color-gradient");
	};
}

LoginController.currentInstance = null;
LoginController.pathToRoot = "./";

LoginController.onPageOpened = function(){
	LoginController.currentInstance = new LoginController();
	LoginController.currentInstance.pageOpened();
}

LoginController.onPageClosed = function(){
	LoginController.currentInstance.pageClosed();
	LoginController.currentInstance = null;
}

LoginController.init = function(pathToRoot){
	LoginController.pathToRoot = pathToRoot;
	$(document).on("pageshow", "#login", LoginController.onPageOpened);
	$(document).on("pagebeforehide", "#login", LoginController.onPageClosed);
}
