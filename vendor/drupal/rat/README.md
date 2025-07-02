# RenderArrayTools (RAT)

See [Examples](rat.example.php).

# Don't mess with #access!

One of the reasons for this library is the `restrictAccess` method.
Altering the #access property of a render array is quite dangerous,
and the `restrictAccess` method does the heavy lifting to do it safely.

So what can happen. Take this example:
```php
$fieldRenderable['#access'] = $fieldIsTranslatable;
```

What can happen? If this code is not the only one messing with the render array,
and there are other components doing their work before this one, then a lot can happen.
And in Drupal, due to the complex nature of the build and render process, this is 
usually the case.

Another component may have set access to false, and this code overwrites it to true, and boom.

Even more tricky: Another component may have sett access to a AccessResult object
which contains cacheability. Which is overwritten, and boom.

Both cases open the door for information disclosure. Not good.

It turns out that restricting access and adding cacheability, while preserving 
cacheability from other components is not trivial. RenderArrayTool to the rescue!

```php
// Add access restriction and cacheability, while preserving any existing access
// restrictions, including cacheability.
\Drupal\rat\v1\RenderArray::alter($fieldRenderable)
  ->restrictAccess($fieldIsTranslatable, $fieldConfig);
```
