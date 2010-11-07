Caching data, especially SQL queries is one of the most important aspects to writing fast php applications.

The problem I had was that the existing caching engines around tend to be very powerful and feature but at the expense of a fair bit of overhead.

So I wrote this short class. It's simply a very basic file cache. It can write cache files, retrieve them and delete them and that's all.