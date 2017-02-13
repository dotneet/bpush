"use strict";

/**
 * With direct embedding, _bpush_env variable must be in global scope 
 * and contains information such as endpoint url for calling api.
 */ 

const VISITOR_ID_KEY = 'bpush_visitor_id';

function isSimpleEmbeddedMode() {
  return _bpush_env.simple_embedded;
}

// call bpush api with JSONP protocol.
function callBpushApi(url, callback, data) {
  var appKey = _bpush_env.app_key;
  var s = document.createElement('script');
  s.type = 'text/javascript';
  s.src = url + '?cb=' + callback + '&' + 'app_key=' + appKey + '&' + data;
  document.body.appendChild(s);
}

function isNotificationDeniedExplicit() {
  if ( typeof(Notification) === "undefined" ) {
    return false;
  }
  return Notification && Notification.permission === "denied";
}

function isSupportPushNotification() {
  if ( typeof(Promise) === 'undefined' || 
       typeof(Notification) === 'undefined' ) {
    return false;
  } 
  if ( !('serviceWorker' in navigator) ) {
    return false;
  }
  return true
}

function enablePushNotification() {
  return new Promise(function(resolve, reject) {
    if ( !isSupportPushNotification() ) {
      reject('PushNotification is not supporeted.');
      return;
    }
    if ( Notification.permission === 'denied' ) {
      reject('PushNotification is denied by browser setting.');
      return;
    }
    // If service worker is enabled and be allowed by an user do nothing.
    if ( isServiceWorkerEnabled() && Notification.permission !== 'default' ) {
      resolve();
      return;
    }
    requestPermission().then(function(){
      return registerServiceWorker();
    }).then(function(worker){
      return registerSubscription(worker);
    }).then(function(){
      resolve();
    }).catch(function(e){
      reject(e);
    })
  });
}

function requestPermission() {
  return new Promise(function(resolve, reject) {
    if ( typeof(Notification) === "undefined" ) {
      reject();
      return;
    }
    if ( Notification ) {
      if ( Notification.permission === "granted" ) {
        resolve();
      } else {
        Notification.requestPermission(function(selectedPermission) {
          if ( selectedPermission === "granted" ) {
            resolve();
          } else {
            reject();
          }
        });
      }
    } else {
      reject();
    }
  });
}

function isServiceWorkerEnabled() {
  if ( 'serviceWorker' in navigator ) {
    if ( navigator.serviceWorker.controller ) {
      return true;
    }
  }
  return false;
}

function registerServiceWorker() {
  return new Promise(function(resolve,reject){
    if ( 'serviceWorker' in navigator ) {
      try {
        var serviceWorkerPath = null;
        if ( isSimpleEmbeddedMode() ) {
          serviceWorkerPath = './service_worker';
        } else {
          serviceWorkerPath = './worker_' + _bpush_env.app_key + '.js';
        }
        var reg = null;
        navigator.serviceWorker.register(serviceWorkerPath, {
          scope: '.'
        }).then(function(registration) {
          reg = registration;
          return navigator.serviceWorker.ready;
        }, reject).then(function(){
          resolve(reg);
        });
      } catch (e) {
        console.log(e);
        reject();
      }
    } else {
      reject();
    }
  });
}

var pushManager = null;
function registerSubscription(worker) {
  return new Promise(function(resolve, reject) {
    if ( 'pushManager' in worker ) {
      pushManager = worker.pushManager;
      return pushManager.getSubscription().then(function(subscription){
        getSubscription(subscription).then(resolve, reject);
      }).catch(function(){
        resetSubscription();
        reject();
      });
    } else {
      reject();
    }
  });
}

var subscription = null;
function getSubscription(subscription) {
  return new Promise(function(resolve, reject){
    if ( !subscription ) {
      navigator.serviceWorker.ready.then(subscribe).then(resolve, reject);
      return;
    }
    sendSubscription(subscription).then(resolve,reject);
  });
}

