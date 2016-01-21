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
						location.href = 'index.php';
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
	};

	this.pageClosed = function(){
		$("#form-login").unbind();
	};
}

LoginController.currentInstance = null;

LoginController.onPageOpened = function(){
	LoginController.currentInstance = new LoginController();
	LoginController.currentInstance.pageOpened();
}

LoginController.onPageClosed = function(){
	LoginController.currentInstance.pageClosed();
	LoginController.currentInstance = null;
}
