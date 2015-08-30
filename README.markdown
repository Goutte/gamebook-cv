
**This is a Work in Progress.**


# WHAT?

This is a Curriculum Vitæ where **YOU** are the hero.
It is easily forkable and editable to make your own.

It is written in [PHP] and is powered by [Silex], [Twig], and [Markdown].


# HOW TO

## USE

Just edit the pages in the `pages/` folder. You can use markdown syntax and simple html tags.
The "page XX" text will be replaced by links automagically.

- `web/index.php` contains most of the PHP glue.
- `view/` contains the html templates.

## INSTALL

Get composer, install, and make sure the `cache/` folder is writeable.

    curl -s https://getcomposer.org/installer | php
    php composer.phar install

    sudo setfacl -R -m u:www-data:rwx -m u:`whoami`:rwx cache
    sudo setfacl -dR -m u:www-data:rwx -m u:`whoami`:rwx cache


# LICENCE

Unless specified otherwise (in third-party vendor libraries), this is public domain.


[PHP]: https://www.php.net
[Silex]: http://silex.sensiolabs.org
[Twig]: http://twig.sensiolabs.org
[Markdown]: https://wikipedia.org/wiki/Markdown