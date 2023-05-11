# anti-DOS
simple, fast and lightweight PHP script to rate limit accesses. Run in memory (shmop) to improve performance

Deployment with shmop ran faster than with redis and memcached.

It's multipurpose. It can be used for rate limit based on ip, user agent, among other parameters.

Default limits hits per second or microsecond per ip to prevent DOS (Denial Of Service) attack.




