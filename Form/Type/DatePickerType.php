<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Xima\CoreBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class DatePickerType.
 *
 *
 * @author Hussein Jafferjee <hussein@jafferjee.ca>
 */
class DatePickerType extends \Sonata\CoreBundle\Form\Type\BasePickerType
{
    const FORMAT = 'd.m.Y';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array_merge($this->getCommonDefaults(), array(
            'date_format' => $this->getDefaultFormat(),
            'dp_pick_time' => false,
        )));
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $format = $options['date_format'];

        // we override format so BasePickerType properly formats the time
        $options['format'] = $format;

        parent::finishView($view, $form, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'xima_type_date_picker';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormat()
    {
        return self::FORMAT;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'text';
    }
}
