<?php
/**
 * Controller for configurable forms (feedback etc).
 *
 * PHP version 7
 *
 * @category VuFind
 * @package  Controller
 * @author   Josiah Knoll <jk1135@ship.edu>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org Main Site
 */
namespace VuFind\Controller;

use Laminas\Mail\Address;
use Laminas\View\Model\ViewModel;
use VuFind\Exception\Mail as MailException;
use VuFind\Form\Form;

/**
 * Controller for configurable forms (feedback etc).
 *
 * @category VuFind
 * @package  Controller
 * @author   Josiah Knoll <jk1135@ship.edu>
 * @author   Samuli Sillanpää <samuli.sillanpaa@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class FeedbackController extends AbstractBase
{
    /**
     * Feedback form class
     *
     * @var string
     */
    protected $formClass = \VuFind\Form\Form::class;

    /**
     * Display Feedback home form.
     *
     * @return ViewModel
     */
    public function homeAction()
    {
        return $this->forwardTo('Feedback', 'Form');
    }

    /**
     * Handles rendering and submit of dynamic forms.
     * Form configurations are specified in FeedbackForms.yaml.
     *
     * @return mixed
     */
    public function formAction()
    {
        $formId = $this->params()->fromRoute('id', $this->params()->fromQuery('id'));
        if (!$formId) {
            $formId = 'FeedbackSite';
        }

        $user = $this->getUser();

        $form = $this->serviceLocator->get($this->formClass);
        $params = [];
        if ($refererHeader = $this->getRequest()->getHeader('Referer')) {
            $params['referrer'] = $refererHeader->getFieldValue();
        }
        if ($userAgentHeader = $this->getRequest()->getHeader('User-Agent')) {
            $params['userAgent'] = $userAgentHeader->getFieldValue();
        }
        $form->setFormId($formId, $params);

        if (!$form->isEnabled()) {
            throw new \VuFind\Exception\Forbidden("Form '$formId' is disabled");
        }

        if (!$user && $form->showOnlyForLoggedUsers()) {
            return $this->forceLogin();
        }

        $view = $this->createViewModel(compact('form', 'formId', 'user'));
        $view->useCaptcha
            = $this->captcha()->active('feedback') && $form->useCaptcha();

        $params = $this->params();
        $form->setData($params->fromPost());

        if (!$this->formWasSubmitted('submit', $view->useCaptcha)) {
            $form = $this->prefillUserInfo($form, $user);
            return $view;
        }

        if (!$form->isValid()) {
            return $view;
        }

        $fields = $form->mapRequestParamsToFieldValues($this->params()->fromPost());
        $emailMessage = $this->getViewRenderer()->partial(
            'Email/form.phtml',
            compact('fields')
        );

        [$senderName, $senderEmail] = $this->getSender($form);

        $replyToName = $params->fromPost(
            'name',
            $user ? trim($user->firstname . ' ' . $user->lastname) : null
        );
        $replyToEmail = $params->fromPost(
            'email',
            $user ? $user->email : null
        );

        $recipients = $form->getRecipient($params->fromPost());

        $emailSubject = $form->getEmailSubject($params->fromPost());

        $sendSuccess = true;
        foreach ($recipients as $recipient) {
            [$success, $errorMsg] = $this->sendEmail(
                $recipient['name'],
                $recipient['email'],
                $senderName,
                $senderEmail,
                $replyToName,
                $replyToEmail,
                $emailSubject,
                $emailMessage
            );

            $sendSuccess = $sendSuccess && $success;
            if (!$success) {
                $this->flashMessenger()->addErrorMessage($errorMsg);
            }
        }

        if ($sendSuccess) {
            $view->setTemplate('feedback/response');
            $view->emailMessage = $emailMessage;
        }

        return $view;
    }

    /**
     * Prefill form sender fields for logged in users.
     *
     * @param Form  $form Form
     * @param array $user User
     *
     * @return Form
     */
    protected function prefillUserInfo($form, $user)
    {
        if ($user) {
            $form->setData(
                [
                 'name' => $user->firstname . ' ' . $user->lastname,
                 'email' => $user['email']
                ]
            );
        }
        return $form;
    }

    /**
     * Send form data as email.
     *
     * @param string $recipientName  Recipient name
     * @param string $recipientEmail Recipient email
     * @param string $senderName     Sender name
     * @param string $senderEmail    Sender email
     * @param string $replyToName    Reply-to name
     * @param string $replyToEmail   Reply-to email
     * @param string $emailSubject   Email subject
     * @param string $emailMessage   Email message
     *
     * @return array with elements success:boolean, errorMessage:string (optional)
     */
    protected function sendEmail(
        $recipientName,
        $recipientEmail,
        $senderName,
        $senderEmail,
        $replyToName,
        $replyToEmail,
        $emailSubject,
        $emailMessage
    ) {
        try {
            $mailer = $this->serviceLocator->get(\VuFind\Mailer\Mailer::class);
            $mailer->send(
                new Address($recipientEmail, $recipientName),
                new Address($senderEmail, $senderName),
                $emailSubject,
                $emailMessage,
                null,
                !empty($replyToEmail)
                    ? new Address($replyToEmail, $replyToName) : null
            );
            return [true, null];
        } catch (MailException $e) {
            return [false, $e->getMessage()];
        }
    }

    /**
     * Return email sender from configuration.
     *
     * @param Form $form Form
     *
     * @return array with name, email
     */
    protected function getSender(Form $form)
    {
        $config = $this->getConfig()->Feedback;
        $email = $form->getEmailFromAddress()
            ?: $config->sender_email ?? 'noreply@vufind.org';
        $name = $form->getEmailFromName()
            ?: $config->sender_name ?? 'VuFind Feedback';

        return [$name, $email];
    }
}
