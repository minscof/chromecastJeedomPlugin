#!/usr/bin/env python
# -*- coding: utf-8 -*-
import os
import sys
import logging
import argparse
import subprocess
import threading
from time import time, localtime, strftime, sleep
from datetime import datetime
from itertools import count

from socketio.server import SocketIOServer
import urllib2
# beginning specific
import pychromecast
#import pychromecast.controllers.youtube as youtube
import uuid
import logging

__version__='0.9'

#sys.setdefaultencoding("utf-8")
# The socket connection is being setup
CONNECTION_STATUS_CONNECTING = "CONNECTING"
# The socket connection was complete
CONNECTION_STATUS_CONNECTED = "CONNECTED"
# The socket connection has been disconnected
CONNECTION_STATUS_DISCONNECTED = "DISCONNECTED"
# Connecting to socket failed (after a CONNECTION_STATUS_CONNECTING)
CONNECTION_STATUS_FAILED = "FAILED"
# The socket connection was lost and needs to be retried
CONNECTION_STATUS_LOST = "LOST"

class connectionListener:
    def __init__(self, name, cast):
        self.name = name
        self.cast = cast
        self.status =''
        self.ip=cast.host
        '''
        # The socket connection is being setup
        CONNECTION_STATUS_CONNECTING = "CONNECTING"
        # The socket connection was complete
        CONNECTION_STATUS_CONNECTED = "CONNECTED"
        # The socket connection has been disconnected
        CONNECTION_STATUS_DISCONNECTED = "DISCONNECTED"
        # Connecting to socket failed (after a CONNECTION_STATUS_CONNECTING)
        CONNECTION_STATUS_FAILED = "FAILED"
        # The socket connection was lost and needs to be retried
        CONNECTION_STATUS_LOST = "LOST"
        '''
        print '***************** initialize connectionListener for :',self.name
        
    def new_connection_status(self, new_status):
    # new_status.status will be one of the CONNECTION_STATUS_ constants defined in the
    # socket_client module.
        global chromecasts,casts
        try:
            dataFull = ['/usr/bin/php',jeeChromecast,'uuid='+str(self.cast.uuid)]
            print '================= new status',new_status
            logger.debug('listener connection status  for '+self.name+' '+new_status.status)
            if new_status.status != self.status:
                self.status = new_status.status
                if new_status.status != CONNECTION_STATUS_CONNECTED:
                    print 'disconnect Chromecast',self.name,self.cast.uuid
                    print 'notify off for chromcast %s',self.name
                    subprocess.Popen(['/usr/bin/php',jeeChromecast,'uuid='+str(casts[self.name]['uuid']),'status=off','appli=aucune','isActiveInput=false','isStandBy=true','appId=-','statusText=','title='])
                    casts[self.name]={'uuid':str(casts[self.name]['uuid']),'appli':'aucune','isActiveInput':'false','isStandBy':'true','appId':'-','statusText':'','title':''}
                    if self.name in chromecasts:
                        chromecasts.pop(self.name)
                print '==================== tKtKtK'
        except BaseException as e:
            print '===================='
            print 'exception status connectionListenner'
            print("args: ", e.args)
            print '====================' 

