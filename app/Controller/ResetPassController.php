<?php

namespace Controller;

use \W\Controller\Controller;
use W\Manager\Manager;


class ResetPassController extends Controller
{

    public function lostPassword()
    {
        $displayMessage = false;

        if (isset($_POST['reset-pass']) && !empty($_POST['mail'])) {
            /* Si on a cliqué sur le submit && que le champ `mail` contient une saisie utilisateur */
            /* On teste l'existence du mail en base de données */
            /* Ici, on pourrait faire une validation de $_POST['mail'] pour s'assurer
              que le mail soit valide avant de tester son existence en DB */

            $userManager = new \Manager\UserManager();
            $mailExists = $userManager->emailExists($_POST['mail']);


            if ($mailExists) {
                $token = \W\Security\StringUtils::randomString(32);
                $uId = $userManager->getIdForMail($_POST['mail']); /* On aurait pu tout placer dans une fonction getUserForMail() et tester si un utilisateur est renvoyé pour un mail */

                $this->insertTokenReplaceOld($uId, $token);
                // Envoi du mail
                $this->sendRecoveryLink($_POST['mail'], $token);
            }
        }

        $this->show('loginPage/lostPass');
    }

    private function insertTokenReplaceOld($uId, $token)
    {
        $recoveryTokenManager = new \Manager\RecoveryTokenManager();
        if($recoveryTokenManager->tokenExistsForUser($uId)) {
            $recoveryTokenManager->deleteTokenForUser($uId);
        }
        $recoveryTokenManager->insert(['id_user' => $uId, 'token' => $token]);
    }

    public function sendRecoveryLink($mail, $token)
    {
        $mailer = new \PHPMailer();

        $mailer->isSMTP();                                          // On va se servir de SMTP
        $mailer->Host = 'smtp.gmail.com';                       // Serveur SMTP
        $mailer->SMTPAuth = true;                                   // Active l'autentification SMTP
        $mailer->Username = 'lor.n.shops@gmail.com';               // SMTP username
        $mailer->Password = '1esquimauentongs%';                           // SMTP password
        $mailer->SMTPSecure = 'tls';                                // TLS Mode
        $mailer->Port = 587;                                        // Port TCP à utiliser

        $mailer->Sender='lor.n.shops@gmail.com';
        $mailer->setFrom('lor.n.shops@gmail.com', 'Bonjour, voici votre nouveau mot de passe', false);
        $mailer->addAddress($mail, 'Joe User');    // Ajouter un destinataire

        $mailer->isHTML(true);                                       // Set email format to HTML

        $mailer->Subject = 'Sujet de l\'email';
        $mailer->Body    = 'Message au format html : <a href="'.Controller::generateUrl('reset', [':tk' => $token]).'">Lien vers la page de mot de passe</a>';
        $mailer->AltBody = 'Le message en texte brut, pour les clients qui ont désactivé l\'affichage HTML';

        $mailer->send();

        if (!$mailer->send()) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        } else {
            echo "Message sent!";
        }
        
        $this->show('loginPage/resetPass');
    }

    public function resetPassword($tk)
    {


        $tk; // token
        $recoveryTokenManager = new \Manager\RecoverytokenManager();
        // On rÈcupËre l'id utilisateur en fonction du token
        $idUser = $recoveryTokenManager->getUserIdByToken($tk);
        if ($idUser === false) {
            /* Si ce token n'a jamais ÈtÈ Èmis, on redirige */
            $this->redirectToRoute('login');
        }
        /* A partir de la, je sais que mon token existe en DB */

        /* Si on a soumis le nouveau mot de passe */
        if (isset($_POST['change_password'])) {
            
                /* Hash du nouveau mot de passe */
                $security = new \W\Security\AuthentificationManager();
                $passHash = $security->hashPassword($_POST['new_pass']);

                /*  Mise ‡ jour du mot de passe */

                // Model pour insertion en base de donnees
                $usersModel = new \Manager\UserManager();

                $data = ['password' => $passHash];
                var_dump($data);

            $usersModel->update($data, $idUser);

                /* Suppression du token maintenant inutile */
                $recoveryTokenManager->deleteTokenForUser($tk);

                /* Pour affichage du message */
                $passUpdated = true;
                $this->redirectToRoute('connexion');
            }
            // Si j'ai une erreur
            //$this->show('loginPage/resetPass' /*['errors' => $errors]*/);
        //}
        $this->show('loginPage/resetPass');

    }
}