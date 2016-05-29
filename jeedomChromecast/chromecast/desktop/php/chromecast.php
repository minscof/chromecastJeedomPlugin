<?php
if (! isConnect ( 'admin' )) {
	throw new Exception ( '{{401 - Accès non autorisé}}' );
}
sendVarToJS ( 'eqType', 'chromecast' );
$eqLogics = eqLogic::byType ( 'chromecast' );
?>
<div class="row row-overflow">
	<div class="col-lg-2">
		<div class="bs-sidebar">
			<ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
				<li class="filter" style="margin-bottom: 5px;"><input
					class="filter form-control input-sm" placeholder="{{Rechercher}}"
					style="width: 100%" /></li>
                <?php
					foreach ( $eqLogics as $eqLogic ) {
						echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId () . '"><a>' . $eqLogic->getHumanName ( true ) . '</a></li>';
					}
				?>
            </ul>
		</div>
	</div>
	<div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay"
		style="border-left: solid 1px #EEE; padding-left: 25px;">
		<legend>
			<i class="fa fa-cog"></i> {{Gestion}}
		</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor" id="bt_panel"
				style="background-color: #ffffff; height: 120px; margin-bottom: 10px; padding: 5px; border-radius: 2px; width: 160px; margin-left: 10px;"
				onclick="window.location='index.php?v=d&m=chromecast&p=panel';">
				<center>
					<i class="fa fa-columns" style="font-size: 5em; color: #767676;"></i>
				</center>
				<span
					style="font-size: 1.1em; position: relative; top: 15px; word-break: break-all; white-space: pre-wrap; word-wrap: break-word; color: #767676"><center>{{Panel}}</center></span>
			</div>
			<div class="cursor eqLogicAction" data-action="gotoPluginConf" style="background-color : #ffffff; height : 140px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
        		<center>
          			<i class="fa fa-wrench" style="font-size : 5em;color:#767676;"></i>
        		</center>
        		<span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
      		</div>
		</div>
		<legend>
			<i class="fa fa-headphones"></i> {{Mes Dispositifs}}
		</legend>
		<div class="eqLogicThumbnailContainer">
         <?php
			foreach ( $eqLogics as $eqLogic ) {
				$opacity = '';
				if ($eqLogic->getIsEnable () != 1) {
					$opacity = '
                    -webkit-filter: grayscale(100%);
                    -moz-filter: grayscale(100);
                    -o-filter: grayscale(100%);
                    -ms-filter: grayscale(100%);
                    filter: grayscale(100%); opacity: 0.35;';
				}
				echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId () . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
				echo "<center>";
				$file = "plugins/chromecast/core/template/images/" . $eqLogic->getConfiguration ( 'model' ) . ".png";
				if (file_exists ( $file )) {
					$path = $file;
				} else {
					$path = 'plugins/chromecast/core/template/images/chromecast_icon.png';
				}
				echo '<img src="' . $path . '" height="105"  />';
				echo "</center>";
				echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName ( true, true ) . '</center></span>';
				echo '</div>';
			}
		?>
        </div>
	</div>

	<div class="col-lg-10 eqLogic"
		style="border-left: solid 1px #EEE; padding-left: 25px; display: none;">
		<div class="row">
			<div class="col-sm-6">
				<form class="form-horizontal">
					<fieldset>
						<legend>
							<i class="fa fa-arrow-circle-left eqLogicAction cursor"
								data-action="returnToThumbnailDisplay"></i> {{Général}}<i
								class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible'
								data-action='configure'></i>
						</legend>
						<div class="form-group">
							<label class="col-lg-2 control-label">{{Nom du dispositif}}</label>
							<div class="col-lg-3">
								<input type="text" class="eqLogicAttr form-control"
									data-l1key="id" style="display: none;" /> <input type="text"
									class="eqLogicAttr form-control" data-l1key="name"
									placeholder="{{Nom de l'équipement chromecast}}" />
							</div>
							<label class="col-lg-3 control-label">{{Objet parent}}</label>
							<div class="col-lg-3">
								<select id="sel_object" class="eqLogicAttr form-control"
									data-l1key="object_id">
									<option value="">{{Aucun}}</option>
        		                    <?php
										foreach ( object::all () as $object ) {
											echo '<option value="' . $object->getId () . '">' . $object->getName () . '</option>';
										}
									?>
                        		</select>
							</div>
						</div>
						<div class="form-group">
							<label class="col-lg-2 control-label">{{Catégorie}}</label>
							<div class="col-lg-9">
                        <?php
							foreach ( jeedom::getConfiguration ( 'eqLogic:category' ) as $key => $value ) {
								echo '<label class="checkbox-inline">';
								echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value ['name'];
								echo '</label>';
							}
						?>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label"></label>
							<div class="col-sm-9">
								<input type="checkbox" class="eqLogicAttr bootstrapSwitch"
									data-label-text="{{Activer}}" data-l1key="isEnable" checked />
								<input type="checkbox" class="eqLogicAttr bootstrapSwitch"
									data-label-text="{{Visible}}" data-l1key="isVisible" checked />
							</div>
						</div>
						<legend>
							<i class="fa fa-wrench"></i> {{Configuration}}
						</legend>
						<div class="form-group">
							<label class="col-lg-2 control-label">{{Modèle :}}</label>
							<div class="col-lg-4">
								<select id="sel_model" class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="model">
										<option value="chromecast-v1">{{Chromecast V1}}</option>
										<option value="chromecast-v2">{{Chromecast V2}}</option>
										<option value="chromecast-audio">{{Chromecast Audio}}</option>n>
										<option value="no_img">{{Autre}}</option>
								</select>
							</div>
						</div>
						<!-- 
						<legend>
							<i class="fa fa-microphone"></i> {{TTS}}
						</legend>
						<div class="form-group">
							<label class="col-lg-3 control-label">{{Jingle Pré TTS:}}</label>
							<div class="col-lg-3">
								<select id="sel_item2" class="form-control eqLogicAttr"
									data-l1key="configuration" data-l2key="pretts">
									<option value="">{{1-Aucun}}</option>
									<option value="dingdong">{{Ding Dong}}</option>
									<option value="attention">{{Attention}}</option>
								</select>
							</div>
						</div>
						 -->
					</fieldset>
				</form>
			</div>
			<div class="col-sm-6">
				<legend>
					<i class="fa fa-info"></i> {{Informations}}
				</legend>

				<div class="form-group">
					<label class="col-lg-2 control-label">{{Nom :}}</label>
					<div class="col-lg-4">
						<input type="text" class="eqLogicAttr form-control"
							data-l1key="configuration" data-l2key="name" readonly />
					</div>
					<label class="col-lg-2 control-label">{{IP :}}</label>
					<div class="col-lg-4">
						<input type="text" class="eqLogicAttr form-control"
							data-l1key="configuration" data-l2key="ip" readonly />
					</div>

				</div>
				<div class="form-group">
					<label class="col-lg-2 control-label">{{Modèle :}}</label>
					<div class="col-lg-4">
						<input type="text" class="eqLogicAttr form-control"
							data-l1key="configuration" data-l2key="modelName" readonly />
					</div>
					<label class="col-lg-2 control-label">{{UUID :}}</label>
					<div class="col-lg-4">
						<input type="text" class="eqLogicAttr form-control"
							data-l1key="configuration" data-l2key="uuid" readonly />
					</div>
					<div class="form-group">
						<div style="text-align: center">
							<img
								src="plugins/chromecast/core/template/images/<?php if (isset($eqLogic) && is_object($eqLogic)) {echo $eqLogic->getConfiguration ( 'modelName' );}?>.png"
								id="img_castModel"
								onerror="this.src='plugins/chromecast/core/template/images/chromecast_icon.png'"
								style="height: 280px;" />
						</div>
					</div>
				</div>
			</div>
		</div>
		<form class="form-horizontal">
			<fieldset>
				<div class="form-actions" align="right">
					<a class="btn btn-danger eqLogicAction" data-action="remove"><i
						class="fa fa-minus-circle"></i> {{Supprimer}}</a> <a
						class="btn btn-success eqLogicAction" data-action="save"><i
						class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
				</div>
			</fieldset>
		</form>
		<legend>
			<i class="fa fa-list-alt"></i> {{Tableau de commandes}}
		</legend>
		<table id="table_cmd" class="table table-bordered table-condensed">
			<thead>
				<tr>
					<th style="width: 300px;">{{Nom}}</th>
					<th>{{Options}}</th>
					<th>{{Actions}}</th>
				</tr>
			</thead>
			<tbody>

			</tbody>
		</table>

		<form class="form-horizontal">
			<fieldset>
				<div class="form-actions">
					<a class="btn btn-danger eqLogicAction" data-action="remove"><i
						class="fa fa-minus-circle"></i> {{Supprimer}}</a> <a
						class="btn btn-success eqLogicAction" data-action="save"><i
						class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
				</div>
			</fieldset>
		</form>

	</div>
</div>

<?php include_file('desktop', 'chromecast', 'js', 'chromecast'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
<script>
$(document).ready(function() {
    var opt = $("#sel_item2 option").sort(function (a,b) { return a.text.toUpperCase().localeCompare(b.text.toUpperCase()) });
    $("#sel_item2").append(opt);
});
</script>