class statusListener:
    def __init__(self, name, cast):
        self.name = name
        self.cast = cast
        self.display_name = ''
        self.is_active_input=''
        self.is_stand_by=''
        self.volume_level=''
        self.volume_muted=''
        self.app_id=''
        self.status_text=''
        print '***************** initialize statusListener for :',self.name
      
    def new_cast_status(self, status):
        global casts, chromecasts
        print '***************** status Listener change.....',self.name
        dataFull = ['/usr/bin/php',jeeChromecast,'uuid='+str(self.cast.uuid)]
        data = []
        #print self.cast
        #self.cast.wait()
        print '_____status=',status
        #print '.....old=',self.cast.status
        display_name=''
        is_active_input=''
        is_stand_by=''
        volume_level=''
        volume_muted=''
        app_id=''
        status_text=''
        try:
            if status is not None:
                print 'debug l1'
                if status.display_name is not None:
                    display_name=str(status.display_name)
                    if display_name != self.display_name:
                        data.append('appli='+display_name)
                        self.display_name=display_name
                if status.is_active_input is not None:
                    is_active_input=str(status.is_active_input)
                    if is_active_input != self.is_active_input:
                        data.append('isActiveInput='+is_active_input)
                        self.is_active_input=is_active_input
                if status.is_stand_by is not None:
                    is_stand_by=str(status.is_stand_by)
                    if is_stand_by != self.is_stand_by:
                        data.append('isStandBy='+is_stand_by)
                        self.is_stand_by=is_stand_by
                if status.volume_level is not None:
                    volume_level=str(100*status.volume_level)
                    if volume_level != self.volume_level:
                        data.append('volume='+volume_level)
                        self.volume_level=volume_level
                print 'debug l2'        
                if status.volume_muted is not None:
                    volume_muted=str(status.volume_muted)
                    if volume_muted != self.volume_muted:
                        data.append('volumeMuted='+volume_muted)
                        self.volume_muted=volume_muted
                if status.app_id is not None:
                    app_id=status.app_id
                    if app_id != self.cast.app_id:
                        data.append('appId='+app_id)
                        self.app_id=app_id
                if status.status_text is not None:
                    status_text=str(status.status_text)
                    if status_text != self.status_text:
                        data.append('statusText='+status_text)
                        self.status_text=status_text
                print 'data=',data
                #subprocess.Popen(['/usr/bin/php',jeeChromecast,'uuid='+str(self.cast.uuid),'status=on','appli='+display_name,'isActiveInput='+is_active_input,'isStandBy='+is_stand_by,'volume='+volume_level,'volumeMuted='+volume_muted,'appId='+app_id,'statusText='+status_text,'title='])
                if len(data) > 0:
                    dataFull.extend(data)
                    subprocess.Popen(dataFull)
                casts[self.name]={'uuid':str(self.cast.device.uuid),'appli':display_name,'isActiveInput':is_active_input,'isStandBy':is_stand_by,'volume':volume_level,'volumeMuted':volume_muted,'appId':app_id,'statusText':status_text,'title':''}
            else:
                print 'disconnect Chromecast',self.name,self.cast.uuid
                print 'notify off for chromcast %s',self.name
                subprocess.Popen(['/usr/bin/php',jeeChromecast,'uuid='+str(casts[self.name]['uuid']),'status=off','appli=aucune','isActiveInput=false','isStandBy=true','appId=-','statusText=','title='])
                print "g1"
                casts[self.name]={'uuid':str(casts[self.name]['uuid']),'appli':'aucune','isActiveInput':'false','isStandBy':'true','appId':'-','statusText':'','title':''}
                print "g2"
                print 'chromecasts=',chromecasts
                if self.name in chromecasts:
                    print 'g3'
                    chromecasts.pop(self.name)
                    print 'g4'
                print "g5"
        except BaseException as e:
            print '===================='
            print 'exception status Listenner'
            print("args: ", e.args)
            print '===================='
            
        print '************************ ZKZKZK'
    
