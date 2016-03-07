var JSONLocale = function(language, path){

	var translations = new Object();
	downloadTranslations(language, path);

	function downloadTranslations(language, path){
		var downloadUrl = path + "locale/" + language + "_client.json";
		$.ajax({type: "GET", url: downloadUrl,
			encoding: "UTF-8",
			contentType: "application/json; charset=UTF-8",
			dataType: "text",
			cache: true,
			success: function(response){
				try{
					translations = JSON.parse(response);
				}
				catch(e){
					console.log("Unable to parse JSON translation data.")
				}

			},
			error: function(xhr, status, error){
				console.log("Unable to download translations for '" + language + "' from '" + downloadUrl + "'.")
		}});
	}

	function getTranslation(key, defaultValue){
		return translations.hasOwnProperty(key) ? translations[key] : defaultValue;
	}

	this.get = getTranslation;
}
