---
title: Writing a Burp Extension – Part One
author: Carl Sampson
type: post
date: 2018-04-11T04:29:25+00:00
url: /blog/writing-a-burp-extension-part-one/
tags: ["burp"]


---
This is the first part in a series that I plan to write on how to create [Burp](https://portswigger.net/burp) extensions. I became interested in writing Burp extensions at a previous company where we were fortunate enough to be given time to do research presentations and then present them to our peers. My first presentation topic was to write an Active Scanning extension in Burp that would look for XXE (XML External Entity Injection). I also implemented a standalone app that could run on a host (external from the scan target) and listen for the XXE payload calling out (by making an HTTP request, for example). Literally that same week [Collaborator](https://portswigger.net/burp/help/collaborator) came out and stole my thunder. ? . Last Fall, I was also accepted to [DerbyCon](https://www.derbycon.com) and presented a stable talk on [Extending Burp](https://chs.us/extendingburptalk/).

This series will start with the very basics of creating a working an extension and then continue building out a full-featured extension as we go along. Burp extensions can be written in Java, Ruby (using [JRuby](http://jruby.org)), and Python (using [Jython](http://www.jython.org)). For this series we will write in Java. Here we go…

**Extension Basics**

In the very simplest case, a Burp extension is a jar file with a class called BurpExtender in a package called Burp. This is the file that Burp looks for when loading an Extension. Based on what you implement in that class is the functionality that the extension will have.

**Steps to create a Burp extension**

1. In your favorite Java editor, create a project of type “Class Library” or whatever the equivalent project type where the output is a jar file containing the classes from the project

2. Open Burp and go the Extender tab. Click on “Save Interface Files” and save them in a folder called “burp” in your file system where the source is for the project you created in Step 1

3. Create a class called BurpExtender in the burp package in your project and implement IBurpExender. This will require that you implement the registerExtenderCallbacks method. This is the method that you will do any kind of setup for your extension and setup anything that your extension needs

4. Compile the extension into a jar file and load it into Burp by going to Extender -> Extensions -> Add. That will prompt you to specify the type of extension (Java in this example) and the path to the jar file. Click Next and your extension is loaded!

**Sample Code**

<pre lang="java" line="1">package burp;
package burp;
import java.io.IOException;
import java.io.OutputStream;
import java.util.logging.Level;
import java.util.logging.Logger;

/**
 *
 * @author chs
 */
public class BurpExtender implements IBurpExtender
{
    private IExtensionHelpers helpers;
    private static final String EXTENSION_NAME = "Sample Burp Extension";
    private OutputStream os;

    @Override
    public void registerExtenderCallbacks(IBurpExtenderCallbacks callbacks)
    {
        this.helpers = callbacks.getHelpers();
        callbacks.setExtensionName(EXTENSION_NAME);
        os = callbacks.getStdout();
        try
        {
            os.write("Hello, World".getBytes());
        } catch (IOException ex)
        {
            Logger.getLogger(BurpExtender.class.getName()).log(Level.SEVERE, null, ex);
        }
    }

}
</pre>

The interesting lines are 14, 15, and 16. In line 14, getHelpers() returns an object that implements IExtensionHelpers. IExtensionHelpers is an interface that provides methods that are useful by the extension, like encoding/parsing requests/responses, etc. Line 15 sets the name of the extension. This is displayed in the Extender tab. Line 16 saves a reference to the OutputStream for the extension. When you write to it, it shows up in the output window of the extension in the Extender tab.

Once you load the extension and navigate to Extender->Extensions, you will see- ![Extender->Extensions Details][1]

It shows the name of the extension, it’s location in the filesystem, and methods from the interfaces it implements.

If you click on the Output tab, you will see-

![Extender->Extensions->Output Details][2]

This shows the output from registerExtenderCallbacks.

That’s it for this time.  Next post will dive deeper…

 [1]: https://chs.us/images/burpextension1.png
 [2]: https://chs.us/images/burpextension2.png
