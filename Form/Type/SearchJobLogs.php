<?php

namespace Markup\JobQueueBundle\Form\Type;

use Markup\JobQueueBundle\Form\Data\SearchJobLogs as SearchJobLogsData;
use Markup\JobQueueBundle\Model\JobLog;
use Markup\JobQueueBundle\Util\LegacyFormHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchJobLogs extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'id',
            LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\TextType'),
            [
                'required' => false,
                'label'    => 'Uuid'
            ]
        )->add(
            'since',
            LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\DateTimeType'),
            [
                'widget'   => 'single_text',
                'attr'     => ['data-dtime-format' => "YYYY-MM-DDTHH:mm:ssZ"],
                'label'    => 'Added After',
                'required' => false,
                'html5'    => false,
            ]
        )->add(
            'before',
            LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\DateTimeType'),
            [
                'widget'   => 'single_text',
                'attr'     => ['data-dtime-format' => "YYYY-MM-DDTHH:mm:ssZ"],
                'label'    => 'Added Before',
                'required' => false,
                'html5'    => false,
            ]
        )->add(
            'status',
            LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\ChoiceType'),
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
            'command_configuration_id',
            LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\HiddenType'),
            [
                'required' => false,
            ]
        )->add(
            'page',
            LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\HiddenType'),
            [
                'required' => false,
            ]
        )->add(
            'search',
            LegacyFormHelper::getType('Symfony\Component\Form\Extension\Core\Type\SubmitType'),
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
