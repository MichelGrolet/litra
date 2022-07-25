<?php
declare(strict_types=1);

namespace litra\utilitaires;

use litra\model\Transactions;
use litra\model\Monnaie;
use litra\model\Compte;
use Illuminate\Database\Capsule\Manager as DB;

class BlockChain
{
    // Retourne le nombre de transactions actuel (nombre de lignes dans la table bdd)
    public static function nbTransactions()
    {
        $nbTransactions = Transactions::count();
        return $nbTransactions;
    }

    // Retourne le hash de la dernière transaction (Le plus grans id)
    /*public static function dernierHashTransac()
    {
        $idMax = Transactions::max('id_transac');
        return Transactions::where('id_transac', '=', $idMax)->get("transac_hash")->toArray()[0]["transac_hash"];
    }*/

    // Hash la transaction
    public static function hashTransac(Transactions $transaction)
    {
        $transac_prec = "";
        if ($transaction->id_transac > 1) {
            $transac_prec = Transactions::where("id_transac", "=", ($transaction->id_transac - 1))->first()->transac_hash;
        }
        return md5($transac_prec."-transaction_N:".$transaction->id_transac."emetteur:".$transaction->id_emetteur."recepteur:".$transaction->id_recepteur."monnaie:".$transaction->id_monnaie."qte:".$transaction->qte_monnaie."created:".$transaction->transac_date);
    }

    // Rend le nombre d'unite de la monnaie possedee par le compte
    public static function soldeCompteMonnaie($id_compte, $id_monnaie)
    {
        $solde = 0;
        // On recupere toutes les lignes de la table transaction dans lesquelles l'id du compte est impliquee
        $transactionsDuCompte = Transactions::where(function ($q) use ($id_compte) {
            $q->where('id_emetteur', '=', $id_compte)->orWhere('id_recepteur', '=', $id_compte);
        })->where('id_monnaie', '=', $id_monnaie)->get();
        // On applique au solde les ajouts et suppression
        foreach ($transactionsDuCompte as $transaction) {
            if ($id_compte == $transaction->id_emetteur) {
                $solde -= $transaction->qte_monnaie;
            } else {
                $solde += $transaction->qte_monnaie;
            }
        }
        return $solde;
    }

    // Prototype de la methode du dessus mais avec adapatation 1/N monnaie(s), possiblement inutile
    /* public static function soldeCompteMonnaie($id_compte, $id_monnaie = 0) {
      $solde = 0;
      // La monnaie n'est pas precisee, par defaut 0, on prend toutes les monnaies
      if ($id_monnaie == 0){
          $transactionsDuCompte = Transactions::where(function ($q) use ($id_compte) {
              $q->where('id_emetteur', '=', $id_compte)->orWhere('id_recepteur', '=', $id_compte);
          })->get();
      }
      // Si la monnaie est precisee, on donne le solde de cette seule monnaie
      else {
          $transactionsDuCompte = Transactions::where(function ($q) {
              $q->where('id_emetteur', '=', $id_compte)->orWhere('id_recepteur', '=', $id_compte);
          })->where('id_monnaie', '=', $id_monnaie)->get();
      }
      // On applique au solde les ajouts et suppression
      foreach ($transactionsDuCompte as $transaction) {
          if ($id_compte == $transaction->id_emetteur){
              $solde -= $transaction->qte_monnaie;
          } else {
              $solde += $transaction->qte_monnaie;
          }
      }
      return $solde;}
    */
    
