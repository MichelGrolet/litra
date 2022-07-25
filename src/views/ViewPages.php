<?php

namespace litra\views;

use litra\model\Evenement;
use litra\model\Monnaie;
use litra\model\Compte;
use litra\utilitaires\BlockChain;
use Slim\Container;

class ViewPages
{
    public array $tab;
    public Container $container;
    public array $erreurs;
    public string $info;

    public function __construct(array $tab, Container $container, array $erreurs = [], string $info = "")
    {
        $this->tab = $tab;
        $this->container = $container;
        $this->erreurs = $erreurs;
        $this->info = $info;
    }

    public function render($selector): string
    {
        $content = match ($selector) {
            0 => $this->home(),
            1 => $this->register(),
            2 => $this->login(),
            3 => $this->formPaiement(),
            4 => $this->qrPaiement(),
            5 => $this->scannerQR(),
            6 => $this->afficherWallet(),
            7 => $this->afficherTransacQR(),
			8 => $this->afficherListeEvenements(),
			9 => $this->afficherListeMonnaies(),
			10 => $this->afficherDetailsEvenement(),
			11 => $this->afficherDetailsMonnaie(),
            'afficherProfil' => $this->afficherProfil(),
            'scanRFIDattente' => $this->scanRFIDattente(),
	        'scanRFIDeffectue' => $this->scanRFIDeffectue(),
            default => "selector inconnu de render",
        };
        $viewElements = new ViewElements($this->container);
        $header = $viewElements->renderHeader();
        $head = $viewElements->renderHead("Litra");
        // ajouter les erreurs en html depuis l'array $erreurs
        if (count($this->erreurs) > 0) {
            $content .= "<br><br><br><h2>Erreurs</h2>";
            foreach ($this->erreurs as $erreur) {
                $content .= "<p class='erreur'>" . $erreur . "</p>";
            }
        }
        if ($this->info != "") {
            $content .= <<<HTML
			<div class="popup">
				<div class="popup-content">
					<p>{$this->info}</p>
				</div>
			</div>
HTML;
        }

        return <<<HTML
			<!DOCTYPE html>
			<html lang="fr">
				$head
				<body>
					$header
					<div class="content">
						$content
					</div>
				   <footer><p>Litra Team® - 2022 - IUT Nancy Charlemagne</p></footer>
				</body>
			</html>
HTML;
    }

    private function home(): string
    {
		$liste_evenements = $this->container->router->pathFor('liste_evenements');
		$liste_monnaies = $this->container->router->pathFor('liste_monnaies');
        $home = $this->container->router->pathFor('home');
	    $img_events = $home . "resources/img/evenements.jpg";
	    $img_monnaies = $home . "resources/img/monnaies.jpg";
        return <<<HTML
			<div class="wrapper">
				<div class="parent" onclick="">
				    <div class="child" style="background-image: url({$img_events})">
						<a class="lien-accueil" href={$liste_evenements}>Événements</a>
				    </div>
				</div>
				<div class="parent" onclick="">
				    <div class="child" style="background-image: url({$img_monnaies})">
						<a class="lien-accueil" href={$liste_monnaies}>Monnaies</a>
				    </div>
				</div>
			</div>
HTML;
    }

    private function register(): string
    {
        $action = $this->container->router->pathFor('register');
        return <<<HTML
			<h2>Inscription</h2>
			<form action=$action method='post'>
				<label for='nom'>Nom</label>
				<input type='text' name='nom' id='nom' required>
				
				<label for='prenom'>Prénom</label>
				<input type='text' name='prenom' id='prenom' required>
				
				<label for='email'>Email</label>
				<input type='email' name='email' id='email' required>
				
				<label for='password'>Mot de passe</label>
				<input type='password' name='password' id='password' required>
				
				<label for='password_confirm'>Confirmation du mot de passe</label>
				<input type='password' name='password2' id='password2' required>
				
				<input type='submit' name='submit' value='Inscription'>
			</form>
HTML;
    }

    private function login(): string
    {
        $action = $this->container->router->pathFor('login');
        return <<<HTML
			<h2>Connexion</h2>
			<form action=$action method='post'>
				<label for='email'>Email</label>
				<input type='text' name='email' id='email' required>
				
				<label for='password'>Mot de passe</label>
				<input type='password' name='password' id='password' required>
				<input type='submit' name='submit' value='Connexion'>
			</form>
HTML;
    }