class statusMediaListener:
    def __init__(self, name, cast):
        self.name = name
        self.cast = cast
        self.title = ''
        self.current_time =''
        self.duration = ''
        self.player_state = ''
        print '***************** initialize statusMediaListener for :',self.name
        
    def new_media_status(self, status):
        print '++++++++++++++++++ status media change.....',self.name
        print '..............status=',status
        try:
            dataFull = ['/usr/bin/php',jeeChromecast,'uuid='+str(self.cast.uuid)]
            data = []
            title = ''
            player_state = ''
            current_time = ''
            duration = ''
            if status is not None:
                if status.title is not None:
                    title=str(status.title.encode('utf-8'))
                else:
                    if status.content_id is not None:
                        title = str(status.content_id.encode('utf-8'))
                    else:
                        title=''
                if title != self.title:
                        data.append('title='+title)
                        self.title = title  
                if status.current_time is not None:
                    current_time=str(status.current_time)
                    if current_time != self.current_time:
                        data.append('current_time='+current_time)
                        self.current_time = current_time
                if status.duration is not None:
                    duration=str(status.duration)
                else:
                    duration = '0'
                if duration != self.duration:
                    data.append('duration='+duration)
                    self.duration = duration
                player_state=str(status.player_state)
                print 'player_state=',player_state
                print 'old player state=',self.player_state
                if player_state != self.player_state:
                    data.append('player_state='+player_state)
                    self.player_state = player_state
                    if player_state == 'IDLE':
                        #todo revoir la manière de détecter la fin
                        '''
                        ex trame
                        DEBUG:pychromecast.controllers:Media:Received status {u'status': [{u'mediaSessionId': 1376630103, u'supportedMediaCommands': 3, u'media': {u'contentType': u'x-youtube/video', u'contentId': u'Hn2q5_Wd-U8', u'customData': {u'listId': u'RQAGdO5p9ujZgkdPC7UjyLEknVrjiQXopBUzSbtcqc0E1Jh2BwHjHUtTdAxyTkC6oAo9fiUkKsHu5M', u'currentIndex': 1}, u'streamType': u'BUFFERED', u'duration': 308.66285714285715, u'metadata': {u'images': [{u'url': u'https://i.ytimg.com/vi/Hn2q5_Wd-U8/hqdefault.jpg'}], u'metadataType': 0, u'title': u'NORMAN - TECHNIQUES DE DRAGUE 3'}}, u'customData': {u'playerState': 0}, u'playbackRate': 1, u'volume': {u'muted': False, u'level': 1}, u'idleReason': u'FINISHED', u'playerState': u'IDLE'}], u'type': u'MEDIA_STATUS', u'requestId': 3}

                        '''
                        if str(status.idle_reason) == 'FINISHED':
                            print '                                         reason='+str(status.idle_reason)
                            '''
                            title = ''
                            data.append('title='+title)
                            self.title = title
                            '''
                            current_time = '0'
                            data.append('current_time='+current_time)
                            self.current_time = current_time
                            duration = '0' 
                            data.append('duration='+duration)
                            self.duration = duration
                print 'data=',data
                #subprocess.Popen(['/usr/bin/php',jeeChromecast,'uuid='+str(self.cast.uuid),'current_time='+time,'title='+title,'player_state='+player_state])
                if len(data) > 0:
                    dataFull.extend(data)
                    subprocess.Popen(dataFull)
        except BaseException as e:
            print '===================='
            print 'exception statusMediaListener new status'
            print("args: ", e.args)
            print '===================='
        print '++++++++++++++++++ OKOKOK'
    #

