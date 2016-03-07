<?php
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_Places extends GeoCatPage {

		public function printHead($config, $locale, $session, $pathToRoot){
?>
	<script type="text/javascript">
		PlacesController.init();
	</script>
<?php
		}

		public function printContent($config, $locale, $session, $pathToRoot){
?>
	<div data-role="page" id="Places" data-theme="a">
<?php self::printHeader($locale->get("places.title"), true, false, $config, $session); ?>
		<div role="main" class="ui-content">
			<div class="ui-field-contain listview-header">
			<p id="PlacesInformation" class="page-number-info"></p>
				<button id="Places_Prev" class="ui-btn ui-btn-inline ui-icon-arrow-l ui-btn-icon-left ui-btn-icon-notext"><?php $locale->write("prev_page") ?></button >
				<button id="Places_Next" class="ui-btn ui-btn-inline ui-icon-arrow-r ui-btn-icon-right ui-btn-icon-notext" style="float:right"><?php $locale->write("next_page") ?></button >
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
				<a href="#" data-role="button" data-rel="back" class="ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-delete ui-btn-icon-notext">Close Window</a>
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
	</div>
<?php
		}
	}
?>
