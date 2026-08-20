# Running this on nginx

osTicket officially documents Apache and IIS. On nginx everything works, but
two things need `PATH_INFO` to reach PHP, and most nginx vhosts for osTicket
found in the wild drop it.

| URL | Needs PATH_INFO |
|---|---|
| `/scp/apps.php/timetrack/` — the report page | yes |
| `/api/timetrack/ticket/47.json` — the REST endpoints | yes |
| `/api/tickets.json` — osTicket's own ticket creation API | yes |

Note the last row: **this is broken in stock nginx setups regardless of this
plugin**. If your API returns `URL not supported` with HTTP 400, that is why.

## The PHP location block

This is what makes the report page work. The `(/|$)` is the important part —
with a plain `\.php$` the request never enters the block, because osTicket URLs
end in `/timetrack/`, not in `.php`:

```nginx
location ~ \.php(/|$) {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    include fastcgi_params;
    fastcgi_read_timeout 120;
}
```

The same block is what makes osTicket's AJAX dialogs work (`ajax.php/orgs/add`
and friends). If your "add organization" modal shows the whole portal inside
it, this is your bug.

## The API location block

`try_files` resets `$fastcgi_path_info`, so the usual

```nginx
location /api/ {
    try_files $uri $uri/ /api/http.php$is_args$args;   # <-- loses PATH_INFO
}
```

hands osTicket an empty path and the dispatcher answers `URL not supported`.
Build it explicitly instead:

```nginx
location ~ ^/api/(?<apipath>(?!http[.]php).*)$ {
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root/api/http.php;
    fastcgi_param SCRIPT_NAME /api/http.php;
    fastcgi_param PATH_INFO /$apipath;
    fastcgi_read_timeout 120;
}
```

The negative lookahead keeps `/api/http.php/...` out of the block so direct
calls still work.

## Checking it

```bash
# Should return the report page (302 to the login when not authenticated)
curl -I https://helpdesk.example.com/scp/apps.php/timetrack/

# Should return 401, not 400: 401 means the route was found and the key checked
curl -o /dev/null -w '%{http_code}\n' \
  https://helpdesk.example.com/api/timetrack/report.json
```

A `400` with `URL not supported` means `PATH_INFO` is still being dropped.

## Reverse proxies and CDNs

If osTicket sits behind a CDN, exclude it from HTML caching. A cached page
carries a stale CSRF token, which breaks the upgrader and every form — and on a
help desk it can serve one user's ticket to another.