def refreshChromecastsList():
        global chromecasts, casts
        try :
            chromecastsCurrentList=pychromecast.get_chromecasts(tries=2)
            chromecastsCurrent={cc.device.friendly_name: cc
                for cc in chromecastsCurrentList}
            #chromecastsCurrent=pychromecast.get_chromecasts_as_dict(tries=2)
            #chromecastsCurrent=pychromecast.get_chromecasts_as_dict()
        except AttributeError as e:
            print '===================='
            print 'exception get_chromecasts'
            print("args: ", e.args)
            print '===================='
        except Exception,e:
            print 'exception scan chromecasts 2', e
            logger.debug('exception occurs during scan chromecasts 2!!!!')
        diff=DictDiffer(chromecastsCurrent,chromecasts)
        print "added:",diff.added()
        print "Removed:", diff.removed()
        print "Changed:", diff.changed()
        print "Unchanged:", diff.unchanged()
        
        chromecastsRemoved= {}
        for key in diff.removed():
            chromecastsRemoved[key]=chromecasts[key]
        
        for key,chromecast in chromecastsRemoved.items():
            print 'notify off for chromcast ',key
            subprocess.Popen(['/usr/bin/php',jeeChromecast,'uuid='+str(casts[key]['uuid']),'status=off','appli=aucune','isActiveInput=false','isStandBy=true','appId=-','statusText= ','title='])
            casts[key]={'uuid':str(casts[key]['uuid']),'appli':'aucune','isActiveInput':'false','isStandBy':'true','appId':'-','statusText':' ','title':''}
            #Chromecast.disconnect()
            if key in chromecasts:
                chromecasts.pop(key)
        '''
        chromecastsChanged= {}
        for key in diff.changed():
            chromecastsChanged[key]=chromecasts[key]
        
        for key,chromecast in chromecastsChanged.items():
            if chromecast is not None:
                print ('notify change for chromcast %s',key)
                chromecast.wait()
                subprocess.Popen(['/usr/bin/php',jeeChromecast,'uuid='+str(chromecast.device.uuid),'status=on','appli='+str(chromecast.status.display_name),'isActiveInput='+str(chromecast.status.is_active_input),'isStandBy='+str(chromecast.status.is_stand_by),'volume='+str(100*chromecast.status.volume_level),'volumeMuted='+str(chromecast.status.volume_muted),'appId='+str(chromecast.status.app_id),'statusText='+str(chromecast.status.status_text)])
                casts[key]={'uuid':str(chromecast.device.uuid),'appli':str(chromecast.status.display_name),'isActiveInput':str(chromecast.status.is_active_input),'isStandBy':str(chromecast.status.is_stand_by),'volume':str(100*chromecast.status.volume_level),'volumeMuted':str(chromecast.status.volume_muted),'appId':str(chromecast.status.app_id),'statusText':str(chromecast.status.status_text)}
        '''
        chromecastsAdded= {}
        for key in diff.added():
            chromecastsAdded[key]=chromecastsCurrent[key]
        
        for key,chromecast in chromecastsAdded.items():
            if chromecast is not None:
                try:
                    print 'notify on for chromcast %s' % key
                    chromecast.wait()
                    subprocess.Popen(['/usr/bin/php',jeeChromecast,'uuid='+str(chromecast.device.uuid),'status=on','appli='+str(chromecast.status.display_name),'isActiveInput='+str(chromecast.status.is_active_input),'isStandBy='+str(chromecast.status.is_stand_by),'volume='+str(100*chromecast.status.volume_level),'volumeMuted='+str(chromecast.status.volume_muted),'appId='+str(chromecast.status.app_id),'statusText='+str(chromecast.status.status_text),'title='])
                    casts[key]={'uuid':str(chromecast.device.uuid),'appli':str(chromecast.status.display_name),'isActiveInput':str(chromecast.status.is_active_input),'isStandBy':str(chromecast.status.is_stand_by),'volume':str(100*chromecast.status.volume_level),'volumeMuted':str(chromecast.status.volume_muted),'appId':str(chromecast.status.app_id),'statusText':str(chromecast.status.status_text)}
                    chromecasts[key]=chromecast
                
                    listenerConnection = connectionListener(chromecast.name, chromecast)
                    chromecast.register_connection_listener(listenerConnection)
                    listenerCast = statusListener(chromecast.name, chromecast)
                    chromecast.register_status_listener(listenerCast)
                    listenerMedia = statusMediaListener(chromecast.name, chromecast)
                    chromecast.media_controller.register_status_listener(listenerMedia)
                except:
                    print '===================='
                    print 'error during register listener !'
                    print("args: ", e.args)
                    print '===================='
        
        print 'all casts ',casts    


#function called periodically 
def polling(unstr):
    polling.active=True
    if not (polling.counter % 10):
        print datetime.now().strftime('%Y-%m-%d %H:%M:%S.%f'), unstr,  polling.counter
        try:
            refreshChromecastsList()
        except Exception,e:
            print 'exception polling',e
    polling.counter +=1        

def not_found(start_response):
    start_response('404 Not Found', [])
    return ['<h1>Not Found</h1>']
    
def end_daemon(start_response):
    start_response('200 Ok', [])
    sys.exit()
    #return ['<h1>End daemon</h1>']
    

