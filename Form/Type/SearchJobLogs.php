<?php

namespace Markup\JobQueueBundle\Form\Type;

use Markup\JobQueueBundle\Form\Data\SearchJobLogs as SearchJobLogsData;
use Markup\JobQueueBundle\Entity\JobLog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchJobLogs extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'id',
            TextType::class,
            [
                'required' => false,
                'label'    => 'Uuid'
            ]
        )->add(
            'command',
            TextType::class,
            [
                'required' => false,
                'label'    => 'Command'
            ]
        )->add(
            'since',
            DateTimeType::class,
            [
                'widget'   => 'single_text',
                'attr'     => ['data-dtime-format' => "YYYY-MM-DDTHH:mm:ssZ"],
                'label'    => 'Added After',
                'required' => false,
                'html5'    => false,
            ]
        )->add(
            'before',
            DateTimeType::class,
            [
                'widget'   => 'single_text',
                'attr'     => ['data-dtime-format' => "YYYY-MM-DDTHH:mm:ssZ"],
                'label'    => 'Added Before',
                'required' => false,
                'html5'    => false,
            ]
        )->add(
            'status',
            ChoiceType::class,
            [
                'required' => false,
                'multiple' => false,
                'empty_data' => null,
                'choices' => [
                    'Added' => JobLog::STATUS_ADDED,
                    'Running' => JobLog::STATUS_RUNNING,
                    'Failed' => JobLog::STATUS_FAILED,
                    'Complete' => JobLog::STATUS_COMPLETE,
                ],
                'choices_as_values' => true,
            ]
        )->add(
            'search',
            SubmitType::class,
            ['attr' => ['class' => 'btn-info'], 'icon' => 'search', 'label' => 'Search']
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'data_class' => SearchJobLogsData::class,
            'csrf_protection' => false
        ]);
    }

    public function getBlockPrefix()
    {
        return 'phoenix_admin_search_job_logs';
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }
}