    private function formPaiement(): string
    {
        if (BlockChain::blockchainValide()) {
            $c = $this->tab[0];

            $monnaies = Monnaie::all()->toArray();
            $comboMonnaies = "";
            foreach ($monnaies as $monnaie) {
                $comboMonnaies .= "<option value='$monnaie[id_monnaie]'>$monnaie[nom_monnaie]</option>\n";
            }

            // si le compte est vendeur ou organisateur
            $formVendeur = "";
            if ($c->privilege == 3 || $c->privilege == 2 || $c->privilege == 1) {
                $action = $this->container->router->pathFor('paiement');
                $scanner_url = $this->container->router->pathFor('scanner_QRcode');

                // on recupere la monnaie de chaque evenement sur lequel le vendeur est vendeur depuis rvendeurevemenen
                $evenements = $c->evenementVendeur()->get()->toArray();
                $idvendeur = $c->id_compte;
                echo '<pre>';
                $formVendeur = <<<HTML
				<div id="divPaiementDemande">
					<form action=$action method='post' id='paiement-form'>
						<p>Ce formulaire permet de générer des QR codes de facturation ou de scanner la carte RFID d'un client.<br>Pour lire un QR code : <a href=$scanner_url><i class="material-icons">qr_code_scanner</i>Scanner</a></p>
						<div class="form-group">
							<label for='value'>Valeur : </label>
							<input type='number' class='styleinput' name="value" min="0.01" max="9999999.99" value="1" step=".01" onblur="checkFormat(this)">
						</div>
						<div class="form-group">
							<label for='listmonnaies'>Monnaie : </label>
							<select id='listmonnaies' class='styleinput' name='listmonnaies' required>$comboMonnaies</select>
						</div>
						<input type="hidden" type="text" name="idvendeur" value=$idvendeur>
						<input type='submit' name='submit' value='Générer le QR code'>
						<input type='submit' name='submit' value='Scanner une carte RFID'>
					</form>
				</div>			
HTML;
            }

            $monnaiesPossedees = (BlockChain::walletComposition($c->id_compte));
            $optionsMonnaiesPossedees = "";


            if ($c->privilege != 2) {
                foreach ($monnaiesPossedees as $monnaie) {
                    $optionsMonnaiesPossedees .= "<option value='$monnaie[id_monnaie]'>$monnaie[nom_monnaie]</option>";
                }
            } else {
                $optionsMonnaiesPossedees = $comboMonnaies;
            }
            return <<<HTML
			<h2>Effectuer un paiement</h2>
			<div id="paiement">
            <input style="visibility: collapse;" id="votreID" value="$c->id_compte">
			<script>
				var checkFormat = function(input) {
                    var baseInput = input.value;
                    input.value = (baseInput.indexOf(".") >= 0) ? (baseInput.substr(0, baseInput.indexOf(".")) + baseInput.substr(baseInput.indexOf("."), 3)) : baseInput;
                    if (parseInt(input.value) > parseInt(input.max)) {
                        input.value = input.max;
                    }
                    if (parseInt(input.value) < parseInt(input.min)) {
                        input.value = input.min;
                    }
				}

                var checkId = function(input) {
                    let votreId  = parseInt(document.getElementById('votreID').value);
                    if (input.value == votreId) {
                        document.getElementById("id-invalide").textContent ="Vous ne pouvez pas envoyer de la monnaie à votre propre compte";
                    } else {
                        document.getElementById("id-invalide").textContent ="";
                    }
					}
			</script>
			<div id='divPaiementEmit'>
			<form id='paiementEmit' method='post'>
				<p>Veuillez choisir la monnaie à utiliser pour le paiement.</p>
				<div class="form-group">
					<label for='recepteur'>ID du compte récepteur : </label>
				<input type=number class='styleinput'  id='recepteur' name='recepteur' onblur="checkId(this)" required>
				<span id="id-invalide" style="color: red;"></span>
				</div>
				<div class="form-group">
					<label for='listmonnaiesPossedees'>Monnaie à envoyer : </label>
					<select id='listmonnaiesPossedees' class='styleinput' name='listmonnaiesPossedees' required>$optionsMonnaiesPossedees</select>
				</div>
				<div class="form-group">
					<label for='qte'>Quantité : </label>
					<input type='number' class='styleinput' id='qte' name='qte' min="0.01" max="9999999.99" value="1" step=".01" onfocus="checkFormat(this)" onblur="checkFormat(this)">
					</div>
					<input id='validerPaiement' type='submit' name='validerPaiement' value='Valider la transaction' required>
			</form>
			</div>
			$formVendeur
HTML;
        } else {
            return <<<HTML
            <h3>Les paiements sont momentanément suspendus en raison d'un blocage de toute transaction.</h3>
HTML;
        }
    }

