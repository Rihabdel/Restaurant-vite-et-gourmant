<?php

namespace App\Service;

use App\Entity\Orders;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;




class MailService
{
    public function __construct(
        private MailerInterface $mailer
    ) {}

    public function sendOrderConfirmation(Orders $order): void
    {
        dump('MAIL SERVICE APPELÉ'); // 🔥 test


        $user = $order->getUser();

        if (!$user) {
            throw new \LogicException('L\'ordre doit être associé à un utilisateur pour envoyer une confirmation par email.');
        }
        $menu = $order->getMenu();
        $date = $order->getDeliveryDate();
        $time = $order->getDeliveryTime();

        if (!$menu || !$date || !$time) {
            throw new \LogicException('L\'ordre doit avoir un menu, une date de livraison et une heure de livraison pour envoyer une confirmation par email.');
        }

        $email = (new Email())
            ->from('noreply@vitegourmand.fr')
            ->to("test@mailtrap.io")
            ->subject('Confirmation de votre commande')
            ->html("
                <h2>Commande confirmée</h2>

                <p>Bonjour {$user->getFirstName()},</p>

                <p>Votre commande a bien été enregistrée.</p>

                <hr>

                <p><strong>Menu :</strong> {$order->getMenu()->getTitle()}</p>
                <p><strong>Convives :</strong> {$order->getNumberOfPeople()}</p>
                <p><strong>Date :</strong> {$order->getDeliveryDate()->format('d/m/Y')}</p>
                <p><strong>Heure :</strong> {$order->getDeliveryTime()->format('H:i')}</p>
        
                <p><strong>Adresse :</strong> {$order->getDeliveryAddress()}</p>
                <p><strong>Ville :</strong> {$order->getDeliveryCity()}</p>

                <hr>

                <p><strong>Livraison :</strong> {$order->getDeliveryCost()} €</p>
                <p><strong>Total :</strong> {$order->getTotalPrice()} €</p>

                <br>

                <p>Merci pour votre confiance.</p>
                <p>L'équipe Vite & Gourmand</p>
            ");

        dump('1 AVANT SEND');

        try {
            $this->mailer->send($email);
        } catch (\Throwable $e) {
            dump('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
        }
        dump('2 APRES SEND');
        dump($user->getEmail());
    }

    public function sendContactConfirmation(string $name, string $email, string $message): void
    {
        $confirmationEmail = (new Email())
            ->from('noreply@vitegourmand.fr')
            ->to($email)
            ->subject('Confirmation de votre message')
            ->html("
                <h2>Message reçu</h2>

                <p>Bonjour {$name},</p>

                <p>Nous avons bien reçu votre message :</p>

                <blockquote>
                    {$message}
                </blockquote>

                <p>Nous vous répondrons dans les plus brefs délais.</p>

                <p>L'équipe Vite & Gourmand</p>
            ");
        try {
            $this->mailer->send($confirmationEmail);
            dump('EMAIL DE CONFIRMATION ENVOYÉ'); // 🔥 test
        } catch (\Exception $e) {
            dump('Erreur lors de l\'envoi de l\'email de confirmation : ' . $e->getMessage());
        }
    }
}
