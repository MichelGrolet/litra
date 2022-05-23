<?php
declare(strict_types=1);

namespace litra\controller;

use litra\model\Compte;
use litra\model\Evenement;
use litra\model\Logrfid;
use litra\model\Monnaie;
use litra\model\RVendeurEvenement;
use litra\utilitaires\BlockChain;
use litra\views\ViewCrea;
use litra\views\ViewPages;
use litra\views\ViewProfil;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Container;
use Illuminate\Database\Capsule\Manager as DB;

class MainController
{
    private Container $container;
    private Compte $compte;
    private Evenement $event;

    public function __construct(Container $container)
    {
        $this->container = $container;
        if (isset($_SESSION['id_compte'])) {
            $this->compte = Compte::where('id_compte', $_SESSION['id_compte'])->first();
        }
    }

    public function home(Request $rq, Response $rs, array $args): Response
    {
        $vue = new ViewPages([], $this->container);
        $html = $vue->render(0);
        $rs->getBody()->write($html);
        return $rs;
    }

    public function register(Request $rq, Response $rs, array $args): Response
    {
        $vue = new ViewPages([], $this->container);
        $html = $vue->render(1);
        $rs->getBody()->write($html);
        if (isset($_POST['submit'])) {
            // Si le bouton "S'inscrire" a été cliqué :
            if ($_POST['submit'] == 'Inscription') {
                $donnees = Compte::where('adr_mail', $_POST['email'])->first();
                $email = $donnees->adr_mail;
                // Si l'email est pas déjà présent dans la base de données :
                if ($donnees != []) {
                    echo "<p class='erreur'>Vous êtes déjà inscrit avec cette adresse mail.</p>";
                } else {
                    if ($_POST['password'] != $_POST['password2']) {
                        echo "<p class='erreur'>Les mots de passe ne correspondent pas.</p>";
                    } else {
                        $email = htmlspecialchars($_POST['email']);
                        $prenom = htmlspecialchars($_POST['prenom']);
                        $nom = htmlspecialchars($_POST['nom']);
                        // Création de l'utilisateur par le modèle :
                        $u = new Compte();
                        $u->adr_mail = $email;
                        $u->prenom = $prenom;
                        $u->nom = $nom;
                        $u->mdp = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $u->save();
                        $_SESSION['id_compte'] = $u->id_compte;
                        $rs = $rs->withRedirect($this->container->router->pathFor('wallet'));
                    }
                }
            }
        }
        return $rs;
    }

    public function logout(Request $rq, Response $rs, $args): Response
    {
        if (isset($_SESSION['id_compte'])) {
            unset($_SESSION['id_compte']);
        }
        return $rs->withRedirect($this->container->router->pathFor('home'));
    }

    public function login(Request $rq, Response $rs, array $args): Response
    {
        $vue = new ViewPages([], $this->container);
        $html = $vue->render(2);
        $rs->getBody()->write($html);
        if (isset($_POST['submit'])) {
            // Si le bouton Connexion a été cliqué :
            if ($_POST['submit'] == 'Connexion') {
                $email = htmlspecialchars($_POST['email']);
                $pass = htmlspecialchars($_POST['password']);
                $user = Compte::where('adr_mail', '=', $_POST['email'])->first();
                // Si l'utilisateur existe :
                if ($user != []) {
                    if (password_verify($pass, $user['mdp'])) {
                        $_SESSION['id_compte'] = $user['id_compte'];
                        $rs = $rs->withRedirect($this->container->router->pathFor('profil_QRcode', ['token' => $args['token']]));
                    } else {
                        echo "<p class='erreur'>Le mot de passe est incorrect.</p>";
                    }
                } else {
                    echo "<p class='erreur'>Aucun compte ne correspond à cet email.</p>";
                }
            }
        }
        return $rs;
    }

