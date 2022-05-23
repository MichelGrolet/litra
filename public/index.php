<?php
declare(strict_types=1);
session_start();
require_once './vendor/autoload.php';

use Slim\App;
use Slim\Container;
use Illuminate\Database\Capsule\Manager as DB;

use litra\controller\ControllerProfil;
use litra\controller\MainController;

$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
        'dbconf' => './config/conf.ini']
];
$c = new Container($configuration);
$app = new App($c);

$db = new DB();
$db->addConnection(parse_ini_file('./config/conf.ini'));
$db->setAsGlobal();
$db->bootEloquent();

////////// ROUTES //////////
$app->get('/', MainController::class . ':home')->setName('home');

// Connexion
$app->get('/connexion', MainController::class . ':login')->setName('login');
$app->post('/connexion', MainController::class . ':login')->setName('loginForm');

// Inscription
$app->get('/inscription', MainController::class . ':register')->setName('register');
$app->post('/inscription', MainController::class . ':register')->setName('registerForm');

// Déconnexion
$app->get('/deconnexion', MainController::class . ':logout')->setName('logout');

// Profil
$app->get('/profil/editer_profil', ControllerProfil::class . ':editer')->setName('editer_profil');
$app->post('/profil/editer_profil', ControllerProfil::class . ':editer')->setName('editer_profil_post');
$app->get('/profil/QRcode', ControllerProfil::class . ':afficherQR')->setName('profil_QRcode');
$app->get('/profil/MesTokens', ControllerProfil::class . ':mesCreaTokens')->setName('mes_tokens');
$app->get('/profil/MesEvenements', ControllerProfil::class . ':mesCreaEvenements')->setName('mes_evenements');
$app->get('/profil/Securite', ControllerProfil::class . ':afficherSecu')->setName('profil_securite');
$app->post('/profil/Securite', ControllerProfil::class . ':afficherSecu')->setName('profil_securite');
$app->get('/profil/recharger', ControllerProfil::class . ':recharger')->setName('profil_recharger');
$app->post('/profil/recharger', ControllerProfil::class . ':recharger')->setName('profil_recharger');

// changement de privilèges
$app->get('/profil/changer_privilege', ControllerProfil::class . ':changerPrivilege')->setName('changer_privilege');
$app->post('/profil/changer_privilege', ControllerProfil::class . ':changerPrivilege')->setName('changer_privilege');

// Evenements
$app->get('/evenement/CreationEvenement', MainController::class . ':creerEvenement')->setName('creer_evenement');
$app->post('/evenement/CreationEvenement', MainController::class . ':creerEvenement')->setName('creer_evenement_form');

// Monnaies
$app->get('/monnaie/CreationMonnaie', MainController::class . ':creerMonnaie')->setName('creer_monnaie');
$app->post('/monnaie/CreationMonnaie', MainController::class . ':creerMonnaie')->setName('creer_monnaie_form');

// Wallet
$app->get('/wallet', MainController::class . ':afficherWallet')->setName('wallet');

// Scan
$app->get('/scanner_QR', MainController::class . ':scannerQR')->setName('scanner_QRcode');

// Paiement
$app->get('/paiement', MainController::class . ':qrPaiement')->setName('paiement');
$app->post('/paiement', MainController::class . ':qrPaiement')->setName('paiement');

$app->get('/qr_paiement', MainController::class . ':qrPaiement')->setName('qr_paiement');

$app->post('/lecture_qr', MainController::class . ':lecture_qr')->setName('lecture_qr');

$app->get('/scanrfid', MainController::class . ':validationRfid')->setName('validationRfid');

$app->post('/scanrfid', MainController::class . ':validationRfid')->setName('validationRfid');

$app->post('/paiementRfid', MainController::class . ':paiementRfid')->setName('paiementRfid');

$app->get('/toggleTheme', MainController::class . ':toggleTheme')->setName('toggleTheme');

//je ne sais pas trop 
$app->post('/abc', MainController::class . ':formulaireVendeur')->setName('abc');

$app->get( '/rfid/{tag}',
    function ($rq, $rs, $args) {
        $idVendeurClient = explode("-", $args['tag']);
        $log = new \litra\model\Logrfid();
        $log->carte_rfid = $idVendeurClient[0];
        $log->id_vendeur = $idVendeurClient[1];
        $log->save();
    }
)->setName('tag');

$app->get('/profil/ajoutCarteRFID', ControllerProfil::class . ':ajoutCarteRFID')->setName('ajoutCarteRFID');

$app->post('/profil/ajoutCarteRFID', ControllerProfil::class . ':ajoutCarteRFID')->setName('ajoutCarteRFID_form');
// Liste des évenements
$app->get('/listeEvenements', MainController::class . ':afficherListeEvenements')->setName('liste_evenements');
$app->post('/listeEvenements', MainController::class . ':afficherListeEvenements')->setName('liste_evenements');
// Liste des monnaies
$app->get('/listeMonnaies', MainController::class . ':afficherListeMonnaies')->setName('liste_monnaies');
$app->post('/listeMonnaies', MainController::class . ':afficherListeMonnaies')->setName('liste_monnaies');

// Détails événement
$app->get('/detailsEvenement/{id_evenement}', 
function ($rq, $rs, $args) {
    $c = new \litra\controller\MainController($this);
    return $c->afficherDetailsEvenement($rq, $rs, $args);
})->setName('details_evenement');
// Détails monnaie
$app->get('/detailsMonnaie/{id_monnaie}', 
function ($rq, $rs, $args) {
    $c = new \litra\controller\MainController($this);
    return $c->afficherDetailsMonnaie($rq, $rs, $args);
})->setName('details_monnaie');


try {
    $app->run();
} catch (Throwable $e) {
    echo 'erreur d\'index';
}
