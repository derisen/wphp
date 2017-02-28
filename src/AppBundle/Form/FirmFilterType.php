<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Form;

use AppBundle\Entity\Firmrole;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Description of FirmFilterType
 *
 * @author mjoyce
 */
class FirmFilterType extends AbstractType {

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry) {
        $this->em = $registry->getManager();
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $firmRoleRepo = $this->em->getRepository(Firmrole::class);
        $roles = $firmRoleRepo->findAll(array(
            'name' => 'ASC',
        ));
        $builder->add('firm_role', ChoiceType::class, array(
            'choices' => $roles,
            'choice_label' => function($value, $key, $index) {
                return $value->getName();
            },
            'choice_value' => function($value) {
                if($value) {
                    return $value->getId();
                }
            },
            'label' => 'Firm Role',
            'required' => false,
            'expanded' => true,
            'multiple' => true,
        ));
        $builder->add('firm_name', TextType::class, array(
            'label' => 'Firm Name',
            'required' => false,
        ));
        $builder->add('firm_address', TextType::class, array(
            'label' => 'Firm Address',
            'required' => false,
        ));
    }

}