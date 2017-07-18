<?php

/* This file is part of Jeedom.
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


if (init('id') == '') {
    throw new Exception('{{L\'id de l\'équipement ne peut etre vide : }}' . init('op_id'));
}
/*
$dir= getcwd ( );
log::add('chromecast','info','Currendt dir =' .$dir);
*/
$id = init('id');
$appli = init('appli');
log::add('chromecast','info','appli =' .$appli);
$chromecast = chromecast::byId($id);
	if (!is_object($chromecast)) { 
			  
	 throw new Exception(__('Aucun equipement ne  correspond : Il faut (re)-enregistrer l\'équipement ', __FILE__) . init('action'));
	 }

$ip = $chromecast->getConfiguration('ip');
$name = $chromecast->getName();
$youtubePlay_id = $chromecast->getCmd( 'action', 'youtubePlay' )->getId();
$lireurl_id = $chromecast->getCmd( 'action', 'lireurl' )->getId();

?>
<div id='div_alert' style="display: none;"></div>
<div id="lireurl" style="display: none;">
	<input class="form-control" type="url" id="url"  value="http://"
		placeholder="Saisir l'url d'un fichier vidéo...">
	&nbsp
	<br>
	{{Saisir l'adresse (url) d'une vidéo en format mp4 ou webm. Puis appuyer sur la touche entrée pour lancer la vidéo.}}
<br>
{{L'historique des 20 dernières vidéos apparaît en appuyant sur la flèche vers le bas.}} 
	
</div>
<div id="youtube" style="display: none;">
	<div>
		<input class="form-control" type="text" id="query"
			placeholder="Rechercher sur Youtube...">
		&nbsp
		<br>
		{{Seule la recherche d'une vidéo est opérationnelle (après avoir renseigné une clé API Youtube).}}
		<br>
		{{Le lancement de la vidéo ne fonctionne pas pour le moment. Toute aide est bienvenue.}} 
	</div>
	<br>
	<div class="list-group" style="display: none" id="videos"></div>
</div>

<script>
	console.log('gjhgjhg');
	if ("<?php echo $appli ?>" == "Lire Url") {
		$("#lireurl").css("display", "block");
		$("#youtube").css("display", "none");
		$("#url").focus();
	} else {
		$("#lireurl").css("display", "none");
		$("#youtube").css("display", "block");
		$("#query").focus();
	}

	function youtubePlay(videoId) {
		console.log("ici:"+videoId);
		jeedom.cmd.execute({id: <?php echo $youtubePlay_id ?>, value: {message: videoId}});
		//jeedom.cmd.execute({id: $(this).data('cmd_id')});
		//ajax::success();
	}

	$.fn.enterKey = function (fnc) {
        return this.each(function () {
            $(this).keypress(function (ev) {
                var keycode = (ev.keyCode ? ev.keyCode : ev.which);
                if (keycode == '13') {
                    fnc.call(this, ev);
                }
            })
        })
    }	

	//$("#query").change(function() {
	$("#query").enterKey(function() {
		$('#videos').css("display", "none");
		$.ajax({
			type: 'POST',
			url: 'plugins/chromecast/core/ajax/chromecast.ajax.php',
			data: {
				action: 'youtubeSearch',
				query: $('#query').val()
			},
			dataType: 'json',
			global: false,
			error: function (request, status, error) {
				alert('resultat nok');
				handleAjaxError(request, status, error, $('#div_alert'));
			},
			success: function (data) {
				if (data.state != 'ok') {
					$('#div_alert').showAlert({message: data.result, level: 'danger'});
					return;
				}
				$('#videos').css("display", "block");
				for (var i in data.result) {
					console.log('reponse='+data.result[i].title);
					var list = '<a href="#" title="Click to do something" href="PleaseEnableJavascript.html" onclick="youtubePlay(this.id);return false;" class="list-group-item" id='+data.result[i].id+'>' + data.result[i].title + "</a>";
		            $('#videos').append(list);
				}
				
			}
		});		
	});

	/*
	$("#url").change(function() {
		$('#videos').css("display", "none");
		jeedom.cmd.execute({id: <?php echo $lireurl_id ?>, value: {message: $("#url").val()}});
	});
	*/

	//historyUrl = ['http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4'];
    //localStorage.setItem('historyChromecastUrl',JSON.stringify(historyUrl));
    var historyUrl = JSON.parse(localStorage.getItem('historyChromecastUrl'));
    if (!historyUrl) {
		historyUrl = new Array();
    }
    
    if (historyUrl.length == 0) {
    	historyUrl.push('http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4');
    }
	
    $('#url').autocomplete({source:historyUrl, minLength:1, delay:0});
    
    
    $("#url").enterKey(function () {
        
        historyUrl.push($("#url").val());
        
        if (historyUrl.length > 20) {
            historyUrl.splice(1,1);
            console.log('history='+historyUrl);
        }
         
        localStorage.setItem('historyChromecastUrl',JSON.stringify(historyUrl));
    	//alert('Enter!'+$("#url").val());
    	jeedom.cmd.execute({id: <?php echo $lireurl_id ?>, value: {message: $("#url").val()}});
    	//window.open('location', '_self', '');
		//window.close();
    })
		
    
</script>