    /**
     * traite le formulaire de creation d evenement
     */
    public function creerEvenement(Request $rq, Response $rs, array $args): Response
    {
        if (isset($_POST['nomevent']) && isset($_POST['dateevent']) && isset($_POST['lieuevent']) && isset($_POST['comboMonnaie'])) {
            if (isset($_POST['publierevent'])) { // si le bouton publier evenement est clique

                //recuperation des donnees dans les champs
                $nom = filter_var($_POST['nomevent'], FILTER_SANITIZE_STRING);
                $descr = filter_var($_POST['descrievent'], FILTER_SANITIZE_STRING);
                $date = $_POST['dateevent'];
                $lieu = filter_var($_POST['lieuevent'], FILTER_SANITIZE_STRING);
                $monnaie = intval(filter_var($_POST['comboMonnaie'], FILTER_SANITIZE_NUMBER_INT));

                //cree l evt dans la bdd avec les donnees recuperees
                $evt = new Evenement();
                $evt->id_createur = $_SESSION['id_compte'];
                $evt->id_monnaie = $monnaie;
                $evt->nom = $nom;
                $evt->description = $descr;
                $evt->date = $date;
                $evt->lieu = $lieu;
				$evt->save();

                if (isset($_FILES['imevent'])) {
                    $types = [".jpg", ".png", ".JPG", ".PNG"];
                    if (in_array(substr($_FILES['imevent']['name'], -4), $types)) {
                        $temp = explode(".", $_FILES["imevent"]["name"]);
	                    $new_name = $evt->id_evenement . '.' . end($temp);
                        $new_path = getcwd() . '\resources\img\imgEvt\\' . $new_name;
                        move_uploaded_file($_FILES['imevent']['tmp_name'], $new_path);
	                    $evt->update(['img' => $new_name]);
                    } else {
                        echo "<p class='erreur'>Le fichier fourni n'est pas une image.</p>";
                    }
                }
                $rs = $rs->withRedirect($this->container->router->pathFor('mes_evenements'));
            }
        }

        $vue = new ViewCrea([], $this->container);
        $html = $vue->render(0);
        $rs->getBody()->write($html);
        return $rs;
    }

    /**
     * traite le formulaire de creation d'une monnaie
     */
    public function creerMonnaie(Request $rq, Response $rs, array $args): Response
    {
        if (isset($_POST['nomtoken']) && isset($_POST['couttoken']) && isset($_POST['expiration'])) { //verification des champs
            if (isset($_POST['cremon'])) { // si le bouton creer token est clique
                //recuperation des donnees dans les champs
                $nom = filter_var($_POST['nomtoken'], FILTER_SANITIZE_STRING);
                $cout = filter_var($_POST['couttoken'], FILTER_SANITIZE_NUMBER_FLOAT);
                $dateexp = $_POST['expiration'];
                $convert = (isset($_POST['convertOui'])) ? 1 : 0;

                //cree le token ds la bdd avec les donnees recuperees
                $tok = new Monnaie();
                $tok->id_createur = $_SESSION['id_compte'];
                $tok->nom_monnaie = $nom;
                $tok->valeur = $cout;
                $tok->date_expiration = $dateexp;
                $tok->reconvertible = $convert;
                $tok->save();
                $rs = $rs->withRedirect($this->container->router->pathFor('mes_tokens'));
            }
        }
        $vue = new ViewCrea([], $this->container);
        $html = $vue->render(1);
        $rs->getBody()->write($html);
        return $rs;
    }

    public function afficherWallet(Request $rq, Response $rs, $args): Response
    {
        $monnaiesEtQte = BlockChain::walletComposition($this->compte->id_compte);
        // Si $monnaiesEtQte = null, c'est que la blockchainValide() vaut faux
        $boolBlockchain = true;
        if (is_null($monnaiesEtQte)) {
            $boolBlockchain = false;
        }
        $vue = new ViewPages([$this->compte, $monnaiesEtQte, $boolBlockchain], $this->container);
        $html = $vue->render(6);
        $rs->getBody()->write($html);
        return $rs;
    }


