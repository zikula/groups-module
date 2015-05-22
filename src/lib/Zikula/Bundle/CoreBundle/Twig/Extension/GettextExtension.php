<?php
/**
 * Copyright Zikula Foundation 2015 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 * @package Zikula
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

namespace Zikula\Bundle\CoreBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * GettextExtension
 *
 * @todo add automatic domain detection (from the environment)
 */
class GettextExtension extends \Twig_Extension
{
    private $container;
    private $translator;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->translator = $this->container->get('translator'); 
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            '__' => new \Twig_Function_Method($this, '__', array('needs_environment' => true)),
            '_n' => new \Twig_Function_Method($this, '_n', array('needs_environment' => true)),
            '__f' => new \Twig_Function_Method($this, '__f', array('needs_environment' => true)),
            '_fn' => new \Twig_Function_Method($this, '_fn', array('needs_environment' => true)),
            '__p' => new \Twig_Function_Method($this, '__p', array('needs_environment' => true)),
            '__fp' => new \Twig_Function_Method($this, '__fp', array('needs_environment' => true)),
            '_fnp' => new \Twig_Function_Method($this, '_fnp', array('needs_environment' => true)),
            'no__' => new \Twig_Function_Method($this, 'no__'),
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'zikula_gettext';
    }

    /**
     * @see __()
     */
    public function __(\Twig_Environment $env, $message, $domain = null, $locale = null)
    {
        return $this->translator->trans($message, array(), $domain, $locale);
    }

    /**
     * @see __p()
     */
    public function __p(\Twig_Environment $env, $context, $message, $domain = null)
    {
        return \__p($context, $message, $domain);
    }

    /**
     * @see __f()
     */
    public function __f(\Twig_Environment $env, $message, $params, $domain = null, $locale = null)
    {
        return $this->translator->trans($message, $params, $domain, $locale);
    }

    /**
     * @see __fp()
     */
    public function __fp(\Twig_Environment $env, $context, $message, $params, $domain = null)
    {
        return \__fp($context, $message, $params, $domain);
    }

    /**
     * @see _fn()
     */
    public function _fn(\Twig_Environment $env, $singular, $plural, $count, $params, $domain = null, $locale = null)
    {
		$id = ($count > 1) ? $singular : $plural ;
		return $this->translator->transChoice($id, $count, $params, $domain, $locale);
    }

    /**
     * @see _fpn()
     */
    public function _fnp(\Twig_Environment $env, $context, $singular, $plural, $count, $params, $domain = null)
    {
        return \_fnp($context, $singular, $plural, $count, $params, $domain);
    }

    /**
     * @see _n()
     */
    public function _n(\Twig_Environment $env, $singular, $plural, $count, $domain = null, $locale = null)
    {
		$id = ($count > 1) ? $singular : $plural ;
		return $this->translator->transChoice($id, $count, array(), $domain, $locale);
    }

    /**
     * @see _np()
     */
    public function _np(\Twig_Environment $env, $context, $singular, $plural, $count, $domain = null)
    {
        return \_np($context, $singular, $plural, $count, $domain);
    }


    /**
     * @see no__()
     */
    public function no__($msgid)
    {
        return $msgid;
    }
}