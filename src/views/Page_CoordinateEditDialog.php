<?php
/**
 * PHP file for the GeoCat coordinate page
 * @package views
 */
	namespace views;

	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	/**
	 * GeoCat coordinate edit dialog
	 * This page is desigend to offer all pages a common edit dialog for coordinates.
	 */
	class Page_CoordinateEditDialog extends \GeoCatPage {

		/**
		 * {@inheritDoc}
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @see GeoCatPage::printHead()
		 */
		public function printHead($locale, $session){
?>
	<script type="text/javascript">
		CoordinateEditDialogController.init("#EditCoordinate");
	</script>
<?php
		}

		/**
		 * {@inheritDoc}
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @see GeoCatPage::printContent()
		 */
		public function printContent($locale, $session){
?>
	<div data-role="page" id="EditCoordinate" data-theme="a" class="full-screen">

		<div role="main" class="ui-content" style="height: 100%; padding: 0;">

			<div class="substance-center-flexcontainer" style="min-height: 100%">
				<div class="substance-half-window substance-box">
					<div class="substance-box-header no-shadow">
						<h3 class="regular center"><?php $locale->write("dialog.editcoord.title"); ?></h3>
					</div>

					<label for="EditCoordinate-name"><?php $locale->write("places.popup_edit.name"); ?></label>
					<input id="EditCoordinate-name" placeholder="<?php $locale->write("places.placeholder.name"); ?>" data-theme="a" type="text">

					<div id="EditCoordinate-desc-container">
						<label for="EditCoordinate-desc"><?php $locale->write("places.popup_edit.description"); ?></label>
						<textarea id="EditCoordinate-desc" placeholder="<?php $locale->write("places.placeholder.description"); ?>"></textarea>
					</div>

					<table>
						<tr>
							<td>
								<label for="EditCoordinate-lat"><?php $locale->write("latitude"); ?>:</label>
								<input id="EditCoordinate-lat" placeholder="50.0000">
							</td>
							<td>
								<label for="EditCoordinate-lon"><?php $locale->write("longitude"); ?>:</label>
								<input id="EditCoordinate-lon" placeholder="8.0000">
							</td>
							<td style="vertical-align: bottom; width: 28px">
								<button id="EditCoordinate-get-gps" style="margin-bottom: 5px;" class="ui-btn ui-icon-navigation ui-btn-icon-notext"><?php $locale->write("dialog.editcoord.getpos") ?></button>
							</td>
							<td style="vertical-align: bottom; width: 28px">
								<button id="EditCoordinate-get-map" style="margin-bottom: 5px;" class="ui-btn ui-icon-location ui-btn-icon-notext"><?php $locale->write("dialog.editcoord.getmap") ?></button>
							</td>
						</tr>
					</table>
					<p id="EditCoordinate-wait-for-gps" class="center small" style="margin: 0; display: none"><?php $locale->write("dialog.editcoord.gpsfix"); ?> <span></span></p>

					<div id="EditCoordinate-ispublic-container">
					    <label><input id="EditCoordinate-ispublic"  type="checkbox"><?php $locale->write("places.popup_edit.ispublic"); ?></label>
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
									<label class="regular"><input id="EditCoordinate-starting-point" type="checkbox" data-mini="true"><?php $locale->write("dialog.editcoord.starting_point"); ?></label>
								</td>
							</tr>
						</table>
					</div>

					<div id="EditCoordinate-code-container" style="display: none">
						<label for="EditCoordinate-code"><?php $locale->write("challenge.navigator.codeinput.placeholder"); ?></label>
						<input id="EditCoordinate-code" data-theme="a" type="text">
					</div>

					<div id="EditCoordinate-add-to-own-places-container" style="display: none">
						<label class="regular"><input id="EditCoordinate-add-to-own-places" type="checkbox" data-mini="true"><?php $locale->write("dialog.editcoord.add2ownplaces"); ?></label>
					</div>

					<div class="center">
						<span id="EditCoordinate-cancel" class="substance-button substance-small-button substance-animated substance-red img-delete"></span>
						<span id="EditCoordinate-confirm" class="substance-button substance-small-button substance-animated substance-green img-check"></span>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
		}
	}
?>
