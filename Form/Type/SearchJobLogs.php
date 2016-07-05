<?php

namespace Markup\JobQueueBundle\Form\Type;

use Markup\JobQueueBundle\Model\JobLog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchJobLogs extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'id',
            'text',
            [
                'required' => false,
                'label'    => 'Uuid'
            ]
        )->add(
            'since',
            'datetime',
            [
                'widget'   => 'single_text',
                'attr'     => ['data-dtime-format' => "YYYY-MM-DDTHH:mm:ssZ"],
                'label'    => 'Added After',
                'required' => false,
                'html5'    => false,
            ]
        )->add(
            'before',
            'datetime',
            [
                'widget'   => 'single_text',
                'attr'     => ['data-dtime-format' => "YYYY-MM-DDTHH:mm:ssZ"],
                'label'    => 'Added Before',
                'required' => false,
                'html5'    => false,
            ]
        )->add(
            'status',
            'choice',
            [
                'required' => false,
                'multiple' => false,
                'empty_data' => null,
                'choices' => [
                    JobLog::STATUS_ADDED => 'Added',
                    JobLog::STATUS_RUNNING => 'Running',
                    JobLog::STATUS_FAILED => 'Failed',
                    JobLog::STATUS_COMPLETE => 'Complete',
                ]
            ]
        )->add(
            'command_configuration_id',
            'hidden',
            [
                'required' => false,
            ]
        )->add(
            'page',
            'hidden',
            [
                'required' => false,
            ]
        )->add(
            'search',
            'submit',
            ['attr' => ['class' => 'btn-info'], 'icon' => 'search', 'label' => 'Search']
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'data_class' => '\Markup\JobQueueBundle\Form\Data\SearchJobLogs',
            'csrf_protection' => false
        ]);
    }

    public function getName()
    {
        return 'phoenix_admin_search_job_logs';
    }
}