    private function qrPaiement(): string
    {
        $val = $this->tab['val'];
        $idmonnaie = $this->tab['idmonnaie'];
        $idvendeur = $this->tab['idvendeur'];
        $monnaie = Monnaie::find($idmonnaie)->nom_monnaie;
        $informations = [
            "val" => $val,
            "idmonnaie" => $idmonnaie,
            "idvendeur" => $idvendeur
        ];
        $code = serialize($informations);
        return <<<HTML
			<h2>QR code généré</h2>
			<div class="center">
				<p><i class="material-icons">info</i>Ce code contient <strong>$val $monnaie</strong></p>
				<div id="qrcode">
					<img id='QRcode' src='https://api.qrserver.com/v1/create-qr-code/?data=${code}' alt='Quelque chose ne s est pas passé comme prévu' title='Votre QRcode Unique' width='300' height='300'/>
				</div>
			</div>
HTML;
    }

    private function scannerQR(): string
    {
        $home = $this->container->router->pathFor('home');
        $launcherJS = $home . "resources/js/launcher.js";
        $action = $this->container->router->pathFor('lecture_qr');
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        //error_reporting(0);
        return <<<HTML
			<h2>Scanner un QR code</h2>
			<div id='QRscanner'>
			<p id='info'><i class="material-icons">info</i>Reculez un peu, seul le centre du flux est utilisé pour la lecture.</p>
			<video id='video'></video>
			<form hidden method='post' id='scanner-form' name='scanner_form' action='$action'>
				<input id='qr-data' type='hidden' name='data' value=''><!-- contiendra le contenu du QR sous forme de tableau serialisé -->
			</form>
			</div>
			<script type="module" src="$launcherJS"></script>
HTML;
    }

    private function afficherWallet(): string
    {
        $content = "";
        // On retire les monnaies dont on a une quantite negative ou nulle, juste pour la banque et au cas ou
        $monnaies = $this->tab[1];
        if ($monnaies == null && BlockChain::blockchainValide() == false) {
            return <<<HTML
				<p class="erreur">Impossible de charger votre portefeuille car le système a été altéré par un tiers<br>Veuillez patienter jusqu'à la résolution de ce problème.</p>
			HTML;
        } else {
            foreach ($monnaies as $k => $m) {
                if ($m['qte'] <= 0) {
                    unset($monnaies[$k]);
                }
            }
            // Si on a aucune monnaie, l'affichage se limite a cette information
            if (sizeof($monnaies) == 0) {
                return <<<HTML
					<p class="erreur">Vous ne possédez aucune monnaie.</p>
HTML;
            } // Dans le cas contraire, on a une affichage ligne par ligne : nom, valeur, quantite, solde dans cette monnaie
            else {
                $sommeTotale = 0;
                $tabRecap = array();
                foreach ($monnaies as $m) {
                    if ($m['qte'] > 0) {
                        $somme = $m['qte'] * $m['valeur'];
                        $sommeTotale += $somme;
                        $content .= <<<HTML
							<tr>
								<td>$m[nom_monnaie]</td>
								<td>$m[valeur] €</td>
								<td>$m[qte] $m[nom_monnaie]</td>
								<td>$somme €</td>
							</tr>
HTML;
                        $tabRecap[$m['nom_monnaie']] = $somme;
                    }
                }
                $script = <<<HTML
				<script>
					window.onload = function() {
						var chart = new CanvasJS.Chart('chartContainer', {
							animationEnabled: true,
							backgroundColor: '#101010',
							title: {
								text: 'Récapitulatif de votre Portefeuille',
								fontColor: 'white',
								fontFamily: 'Poppins'
							},
							data: [{
								indexLabelFontColor: 'white',
								indexLabelFontFamily: 'Poppins',
								type: 'pie',
								startAngle: 270,
								yValueFormatString: '##0.00\'%\'',
								indexLabel: '{label} {y}',
								dataPoints: [
HTML;

                foreach ($tabRecap as $cle => $tb) {
                    $tb = ($tb / $sommeTotale) * 100;
                    $script .= "{y: $tb, label: '$cle'},";
                }

                $script .= <<<HTML
								]
							}]
						});
						chart.render();
					}
				</script>
				<div id='chartContainer' style='height: 300px; width: 100%;'></div>
				<script src='https://canvasjs.com/assets/script/canvasjs.min.js'></script>
HTML;
            }
        }
        return <<<HTML
			<h2>Portefeuille de tokens</h2>
			<table>
				<tr>
					<th>Nom de la monnaie</th>
					<th>Valeur unitaire</span>
					<th>Quantité possédée</th>
					<th>Valeur possédée</th>
				</tr>
				$content
				<tr class="ligne-total">
					<td colspan="4" id='walletTotal'>Total : $sommeTotale €</td>
				</tr>
			</table>
			
			$script
HTML;
    }

