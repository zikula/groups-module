<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\GroupsModule\Container;

use ModUtil;
use Symfony\Component\Routing\RouterInterface;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Core\LinkContainer\LinkContainerInterface;
use Zikula\PermissionsModule\Api\PermissionApi;

class LinkContainer implements LinkContainerInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var PermissionApi
     */
    private $permissionApi;

    /**
     * LinkContainer constructor.
     *
     * @param TranslatorInterface $translator    TranslatorInterface service instance
     * @param RouterInterface     $router        RouterInterface service instance
     * @param PermissionApi       $permissionApi PermissionApi service instance
     */
    public function __construct(TranslatorInterface $translator, RouterInterface $router, PermissionApi $permissionApi)
    {
        $this->translator = $translator;
        $this->router = $router;
        $this->permissionApi = $permissionApi;
    }

    /**
     * get Links of any type for this extension
     * required by the interface
     *
     * @param string $type
     * @return array
     */
    public function getLinks($type = LinkContainerInterface::TYPE_ADMIN)
    {
        $method = 'get' . ucfirst(strtolower($type));
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return [];
    }

    /**
     * get the Admin links for this extension
     *
     * @return array
     */
    private function getAdmin()
    {
        $links = [];

        if ($this->permissionApi->hasPermission($this->getBundleName() . '::', '::', ACCESS_READ)) {
            $links[] = [
                'url' => $this->router->generate('zikulagroupsmodule_group_adminlist'),
                'text' => $this->translator->__('Groups list'),
                'icon' => 'list'
            ];
        }
        if ($this->permissionApi->hasPermission($this->getBundleName() . '::', '::', ACCESS_ADD)) {
            $links[] = [
                'url' => $this->router->generate('zikulagroupsmodule_group_create'),
                'text' => $this->translator->__('New group'),
                'icon' => 'plus'
            ];
        }
        if ($this->permissionApi->hasPermission($this->getBundleName() . '::', '::', ACCESS_ADMIN)) {
            $links[] = [
                'url' => $this->router->generate('zikulagroupsmodule_config_config'),
                'text' => $this->translator->__('Settings'),
                'icon' => 'wrench'
            ];
        }

        return $links;
    }

    /**
     * get the Account links for this extension
     *
     * @return array
     */
    private function getUser()
    {
        $links = [];
        $links[] = [
            'url' => $this->router->generate('zikulagroupsmodule_group_list'),
            'text' => $this->translator->__('Group list'),
            'icon' => 'group'
        ];
        if ($this->permissionApi->hasPermission($this->getBundleName() . '::', '::', ACCESS_EDIT)) {
            $links[] = [
                'url' => $this->router->generate('zikulagroupsmodule_group_adminlist'),
                'text' => $this->translator->__('Groups admin'),
                'icon' => 'list'
            ];
        }

        return $links;
    }

    /**
     * get the Account links for this extension
     *
     * @return array
     */
    private function getAccount()
    {
        $links = [];

        // Check if there is at least one group to show
        $groups = ModUtil::apiFunc($this->getBundleName(), 'user', 'getallgroups');
        if ($groups) {
            $links[] = [
                'url' => $this->router->generate('zikulagroupsmodule_group_list'),
                'text' => $this->translator->__('Groups manager'),
                'icon' => 'group'
            ];
        }

        return $links;
    }

    /**
     * set the BundleName as required by the interface
     *
     * @return string
     */
    public function getBundleName()
    {
        return 'ZikulaGroupsModule';
    }
}
