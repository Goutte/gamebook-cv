<?php

// Configuration ///////////////////////////////////////////////////////////////

define('GP_ROOT_PATH',  __DIR__ . '/../'); // :(|) oook?
define('GP_PAGES_PATH', GP_ROOT_PATH . 'pages/');
define('GP_PAGE_REGEX', '[a-zA-Z0-9_-]+'); // NEVER allow directory separators !

// Autoloading & Vendors ///////////////////////////////////////////////////////

$loader = require_once GP_ROOT_PATH . 'vendor/autoload.php';
//$loader->add('Goutte\Story', __DIR__.'/src'); // snippet

use Silex\Application;
use Symfony\Component\Finder\Finder;
use Michelf\Markdown;

// todo: Ideally, it should also be possible to freeze this into static files.

// Utils (some more monkey coding) /////////////////////////////////////////////

/**
 * @return bool Whether this script is run on local host or not.
 */
function is_localhost () {
    return (in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1','::1',)));
}

/**
 * Get the contents of the page in the file `pages/{$id}`, null when not found.
 *
 * @param  int $id
 * @return null|string
 */
function get_page ($id) {
    $finder = new Finder();
    $finder->files()->in(GP_PAGES_PATH)->name($id);
    $files = array_values(iterator_to_array($finder));
    if (count($files) != 1) return null;
    return file_get_contents($files[0]->getPathname());
}

/**
 * @param  string $id
 * @return bool   Whether the page described by $id exists or not.
 */
function is_page ($id) {
    return 1 == (new Finder())->files()->in(GP_PAGES_PATH)->name($id)->count();
}


// Engine : Silex App //////////////////////////////////////////////////////////

$app = new Application();
$app['debug'] = is_localhost() || false;


// Templating : Twig ///////////////////////////////////////////////////////////

$twig_loader = new Twig_Loader_Filesystem(array(
    GP_ROOT_PATH . 'view',
//    GP_ROOT_PATH . 'cache/pages',
));
$twig = new Twig_Environment($twig_loader, array(
    'cache' => GP_ROOT_PATH . 'cache',
));


// Route : Aliases /////////////////////////////////////////////////////////////

$app->get('/', function(Application $app) {
    return $app->redirect('page/1');
});


// Route : Show a Page in the Story ////////////////////////////////////////////

$app->get('/page/{id}', function (Application $app, $id) use ($twig) {

    // Grab the source file contents
    $source = get_page($id);
    if (null == $source) $app->abort(404, "Page {$id} does not exist.");

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

    // Transform the markdown
    $page = $markdownParser->transform($source);

    // Transform page links
    $page = preg_replace_callback('!page ('.GP_PAGE_REGEX.')!', function ($m) {
        if (is_page($m[1])) return '<a href="../page/'.$m[1].'">'.$m[0].'</a>';
        else                return $m[0];
    }, $page);

    // Transform dialogue links `(xxx)> blablabla`
    $page = preg_replace_callback(
        '!\s*\(('.GP_PAGE_REGEX.')\)\s*>\s*(.+?)(</p>)?$!m',
        function ($m) use ($id) {
            if (is_page($m[1])) {
                if ($m[1] != $id) {
                    return '&gt; <a class="talk" href="../page/'.$m[1].'">'.
                           $m[2].
                           '</a><br>'.
                           ((isset($m[3])) ? '</p>' : '');
                } else {
                    return '';
                }
            } else {
                return '&gt; <a class="talk todo" href="#">'.$m[2].'</a><br>'.
                       ((isset($m[3])) ? '</p>' : '');
            }
        },
        $page
    );

    return $twig->render('page.html.twig', array('page' => $page));

})->assert('id', GP_PAGE_REGEX);


// Finally, run the app ////////////////////////////////////////////////////////

$app->run();
