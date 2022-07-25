<?php

namespace litra\views;

use DateTime;
use litra\model\Monnaie;
use Slim\Container;

class ViewCrea {
	public array $tab;
	public Container $container;
	public string $tomorrow;

	public function __construct(array $tab, Container $container) {
		$this->tab = $tab;
		$this->container = $container;
		$this->tomorrow = (new DateTime('tomorrow'))->format('d-m-Y');
	}

	public function render($selector): string {
		$content = "";
		switch ($selector) {
			case 0:
			{
				$content = $this->creaEvenement();
				break;
			}
			case 1:
			{
				$content = $this->creaMonnaie();
				break;
			}
		}

        $viewElements = new ViewElements($this->container);
        $header = $viewElements->renderHeader();
        $head = $viewElements->renderHead("Litra");

		return <<<HTML
            <!DOCTYPE html>
            <html lang="fr">
                $head
                <body>
                     $header  
                    <div class="content">
                        $content
                    </div>

                   <!-- <footer><p>Litra Team® - 2022 - IUT Nancy Charlemagne</p></footer>-->
                </body>
            </html>
HTML;
	}

	private function creaEvenement(): string {

		$url_creer_monnaie = $this->container->router->pathFor('creer_monnaie');

		$monnaies = Monnaie::where("id_createur", "=", $_SESSION['id_compte'])->get()->toArray();

		$comboMonnaies = "";

		foreach ($monnaies as $monnaie) {
			$comboMonnaies .= "<option value='$monnaie[id_monnaie]'>$monnaie[nom_monnaie]</option>\n";
		}

		return <<<HTML
            <section>
                <h2>Créer un évènement</h2>
                <form id='editionProfil' method='post' enctype='multipart/form-data'>
	                <div class='edition_profil'>
	                    <label for='nomevent'>Nom de l'évènement :</label>
	                    <input type='text' class='styleinput' id='nomevent' name='nomevent' required>
	                </div>
	                <div class='edition_profil'>
	                    <label for='descrievent'>Description de l'évènement :</label>
	                    <textarea id='descrievent' name='descrievent' placeholder='Description' maxlength=255 cols=50 rows=8></textarea><br>
	                </div>
	                <div class='edition_profil'>
	                    <label for='dateevent'>Date de l'évènement :</label>
	                    <input type='date' class='styleinput' id='dateevent' name ='dateevent' placeholder='dateevent' value='$this->tomorrow' min='$this->tomorrow' required/>
	                </div>
	                <div class='edition_profil'>
	                    <label for='lieuevent'>Lieu de l'évènement :</label>
	                    <input type='text' class='styleinput' id='lieuevent' name='lieuevent' required>
	                </div>
	                <div class='edition_profil'>
	             
	                    <label for='listmonnaies'>Monnaie de l'évènement :</label>
	                    <select id='listmonnaies' name='comboMonnaie' class='styleinput' onchange='location = this.value;' required>
	                        $comboMonnaies
	                        
	                    </select>
	                    <br>
	                    <a href='$url_creer_monnaie' ><input type="button" value='créer une nouvelle monnaie' href="$url_creer_monnaie"></input></a>
	                    
	                </div>
	                <div class='edition_profil'>
	                    <label for='imevent'>Ajouter une image pour l'évènement :</label>
	                    <input type='file' class='styleinput' id='imevent' name='imevent' accept='image/*'>
	                    
	                </div><br>
	
	                <input id='publier' type='submit' name='publierevent' value="Créer l'événement">
                </form>
            </section>
HTML;
	}

	private function creaMonnaie(): string {
		return <<<HTML
		<section id='box'>
            <h2>Créer une nouvelle monnaie</h2>
            <p id='contenu_profil'>
            <form id='editionProfil' method='post'>
                <div class='edition_profil'>
                    <label for='nomtoken'>Nom du token :</label>
                    <input type='text' class='styleinput' id='nomtoken' name='nomtoken' required>
                </div>
                <div class='edition_profil'>
                    <label for='couttoken'>Coût du token :</label>
                    <input type='number' class='styleinput' min='0.05' step='0.01' id='couttoken' name='couttoken' required>
                    <span>€</span>
                </div>
                <div class='edition_profil'>
                    <label for='expiration'>Date d'expiration :</label>
                    <input type='date' class='styleinput' id='expiration' name ='expiration' placeholder='expiration' value='$this->tomorrow' min='$this->tomorrow' required/>
                </div>
                <div class='edition_profil'>
                    <label for='convert'>Reconvertibilité du token :</label>
                    <input type='checkbox' class='checkconvert' id='convert' name='convertOui'>
                </div><br>
                <input id='valider' type='submit' name='cremon' value='Créer le Token'>
            </form>
            </p>
        </section>
HTML;
	}

	private function header(): string {
		$home_url = $this->container->router->pathFor('home');
		$login_url = $this->container->router->pathFor('login');
		$register_url = $this->container->router->pathFor('register');
		$qr_code_url = $this->container->router->pathFor('profil_QRcode');
		$logout_url = $this->container->router->pathFor('logout');
		$liens = "
			<li><a href='$register_url'>S'inscrire</a></li>
			<li><a href='$login_url'>Se connecter</a></li>
		";
		if (isset($_SESSION['id_compte'])) {
			$liens = "
			<li><a href='$qr_code_url'>Profil</a></li>
			<li><a href='$logout_url'>Se déconnecter</a></li>
		";
		}

		return "
			<nav>
				<h1><a href='$home_url'>LITRA</a></h1>
				<ul>
					$liens
				</ul>
			</nav>
		";
	}
}