    /**
     * Formulaire de validation de la transaction après scan du QR code
     */
    private function afficherTransacQR(): string
    {
        $data = $this->tab[0];
        $val = $data['val'];
        $idmonnaie = $data['idmonnaie'];
        $monnaie = Monnaie::where('id_monnaie', '=', $idmonnaie)->first()->nom_monnaie;
        $idvendeur = $data['idvendeur'];
        $action = $this->container->router->pathFor('lecture_qr');
        $content = <<<HTML
			<p>Valeur : $val $monnaie</p>
			<form method="post" action=$action>
				<input type="hidden" name="idmonnaie" value=$idmonnaie>
				<input type="hidden" name="val" value=$val>
				<input type="hidden" name="idvendeur" value=$idvendeur>
				<input type="submit" name="submit" value="Valider la transaction">
			</form>
		HTML;
        return <<<HTML
			<h2>Transaction</h2>
			$content
HTML;
    }

    /**
     * Affichage d'un profil après scan
     */
    private function afficherProfil(): string
    {
        $home_url = $this->container->router->pathFor('home');
        $autre_compte = $this->tab[0];
        $p_url = $autre_compte->url_p;
        if ($p_url == null) $p_url = $home_url . "resources/img/DefaultProfilPicture.png";
        else $p_url = $home_url . "resources/img/pps/" . $p_url;

        $privilegeStr = ViewElements::privFromInt($autre_compte->privilege);
		$form = '';
		$mon_compte = Compte::where('id_compte', '=', $_SESSION['id_compte'])->first();
		if ($mon_compte->privilege == 2 || $mon_compte->privilege == 3) {
			if ($autre_compte->privilege != 1) {
				$autre_compte->privilege = 1;
				$autre_compte->update();
			}
			// insérer dans revenementvendeur
			$options ="";
			$listeEvent = Evenement::where('id_createur', '=', $mon_compte->id_compte)->get();
			foreach($listeEvent as $l) {
				$options .= <<<HTML
					<option value={$l->id_evenement}>$l->nom</option>
HTML;
			}
			$controller = $this->container->router->pathFor('abc');
			$form = <<<HTML
				<form method='post' action={$controller}>
					<p>Ajoutez cet utilisateur comme vendeur pour un de vos événements.</p>
					<select name="id_evenement" id="event-select" required>
						$options
					</select>
					<input type="hidden" value={$autre_compte->id_compte} name='id_compte'/>
					<input type="submit" value="Ajouter un vendeur" name="submit"/>
				</form>
			
HTML;
		}
        return <<<HTML
			<div id="infos infos-scan">
				<div id="profil-pp-container"><img src="$p_url" alt="Votre image de profil"/></div>
				<br>
				<p id="info-nom">$autre_compte->prenom <strong>$autre_compte->nom</strong></p>
				<p class="info-role"><strong>$privilegeStr</strong></p>
				<p class="info-id"><i class="material-icons">perm_identity</i>Identifiant unique : <strong>$autre_compte->id_compte</strong></p>
				<p class="info-id"><i class="material-icons">phone</i>Téléphone : <a href="tel:$autre_compte->num_tel">$autre_compte->num_tel</a></p>
				<p class="info-id"><i class="material-icons">email</i>Adresse email : <a href="mailto:$autre_compte->adr_mail">$autre_compte->adr_mail</a></p>
			</div>
			$form
HTML;
    }

