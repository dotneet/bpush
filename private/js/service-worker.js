"use strict";

/**
 * Reference:
 * http://updates.html5rocks.com/2015/03/push-notificatons-on-the-open-web
 */

self.addEventListener('install', function(evt) {
  //console.log('oninstall');
  evt.waitUntil(skipWaiting());
});

self.addEventListener('message', function(evt) {
  //console.log('onmessage');
});

self.addEventListener('push', function(evt) {
  var payload = null;
  var notification_id = '';
  if ( typeof(evt.data) !== 'undefined' && evt.data ) {
      var payload = evt.data.text();
      var data = JSON.parse(payload);
      if ( data && typeof(data.id) !== 'undefined' ) {
        payload = data;
        notification_id = data.id;
      }
  }
  if ( payload !== null ) {
    // If the notification has a payload show the notification immediately.
    // If you not use VAPID protocol a payload would be empty on Chrome Browser.

    evt.waitUntil(
      self.registration.showNotification(
        payload.subject,
        {
          body: payload.body,
          tag: payload.tag,
          icon: payload.icon + '?nid=' + payload.id 
        }
      ).then(function(){
        var endpoint = _bpush_env.endpoint_base + '/sapi/v1/count_receive';
        var params = "app_key=" + _bpush_env.app_key + "&nid=" + payload.id;
        return fetch(endpoint + '?' + params).then(function(response) {
          console.log('receive notification');
        });
      })
    );

  } else {
    var endpoint = _bpush_env.endpoint_base + '/sapi/v1/get_notification';
    evt.waitUntil(
        self.registration.pushManager.getSubscription().then(function(subscription) {
          var params = "app_key=" + _bpush_env.app_key + "&nid=";
          return fetch(endpoint + '?' + params).then(function(response) {
            if ( response.status !== 200 ) {
              //console.log('Looks like there was a problem. Status Code: ' + response.status);  
              throw new Error();
            }
            return response.json().then(function(data) {
              if ( data.error || !data.notification ) {  
                // console.error('The API returned an error.', data.error);  
                throw new Error();  
              }
              var notification = data.notification;
              return self.registration.showNotification(
                notification.subject,
                {
                  body: notification.body,
                  tag: notification.tag,
                  icon: notification.icon + '?nid=' + notification.id 
                }
              );
            });
          });
        })
    );
  }
});

self.addEventListener('notificationclick', function(evt) {
  //console.log('onnotifiationclick');

  evt.notification.close();

  // To avoid to display error message such as "This site has been update in the background".
  if ( evt.notification.tag == "user_visible_auto_notification" ) {
    return;
  }

  var params = evt.notification.icon.split("?")[1].split("&");
  var nid = params[0].split("=")[1];
  evt.waitUntil(
      clients.matchAll({  
        type: "window"  
      }).then(function(clientList) {  
        for (var i = 0; i < clientList.length; i++) {  
          var client = clientList[i];  
          if (client.url == '/' && 'focus' in client) {
            return client.focus();  
          }
        }  
        if (clients.openWindow) {
          var global = ("global",eval)("this");
          var endpointBase = global._bpush_env['endpoint_base'];
          var appKey = global._bpush_env.app_key;
          return clients.openWindow(endpointBase + '/sapi/v1/click?app_key=' + appKey + '&nid=' + nid);
        }
     })
  );
});


// Reference
// http://blog.nhiroki.jp/2015/04/18/service-worker-claim/
self.addEventListener('activate', function(event) {
  //console.log('onactivate');
  event.waitUntil(self.clients.claim());
});


