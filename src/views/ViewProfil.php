<?php

namespace litra\views;

use DateTime;
use litra\model\Compte;
use litra\model\Monnaie;
use Slim\Container;

class ViewProfil
{
    public array $tab;
    public Container $container;

    public function __construct(array $tab, Container $container)
    {
        $this->tab = $tab;
        $this->container = $container;
    }

    public function render($selector): string
    {
        $content = match ($selector) {
            'QRCode' => $this->QRCode(),
            1 => $this->editer(),
            4 => $this->securite(),
            5 => $this->mesTokens(),
            6 => $this->mesEvenements(),
            'changerPrivilege' => $this->changerPrivilege(),
            'recharger' => $this->recharger(),
            'ajoutCarteRFID' => $this->ajoutCarteRFID(),
            default => "selector inconnu de render",
        };
        $router = $this->container->router;
        $url_QRcode = $router->pathFor('profil_QRcode');
        $url_Edition = $router->pathFor('editer_profil');
        $url_AjoutCarte = $router->pathFor('ajoutCarteRFID');
        $url_Recharger = $router->pathFor('profil_recharger');
        $url_mesTokens = $router->pathFor('mes_tokens');
        $url_mesEvenements = $router->pathFor('mes_evenements');
        $url_securite = $router->pathFor('profil_securite');
        $url_deconnexion = $router->pathFor('logout');

        // Accès compte actuel
        $compte = Compte::where('id_compte', $_SESSION['id_compte'])->first();
        $home = $router->pathFor('home');

        $url_pp = $home."resources/img/pps/".$compte->url_p;
        if ($compte->url_p == null /*|| !file_exists($_SERVER['DOCUMENT_ROOT'].$url_pp)*/) {
            $p_url = $home."resources/img/DefaultProfilPicture.png";
        } else {
            $p_url = $url_pp;
        }

        $p = $this->tab[0];
        $numTel = $p->num_tel;
        if ($numTel === null) {
            $numTel = "Manquant";
        }

        $viewElements = new ViewElements($this->container);
        $header = $viewElements->renderHeader();
        $head = $viewElements->renderHead("Profil");
        $role = ViewElements::privFromInt($p->privilege);

        $organisateur = "";
        if ($p->privilege == 0) {
            $url_changer_privilege = $router->pathFor('changer_privilege');
            $organisateur = <<<HTML
				<button class="bouton" onclick="location.href='$url_changer_privilege'"><i class="material-icons">manage_accounts</i>Changer droits</button>
HTML;
        }

        $pagespe = "";
        if ($p->privilege == 2 || $p->privilege == 3) {
            $pagespe = <<<HTML
				<button class="bouton" onclick="location.href='$url_mesEvenements'"><i class="material-icons">date_range</i>Mes événements</button>
				<button class="bouton" onclick="location.href='$url_mesTokens'"><i class="material-icons">token</i>Mes tokens</button>
HTML;
        }

        return <<<HTML
			<!DOCTYPE html>
			<html lang="fr">
				$head
				<body id="profil-body">
					$header	
					<div id="content">
						<div id="infos">
							<div id="profil-pp-container"><img src="$p_url" alt="Votre image de profil"/></div>
							<br>
							<p id="info-nom">$p->prenom <strong>$p->nom</strong></p>
							<p class="info-role"><strong>$role</strong></p>
							<p class="info-id"><i class="material-icons">perm_identity</i>Identifiant unique : <strong>$p->id_compte</strong></p>
							<p class="info-id"><i class="material-icons">phone</i>Téléphone : <a href="tel:$numTel">$numTel</a></p>
							<p class="info-id"><i class="material-icons">email</i>Adresse email : <a href="mailto:$p->adr_mail">$p->adr_mail</a></p>
						</div>
					
						<section id="box">
							<div class="content">
								$content
							</div>
						</section>
					
						<a id="menu" href=#><i class="material-icons">menu</i></a>
						<script src="${home}resources/js/menu.js"></script>
						<nav id="nav_profil">
							<button class="bouton" onclick="location.href='$url_QRcode'"><i class="material-icons">qr_code</i>QR Code</button>
							$organisateur
							<button class="bouton" onclick="location.href='$url_Recharger'"><i class="material-icons">credit_card</i>Recharger</button>
							<button class="bouton" onclick="location.href='$url_AjoutCarte'"><i class="material-icons">add_card</i>Ajouter une carte</button>
							$pagespe
							<button class="bouton" onclick="location.href='$url_securite'"><i class="material-icons">gpp_good</i>Sécurité</button>
							<button class="bouton" onclick="location.href='$url_Edition'"><i class="material-icons">edit</i>Éditer profil</button>
							<button class="bouton" onclick="location.href='$url_deconnexion'"><i class="material-icons">logout</i>Déconnexion</button>
						</nav>
					</div>
					<footer><p>Litra Team® - 2022 - IUT Nancy Charlemagne</p></footer>
				</body>
			</html>
HTML;
    }

