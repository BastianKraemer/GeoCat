<?php
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_GPSNavigator extends GeoCatPage {

		public function printHead($config, $locale, $session, $pathToRoot){
?>
	<script type="text/javascript">
		GPSNavigationController.init();
	</script>
<?php
		}

		public function printContent($config, $locale, $session, $pathToRoot){
?>
	<div data-role="page" id="GPSNavigator" data-theme="a">
<?php self::printHeader("GPS Navigator", true, false, $config, $session); ?>
		<div id="gpsnavigator_content" role="main" class="ui-content my-page">
			<div class="gpsradar-container">
				<canvas id="NavigatorCanvas" class="gpsradar"></canvas>
			</div>
		</div>

		<div data-role="footer" data-id="navbar" data-position="fixed" data-tap-toggle="false" data-theme="b" style="overflow:hidden;">
			<div data-role="navbar" class="navigationbar">
			<ul>
					<li><a href="#Home" data-transition="none">Zurück</a></li>
					<li><a id="GPSNavigator_AddCoordinate" >Ort hinzufügen</a></li>
					<li><a href="#CurrentDesitionListPanel" data-transition="none">Ziele anzeigen</a></li>
					<li><a href="#GPSNavigatorPreferencesPanel" data-rel="panel" data-transition="none">Optionen</a></li>
				</ul>
			</div>
		</div>

		<!-- GPS navigator preferences panel -->
		<div data-role="panel" id="GPSNavigatorPreferencesPanel" data-position="right" data-display="overlay">
			<h2>Optionen</h2>
			<label for="GPSNavDisableRotation">Ansicht automatisch drehen</label>
			<input id="GPSNavDisableRotation" data-role="flipswitch" name="GPSNavDisableRotation" type="checkbox" checked>

			<label for="GPSNavShowDebugInfo">Debug Informationen anzeigen</label>
			<input id="GPSNavShowDebugInfo" data-role="flipswitch" name="GPSNavShowDebugInfo" type="checkbox" checked>

			<label for="GPSNavOfflineMode">Offline Modus verwenden</label>
			<input id="GPSNavOfflineMode" data-role="flipswitch" name="GPSNavOfflineMode" type="checkbox">
		</div>

		<!-- Side panel to show destination list -->
		<div data-role="panel" id="CurrentDesitionListPanel" data-position="right" data-display="overlay">
			<ul id="CurrentDestinationList" data-role="listview" data-filter="true" data-filter-placeholder="Suchen..." data-inset="true">
			</ul>
		</div>

		<!-- Popup to add/edit a destination -->
		<div id="GPSNavDestListPopup" data-role="popup" data-theme="a" class="ui-corner-all">

			<div data-role="header" data-theme="b">
				<h3>GPS Navigator</h3>
				<button id="GPSNavDestListPopup_Close" class="ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-delete ui-btn-icon-notext">Close Window</button>
			</div>

			<div role="main" class="ui-content">
				<label for="GPSNavDestListPopup_Name"><?php $locale->write("gpsnav.popup.name"); ?></label>
				<input id="GPSNavDestListPopup_Name" name="Destination_Name" placeholder="<?php $locale->write("gpsnav.placeholder.name"); ?>" data-theme="a" type="text">

				<label for="GPSNavDestListPopup_Desc"><?php $locale->write("gpsnav.popup.description"); ?></label>
				<textarea id="GPSNavDestListPopup_Desc" name="Destination_Description" placeholder="<?php $locale->write("gpsnav.placeholder.description"); ?>" ></textarea>

				<table>
					<tr>
						<td>
							<label for="GPSNavDestListPopup_Lat"><?php $locale->write("latitude"); ?>:</label>
							<input id="GPSNavDestListPopup_Lat" name="Destination_Latitude" placeholder="50.0000">
						</td>
						<td>
							<label for="GPSNavDestListPopup_Lon"><?php $locale->write("longitude"); ?>:</label>
							<input id="GPSNavDestListPopup_Lon" name="Destination_Longitude" placeholder="8.0000">
						</td>
					</tr>
				</table>

				<label>
			        <input id="GPSNavDestListPopup_Add2OwnPlaces" name="GPSNavDestListPopup_Add2OwnPlaces" type="checkbox"><?php $locale->write("gpsnav.popup.add_to_own_places"); ?>
			    </label>

				<button id="GPSNavDestListPopup_Save" class="ui-btn ui-corner-all ui-shadow ui-btn-icon-left ui-icon-check"><?php $locale->write("gpsnav.popup.save"); ?></button>
			</div>
		</div>
	</div>
<?php
		}
	}
?>
