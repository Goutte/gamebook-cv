<?php

define('GP_ROOT_PATH', __DIR__ . '/../'); // :(|) oook?

$loader = require_once GP_ROOT_PATH . 'vendor/autoload.php';
//$loader->add('Goutte\Story', __DIR__.'/src'); // snippet for future usage

use Silex\Application;
use Symfony\Component\Finder\Finder;
use Michelf\Markdown;

// Hmmmm. Ideally, this should be frozen into static files.

// Utils (some more monkey coding)

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
    $finder->files()->in(GP_ROOT_PATH . 'pages')->name($id);
    $files = array_values(iterator_to_array($finder));
    if (count($files) != 1) return null;
    return file_get_contents($files[0]->getPathname());
}


// Engine : Silex App

$app = new Application();
$app['debug'] = is_localhost();


// Templating : Twig

$twig_loader = new Twig_Loader_Filesystem(array(
    GP_ROOT_PATH . 'view',
//    GP_ROOT_PATH . 'cache/pages',
));
$twig = new Twig_Environment($twig_loader, array(
    'cache' => GP_ROOT_PATH . 'cache',
));


// Route : Aliases

$app->get('/', function(Application $app) {
    return $app->redirect('page/1');
});

$app->get('/porte/{id}', function (Application $app, $id) {
    return $app->redirect('../page/'.$id);
})->assert('id', '\d+');


// Route : Show a Page in the Story

$app->get('/page/{id}', function (Application $app, $id) use ($twig) {

    // Grab the source file contents
    $source = get_page($id);
    if (null == $source) $app->abort(404, "Page {$id} does not exist.");

    // Parse its markdown
    $markdownParser = new Markdown();
    $page = $markdownParser->transform($source);

    // Add page links
    $page = preg_replace('!page (\d+)!', '<a href="../page/$1">$0</a>', $page);

    // Add conversation links
    $page = preg_replace('!\s*\((\d+)\)\s*>\s*(.+)$!m', '&gt; <a class="talk" href="../page/$1">$2</a><br>', $page);

    return $twig->render('page.html.twig', array('page' => $page));

})->assert('id', '\d+');


// Finally, run the app

$app->run();
