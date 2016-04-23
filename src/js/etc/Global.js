/**
 * Replaces string identifiers like "{0}" or "{1}" with the index in the <i>args</i> array
 * @param txt The string
 * @param args {string[]} The arguments
 * @return A string with replaced contents
 *
 * @public
 * @function sprintf
 */
function sprintf(txt, args){

	/* The following code is based on an answer written by the StackOverflow (stackoverflow.com) users Brad Larson and fearphage.
	 * The code is licensed under CC BY-SA 3.0 "Creative Commons Attribution-ShareAlike 3.0 Unported", http://creativecommons.org/licenses/by-sa/3.0/)
	 *
	 * http://stackoverflow.com/questions/610406/javascript-equivalent-to-printf-string-format
	 * The code has been slightly modified.
	 */

	return txt.replace(/{(\d+)}/g, function(match, number){
			return typeof args[number] != 'undefined' ? args[number] : match;
		});
 };
