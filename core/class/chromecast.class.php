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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';


class chromecast extends eqLogic {

	public static function health() {
		$return = array();
		$depavconv=false;
		$deptts=false;
		$version = config::byKey('version', 'chromecast', '0');
		if ($version=='0') {
			$version=false;
		}
		$playerCount = config::byKey('playerCount', 'chromecast', '0');
		if ($playerCount=='0') {
			$playerCount=false;
		}
		if (file_exists('/usr/bin/avconv')) {
			$depavconv=true;
		}
		$return[] = array(
				'test' => __('Dépendances', __FILE__),
				'result' => ($depavconv) ? __('OK', __FILE__) : __('NOK', __FILE__),
				'advice' => ($depavconv) ? '' : __('Vérifiez que vous avez les droits Sudo et allez sur la page du plugin et cliquez sur le bouton "Installer Dépendances"', __FILE__),
				'state' => $depavconv,
		);
		$return[] = array(
				'test' => __('Nombre Chromecasts', __FILE__),
				'result' => ($playerCount) ? __($playerCount, __FILE__) : __('NOK', __FILE__),
				'advice' => ($playerCount) ? '' : __('Allez sur la page du plugins et lancer un scan de vos chromecasts.', __FILE__),
		        'state' => ($playerCount) ? __($playerCount, __FILE__) : false,
		);
		$statusDaemon=false;
		$statusDaemon = config::byKey('daemon','chromecast');
		$libVer = config::byKey('daemonVer','chromecast');
		if ($libVer=='') {
			$libVer = '{{version inconnue}}';
		}
	
		$return[] = array(
				'test' => __('Version Daemon', __FILE__),
				'result' => ($statusDaemon) ? $libVer : __('NOK', __FILE__),
				'advice' => ($statusDaemon) ? '' : __('Indique si la daemon est opérationel avec sa version', __FILE__),
				'state' => $statusDaemon,
		);
		return $return;
	}
	
	public static function deamon_info() {
		$return = array();
		$return['log'] = 'chromecast_log';
		$return['state'] = 'nok';
		$count = trim( shell_exec ('ps ax | grep "chromecast_server" | grep -v "grep" | wc -l') );
		if ($count >= 1) {
			$return['state'] = 'ok';
		}
		$return['launchable'] = 'ok';
		return $return;
	}
	
	public static function deamon_start($_debug = false) {
		self::deamon_stop();
		$shell = realpath(dirname(__FILE__)).'/../../3rdparty/chromecast_server.py';
		$string = file_get_contents($shell);
		preg_match("/__version__='([0-9.]+)/mis", $string, $matches);
		
		config::save('daemonVer', 'Version '.$matches[1],  'chromecast');
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		log::add('chromecast', 'info', 'Lancement du daemon chromecast '.$matches[1]);
	
		$cmd = 'nice -n 19 /usr/bin/python ' . $shell .($_debug?'debug':'info').' scan=true '.config::byKey('chromecastPort', 'chromecast', '0');
		log::add('chromecast', 'debug', 'Commande complète pour lancer le daemon : ' . $cmd);
		if ($_debug = true) {
			$result = exec('nohup sudo ' . $cmd . ' >> ' . log::getPathToLog('chromecastDaemon') . ' 2>&1 &');
		} else {
			$result = exec('nohup sudo ' . $cmd . '  &');
		}
		
		//$result = exec('nohup sudo ' . $shell . ' >> ' . log::getPathToLog('chromecastDaemon') . ' 2>&1 &');
		if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
			log::add('chromecast', 'error', 'Echec lancement du daemon :'.$result);
			return false;
		}
	
