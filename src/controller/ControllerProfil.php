<?php
declare(strict_types=1);

namespace litra\controller;

use litra\model\Compte;
use litra\model\Evenement;
use litra\model\Monnaie;
use litra\utilitaires\BlockChain;
use litra\views\ViewElements;
use litra\views\ViewProfil;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Container;

class ControllerProfil {
	private Container $container;
	private Compte $compte;

	public function __construct(Container $container) {
		$this->container = $container;
		$this->compte = Compte::where('id_compte', $_SESSION['id_compte'])->first();
	}

	public function afficherQR(Request $rq, Response $rs, $args): Response {
		if ($this->compte === null) {
			$rs = $rs->withRedirect($this->container->router->pathFor('login'));
		} else {
			$vue = new ViewProfil([$this->compte], $this->container);
			$html = $vue->render('QRCode');
		}
		$rs->getBody()->write($html);
		return $rs;
	}

	public function editer(Request $rq, Response $rs, $args): Response {
		$vue = new ViewProfil([$this->compte], $this->container);
		$html = $vue->render(1);
		$rs->getBody()->write($html);
		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Enregistrer les informations') {
				if (isset($_POST['password']) && ($_POST['password'] != null)) {
					$pass = htmlspecialchars($_POST['password']);
					if (isset($_POST['user_tel']) && ($_POST['user_tel'] != null)) {
						if (password_verify($pass, $this->compte['mdp'])) {
							$num = filter_var($_POST['user_tel'], FILTER_SANITIZE_NUMBER_INT);
							$num = preg_replace('/[^0-9+-]/', '', $num);
							$this->compte->update(['num_tel' => $num]);
						}
					}
				//PP
					if (isset($_FILES['pp']) && ($_FILES['pp'] != null)) {
						$types = [".jpg", ".png", ".JPG", ".PNG"];
						// On vérifie que c'est bien une image
						if (in_array(substr($_FILES['pp']['name'], -4), $types)) {
							// On vérifie que le fichier n'est pas trop lourd
							if ($_FILES["pp"]["size"] < 10000000) {
								$user_id = $_SESSION['id_compte'];
								//Création du nouveau nom de fichier
								$temp = explode(".", $_FILES["pp"]["name"]);
								$new_name = $user_id . '.' . end($temp);
								$new_path = getcwd() . '\resources\img\pps\\' . $new_name;
								// On déplace le fichier dans le dossier
								move_uploaded_file($_FILES["pp"]["tmp_name"], $new_path);
								// Mise à jour la base de données
								$this->compte->update(['url_p' => $new_name]);
								$rs = $rs->withRedirect($this->container->router->pathFor('profil_QRcode'));
							} else {
								echo "<p class='erreur'>Le fichier fourni est trop lourd (max : 10 mo).</p>";
							}
						} else {
							echo "<p class='erreur'>Le fichier fourni n'est pas une image.</p>";
						}
					}
				}
			}
		}
		return $rs;
	}

	public function afficherWallet(Request $rq, Response $rs, $args): Response {
		$monnaiesEtQte = BlockChain::walletComposition($this->compte->id_compte);
		$vue = new ViewProfil([$this->compte, $monnaiesEtQte, BlockChain::blockchainValide()], $this->container);
		$html = $vue->render(3);
		$rs->getBody()->write($html);
		return $rs;
	}

	public function mesCreaTokens(Request $rq, Response $rs, $args): Response {
		$monnaiesCrees = Monnaie::where('id_createur', '=', $this->compte['id_compte'])->get();
		$vue = new ViewProfil([$this->compte, $monnaiesCrees], $this->container);
		$html = $vue->render(5);
		$rs->getBody()->write($html);
		return $rs;
	}

	public function changerPrivilege(Request $rq, Response $rs, $args): Response {
		if (isset($_SESSION['id_compte'])) {
			$vue = new ViewProfil([$this->compte], $this->container);
			$html = $vue->render('changerPrivilege');
			$rs->getBody()->write($html);
			if (isset($_POST['submit'])) {
				if ($_POST['submit'] == 'Changer mes droits') {
					$privilege = ViewElements::privFromString($_POST['newrole']);
					if (!$privilege==null) {
						$this->compte->privilege = $privilege;
						$this->compte->update();
						$rs = $rs->withRedirect($this->container->router->pathFor('profil_QRcode'));
					} else echo "<p class='erreur'>Erreur exceptionelle.</p>";
				}
			}
		} else {
			$rs = $rs->withRedirect($this->container->router->pathFor('login'));
		}
		return $rs;
	}

	public function mesCreaEvenements(Request $rq, Response $rs, $args): Response {
		$evenemCrees = Evenement::where('id_createur', '=', $this->compte['id_compte'])->get();
		$vue = new ViewProfil([$this->compte, $evenemCrees], $this->container);
		$html = $vue->render(6);
		$rs->getBody()->write($html);
		return $rs;
	}

	public function afficherSecu(Request $rq, Response $rs, $args): Response {
		$vue = new ViewProfil([$this->compte], $this->container);
		$html = $vue->render(4);
		$rs->getBody()->write($html);
		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Valider') {
				if (isset($_POST['user_old_mdp']) && ($_POST['user_old_mdp'] != null)) {
					$pass = htmlspecialchars($_POST['user_old_mdp']);
					if ((isset($_POST['user_new_mdp'])) && ($_POST['user_new_mdp'] != null) && (isset($_POST['conf_user_new_mdp'])) && ($_POST['conf_user_new_mdp'] != null)) {
						if (password_verify($pass, $this->compte['mdp'])) {
							if ($_POST['user_new_mdp'] === $_POST['conf_user_new_mdp']) {
								$this->compte->mdp = password_hash($_POST['user_new_mdp'], PASSWORD_DEFAULT);
								$this->compte->update();
							}
						}
					}
				}
				$rs = $rs->withRedirect($this->container->router->pathFor('profil_securite'));
			}
		}
		return $rs;
	}

	public function recharger(Request $rq, Response $rs, $args): Response {
		if (isset($_POST['submit'])) {
			if ($_POST['submit'] == 'Je reçois mes tokens 💰') {
				if (isset($_POST['listmonnaies']) && isset($_POST['montant']) && isset($_POST['id_compte'])) {
					$idmonnaie = intval(filter_var($_POST['listmonnaies'], FILTER_SANITIZE_NUMBER_INT));
					$qte = floatval(filter_var($_POST['montant'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
					$idc = intval(filter_var($_POST['id_compte'], FILTER_SANITIZE_NUMBER_INT));
					if (BlockChain::ajouterTransaction(1, $idc, $idmonnaie, $qte)) {
						$rs = $rs->withRedirect($this->container->router->pathFor('wallet'));
					} else echo "<p class='erreur'>Erreur dans la Blockchain</p>";
				} else echo "<p class='erreur'>Le formulaire n'est pas complet.</p>";
			}
		} else {
			$vue = new ViewProfil([$this->compte], $this->container);
			$html = $vue->render("recharger");
			$rs->getBody()->write($html);
		}
		return $rs;
	}

    public function ajoutCarteRFID(Request $rq, Response $rs, $args): Response{
        if (isset($_POST['submit'])) {
            if ($_POST['submit'] == 'Ajouter ma carte de paiement') {
                if(isset($_POST['numcarte'])) {
                    $numcarte = filter_var($_POST['numcarte'], FILTER_SANITIZE_STRING);
                    $test = Compte::where('carte_rfid', $numcarte)->first();
                    if($test == null) {
                        $id = $_SESSION['id_compte'];
                        $compteToUpdate = Compte::where("id_compte", "=", $id)->first();
                        $compteToUpdate->carte_rfid = $numcarte;
                        $compteToUpdate->update();
                        $rs = $rs->withRedirect($this->container->router->pathFor('profil_QRcode'));
                    } else {
                        $vue = new ViewProfil([$this->compte, "<p class='erreur'>La carte est déjà attribué à un compte</p>"], $this->container);
                        $html = $vue->render("ajoutCarteRFID");
                        $rs->getBody()->write($html);
                    }
                }
            }
    } else {
            $vue = new ViewProfil([$this->compte], $this->container);
            $html = $vue->render("ajoutCarteRFID");
            $rs->getBody()->write($html);
        }
        return $rs;
    }
}
