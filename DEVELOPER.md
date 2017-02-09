## FAQ

### Do I need SSL and a domain to develop this system?

Yes, you need SSL and a domain.
We recommend you to use Let's Encrypt.

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