		$i = 0;
		while ($i < 30) {
			$deamon_info = self::deamon_info();
			if ($deamon_info['state'] == 'ok') {
				break;
			}
			sleep(1);
			$i++;
		}
		if ($i >= 30) {
			log::add('chromecast', 'error', 'Impossible de lancer le daemon chromecast, vérifiez les logs', 'unableStartDeamon');
			return false;
		}
		message::removeAll('chromecast', 'unableStartDeamon');
		//mettre une gestion d'event pour gérer le statut de daemon
		//config::save('daemon', '1',  'chromecast');
		log::add('chromecast', 'info', 'Chromecast daemon lancé');
		return true;
	}
	
	public static function deamon_stop() {
		$opts = array(
				'http'=>array(
						'method'=>"GET",
						'header'=>"Accept-language: en\r\n" .
						"Cookie: foo=bar\r\n"
				)
		);
		$context = stream_context_create($opts);
		//pour éviter des logs intempestifs quand on cherche à arrêter un serveur déjà arrêté.. @
		@$file = file_get_contents('http://127.0.0.1:'.config::byKey('chromecastPort', 'chromecast', '0').'/halt', false, $context);
		log::add('chromecast', 'info', 'Arrêt du daemon chromecast');
		$deamon_info = self::deamon_info();
		if ($deamon_info['state'] == 'ok') {
			sleep(2);
			exec('sudo kill $(ps aux | grep "chromecast_server" | grep -v "grep" | awk \'{print $2}\')');
		}
		$deamon_info = self::deamon_info();
		if ($deamon_info['state'] == 'ok') {
			sleep(2);
			exec('sudo kill -9 $(ps aux | grep "chromecast_server" | awk \'{print $2}\')');
		}
		//config::save('daemon', '0',  'chromecast');
	}
	
	
	public static function dependancy_info() {
		$return = array();
		$return['log'] = 'chromecast_update';
		$return['progress_file'] = '/tmp/dependancy_chromecast_in_progress';
		$shell = '/usr/bin/python '. dirname(__FILE__) . '/../../3rdparty/testDependancy.py';
		$command = escapeshellcmd($shell);
		$output = trim(shell_exec($command));
		if ($output=='ok') {
			$return['state'] = 'ok';
		} else {
			$return['state'] = 'nok';
		}
		return $return;
	}

	public static function dependancy_install() {
		if (file_exists('/tmp/dependancy_chromecast_in_progress')) {
			return;
		}
		log::remove('chromecast_update');
		$resource_path = realpath(dirname(__FILE__) . '/../..');
		//passthru('/bin/bash ' . $resource_path . '/nodejs.sh ' . $resource_path . ' > ' . log::getPathToLog('btsniffer_dep') . ' 2>&1 &');
		$shell = 'sudo /bin/bash ' . dirname(__FILE__) . '/../../3rdparty/install.sh '.$resource_path;
		$shell .= ' >> ' . log::getPathToLog('chromecast_update') . ' 2>&1 &';
		exec($shell);
	}
  
    public static function updatechromecast() {
		log::remove('chromecast_update');
		$shell = '/bin/bash ' .dirname(__FILE__) . '/../../3rdparty/install.sh';
		$shell .= ' >> ' . log::getPathToLog('chromecast_update') . ' 2>&1 &';
		exec($shell);
	}
	
	public static function executeAction($parm) {
		if (self::deamon_info()['state']=='nok') return array(false,__('Il faut démarrer le daemon avant de lancer une commande.', __FILE__));
		$count = 0;
		$opts = array(
				'http'=>array(
					'method'=>"GET",
					'header'=>"Accept-language: en\r\n" .
					"Cookie: foo=bar\r\n"
				)
			);
		$context = stream_context_create($opts);
		
		if (!$file = @file_get_contents('http://127.0.0.1:'.config::byKey('chromecastPort', 'chromecast', '0').'/'.$parm, false, $context)) {
			if (isset($http_response_header[0])) {
				$response = $http_response_header[0];
			} else {
				$response = 'pas de header http !';
			}
			return array(false,"Problème dans la réponse du  daemon : $response - vérifier que votre daemon est bien démarré et/ou regarder ses logs",$http_response_header[0]);
		
		}
		return array(true,json_decode($file));
	}
	
	public static function detection() {
		log::add('chromecast','debug','******** Début du scan des chromecasts ********');
		$result = chromecast::executeAction('scan');
		if (!$result[0]) return $result;
		 
		$count = 0;
		foreach ($result[1] as $equipment) {
			$count++;
			$a='';
			$vendor='';
			$logicalName='';
			$uuid='';
			$modelName='';
			$ip='';
			foreach ($equipment as $key => $value) {
				$a .= $key.'='.$value.' ';
				switch ($key) {
					case 'vendor' :
						$vendor = $value;
						break;
					case 'logicalName' :
						$logicalName = $value;
						break;
					case 'uuid' :
						$uuid = $value;
						break;
					case 'modelName' :
						$modelName = $value;
						break;
					case 'ip' :
						$ip = $value;
						break;
				}
			}
			log::add('chromecast','debug','Equipement trouvé : '.$a);
			self::saveEquipment($vendor,$logicalName,$uuid,$modelName,$ip);
		}
		log::add('chromecast','debug','count  : ' .$count);
		config::save('playerCount', $count, 'chromecast');
		log::add('chromecast','info','******** scan des chromecasts - nombre d\'équipements trouvés = '.$count.' ********');
		return array(true,'******** scan des chromecasts - nombre d\'équipements trouvés = '.$count.' ********');
	}	

	public static function saveEquipment($vendor,$logicalName,$uuid,$modelName,$ip) {
		log::add('chromecast','debug','Début saveEquipment ='.$logicalName);
		$eqLogic = self::byLogicalId($uuid, 'chromecast');
		if (is_object($eqLogic)) {
			$changed = false;
			log::add('chromecast','debug','Equipement déjà existant - mise à jour des informations de l\'équipement détecté : '.$logicalName);
			if ($eqLogic->getConfiguration('ip') != $ip) {
				log::add('chromecast','info','Mise à jour de l\'adresse IP ('.$eqLogic->getConfiguration('ip').'=>'.$ip.') de l\'équipement :' .$uuid);
				$eqLogic->setConfiguration('ip', $ip);
				$changed = true;
			} 
			if ($eqLogic->getConfiguration('name') != $logicalName) {
				log::add('chromecast','info','Mise à jour du nom ('.$eqLogic->getConfiguration('name').'=>'.$logicalName.') de l\'équipement :' .$uuid);
				$eqLogic->setConfiguration('name', $logicalName);
				$changed = true;
			}
			if ($eqLogic->getConfiguration('modelName') != ucfirst($modelName)) {
				log::add('chromecast','info','Mise à jour du modèle ('.$eqLogic->getConfiguration('modelName').'=>'.ucfirst($modelName).') de l\'équipement :' .$uuid);
				$eqLogic->setConfiguration('modelName', ucfirst($modelName));
				$changed = true;
			}
			if ($eqLogic->getConfiguration('vendor') != $vendor) {
				log::add('chromecast','info','Mise à jour du vendeur ('.$eqLogic->getConfiguration('vendor').'=>'.$vendor.') de l\'équipement :' .$uuid);
				$eqLogic->setConfiguration('vendor', $vendor);
				$changed = true;
			}
			if ($changed) $eqLogic->save();
		} else {
			$eqLogic = new self();
			foreach (object::all() as $object) {
				if (stristr($logicalName,$object->getName())){
					$eqLogic->setObject_id($object->getId());
					break;
				}
			}
			$eqLogic->setLogicalId($uuid);
			$eqLogic->setName('Cast '.$logicalName);
			$eqLogic->setEqType_name('chromecast');
			$eqLogic->setConfiguration('name', $logicalName);
			$eqLogic->setConfiguration('ip', $ip);
			$eqLogic->setConfiguration('modelName', ucfirst($modelName));
			$eqLogic->setConfiguration('uuid', $uuid);
			$eqLogic->setConfiguration('vendor', $vendor);
			$eqLogic->setIsVisible(1);
			$eqLogic->setIsEnable(1);
			$eqLogic->save();
			
			foreach ($eqLogic->getCmd('info') as $cmd) {
				switch ($cmd->getLogicalId()) {
					case 'volume':
						$value=0.5;
						break;
					case 'volumeMuted':
						$value=false;
						break;
					case 'appli':
						$value="inconnu";
						break;
					case 'appId':
						$value="inconnu";
						break;
					case 'title':
						$value="inconnu";
						break;
					case 'status':
						$value='on';
						break;
					case 'statusText':
						$value='inconnu';
						break;
				}
				$cmd->event($value);
				log::add('chromecast','debug','set:'.$cmd->getName().' to '. $value);
			}
		}
	}
	
	public static function scanchromecast() {
		return chromecast::detection();
	}

	public function onoffall($action) {
        $eqLogics = eqLogic::byType('chromecast');
        foreach($eqLogics as $chromecast) {
            $device=$chromecast->getLogicalId();
            $shell = '/usr/bin/python ' .dirname(__FILE__) . '/../../3rdparty/executer_action.py '. $device.' '.$action;
            log::add('chromecast','debug','Execution de la commande suivante : ' .$shell);
            $result=shell_exec($shell);
       }
    }
    
	public static function event() {
		$value = init('value');
		$mac = init('adress');
		log::add('chromecast','debug','Received : ' .$value . ' de : '. $mac);
		$eqLogic = eqLogic::byLogicalId($mac,'chromecast');
		$mastername= $eqLogic->getName();
		$json=json_decode($value,true);
		$changed = false;
		$title=(isset($json['title'])) ? $json['title'] : "old";
		$volume=(isset($json['volume'])) ? $json['volume'] : "old";
		$status=(isset($json['statut'])) ? $json['statut'] : "old";
		$cmd_title = $eqLogic->getCmd(null, 'title');
		if (is_object($cmd_title)) {
			if ($title != $cmd_title->execCmd(null, 2) && $title != "old") {
				$cmd_title->setCollectDate('');
				$cmd_title->event($title);
				$changed = true;
			}
		}
		$cmd_volume= $eqLogic->getCmd(null, 'volume');
		if (is_object($cmd_volume)) {
			if ($volume != $cmd_volume->execCmd(null, 2) && $volume != "old") {
				$cmd_volume->setCollectDate('');
				$cmd_volume->event($volume);
				$changed = true;
			}
		}
		$cmd_status= $eqLogic->getCmd(null, 'status');
		if (is_object($cmd_status)) {
			if ($status != $cmd_status->execCmd(null, 2) && $status != "old") {
				$cmd_status->setCollectDate('');
				$cmd_status->event($status);
				$changed = true;
			}
		}
		if ($changed) {
			$eqLogic->refreshWidget();
		}
	}
	
	
	/*     * *********************Méthodes d'instance************************* */

	public function preUpdate() {
		if ($this->getConfiguration('volume_inc') != '' && ($this->getConfiguration('volume_inc') <=  0 || $this->getConfiguration('volume_inc') >=  100)) {
            throw new Exception(__('Le volume +/- doit être > 0 et < 100',__FILE__));
        }
	}
	public function preSave() {
		$this->setCategory('multimedia', 1);
	}
	
	public function postSave() {
		$play = $this->getCmd(null, 'play');
		if (!is_object($play)) {
			$play = new chromecastCmd();
			$play->setLogicalId('play');
			$play->setIsVisible(1);
			$play->setName(__('Lecture', __FILE__));
		}
		$play->setType('action');
		$play->setSubType('other');
		$play->setEqLogic_id($this->getId());
		$play->save();

		$setTime = $this->getCmd(null, 'setTime');
		if (!is_object($setTime)) {
			$setTime = new chromecastCmd();
			$setTime->setLogicalId('setTime');
			$setTime->setIsVisible(1);
			$setTime->setName(__('Positionnement', __FILE__));
		}
		$setTime->setType('action');
		$setTime->setSubType('slider');
		$setTime->setEqLogic_id($this->getId());
		$setTime->save();	
		
		$stop = $this->getCmd(null, 'stop');
		if (!is_object($stop)) {
			$stop = new chromecastCmd();
			$stop->setLogicalId('stop');
			$stop->setIsVisible(1);
			$stop->setName(__('Stop', __FILE__));
		}
		$stop->setType('action');
		$stop->setSubType('other');
		$stop->setEqLogic_id($this->getId());
		$stop->save();

		$pause = $this->getCmd(null, 'pause');
		if (!is_object($pause)) {
			$pause = new chromecastCmd();
			$pause->setLogicalId('pause');
			$pause->setIsVisible(1);
			$pause->setName(__('Pause', __FILE__));
		}
		$pause->setType('action');
		$pause->setSubType('other');
		$pause->setEqLogic_id($this->getId());
		$pause->save();

		$next = $this->getCmd(null, 'next');
		if (!is_object($next)) {
			$next = new chromecastCmd();
			$next->setLogicalId('next');
			$next->setIsVisible(1);
			$next->setName(__('Suivant', __FILE__));
		}
		$next->setType('action');
		$next->setSubType('other');
		$next->setEqLogic_id($this->getId());
		$next->save();

		$previous = $this->getCmd(null, 'previous');
		if (!is_object($previous)) {
			$previous = new chromecastCmd();
			$previous->setLogicalId('previous');
			$previous->setIsVisible(1);
			$previous->setName(__('Précédent', __FILE__));
		}
		$previous->setType('action');
		$previous->setSubType('other');
		$previous->setEqLogic_id($this->getId());
		$previous->save();
		
		$forward = $this->getCmd(null, 'forward');
		if (!is_object($forward)) {
			$forward = new chromecastCmd();
			$forward->setLogicalId('forward');
			$forward->setIsVisible(1);
			$forward->setName(__('Avance', __FILE__));
		}
		$forward->setType('action');
		$forward->setSubType('other');
		$forward->setEqLogic_id($this->getId());
		$forward->save();
		
		$rewind = $this->getCmd(null, 'rewind');
		if (!is_object($rewind)) {
			$rewind = new chromecastCmd();
			$rewind->setLogicalId('rewind');
			$rewind->setIsVisible(1);
			$rewind->setName(__('Recule', __FILE__));
		}
		$rewind->setType('action');
		$rewind->setSubType('other');
		$rewind->setEqLogic_id($this->getId());
		$rewind->save();

		$mute = $this->getCmd(null, 'mute');
		if (!is_object($mute)) {
			$mute = new chromecastCmd();
			$mute->setLogicalId('mute');
			$mute->setIsVisible(1);
			$mute->setName(__('Muet', __FILE__));
		}
		$mute->setType('action');
		$mute->setSubType('other');
		$mute->setEqLogic_id($this->getId());
		$mute->save();

		$unmute = $this->getCmd(null, 'unmute');
		if (!is_object($unmute)) {
			$unmute = new chromecastCmd();
			$unmute->setLogicalId('unmute');
			$unmute->setIsVisible(1);
			$unmute->setName(__('Non muet', __FILE__));
		}
		$unmute->setType('action');
		$unmute->setSubType('other');
		$unmute->setEqLogic_id($this->getId());
		$unmute->save();
		
		$off = $this->getCmd(null, 'off');
		if (!is_object($off)) {
			$off = new chromecastCmd();
			$off->setLogicalId('off');
			$off->setIsVisible(1);
			$off->setName(__('Eteindre', __FILE__));
		}
		$off->setType('action');
		$off->setSubType('other');
		$off->setEqLogic_id($this->getId());
		$off->save();
        /*
        $offall = $this->getCmd(null, 'offall');
		if (!is_object($offall)) {
			$offall = new chromecastCmd();
			$offall->setLogicalId('offall');
			$offall->setIsVisible(1);
			$offall->setName(__('Eteindre tous', __FILE__));
		}
		$offall->setType('action');
		$offall->setSubType('other');
		$offall->setEqLogic_id($this->getId());
		$offall->save();
		*/
		$on = $this->getCmd(null, 'on');
		if (!is_object($on)) {
			$on = new chromecastCmd();
			$on->setLogicalId('on');
			$on->setIsVisible(1);
			$on->setName(__('Allumer', __FILE__));
		}
		$on->setType('action');
		$on->setSubType('other');
		$on->setEqLogic_id($this->getId());
		$on->save();
		/*
        $onall = $this->getCmd(null, 'onall');
		if (!is_object($onall)) {
			$onall = new chromecastCmd();
			$onall->setLogicalId('onall');
			$onall->setIsVisible(1);
			$onall->setName(__('Allumer tous', __FILE__));
		}
		$onall->setType('action');
		$onall->setSubType('other');
		$onall->setEqLogic_id($this->getId());
		$onall->save();
		*/
		$volume = $this->getCmd(null, 'volume');
		if (!is_object($volume)) {
			$volume = new chromecastCmd();
			$volume->setLogicalId('volume');
			$volume->setIsVisible(0);
			$volume->setName(__('Volume niveau', __FILE__));
		}
		$volume->setUnite('%');
		$volume->setType('info');
		$volume->setEventOnly(1);
		$volume->setSubType('numeric');
		$volume->setEqLogic_id($this->getId());
		$volume->save();

		$volumeMuted = $this->getCmd(null, 'volumeMuted');
		if (!is_object($volumeMuted)) {
			$volumeMuted = new chromecastCmd();
			$volumeMuted->setLogicalId('volumeMuted');
			$volumeMuted->setIsVisible(1);
			$volumeMuted->setName(__('Sourdine', __FILE__));
		}
		$volumeMuted->setType('info');
		$volumeMuted->setSubType('string');
		$volumeMuted->setEventOnly(1);
		$volumeMuted->setEqLogic_id($this->getId());
		$volumeMuted->save();
				
		$setVolume = $this->getCmd(null, 'setVolume');
		if (!is_object($setVolume)) {
			$setVolume = new chromecastCmd();
			$setVolume->setLogicalId('setVolume');
			$setVolume->setIsVisible(1);
			$setVolume->setName(__('Volume', __FILE__));
		}
		$setVolume->setType('action');
		$setVolume->setSubType('slider');
		$setVolume->setValue($volume->getId());
		$setVolume->setEqLogic_id($this->getId());
		$setVolume->save();
		
		$plusVolume = $this->getCmd(null, 'vol+');
		if (!is_object($plusVolume)) {
			$plusVolume = new chromecastCmd();
			$plusVolume->setLogicalId('vol+');
			$plusVolume->setIsVisible(1);
			$plusVolume->setName(__('Volume+', __FILE__));
		}
		$plusVolume->setType('action');
		$plusVolume->setSubType('other');
		$plusVolume->setEqLogic_id($this->getId());
		$plusVolume->save();
		
		$moinsVolume = $this->getCmd(null, 'vol-');
		if (!is_object($moinsVolume)) {
			$moinsVolume = new chromecastCmd();
			$moinsVolume->setLogicalId('vol-');
			$moinsVolume->setIsVisible(1);
			$moinsVolume->setName(__('Volume-', __FILE__));
		}
		$moinsVolume->setType('action');
		$moinsVolume->setSubType('other');
		$moinsVolume->setEqLogic_id($this->getId());
		$moinsVolume->save();
		
		$appli = $this->getCmd(null, 'appli');
		if (!is_object($appli)) {
			$appli = new chromecastCmd();
			$appli->setLogicalId('appli');
			$appli->setIsVisible(1);
			$appli->setName(__('Appli en cours', __FILE__));
		}
		$appli->setType('info');
		$appli->setEventOnly(1);
		$appli->setUnite('');
		$appli->setConfiguration('onlyChangeEvent',1);
		$appli->setSubType('string');
		$appli->setEqLogic_id($this->getId());
		$appli->save();

		$appId = $this->getCmd(null, 'appId');
		if (!is_object($appId)) {
			$appId = new chromecastCmd();
			$appId->setLogicalId('appId');
			$appId->setIsVisible(1);
			$appId->setName(__('Appli Id', __FILE__));
		}
		$appId->setType('info');
		$appId->setEventOnly(1);
		$appId->setUnite('');
		$appId->setConfiguration('onlyChangeEvent',1);
		$appId->setSubType('string');
		$appId->setEqLogic_id($this->getId());
		$appId->save();
		
		$title = $this->getCmd(null, 'title');
		if (!is_object($title)) {
			$title = new chromecastCmd();
			$title->setLogicalId('title');
			$title->setIsVisible(true);
			$title->setName(__('Titre en cours', __FILE__));
		}
		$title->setType('info');
		$title->setEventOnly(1);
		$title->setUnite('');
		$title->setConfiguration('onlyChangeEvent',1);
		$title->setSubType('string');
		$title->setEqLogic_id($this->getId());
		$title->save();
		
		$status = $this->getCmd(null, 'status');
		if (!is_object($status)) {
			$status = new chromecastCmd();
			$status->setLogicalId('status');
			$status->setIsVisible(1);
			$status->setName(__('Etat', __FILE__));
		}
		$status->setType('info');
		$status->setSubType('string');
		$status->setEventOnly(1);
		$status->setEqLogic_id($this->getId());
		$status->save();
		
		$statusText = $this->getCmd(null, 'statusText');
		if (!is_object($statusText)) {
			$statusText = new chromecastCmd();
			$statusText->setLogicalId('statusText');
			$statusText->setIsVisible(1);
			$statusText->setName(__('Info', __FILE__));
		}
		$statusText->setType('info');
		$statusText->setSubType('string');
		$statusText->setEventOnly(1);
		$statusText->setEqLogic_id($this->getId());
		$statusText->save();

		$isActiveInput = $this->getCmd(null, 'isActiveInput');
		if (!is_object($isActiveInput)) {
			$isActiveInput = new chromecastCmd();
			$isActiveInput->setLogicalId('isActiveInput');
			$isActiveInput->setIsVisible(1);
			$isActiveInput->setName(__('Entrée active', __FILE__));
		}
		$isActiveInput->setType('info');
		$isActiveInput->setSubType('string');
		$isActiveInput->setEventOnly(1);
		$isActiveInput->setEqLogic_id($this->getId());
		$isActiveInput->save();

		$isStandBy = $this->getCmd(null, 'isStandBy');
		if (!is_object($isStandBy)) {
			$isStandBy = new chromecastCmd();
			$isStandBy->setLogicalId('isStandBy');
			$isStandBy->setIsVisible(1);
			$isStandBy->setName(__('StandBy', __FILE__));
		}
		$isStandBy->setType('info');
		$isStandBy->setSubType('string');
		$isStandBy->setEventOnly(1);
		$isStandBy->setEqLogic_id($this->getId());
		$isStandBy->save();
		/*
        $parle = $this->getCmd(null, 'parle');
		if (!is_object($parle)) {
			$parle = new chromecastCmd();
			$parle->setLogicalId('parle');
			$parle->setIsVisible(1);
			$parle->setName(__('Parle', __FILE__));
		}
		$parle->setType('action');
        $parle->setDisplay('title_placeholder', __('Volume [0-100]', __FILE__));
        $parle->setDisplay('message_placeholder', __('Phrase', __FILE__));
		$parle->setSubType('message');
		$parle->setEqLogic_id($this->getId());
		$parle->save();
        
        $paramtts = $this->getCmd(null, 'paramtts');
		if (!is_object($paramtts)) {
			$paramtts = new chromecastCmd();
			$paramtts->setLogicalId('paramtts');
			$paramtts->setIsVisible(1);
			$paramtts->setName(__('Paramétrer TTS', __FILE__));
		}
		$paramtts->setType('action');
        $paramtts->setDisplay('title_placeholder', __('Moteur [picotts,google,voxygen]', __FILE__));
        $paramtts->setDisplay('message_placeholder', __('Options moteur [voir doc]', __FILE__));
		$paramtts->setSubType('message');
		$paramtts->setEqLogic_id($this->getId());
		$paramtts->save();
        */
        $lireurl = $this->getCmd(null, 'lireurl');
		if (!is_object($lireurl)) {
			$lireurl = new chromecastCmd();
			$lireurl->setLogicalId('lireurl');
			$lireurl->setIsVisible(1);
			$lireurl->setName(__('Jouer Url', __FILE__));
		}
		$lireurl->setType('action');
		$lireurl->setSubType('message');
        $lireurl->setDisplay('title_disable', 1);
        $lireurl->setDisplay('message_placeholder', __('Url', __FILE__));
		$lireurl->setEqLogic_id($this->getId());
		$lireurl->save();
		
		$youtubePlay = $this->getCmd(null, 'youtubePlay');
		if (!is_object($youtubePlay)) {
			$youtubePlay = new chromecastCmd();
			$youtubePlay->setLogicalId('youtubePlay');
			$youtubePlay->setIsVisible(1);
			$youtubePlay->setName(__('Jouer vidéo youtube', __FILE__));
		}
		$youtubePlay->setType('action');
		$youtubePlay->setSubType('message');
		$youtubePlay->setDisplay('title_disable', 1);
		$youtubePlay->setDisplay('message_placeholder', __('id youtube', __FILE__));
		$youtubePlay->setEqLogic_id($this->getId());
		$youtubePlay->save();
		
		$current_time = $this->getCmd(null, 'current_time');
		if (!is_object($current_time)) {
			$current_time = new chromecastCmd();
			$current_time->setLogicalId('current_time');
			$current_time->setIsVisible(false);
			$current_time->setIsHistorized(false);
			$current_time->setName(__('Position', __FILE__));
		}
		$current_time->setUnite('s');
		$current_time->setType('info');
		$current_time->setEventOnly(1);
		$current_time->setSubType('numeric');
		$current_time->setEqLogic_id($this->getId());
		$current_time->save();
		
		$duration = $this->getCmd(null, 'duration');
		if (!is_object($duration)) {
			$duration = new chromecastCmd();
			$duration->setLogicalId('duration');
			$duration->setIsVisible(0);
			$duration->setName(__('Durée', __FILE__));
		}
		$duration->setUnite('s');
		$duration->setType('info');
		$duration->setEventOnly(1);
		$duration->setSubType('numeric');
		$duration->setEqLogic_id($this->getId());
		$duration->save();
        
	}
	
	public function toHtml($_version = 'dashboard') {
		if ($this->getIsEnable() != 1) {
			return '';
		}
		if (!$this->hasRight('r')) {
			return '';
		}
		$_version = jeedom::versionAlias($_version);

		$replace = array(
			'#id#' => $this->getId(),
			'#uid#' => $this->getId(),
			'#info#' => (isset($info)) ? $info : '',
			'#name#' => $this->getName(),
			'#eqLink#' => $this->getLinkToConfiguration(),
			'#text_color#' => $this->getConfiguration('text_color'),
			'#background#' => 'background-color:'.$this->getBackgroundColor($_version),
			'#hideThumbnail#' => 0,
			'#volumeMuted#' => strtolower($this->getCmd(null, 'volumeMuted')->execCmd()),
			'#duration#' => 0,
			'#YouTube#' => (config::byKey('chromecastYoutubeKey', 'chromecast', '0') != '')					
			//'#log::add('chromecast','debug','Execution de la commande suivante : ' .$cmd);#' => 'false'
		);
		
		$cmd_etat = $this->getCmd(null, 'status');
		if (is_object($cmd_etat)) {
			if ($cmd_etat->execCmd()=='off'){
				$etat='<i class="fa fa-power-off"></i>';
				$togglepower=$this->getCmd(null, 'on')->getId();
			} else {
				$etat='<i class="fa fa-power-off" style="color:#61f603;"></i>';
				$togglepower=$this->getCmd(null, 'off')->getId();
			}
			if (in_array($cmd_etat->execCmd(),array('PLAYING','BUFFERING'))) {
				$state_nb=1;
			} else {
				$state_nb=0;
			}
			$replace['#power#'] = $etat;
			$replace['#toggle_power_id#'] = $togglepower;
			$replace['#state_nb#'] = $state_nb;
		}
		
		$cmd_appli = $this->getCmd(null, 'appli');
		if (is_object($cmd_appli)) {
			$name=$cmd_appli->execCmd();
			$replace['#appli#'] = $name;
		} else {
			$replace['#appli#'] = 'inconnu';
		}
		
		$cmd_title = $this->getCmd(null, 'title');
		if (is_object($cmd_title)) {
			if (strlen($cmd_title->execCmd())>17){
				$name='<marquee behavior="scroll" direction="left" scrollamount="2">'.$cmd_title->execCmd().'</marquee>';
			} else {
				$name=$cmd_title->execCmd();
			}
			$replace['#orititle#'] =$cmd_title->execCmd();
			$replace['#title#'] = $name;
		}
		
		$cmd_statusText = $this->getCmd(null, 'statusText');
		if (is_object($cmd_statusText)) {
			if (strlen($cmd_statusText->execCmd())>17){
				$name='<marquee behavior="scroll" direction="left" scrollamount="2">'.$cmd_statusText->execCmd().'</marquee>';
			} else {
				$name=$cmd_statusText->execCmd();
			}
			$replace['#oristatusText#'] =$cmd_statusText->execCmd();
			$replace['#statusText#'] = $name;
		}
		
		$cmd_volume = $this->getCmd(null, 'volume');
		if (is_object($cmd_volume)) {
			$replace['#volume#'] = $cmd_volume->execCmd();
		}
		
		$cmd_setVolume = $this->getCmd(null, 'setVolume');
		if (is_object($cmd_setVolume)) {
			$replace['#volume_id#'] = $cmd_setVolume->getId();
		}

		$cmd_current_time = $this->getCmd(null, 'current_time');
		if (is_object($cmd_current_time)) {
			$replace['#current_time#'] = $cmd_current_time->execCmd();
		}
		
		$cmd_setTime = $this->getCmd(null, 'setTime');
		if (is_object($cmd_setTime)) {
			$replace['#time_id#'] = $cmd_setTime->getId();
		}
		
		$cmd_duration = $this->getCmd(null, 'duration');
		if (is_object($cmd_duration)) {
			$duration = $cmd_duration->execCmd();
			$replace['#duration#'] = (is_numeric($duration)? $cmd_duration->execCmd():0);
		}
		
		foreach ($this->getCmd('action') as $cmd) {
			$replace['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
		}

		return template_replace($replace, getTemplate('core', $_version, 'eqLogic', 'chromecast'));
	}
}
 
