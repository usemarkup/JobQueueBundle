<?php

namespace Markup\JobQueueBundle\Util;

final class LegacyFormHelper
{
    /**
     * @var string[]
     */
    private static $map = [
        'Symfony\Component\Form\Extension\Core\Type\TextType' => 'text',
        'Symfony\Component\Form\Extension\Core\Type\DateTimeType' => 'datetime',
        'Symfony\Component\Form\Extension\Core\Type\ChoiceType' => 'choice',
        'Symfony\Component\Form\Extension\Core\Type\HiddenType' => 'hidden',
        'Symfony\Component\Form\Extension\Core\Type\SubmitType' => 'submit',
    ];

    /**
     * @param mixed $class
     *
     * @return mixed
     */
    public static function getType($class)
    {
        if (!self::isLegacy()) {
            return $class;
        }
        if (!isset(self::$map[$class])) {
            throw new \InvalidArgumentException(sprintf('Form type with class "%s" can not be found. Please check for typos or add it to the map in LegacyFormHelper', $class));
        }
        return self::$map[$class];
    }
    /**
     * @return bool
     */
    public static function isLegacy()
    {
        return !method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix');
    }

    /**
     * LegacyFormHelper constructor.
     */
    private function __construct()
    {
    }

    private function __clone()
    {
    }
}
