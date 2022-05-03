#/Library/Frameworks/Python.framework/Versions/3.8/bin/python3 /Users/chs/scripts/pocket-wp-template.py golang "Interesting Go Links" > /Users/chs/Documents/chs.us/content/go.html
hugo
hugo --minify
rsync -avz --delete public/ chs@chs.us:chs.us/
