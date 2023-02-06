<?php

// Configuration ///////////////////////////////////////////////////////////////

define('DS', DIRECTORY_SEPARATOR);
define('GP_ROOT_PATH',  __DIR__ . DS . '..' . DS); // :(|) oook?
define('GP_PAGES_PATH', GP_ROOT_PATH . 'pages' . DS);
define('GP_PAGE_REGEX', '[a-zA-Z0-9_-]+'); // NEVER allow directory separators !
define('GP_URL_REGEX', 'https?:\/\/(?:www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b(?:[-a-zA-Z0-9()@:%_\+.~#?&//=]*)');

$icons = [
    'python'     => ["Made with Python"],
    'php'        => ["Made with PHP"],
    'html5'      => ["Compatible HTML5"],
    'linux'      => ["Compatible Linux"],
    'windows'    => ["Compatible Windows"],
    'apple'      => ["Compatible Apple"],
    'opensource' => ["Open Source"],
];

// Autoloading & Vendors ///////////////////////////////////////////////////////

$loader = require_once GP_ROOT_PATH . 'vendor' . DS . 'autoload.php';
//$loader->add('Goutte\Story', __DIR__.'/src'); // snippet

use Silex\Application;
use Symfony\Component\Finder\Finder;
use Michelf\Markdown;
use Twig\TwigFunction;

// Ideally, it should also be possible to freeze this into static files.
// We **could**, but I like the epicene dynamism better.

// Utils (some more monkey coding) /////////////////////////////////////////////

/**
 * @return bool Whether this script is run on local host (during dev) or not.
 */
function is_localhost () {
    return (
        in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1','::1',))
        ||
        @$_SERVER['SERVER_NAME'] == 'localhost'
    );
}

/**
 * A factory for our page finder.
 * You need to apply the ->name() filter on it with the page id, to find a page.
 *
 * @return Finder
 */
function get_page_finder() {
    $finder = new Finder();
    $finder->files()->in(GP_PAGES_PATH)->depth('< 1');
    return $finder;
}

/**
 * Get the contents of the page in the file `pages/{$id}`, null when not found.
 *
 * @param  int $id
 * @return null|string
 */
function get_page ($id) {
    $finder = get_page_finder()->name($id);
    $files = array_values(iterator_to_array($finder));
    if (count($files) != 1) return null;
    return file_get_contents($files[0]->getPathname());
}

/**
 * @param  string $id
 * @return bool   Whether the page described by $id exists or not.
 */
function is_page ($id) {
    return 1 == get_page_finder()->name($id)->count();
}


// Visitor (Webnaut) ///////////////////////////////////////////////////////////

define('FEMALE', 0);
define('MALE', 1);
define('OTHER', 2);  // unused for now
$genre = rand(0, 1);  // use userland cookie and a form at some point ?


// Engine : Silex App //////////////////////////////////////////////////////////

$app = new Application();
$app['debug'] = is_localhost();


// Templating : Twig ///////////////////////////////////////////////////////////

$twig_loader = new Twig_Loader_Filesystem(array(
    GP_ROOT_PATH . 'view',
));
$twig_config = array();
if ( ! is_localhost()) {
    //$twig_config['cache'] = GP_ROOT_PATH . 'cache';
    // Having to handle write permissions is annoying, so we write to /tmp
    $twig_config['cache'] = "/tmp/gamebook-cv-cache";
}
$twig = new Twig_Environment($twig_loader, $twig_config);

$twig_epicene_function = new TwigFunction('epicene', function () use ($genre) {
    $args = func_get_args();
    $amount_of_args = count($args);

    if ($amount_of_args == 1 && is_array($args[0])) {
        $argc = count($args[0]);
        if ($argc == 0) {  // to switch() or not to switch() ?
            return '';
        }
        else if ($argc == 1) {
            return $args[0][0];
        }
        else if ($argc == 2) {
            if ($genre < 2) {
                return $args[0][$genre];
            }
        }
        else if ($argc == 3) {
            return $args[0][$genre];
        }

        return $args[0][rand(0, count($args[0]) - 1)];
    }

    return "Not implemented (yet).";
});
$twig->addFunction($twig_epicene_function);


// Route : Aliases /////////////////////////////////////////////////////////////

$app->get('/', function(Application $app) {
    return $app->redirect('page/1');
});


// Route : Show a Page in the Story ////////////////////////////////////////////

$app->get('/page/{id}', function (Application $app, $id) use ($twig, $genre, $icons) {

    // Grab the source file contents, or 404
    $source = get_page($id);
    if (null == $source) {
        $app->abort(404, "Page {$id} does not exist.");
    }

    // Handle page inclusions `{% include page xxx %}`
    $source = preg_replace_callback(
        '!\{%\s+include\s+page\s+('.GP_PAGE_REGEX.')\s+%\}!',
        function ($m) { return (is_page($m[1])) ? get_page($m[1]) : $m[1]; },
        $source
    );

    // Convert integers in URLs to internal page links
    $markdownParser = new Markdown();
    $markdownParser->url_filter_func = function ($url) {
        $m = array();
        if (preg_match('!^\s*('.GP_PAGE_REGEX.')\s*$!', $url, $m)) {
            $url = "../page/${m[1]}";
        }
        return $url;
    };

    // Apply Twig to the page source  #security-concern  (ok so long as pages are curated)
    $pageTwig = $twig->createTemplate($source, 'page-' . $id);
    $source = $pageTwig->render(array(
        'e' => ($genre == 0) ? 'e' : '',
    ));

    // Convert talk links to external pages
    $source = preg_replace_callback(
        '!^\((?P<url>'.GP_URL_REGEX.')\)\s*>\s*(?P<anchor>.+?)$!m',
        function ($m) use ($id) {
            return '&#11166; <a class="talk" href="'.$m['url'].'" target="_blank">'.
                $m['anchor'] .
                '</a><br>'
                ;
        },
        $source
    );

    // Convert [[someicon]] to icons (see $icons in config up top)
    foreach ($icons as $icon => $iconData) {
        $source = preg_replace_callback(
            '!\[\[(?P<icon>'.$icon.')]]!',
            function ($m) use ($icon, $iconData) {
                return '<i class="icon-'.$icon.'" title="'.$iconData[0].'" aria-label="'.$iconData[0].'"></i>';
            },
            $source
        );
    }

    // Transform the markdown
    $page = $markdownParser->transform($source);

    // Transform page links
    $page = preg_replace_callback('!page ('.GP_PAGE_REGEX.')!', function ($m) {
        if (is_page($m[1])) return '<a href="../page/'.$m[1].'">'.$m[0].'</a>';
        else                return $m[0];
    }, $page);

    // Transform dialogue links `(xxx)> blablabla`
    $page = preg_replace_callback(
        '!\s*\(('.GP_PAGE_REGEX.')(#[a-zA-z0-9_-]*|)\)\s*>\s*(.+?)(</p>)?$!m',
        function ($m) use ($id) {
            if (is_page($m[1])) {
                if ($m[1] != $id) {
                    return '&#11166; <a class="talk" href="../page/'.$m[1].$m[2].'">'.
                           $m[3].
                           '</a><br>'.
                           ((isset($m[4])) ? '</p>' : '');
                } else {
                    return '';
                }
            } else {
                return '&#11166; <a class="talk todo" href="#">'.$m[3].'</a><br>'.
                       ((isset($m[4])) ? '</p>' : '');
            }
        },
        $page
    );

    return $twig->render('page.html.twig', array(
        'page' => $page,
    ));

})->assert('id', GP_PAGE_REGEX);


// Finally, run the app ////////////////////////////////////////////////////////

$app->run();