class chromecastCmd extends cmd {
		/* * *************************Attributs****************************** */
		
	/* * ***********************Methode static*************************** */
		
	/* * *********************Methode d'instance************************* */
	public function execute($_options = array()) {
		$chromecast = $this->getEqLogic ();
		$device = $chromecast->getConfiguration ( 'name' );
		$action = $this->getLogicalId ();
		$parm = $action . '?name=' . $device;
		if ($action == 'parle') {
			$jingle = 'nojingle';
			$moteurtts = $chromecast->getConfiguration ( 'moteurtts' );
			if ($moteurtts == 'picotts') {
				$options = $chromecast->getConfiguration ( 'picovoice' );
			} else if ($moteurtts == 'google') {
				$options = $chromecast->getConfiguration ( 'googlelang' );
			} else if ($moteurtts == 'voxygen') {
				$options = $chromecast->getConfiguration ( 'voxygenvoice' );
			}
			if ($chromecast->getConfiguration ( 'pretts' ) != "") {
				$jingle = $chromecast->getConfiguration ( 'pretts' );
			}
			$phrase = $_options ['message'];
			$volumetts = $_options ['title'];
			if ($volumetts == '#title#' || $volumetts == '' || ! is_numeric ( $volumetts )) {
				$volumetts = 'nochange';
			}
			$tts = $phrase;
			$jeedompath = network::getNetworkAccess ( 'internal' );
			// $cmd = '/usr/bin/python ' .dirname(__FILE__) . '/../../3rdparty/executer_action.py '. $device.' '.$action. ' "'.$tts.'" "'.$jeedompath.'" '.$volumetts.' '.$jingle . ' ' . $moteurtts . ' ' . $options;
		} elseif ($action == 'setVolume') {
			if ($_options ['slider'] < 0) {
				$_options ['slider'] = 0;
			}
			if ($_options ['slider'] > 100) {
				$_options ['slider'] = 100;
			}
			$volume = $_options ['slider'];
			// $cmd = '/usr/bin/python ' .dirname(__FILE__) . '/../../3rdparty/executer_action.py '. $device.' '.$action.' '.$volume;
			$parm .= '&volume=' . $volume;
		} elseif ($action == 'vol+' || $action == 'vol-') {
			if ($chromecast->getConfiguration ( 'volume_inc' ) == '' or $chromecast->getConfiguration ( 'volume_inc' ) < 2) {
				$pas = 1;
			} else {
				$pas = $chromecast->getConfiguration ( 'volume_inc' );
			}
			if ($action == 'vol+') {
				$change = $pas;
			} else {
				$change = - $pas;
			}
			$volumechange = $change;
			$action = 'changeVolume';
			// $cmd = '/usr/bin/python ' .dirname(__FILE__) . '/../../3rdparty/executer_action.py '. $device.' '.$action.' '.$volumechange;
		} elseif ($action == 'onall' || $action == 'offall') {
			$eqLogics = eqLogic::byType ( 'chromecast' );
			foreach ( $eqLogics as $chromecast ) {
				$device = $chromecast->getLogicalId ();
				$actionsub = substr ( $action, 0, - 3 );
				// $cmd = '/usr/bin/python ' .dirname(__FILE__) . '/../../3rdparty/executer_action.py '. $device.' '.$actionsub;
				// log::add('chromecast','debug','Execution de la commande suivante : ' .$cmd);
				// $result=shell_exec($cmd);
			}
			return;
		} elseif ($action == 'lireurl') {
			$option = $_options ['message'];
			$parm .= '&url=' . urlencode ( $option );
		} elseif ($action == 'youtubePlay') {
			$option = $_options ['message'];
			$parm .= '&videoId=' . urlencode ( $option );
		}  elseif ($action == 'setTime') {
			if ($_options ['slider'] < 0) {
				$_options ['slider'] = 0;
			}
			
			$time = $_options ['slider'];
			// $cmd = '/usr/bin/python ' .dirname(__FILE__) . '/../../3rdparty/executer_action.py '. $device.' '.$action.' '.$volume;
			$parm .= '&setTime=' . $time;
		}
		log::add ( 'chromecast', 'debug', 'Execution de la commande suivante1 : ' . $parm );
		// $result=shell_exec($cmd);
		$result = chromecast::executeAction ( $parm );
		/*
		$a = print_r ( $result, true );
		log::add ( 'chromecast', 'debug', 'cmd result= ' . $a );
		*/
		if (! $result [0]) {
			if (isset($result [2]) and $result [2] == 'HTTP/1.1 404 Not Found') {
				$cmd = $chromecast->getCmd ( 'info', 'status' );
				if ($cmd) {
					$value = 'off';
					log::add ( 'chromecast', 'debug', 'event status ok value :' . $value );
					$cmd->event ( $value );
					$cmd->setValue ( $value );
					$cmd->save ();
					$chromecast->refreshWidget ();
				} else {
					log::add ( 'chromecast', 'debug', 'cmd status not found for ' . $device );
				}
			}
			//log::add ( 'chromecast', 'debug', 'Execution de la commande suivante2 : ' . $parm . ' result=' . $result [1] );
		} else {
			/*
			$a = print_r ( $result [1], true );
			log::add ( 'chromecast', 'debug', 'retour de la commande ' . $a );
			*/
		}
	}
}

?>