    private function QRCode(): string
    {
        $id = $this->tab[0]->id_compte;
        return <<<HTML
			<h2>QR code</h2>
			<p>Ce QR code contient des informations personnelles. Ne le partagez pas avec n'importe qui.</p>
			<div id='QRdiv'>
				<img id='QRcode'
				src="https://api.qrserver.com/v1/create-qr-code/?data=litraid=${id}&amp;size=300x300"
				alt="Le QR code n'a pas pu être généré. Vérifiez votre connexion à internet ou reessayez ultérieurement."
				title='Votre QR code unique' width='300' height='300'/>
			</div>
HTML;
    }

    private function editer(): string
    {
        return <<<HTML
			<h2>Editer Profil</h2>
			<p>Entrez les informations que vous souhaitez modifier.</p>
			<p id='contenu_profil'>
				<form id='editionProfil' method='post' enctype='multipart/form-data'>
					<div class='edition_profil'>
						<label for='avatar'><i class="material-icons">account_circle</i>Nouvelle image de profil:</label>
						<input type='file' class='styleinput' id='pp' name='pp' accept='image/png, image/jpeg'>
					</div>
					<div class='edition_profil'>
						<label for='tel'><i class="material-icons">phone</i> Nouveau numéro :</label>
						<input type='tel' class='styleinput' id='tel' name='user_tel' autocomplete="tel" placeholder="0610101010">
					</div>
					<div class='edition_profil'>
						<label for='mdp'><i class="material-icons">key</i>Entrez votre mot de passe * :</label>
						<input required type='password' class='styleinput' id='mdp' name='password'>
						<input id='valider' type='submit' name='submit' value='Enregistrer les informations'>
					</div>
				</form>
			</p>
HTML;
    }

    private function securite(): string
    {
        $erreur = "";
        if ($this->tab[1]!=null)
            $erreur = $this->tab[1];
        $action = $this->container->router->pathFor('profil_securite');
        return <<<HTML
		<h2>Sécurité</h2>
		<p id='contenu_profil'>
			<form id='editionProfil' method='post'>
				<div class='edition_profil'>
					<p>Remplissez ce formulaire pour changer de mot de passe.</p>
					$erreur
					<div class='edition_profil'>
						<label for='oldmdp'>Ancien mot de passe :</label>
						<input type='password' class='styleinput' id='oldmdp' name='user_old_mdp'>
					</div>
					<div class='edition_profil'>
						<label for='newmdp'>Nouveau mot de passe :</label>
						<input type='password' class='styleinput' id='newmdp' name='user_new_mdp'>
					</div>
					<div class='edition_profil'>
						<label for='confmdp'>Confirmer le nouveau mot de passe :</label>
						<input type='password' class='styleinput' id='confmdp' name='conf_user_new_mdp'>
					</div>
					<input id='valider' type='submit' name='submit' value='Valider'>
				</div>
			</form>
		</p>
HTML;
    }

    private function mesTokens(): string
    {
        $url_creaMonnaie = $this->container->router->pathFor('creer_monnaie');
        $content = <<<HTML
			<h2>Token créés</h2>
			<button class='crea-event' onclick="location.href='$url_creaMonnaie'">Créer une monnaie</button>
			<div id='mesTokenDiv'>
HTML;
        $monnaiesCrees = $this->tab[1];
        if (sizeof($monnaiesCrees) == 0) {
            $content .= "<p class='erreur'>Vous n'avez créé aucune monnaie</p>";
        } else {
            foreach ($monnaiesCrees as $m) {
                $colorStyle = "mesTokenBlockNormal";
                $expiration = "Pas de date d'expiration";
                if ("$m[date_expiration]" != null) {
                    $joursRestants = date_diff(new DateTime("$m[date_expiration]"), new DateTime())->format('%a');
                    if (new DateTime("$m[date_expiration]") <= new DateTime()) {
                        $colorStyle = "mesTokenBlockExpire";
                        if ($joursRestants == 0) {
                            $expiration = "Expiré depuis aujourd'hui";
                        } else {
                            $expiration = "Expiré depuis $joursRestants jours";
                        }
                    } else {
                        if ($joursRestants == 0) {
                            $expiration = "Expire aujourd'hui à 23h59";
                        } else {
                            $expiration = "Expire dans $joursRestants jours";
                        }
                    }
                }

                $content .= <<<HTML
					<div class='$colorStyle'>
						<a href='#' id='mesTokenBlockLink'>
							<div class='mesTokenBlockPhoto'>photo</div>
							<div class='mesTokenBlockName'>$m[nom_monnaie]</div>
							<hr>
							<div class='mesTokenBlockExp'>$expiration</div>
						</a>
					</div>
HTML;
            }
        }
        $content .= "</div>";
        return $content;
    }