    // Retourne un tableau qui contient des tableaux (nom_monnaie, valeur, qte) des monnaies possedees par le compte
    public static function walletComposition($id_compte)
    {
        if (Blockchain::blockchainValide()) {
            $tabMonnaiesComplet = [];
            $idMonnaiesCompte = Transactions::where(function ($q) use ($id_compte) {
                $q->where('id_emetteur', '=', $id_compte)->orWhere('id_recepteur', '=', $id_compte);
            })->distinct('id_monnaie')->get(['id_monnaie']);
            foreach ($idMonnaiesCompte as $monnaie) {
                $nom = Monnaie::where('id_monnaie', '=', $monnaie['id_monnaie'])->get(['nom_monnaie'])->toArray();
                $valeur = Monnaie::where('id_monnaie', '=', $monnaie['id_monnaie'])->get(['valeur'])->toArray();
                $qte = Blockchain::soldeCompteMonnaie($id_compte, $monnaie['id_monnaie']);
                $monnaieComplete =['id_monnaie' => $monnaie['id_monnaie'], 'nom_monnaie' => $nom[0]['nom_monnaie'], 'valeur' =>$valeur[0]['valeur'], 'qte' => $qte];
                array_push($tabMonnaiesComplet, $monnaieComplete);
            }
            return $tabMonnaiesComplet;
        } else {
            return null;
        }
    }

    
    public static function ajouterTransaction(int $id_emetteur, int $id_recepteur, int $id_monnaie, float $qte_monnaie)
    {
        echo "tentative d'ajout de transaction : ".$id_emetteur." ".$id_recepteur." ".$id_monnaie." ".$qte_monnaie."\n";
        if (Blockchain::blockchainValide()) {

            // Simple mesure de securite, sera deja de cette taille en theorie
            $qte = number_format(round($qte_monnaie, 2), 2, '.', '');

            // La transaction implique de la monnaie
            if ($qte <= 0 || (Monnaie::where("id_monnaie", "=", $id_monnaie)->first() == null)) {
                return false;
            }

            // Le compte qui recoit doit exister, tout comme celui qui envoit
            if ((Compte::where("id_compte", "=", $id_recepteur)->first() == null) || (Compte::where("id_compte", "=", $id_emetteur)->first() == null)) {
                echo "<p class='erreur'>Le compte auquel vous tentez d'envoyer de la monnaie n'existe pas.</p>";
                return false;
            }

            // La transaction se fait d'un compte a un autre
            if ($id_emetteur == $id_recepteur) {
                return false;
            }

            // On verifie que l'emetteur possede l'argent qu'il veut envoyer, sauf s'il s'agit de l'admin banque
            $sommePossedee = BlockChain::soldeCompteMonnaie($id_emetteur, $id_monnaie);
            if ($sommePossedee < $qte) {
                $compteEmet = Compte::where("id_compte", "=", $id_emetteur)->first();
                if ($compteEmet['privilege'] != 2) {
                    return false;
                }
            }
            
                $transaction = new Transactions();
                $transaction->id_emetteur = $id_emetteur;
                $transaction->id_recepteur = $id_recepteur;
                $transaction->id_monnaie = $id_monnaie;
                $transaction->qte_monnaie = $qte;
                $transaction->transac_date = date("Y-m-d H:i:s");
                // save avant le hash pour avoir l'id de l'auto increment
                $transaction->save();
                $transaction->transac_hash = BlockChain::hashTransac($transaction);
                $transaction->update();
        } else {
            return false;
        }
        return true;
    }
    
    public static function blockchainValide()
    {
        // Verifier le genesis block ?
        // Bien vérifier la premiere ligne

        // Verification globale de la chaine
        $bool = true;
        for ($i = 1; $i <= Blockchain::nbTransactions(); $i++) {
            $transactionCourante = Transactions::where('id_transac', '=', $i)->first();
            $transactionPrecedente = null;
            if ($i > 1) {
                $transactionPrecedente = Transactions::where('id_transac', '=', $i-1)->first();
            }
            

            // La transaction courante possede bien le hash de la transaction precedente
            /*if ($transactionPrecedente != null) {
                if ($transactionPrecedente->transac_hash !== $transactionCourante->prec_transac_hash) {
                    $bool = false;
                    break;
                }
            }*/

            // Le hash de la transaction courante est toujours correct
            if ($transactionCourante["transac_hash"] !== Blockchain::hashTransac($transactionCourante)) {
                $bool = false;
                break;
            }
        }
        // Id de la prochaine transaction d'apres l'auto-increment
        $nextIdTransac  = DB::select("SHOW TABLE STATUS LIKE 'transactions'")[0]->Auto_increment;
        if ($nextIdTransac != Transactions::max('id_transac')+1) {
            $bool = false;
        }
        return $bool;
    }
}
