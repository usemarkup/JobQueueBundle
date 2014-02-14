<?php

namespace Markup\Bundle\JobQueueBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RecurringViewController extends Controller
{
    /**
     * View the recurring job configuration
     * @return Response
     */
    public function viewAction()
    {
        return $this->render(
            'MarkupJobQueueBundle:View:recurring.html.twig',
            array(
                'recurringReader' => $this->get('markup_admin_job_queue_recurring_console_command_reader'),
                'resque' => $this->get('bcc_resque.resque'),
            )
        );
    }
}
