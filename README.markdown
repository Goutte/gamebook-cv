
> **Note**: Silex has been deprecated.  This still works, though.

# WHAT?

This is a Curriculum Vitæ where **YOU** are the hero, in the form of a website.
It should be easily forkable and editable to make your own.

The HTML generator is written in [PHP] and is powered by [Silex] and [Twig].


# HOW TO


## USE

### Files Overview

- `pages/` contains the source files for each page.
- `view/` contains the html templates.
- `web/index.php` contains most of the PHP glue.

### Features Breakdown

The source files in `pages/` support the following features :

- [Markdown]
- Simple html tags
- Translating `page 1` into a link to page `1`
- Translating `(42)> What was the question again?` into a dialogue link to page `42`
- Translating `[go to hell](hell)` into a link to page `hell`

The pages names **must** be alphanumerical, ie. validate `[a-zA-Z0-9_-]+`.

### Test the website locally

To run the website locally, simply go into the `web/` directory, and launch PHP's server :

    cd web
    php -S localhost:3000

Then, you can browse [http://localhost:3000](http://localhost:3000).


## INSTALL

You'll need `php >= 5.4`.

The setup should be pretty straightforward if you already have [Composer] :

    composer install

Otherwise, here's how to get [Composer] and install :

    curl -s https://getcomposer.org/installer | php
    php composer.phar install

If it complains that the `cache/` folder is not writeable, you can set it up :

    sudo setfacl  -R -m u:www-data:rwx -m u:`whoami`:rwx cache
    sudo setfacl -dR -m u:www-data:rwx -m u:`whoami`:rwx cache

# LICENCE

Unless specified otherwise (in third-party vendor libraries), this is public domain.


[PHP]: https://www.php.net
[Silex]: http://silex.sensiolabs.org
[Twig]: http://twig.sensiolabs.org
[Markdown]: https://wikipedia.org/wiki/Markdown
[Composer]: https://getcomposer.org