    private function scanRFIDattente(): string
    {
	    $data = $this->tab;
        $url_controller = $this->container->router->pathFor('validationRfid');
        return <<<HTML
			<h2>Transaction RFID</h2>
			<form method="post" action="$url_controller">
				<p><i class="material-icons">info</i>Le premier scan à votre compte est pris en compte. Tous les scans sont supprimés entre chaque transaction RFID.</p>
				<input type="hidden" name="value" value={$data['val']}>
				<input type="hidden" name="listmonnaies" value={$data['idmonnaie']}>
				<input type="submit" name="submit" value="Rafraîchir">
			</form>
HTML;
    }

	private function scanRFIDeffectue(): string
	{
		$data = $this->tab;
		if (!isset($data['log'])) {
			$url_controller = $this->container->router->pathFor('validationRfid');
			$form_content = <<<HTML
					<p class="erreur">Votre scanneur RFID n'a pas encore scanné de carte.</p>
					<input type="submit" name="submit" value="Rafraîchir">
HTML;
		} else {
			$url_controller = $this->container->router->pathFor('paiementRfid');
			$client = Compte::where('carte_rfid', $data['log']->carte_rfid)->first();
			$form_content = <<<HTML
					<div class="form-group">
						<label for="client">Client :</label>
						<input disabled name="client" value="{$client->prenom} {$client->nom}">
					</div>
					<input type="hidden" name="val" value={$data['val']}>
					<input type="hidden" name="idmonnaie" value={$data['idmonnaie']}>
					<input type="hidden" name="id_client" value={$client->id_compte}>
					<input type="submit" name="submit" value="Confirmer la transaction">
					<input type="submit" class="red" name="submit" value="Supprimer les scans en cours">
HTML;
		}
		return <<<HTML
			<h2>Transaction RFID</h2>
			<form method="post" action=$url_controller>
				$form_content
			</form>
HTML;
	}

	/**
     * Affichage de la liste des événements existants
     */
    private function afficherListeEvenements(): string {
        $events = $this->tab[0];
        //$listeEvenements = Evenement::All();
		$searchbarContent = $this->tab[1];
		$searchbar = <<<HTML
        <form method='POST' name='formSearchbar' id='formSearchbar'>
            <input type="text" class = "search-product" name="searchbar" id="" placeholder="Rechercher un événement" value='$searchbarContent'/>
            <input type='submit' value='Rechercher'>
        </form>
HTML;
$content =<<<HTML
		<h2>Liste des Événements</h2>
		$searchbar
HTML;
		if (count($events) == 0) {
			$content .= "<p class='erreur'>Aucun événement n'a été trouvé.</p>";
		} else {
			foreach ($events as $l) {
				$event = $this->container->router->pathFor('details_evenement', ["id_evenement" => $l['id_evenement']]);
				$nom = $l['nom'];
				if ($l['date'] == "") {
					$date = "A déterminer";
				} else {
					$date = $l['date'];
				}
				$lieu = $l['lieu'];
				$content .= "
	            <div class='listLineEvent'>
	                <div class='body_event'>
	                    <a href=$event><h3 class='nom_event'>$nom</h></a>
	                    <p class='date_event'>$date</p>
	                    <p class='lieu_event'>$lieu</p>
	                </div>
				</div>
	            ";

			}
		}
        return $content;
    }
    
