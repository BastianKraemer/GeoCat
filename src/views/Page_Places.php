<?php
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_Places extends GeoCatPage {

		public function getPageId(){return "places";}

		/**
		 * Add a tile for a jQuery Mobile Listview
		 * @param string $title Tile title
		 * @param string $text Tile text
		 * @param string $aside Tile description (will be displayed in the top right corner of the tile)
		 * @param string $target Hyperlink (href) for this tile
		 * @param string $imgsrc Path to the background image
		 */
		function addTile($title, $text, $aside, $target, $imgsrc, $locale){
			print(	"\t\t\t" .
					"<li><a href=\"" . $target . "\">" .
					"<img src=\"" . $imgsrc . "\" class=\"ui-li-thumb\">" .
					"<h2>" . $locale->get($title) . "</h2>" .
					"<p>" . $locale->get($text) . "</p>" .
					"<p class=\"ui-li-aside\">" . $locale->get($aside) . "</p>" .
					"</a></li>\n");
		}

		/**
		 * Adds a (default) tile for a jQuery Mobile Listview
		 * Compared to "addTile()" this methods appends a prefix to the "tilename" parameters
		 *
		 * Prefix: "mainpage.tiles." . $tilename . ".title|text|aside"
		 * @param string $tilename The tile name - take a look at the JSON locale file.
		 * @param string $target Hyperlink (href) for this tile
		 * @param string $imgsrc Path to the background image
		 */
		function addDefaultTile($tilename, $target, $imgsrc, $locale){
			$this->addTile(	"mainpage.tiles." . $tilename . ".title",
							"mainpage.tiles." . $tilename . ".text",
							"mainpage.tiles." . $tilename . ".aside",
							$target, $imgsrc, $locale);
		}


		protected function printContent($config, $locale, $session){

			$this->printHeader($locale->get("places.title"), true, false, $config, $session);
?>

	<div role="main" class="ui-content">
		<div class="ui-field-contain places_header">
		<p id="PlacesInformation"></p>
			<button id="Places_Prev" class="ui-btn ui-btn-inline ui-icon-arrow-l ui-btn-icon-left ui-btn-icon-notext"><?php $locale->write("places.prev_page") ?></button >
			<button id="Places_Next" class="ui-btn ui-btn-inline ui-icon-arrow-r ui-btn-icon-right ui-btn-icon-notext" style="float:right"><?php $locale->write("places.next_page") ?></button >
		</div>

		<ul id="PlacesListView" data-role="listview" data-inset="true">
			<li><span><?php $locale->write("places.empty_list") ?></span></li>
		</ul>
	</div>

	<div data-role="footer" data-id="navbar" data-position="fixed" data-tap-toggle="false" data-theme="b" style="overflow:hidden;">
		<div data-role="navbar" class="navigationbar">
			<ul>
				<li><a id="Places_Find"><?php $locale->write("places.find") ?></a></li>
				<li><a id="Places_ShowMyPlaces"><?php $locale->write("places.private_places") ?></a></li>
				<li><a id="Places_ShowPublicPlaces" data-transition="none"><?php $locale->write("places.public_places") ?></a></li>
				<li><a id="Places_newPlace"><?php $locale->write("places.new_place") ?></a></li>
			</ul>
		</div>
	</div>

	<!-- Popup to add/edit places -->
	<div id="EditPlacePopup" data-role="popup" data-theme="a" data-position-to="window" class="ui-corner-all">
		<div data-role="header" data-theme="b">
			<h3 id="EditPlacePopup_Title"><?php $locale->write("places.popup_edit.title"); ?></h3>
			<button id="EditPlacePopup_Close" class="ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-delete ui-btn-icon-notext">Close Window</button>
			<button id="EditPlacePopup_Delete" class="ui-btn-left ui-btn ui-btn-inline ui-mini ui-corner-all">
				<?php $locale->write("places.delete"); ?>
			</button>
		</div>

		<div role="main" class="ui-content">
			<label for="EditPlacePopup_Name"><?php $locale->write("places.popup_edit.name"); ?></label>
			<input id="EditPlacePopup_Name" name="EditPlacePopup_Name" placeholder="<?php $locale->write("places.placeholder.name"); ?>" data-theme="a" type="text">

			<label for="EditPlacePopup_Desc"><?php $locale->write("places.popup_edit.description"); ?></label>
			<textarea id="EditPlacePopup_Desc" name="EditPlacePopup_Desc" placeholder="<?php $locale->write("places.placeholder.description"); ?>"></textarea>

			<table>
				<tr>
					<td>
						<label for="EditPlacePopup_Lat"><?php $locale->write("latitude"); ?>:</label>
						<input id="EditPlacePopup_Lat" name="EditPlacePopup_Lat" placeholder="50.0000">
					</td>
					<td>
						<label for="EditPlacePopup_Lon"><?php $locale->write("longitude"); ?>:</label>
						<input id="EditPlacePopup_Lon" name="EditPlacePopup_Lon" placeholder="8.0000">
					</td>
				</tr>
			</table>

		    <label>
		        <input id="EditPlacePopup_Public" name="EditPlacePopup_Public" type="checkbox"><?php $locale->write("places.popup_edit.ispublic"); ?>
		    </label>

			<button id="EditPlacePopup_Save" class="ui-btn ui-corner-all ui-shadow ui-btn-icon-left ui-icon-check"><?php $locale->write("save"); ?></button>
		</div>
	</div>
<?php
		}
	}
?>
