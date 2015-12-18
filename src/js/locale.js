var JSONLocale = function(language, path){

	var translations = new Object();
	downloadTranslations(language, path);

	function downloadTranslations(language, path){
		var downloadUrl = path + "locale/" + language + "_client.json";
		$.ajax({type: "GET", url: downloadUrl,
			cache: true,
			success: function(response){
				translations = response;
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
