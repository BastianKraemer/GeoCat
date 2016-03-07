<?php
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_CoordinateEditDialog extends GeoCatPage {

		public function printHead($config, $locale, $session, $pathToRoot){
?>
	<script type="text/javascript">
		CoordinateEditDialogController.init("#EditCoordinate");
	</script>
<?php
		}

		public function printContent($config, $locale, $session, $pathToRoot){
?>
	<div data-role="page" id="EditCoordinate" data-theme="a" class="full-screen">

		<div role="main" class="ui-content" style="height: 100%; padding: 0;">

			<div class="substance-center-flexcontainer" style="min-height: 100%">
				<div class="substance-half-window substance-box">
					<div class="substance-box-header no-shadow">
						<h3 class="regular center"><?php $locale->write("dialog.editcoord.title"); ?></h3>
					</div>

					<label for="EditCoordinate-name"><?php $locale->write("places.popup_edit.name"); ?></label>
					<input id="EditCoordinate-name"  placeholder="<?php $locale->write("places.placeholder.name"); ?>" data-theme="a" type="text">

					<div id="EditCoordinate-desc-container">
						<label for="EditCoordinate-desc"><?php $locale->write("places.popup_edit.description"); ?></label>
						<textarea id="EditCoordinate-desc" placeholder="<?php $locale->write("places.placeholder.description"); ?>"></textarea>
					</div>

					<table>
						<tr>
							<td>
								<label for="EditCoordinate-lat"><?php $locale->write("latitude"); ?>:</label>
								<input id="EditCoordinate-lat" name="EditPlacePopup_Lat" placeholder="50.0000">
							</td>
							<td>
								<label for="EditCoordinate-lon"><?php $locale->write("longitude"); ?>:</label>
								<input id="EditCoordinate-lon" name="EditPlacePopup_Lon" placeholder="8.0000">
							</td>
						</tr>
					</table>

					<div id="EditCoordinate-ispublic-container">
					    <label>
					        <input id="EditCoordinate-ispublic" name="EditPlacePopup_Public" type="checkbox"><?php $locale->write("places.popup_edit.ispublic"); ?>
					    </label>
					</div>

					<div id="EditCoordinate-hint-container" style="display: none">
						<hr>
						<label for="EditCoordinate-hint"><?php $locale->write("challenge.info.hint"); ?></label>
						<input id="EditCoordinate-hint" data-theme="a" type="text">
					</div>

					<div id="EditCoordinate-priority-container" style="display: none">
						<table style="margin: -3px;">
							<tr>
								<td colspan="2"><label for="EditCoordinate-priority" style="margin: 0px 0px -0.5em;"><?php $locale->write("dialog.editcoord.priority"); ?></label></td>
							</tr>
							<tr>
								<td>
									<input id="EditCoordinate-priority" data-theme="a" type="number" min="0" max="100">
								</td>
								<td>
									<label class="regular">
								        <input id="EditCoordinate-starting-point" name="EditPlacePopup_Public" type="checkbox" data-mini="true"><?php $locale->write("dialog.editcoord.starting_point"); ?>
								    </label>
								</td>
							</tr>
						</table>

					</div>

					<div id="EditCoordinate-code-container" style="display: none">
						<label for="EditCoordinate-code"><?php $locale->write("challenge.navigator.codeinput.placeholder"); ?></label>
						<input id="EditCoordinate-code" data-theme="a" type="text">
					</div>

					 <div class="center">
						<span id="EditCoordinate-cancel" class="substance-button substance-small-button substance-red img-delete"></span>
						<span id="EditCoordinate-confirm" class="substance-button substance-small-button substance-green img-check"></span>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
		}
	}
?>
