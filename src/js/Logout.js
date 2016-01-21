function Logout(){}

Logout.send = function(pathToRoot){
	$.ajax({
		type: "POST", url: pathToRoot + "/query/logout.php",
		data: {logout: "true"},
		cache: false,
		success: function(response){
			if(response.toLowerCase() == "true"){
				location.reload();
			}
			else{
				alert("Logout failed.")
			}
		},
		error: function(xhr, status, error){}
	});
};
