---
title: 'BinPeek – an app to determine if a #Windows executable is managed or unmanaged.'
author: Carl Sampson
type: post
date: 2017-12-30T02:47:25+00:00
url: /blog/binpeek-an-app-to-determine-if-a-windows-executable-is-managed-or-unmanaged/

---
BinPeek is an application that checks to see if a Windows application is managed(.NET) or unmanaged(native). It handles x86 and x84 executables. If doing it manually, you must check several values in the PE (Portable Executable) file header that differ slightly based on whether the executable is 32-bit or 64-bit. BinPeek does that work for you.

**Usage**

<pre>D:\source\repos\BinPeek>binpeek BinPeek.exe
BinPeek.exe --> Unmanaged
</pre>

**Project Page on Github**

[![BinPeek](https://chs.us/images/binpeek-300x197.png)](https://www.github.com/sampsonc/BinPeek)

**Install**

Build with Visual Studio or just use the release version in the repo.

**License**

MIT
