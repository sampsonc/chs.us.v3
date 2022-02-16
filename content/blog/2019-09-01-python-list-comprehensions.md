---
title: Python List Comprehensions
author: Carl Sampson
type: post
date: 2019-09-01T06:25:39+00:00
draft: false
url: /blog/python-list-comprehensions
tags: ["python"]

---
Python List Comprehensions is a pretty interesting feature that I haven&#8217;t seen in other languages (at least that I&#8217;ve played with). The basic idea is that they create lists from other iterables. They consists of brackets containing the expression which is executed against each item in the iterable object. One or more conditionals dictate if the item is added to the new list.

The basic format is &#8211;

list = [expression for\_loop\_one\_or\_more conditions]

Some examples-

```python
#Let's square all the numbers
numbers = [1,2,3,4,5,6,7,8,9,10]
squares = [n**2 for n in numbers]
#squares = [1, 4, 9, 16, 25, 36, 49, 64, 81, 100]
```

```python
#Let's square only the odds
numbers = [1,2,3,4,5,6,7,8,9,10]
squares = [n**2 for n in numbers if n % 2]
#squares = [1, 9, 25, 49, 81]
```
