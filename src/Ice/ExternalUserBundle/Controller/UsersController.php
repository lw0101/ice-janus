<?php

namespace Ice\ExternalUserBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController,
    FOS\RestBundle\Controller\Annotations\QueryParam,
    FOS\RestBundle\Request\ParamFetcher;

use Ice\ExternalUserBundle\Entity\User,
    Ice\ExternalUserBundle\Form\Type\SetPasswordFormType;

use Ice\ExternalUserBundle\Filter\UserFilterType;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Route,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Method,
    Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

class UsersController extends FOSRestController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("api/users", name="get_users")
     * @Method("GET")
     *
     * @ApiDoc(
     *  resource=true,
     *  description="Returns a collection of User"
     * )
     */
    public function getUsersAction()
    {
        $form = $this->createForm(new UserFilterType());

        if ($this->getRequest()->query->count()) {
            $form->bind($this->getRequest());

            if (!$form->isValid()) {
                return $this->view($form, 400);
            }

            $filter = $this->get('lexik_form_filter.query_builder_updater');

            $users = $this->getDoctrine()
                ->getRepository('IceExternalUserBundle:User')
                ->findAllFiltered($filter, $form);
        } else {
            $users = $this->getDoctrine()
                ->getRepository('IceExternalUserBundle:User')
                ->findAll();
        }

        return $this->view($users);
    }

    /**
     * @param string $term Search term
     * @return \FOS\RestBundle\View\View
     *
     * @Route("api/users/search/{term}", name="search_users")
     * @Method("GET")
     *
     * @ApiDoc(
     *   resource=true,
     *   description="Returns a collection of Users which match the search term"
     * )
     */
    public function searchUsersAction($term)
    {
        $users = $this->getDoctrine()->getRepository('IceExternalUserBundle:User')->search($term);

        return $this->view($users);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @Route("api/users", name="register_user")
     * @Method("POST")
     *
     * @ApiDoc(
     *   resource=true,
     *   description="Create a new User",
     *   input="Ice\ExternalUserBundle\Form\Type\RegistrationFormType",
     *   statusCodes={
     *      201="Returned when User successfully created",
     *      400="Returned when there is a validation error"
     *   }
     * )
     */
    public function postUsersAction()
    {
        $formName = $this->container->get('ice_external_user.registration.form.type');
        return $this->processForm($formName, new User());
    }

    /**
     * @param $username
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("api/users/{username}", name="update_user")
     * @Method("PUT")
     *
     * @ApiDoc(
     *   resource=true,
     *   description="Update an existing User",
     *   input="Ice\ExternalUserBundle\Form\Type\UpdateFormType",
     *   statusCodes={
     *     204="Returned when User successfully updated",
     *     400="Returned when there is a validation error"
     *   }
     * )
     */
    public function putUsersAction($username)
    {
        $user = $this->getUserManager()->findUserByUsernameOrEmail($username);

        if (!$user) {
            throw $this->createNotFoundException();
        }

        $formName = $this->container->get('ice_external_user.update.form.type');
        return $this->processForm($formName, $user);
    }

    private function processForm($formName, User $user)
    {
        $statusCode = $this->getRequest()->isMethod('POST') ? 201 : 204;

        $form = $this->createForm($formName, $user);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            // Only generate a username on the original registration
            if ($this->getRequest()->isMethod('POST')) {
                /** @var $generator \Ice\UsernameGeneratorBundle\UsernameGenerator */
                $generator = $this->get('ice_username.generator');
                $username = $generator->getUsernameForInitials($user->getInitials());

                $user
                    ->setUsername($username->getGeneratedUsername())
                    ->setEnabled(true); // User won't need to be enabled on update
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->view($user, $statusCode);
        }

        return $this->view($form, 400);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("api/users/authenticate", name="authenticate_user")
     * @Method("GET")
     *
     * @ApiDoc(
     *   resource=true,
     *   description="Authenticate an existing User using HTTP basic authentication",
     *   return="Ice\ExternalUserBundle\Entity\User",
     *   statusCodes={
     *      200="Returned when authentication is successful",
     *      401="Returned when authentication is not successful"
     *   }
     * )
     */
    public function getUsersAuthenticateAction()
    {
        $username = $this->getUser()->getUsername();

        /** @var $user User */
        $user = $this->getDoctrine()->getRepository('IceExternalUserBundle:User')->findOneBy(array('username' => $username));
        $user->setLastLogin(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        return $this->view($user, 200);
    }

    /**
     * @Route("api/users/{username}/password", name="set_password_user")
     * @Method("PUT")
     *
     * @ApiDoc(
     *   resource=true,
     *   description="Set the password for an existing User",
     *   input="Ice\ExternalUserBundle\Form\Type\SetPasswordFormType",
     *   statusCodes={
     *      204="Returned when User successfully updated",
     *      400="Returned when there is a validation error"
     *   }
     * )
     */
    public function putUsersPasswordAction($username)
    {
        $user = $this->getUserManager()->findUserByUsernameOrEmail($username);

        if (!$user) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(new SetPasswordFormType(), $user);
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            /** @var $manager \FOS\UserBundle\Model\UserManager */
            $manager = $this->get('fos_user.user_manager');
            $manager->updateUser($user);

            return $this->view($user, 204);
        }

        return $this->view($form, 400);
    }

    /**
     * @return \FOS\UserBundle\Doctrine\UserManager
     */
    private function getUserManager()
    {
        return $this->get('fos_user.user_manager');
    }

    /**
     * @param $username
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("api/users/{username}", name="get_user")
     * @Method("GET")
     *
     * @ApiDoc(
     *   resource=true,
     *   description="Returns a single User",
     *   return="Ice\ExternalUserBundle\Entity\User"
     * )
     */
    public function getUserAction($username)
    {
        $user = $this->getUserManager()->findUserByUsernameOrEmail($username);

        if (!$user) {
            throw $this->createNotFoundException();
        }

        return $this->view($user, 200);
    }
}
