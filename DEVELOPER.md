## FAQ

### Do I need a SSL and a domain to develop this system?

No, you don't need those in development.
Service Worker can be hosted on localhost or https.

### Why isn't behavior of notifier.php changed althought changes php code?

Please restart the notifier.php.
php process already started doesn't read files again even if you change files.

## Frequently use commands.

### Compile a sass

```
gulp sass
gulp sass:watch  # add ':watch' to watch change files.
```

### Transpile a JavaScript

```
gulp closure
gulp closure:watch   # add ':watch' to watch change files.
```