    /**
     * Affichage de la liste des monnaies existantes
     */
    private function afficherListeMonnaies(): string {
        $monnaies = $this->tab[0];
		$searchbarContent = $this->tab[1];
		$searchbar = <<<HTML
        <form method='POST' name='formSearchbar' id='formSearchbar'>
            <input type="text" class = "search-product" name="searchbar" id="" placeholder="Rechercher un événement" value='$searchbarContent'/>
            <input type='submit' value='Rechercher'>
        </form>
HTML;
$content =<<<HTML
		<h2>Liste des Monnaies</h2>
		$searchbar
HTML;
		if (count($monnaies) == 0) {
			$content .= "<p class='erreur'>Aucun événement n'a été trouvé.</p>";
		} else {
			foreach ($monnaies as $l) {
				$monnaie = $this->container->router->pathFor('details_monnaie',["id_monnaie"=>$l['id_monnaie']]);
				$nom = $l['nom_monnaie'];
				$valeur = $l['valeur'];
				$id = $l['id_monnaie'];

				$content .= "
				<div class='listLineMonnaie'>
					<div id='body_monnaie'>
						<a href=$monnaie><h3 class='nom_monnaie'>$nom</h></a>
						<p>Correspond à $valeur €</p>
						<p>Id : $id</p>
					</div>
				</div>
				";
			}
		}
		return $content;
    }

	/**
	 * Affichage des détails d'un événement
	 */
	private function afficherDetailsEvenement(): string {
		$retour = $this->container->router->pathFor("liste_evenements");
		$e = $this->tab[0];
		$nom = $e['nom'];
		$descr = $e['description'];
		if ($descr == "") {
			$descr = "A déterminer";
		}
		$lieu = $e['lieu'];
		if ($lieu == "") {
			$lieu = "A déterminer";
		}
		$date = $e['date'];
		if ($date == "") {
			$date = "A déterminer";
		}
		$img = $e['img'];
		$home = $this->container->router->pathFor('home');
		if ($img == "") {
			$path = $home . "resources/img/evenements.jpg";
		} else {
			$path = $home . 'resources/img/imgEvt/' . $img;
		}
		$url_monnaie = $this->container->router->pathFor("details_monnaie", ["id_monnaie"=>$e['id_monnaie']]);
		$monnaie = Monnaie::where('id_monnaie', '=', $e['id_monnaie'])->first();
		$monnaie = $monnaie->nom_monnaie;
		return <<<HTML
			<section id="boxdetail">
				<div class='detailItem'>
					<img id="imgItem" src={$path}> 
				</div>
				<div class='detailEvent'>
					<h2>$nom</h2>
					<p>Description : $descr</p>
					<p>Lieu : $lieu</p>
					<p>Date : $date</p>
					<a href= $url_monnaie><p>Monnaie : $monnaie</p></a>
				</div>
			</section>
			<a href=$retour><p>Retour</p></a>
		HTML;
	}

	/**
	 * Affichage des détails d'un événement
	 */
	private function afficherDetailsMonnaie(): string 
	{
		$retour = $this->container->router->pathFor("liste_monnaies");
		$m = $this->tab[0];
		$nom = $m['nom_monnaie'];
		$createur = Compte::where('id_compte', '=', $m['id_createur'])->first();
		$nom_createur = $createur->nom;
		$prenom_createur = $createur->prenom;
		$valeur = $m['valeur'];
		$int_rec = $m['reconvertible'];
		if ($int_rec == 0) {
			$rec = "Cette monnaie n'est pas reconvertible.";
		} else {
			$rec = "Cette monnaie est reconvertible.";
		}
		$date_exp = $m['date_expiration'];
		if ($date_exp == "") {
			$date_exp = "A déterminer";
		}
		$listeEvent = Evenement::where('id_monnaie', '=', $m['id_monnaie'])->get()->toArray();
		$content = "";
		if ($listeEvent!=null) {
			shuffle($listeEvent);
			for ($i = 0; $i<3; $i++) {
				if ($listeEvent[$i] != null) {
					$url = $this->container->router->pathFor("details_evenement", ['id_evenement'=> $listeEvent[$i]['id_evenement']]);
					$nom_m = $listeEvent[$i]['nom'];
					$content .= "<div>-<a href=$url>$nom_m</a>-</div>";
				}
			}
		} else {
			$content = "<div>Pas d'événement renseigné avec cette monnaie.</div>";
		}
		
		
		return <<<HTML
			<section id="boxdetail">
				<div class='detail'>
					<h2>$nom</h2>
					<p>Créer par : $nom_createur $prenom_createur</p>
					<p>Correspond à : $valeur €</p>
					<p>$rec</p>
					<p>Date d'expiration : $date_exp</p>
					<hr>
					<p>Evenement utilisant cette monnaie :</p>
					<span class='listEvent'>
						$content
					</span>
				</div>
			</section>
			<a href=$retour><p>Retour</p></a>
		HTML;
	}
}