    public function scannerQR(Request $rq, Response $rs, $args): Response
    {
        $vue = new ViewPages([], $this->container);
        if (isset($_SESSION['id_compte'])) {
            $html = $vue->render(5);
            $rs->getBody()->write($html);
        } else {
            $rs = $rs->withRedirect($this->container->router->pathFor('login'));
        }
        return $rs;
    }

	/**
	 * Génère le formulaire de création d'un qr code de paiement
	 * Traite le formulaire une fois qu'il est validé
	 */
	public function qrPaiement(Request $rq, Response $rs, array $args): Response {
		if (isset($_POST['submit']) || isset($_POST['validerPaiement'])) {
			if (isset($_POST['submit']) && isset($_POST['value']) && isset($_POST['listmonnaies']) && isset($_POST['idvendeur'])) {
				if ($_POST['submit'] == 'Générer le QR code') {
					$array = [
						'val' => floatval(filter_var($_POST['value'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)),
						'idmonnaie' => intval(filter_var($_POST['listmonnaies'], FILTER_SANITIZE_NUMBER_INT)),
						'idvendeur' => filter_var($_POST['idvendeur'], FILTER_SANITIZE_NUMBER_INT)
					];
					$vue = new ViewPages($array, $this->container);
					$html = $vue->render(4);
					$rs->getBody()->write($html);
				} elseif ($_POST['submit'] == 'Scanner une carte RFID') {
					$this->validationRfid($rq, $rs, $args);
				}
			} elseif (isset($_POST['validerPaiement']) && $_POST['validerPaiement'] == 'Valider la transaction') {
				$idmonnaie = intval(filter_var($_POST['listmonnaiesPossedees'], FILTER_SANITIZE_NUMBER_INT));
				$qte = floatval(filter_var($_POST['qte'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
				$idrecepteur = intval(filter_var($_POST['recepteur'], FILTER_SANITIZE_NUMBER_INT));
				if (BlockChain::ajouterTransaction($this->compte->id_compte, $idrecepteur, $idmonnaie, $qte)) {
					$vue = new ViewPages([], $this->container, [], "Transaction effectuée avec succès");
					$html = $vue->render(0);
					$rs->getBody()->write($html);
				} else {
					$vue = new ViewPages([$this->compte], $this->container, ["La transaction n'a pas eu lieu. Veuillez réessayer ultérieurement."]);
					$html = $vue->render(3);
				}
			} else {
				$vue = new ViewPages([$this->compte], $this->container, ["Le formulaire n'est pas complet"]);
				$html = $vue->render(3);
				$rs->getBody()->write($html);
			}
		} else {
			$vue = new ViewPages([$this->compte], $this->container);
			$html = $vue->render(3);
			$rs->getBody()->write($html);
		}
		return $rs;
	}

    /**
     * formulaire pour ajouter dans revenementcompte
     * besoin de l'id_compte du vendeur et de l'id_evenement
     */
    public function formulaireVendeur(Request $rq, Response $rs, array $args): Response {
        if ($_POST['submit'] == 'Ajouter un vendeur') {
	        $id_evenement = intval(filter_var($_POST['id_evenement'], FILTER_SANITIZE_NUMBER_INT));
	        $id_vendeur = intval(filter_var($_POST['id_compte'], FILTER_SANITIZE_NUMBER_INT));
			$rv = new RVendeurEvenement();
			$rv->id_vendeur = $id_vendeur;
			$rv->id_evenement = $id_evenement;
			$rv->save();
        } else echo "<p class='erreur'>Erreur d'ajout de vendeur</p>";
		$compte = Compte::where('id_compte', '=', $_POST['id_compte'])->first();
        $vue = new ViewPages([$compte], $this->container);
        $html = $vue->render('afficherProfil');
        $rs->getBody()->write($html);
        return $rs;
    }

    /**
     * Gère le formulaire de validation de la transaction après scan du QR code
     */
    public function lecture_qr(Request $rq, Response $rs, array $args): Response
    {
        if (isset($_POST['data'])) {
            $data = $_POST['data'];
            $prefix = 'litraid=';
			// On vérifie que le QR code est bien un QR code de profil
            if (str_starts_with($data, $prefix)) {
                $id = substr($data, strlen($prefix));
                $user = Compte::select('*')->where('id_compte', '=', $id)->first();
                $vue = new ViewPages([$user], $this->container);
                $html = $vue->render('afficherProfil');
            } else {
                $data = unserialize($data);
                if ($data == null) {
                    $vue = new ViewPages([$data], $this->container, ["Le QR code n'est pas valide"]);
                } else {
                    $vue = new ViewPages([$data], $this->container);
                }
                $html = $vue->render(7);
            }
            $rs->getBody()->write($html);
        } elseif (isset($_POST['submit'])) {
            if ($_POST['submit'] == 'Valider la transaction') {
                $idmonnaie = intval(filter_var($_POST['idmonnaie'], FILTER_SANITIZE_NUMBER_INT));
                $val = floatval(filter_var($_POST['val'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                $idvendeur = intval(filter_var($_POST['idvendeur'], FILTER_SANITIZE_NUMBER_INT));
                if (BlockChain::ajouterTransaction($this->compte->id_compte, $idvendeur, $idmonnaie, $val)) {
	                $rs = $rs->withRedirect($this->container->router->pathFor('wallet'));
                } else {
                    $vue = new ViewPages([], $this->container, ["La transaction n'a pas eu lieu. Veuillez réessayer ultérieurement."]);
                    $html = $vue->render(5);
                }
                $rs->getBody()->write($html);
            }
        } else {
            $vue = new ViewPages([], $this->container, ["Erreur de lecture QR. Veuillez réessayer ultérieurement."]);
            $html = $vue->render(5);
            $rs->getBody()->write($html);
        }
        return $rs;
    }

	public function toggleTheme(Request $rq, Response $rs, array $args): Response {
		$vue = new ViewPages([], $this->container);
		$html = $vue->render(0);
		$rs->getBody()->write($html);
		if (isset($_COOKIE['lighttheme'])) {
			unset($_COOKIE['lighttheme']);
			setcookie('lighttheme', '', -1);

		} else {
			setcookie('lighttheme', 'lighttheme', time()+3600*365*24);
		}
		return $rs->withRedirect($this->container->router->pathFor('home'));
	}

	public function validationRfid(Request $rq, Response $rs, array $args): Response {
		$idvendeur = intval($this->compte->id_compte);
		$log = Logrfid::where('id_vendeur', $idvendeur)->first();
		$array = array(
			'val' => floatval(filter_var($_POST['value'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)),
			'idmonnaie' => intval(filter_var($_POST['listmonnaies'], FILTER_SANITIZE_NUMBER_INT)),
			'idvendeur' => $idvendeur,
			'log' => $log
		);
		if ($log != null) {
			$client = Compte::where('carte_rfid', $log->carte_rfid)->first();
			if ($client != null) {
				$vue = new ViewPages($array, $this->container);
				$html = $vue->render('scanRFIDeffectue');
			} else {
				$vue = new ViewPages($array, $this->container, ["Cette carte n'est associée à aucun compte. Les scans ont été supprimés."]);
				Logrfid::where('id_vendeur', $this->compte->id_compte)->delete();
				$html = $vue->render('scanRFIDattente');
			}
		} else {
			$vue = new ViewPages($array, $this->container, ["Votre scanneur RFID n'a pas encore scanné de carte."]);
			$html = $vue->render('scanRFIDattente');
		}
		$rs->getBody()->write($html);
		return $rs;
	}

	public function paiementRfid(Request $rq, Response $rs, array $args): Response {
		$html = "";
		$erreur = false;
		$debug = "";
		$array = array(
			'val' => floatval(filter_var($_POST['val'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)),
			'idmonnaie' => intval(filter_var($_POST['idmonnaie'], FILTER_SANITIZE_NUMBER_INT)),
		);
		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Confirmer la transaction') {
				if (isset($_POST['val']) && isset($_POST['idmonnaie']) && isset($_POST['id_client'])) {
					$val = floatval(filter_var($_POST['val'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
					$idmonnaie = intval(filter_var($_POST['idmonnaie'], FILTER_SANITIZE_NUMBER_INT));
					$id_client = intval(filter_var($_POST['id_client'], FILTER_SANITIZE_NUMBER_INT));
					$debug = $id_client. $this->compte->id_compte. $idmonnaie. $val;
					if (BlockChain::ajouterTransaction($id_client, $this->compte->id_compte, $idmonnaie, $val)) {
                        $rs = $rs->withRedirect($this->container->router->pathFor('paiement'));
						Logrfid::where('id_vendeur', $this->compte->id_compte)->delete();
					} else $erreur = true;
				} else $erreur = true;
			} elseif ($_POST['submit'] == 'Supprimer les scans en cours') {
				Logrfid::where('id_vendeur', $this->compte->id_compte)->delete();
				$vue = new ViewPages($array, $this->container, [], "Supression effectuée avec succès");
				$html = $vue->render('scanRFIDattente');
			}
		} else $erreur = true;
		if ($erreur) {
			$vue = new ViewPages($array, $this->container, ["La transaction n'a pas eu lieu. Veuillez réessayer ultérieurement. Erreur: ".$debug]);
			$html = $vue->render('scanRFIDattente');
		}
		$rs->getBody()->write($html);
		return $rs;
	}

	/**
     * ici réside une fonction pour la liste des événements
     */
    public function afficherListeEvenements(Request $rq, Response $rs, array $args): Response
    {
        $search = "";
        if (isset($_POST['searchbar'])) {
            $search = $_POST['searchbar'];
            $event = Evenement::where('nom', 'LIKE', "%$search%")->get()->toArray();
        } else {
            $event = Evenement::All()->toArray();
        }        
        $vue = new ViewPages([$event, $search], $this->container);
        $html = $vue->render(8);
        $rs->getBody()->write($html);
        return $rs;
    }

    /**
     * et ici celle pour la liste des monnaies
     */
    public function afficherListeMonnaies(Request $rq, Response $rs, array $args): Response
    {
        $search = "";
        if (isset($_POST['searchbar'])) {
            $search = $_POST['searchbar'];
            $monnaies = Monnaie::where('nom_monnaie', 'LIKE', "%$search%")->get()->toArray();
        } else {
            $monnaies = Monnaie::All()->toArray();
        }        
        $vue = new ViewPages([$monnaies, $search], $this->container);
        $html = $vue->render(9);
        $rs->getBody()->write($html);
        return $rs;
    }

	/**
	 * Détails d'un évenement
	 */
	public function afficherDetailsEvenement(Request $rq, Response $rs, array $args): Response
	{
		$event =Evenement::where('id_evenement', '=', $args['id_evenement'])->first();
        $data = $rq->getParsedBody();
		$vue = new ViewPages([$event], $this->container) ;
        $html = $vue->render(10);
        $rs->getBody()->write($html);
        return $rs;
	}

	/**
	 * Détails d'une monnaie
	 */
	public function afficherDetailsMonnaie(Request $rq, Response $rs, array $args): Response
	{
		$monnaie =Monnaie::where('id_monnaie', '=', $args['id_monnaie'])->first();
        $data = $rq->getParsedBody();
		$vue = new ViewPages([$monnaie], $this->container) ;
        $html = $vue->render(11);
        $rs->getBody()->write($html);
        return $rs;
	}
}