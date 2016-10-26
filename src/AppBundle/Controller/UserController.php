<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Form\UserType;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller
{
    public function showAllUsersAction() {

        return $this->render(
            'login/userlist.html.twig', // TO BE impleemented
            array('babes' => $this->getDoctrine()->getManager()->getRepository('AppBundle:User')->findAll())
        );

    }
	public function registerAction(Request $request)
	{
		// 1) build the form
		$user = new User();
		$form = $this->createForm(UserType::class, $user, array(
        'environment' => $this->get( 'kernel' )->getEnvironment()
    	));

		// 2) handle the submit (will only happen on POST)
		$form->handleRequest($request);
		if ($form->isSubmitted() && $form->isValid()) {

			// 3) Encode the password and set user salt (you could also do this via Doctrine listener)

			// Generate random salt. Warning: The algorithm may fail. TODO: Investigate bcrypt (which Symfony recommends) and other better algorithms for salting passwords.
			$salt=$this->generateSalt();

			// Hash password
			$pass_hash = $this->get('security.encoder_factory')->getEncoder(User::class)->encodePassword($form->get('password')->getData(), $salt);

			$user->setPassword($pass_hash);
			$user->setSalt($salt);

			// Authorize User as... GUEST. TODO: Remove Guest role, as it is not used.
			$user->addRole("ROLE_GUEST");
			
			$user->setIsActive(0); // YOU! SHALL NOT! PASS!!
			
			// 4) save the User!
			$em = $this->getDoctrine()->getManager();
			$em->persist($user);
			$em->flush();

			// TODO:
			// ... do any other work - like sending them an email, etc
			// maybe set a "flash" success message for the user

			// permament redirect to login.
			return $this->redirectToRoute('login',array(),301);
		}

		return $this->render(
			'login/register.html.twig',
			array('form' => $form->createView())
		);
	}
	private function validPassLen($password){
		return strlen($password)>=7;
	}

	/**
	 * Generates a Salt
	 *
	 * Generates a random 64-byte long string by converting a 32 byte binary
	 * openssl pseudo random string into a 64 byte hex string.
	 *
	 * @return null|string  the salt. If null is returned, something went wrong.
	 */
	private function generateSalt()
	{
		$salt = null;
		$isSecure = 0;
		$ATTEMPTS_LIMIT = 999; // fail-safe for infinite loop

		while (!$isSecure && $ATTEMPTS_LIMIT > 0) {
			$salt = bin2hex(openssl_random_pseudo_bytes(32, $isSecure));
			$ATTEMPTS_LIMIT--;
		}
		return $salt;
	}
}
