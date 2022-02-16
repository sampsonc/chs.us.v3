---
title: New Burp Extension – Perfmon
author: Carl Sampson
type: post
date: 2018-05-23T04:23:30+00:00
url: /blog/new-burp-extension-perfmon/
tags: ["burp", "security"]


---
Just whipped together a new [Burp](https://portswigger.net/burp) extension called [perfmon](https://github.com/sampsonc/Perfmon) (not to be confused with the Windows tool of the same name). I was really interested in the the resource usage of Burp while doing certain activities.

It adds a new tab to Burp and samples every 5 seconds-

* Current and max number of threads in use

* Current and max memory used

* Current and max memory allocated

* Ticker to set how often the stats update. 1 – 5 seconds.

![PerfMon](https://chs.us/images/perfmon1-300x120.jpg)

I plan to add a few more things and clean up the UI a bit, but it was an interesting exercise.

The source and plugin are on [Github](https://github.com/sampsonc/Perfmon).
