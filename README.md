# anti-DOS
Simple, fast and lightweight PHP script to rate limit accesses. Run in memory (shmop) to improve performance

Approach with shmop worked faster than with redis and memcached.

It's multipurpose. It can be used for rate limit based on ip, user agent, among other parameters.

Default limits hits per second or microsecond per ip to prevent DOS (Denial Of Service) attack.


# Usage:

Just add anti_DOS.php file in the top of your main php file (like index.php) or resource file being abused and configure parameters $intervalo (check interval), $qtd_max (rate limit) and $tempo_bloqueio (block time) to adjust according to the desired limits.
eg:
```php
<?php
include_once('/home/yoursite/anti_DOS.php');
```


