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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
  
	if (init('action') == 'scanchromecast') {
		$return = chromecast::scanchromecast();
		if ($return[0]) {
			ajax::success($return[1]);
		} else {
			ajax::error($return[1]);
		}
	}
	
    if (init('action') == 'onall') {
		chromecast::onoffall('on');
		ajax::success();
	}
    
    if (init('action') == 'offall') {
		chromecast::onoffall('off');
		ajax::success();
	}
    
    if (init('action') == 'getchromecast') {
		if (init('object_id') == '') {
			$object = object::byId($_SESSION['user']->getOptions('defaultDashboardObject'));
		} else {
			$object = object::byId(init('object_id'));
		}
		if (!is_object($object)) {
			$object = object::rootObject();
		}
		$return = array();
		$return['eqLogics'] = array();
		if (init('object_id') == '') {
			foreach (object::all() as $object) {
				foreach ($object->getEqLogic(true, false, 'chromecast') as $chromecast) {
					$return['eqLogics'][] = $chromecast->toHtml(init('version'));
				}
			}
		} else {
			foreach ($object->getEqLogic(true, false, 'chromecast') as $chromecast) {
				$return['eqLogics'][] = $chromecast->toHtml(init('version'));
			}
			foreach (object::buildTree($object) as $child) {
				$chromecasts = $child->getEqLogic(true, false, 'chromecast');
				if (count($chromecasts) > 0) {
					foreach ($chromecasts as $chromecast) {
						$return['eqLogics'][] = $chromecast->toHtml(init('version'));
					}
				}
			}
		}
		ajax::success($return);
	}
	
	if (init('action') == 'youtubeSearch') {
		require_once('../../3rdparty/Youtube/Youtube.php');
		$return = array();
		$query=init('query');
		
		$yt = new Youtube();  //Instantiate Youtube Object
		//$ytKey='AIzaSyDB7B43bQCRz0jjFe4mtI36mTucAwDQ32I';
		$ytKey=config::byKey('chromecastYoutubeKey', 'chromecast', '0');
		$yt->key($ytKey);  //Set Youtube API Key Here
		//Set Youtube Search Parameters
		$yt->set()->
		q($query)->
		maxResults(10)->
		order('relevance')->
		safeSearch('none')->
		videoDuration('any')->
		videoEmbeddable('true')->
		regionCode("FR");
		//Now get the Title and VideoIDS
		$num = count($yt->get()->id());  //Obtain number of results (taken from maxResults)
		$ytIDArr = $yt->get()->id();  //Video ID array
		$ytTitleArr = $yt->get()->title();  //Title array
		for ($i = 0; $i < $num; $i++) {
			$ytID = $ytIDArr[$i];
			$ytTitle = $ytTitleArr[$i];
			//Your code here, for example, you can link to the youtube video results like so.  Ex:
			//$link .= "<a href='http://www.youtube.com/watch?v=$ytID'>$ytTitle</a><br>";
			$return[]=array('id'=>$ytID,'title'=>$ytTitle);
		}
		$yt->clear();  //Clear query string, extremely important if iterating through multiple keywords!
		//echo $link;
		ajax::success($return);
	}
	

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
}
?>