class jeedomHandler(object):
    def __init__(self):
        # initialization.
        print 'initialize jeedom handler'
        self.polling = MyTimer(10.0, polling, ["polling cast device"])
        if automaticScan:
            self.polling.start()
        
    def __call__(self, environ, start_response):
        cmd = environ['PATH_INFO'].strip('/')
        arg = urllib2.unquote(environ['QUERY_STRING'])
        key = ''
        value = ''
        key2 = ''
        value2 = ''
        if arg:
            options = arg.split('&') 
            key = options[0].partition('=')[0]
            value = urllib2.unquote(options[0].partition('=')[2])
            if len(options) >= 2:
                key2 = options[1].partition('=')[0]
                value2 = urllib2.unquote(options[1].partition('=')[2])
            
        print '________________________________________________________'
        print '......CMD:', cmd, ' arg:', arg, ' key:', key, ' value: ', value, ' key2: ', key2, ' value2: ', value2
        
        logger.debug('..CMD: %s arg: %s key: %s value: %s key2: %s value2: %s', cmd,  arg,  key,  value,  key2,  value2)
        
        if cmd == 'startPolling':
            self.polling.start()
            start_response('200 OK', [('Content-Type', 'text/html')])
            return ['<h1>start Polling command done.</h1>']
        
        if cmd == 'stopPolling':
            if self.polling != '':
                self.polling.stop()
            start_response('200 OK', [('Content-Type', 'text/html')])
            return ['<h1>stop Polling command done.</h1>']
        
        if cmd == 'halt':
            print 'arrêt du serveur demandé'
            if polling.active:
                self.polling.stop()
                polling.active=False
            return end_daemon(start_response)
        
        # beginning specicific command
        
        if cmd == 'scan':
            refreshChromecastsList()
            data = '{'
            i=0
            for key,chromecast in chromecasts.items():
                data +='"'+str(i)+'":{"vendor":"'+str(chromecast.device.manufacturer )+'","uuid":"'+str(chromecast.uuid)+'","logicalName":"'+str(chromecast.name)+'","ip":"'+str(chromecast.host)+'","modelName":"'+str(chromecast.device.model_name )+'"}'
                i+=1
                    
            data +='}'
                
            print "data =", data
            logger.debug('scan='+data)
            content_type = "text/javascript"
            start_response('200 OK', [('Content-Type', content_type)])
            return [data]
        
        if cmd == 'vol+':
            #x=uuid.UUID(value)
            chromecast=None
            #logger.debug('search chromecast %s',value)
            try:
                if value in chromecasts.keys():
                    #logger.debug('chromecast found %s',value)
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
                    #chromecast.wait()
            except BaseException as e:
                chromecast = None
                print("args: ", e.args)
                logger.warn('exception chromecast not found %s',value)
            
            if chromecast is not None:
                print chromecast
                result=int(100*chromecast.volume_up())
                content_type = "text/javascript"
                start_response('200 OK', [('Content-Type', content_type)])
                return ['{"vol":"'+str(result)+'"}']
            else:
                print "notfound"
                return not_found(start_response)
            
        if cmd == 'vol-':
            chromecast=None
            try:
                if value in chromecasts.keys():
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
            except:
                chromecast = None
                logger.warn('exception chromecast not found %s',value)
            
            if chromecast is not None:
                result=int(100*chromecast.volume_down())
                content_type = "text/javascript"
                start_response('200 OK', [('Content-Type', content_type)])
                return ['{"vol":"'+str(result)+'"}']
            else:
                print "notfound"
                return not_found(start_response)

        if cmd == 'setVolume':
            chromecast=None
            try:
                if value in chromecasts.keys():
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
             
                if chromecast is not None:
                    result=int(100*chromecast.set_volume(float(value2)/100.0))
                    content_type = "text/javascript"
                    start_response('200 OK', [('Content-Type', content_type)])
                    return ['{"vol":"'+str(result)+'"}']
            except:
                chromecast = None
                logger.warn('exception chromecast not found %s',value)
                if chromecast is not None:
                    chromecast.disconnect(10.0,False)
                    chromecast = None
                    if value in chromecasts:
                        chromecasts.pop(value)
                    #notify disconnect ?
            
            print "notfound"
            return not_found(start_response)           
        
        if cmd == 'mute':
            chromecast=None
            try:
                if value in chromecasts.keys():
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
            except:
                chromecast = None
                logger.warn('exception chromecast not found %s',value)
            
            if chromecast is not None:
                result=chromecast.set_volume_muted(True)
                content_type = "text/javascript"
                start_response('200 OK', [('Content-Type', content_type)])
                return ['{"mute":"'+str(result)+'"}']
            else:
                print "notfound"
                return not_found(start_response)
        
        if cmd == 'unmute':
            chromecast=None
            try:
                if value in chromecasts.keys():
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
            except:
                chromecast = None
                logger.warn('exception chromecast not found %s',value)
            
            if chromecast is not None:
                result=chromecast.set_volume_muted(False)
                content_type = "text/javascript"
                start_response('200 OK', [('Content-Type', content_type)])
                return ['{"mute":"'+str(result)+'"}']
            else:
                print "notfound"
                return not_found(start_response)
            
        if cmd == 'lireurl':
            chromecast=None
            try:
                if value in chromecasts.keys():
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
            except:
                chromecast = None
                logger.warn('exception chromecast not found %s',value)
            
            if chromecast is not None:
                url='http://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4'
                fmt='video/mp4'
                if value2!='Ceci+est+un+test+de+message+pour+la+commande+Jouer+Url':
                    url=value2
                result=chromecast.play_media(url,fmt)
                content_type = "text/javascript"
                start_response('200 OK', [('Content-Type', content_type)])
                return ['{"lireurl":"'+str(result)+'"}']
            else:
                print "notfound"
                return not_found(start_response)
        
        if cmd == 'youtubePlay':
            chromecast=None
            result='nok'
            try:
                if value in chromecasts.keys():
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
            except:
                chromecast = None
                logger.warn('exception chromecast not found %s',value)
            
            if chromecast is not None:
                id="L0MK7qz13bU"
                if value2!='':
                    id=value2
                
                #result=yt.play_video("L0MK7qz13bU")
                #https://www.youtube.com/watch?v=mL9B8wuEdms
                #bypass youtube controlleur does not work
                #chromecast.register_handler(yt)
                #result=yt.play_video(id)
                url='https://www.youtube.com/watch?v='+id
                fmt='video/mp4'
                
                result=chromecast.play_media(url,fmt)
                print 'youtube id',id
                '''
                head = {'Content-type': 'application/json', 'Accept': 'text/plain'}
                address = "192.168.0.19"
                url = "http://%s:8008/apps/YouTube" % (address)
                requests.post(url, id, headers=head)
                '''
                urllib2.urlopen(url) 
                
                content_type = "text/javascript"
                start_response('200 OK', [('Content-Type', content_type)])
                return ['{"youtubePlay":"'+str(result)+'"}']
            else:
                print "notfound"
                return not_found(start_response)

        if cmd == 'setTime':
            chromecast=None
            try:
                if value in chromecasts.keys():
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
            except:
                chromecast = None
                logger.warn('exception chromecast not found %s',value)
            
            if chromecast is not None:
                print 'new time= ',value2
                result=chromecast.media_controller.seek(float(value2))
                content_type = "text/javascript"
                start_response('200 OK', [('Content-Type', content_type)])
                return ['{"vol":"'+str(result)+'"}']
            else:
                print "notfound"
                return not_found(start_response)
        
        if cmd == 'play':
            chromecast=None
            try:
                if value in chromecasts.keys():
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
            except:
                chromecast = None
                logger.warn('exception chromecast not found %s',value)
            
            if chromecast is not None:
                result=chromecast.media_controller.play()
                content_type = "text/javascript"
                start_response('200 OK', [('Content-Type', content_type)])
                return ['{"play":"'+str(result)+'"}']
            else:
                print "notfound"
                return not_found(start_response)
        
        if cmd == 'pause':
            chromecast=None
            try:
                if value in chromecasts.keys():
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
            except:
                chromecast = None
                logger.warn('exception chromecast not found %s',value)
            
            if chromecast is not None:
                result=chromecast.media_controller.pause()
                content_type = "text/javascript"
                start_response('200 OK', [('Content-Type', content_type)])
                return ['{"pause":"'+str(result)+'"}']
            else:
                print "notfound"
                return not_found(start_response)
        
        if cmd == 'stop':
            chromecast=None
            try:
                if value in chromecasts.keys():
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
            except:
                chromecast = None
                logger.warn('exception chromecast not found %s',value)
            
            if chromecast is not None:
                result=chromecast.media_controller.stop()
                content_type = "text/javascript"
                start_response('200 OK', [('Content-Type', content_type)])
                return ['{"stop":"'+str(result)+'"}']
            else:
                print "notfound"
                return not_found(start_response)
        
        if cmd in ['next','forward']:
            chromecast=None
            try:
                if value in chromecasts.keys():
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
            except:
                chromecast = None
                logger.warn('exception chromecast not found %s',value)
            
            if chromecast is not None:
                print 'status media contoller=',chromecast.media_controller.status
                result='nok'
                if chromecast.media_controller.status.duration is not None:
                    result=chromecast.media_controller.skip()
                content_type = "text/javascript"
                start_response('200 OK', [('Content-Type', content_type)])
                return ['{"next":"'+str(result)+'"}']
            else:
                print "notfound"
                return not_found(start_response)
            
        if cmd in ['rewind', 'previous']:
            chromecast=None
            try:
                if value in chromecasts.keys():
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
            except:
                chromecast = None
                logger.warn('exception chromecast not found %s',value)
            
            if chromecast is not None:
                result=chromecast.media_controller.rewind()
                content_type = "text/javascript"
                start_response('200 OK', [('Content-Type', content_type)])
                return ['{"rewind":"'+str(result)+'"}']
            else:
                print "notfound"
                return not_found(start_response)
        
        if cmd in ['off']:
            chromecast=None
            try:
                if value in chromecasts.keys():
                    chromecast=chromecasts[value]
                else:
                    logger.debug('chromecast not found %s',value)
            except:
                chromecast = None
                logger.warn('exception chromecast not found %s',value)
            
            if chromecast is not None:
                result=chromecast.quit_app()
                content_type = "text/javascript"
                start_response('200 OK', [('Content-Type', content_type)])
                return ['{"off":"'+str(result)+'"}']
            else:
                print "notfound"
                return not_found(start_response)
        
                # end specicific command
        logger.warn('Command not recognized : %s', cmd)    
        print "Command not recognized :", cmd
        
        return not_found(start_response)


