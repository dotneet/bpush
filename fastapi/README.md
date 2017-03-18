# fastapi

fastapi is an accelerator of receive counting api written with golang.
After you send a push notification, many clients access to your server in a short time.
fastapi can process many requests faster than api written with PHP.

## How to use

1. Edit config.yml

Change config.yml for your enviroment.
Redis setting must be set to same as bpush .

2. Run accelerator.

```bash
cd fastapi
go get
go run fastapi.go
```

In production environment we highly recommend you to use process management tool such a supervisord.

3. Setup reverse proxy to handle receive counting api.

Nginx example:

```nginx
location /sapi/v1/count_receive {
  proxy_pass http://localhost:8100/count_receive;
}
```

