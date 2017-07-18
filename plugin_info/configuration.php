<?php

/*
 * This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */
require_once dirname ( __FILE__ ) . '/../../../core/php/core.inc.php';
include_file ( 'core', 'authentification', 'php' );
if (! isConnect ()) {
	include_file ( 'desktop', '404', 'php' );
	die ();
}
?>
<form class="form-horizontal">
	<div class="form-group">
		<label class="col-lg-2 control-label">{{Port du serveur}}</label>
		<div class="col-lg-2">
			<input class="configKey form-control" data-l1key="chromecastPort"
				value="6100" placeholder="{{n° port}}" />
		</div>
	</div>
	<div class="form-group">
		<label class="col-lg-2 control-label">{{clé API navigateur Youtube}}</label>
		<div class="col-lg-2">
			<input class="configKey form-control" data-l1key="chromecastYoutubeKey"
				value="xxxx" placeholder="{{clé api youtube}}" />
		</div>
	</div>
	<fieldset>
		<div class="form-group">
			<label class="col-lg-2 control-label">{{Nombre de lecteurs lors du dernier scan: }}</label>
			<div class="col-lg-2">
				<input id="chromecast_player_count" class="configKey form-control"
					data-l1key="playerCount" placeholder="" readonly />
			</div>
	
	</fieldset>
	</div>
	<div class="form-group" style="margin-top: -5px">
		<label class="col-lg-2 control-label">{{Players}}</label>
		<div class="col-lg-2">
			<a class="btn btn-warning" id="bt_scan"><i class="fa fa-check"></i>
				{{Scanner}}</a>
		</div>
	</div>
	</fieldset>
</form>
<script>
	$('#bt_scan').on('click', function () {
        bootbox.confirm('{{Voulez-vous lancer une auto découverte de vos Chromecasts ? }}', function (result) {
			if (result) {
		        $.ajax({// fonction permettant de faire de l'ajax
		            type: "POST", // methode de transmission des données au fichier php
		            url: "plugins/chromecast/core/ajax/chromecast.ajax.php", // url du fichier php
		            data: {
		            	action: "scanchromecast",
		            },
		            dataType: 'json',
		            error: function (request, status, error) {
		            	handleAjaxError(request, status, error);
		            },
		            success: function (data) { // si l'appel a bien fonctionné
			            if (data.state != 'ok') {
			            	$('#div_alert').showAlert({message: data.result, level: 'danger'});
			            	return;
			            }
			            $('#div_alert').showAlert({message: '{{Scan réussi}}', level: 'success'});
						$('#ul_plugin .li_plugin[data-plugin_id=chromecast').click();
		        	}
    			});
    		}
    	});
    });
</script>