class MyTimer: 
    def __init__(self, tempo, target, args= [], kwargs={}): 
        self._target = target 
        self._args = args 
        self._kwargs = kwargs 
        self._tempo = tempo 
  
    def _run(self): 
        self._timer = threading.Timer(self._tempo, self._run) 
        self._timer.start() 
        self._target(*self._args, **self._kwargs) 
  
    def start(self): 
        self._timer = threading.Timer(self._tempo, self._run) 
        self._timer.start() 
  
    def stop(self): 
        self._timer.cancel() 


class DictDiffer(object):
    """
    Calculate the difference between two dictionaries as:
    (1) items added
    (2) items removed
    (3) keys same in both but changed values
    (4) keys same in both and unchanged values
    """
    def __init__(self, current_dict, past_dict):
        self.current_dict, self.past_dict = current_dict, past_dict
        self.current_keys, self.past_keys = [
            set(d.keys()) for d in (current_dict, past_dict)
        ]
        self.intersect = self.current_keys.intersection(self.past_keys)

    def added(self):
        return self.current_keys - self.intersect

    def removed(self):
        return self.past_keys - self.intersect

    def changed(self):
           
        return set(o for o in self.intersect
                   if self.past_dict[o] != self.current_dict[o])
        

    def unchanged(self):
        return set(o for o in self.intersect
                   if self.past_dict[o] == self.current_dict[o])

