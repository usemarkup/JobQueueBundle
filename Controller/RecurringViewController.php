<?php

namespace Markup\JobQueueBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class RecurringViewController extends Controller
{
    /**
     * @deprecated Will be removed in furture release
     *
     * View the recurring job configuration
     * @return Response
     */
    public function viewAction()
    {
        return $this->render(
            'MarkupJobQueueBundle:View:recurring.html.twig',
            array(
                'recurringReader' => $this->get('markup_job_queue.reader.recurring_console_command'),
            )
        );
    }
}
