<?php
namespace Ice\MailerBundle\Template;

use Ice\MailerBundle\Entity\Mail;

abstract class AbstractTemplate implements TemplateInterface
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var array
     */
    protected $vars;

    /**
     * @var string
     */
    protected $templateName;

    /**
     * @var Mail
     */
    protected $mail;

    /**
     * @param string $templateName
     * @return AbstractTemplate
     */
    public function setTemplateName($templateName)
    {
        $this->templateName = $templateName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTemplateName()
    {
        return $this->templateName;
    }

    /**
     * @param \Ice\MailerBundle\Template\Manager $manager
     * @return AbstractTemplate
     */
    public function setManager(Manager $manager)
    {
        $this->manager = $manager;
        return $this;
    }

    /**
     * @return \Ice\MailerBundle\Template\Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param \Ice\MailerBundle\Entity\Mail $mail
     * @return AbstractTemplate
     */
    public function setMail($mail)
    {
        $this->mail = $mail;
        return $this;
    }

    /**
     * @return \Ice\MailerBundle\Entity\Mail
     */
    public function getMail()
    {
        return $this->mail;
    }

    /**
     * @param array $vars
     * @return AbstractTemplate
     */
    public function setVars(array $vars)
    {
        $this->vars = $vars;
        return $this;
    }

    /**
     * @return array
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * Returns an array in the form:
     *
     * array('john@doe.com' => 'John Doe')
     *
     * @return array
     */
    public function getFrom()
    {
        return [
            'no-reply@ice.cam.ac.uk' => 'Institute of Continuing Education'
        ];
    }

    /**
     * Returns an array in the form:
     *
     * array(
     *      'john@doe.com' => 'John Doe'
     * )
     *
     * @return array
     */
    public function getTo()
    {
        $user = $this->getMail()->getRecipient();
        return [
            $user->getEmail() => $user->getFullName()
        ];
    }

    /**
     * Returns an array in the form:
     *
     * array(
     *      'john@doe.com' => 'John Doe'
     * )
     *
     * @return array
     */
    public function getCC()
    {
        return [];
    }

    /**
     * Returns an array in the form:
     *
     * array(
     *      'john@doe.com' => 'John Doe'
     * )
     *
     * @return array
     */
    public function getBCC()
    {
        return [];
    }


}