var sendSubscriptionCallbackArgs = null;
function sendSubscriptionCallback(jsonStr) {
  var result = JSON.parse(jsonStr);
  if ( result.status === 'success' ) {
    window.localStorage.setItem(VISITOR_ID_KEY, result.visitor_id); 
    sendSubscriptionCallbackArgs.resolve();
  } else {
    sendSubscriptionCallbackArgs.reject();
  }
}

function sendSubscription(subscription) {
  //console.log('sendSubscription');
  return new Promise(function(resolve, reject) {
    var sid;
    sendSubscriptionCallbackArgs = {
      resolve: resolve,
      reject: reject
    };
    var data = encodeURIComponent(JSON.stringify(subscription.toJSON()));
    var params = 'data=' + data;
    var visitor_id = window.localStorage.getItem(VISITOR_ID_KEY);
    if ( visitor_id ) {
      params += '&visitor_id=' + visitor_id;
      // for failsafe. if server process is failed by invalid format of visitor_id this code could be work as failsafe.
      window.localStorage.removeItem(VISITOR_ID_KEY);
    }
    callBpushApi(_bpush_env.endpoint_base + '/sapi/v1/register_subscription', 'sendSubscriptionCallback', params);
  });
}

function resetSubscription() {
  //console.log('resetSubscription');
}

function subscribe(sw) {
  //console.log('subscribe');
  var data = {
    userVisibleOnly: true
  };
  if ( _bpush_env.vapid ) {
    data.applicationServerKey = _bpush_env.vapidPublicKey;
  }
  return sw.pushManager.subscribe(data).then(sendSubscription, resetSubscription);
}

function unsubscribe(sw) {
  sw.pushManager.unsubscribe();
}

function unregisterServiceWorker() {
  return new Promise(function(resolve,reject) {
    return navigator.serviceWorker.getRegistration().then(function(registration) {
      if ( registration ) {
        return registration.unregister().then(function(res){
          //console.log(res);
          resolve(res);
        }).catch(function(){
          reject();
        });
      } else {
        reject();
      }
    }).catch(function(){
        reject();
    });
  });
}

function send(message) {
  // This wraps the message posting/response in a promise, which will resolve if the response doesn't
  // contain an error, and reject with the error if it does. If you'd prefer, it's possible to call
  // controller.postMessage() and set up the onmessage handler independently of a promise, but this is
  // a convenient wrapper.
  return new Promise(function(resolve, reject) {
    var messageChannel = new MessageChannel();
    messageChannel.port1.onmessage = function(event) {
      if (event.data.error) {
        reject(event.data.error);
      } else {
        resolve(event.data);
      }
    };
    // This sends the message data as well as transferring messageChannel.port2 to the service worker.
    // The service worker can then use the transferred port to reply via postMessage(), which
    // will in turn trigger the onmessage handler on messageChannel.port1.
    // See https://html.spec.whatwg.org/multipage/workers.html#dom-worker-postmessage
    navigator.serviceWorker.controller.postMessage(message, [messageChannel.port2]);
  });
}

var setVisitorTagCallbackArgs = {};
function setVisitorTag(tags) {
  return new Promise(function(resolve, reject) {
    if ( !isSupportPushNotification() ) {
      return reject();
    }
    var visitor_id = window.localStorage.getItem(VISITOR_ID_KEY);
    if ( visitor_id && isServiceWorkerEnabled() && Notification.permission == 'granted' ) {
      var params = 'visitor_id=' + visitor_id + '&tags=' + tags.join(',');
      setVisitorTagCallbackArgs = {
        resolve: resolve,
        reject: reject
      }
      callBpushApi(_bpush_env.endpoint_base + '/sapi/v1/set_visitor_tag', 'setVisitorTagCallback', params);
    } else {
      return reject();
    }
  });
}

function setVisitorTagCallback(jsonStr) {
  var result = JSON.parse(jsonStr);
  if ( result.status === 'success' ) {
    setVisitorTagCallbackArgs.resolve();
  } else {
    setVisitorTagCallbackArgs.reject();
  }
}