'''
********************************************************************
*
* main

 Arguments
1 : 1/0 debug on/off
2 : scan=true/false
3 : xxx port


*
********************************************************************
'''

reqlog = logging.getLogger("requests.packages.urllib3.connectionpool")
reqlog.disabled = True

logger = logging.getLogger("chromecastDaemon")
handler_info = logging.FileHandler("/usr/share/nginx/www/jeedom/log/chromecastDaemon", mode="a", encoding="utf-8")
formatter = logging.Formatter("[%(asctime)s][%(levelname)s][%(name)s] : %(message)s")
handler_info.setFormatter(formatter)
logger.setLevel(logging.INFO)
logger.addHandler(handler_info)


logger.info('Start chromecast daemon %s',sys.argv[1])

if len(sys.argv) > 1 and sys.argv[1] == 'debug' :
    level = logging.DEBUG
else:
    level = logging.INFO
    f = open(os.devnull, 'w')
    sys.stdout = f
    
logging.basicConfig(level=level)
logger.setLevel(level)
logger.info('Level of log is %s',level)

print 'chromecastDaemon level log ',logger.level

logger2 = logging.getLogger("pychromecast")
#logger2.setLevel(10)
logger2.setLevel(level)
print 'pychromecast level log ',logger2.level


# automaticScan=${2}
automaticScan = True
if len(sys.argv) > 2:
    if sys.argv[2] == 'scan=false':
        automaticScan = False


