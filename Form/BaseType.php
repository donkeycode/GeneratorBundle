<?php

namespace Admingenerator\GeneratorBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Base class for new and edit forms.
 * 
 * @author Piotr Gołębiewski <loostro@gmail.com>
 */
abstract class BaseType extends AbstractType
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'cascade_validation' => true
        ));
    }

    /**
     * @param $credentials
     * @return bool
     */
    protected function checkCredentials($credentials, $model)
    {
        return 'AdmingenAllowed' == $credentials
            || $this->authorizationChecker->isGranted($credentials, $model);
    }

    /**
     * This method is used to pass the securityContext into custom formTypes.
     *
     * @param string|FormTypeInterface $formType
     * @return string|FormTypeInterface
     */
    protected function inject($formType)
    {
        if (is_object($formType) && method_exists($formType, 'setAuthorizationChecker')) {
            $formType->setAuthorizationChecker($this->authorizationChecker);
        }

        return $formType;
    }

    /**
     * Resolve field options.
     * 
     * @param  string       $name               Field name.
     * @param  array        $fieldOptions       Field options.
     * @param  array        $builderOptions     Form builder options.
     * @param  object|null  $optionsClass       The options class.
     * @return array                            Resolved field options.
     */
    protected function resolveOptions($name, array $fieldOptions, array $builderOptions = array(), $optionsClass = null)
    {
        $getter = 'get'.ucfirst($name).'Options';

        if ($optionsClass) {
            if (method_exists($optionsClass, 'setAuthorizationChecker')) {
                $optionsClass->setAuthorizationChecker($this->authorizationChecker);
            }

            if (method_exists($optionsClass, $getter)) {
                // merge options from options class
                $fieldOptions = $optionsClass->$getter($fieldOptions, $builderOptions);
            }
        }

        if (method_exists($this, $getter)) {
            // merge options from form type class
            $fieldOptions = $this->$getter($fieldOptions, $builderOptions);
        }
        
        // Pass on securityContext to collection types
        if (array_key_exists('type', $fieldOptions)) {
            $fieldType = $fieldOptions['type'];
            $fieldOptions['type'] = $this->inject($fieldType);
            $fieldOptions['validation_groups'] = $builderOptions['validation_groups'];
        }

        return $fieldOptions;
    }

    public function getName()
    {
        return 'admingenerator_base_type';
    }
}
