# Extras

Fixes for osTicket core bugs found while building this plugin. **They have
nothing to do with time tracking** and are not needed for the plugin to work.
They live here because they are small, verified, and someone else is bound to
hit the same thing.

Like any core patch, they must be re-applied after an osTicket upgrade.

## queue-pagination.patch

**Symptom:** an advanced search that returns 10 tickets offers 10 pages of
results. Pages 2 and up are empty, and the footer reads something like
`Showing 451 - 500 of about 500` on a help desk with 42 tickets.

**Cause:** two pieces of the core, working as designed and adding up to a bug.

`include/staff/templates/queue-tickets.tmpl.php` deliberately skips counting
when the queue is backed by the full-text search engine (counting full-text
matches is expensive) and passes the total as `'-'`. Then
`include/class.pagenate.php`:

```php
function setTotal($total, $approx=false) {
    if (is_numeric($total)) { ... }
    else {
        $this->total = 500;      // made up
        $this->isrealtotal = false;
    }
    $this->pages = ceil($this->total / $this->limit);
```

Every keyword search therefore claims 500 results without having counted
anything, and paginates over that number. With 25 rows per page you get 20
pages; with 50, ten. The "of **about**" in the footer is osTicket admitting the
number is invented.

**Fix:** count the rows actually rendered — they are right there — and correct
the paginator before the page links are printed. No extra query. If the page
came back full, it still offers the next one, so nothing is lost on large
result sets; if it came back short, this was the last page and the phantom
links disappear.

```bash
cd /var/www/osticket
patch -p1 < /path/to/extras/queue-pagination.patch
```

Verified on osTicket 1.18.4: a search returning 10 hits went from
`Page: [1] 2 3 4 5 6 7 8 9 10 »` to `Page: [1]`, and with 5 rows per page it
correctly offered page 2 and stopped there.