if len(sys.argv) > 3:
    PORT = int(sys.argv[3])
else:
    PORT = 6100


jeeChromecast = os.path.abspath(os.path.join(os.path.dirname(__file__), '../core/php/jeeChromecast.php')) 
if jeeChromecast == '/core/php/jeeChromecast.php' :
    jeeChromecast = '/usr/share/nginx/www/jeedom/plugins/chromecast/core/php/jeeChromecast.php'


polling.counter=0
polling.active=False
chromecasts={}
casts={}
#yt = youtube.YouTubeController()


refreshChromecastsList()
'''
try :
    chromecasts=pychromecast.get_chromecasts_as_dict(tries=1)
except:
    print 'exception scan chromecasts 1'
    logger.debug('exception occurs during scan chromecasts 1!!!!') 
for key,chromecast in chromecasts.items():
    chromecast.wait()
    subprocess.Popen(['/usr/bin/php',jeeChromecast,'uuid='+str(chromecast.uuid),'status=on','appli='+str(chromecast.app_display_name),'isActiveInput='+str(chromecast.status.is_active_input),'isStandBy='+str(chromecast.status.is_stand_by),'volume='+str(100*chromecast.status.volume_level),'volumeMuted='+str(chromecast.status.volume_muted),'appId='+str(chromecast.status.app_id),'statusText='+str(chromecast.status.status_text),'title='])
    casts[key]={'uuid':str(chromecast.uuid),'appli':str(chromecast.app_display_name),'isActiveInput':str(chromecast.status.is_active_input),'isStandBy':str(chromecast.status.is_stand_by),'volume':str(chromecast.status.volume_level),'volumeMuted':str(chromecast.status.volume_muted),'appId':str(chromecast.status.app_id),'statusText':str(chromecast.status.status_text),'title':''}
    try:
        listenerCast = statusListener(chromecast.name, chromecast)
        chromecast.register_status_listener(listenerCast)
        listenerMedia = statusMediaListener(chromecast.name, chromecast)
        chromecast.media_controller.register_status_listener(listenerMedia)
    except:
        print 'error register callback'
'''

print 'list of chromecast',chromecasts
print 'active casts ',casts


try:
    SocketIOServer(('', PORT), jeedomHandler(),policy_server=False,resource="socket.io").serve_forever()
except (KeyboardInterrupt, SystemExit):
    print 'interception signal'
    logger.info('Stop chromecast daemon')
    sys.exit(0)
except Exception,e :
    print 'exception main',e
