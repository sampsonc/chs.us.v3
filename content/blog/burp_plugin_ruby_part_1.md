---
title: "Writing a Burp Extension in Ruby Part 1"
date: 2020-11-18T14:05:18-05:00
draft: false
type: post
url: /blog/burp_plugin_ruby_part_1/
tags: ["burp", "ruby"]
---

### Writing a Burp Extension in Ruby
Burp extensions can be written in 3 languages - Java, Python, and Ruby.   Since Burp is a java app, in order to write extensions in Python you need Jython and in Ruby you need JRuby.  For this example, we'll use Ruby.

#### Step 1 - Downloading JRuby

The first step is to download JRuby from https://jruby.org/download.   

![JRuby Download](/images/jruby_download.png)

For this example we will be using the latest - [9.2.13.0 Complete.jar](https://repo1.maven.org/maven2/org/jruby/jruby-complete/9.2.13.0/jruby-complete-9.2.13.0.jar).

#### Step 2 - Configure Burp to use the JRuby library downloaded in Step 1

To configure Burp to use the new JRuby, go to Extender -> Options -> Ruby Environment and specify the path to the downloaded library.
![JRuby Location](/images/jruby_Location.png)

#### Step 3 - Write the Extension
For this example, I'll use a very simple Extension that does nothing but load and sets the name of the extension.  In subsequent posts, I'll build on this to build something useful.

```ruby
#demo_extension.rb
require 'java'

java_import 'burp.IBurpExtender'

class BurpExtender
  include IBurpExtender

  def registerExtenderCallbacks(callbacks)
    callbacks.setExtensionName("Demo Ruby Plugin")
  end
end
```

IBurpExtender is the interface that defines the methods that a plugin must implement for Burp to recognize it as an extension and load it.  The callbacks object has a number of methods, but for this example I'm just setting the extension name.

#### Step 4 - Load the Extension

To load the extension, go to the Extensions tab and click "Add".
![Add Extension](/images/extension_add.png)

Once the dialog opens, specify the extension type of ruby and navigate to the path of the extension.
![Add Extension](/images/extension_add_file.png)

When you click "Next", the extension is loaded.

#### Step 5 - View the Extension
Go to the "Extensions" tab, and select the new extension you just added and see the details.
![Extension Running](/images/extension_running.png)

That's it for Part 1!  Check back next week for the next in the series.
