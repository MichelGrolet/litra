<?php

namespace litra\views;

use DateTime;
use litra\model\Compte;
use Slim\Container;

/**
 * Vue retournant les éléments communs à toutes les autres vues :
 * - Head : balise <head>
 * - Header : en-tête des pages
 */
class ViewElements {
	public array $tab;
	public Container $container;
	public string $tomorrow;

	public function __construct(Container $container, array $tab = []) {
		$this->tab = $tab;
		$this->container = $container;
		$this->tomorrow = (new DateTime('tomorrow'))->format('d-m-Y');
	}

	/**
	 * Retourne le code HTML de la balise <head> des pages
	 * @param String $name nom de la page
	 * @return string
	 */
	public function renderHead(String $name): string {
		$css_url = $this->container->router->pathFor('home');
		$css_url .= 'resources/css/';
		$home_url = $this->container->router->pathFor('home');
		$light = "";
		if (isset($_COOKIE['lighttheme'])) {
			return <<<HTML
			<head>
				<meta charset="utf-8">
                <link rel="stylesheet" href="{$css_url}styles.css">
                <link rel="stylesheet" href="{$css_url}header.css">
                <link rel="stylesheet" href="{$css_url}ProfilUser.css">
				<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">     
				<link rel="stylesheet" href="${css_url}lightTheme.css">
                <title>$name</title>
                <link rel="icon" type="image/x-icon" href="{$home_url}/resources/img/icon.ico">
            </head>
HTML;
		} else {

			$light = `<link rel="stylesheet" href="${css_url}lightTheme.css">`;
		return <<<HTML
			<head>
				<meta charset="utf-8">
                <link rel="stylesheet" href="{$css_url}styles.css">
                <link rel="stylesheet" href="{$css_url}header.css">
                <link rel="stylesheet" href="{$css_url}ProfilUser.css">
				<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">     
				$light
                <title>$name</title>
            </head>
HTML;
		}
	}

	/**
	 * Retourne le code HTML de l'en-tête des pages
	 * @return string
	 */
	public function renderHeader(): string {
		$router = $this->container->router;
		$home_url = $router->pathFor('home');

		// Liens non connecté
		$login_url = $router->pathFor('login');
		$register_url = $router->pathFor('register');
		
		// Liens utilisateur connecté
		$profil_qr_url = $router->pathFor('profil_QRcode');
		$scanner_url = $router->pathFor('scanner_QRcode');
		$payer_url = $router->pathFor('paiement');
		$wallet_url = $router->pathFor('wallet');

		if (isset($_SESSION['id_compte'])) {
			$user = Compte::where('id_compte', $_SESSION['id_compte'])->first();
			$url_pp = $router->pathFor('home')."/resources/img/pps/".$user->url_p;
			if ($user->url_p == null /*|| !file_exists($_SERVER['DOCUMENT_ROOT'].$url_pp)*/) $pic = <<<HTML
				<i class="material-icons account-icon">account_circle</i>
HTML;
			else $pic = <<<HTML
				<div id="pic-container"><img id="pic" src=$url_pp alt="Votre image de profil"/></div>
HTML;
			$home_url = $router->pathFor('home');
			$liens = <<<HTML
				<li><a href=$wallet_url><i class="material-icons">wallet</i><span class="text-menu">Portefeuille</span></a></li>
				<li><a href=$scanner_url><i class="material-icons">qr_code_scanner</i><span class="text-menu">Scanner</span></a></li>
				<li><a href=$payer_url><i class="material-icons">attach_money</i><span class="text-menu">Paiement</span></a></li>
				<li><a id="profil-a" href=$profil_qr_url>$pic</a></li>
HTML;
		} else {
			$liens = <<<HTML
				<li><a class="not-conn-a" href='$register_url'>S'inscrire</a></li>
				<li><a class="not-conn-a" href='$login_url'>Se connecter</a></li>
			HTML;
		}
		$toggleTheme = $router->pathFor('toggleTheme');
		if (isset($_COOKIE['lighttheme']))
			$theme = "dark_mode";
		else
			$theme = "light_mode";

		return <<<HTML
			<nav>
				<h1><a href='$home_url'>Litra</a><a href=$toggleTheme><i title="cliquez-moi !" class="material-icons">{$theme}</i></a></h1>
				<ul>
					$liens					
				</ul>
			</nav>
		HTML;
	}

	/**
	 * Retourne le privilege associé à la chaine en paramètre
	 * @param string $text privilege en chaine de caractère
	 * @return int privilege associé au compte en int
	 */
	public static function privFromString(string $text): int {
		return match ($text) {
			"user"|"utilisateur" => 0,
			"vendeur" => 1,
			"admin"|"administrateur" => 2,
			"organisateur" => 3,
			default => null
		};
	}

	/**
	 * Retourne le privilege associé à l'entier en paramètre
	 */
	public static function privFromInt($no): string {
		return match ($no) {
			0 => "Utilisateur",
			1 => "Vendeur",
			2 => "Administrateur",
			3 => "Organisateur",
			default => "Rôle non géré"
		};
	}
}
