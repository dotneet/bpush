# What is bpush

**bpush** is a management system of web push notification.

## Screenshot

![Screenshot: bpush Dashboard](http://i.imgur.com/TJSgZMK.jpg)
![Screenshot: Register new notification](http://i.imgur.com/raCcrrO.jpg)

## Support Browsers

 - Chrome
 - Firefox

bpush supports VAPID that is standard for the web push notification.

## Features

 - Send a push notification.
 - Scheduled delivery.
 - Support an Non-SSL WebSite (Service-Worker is hosted by bpush host).
 - Send an notification via Web-API.
 - Manage multiple websites.
 - RSS coordination (deliver rss items as a push notification automatically).
 - Support Japanese language.

## Installation

See [INSTALL.md](INSTALL.md)

## fastapi

fastapi is an accelerator of receive count api written with golang.
After you send a push notification, many clients access to your server in a short time.
fastapi can process many requests faster than api written with PHP.

## Author

devneko <dotneet@gmail.com>

## License

MIT