    private function mesEvenements(): string
    {
        $url_creaEvenement = $this->container->router->pathFor('creer_evenement');
        $content = <<<HTML
			<h2>Vos événements créés</h2>
			<button class='crea-event' onclick="location.href='$url_creaEvenement'">Créer un événement</button>
			<div id='mesEvenementsDiv'>
HTML;
        $evenementsCrees = $this->tab[1];
        if (sizeof($evenementsCrees) == 0) {
            $content .= "<p class='erreur'>Vous n'avez créé aucun événement</p>";
        } else {
            foreach ($evenementsCrees as $e) {
                $colorStyle = "mesEvenementsBlockNormal";
                $expiration = "Date non renseignée";
                if ("$e[date]" != null) {
                    $joursRestants = date_diff(new DateTime("$e[date]"), new DateTime())->format('%a');
                    if (new DateTime("$e[date]") <= new DateTime()) {
                        $colorStyle = "mesEvenementsBlockExpire";
                        if ($joursRestants == 0) {
                            $expiration = "A pris fin aujourd'hui";
                        } else {
                            $expiration = "A pris fin depuis $joursRestants jours";
                        }
                    } else {
                        if ($joursRestants == 0) {
                            $expiration = "Prendra fin aujourd'hui à 23h59";
                        } else {
                            $expiration = "Prendra fin dans $joursRestants jours";
                        }
                    }
                }
				$home = $this->container->router->pathFor('home');
	            if ($e->img == "") {
		            $path = $home . "resources/img/evenements.jpg";
	            } else {
		            $path = $home . 'resources/img/imgEvt/' . $e->img;
	            }
                $content .= "
				<div class='$colorStyle'>
					<a href='#' id='mesEvenementsBlockLink'>
						<div class='mesEvenementsBlockPhoto'><img src='{$path}'></div>
						<div class='mesEvenementsBlockName'>$e[nom]</div>
						<hr>
						<div class='mesEvenementsBlockExp'>$expiration</div>
					</a>
				</div>";
            }
        }
        $content .= "</div>";
        return $content;
    }

    /**
     * Utilisateur peut devenir Organisateur ou Vendeur pour un événement
     */
    private function changerPrivilege(): string
    {
        $priv = $this->tab[0]->privilege;
        $action = $this->container->router->pathFor('changer_privilege');
        return <<<HTML
			<form method="post" action=$action>
				<label for="newrole">Je souhaite devenir...</label>
				<select name="newrole">
					<option value="organisateur">Organisateur</option>
					<option value="vendeur">Vendeur</option>
				</select><br>
				<input type='submit' name='submit' value='Changer mes droits'><br>
			</form>
HTML;
    }

    private function recharger(): string
    {
        $id = $_SESSION['id_compte'];
        $action = $this->container->router->pathFor('profil_recharger');

        $monnaies = Monnaie::all()->toArray();
        $comboMonnaies = "";
        foreach ($monnaies as $monnaie) {
            $comboMonnaies .= "<option value='$monnaie[id_monnaie]'>$monnaie[nom_monnaie]</option>\n";
        }

        return <<<HTML
			<h2>Recharger mon compte</h2>
			<form method="post" action=$action>
				<div class="form-group">
					<label for='montant'>Montant à créditer : </label>
					<input required type='number' class='styleinput' name="montant" min=0.01 max=9999999.99 value=1 step=".01" onblur="checkFormat(this)">
				</div>
				<div class="form-group">
					<label for='listmonnaies'>Monnaie : </label>
					<select id='listmonnaies' class='styleinput' name='listmonnaies' required>$comboMonnaies</select>
				</div>
				<input type="hidden" name="id_compte" value="$id">
				<input type='submit' name='submit' value='Je reçois mes tokens 💰'>
			</form>
HTML;
    }

    private function ajoutCarteRFID():string
    {
        $action = $this->container->router->pathFor('ajoutCarteRFID_form');

        return <<<HTML
			<h2>Ajouter une carte de paiement</h2>
			<form method="post" action=$action>
				<div class="form-group">
					<label for='numcarte'><i class="material-icons">add_card</i>Numéro de votre carte : </label>
					<input required type='text' pattern="[A-Z0-9]{12}" class='styleinput' name="numcarte">
				</div>
				<input type='submit' name='submit' value='Ajouter ma carte de paiement'>
			</form>
HTML;
    }
}
