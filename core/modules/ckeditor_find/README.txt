CKEditor Find/Replace
--------------------------------------------------------------------------------

This plugin adds Find and Replace functionality for CKEditor in Drupal 8:

- Quickly search the text as well as replace words inside it.
- Common options available for matching:
  - case
  - whole word
  - cyclic
  
INSTALLATION
------------

Manual
------
1. Download the plugin from http://ckeditor.com/addon/find.
2. Place the 'find' plugin directory within the root libraries folder in a
   CKEditor plugin subdirectory (DRUPAL_ROOT/libraries/ckeditor/plugins).
3. Enable the CKEditor Find/replace module.
4. Configure your WYSIWYG toolbar to include the buttons.

Composer
--------
1. Add the following repository to you composer.json file:

{
  "repositories": [
    {
      "type": "package",
      "package": {
        "name": "ckeditor/find",
        "version": "4.8.0",
        "type": "drupal-library",
        "extra": {
          "installer-name": "find"
        },
        "dist": {
          "url": "https://download.ckeditor.com/find/releases/find_4.8.0.zip",
          "type": "zip"
        }
      }
    }
  ]
}

2. Add the following installer-path to your composer file:

{
  "extra": {
    "installer-paths": {
      "libraries/ckeditor/plugins/{$name}": ["ckeditor/find"]
    }
  }
}

3. Run `composer require drupal/ckeditor_find`
3. Enable the CKEditor Find/replace module.
4. Configure your WYSIWYG toolbar to include the buttons.