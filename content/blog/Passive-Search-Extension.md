---
title: "Passive Search Burp Extension"
date: 2020-02-25T15:06:39-05:00
type: post
draft: false
---

Introducing my latest Burp extension - "Passive Search".   Passive Search searches for a list of terms in HTTP responses and creates an issue if it finds one.   

I got the idea of after maintaining a list of things that I was checking for in responses and using either the search in HTTP History on an item by item basis or using the global Search.  It was a completely manual process.

The way it works is by creating a tab called "Passive Search" that looks like this-

![Passive Search](/images/passivesearch.png)

You can add multiple search terms to look for and specify whether to look in the HTTP headers, body, or both.  You can add as many terms as you like and they persist when you close down Burp.

After adding the terms, when any of those terms are found in a response, an issue will be added to the site with details.

The overview of the issue looks like this-

![image](/images/passivesearch1.png)


And when you click on the details of the issue and click on the response you see this-
![image](/images/passivesearch2.png)

Check out the extension on [GitHub](https://github.com/sampsonc/PassiveSearch).
